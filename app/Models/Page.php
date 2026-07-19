<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\PageMode;
use App\Support\HasTranslations;
use Database\Factories\PageFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property int $id
 * @property PageMode $mode
 * @property string $template_key
 * @property bool $hero_enabled
 * @property ?string $hero_image
 * @property bool $sidebar_enabled
 */
class Page extends Model
{
    /** @use HasFactory<PageFactory> */
    use HasFactory;

    use HasTranslations;

    protected $fillable = [
        'mode',
        'template_key',
        'hero_enabled',
        'hero_image',
        'sidebar_enabled',
    ];

    protected function casts(): array
    {
        return [
            'mode' => PageMode::class,
            'hero_enabled' => 'boolean',
            'sidebar_enabled' => 'boolean',
        ];
    }

    public function translations(): HasMany
    {
        return $this->hasMany(PageTranslation::class);
    }
}
