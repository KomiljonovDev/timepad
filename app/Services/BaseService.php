<?php

namespace App\Services;

use App\Repositories\Contracts\RepositoryInterface;
use App\Services\Contracts\ServiceInterface;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Exception;
use Illuminate\Support\Facades\Log;

/**
 * Class BaseService
 *
 * @package App\Services
 */
abstract class BaseService implements ServiceInterface
{
    /**
     * @var array
     */
    protected array $relations = [];

    /**
     * @var array
     */
    protected array $attributes = [];

    /**
     * @var array
     */
    protected array $filter_fields = [];

    /**
     * @var RepositoryInterface
     */
    protected RepositoryInterface $repo;

    /**
     * BaseService constructor.
     *
     * @param RepositoryInterface $repository
     */
    public function __construct(RepositoryInterface $repository)
    {
        $this->repo = $repository;
    }

    /**
     * Set relation to be loaded with the model.
     *
     * @param string $relation
     * @return static
     */
    public function setRelation(string $relation): static
    {
        in_array($relation, $this->relations) ?: $this->relations[] = $relation;
        return $this;
    }

    /**
     * Set attributes to be selected from the model.
     *
     * @param array|string $attributes
     * @return static
     */
    public function setAttributes(array|string $attributes): static
    {
        if (is_array($attributes)) {
            foreach ($attributes as $attribute) {
                in_array($attribute, $this->attributes) ?: $this->attributes[] = $attribute;
            }
        } else {
            in_array($attributes, $this->attributes) ?: $this->attributes[] = $attributes;
        }
        return $this;
    }

    /**
     * Set filters for the query.
     *
     * @param array $filters
     * @return static
     */
    public function setFilters(array $filters): static
    {
        $this->filter_fields = $filters;
        return $this;
    }

    /**
     * Get paginated results or collection based on the current settings.
     *
     * @return Collection|LengthAwarePaginator
     */
    public function get(): Collection|LengthAwarePaginator
    {
        $query = $this->repo->getQuery();
        $query = $this->relation($query, $this->relations);
        $query = $this->filter($query, $this->filter_fields);
        $query = $this->sort($query);
        $query = $this->select($query, $this->attributes);
        return $this->repo->getPaginate($query, $this->filter_fields['per_page'] ?? null);
    }

    /**
     * Get a list of items based on the provided parameters.
     *
     * @param array $params
     * @return Collection
     */
    public function list(array $params): Collection
    {
        $query = $this->repo->getQuery();
        $query = $this->filter($query, array_merge($this->filter_fields, $params));
        $query = $this->relation($query, $this->relations);
        $query = $this->select($query, $this->attributes);
        $query = $this->sort($query);
        return $query->get();
    }

    /**
     * Apply relation loading to the query builder.
     *
     * @param Builder $query
     * @param array|string|null $relations
     * @return Builder
     */
    public function relation(Builder $query, array|string|null $relations = null): Builder
    {
        if ($relations) {
            if (is_array($relations)) {
                $query->with($relations);
            } else {
                $query->with([$relations]);
            }
        }
        return $query;
    }

    /**
     * Apply column selection to the query builder.
     *
     * @param Builder $query
     * @param array|null $attributes
     * @return Builder
     */
    public function select(Builder $query, ?array $attributes = null): Builder
    {
        if ($attributes && !empty($attributes)) {
            $query->select($attributes);
        }
        return $query;
    }

    /**
     * Apply filters to the query builder.
     *
     * @param Builder $query
     * @param array $filters
     * @return Builder
     */
    public function filter(Builder $query, array $filters): Builder
    {
        foreach ($filters as $key => $item) {
            // Skip empty filters
            if (!isset($item['value']) || $item['value'] === '' || $item['value'] === null) {
                continue;
            }

            $value = $item['value'];

            try {
                switch ($item['type'] ?? '') {
                    case 'string':
                        $query->where($key, 'like', '%' . $value . '%');
                        break;

                    case 'exact_string':
                        $query->where($key, '=', $value);
                        break;

                    case 'array':
                        $query->whereIn($key, $value);
                        break;

                    case 'not_in_array':
                        $query->whereNotIn($key, $value);
                        break;

                    case 'intarray':
                        if (!in_array(0, $value)) {
                            $query->where($key, '&&', "{" . implode(',', $value) . "}");
                        }
                        break;

                    case 'intarrayand':
                        $query->where($key, '@>', "{" . implode(',', $value) . "}");
                        break;

                    case 'number':
                    case 'bool':
                        $query->where($key, $value);
                        break;

                    case 'day':
                        $query->where($key, '>=', Carbon::now()->subDays($value));
                        break;

                    case 'to':
                        $query->where($key, '<=', $value);
                        break;

                    case 'date':
                        $query->whereDate($key, '=', $value);
                        break;

                    case 'datefrom':
                        $column = str_replace("_from", "", $key);
                        $query->whereDate($column, '>=', $value);
                        break;

                    case 'dateto':
                        $column = str_replace("_to", "", $key);
                        $query->whereDate($column, '<=', $value);
                        break;

                    case 'from':
                        $query->where($key, '>=', $value);
                        break;

                    case 'json':
                        if (isset($item['json_type']) && $item['json_type'] == 'array') {
                            if (isset($item['search']) && $item['search'] == 'string') {
                                $query->where('data->' . $key, 'like', '%' . $value . '%');
                            } elseif (isset($item['search']) && $item['search'] == 'number') {
                                $query->where('data->' . $key, $value);
                            }
                        }
                        break;

                    case 'between':
                        if (isset($item['min']) && isset($item['max'])) {
                            $query->whereBetween($key, [$item['min'], $item['max']]);
                        }
                        break;

                    case 'isNull':
                        $query->whereNull($key);
                        break;

                    case 'isNotNull':
                        $query->whereNotNull($key);
                        break;

                    case 'custom':
                        if (isset($item['callback']) && is_callable($item['callback'])) {
                            call_user_func($item['callback'], $query, $value);
                        }
                        break;
                }
            } catch (Exception $e) {
                Log::error('Error applying filter: ' . $e->getMessage(), [
                    'filter' => $key,
                    'value' => $value,
                    'type' => $item['type'] ?? 'undefined'
                ]);
            }
        }

        return $query;
    }

    /**
     * Apply sorting to the query builder.
     *
     * @param Builder $query
     * @return Builder
     */
    public function sort(Builder $query): Builder
    {
        try {
            if (isset($this->filter_fields['sort_by']) && isset($this->filter_fields['order_by'])) {
                $sortBy = $this->filter_fields['sort_by'];
                $orderBy = in_array(strtolower($this->filter_fields['order_by']), ['asc', 'desc'])
                    ? $this->filter_fields['order_by']
                    : 'desc';

                $query->orderBy($sortBy, $orderBy);
            } else {
                $query->orderBy('id', 'desc');
            }
        } catch (Exception $e) {
            Log::error('Error applying sort: ' . $e->getMessage());
            $query->orderBy('id', 'desc');
        }

        return $query;
    }

    /**
     * Create a new model with the given parameters.
     *
     * @param array $params
     * @return Model
     */
    public function create(array $params): Model
    {
        try {
            return $this->repo->store($params);
        } catch (Exception $e) {
            Log::error('Error creating model: ' . $e->getMessage(), ['params' => $params]);
            throw $e;
        }
    }

    /**
     * Retrieve a model by its ID.
     *
     * @param int|string $id
     * @return Model|null
     */
    public function show($id): ?Model
    {
        try {
            return $this->repo->getById($id);
        } catch (Exception $e) {
            Log::error('Error retrieving model: ' . $e->getMessage(), ['id' => $id]);
            return null;
        }
    }

    /**
     * Update a model with the given parameters.
     *
     * @param array $params
     * @param int|string $id
     * @return Model|bool
     */
    public function edit(array $params, $id): Model|bool
    {
        try {
            return $this->repo->update($params, $id);
        } catch (Exception $e) {
            Log::error('Error updating model: ' . $e->getMessage(), ['id' => $id, 'params' => $params]);
            return false;
        }
    }

    /**
     * Delete a model by its ID.
     *
     * @param int|string $id
     * @return bool
     */
    public function delete($id): bool
    {
        try {
            return $this->repo->destroy($id);
        } catch (Exception $e) {
            Log::error('Error deleting model: ' . $e->getMessage(), ['id' => $id]);
            return false;
        }
    }

    /**
     * Insert multiple rows into the database.
     *
     * @param array $rows
     * @return bool
     */
    public function insert(array $rows): bool
    {
        try {
            return $this->repo->insert($rows);
        } catch (Exception $e) {
            Log::error('Error inserting multiple rows: ' . $e->getMessage(), ['count' => count($rows)]);
            return false;
        }
    }

    /**
     * Create or update multiple records in a transaction.
     *
     * @param array $records
     * @return bool
     */
    public function bulkCreateOrUpdate(array $records): bool
    {
        return $this->transaction(function () use ($records) {
            $success = true;

            foreach ($records as $record) {
                // If the record has an ID, update it
                if (isset($record['id'])) {
                    $id = $record['id'];
                    unset($record['id']);

                    $result = $this->edit($record, $id);
                    if ($result === false) {
                        $success = false;
                    }
                } else {
                    // Otherwise create a new record
                    try {
                        $this->create($record);
                    } catch (Exception $e) {
                        $success = false;
                    }
                }

                // If any operation failed, abort the transaction
                if (!$success) {
                    return false;
                }
            }

            return $success;
        });
    }

    /**
     * Delete multiple records by their IDs.
     *
     * @param array $ids
     * @return bool
     */
    public function bulkDelete(array $ids): bool
    {
        return $this->transaction(function () use ($ids) {
            $success = true;

            foreach ($ids as $id) {
                $result = $this->delete($id);
                if (!$result) {
                    $success = false;
                    break;
                }
            }

            return $success;
        });
    }

    /**
     * Execute a complex operation within a database transaction.
     *
     * @param callable $callback
     * @return mixed
     */
    public function transaction(callable $callback): mixed
    {
        try {
            return DB::transaction($callback);
        } catch (Exception $e) {
            Log::error('Transaction failed: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString()
            ]);

            throw $e;
        }
    }
}
