<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @mixin IdeHelperSeriesScan
 */
class SeriesScan extends Model
{
    protected $fillable = ['library_id', 'basepath', 'serie_id'];

    public $timestamps = false;

    /**
     * @return BelongsTo<Serie, $this>
     */
    public function serie(): BelongsTo
    {
        return $this->belongsTo(Serie::class);
    }
}
