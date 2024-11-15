<?php

namespace App\Models;

use App\Http\OpenApi\PaginationSchema;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use OpenApi\Attributes as OA;

#[PaginationSchema(schema: 'ChapterListPaginated', type: Chapter::class)]
#[OA\Schema(properties: [
    new OA\Property(property: 'id', type: 'integer'),
    new OA\Property(property: 'number', type: 'integer'),
    new OA\Property(property: 'pages_count', type: 'integer'),
])]
class Chapter extends Model
{
    /** @use HasFactory<\Database\Factories\ChapterFactory> */
    use HasFactory;

    protected $hidden = ['serie_id'];

    /**
     * @return HasMany<Page, $this>
     */
    public function pages(): HasMany
    {
        return $this->hasMany(Page::class);
    }

    public static function updateOrCreate(Serie $serie, string $number, int $pages_count): self
    {
        return self::query()->updateOrCreate([
            'serie_id' => $serie->id,
            'number' => $number,
        ], [
            'pages_count' => $pages_count,
        ]);
    }
}
