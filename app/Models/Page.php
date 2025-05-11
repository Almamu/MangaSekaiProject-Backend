<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * @mixin IdeHelperPage
 */
class Page extends Model
{
    /** @use HasFactory<\Database\Factories\PageFactory> */
    use HasFactory;

    protected $appends = ['public_url'];

    protected $hidden = ['chapter_id', 'mime_type'];

    public $timestamps = false;

    public function getPublicUrlAttribute(): null|string
    {
        return route('images.pages', ['page' => $this->id]);
    }
}
