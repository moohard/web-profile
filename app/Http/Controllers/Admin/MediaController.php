<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Page;
use App\Models\Post;
use App\Models\Testimonial;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

class MediaController extends Controller
{
    /**
     * Koleksi media yang diizinkan per tipe model.
     *
     * @var array<string, list<string>>
     */
    private const COLLECTION_ALLOWLIST = [
        'Post' => ['featured_image'],
        'Page' => ['hero_image'],
        'Testimonial' => ['photo'],
    ];

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
            // SVG sengaja ditolak: format ini dapat membawa script/foreignObject
            // bila disajikan kembali sebagai file publik.
            'file' => ['required', 'file', 'mimes:jpg,jpeg,png,webp', 'max:10240'],
            'model_type' => ['required', 'in:Post,Page,Testimonial'],
            'model_id' => ['required', 'integer'],
            'collection' => [
                'required',
                'string',
                'max:100',
                Rule::in(self::COLLECTION_ALLOWLIST[$request->input('model_type')] ?? []),
            ],
        ]);

        $model = $this->resolveModel($validated['model_type'], (int) $validated['model_id']);
        $this->authorize('update', $model);
        $file = $request->file('file');

        abort_unless($file instanceof UploadedFile, 422, 'File upload tidak valid.');

        $media = $model
            ->addMedia($file)
            ->toMediaCollection($validated['collection']);

        return back()->with('success', 'Media uploaded: '.$media->file_name);
    }

    /**
     * Hapus item media dari library.
     */
    public function destroy(Media $media): RedirectResponse
    {
        $parent = $media->model;

        if ($parent instanceof Post || $parent instanceof Page || $parent instanceof Testimonial) {
            $this->authorize('update', $parent);
        } elseif ($parent !== null) {
            abort(403);
        }

        $media->delete();

        return back()->with('success', 'Media deleted.');
    }

    /**
     * Resolve model parent media dari tipe & id.
     */
    private function resolveModel(string $modelType, int $modelId): Post|Page|Testimonial
    {
        $class = match ($modelType) {
            'Post' => Post::class,
            'Page' => Page::class,
            'Testimonial' => Testimonial::class,
            default => abort(422, 'Tipe model tidak didukung.'),
        };

        return $class::query()->findOrFail($modelId);
    }
}
