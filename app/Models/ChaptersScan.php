<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @mixin IdeHelperChaptersScan
 */
class ChaptersScan extends Model
{
    protected $fillable = ['series_scan_id', 'basepath'];

    public $timestamps = false;

    /**
     * @return BelongsTo<SeriesScan, $this>
     */
    public function serie(): BelongsTo
    {
        return $this->belongsTo(SeriesScan::class, 'series_scan_id', 'id');
    }
}
