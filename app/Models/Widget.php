<?php

declare(strict_types=1);

namespace App\Models;

use App\Support\HasTranslations;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property int $id
 * @property string $type
 * @property ?array<string, mixed> $config
 * @property bool $is_active
 */
class Widget extends Model
{
    use HasTranslations;

    protected $fillable = ['type', 'config', 'is_active'];

    protected function casts(): array
    {
        return [
            'config' => 'array',
            'is_active' => 'boolean',
        ];
    }

    public function placements(): HasMany
    {
        return $this->hasMany(WidgetPlacement::class);
    }

    public function translations(): HasMany
    {
        return $this->hasMany(WidgetTranslation::class);
    }
}
