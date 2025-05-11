<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

/**
 * @mixin IdeHelperSerie
 */
class Serie extends Model
{
    /** @use HasFactory<\Database\Factories\SerieFactory> */
    use HasFactory;

    protected $hidden = ['image', 'mime_type', 'genres', 'staff', 'external_id', 'blocked_fields'];

    protected $fillable = [
        'name',
        'chapter_count',
        'pages_count',
        'description',
        'image',
        'mime_type',
        'external_id',
        'matcher',
        'synced',
        'blocked_fields',
    ];

    protected function casts(): array
    {
        return [
            'blocked_fields' => 'array',
        ];
    }

    //TODO: ARE THESE REALLY NEEDED?
    /**
     * @return \Illuminate\Database\Eloquent\Collection<int, Genre>
     */
    public function getGenresAttribute(): \Illuminate\Database\Eloquent\Collection
    {
        return $this->genres()->get();
    }

    /**
     * @return \Illuminate\Database\Eloquent\Collection<int, Chapter>
     */
    public function getChaptersAttribute(): \Illuminate\Database\Eloquent\Collection
    {
        return $this->chapters()->get();
    }

    /**
     * @return \Illuminate\Database\Eloquent\Collection<int, Staff>
     */
    public function getStaffAttribute(): \Illuminate\Database\Eloquent\Collection
    {
        return $this->staff()->get();
    }

    /**
     * @return BelongsToMany<Genre, $this>
     */
    public function genres(): BelongsToMany
    {
        return $this->belongsToMany(Genre::class);
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany<Chapter, $this>
     */
    public function chapters(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(Chapter::class);
    }

    /**
     * @return BelongsToMany<Staff, $this>
     */
    public function staff(): BelongsToMany
    {
        return $this->belongsToMany(Staff::class)->withPivot('role');
    }

    public function hasImage(): bool
    {
        return is_null($this->image) === false && is_null($this->mime_type) === false;
    }
}
