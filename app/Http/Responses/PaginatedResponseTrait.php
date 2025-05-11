<?php

namespace App\Http\Responses;

use App\Http\OpenApi\OpenApiSpec;

trait PaginatedResponseTrait
{
    /**
     * @template TModel of \Illuminate\Database\Eloquent\Model
     *
     * @param \Illuminate\Database\Eloquent\Builder<TModel>  $builder
     *
     * @return PaginatedResponse<TModel>
     */
    public function paginate(\Illuminate\Database\Eloquent\Builder $builder): PaginatedResponse
    {
        $perPage = request()->integer('perPage', OpenApiSpec::RECORDS_PER_PAGE);
        $page = request()->integer('page', 1);
        $pagination = $builder->paginate($perPage, page: $page);

        return new PaginatedResponse($pagination);
    }
}
