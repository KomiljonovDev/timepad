<?php

namespace App\Repositories;

use App\Repositories\Contracts\RepositoryInterface;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Pagination\LengthAwarePaginator;
use Exception;
use Illuminate\Support\Facades\Log;

/**
 * Class BaseRepository
 *
 * @package App\Repositories
 */
abstract class BaseRepository implements RepositoryInterface
{
    /**
     * @var Model
     */
    protected $entity;

    /**
     * Get a query builder instance for the repository model.
     *
     * @return Builder
     */
    public function getQuery(): Builder
    {
        return $this->entity->query();
    }

    /**
     * Find a model by its primary key.
     *
     * @param int|string $id
     * @return Model|null
     */
    public function getById($id): ?Model
    {
        try {
            return $this->entity->find($id);
        } catch (Exception $e) {
            Log::error('Error retrieving model: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Find a model by its primary key or throw an exception.
     *
     * @param int|string $id
     * @return Model
     *
     * @throws ModelNotFoundException
     */
    public function findOrFail($id): Model
    {
        return $this->entity->findOrFail($id);
    }

    /**
     * Execute the query and get the first result or throw an exception.
     *
     * @param array $conditions
     * @return Model
     *
     * @throws ModelNotFoundException
     */
    public function firstOrFail(array $conditions = []): Model
    {
        $query = $this->getQuery();

        foreach ($conditions as $field => $value) {
            $query->where($field, $value);
        }

        return $query->firstOrFail();
    }

    /**
     * Get the first record matching the attributes or create it.
     *
     * @param array $attributes
     * @param array $values
     * @return Model
     */
    public function firstOrCreate(array $attributes, array $values = []): Model
    {
        return $this->entity->firstOrCreate($attributes, $values);
    }

    /**
     * Paginate the query or get all results.
     *
     * @param Builder $query
     * @param int|null $perPage
     * @return Collection|LengthAwarePaginator
     */
    public function getPaginate(Builder $query, ?int $perPage = null)
    {
        if ($perPage) {
            return $query->paginate($perPage);
        }

        return $query->get();
    }

    /**
     * Create a new model instance with the given attributes.
     *
     * @param array $params
     * @return Model
     */
    public function store(array $params): Model
    {
        try {
            return $this->entity->create($params);
        } catch (Exception $e) {
            Log::error('Error creating model: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Update an existing model instance with the given attributes.
     *
     * @param array $params
     * @param int|string $id
     * @return Model|bool
     */
    public function update(array $params, $id)
    {
        try {
            $model = $this->getById($id);

            if (!$model) {
                return false;
            }

            $model->update($params);
            return $model->fresh();
        } catch (Exception $e) {
            Log::error('Error updating model: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Delete a model instance by its primary key.
     *
     * @param int|string $id
     * @return bool
     */
    public function destroy($id): bool
    {
        try {
            $entity = $this->getById($id);
            return $entity ? $entity->delete() : false;
        } catch (Exception $e) {
            Log::error('Error deleting model: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Insert multiple rows into the database table.
     *
     * @param array $rows
     * @return bool
     */
    public function insert(array $rows): bool
    {
        try {
            return $this->entity::insert($rows);
        } catch (Exception $e) {
            Log::error('Error inserting data: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Create or update a record matching the attributes, and fill it with values.
     *
     * @param array $attributes
     * @param array $values
     * @return Model
     * @throws Exception
     */
    public function updateOrCreate(array $attributes, array $values): Model
    {
        try {
            return $this->entity::updateOrCreate($attributes, $values);
        } catch (Exception $e) {
            Log::error('Error updating or creating model: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Get models by specified conditions.
     *
     * @param array $conditions
     * @param array $relations
     * @param array $columns
     * @return Collection
     */
    public function getByConditions(array $conditions = [], array $relations = [], array $columns = ['*']): Collection
    {
        $query = $this->getQuery();

        // Apply conditions
        foreach ($conditions as $field => $value) {
            $query->where($field, $value);
        }

        // Load relations
        if (!empty($relations)) {
            $query->with($relations);
        }

        return $query->get($columns);
    }

    /**
     * Find a model by specified conditions.
     *
     * @param array $conditions
     * @param array $relations
     * @param array $columns
     * @return Model|null
     */
    public function findByConditions(array $conditions = [], array $relations = [], array $columns = ['*']): ?Model
    {
        $query = $this->getQuery();

        // Apply conditions
        foreach ($conditions as $field => $value) {
            $query->where($field, $value);
        }

        // Load relations
        if (!empty($relations)) {
            $query->with($relations);
        }

        return $query->first($columns);
    }
}
