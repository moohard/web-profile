<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property int $id
 * @property string $name
 * @property ?string $prompt
 */
class WritingStyle extends Model
{
    protected $fillable = ['name', 'prompt'];

    public function contentTypes(): HasMany
    {
        return $this->hasMany(ContentType::class);
    }
}
