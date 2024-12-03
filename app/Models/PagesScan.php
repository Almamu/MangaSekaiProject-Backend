<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @mixin IdeHelperPagesScan
 */
class PagesScan extends Model
{
    protected $fillable = ['chapters_scan_id', 'path'];

    public $timestamps = false;

    /**
     * @return BelongsTo<ChaptersScan, $this>
     */
    public function chapter(): BelongsTo
    {
        return $this->belongsTo(ChaptersScan::class, 'chapters_scan_id', 'id');
    }
}
