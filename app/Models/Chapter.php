<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use OpenApi\Attributes as OA;

#[OA\Schema(properties: [
    new OA\Property(property: 'id', type: 'integer'),
    new OA\Property(property: 'number', type: 'integer'),
    new OA\Property(property: 'pages_count', type: 'integer'),
])]
class Chapter extends Model
{
    protected $hidden = ['serie_id'];
}
