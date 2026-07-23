<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Language;
use App\Models\Page;
use App\Models\Post;
use App\Models\Testimonial;
use Illuminate\Http\JsonResponse;
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
            ->through($this->serializeMedia(...));

        return Inertia::render('admin/media/index', [
            'media' => $media,
            // Bahasa aktif untuk editor override alt per bahasa.
            'locales' => Language::active()
                ->get(['code', 'name'])
                ->map(fn (Language $lang): array => [
                    'code' => $lang->code,
                    'name' => $lang->name,
                ])
                ->all(),
        ]);
    }

    /**
     * Data ringkas untuk modal pemilih media tanpa navigasi Inertia.
     */
    public function picker(): JsonResponse
    {
        $media = Media::query()
            ->latest()
            ->limit(100)
            ->get()
            ->map($this->serializeMedia(...))
            ->values();

        return response()->json(['data' => $media]);
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
            'alt' => ['nullable', 'string', 'max:255'],
        ]);

        $model = $this->resolveModel($validated['model_type'], (int) $validated['model_id']);
        $this->authorize('update', $model);
        $file = $request->file('file');

        abort_unless($file instanceof UploadedFile, 422, 'File upload tidak valid.');

        $media = $model
            ->addMedia($file)
            ->withCustomProperties(['alt' => $validated['alt'] ?? ''])
            ->toMediaCollection($validated['collection']);

        return back()->with('success', 'Media uploaded: '.$media->file_name);
    }

    /**
     * Perbarui alt-text media: satu alt default + override opsional per bahasa
     * (baseline ramping PRD Lampiran A.2). Otorisasi via model induk.
     */
    public function update(Request $request, Media $media): RedirectResponse
    {
        $validated = $request->validate([
            'alt' => ['nullable', 'string', 'max:255'],
            'alt_overrides' => ['nullable', 'array'],
            'alt_overrides.*' => ['nullable', 'string', 'max:255'],
        ]);

        $parent = $media->model;

        if ($parent instanceof Post || $parent instanceof Page || $parent instanceof Testimonial) {
            $this->authorize('update', $parent);
        } elseif ($parent !== null) {
            abort(403);
        }

        // Buang override kosong agar fallback ke alt default.
        $overrides = array_filter(
            $validated['alt_overrides'] ?? [],
            fn (?string $value): bool => $value !== null && $value !== '',
        );

        $media->setCustomProperty('alt', $validated['alt'] ?? '');
        $media->setCustomProperty('alt_overrides', $overrides);
        $media->save();

        return back()->with('success', 'Alt text diperbarui.');
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

    /**
     * @return array{
     *     id: int,
     *     file_name: string,
     *     mime_type: string,
     *     size: int,
     *     collection_name: string,
     *     model_type: string,
     *     model_id: int|string,
     *     url: string,
     *     thumb_url: string,
     *     alt: string,
     *     alt_overrides: array<mixed>
     * }
     */
    private function serializeMedia(Media $media): array
    {
        $url = $media->getUrl();

        return [
            'id' => $media->id,
            'file_name' => $media->file_name,
            'mime_type' => $media->mime_type,
            'size' => $media->size,
            'collection_name' => $media->collection_name,
            'model_type' => class_basename((string) $media->model_type),
            'model_id' => $media->model_id,
            'url' => $url,
            'thumb_url' => $media->hasGeneratedConversion('thumb')
                ? $media->getUrl('thumb')
                : $url,
            'alt' => (string) ($media->getCustomProperty('alt') ?? ''),
            'alt_overrides' => (array) ($media->getCustomProperty('alt_overrides') ?? []),
        ];
    }
}
