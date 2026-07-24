<?php

declare(strict_types=1);

namespace App\Actions\Testimonials;

use App\Enums\TestimonialStatus;
use App\Models\Testimonial;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;

class StoreTestimonial
{
    /**
     * @param  array{author_name: string, author_title?: ?string, content: string}  $data
     */
    public function __invoke(array $data, ?UploadedFile $photo = null): Testimonial
    {
        return DB::transaction(function () use ($data, $photo): Testimonial {
            $testimonial = Testimonial::create([
                'author_name' => $data['author_name'],
                'author_title' => $data['author_title'] ?? null,
                'content' => $data['content'],
                'status' => TestimonialStatus::Pending,
            ]);

            if ($photo !== null) {
                $media = $testimonial->addMedia($photo)->toMediaCollection('photo');
                $testimonial->update(['photo_media_id' => $media->id]);
            }

            return $testimonial;
        });
    }
}
