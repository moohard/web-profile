<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $rating_id
 * @property int $criterion_id
 * @property int $score
 */
class RatingScore extends Model
{
    public $timestamps = false;

    protected $fillable = ['rating_id', 'criterion_id', 'score'];

    protected function casts(): array
    {
        return [
            'score' => 'integer',
        ];
    }

    /** @return BelongsTo<Rating, $this> */
    public function rating(): BelongsTo
    {
        return $this->belongsTo(Rating::class);
    }

    /** @return BelongsTo<RatingCriterion, $this> */
    public function criterion(): BelongsTo
    {
        return $this->belongsTo(RatingCriterion::class, 'criterion_id');
    }
}
