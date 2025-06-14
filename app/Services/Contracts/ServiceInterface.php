<?php

namespace App\Services\Contracts;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Pagination\LengthAwarePaginator;

/**
 * Interface ServiceInterface
 *
 * @package App\Services\Contracts
 */
interface ServiceInterface
{
    /**
     * Set relation to be loaded with the model.
     *
     * @param string $relation
     * @return static
     */
    public function setRelation(string $relation): static;

    /**
     * Set attributes to be selected from the model.
     *
     * @param array|string $attributes
     * @return static
     */
    public function setAttributes(array|string $attributes): static;

    /**
     * Set filters for the query.
     *
     * @param array $filters
     * @return static
     */
    public function setFilters(array $filters): static;

    /**
     * Get paginated results or collection based on the current settings.
     *
     * @return Collection|LengthAwarePaginator
     */
    public function get(): Collection|LengthAwarePaginator;

    /**
     * Get a list of items based on the provided parameters.
     *
     * @param array $params
     * @return Collection
     */
    public function list(array $params): Collection;

    /**
     * Apply relation loading to the query builder.
     *
     * @param Builder $query
     * @param array|string|null $relations
     * @return Builder
     */
    public function relation(Builder $query, array|string|null $relations = null): Builder;

    /**
     * Apply column selection to the query builder.
     *
     * @param Builder $query
     * @param array|null $attributes
     * @return Builder
     */
    public function select(Builder $query, ?array $attributes = null): Builder;

    /**
     * Apply filters to the query builder.
     *
     * @param Builder $query
     * @param array $filters
     * @return Builder
     */
    public function filter(Builder $query, array $filters): Builder;

    /**
     * Apply sorting to the query builder.
     *
     * @param Builder $query
     * @return Builder
     */
    public function sort(Builder $query): Builder;

    /**
     * Create a new model with the given parameters.
     *
     * @param array $params
     * @return Model
     */
    public function create(array $params): Model;

    /**
     * Retrieve a model by its ID.
     *
     * @param int|string $id
     * @return Model|null
     */
    public function show($id): ?Model;

    /**
     * Update a model with the given parameters.
     *
     * @param array $params
     * @param int|string $id
     * @return Model|bool
     */
    public function edit(array $params, $id): Model|bool;

    /**
     * Delete a model by its ID.
     *
     * @param int|string $id
     * @return bool
     */
    public function delete($id): bool;

    /**
     * Insert multiple rows into the database.
     *
     * @param array $rows
     * @return bool
     */
    public function insert(array $rows): bool;

    /**
     * Create or update multiple records in a transaction.
     *
     * @param array $records
     * @return bool
     */
    public function bulkCreateOrUpdate(array $records): bool;

    /**
     * Delete multiple records by their IDs.
     *
     * @param array $ids
     * @return bool
     */
    public function bulkDelete(array $ids): bool;

    /**
     * Execute a complex operation within a database transaction.
     *
     * @param callable $callback
     * @return mixed
     */
    public function transaction(callable $callback): mixed;
}
