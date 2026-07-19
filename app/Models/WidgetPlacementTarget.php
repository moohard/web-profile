<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $placement_id
 * @property string $target_type
 * @property ?string $target_ref
 */
class WidgetPlacementTarget extends Model
{
    public const TYPE_PAGE = 'Page';

    public const TYPE_CONTENT_ARCHIVE = 'ContentArchive';

    public const TYPE_CONTENT_SINGLE = 'ContentSingle';

    protected $fillable = [
        'placement_id',
        'target_type',
        'target_ref',
    ];

    public function placement(): BelongsTo
    {
        return $this->belongsTo(WidgetPlacement::class, 'placement_id');
    }
}
