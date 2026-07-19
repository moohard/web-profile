<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $widget_id
 * @property int $language_id
 * @property ?string $title
 * @property ?string $content
 */
class WidgetTranslation extends Model
{
    protected $fillable = [
        'widget_id',
        'language_id',
        'title',
        'content',
    ];

    public function widget(): BelongsTo
    {
        return $this->belongsTo(Widget::class);
    }

    public function language(): BelongsTo
    {
        return $this->belongsTo(Language::class);
    }
}
