<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $criterion_id
 * @property int $language_id
 * @property string $name
 */
class RatingCriterionTranslation extends Model
{
    /** Nama tabel plural non-standar (bukan rating_criterion_translations). */
    protected $table = 'rating_criteria_translations';

    protected $fillable = ['criterion_id', 'language_id', 'name'];

    public function criterion(): BelongsTo
    {
        return $this->belongsTo(RatingCriterion::class, 'criterion_id');
    }

    public function language(): BelongsTo
    {
        return $this->belongsTo(Language::class);
    }
}
