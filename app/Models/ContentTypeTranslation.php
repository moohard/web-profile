<?php

declare(strict_types=1);

namespace App\Models;

use Database\Factories\ContentTypeTranslationFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $content_type_id
 * @property int $language_id
 * @property string $name
 * @property ?string $description
 */
class ContentTypeTranslation extends Model
{
    /** @use HasFactory<ContentTypeTranslationFactory> */
    use HasFactory;

    protected $fillable = ['content_type_id', 'language_id', 'name', 'description'];

    /** @return BelongsTo<ContentType, $this> */
    public function contentType(): BelongsTo
    {
        return $this->belongsTo(ContentType::class);
    }

    /** @return BelongsTo<Language, $this> */
    public function language(): BelongsTo
    {
        return $this->belongsTo(Language::class);
    }
}
