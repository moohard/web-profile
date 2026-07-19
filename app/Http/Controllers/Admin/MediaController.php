<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

class MediaController extends Controller
{
    /**
     * Daftar media terbaru (grid admin).
     */
    public function index(): Response
    {
        $media = Media::query()
            ->latest()
            ->paginate(24)
            ->through(fn (Media $item): array => [
                'id' => $item->id,
                'file_name' => $item->file_name,
                'mime_type' => $item->mime_type,
                'size' => $item->size,
                'collection_name' => $item->collection_name,
                'model_type' => class_basename((string) $item->model_type),
                'model_id' => $item->model_id,
                'url' => $item->getUrl(),
                'thumb_url' => $item->hasGeneratedConversion('thumb')
                    ? $item->getUrl('thumb')
                    : $item->getUrl(),
            ]);

        return Inertia::render('admin/media/index', [
            'media' => $media,
        ]);
    }

    /**
     * Upload file ke koleksi media model yang didukung.
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'file' => ['required', 'file', 'mimes:jpg,jpeg,png,webp,svg', 'max:10240'],
            'model_type' => ['required', 'in:Post,Page,Testimonial'],
            'model_id' => ['required', 'integer'],
            'collection' => ['required', 'string', 'max:100'],
        ]);

        $class = 'App\\Models\\'.$validated['model_type'];

        /** @var HasMedia $model */
        $model = $class::query()->findOrFail($validated['model_id']);

        $media = $model
            ->addMediaFromRequest('file')
            ->toMediaCollection($validated['collection']);

        return back()->with('success', 'Media uploaded: '.$media->file_name);
    }

    /**
     * Hapus item media dari library.
     */
    public function destroy(Media $media): RedirectResponse
    {
        $media->delete();

        return back()->with('success', 'Media deleted.');
    }
}
