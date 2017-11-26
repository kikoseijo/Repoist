<?php

namespace Kurt\Repoist\Repositories\Contracts;

interface RepositoryInterface
{
    public function all();
    public function find($id);
    public function findWhere($column, $value);
    public function findWhereFirst($column, $value);


    /**
     * Accepts string collumn or an array of columns
     * 'name' | ['name', 'description']
     *
     * @param string|array $column
     * @param string $value
     * @return Illuminate\Support\Collection|Illuminate\Pagination\LengthAwarePaginator
     */
    public function findWhereLike($column, $value);


    /**
     * Returns paginated object with records IF $request->limit > 0
     * /users?page=1&limit=25
     *
     * @param Illuminate\Support\Collection $records
     * @return Illuminate\Support\Collection|Illuminate\Pagination\LengthAwarePaginator
     */
    public function paginateIf($records);


    public function create(array $properties);
    public function update($id, array $properties);
    public function delete($id);
}
