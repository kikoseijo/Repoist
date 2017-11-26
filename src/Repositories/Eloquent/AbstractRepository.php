<?php

namespace Kurt\Repoist\Repositories\Eloquent;

use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Pagination\LengthAwarePaginator;
use Kurt\Repoist\Exceptions\NoEntityDefined;
use Kurt\Repoist\Repositories\Contracts\RepositoryInterface;
use Kurt\Repoist\Repositories\Criteria\CriteriaInterface;
use Illuminate\Http\Request;

abstract class AbstractRepository implements RepositoryInterface, CriteriaInterface
{
    /**
     * @var mixed
     */
    protected $entity;

    /**
     * @var Illuminate\Http\Request
     */
    protected $request;


    public function __construct(Request $request)
    {
        $this->entity = $this->resolveEntity();
        $this->request = $request;
    }

    /**
     * @return Illuminate\Support\Collection
     */
    public function all()
    {
        return $this->entity->get();
    }

    /**
     * @param $id
     * @return Illuminate\Database\Eloquent\Model|ModelNotFoundException
     */
    public function find($id)
    {
        $model = $this->entity->find($id);

        if (!$model) {
            throw (new ModelNotFoundException)->setModel(
                get_class($this->entity->getModel()),
                $id
            );
        }

        return $model;
    }

    /**
     * @param $column
     * @param $value
     * @return Illuminate\Support\Collection
     */
    public function findWhere($column, $value)
    {
        return $this->entity->where($column, $value)->get();
    }

    /**
     * @param $column
     * @param $value
     * @return Illuminate\Database\Eloquent\Model|ModelNotFoundException
     */
    public function findWhereFirst($column, $value)
    {
        $model = $this->entity->where($column, $value)->first();

        if (!$model) {
            throw (new ModelNotFoundException)->setModel(
                get_class($this->entity->getModel())
            );
        }

        return $model;
    }

    /**
     * {@inheritdoc}
     */
    public function findWhereLike($column, $value)
    {
        $query = $this->entity;
        if (is_array($column)) {
            $i = 0;
            foreach ($column as $columnItem) {
                if ($i == 0) {
                    $query->where($column, 'like', $value);
                } else {
                    $query->orWhere($column, 'like', $value);
                }
                $i++;
            }
        } else {
            $query->where($column, 'like', $value);
        }
        return $this->paginateIf($query->get());
    }

    /**
     * {@inheritdoc}
     */
    public function paginate($perPage = 10){
        $this->request->limit = $perPage;
        return $this->paginateIf();

    }

    /**
     * {@inheritdoc}
     */
    public function paginateIf($records)
    {
        $per_page = $this->request->input('limit') > 0 ? $this->request->input('limit') : 0;

        if ($per_page > 0) {

            $page   = $this->request->input('page') > 0 ? $this->request->input('page') : 1;
            $offset = ($page * $per_page) - $per_page;

            return new LengthAwarePaginator(
                array_slice($records->toArray(), $offset, $per_page, true),
                count($records),
                $per_page,
                $page,
                ['path' => $this->request->url(), 'query' => $this->request->query()]
            );
        } else {

            return $records;
        }
    }

    /**
     * @param array $properties
     * @return mixed
     */
    public function create(array $properties)
    {
        return $this->entity->create($properties);
    }

    /**
     * @param $id
     * @param array $properties
     * @return mixed
     */
    public function update($id, array $properties)
    {
        return $this->find($id)->update($properties);
    }

    /**
     * @param $id
     * @return mixed
     */
    public function delete($id)
    {
        return $this->find($id)->delete();
    }

    /**
     * @param $criteria
     * @return mixed
     */
    public function withCriteria(...$criteria)
    {
        $criteria = array_flatten($criteria);

        foreach ($criteria as $criterion) {
            $this->entity = $criterion->apply($this->entity);
        }

        return $this;
    }

    protected function resolveEntity()
    {
        if (!method_exists($this, 'entity')) {
            throw new NoEntityDefined();
        }

        return app($this->entity());
    }
}
