<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use OpenApi\Attributes as OA;

#[OA\Schema(
    required: ['id', 'name'],
    properties: [
        new OA\Property(
            property: 'id',
            type: 'integer',
        ),
        new OA\Property(
            property: 'name',
            type: 'string',
        ),
    ],
)]
/**
 * @mixin IdeHelperGenre
 */
class Genre extends Model
{
    protected $hidden = ['pivot', 'created_at', 'updated_at'];
}
