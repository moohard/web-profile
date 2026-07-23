<?php

declare(strict_types=1);

namespace App\Models;

use Database\Factories\WritingStyleFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property int $id
 * @property string $name
 * @property ?string $prompt
 * @property bool $content_types_exists
 */
class WritingStyle extends Model
{
    /** @use HasFactory<WritingStyleFactory> */
    use HasFactory;

    protected $fillable = ['name', 'prompt'];

    /** @return HasMany<ContentType, $this> */
    public function contentTypes(): HasMany
    {
        return $this->hasMany(ContentType::class);
    }
}
