<?php

declare(strict_types=1);

namespace App\Models;

use App\Support\HasTranslations;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property int $id
 * @property string $slug
 * @property ?string $icon
 * @property ?int $writing_style_id
 * @property string $archive_template_key
 * @property string $single_template_key
 * @property bool $is_active
 * @property int $sort_order
 */
class ContentType extends Model
{
    use HasTranslations;

    protected $fillable = [
        'slug',
        'icon',
        'writing_style_id',
        'archive_template_key',
        'single_template_key',
        'is_active',
        'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'sort_order' => 'integer',
        ];
    }

    public function writingStyle(): BelongsTo
    {
        return $this->belongsTo(WritingStyle::class);
    }

    public function posts(): HasMany
    {
        return $this->hasMany(Post::class, 'type_id');
    }

    public function translations(): HasMany
    {
        return $this->hasMany(ContentTypeTranslation::class);
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true)->orderBy('sort_order');
    }
}
