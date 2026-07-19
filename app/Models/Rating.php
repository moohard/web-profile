<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property int $id
 * @property ?string $comment
 * @property string $visitor_hash
 */
class Rating extends Model
{
    protected $fillable = ['comment', 'visitor_hash'];

    /** @return HasMany<RatingScore, $this> */
    public function scores(): HasMany
    {
        return $this->hasMany(RatingScore::class);
    }
}
