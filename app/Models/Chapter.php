<?php

namespace App\Models;

use App\Http\OpenApi\PaginationSchema;
use Illuminate\Database\Eloquent\Model;
use OpenApi\Attributes as OA;

#[PaginationSchema(schema: 'ChapterListPaginated', type: Chapter::class)]
#[OA\Schema(properties: [
    new OA\Property(property: 'id', type: 'integer'),
    new OA\Property(property: 'number', type: 'integer'),
    new OA\Property(property: 'pages_count', type: 'integer'),
])]
class Chapter extends Model
{
    protected $hidden = ['serie_id'];
}
