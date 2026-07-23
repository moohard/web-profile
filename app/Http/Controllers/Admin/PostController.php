<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Actions\Posts\PermanentlyDeletePost;
use App\Actions\Posts\SyncPostFeaturedMedia;
use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\PostRequest;
use App\Models\Category;
use App\Models\ContentType;
use App\Models\Language;
use App\Models\Post;
use App\Models\PostTranslation;
use App\Models\Tag;
use App\Models\User;
use App\Services\Html\Sanitizer;
use App\Support\Categories\CategoryTree;
use App\Support\ContentSlug;
use App\Support\Posts\PostFeaturedImage;
use App\Support\PublicLayoutProps;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

class PostController extends Controller
{
    public function __construct(private readonly Sanitizer $sanitizer) {}

    /**
     * Daftar post — filter jenis konten & status; Author hanya melihat miliknya.
     */
    public function index(Request $request): Response
    {
        $this->authorize('viewAny', Post::class);

        $user = $request->user();
        $currentLanguageId = Language::current()->id;
        $languages = Language::active()->get(['id', 'code', 'name']);
        $typeFilter = $request->string('type')->value() ?: null;
        $statusFilter = $request->string('status')->value() ?: null;

        $posts = Post::query()
            ->with(['type.translations', 'translations', 'author'])
            ->when(
                $typeFilter !== null,
                fn ($query) => $query->whereHas('type', fn ($q) => $q->where('slug', $typeFilter)),
            )
            ->when(
                $statusFilter !== null,
                fn ($query) => $query->whereHas(
                    'translations',
                    fn ($q) => $q->where('language_id', $currentLanguageId)->where('status', $statusFilter),
                ),
            )
            ->when(
                $this->isAuthorScoped($user),
                fn ($query) => $query->where('author_id', $user->id),
            )
            ->latest('updated_at')
            ->paginate(20)
            ->withQueryString()
            ->through(fn (Post $post): array => $this->toSummary($post, $currentLanguageId, $languages));

        return Inertia::render('admin/posts/index', [
            'posts' => $posts,
            'contentTypes' => $this->contentTypeOptions(),
            'languages' => $languages
                ->map(fn (Language $language): array => [
                    'id' => $language->id,
                    'code' => $language->code,
                    'name' => $language->name,
                ])
                ->values()
                ->all(),
            'filters' => [
                'type' => $typeFilter,
                'status' => $statusFilter,
            ],
        ]);
    }

    public function trash(Request $request): Response
    {
        $this->authorize('viewTrash', Post::class);

        $user = $request->user();
        $currentLanguageId = Language::current()->id;
        $languages = Language::active()->get(['id', 'code', 'name']);
        $posts = Post::onlyTrashed()
            ->with(['type.translations', 'translations', 'author'])
            ->when(
                $user !== null
                    && $user->hasRole(UserRole::Author->value)
                    && ! $user->hasAnyRole([UserRole::Admin->value, UserRole::Editor->value]),
                fn ($query) => $query->where('author_id', $user->id),
            )
            ->latest('deleted_at')
            ->paginate(20)
            ->withQueryString()
            ->through(fn (Post $post): array => $this->toTrashSummary($post, $currentLanguageId, $user, $languages));

        return Inertia::render('admin/posts/trash', [
            'posts' => $posts,
        ]);
    }

    /**
     * Form pembuatan post baru.
     */
    public function create(): Response
    {
        $this->authorize('create', Post::class);

        return Inertia::render('admin/posts/form', [
            'post' => null,
            'languages' => $this->languageOptions(),
            'contentTypes' => $this->contentTypeOptions(),
            'categories' => $this->categoryOptions(),
            'tags' => $this->tagOptions(),
        ]);
    }

    /**
     * Simpan post baru + translation per bahasa yang diisi.
     */
    public function store(
        PostRequest $request,
        SyncPostFeaturedMedia $syncFeaturedMedia,
    ): RedirectResponse {
        $data = $request->validated();
        $userId = $request->user()?->id;

        DB::transaction(function () use ($data, $userId, $syncFeaturedMedia): void {
            $post = Post::create([
                'type_id' => $data['type_id'],
                'category_id' => $data['category_id'] ?? null,
                'author_id' => $userId,
            ]);

            $post->tags()->sync($this->resolveTagIds($data['tags'] ?? [], $data['new_tags'] ?? []));
            $syncFeaturedMedia($post, isset($data['featured_media_id'])
                ? (int) $data['featured_media_id']
                : null);
            $this->syncTranslations($post, $data['translations']);
        });

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Post berhasil dibuat.']);

        return redirect()->route('admin.posts.index');
    }

    /**
     * Form perubahan post.
     */
    public function edit(Post $post): Response
    {
        $this->authorize('update', $post);

        $post->load(['translations', 'tags', 'media']);

        return Inertia::render('admin/posts/form', [
            'post' => $this->toFormArray($post),
            'languages' => $this->languageOptions(),
            'contentTypes' => $this->contentTypeOptions(),
            'categories' => $this->categoryOptions(),
            'tags' => $this->tagOptions(),
        ]);
    }

    /**
     * Perbarui post: upsert translations, sync tags, ganti kategori/featured.
     */
    public function update(
        PostRequest $request,
        Post $post,
        SyncPostFeaturedMedia $syncFeaturedMedia,
    ): RedirectResponse {
        $data = $request->validated();

        DB::transaction(function () use ($data, $post, $syncFeaturedMedia): void {
            $post->update([
                'type_id' => $data['type_id'],
                'category_id' => $data['category_id'] ?? null,
            ]);

            $post->tags()->sync($this->resolveTagIds($data['tags'] ?? [], $data['new_tags'] ?? []));
            $syncFeaturedMedia($post, isset($data['featured_media_id'])
                ? (int) $data['featured_media_id']
                : null);
            $this->syncTranslations($post, $data['translations']);
        });

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Post berhasil diperbarui.']);

        return redirect()->route('admin.posts.index');
    }

    /**
     * Hapus post — otorisasi Admin/Editor bebas, Author hanya miliknya. Soft delete
     * (trait SoftDeletes): translations & media tetap ada, muncul di trash.
     */
    public function destroy(Post $post): RedirectResponse
    {
        $this->authorize('delete', $post);

        $post->delete();
        PublicLayoutProps::flushCache();

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Post berhasil dihapus.']);

        return redirect()->route('admin.posts.index');
    }

    public function restore(Post $post): RedirectResponse
    {
        abort_unless($post->trashed(), 404);
        $this->authorize('restore', $post);

        $post->restore();
        PublicLayoutProps::flushCache();

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Post berhasil dipulihkan.']);

        return redirect()->route('admin.posts.trash');
    }

    public function forceDelete(Post $post, PermanentlyDeletePost $permanentlyDelete): RedirectResponse
    {
        abort_unless($post->trashed(), 404);
        $this->authorize('forceDelete', $post);

        $permanentlyDelete($post);

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Post dihapus permanen.']);

        return redirect()->route('admin.posts.trash');
    }

    /**
     * Selaraskan gambar unggulan post ke koleksi media `featured` (singleFile).
     * Media pilihan dari MediaPicker di-copy (bukan dipindah) ke koleksi post ini
     * supaya media asal — bila kepunyaan model lain — tidak ikut berpindah/terhapus.
     * No-op bila id yang dipilih sama dengan media featured yang sudah terpasang
     * (mencegah duplikasi & regenerasi conversion setiap kali post disimpan ulang).
     */
    private function syncFeaturedMedia(Post $post, ?int $mediaId): void
    {
        $currentMediaId = $post->getFirstMedia('featured')?->id;

        if ($mediaId === $currentMediaId) {
            return;
        }

        if ($mediaId === null) {
            $post->getFirstMedia('featured')?->delete();

            return;
        }

        Media::findOrFail($mediaId)->copy($post, 'featured');
    }

    /**
     * Upsert translation per bahasa untuk post — hanya menambah/mengubah,
     * tidak menghapus translation bahasa yang tak disertakan di request.
     * Slug dijaga unik per bahasa (constraint DB: unique(language_id, slug)).
     *
     * @param  list<array{language_id: int, title?: ?string, slug?: ?string, body?: ?string, status: string, published_at?: ?string, meta_title?: ?string, meta_description?: ?string}>  $translations
     */
    private function syncTranslations(Post $post, array $translations): void
    {
        foreach ($translations as $translation) {
            $languageId = (int) $translation['language_id'];
            $title = (string) ($translation['title'] ?? '');

            $existing = PostTranslation::query()
                ->where('post_id', $post->id)
                ->where('language_id', $languageId)
                ->first();

            $slugSource = filled($translation['slug'] ?? null)
                ? (string) $translation['slug']
                : (filled($title) ? $title : "draft-{$post->id}-{$languageId}");
            $slug = ContentSlug::unique(
                PostTranslation::class,
                $slugSource,
                $existing?->id,
                'slug',
                ['language_id' => $languageId],
            );

            $body = $translation['body'] ?? null;

            $post->translations()->updateOrCreate(
                ['language_id' => $languageId],
                [
                    'title' => $title,
                    'slug' => $slug,
                    'body' => $body !== null ? $this->sanitizer->cleanRichText($body) : null,
                    'status' => $translation['status'],
                    'published_at' => $translation['published_at'] ?? null,
                    'meta_title' => $translation['meta_title'] ?? null,
                    'meta_description' => $translation['meta_description'] ?? null,
                ],
            );
        }
    }

    /**
     * @param  Collection<int, Language>  $languages
     * @return array{id: int, title: string, typeName: string, typeSlug: string, status: ?string, statuses: array<int, array{code: string, label: string, name: string, status: ?string}>, author: string, updated_at: string, editUrl: string}
     */
    private function toSummary(
        Post $post,
        int $currentLanguageId,
        Collection $languages,
    ): array {
        $translation = $post->translations->firstWhere('language_id', $currentLanguageId)
            ?? $post->translations->first();

        $typeTranslation = $post->type->translations->firstWhere('language_id', $currentLanguageId)
            ?? $post->type->translations->first();

        return [
            'id' => $post->id,
            'title' => $translation !== null ? $translation->title : '(tanpa judul)',
            'typeName' => $typeTranslation !== null ? $typeTranslation->name : $post->type->slug,
            'typeSlug' => $post->type->slug,
            'status' => $translation?->status?->value,
            'statuses' => $languages
                ->map(function (Language $language) use ($post): array {
                    $tr = $post->translations->firstWhere('language_id', $language->id);

                    return [
                        'code' => $language->code,
                        'label' => strtoupper($language->code),
                        'name' => $language->name,
                        'status' => $tr?->status?->value,
                    ];
                })
                ->values()
                ->all(),
            'author' => $post->author !== null ? $post->author->name : '-',
            'updated_at' => $post->updated_at?->toIso8601String() ?? '',
            'editUrl' => route('admin.posts.edit', $post->id),
        ];
    }

    /**
     * @param  Collection<int, Language>  $languages
     * @return array{id: int, title: string, typeName: string, author: string, deleted_at: string, canRestore: bool, canForceDelete: bool, statuses: array<int, array{code: string, label: string, name: string, status: ?string}>}
     */
    private function toTrashSummary(Post $post, int $currentLanguageId, ?User $user, Collection $languages): array
    {
        $translation = $post->translations->firstWhere('language_id', $currentLanguageId)
            ?? $post->translations->first();
        $typeTranslation = $post->type->translations->firstWhere('language_id', $currentLanguageId)
            ?? $post->type->translations->first();

        return [
            'id' => $post->id,
            'title' => $translation !== null ? $translation->title : '(tanpa judul)',
            'typeName' => $typeTranslation !== null ? $typeTranslation->name : $post->type->slug,
            'author' => $post->author !== null ? $post->author->name : '-',
            'deleted_at' => $post->deleted_at?->toIso8601String() ?? '',
            'canRestore' => $user?->can('restore', $post) ?? false,
            'canForceDelete' => $user?->can('forceDelete', $post) ?? false,
            'statuses' => $languages
                ->map(function (Language $language) use ($post): array {
                    $tr = $post->translations->firstWhere('language_id', $language->id);

                    return [
                        'code' => $language->code,
                        'label' => strtoupper($language->code),
                        'name' => $language->name,
                        'status' => $tr?->status?->value,
                    ];
                })
                ->values()
                ->all(),
        ];
    }

    /**
     * @return array{id: int, type_id: int, category_id: ?int, featured_media: null|array{id: int, url: string, src: string, srcset: string, thumb_url: string, alt: string}, tag_ids: list<int>, translations: array<string, array{language_id: int, title: string, slug: string, body: ?string, status: string, published_at: ?string, meta_title: ?string, meta_description: ?string}>}
     */
    private function toFormArray(Post $post): array
    {
        $languagesByCode = Language::active()->get(['id', 'code'])->keyBy('id');
        $featuredMedia = $post->getFirstMedia('featured');

        return [
            'id' => $post->id,
            'type_id' => $post->type_id,
            'category_id' => $post->category_id,
            'featured_media' => PostFeaturedImage::from($post->getFirstMedia('featured')),
            'tag_ids' => array_values(
                $post->tags->pluck('id')->map(fn ($id): int => (int) $id)->all(),
            ),
            'translations' => $post->translations
                ->mapWithKeys(function (PostTranslation $t) use ($languagesByCode): array {
                    $language = $languagesByCode->get($t->language_id);
                    $code = $language !== null ? $language->code : (string) $t->language_id;

                    return [$code => [
                        'language_id' => $t->language_id,
                        'title' => $t->title,
                        'slug' => $t->slug,
                        'body' => $t->body,
                        'status' => $t->status->value,
                        'published_at' => $t->published_at?->toDateTimeString(),
                        'meta_title' => $t->meta_title,
                        'meta_description' => $t->meta_description,
                    ]];
                })
                ->all(),
        ];
    }

    /**
     * @return array<int, array{id: int, code: string, name: string}>
     */
    private function languageOptions(): array
    {
        return Language::active()
            ->get(['id', 'code', 'name'])
            ->map(fn (Language $lang): array => [
                'id' => $lang->id,
                'code' => $lang->code,
                'name' => $lang->name,
            ])
            ->values()
            ->all();
    }

    /**
     * @return array<int, array{id: int, slug: string, name: string, writing_style_id: ?int}>
     */
    private function contentTypeOptions(): array
    {
        $currentLanguageId = Language::current()->id;

        return ContentType::query()
            ->with('translations')
            ->orderBy('sort_order')
            ->get()
            ->map(function (ContentType $contentType) use ($currentLanguageId): array {
                $translation = $contentType->translations->firstWhere('language_id', $currentLanguageId)
                    ?? $contentType->translations->first();

                return [
                    'id' => $contentType->id,
                    'slug' => $contentType->slug,
                    'name' => $translation !== null ? $translation->name : $contentType->slug,
                    'writing_style_id' => $contentType->writing_style_id,
                ];
            })
            ->values()
            ->all();
    }

    /**
     * @return array<int, array{id: int, name: string}>
     */
    private function categoryOptions(): array
    {
        $currentLanguageId = Language::current()->id;

        $categories = Category::query()
            ->with('translations')
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get();

        return CategoryTree::flatten($categories)
            ->map(function (array $node) use ($currentLanguageId): array {
                $category = $node['category'];
                $translation = $category->translations->firstWhere('language_id', $currentLanguageId)
                    ?? $category->translations->first();

                return [
                    'id' => $category->id,
                    'name' => str_repeat('— ', $node['depth'])
                        .($translation !== null ? $translation->name : $category->slug),
                ];
            })
            ->values()
            ->all();
    }

    /**
     * Apakah query post harus dibatasi ke milik user (Author tanpa role Admin/Editor).
     */
    private function isAuthorScoped(?User $user): bool
    {
        return $user !== null
            && $user->hasRole(UserRole::Author->value)
            && ! $user->hasAnyRole([UserRole::Admin->value, UserRole::Editor->value]);
    }

    /**
     * @return array<int, array{id: int, name: string}>
     */
    private function tagOptions(): array
    {
        $currentLanguageId = Language::current()->id;

        return Tag::query()
            ->with('translations')
            ->get()
            ->map(function (Tag $tag) use ($currentLanguageId): array {
                $translation = $tag->translations->firstWhere('language_id', $currentLanguageId)
                    ?? $tag->translations->first();

                return [
                    'id' => $tag->id,
                    'name' => $translation !== null ? $translation->name : $tag->slug,
                ];
            })
            ->values()
            ->all();
    }

    /**
     * Gabungkan id tag yang sudah dipilih (`tags[]`) dengan tag baru yang
     * diketik di editor (`new_tags[]`, create-on-type). Nama baru dicocokkan
     * dahulu (case-insensitive, firstOrCreate) supaya tag yang sudah ada
     * tidak diduplikasi; nama yang benar-benar baru membuat Tag baru.
     *
     * @param  list<int|string>  $existingIds
     * @param  list<string>  $newTagNames
     * @return list<int>
     */
    private function resolveTagIds(array $existingIds, array $newTagNames): array
    {
        $ids = array_map(fn (int|string $id): int => (int) $id, $existingIds);

        $seenNames = [];

        foreach ($newTagNames as $name) {
            $name = trim($name);
            $normalized = mb_strtolower($name);

            if ($name === '' || in_array($normalized, $seenNames, true)) {
                continue;
            }

            $seenNames[] = $normalized;
            $ids[] = $this->findOrCreateTagIdByName($name);
        }

        return array_values(array_unique($ids));
    }

    /**
     * Cari Tag yang salah satu translation-nya cocok $name (case-insensitive,
     * bahasa apa pun) — bila tidak ada, buat Tag baru + TagTranslation dengan
     * nama yang sama di SEMUA bahasa aktif (default aman karena create-on-type
     * hanya mengetik satu nama, bukan per-bahasa; mengikuti pola
     * TagController::syncTranslations, bukan skema baru).
     *
     * Keputusan otorisasi (DISENGAJA): pembuatan Tag di sini TIDAK lewat
     * TagPolicy::create (butuh permission `content-types.create`, yang hanya
     * dimiliki Admin — Editor & Author pun tidak punya). Otorisasi method ini
     * ikut PostPolicy::create/update (via PostRequest::authorize()) yang sudah
     * dijalankan sebelum controller method store()/update() dipanggil — supaya
     * siapa pun yang berhak menulis post (termasuk Author, atas post miliknya)
     * bisa membuat tag baru inline saat mengetik, tanpa perlu capability
     * taksonomi terpisah. Batas abuse dijaga validasi `new_tags` `max:20`
     * (PostRequest) — bukan oleh TagPolicy.
     */
    private function findOrCreateTagIdByName(string $name): int
    {
        $existing = Tag::query()
            ->whereHas('translations', fn ($q) => $q->whereRaw('LOWER(name) = ?', [mb_strtolower($name)]))
            ->first();

        if ($existing !== null) {
            return $existing->id;
        }

        $tag = Tag::create(['slug' => ContentSlug::unique(Tag::class, $name)]);

        foreach (Language::active()->get(['id']) as $language) {
            $tag->translations()->create([
                'language_id' => $language->id,
                'name' => $name,
            ]);
        }

        return $tag->id;
    }
}
