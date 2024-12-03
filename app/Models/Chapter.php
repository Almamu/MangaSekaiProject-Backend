<?php

namespace App\Models;

use App\Http\OpenApi\PaginationSchema;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use OpenApi\Attributes as OA;

#[PaginationSchema(schema: 'ChapterListPaginated', type: Chapter::class)]
#[OA\Schema(properties: [
    new OA\Property(property: 'id', type: 'integer'),
    new OA\Property(property: 'number', type: 'integer'),
    new OA\Property(property: 'pages_count', type: 'integer'),
])]
/**
 * @mixin IdeHelperChapter
 */
class Chapter extends Model
{
    /** @use HasFactory<\Database\Factories\ChapterFactory> */
    use HasFactory;

    protected $hidden = ['serie_id'];

    protected $fillable = ['serie_id', 'number', 'pages_count'];

    /**
     * @return HasMany<Page, $this>
     */
    public function pages(): HasMany
    {
        return $this->hasMany(Page::class);
    }

    /**
     * @return BelongsTo<Serie, $this>
     */
    public function serie(): BelongsTo
    {
        return $this->belongsTo(Serie::class);
    }

    public static function updateOrCreate(int|Serie $serie, string $number, int $pages_count): self
    {
        return self::query()->updateOrCreate([
            'serie_id' => is_int($serie) ? $serie : $serie->id,
            'number' => $number,
        ], [
            'pages_count' => $pages_count,
        ]);
    }
}
