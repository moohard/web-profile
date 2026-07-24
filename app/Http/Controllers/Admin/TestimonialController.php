<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Enums\TestimonialStatus;
use App\Http\Controllers\Controller;
use App\Models\Testimonial;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class TestimonialController extends Controller
{
    public function index(): Response
    {
        $this->authorize('viewAny', Testimonial::class);

        return Inertia::render('admin/testimonials/index', [
            'testimonials' => Testimonial::query()
                ->with('media')
                ->orderBy('sort_order')
                ->latest('id')
                ->get()
                ->map(fn (Testimonial $testimonial): array => $this->testimonialData($testimonial))
                ->all(),
        ]);
    }

    public function approve(Testimonial $testimonial): RedirectResponse
    {
        $this->authorize('update', $testimonial);

        if ($testimonial->status === TestimonialStatus::Pending) {
            $testimonial->update(['status' => TestimonialStatus::Approved]);
        }

        return to_route('admin.testimonials.index');
    }

    public function destroy(Testimonial $testimonial): RedirectResponse
    {
        $this->authorize('delete', $testimonial);

        $testimonial->delete();

        return to_route('admin.testimonials.index');
    }

    public function reorder(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'testimonial_ids' => ['required', 'array', 'min:1'],
            'testimonial_ids.*' => ['required', 'integer', 'distinct', 'exists:testimonials,id'],
        ]);

        $this->authorize('update', Testimonial::query()->findOrFail($data['testimonial_ids'][0]));

        foreach ($data['testimonial_ids'] as $sortOrder => $testimonialId) {
            Testimonial::query()->whereKey($testimonialId)->update(['sort_order' => $sortOrder]);
        }

        return to_route('admin.testimonials.index');
    }

    /**
     * @return array{id: int, author_name: string, author_title: ?string, content: string, status: string, sort_order: int, photo_url: ?string}
     */
    private function testimonialData(Testimonial $testimonial): array
    {
        return [
            'id' => $testimonial->id,
            'author_name' => $testimonial->author_name,
            'author_title' => $testimonial->author_title,
            'content' => $testimonial->content,
            'status' => $testimonial->status->value,
            'sort_order' => $testimonial->sort_order,
            'photo_url' => $testimonial->getFirstMediaUrl('photo') ?: null,
        ];
    }
}
