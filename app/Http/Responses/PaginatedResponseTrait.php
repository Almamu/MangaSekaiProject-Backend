<?php

namespace App\Http\Responses;

use App\Http\Mappers\Mapper;
use App\Http\OpenApi\OpenApiSpec;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

trait PaginatedResponseTrait
{
    /**
     * @template TModel of Model
     *
     * @param Builder<TModel> $builder
     * @param ?Mapper<TModel> $mapper
     *
     * @return PaginatedResponse<TModel>
     */
    public function paginate(Builder $builder, null|Mapper $mapper = null): PaginatedResponse
    {
        $perPage = request()->integer('perPage', OpenApiSpec::RECORDS_PER_PAGE);
        $page = request()->integer('page', 1);
        $pagination = $builder->paginate($perPage, page: $page);

        return new PaginatedResponse($pagination, $mapper);
    }
}
