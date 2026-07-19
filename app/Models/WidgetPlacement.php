<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\PlacementScope;
use App\Enums\WidgetPosition;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property int $id
 * @property int $widget_id
 * @property WidgetPosition $position
 * @property PlacementScope $scope
 * @property int $sort_order
 */
class WidgetPlacement extends Model
{
    protected $fillable = [
        'widget_id',
        'position',
        'scope',
        'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'position' => WidgetPosition::class,
            'scope' => PlacementScope::class,
            'sort_order' => 'integer',
        ];
    }

    /** @return BelongsTo<Widget, $this> */
    public function widget(): BelongsTo
    {
        return $this->belongsTo(Widget::class);
    }

    /** @return HasMany<WidgetPlacementTarget, $this> */
    public function targets(): HasMany
    {
        return $this->hasMany(WidgetPlacementTarget::class, 'placement_id');
    }
}
