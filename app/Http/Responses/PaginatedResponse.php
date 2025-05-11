<?php

namespace App\Http\Responses;

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
     */
    public function __construct(LengthAwarePaginator $paginator)
    {
        parent::__construct([
            'data' => $paginator->items(),
            'current_page' => $paginator->currentPage(),
            'records_per_page' => $paginator->perPage(),
            'last_page' => $paginator->lastPage(),
            'total' => $paginator->total(),
        ]);
    }
}
