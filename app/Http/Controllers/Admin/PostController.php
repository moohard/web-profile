<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

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
use App\Support\ContentSlug;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

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
            ->through(fn (Post $post): array => $this->toSummary($post, $currentLanguageId));

        return Inertia::render('admin/posts/index', [
            'posts' => $posts,
            'contentTypes' => $this->contentTypeOptions(),
            'filters' => [
                'type' => $typeFilter,
                'status' => $statusFilter,
            ],
        ]);
    }

    /**
     * Daftar post yang sudah di-trash (soft deleted) — Author hanya melihat miliknya.
     */
    public function trash(Request $request): Response
    {
        $this->authorize('viewAny', Post::class);

        $user = $request->user();
        $currentLanguageId = Language::current()->id;

        $posts = Post::onlyTrashed()
            ->with(['type.translations', 'translations', 'author'])
            ->when(
                $this->isAuthorScoped($user),
                fn ($query) => $query->where('author_id', $user->id),
            )
            ->latest('deleted_at')
            ->paginate(20)
            ->withQueryString()
            ->through(fn (Post $post): array => $this->toSummary($post, $currentLanguageId));

        return Inertia::render('admin/posts/trash', [
            'posts' => $posts,
            'canManageTrash' => $user !== null && $user->hasAnyRole([UserRole::Admin->value, UserRole::Editor->value]),
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
    public function store(PostRequest $request): RedirectResponse
    {
        $data = $request->validated();
        $userId = $request->user()?->id;

        DB::transaction(function () use ($data, $userId): void {
            $post = Post::create([
                'type_id' => $data['type_id'],
                'category_id' => $data['category_id'] ?? null,
                'author_id' => $userId,
                'featured_image' => $data['featured_image'] ?? null,
            ]);

            $post->tags()->sync($data['tags'] ?? []);

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

        $post->load(['translations', 'tags']);

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
    public function update(PostRequest $request, Post $post): RedirectResponse
    {
        $data = $request->validated();

        DB::transaction(function () use ($data, $post): void {
            $post->update([
                'type_id' => $data['type_id'],
                'category_id' => $data['category_id'] ?? null,
                'featured_image' => $data['featured_image'] ?? null,
            ]);

            $post->tags()->sync($data['tags'] ?? []);

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

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Post berhasil dihapus.']);

        return redirect()->route('admin.posts.index');
    }

    /**
     * Kembalikan post dari trash — Admin/Editor saja (lihat PostPolicy::restore).
     */
    public function restore(Post $post): RedirectResponse
    {
        $this->authorize('restore', $post);

        $post->restore();

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Post berhasil dikembalikan.']);

        return redirect()->route('admin.posts.trash');
    }

    /**
     * Hapus post permanen — translations (FK cascade) & media (Spatie) ikut terhapus.
     */
    public function forceDelete(Post $post): RedirectResponse
    {
        $this->authorize('forceDelete', $post);

        $post->forceDelete();

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Post berhasil dihapus permanen.']);

        return redirect()->route('admin.posts.trash');
    }

    /**
     * Upsert translation per bahasa untuk post — hanya menambah/mengubah,
     * tidak menghapus translation bahasa yang tak disertakan di request.
     * Slug dijaga unik per bahasa (constraint DB: unique(language_id, slug)).
     *
     * @param  list<array{language_id: int, title: string, slug?: ?string, body?: ?string, status: string, published_at?: ?string, meta_title?: ?string, meta_description?: ?string}>  $translations
     */
    private function syncTranslations(Post $post, array $translations): void
    {
        foreach ($translations as $translation) {
            $languageId = (int) $translation['language_id'];

            $existing = PostTranslation::query()
                ->where('post_id', $post->id)
                ->where('language_id', $languageId)
                ->first();

            $slugSource = $translation['slug'] ?? $translation['title'];
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
                    'title' => $translation['title'],
                    'slug' => $slug,
                    'body' => $body !== null ? $this->sanitizer->clean($body) : null,
                    'status' => $translation['status'],
                    'published_at' => $translation['published_at'] ?? null,
                    'meta_title' => $translation['meta_title'] ?? null,
                    'meta_description' => $translation['meta_description'] ?? null,
                ],
            );
        }
    }

    /**
     * @return array{id: int, title: string, typeName: string, typeSlug: string, status: ?string, author: string, updated_at: string, editUrl: string}
     */
    private function toSummary(Post $post, int $currentLanguageId): array
    {
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
            'author' => $post->author !== null ? $post->author->name : '-',
            'updated_at' => $post->updated_at?->toIso8601String() ?? '',
            'editUrl' => route('admin.posts.edit', $post->id),
        ];
    }

    /**
     * @return array{id: int, type_id: int, category_id: ?int, featured_image: ?string, tag_ids: list<int>, translations: array<string, array{language_id: int, title: string, slug: string, body: ?string, status: string, published_at: ?string, meta_title: ?string, meta_description: ?string}>}
     */
    private function toFormArray(Post $post): array
    {
        $languagesByCode = Language::active()->get(['id', 'code'])->keyBy('id');

        return [
            'id' => $post->id,
            'type_id' => $post->type_id,
            'category_id' => $post->category_id,
            'featured_image' => $post->featured_image,
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

        return Category::query()
            ->with('translations')
            ->orderBy('sort_order')
            ->get()
            ->map(function (Category $category) use ($currentLanguageId): array {
                $translation = $category->translations->firstWhere('language_id', $currentLanguageId)
                    ?? $category->translations->first();

                return [
                    'id' => $category->id,
                    'name' => $translation !== null ? $translation->name : $category->slug,
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
}
