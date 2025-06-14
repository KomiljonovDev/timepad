<?php

namespace App\Repositories\Contracts;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Pagination\LengthAwarePaginator;

/**
 * Interface RepositoryInterface
 *
 * @package App\Repositories\Contracts
 */
interface RepositoryInterface
{
    /**
     * Get a query builder instance for the repository model.
     *
     * @return Builder
     */
    public function getQuery(): Builder;

    /**
     * Find a model by its primary key.
     *
     * @param int|string $id
     * @return Model|null
     */
    public function getById($id): ?Model;

    /**
     * Find a model by its primary key or throw an exception.
     *
     * @param int|string $id
     * @return Model
     *
     * @throws \Illuminate\Database\Eloquent\ModelNotFoundException
     */
    public function findOrFail($id): Model;

    /**
     * Execute the query and get the first result or throw an exception.
     *
     * @param array $conditions
     * @return Model
     *
     * @throws \Illuminate\Database\Eloquent\ModelNotFoundException
     */
    public function firstOrFail(array $conditions = []): Model;

    /**
     * Get the first record matching the attributes or create it.
     *
     * @param array $attributes
     * @param array $values
     * @return Model
     */
    public function firstOrCreate(array $attributes, array $values = []): Model;

    /**
     * Paginate the query or get all results.
     *
     * @param Builder $query
     * @param int|null $perPage
     * @return Collection|LengthAwarePaginator
     */
    public function getPaginate(Builder $query, ?int $perPage = null);

    /**
     * Create a new model instance with the given attributes.
     *
     * @param array $params
     * @return Model
     */
    public function store(array $params): Model;

    /**
     * Update an existing model instance with the given attributes.
     *
     * @param array $params
     * @param int|string $id
     * @return Model|bool
     */
    public function update(array $params, $id);

    /**
     * Delete a model instance by its primary key.
     *
     * @param int|string $id
     * @return bool
     */
    public function destroy($id): bool;

    /**
     * Insert multiple rows into the database table.
     *
     * @param array $rows
     * @return bool
     */
    public function insert(array $rows): bool;

    /**
     * Create or update a record matching the attributes, and fill it with values.
     *
     * @param array $attributes
     * @param array $values
     * @return Model
     */
    public function updateOrCreate(array $attributes, array $values): Model;

    /**
     * Get models by specified conditions.
     *
     * @param array $conditions
     * @param array $relations
     * @param array $columns
     * @return Collection
     */
    public function getByConditions(array $conditions = [], array $relations = [], array $columns = ['*']): Collection;

    /**
     * Find a model by specified conditions.
     *
     * @param array $conditions
     * @param array $relations
     * @param array $columns
     * @return Model|null
     */
    public function findByConditions(array $conditions = [], array $relations = [], array $columns = ['*']): ?Model;
}
