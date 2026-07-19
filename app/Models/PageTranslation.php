<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $page_id
 * @property int $language_id
 * @property string $slug
 * @property string $title
 * @property ?array<string, mixed> $content
 * @property ?string $hero_heading
 * @property ?string $hero_subheading
 * @property ?string $hero_cta_text
 * @property ?string $hero_cta_link
 * @property string $status
 * @property ?string $meta_title
 * @property ?string $meta_description
 */
class PageTranslation extends Model
{
    protected $fillable = [
        'page_id',
        'language_id',
        'slug',
        'title',
        'content',
        'hero_heading',
        'hero_subheading',
        'hero_cta_text',
        'hero_cta_link',
        'status',
        'meta_title',
        'meta_description',
    ];

    protected function casts(): array
    {
        return [
            'content' => 'array',
        ];
    }

    public function page(): BelongsTo
    {
        return $this->belongsTo(Page::class);
    }

    public function language(): BelongsTo
    {
        return $this->belongsTo(Language::class);
    }
}
