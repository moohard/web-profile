<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Actions\Pages\PermanentlyDeletePage;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\PageRequest;
use App\Models\Language;
use App\Models\Page;
use App\Models\PageTranslation;
use App\Models\User;
use App\Services\Html\Sanitizer;
use App\Support\ContentSlug;
use App\Support\PublicLayoutProps;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class PageController extends Controller
{
    /**
     * Daftar template hardcode — dipakai mode Template (§8.4).
     *
     * @var list<array{key: string, label: string}>
     */
    private const TEMPLATE_OPTIONS = [
        ['key' => 'default', 'label' => 'Default'],
        ['key' => 'full-width', 'label' => 'Full width'],
        ['key' => 'landing', 'label' => 'Landing'],
    ];

    public function __construct(private readonly Sanitizer $sanitizer) {}

    /**
     * Daftar halaman — filter status opsional.
     */
    public function index(Request $request): Response
    {
        $this->authorize('viewAny', Page::class);

        $currentLanguageId = Language::current()->id;
        $statusFilter = $request->string('status')->value() ?: null;

        $pages = Page::query()
            ->with('translations')
            ->when(
                $statusFilter !== null,
                fn ($query) => $query->whereHas(
                    'translations',
                    fn ($q) => $q->where('language_id', $currentLanguageId)->where('status', $statusFilter),
                ),
            )
            ->latest('updated_at')
            ->paginate(20)
            ->withQueryString()
            ->through(fn (Page $page): array => $this->toSummary($page, $currentLanguageId));

        return Inertia::render('admin/pages/index', [
            'pages' => $pages,
            'filters' => [
                'status' => $statusFilter,
            ],
        ]);
    }

    public function trash(Request $request): Response
    {
        $this->authorize('viewTrash', Page::class);

        $user = $request->user();
        $currentLanguageId = Language::current()->id;
        $pages = Page::onlyTrashed()
            ->with('translations')
            ->latest('deleted_at')
            ->paginate(20)
            ->withQueryString()
            ->through(fn (Page $page): array => $this->toTrashSummary($page, $currentLanguageId, $user));

        return Inertia::render('admin/pages/trash', [
            'pages' => $pages,
        ]);
    }

    /**
     * Form pembuatan halaman baru.
     */
    public function create(): Response
    {
        $this->authorize('create', Page::class);

        return Inertia::render('admin/pages/form', [
            'page' => null,
            'languages' => $this->languageOptions(),
            'canUseCodeMode' => Gate::allows('use-page-code-mode'),
            'templateOptions' => self::TEMPLATE_OPTIONS,
        ]);
    }

    /**
     * Simpan halaman baru + translation per bahasa yang diisi.
     */
    public function store(PageRequest $request): RedirectResponse
    {
        $data = $request->validated();

        DB::transaction(function () use ($data): void {
            $page = Page::create([
                'mode' => $data['mode'],
                'template_key' => $data['template_key'],
                'hero_enabled' => $data['hero_enabled'],
                'hero_image' => $data['hero_image'] ?? null,
                'sidebar_enabled' => $data['sidebar_enabled'],
            ]);

            $this->syncTranslations($page, $data['translations']);
        });

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Halaman berhasil dibuat.']);

        return redirect()->route('admin.pages.index');
    }

    /**
     * Form perubahan halaman.
     */
    public function edit(Page $page): Response
    {
        $this->authorize('update', $page);

        $page->load('translations');

        return Inertia::render('admin/pages/form', [
            'page' => $this->toFormArray($page),
            'languages' => $this->languageOptions(),
            'canUseCodeMode' => Gate::allows('use-page-code-mode'),
            'templateOptions' => self::TEMPLATE_OPTIONS,
        ]);
    }

    /**
     * Perbarui halaman: upsert translations, ganti hero image/mode/template.
     */
    public function update(PageRequest $request, Page $page): RedirectResponse
    {
        $data = $request->validated();

        DB::transaction(function () use ($data, $page): void {
            $page->update([
                'mode' => $data['mode'],
                'template_key' => $data['template_key'],
                'hero_enabled' => $data['hero_enabled'],
                'hero_image' => $data['hero_image'] ?? null,
                'sidebar_enabled' => $data['sidebar_enabled'],
            ]);

            $this->syncTranslations($page, $data['translations']);
        });

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Halaman berhasil diperbarui.']);

        return redirect()->route('admin.pages.index');
    }

    /**
     * Hapus halaman.
     */
    public function destroy(Page $page): RedirectResponse
    {
        $this->authorize('delete', $page);

        $page->delete();
        PublicLayoutProps::flushCache();

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Halaman berhasil dihapus.']);

        return redirect()->route('admin.pages.index');
    }

    public function restore(Page $page): RedirectResponse
    {
        abort_unless($page->trashed(), 404);
        $this->authorize('restore', $page);

        $page->restore();
        PublicLayoutProps::flushCache();

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Halaman berhasil dipulihkan.']);

        return redirect()->route('admin.pages.trash');
    }

    public function forceDelete(Page $page, PermanentlyDeletePage $permanentlyDelete): RedirectResponse
    {
        abort_unless($page->trashed(), 404);
        $this->authorize('forceDelete', $page);

        $permanentlyDelete($page);

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Halaman dihapus permanen.']);

        return redirect()->route('admin.pages.trash');
    }

    /**
     * Upsert translation per bahasa untuk halaman — hanya menambah/mengubah,
     * tidak menghapus translation bahasa yang tak disertakan di request.
     * Slug dijaga unik per bahasa (constraint DB: unique(language_id, slug)).
     *
     * @param  list<array{language_id: int, title: string, slug?: ?string, content?: ?string, hero_heading?: ?string, hero_subheading?: ?string, hero_cta_text?: ?string, hero_cta_link?: ?string, status: string, meta_title?: ?string, meta_description?: ?string}>  $translations
     */
    private function syncTranslations(Page $page, array $translations): void
    {
        foreach ($translations as $translation) {
            $languageId = (int) $translation['language_id'];

            $existing = PageTranslation::query()
                ->where('page_id', $page->id)
                ->where('language_id', $languageId)
                ->first();

            $slugSource = $translation['slug'] ?? $translation['title'];
            $slug = ContentSlug::unique(
                PageTranslation::class,
                $slugSource,
                $existing?->id,
                'slug',
                ['language_id' => $languageId],
            );

            $content = $translation['content'] ?? '';

            $page->translations()->updateOrCreate(
                ['language_id' => $languageId],
                [
                    'title' => $translation['title'],
                    'slug' => $slug,
                    'content' => ['html' => $this->sanitizer->clean($content)],
                    'hero_heading' => $translation['hero_heading'] ?? null,
                    'hero_subheading' => $translation['hero_subheading'] ?? null,
                    'hero_cta_text' => $translation['hero_cta_text'] ?? null,
                    'hero_cta_link' => $translation['hero_cta_link'] ?? null,
                    'status' => $translation['status'],
                    'meta_title' => $translation['meta_title'] ?? null,
                    'meta_description' => $translation['meta_description'] ?? null,
                ],
            );
        }
    }

    /**
     * @return array{id: int, title: string, mode: string, status: ?string, updated_at: string, editUrl: string}
     */
    private function toSummary(Page $page, int $currentLanguageId): array
    {
        $translation = $page->translations->firstWhere('language_id', $currentLanguageId)
            ?? $page->translations->first();

        return [
            'id' => $page->id,
            'title' => $translation !== null ? $translation->title : '(tanpa judul)',
            'mode' => $page->mode->value,
            'status' => $translation?->status,
            'updated_at' => $page->updated_at?->toIso8601String() ?? '',
            'editUrl' => route('admin.pages.edit', $page->id),
        ];
    }

    /**
     * @return array{id: int, title: string, mode: string, deleted_at: string, canRestore: bool, canForceDelete: bool}
     */
    private function toTrashSummary(Page $page, int $currentLanguageId, ?User $user): array
    {
        $translation = $page->translations->firstWhere('language_id', $currentLanguageId)
            ?? $page->translations->first();

        return [
            'id' => $page->id,
            'title' => $translation !== null ? $translation->title : '(tanpa judul)',
            'mode' => $page->mode->value,
            'deleted_at' => $page->deleted_at?->toIso8601String() ?? '',
            'canRestore' => $user?->can('restore', $page) ?? false,
            'canForceDelete' => $user?->can('forceDelete', $page) ?? false,
        ];
    }

    /**
     * @return array{id: int, mode: string, template_key: string, hero_enabled: bool, hero_image: ?string, sidebar_enabled: bool, translations: array<string, array{language_id: int, title: string, slug: string, content: string, hero_heading: ?string, hero_subheading: ?string, hero_cta_text: ?string, hero_cta_link: ?string, status: string, meta_title: ?string, meta_description: ?string}>}
     */
    private function toFormArray(Page $page): array
    {
        $languagesByCode = Language::active()->get(['id', 'code'])->keyBy('id');

        return [
            'id' => $page->id,
            'mode' => $page->mode->value,
            'template_key' => $page->template_key,
            'hero_enabled' => $page->hero_enabled,
            'hero_image' => $page->hero_image,
            'sidebar_enabled' => $page->sidebar_enabled,
            'translations' => $page->translations
                ->mapWithKeys(function (PageTranslation $t) use ($languagesByCode): array {
                    $language = $languagesByCode->get($t->language_id);
                    $code = $language !== null ? $language->code : (string) $t->language_id;

                    $content = $t->content;
                    $html = is_array($content) && isset($content['html']) && is_string($content['html'])
                        ? $content['html']
                        : '';

                    return [$code => [
                        'language_id' => $t->language_id,
                        'title' => $t->title,
                        'slug' => $t->slug,
                        'content' => $html,
                        'hero_heading' => $t->hero_heading,
                        'hero_subheading' => $t->hero_subheading,
                        'hero_cta_text' => $t->hero_cta_text,
                        'hero_cta_link' => $t->hero_cta_link,
                        'status' => $t->status,
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
}
