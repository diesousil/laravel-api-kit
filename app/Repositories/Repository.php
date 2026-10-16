<?php

namespace App\Repositories;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Response;
use Illuminate\Support\Collection;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Pagination\LengthAwarePaginator;

class Repository
{
    public function __construct(
        protected Model $model
    ) {
    }

    protected function paginate(Builder $query, array|null $data): LengthAwarePaginator
    {
        $pageSize = (isset($data['page_size']) ? intval($data['page_size']) :
                        (env('DEFAULT_LIST_PAGE_SIZE') != null ? env('DEFAULT_LIST_PAGE_SIZE') : 15));

        $page     = (isset($data['page']) ? intval($data['page']) : 1);

        return $query->paginate($pageSize, ['*'], 'page', $page);
    }

    private function sanitize_data(array $data): array
    {
        return array_filter($data, function ($item, $key) {
            return $item !== null;
        }, ARRAY_FILTER_USE_BOTH);
    }

    protected function getQuery(): Builder
    {
        return $this->model::query();
    }

    public function create(array $data): Model
    {
        return $this->model->create($this->sanitize_data($data));
    }

    public function batchCreate(array $data): bool
    {
        return $this->model->insert($this->sanitize_data($data));
    }

    public function update(array $data, int|string $id): Model
    {
        $dataObj = $this->getQuery()->findOrFail($id);

        $dataObj->fill($data);
        $dataObj->save();

        return $dataObj;
    }

    public function delete(int|string $id): bool
    {
        return $this->getQuery()->findOrFail($id)->delete();
    }

    public function getById(int|string $id): ?Model
    {
        return $this->getQuery()->find($id);
    }

    public function listWithPagination(array|null $paginationSets): LengthAwarePaginator
    {
        return $this->paginate($this->getQuery(), $paginationSets);
    }

    public function list(array|null $data): Collection
    {
        return $this->getQuery()->get();
    }

    public function search(array $fields, string $searchTerms): Collection
    {
        return $this->getQuery()->search($fields, $searchTerms)->get();
    }

    public function searchWithPagination(array $fields, string $searchTerms, array $paginationSets): LengthAwarePaginator
    {
        return $this->paginate($this->getQuery()->search($fields, $searchTerms), $paginationSets);
    }


}
