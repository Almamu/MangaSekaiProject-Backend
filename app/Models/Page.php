<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Routing\UrlGenerator;

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

    /**
     * Create a new Eloquent model instance.
     *
     * @param array<mixed, mixed> $attributes
     */
    public function __construct(
        array $attributes,
        private readonly \Illuminate\Routing\UrlGenerator $urlGenerator,
    ) {
        parent::__construct($attributes);
    }

    // @phpstan-ignore missingType.generics (This doesn't really have generics but something we have is triggering it)
    protected function publicUrl(): \Illuminate\Database\Eloquent\Casts\Attribute
    {
        return \Illuminate\Database\Eloquent\Casts\Attribute::make(get: function () {
            return $this->urlGenerator->route('images.pages', ['page' => $this->id]);
        });
    }
}
