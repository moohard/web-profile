<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\TestimonialStatus;
use App\Policies\TestimonialPolicy;
use App\Support\Media\HasDefaultMediaConversions;
use Database\Factories\TestimonialFactory;
use Illuminate\Database\Eloquent\Attributes\UsePolicy;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

/**
 * @property int $id
 * @property string $author_name
 * @property ?string $author_title
 * @property string $content
 * @property ?int $photo_media_id
 * @property TestimonialStatus $status
 * @property int $sort_order
 */
#[UsePolicy(TestimonialPolicy::class)]
class Testimonial extends Model implements HasMedia
{
    use HasDefaultMediaConversions, InteractsWithMedia {
        HasDefaultMediaConversions::registerMediaConversions insteadof InteractsWithMedia;
    }

    /** @use HasFactory<TestimonialFactory> */
    use HasFactory;

    protected $fillable = [
        'author_name',
        'author_title',
        'content',
        'photo_media_id',
        'status',
        'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'status' => TestimonialStatus::class,
            'sort_order' => 'integer',
            'photo_media_id' => 'integer',
        ];
    }

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('photo')->singleFile();
    }

    /** @return BelongsTo<Media, $this> */
    public function photoMedia(): BelongsTo
    {
        return $this->belongsTo(Media::class, 'photo_media_id');
    }
}
