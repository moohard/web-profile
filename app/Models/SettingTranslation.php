<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property string $key
 * @property int $language_id
 * @property ?string $value
 */
class SettingTranslation extends Model
{
    protected $fillable = ['key', 'language_id', 'value'];

    public function language(): BelongsTo
    {
        return $this->belongsTo(Language::class);
    }
}
