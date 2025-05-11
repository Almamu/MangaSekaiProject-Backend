<?php

namespace App\Http\Responses;

use App\Http\Mappers\Mapper;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\JsonResponse;

/**
 * Simple class that handles paginated objects and turns them into JsonResponses
 *
 * This one lacks OpenApi specification because it's a 'generic' of sorts
 *
 * @template TItem
 */
class PaginatedResponse extends JsonResponse
{
    /**
     * @param LengthAwarePaginator<int, TItem>  $paginator
     * @param ?Mapper<TItem> $mapper
     */
    public function __construct(LengthAwarePaginator $paginator, null|Mapper $mapper)
    {
        parent::__construct([
            'data' => is_null($mapper) ? $paginator->items() : array_map($mapper, $paginator->items()),
            'current_page' => $paginator->currentPage(),
            'records_per_page' => $paginator->perPage(),
            'last_page' => $paginator->lastPage(),
            'total' => $paginator->total(),
        ]);
    }
}
