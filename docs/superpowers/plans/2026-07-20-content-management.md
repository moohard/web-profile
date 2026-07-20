# Rencana Implementasi — Subsistem Konten (CRUD + Editor)

> **For agentic workers:** Implementasikan task-by-task secara TDD (test dulu → gagal → implement → hijau → verifikasi). Step pakai checkbox `- [ ]`.

**Goal:** Membuat CMS bisa **dikelola tanpa menyentuh kode** untuk pilar Konten (PRD §9.1): CRUD Jenis Konten, Kategori, Tag, dan Posts, lengkap dengan **editor konten multi-bahasa** (Opsi B) + tombol **Terjemahkan (AI)** dan **Koreksi (AI)**. Ini subsistem fitur pertama di atas pondasi 7 fase.

**Spec sumber:** PRD `docs/PRD-Website-Company-Profile-CMS-v1.0.md` §4.1, §7.1, §8.5, §9.1; pondasi `docs/superpowers/plans/2026-07-19-cms-foundation-rev.md`.

**Prasyarat (sudah ada di pondasi — REUSE, jangan buat ulang):**
- Model + migrasi + relasi: `Post`, `PostTranslation` (scope `published`, cast `PostStatus`), `Category`/`CategoryTranslation`, `Tag`/`TagTranslation`, `ContentType`/`ContentTypeTranslation`, `WritingStyle`, pivot `post_tags`.
- `PostPolicy` (viewAny semua; create Admin/Editor/Author; update Admin/Editor semua, Author miliknya; delete=update; deleteOwn). `Post` pakai `#[UsePolicy(PostPolicy::class)]` + `author_id`.
- Media: `Post` `HasMedia` koleksi `featured_image` (singleFile) + konversi WebP; komponen React `MediaPicker` (`onPick(mediaId, url)`), endpoint `admin/media`.
- AI: `AiController::translate|applyTranslation` (allowlist field `title,body,meta_title,meta_description,content` + `authorizeParentUpdate` + `Sanitizer`). Enum `AiTask::{Translation,ContentRefinement,MarkupConform}`. `AiConfig::resolve(AiTask)` (api_key encrypted/hidden).
  - **TRANSLATION = BytePlus Ark `seed-translation-250915`** via `ArkTranslationClient` (Responses API `/responses` + `translation_options`, BUKAN chat/prompt). Config di `config/services.ark.*` (`ARK_API_KEY`/`ARK_BASE_URL`/`ARK_TRANSLATION_MODEL`) → di-seed ke `ai_configs` (task Translation) oleh `AiConfigSeeder` bila key ada. `TranslationTask` mendelegasikan ke `ArkTranslationClient`. **Sudah fungsional (K5 tinggal wiring UI).**
  - **CONTENT_REFINEMENT & MARKUP_CONFORM = chat** → butuh provider OpenAI-compatible terpisah (`AiClient` + `AiConfig` task masing-masing). `CONTENT_REFINEMENT` masih skeleton (diimplementasikan K5); **butuh konfigurasi provider chat tersendiri** (belum disediakan — lihat catatan K5).
  - **Caveat HTML:** seed-translation tak menjamin preservasi tag HTML pada `body`. Perlu diuji dengan konten nyata; bila rusak, terjemahkan hanya `title`/`meta` via AI dan `body` manual, atau tambah penanganan node-level.
- Permission (RolePermissionSeeder): `posts.{viewAny,create,update,delete}`, `posts.deleteOwn`, `content-types.{...}`, `galleries.{...}`; Editor sudah punya `posts.*`; Author `posts.viewAny/create/update/deleteOwn`. **Kategori/Tag/Jenis konten pakai `content-types.*`** (lihat sidebar-nav-config yang ada).
- Shared props Inertia: `contentTypes` (slug,name per-locale), `auth.user.permissions`, `auth.user.canUseCodeMode`.
- Routing admin: rute placeholder bernama sudah ada (`posts.index`, `categories.index`, `tags.index`, `content-types.index`) + helper Wayfinder. Layout admin otomatis untuk `pages/admin/*` (app.tsx). Sanitizer HTML untuk `body`.

**Constraint (warisan pondasi — wajib):** PHP 8.4 `declare(strict_types=1)` + tipe lengkap; PostgreSQL (jsonb bila perlu); role via `spatie/laravel-permission`; **Wayfinder** untuk semua URL React (tanpa string manual); Inertia v3; TDD Pest v4; `vendor/bin/pint --dirty --format agent` tiap ubah PHP; `php artisan make:*` untuk file baru; **tanpa dependency baru tanpa persetujuan** (lihat §Keputusan soal WYSIWYG).

---

## Keputusan Desain

1. **Editor body = textarea HTML + Sanitizer**, bukan WYSIWYG. Alasan: WYSIWYG (mis. TipTap) = dependency baru → butuh persetujuan terpisah. Foundation sudah men-sanitasi `body`. **WYSIWYG dicatat sebagai enhancement opsional** (§Di Luar Lingkup). Preview render aman via panel pratinjau (opsional K4).
2. **Primitives UI tanpa dependency baru.** `components/ui` belum punya `table`, `tabs`, `textarea`. Bangun ringan dengan HTML+Tailwind (tab bahasa = tombol state; tabel = `<table>`; textarea = `<textarea>`) — konsisten dengan `media/index.tsx` (pakai `<select>` polos). Jangan tambah paket Radix baru.
3. **Multi-bahasa Opsi B di editor:** satu layar, **tab per bahasa aktif** (dari `languages`), tiap tab meng-edit satu `PostTranslation` (slug/title/body/status/published_at/meta_*). Simpan sekali → upsert semua translation.
4. **Alur AI non-destruktif** (PRD §7.4): tombol *Terjemahkan*/*Koreksi* memanggil endpoint → tampil sebagai **saran** (Terima/Batalkan) di UI, tidak menimpa otomatis. Reuse `AiController::translate`; tambah `AiController::refine` untuk `CONTENT_REFINEMENT`.
5. **Slug**: auto-generate dari title (client, `slugify`) dengan opsi override; unik per `(content_type, language)` untuk post — validasi di FormRequest. Kategori/Tag/ContentType slug unik global.
6. **Author scoping**: index Posts memfilter agar Author hanya melihat/mengelola miliknya (`author_id`), mengikuti `PostPolicy`. `author_id` di-set otomatis saat create.
7. **Controller** = resource controller konvensional (`make:controller --resource`) di `App\Http\Controllers\Admin`, kembalikan `Inertia::render` / `redirect()->route()`. FormRequest terpisah untuk validasi.

---

## File Structure

```
app/Http/Controllers/Admin/PostController.php          [Create] resource (index/create/store/edit/update/destroy)
app/Http/Controllers/Admin/CategoryController.php      [Create] resource
app/Http/Controllers/Admin/TagController.php           [Create] resource
app/Http/Controllers/Admin/ContentTypeController.php   [Create] resource
app/Http/Requests/Admin/PostRequest.php                [Create] validasi post + translations
app/Http/Requests/Admin/CategoryRequest.php            [Create]
app/Http/Requests/Admin/TagRequest.php                 [Create]
app/Http/Requests/Admin/ContentTypeRequest.php         [Create]
app/Policies/CategoryPolicy.php                        [Create] map ke content-types.*
app/Policies/TagPolicy.php                             [Create] map ke content-types.*
app/Policies/ContentTypePolicy.php                     [Create] map ke content-types.*
app/Services/Ai/Tasks/ContentRefinementTask.php        [Modify] implement suggest()
app/Http/Controllers/Admin/AiController.php            [Modify] + refine()
app/Support/ContentSlug.php                            [Create] helper unik slug (server-side authority)
routes/admin.php                                       [Modify] ganti placeholder → resource routes + ai/refine
database/factories/CategoryFactory.php                 [Create] + CategoryTranslationFactory
database/factories/TagFactory.php                      [Create] + TagTranslationFactory
database/factories/ContentTypeTranslationFactory.php   [Create]

resources/js/pages/admin/posts/index.tsx              [Create] daftar + filter type/status + paginate
resources/js/pages/admin/posts/form.tsx               [Create] create/edit (tab bahasa + AI)
resources/js/pages/admin/categories/index.tsx         [Create] list + inline/modal form
resources/js/pages/admin/tags/index.tsx               [Create]
resources/js/pages/admin/content-types/index.tsx      [Create]
resources/js/pages/admin/content-types/form.tsx       [Create]
resources/js/components/admin/language-tabs.tsx       [Create] tab bahasa reusable
resources/js/components/admin/ai-suggest-button.tsx   [Create] tombol Terjemahkan/Koreksi + review
resources/js/components/admin/data-table.tsx          [Create] tabel ringan (tanpa dep)
resources/js/hooks/use-slugify.ts                     [Create]
```

Tes: `tests/Feature/Admin/{PostCrudTest,CategoryCrudTest,TagCrudTest,ContentTypeCrudTest,ContentEditorTranslationTest,AiRefineTest}.php` + `tests/Feature/PostPolicyTest.php` (extend).

---

## Urutan Task

Dari sederhana → kompleks, tiap task TDD. Fase K1–K2 memberi taksonomi (kategori/tag/jenis) yang dibutuhkan editor Posts (K3–K4). K5 lapisi AI. K6 rapikan otorisasi + navigasi.

- **K1** Kategori & Tag (CRUD + translations) — warm-up pola.
- **K2** Jenis Konten (Content Types) CRUD.
- **K3** Posts: daftar (index) + hapus + filter/otorisasi.
- **K4** Editor Konten (create/store/edit/update) — tab bahasa + media + status/jadwal + SEO.
- **K5** AI di editor: `CONTENT_REFINEMENT` + `AiController::refine` + wiring tombol Terjemahkan/Koreksi.
- **K6** Policies (Category/Tag/ContentType) + permission + sidebar (ganti placeholder) + Wayfinder + verifikasi akhir.

---

## K1 — Kategori & Tag

### Task K1.1: Factories + Policy Category/Tag
- [ ] **Test dulu:** `CategoryCrudTest` — Admin GET `/admin/categories` 200 & render `admin/categories/index`; POST membuat Category + `CategoryTranslation` per bahasa; user tanpa `content-types.viewAny` → 403.
- [ ] `php artisan make:factory CategoryFactory` (+ `CategoryTranslationFactory`, `TagFactory`, `TagTranslationFactory`). State `->withTranslation(locale, langId, ['name'=>...])` mengikuti pola `PostFactory::withTranslation`.
- [ ] `php artisan make:policy CategoryPolicy` (+ Tag) — semua method map ke permission `content-types.*` (`viewAny/create/update/delete`); daftarkan via atribut `#[UsePolicy]` di model atau `Gate`/auto-discovery.

### Task K1.2: Controller + Request + Routes (Category)
- [ ] `php artisan make:controller Admin/CategoryController --resource` (buang method yang tak dipakai; simpan index/store/update/destroy — kategori dikelola inline, tanpa halaman create/edit terpisah).
- [ ] `php artisan make:request Admin/CategoryRequest`: `slug` (nullable→auto), `parent_id` (nullable exists), `sort_order` (int), `translations` (array; `translations.*.language_id` exists, `translations.*.name` required string). `authorize()` delegasi ke policy.
- [ ] `index`: daftar kategori + `translate()` nama untuk locale aktif + pohon (parent/children) + kirim `languages` aktif untuk form.
- [ ] `store`/`update`: transaksi — simpan Category, upsert `CategoryTranslation` per bahasa; slug via `ContentSlug::unique(Category::class, $slug ?? $name)`.
- [ ] `destroy`: cegah hapus bila punya posts (atau set null) — putuskan: **tolak** dengan pesan bila `posts()->exists()`.
- [ ] `routes/admin.php`: ganti placeholder `categories.index` → `Route::resource('categories', CategoryController::class)->only([...])->middleware('permission:content-types.viewAny')` (sesuaikan per-method permission via policy).
- [ ] Verifikasi: `php artisan test --compact --filter=CategoryCrudTest` hijau. `pint --dirty`.

### Task K1.3: Tag (ulangi pola K1.2, lebih ringan — hanya `slug` + `name`)
- [ ] `TagCrudTest` + `TagController` + `TagRequest` + route resource. Verifikasi hijau.

### Task K1.4: UI Kategori & Tag
- [ ] `admin/categories/index.tsx` + `admin/tags/index.tsx`: `DataTable` ringan + form tambah/edit (dialog `components/ui/dialog` yang sudah ada) dengan input nama **per bahasa** (`LanguageTabs`). Pakai `useForm` + Wayfinder `.url()`. Toast sukses via `sonner` (sudah ada).
- [ ] Verifikasi: `npm run types:check`, `npm run lint:check`, `npm run build`.

---

## K2 — Jenis Konten (Content Types)

### Task K2.1: Policy + Request + Controller
- [ ] **Test dulu:** `ContentTypeCrudTest` — CRUD ContentType + translations (name/description); toggle `is_active`; `writing_style_id` opsional; `archive_template_key`/`single_template_key` default `'default'`; hanya `content-types.*`.
- [ ] `ContentTypePolicy` (map `content-types.*`), `ContentTypeRequest` (slug unik, icon nullable, writing_style_id nullable exists, template keys string, is_active bool, sort_order int, translations name required + description nullable).
- [ ] `ContentTypeController --resource` (index/create/store/edit/update/destroy). `destroy` tolak bila `posts()->exists()`.
- [ ] Route resource ganti placeholder `content-types.index`.
- [ ] **Penting:** setelah create/update/delete ContentType, **bust cache** `inertia.content_types.*` dan `public_layout.*` (mereka di-cache 1 jam) agar sidebar dinamis & menu publik ikut ter-update. Tambah `Cache::forget` atau `Cache::flush` selektif di controller.

### Task K2.2: UI Content Types
- [ ] `content-types/index.tsx` (tabel + status aktif + urutan) & `content-types/form.tsx` (slug, icon, writing style select, template keys, tab bahasa name/description). Wayfinder + types/lint/build hijau.

---

## K3 — Posts: Daftar & Hapus

### Task K3.1: PostController index + destroy
- [ ] **Test dulu:** `PostCrudTest`:
  - Admin GET `/admin/posts` 200, render `admin/posts/index`, punya `posts.data`.
  - Filter `?type={slug}` & `?status=Draft|Published` bekerja.
  - **Author hanya melihat post miliknya** (`author_id`); Editor/Admin melihat semua.
  - DELETE menghapus (Admin/Editor semua; Author miliknya → selain miliknya 403). Extend `PostPolicyTest` bila perlu.
- [ ] `PostController --resource`. `index`: query `Post::with('type','translations','author')`, filter type/status (status via `whereHas('translations')` locale aktif), scope Author (`when(author, ->where('author_id', id))`), paginate 20, map ke ringkasan (id, title locale aktif, type name, status, author, updated_at, edit url via Wayfinder). Kirim `contentTypes` untuk filter dropdown.
- [ ] `destroy`: `$this->authorize('delete',$post)` → hapus (translations cascade dari FK).
- [ ] Route resource `posts` (ganti placeholder), per-method policy.

### Task K3.2: UI Posts index
- [ ] `admin/posts/index.tsx`: `DataTable` + filter (type select dari `contentTypes`, status), badge status, tombol Edit (Wayfinder `posts.edit`) & Hapus (konfirmasi). Empty state. types/lint/build hijau.

---

## K4 — Editor Konten (create/edit)

### Task K4.1: store/update + PostRequest (backend)
- [ ] **Test dulu:** `ContentEditorTranslationTest`:
  - `store` membuat Post (type_id, category_id, tags[], featured via media picker) + `PostTranslation` untuk tiap bahasa yang diisi; `author_id` = user; body ter-sanitasi; status/published_at tersimpan; slug unik per (type, language).
  - `update` meng-upsert translations (tambah/ubah), sync tags, ganti featured.
  - Validasi: minimal 1 bahasa terisi (default locale wajib title+slug); slug bentrok → error.
  - Author boleh edit miliknya, tidak boleh milik orang lain (403).
- [ ] `PostRequest`: `type_id` required exists content_types; `category_id` nullable exists; `tags` array of exists; `featured_media_id`/`featured_image` nullable; `translations` array keyed by language_id → { title required (min utk default), slug nullable, body nullable, status in Draft/Published, published_at nullable date, meta_title/meta_description nullable }.
- [ ] `create`: kirim daftar `languages` aktif, `contentTypes`, `categories`, `tags` untuk form kosong.
- [ ] `store`/`update` (transaksi): simpan Post; `ContentSlug::unique` per translation; `body` di-`Sanitizer::clean`; `tags()->sync`; featured via media (set `featured_image` atau relasikan media id yang sudah di-upload lewat MediaPicker); `author_id` saat create.
- [ ] `edit`: muat Post + semua translations (map by language code) + tags terpilih + featured url.

### Task K4.2: UI Editor (`posts/form.tsx`)
- [ ] Dua kolom (PRD §8.5): **kolom utama** = `LanguageTabs` → per bahasa: title, slug (auto via `useSlugify`, bisa override), body `<textarea>`; **kolom samping** = jenis konten (select), kategori (select), tags (multi), featured image (`MediaPicker`), status (select Draft/Published), tanggal publish (input datetime), SEO (meta_title, meta_description).
- [ ] Toggle bahasa + tombol **Terjemahkan dengan AI** & **Koreksi dengan AI** per field teks (komponen `AiSuggestButton`, diaktifkan penuh di K5 — di K4 render disabled/placeholder).
- [ ] Submit via `useForm` → Wayfinder `posts.store`/`posts.update`. Toast + redirect ke index. types/lint/build hijau.

---

## K5 — AI di Editor (Terjemahkan + Koreksi)

### Task K5.1: Implement `ContentRefinementTask` + endpoint
- [ ] **Test dulu:** `AiRefineTest` — mock `AiClient` (pola `AiClientTest`/`AiControllerTest`): POST `/admin/ai/refine` mengembalikan `{suggestion}`; otorisasi via parent update (403 utk non-pemilik); throttle. Tidak menulis DB (non-destruktif).
- [ ] `ContentRefinementTask::suggest(string $text, string $writingStylePrompt): string` — bangun prompt (gaya + teks, pertahankan HTML), `->task(AiTask::ContentRefinement)->chat()`. Hapus `throw`.
- [ ] `AiController::refine(Request)`: validasi `entity_type,entity_id,field(title|body|meta_*),writing_style_id?`; `authorizeParentUpdate`; ambil source text (reuse `extractSourceText`); resolusi `writing_style` (dari ContentType post bila ada, atau `writing_style_id`); panggil task; return `{suggestion}`. Route `POST /admin/ai/refine` `permission:ai.update` + `throttle:30,1`.

### Task K5.2: Wiring UI AI
- [ ] `AiSuggestButton`: mode `translate` (reuse endpoint `ai.translate`) & `refine` (`ai.refine`). Panggil via `useHttp`/router XHR; tampilkan hasil sebagai **saran** (panel Terima/Batalkan). "Terima" → set nilai field di form (non-destruktif; simpan tetap lewat submit form atau `ai.apply-translation`).
- [ ] Aktifkan tombol di `posts/form.tsx` (title/body/meta per bahasa). types/lint/build hijau.

---

## K6 — Otorisasi, Navigasi, Verifikasi

### Task K6.1: Sidebar & Wayfinder
- [ ] `resources/js/components/admin/sidebar-nav-config.ts` sudah pakai Wayfinder `.index.url()` — pastikan entri Kategori/Tag/Jenis konten/Posts mengarah ke rute resource baru (bukan placeholder). Entri dinamis per content type di `app-sidebar.tsx` sudah `posts.index.url({query:{type}})` — verifikasi masih valid.
- [ ] Hapus rute placeholder yang sudah digantikan resource di `routes/admin.php`.

### Task K6.2: Permission review
- [ ] Pastikan `content-types.*` mencakup Kategori/Tag/Jenis (sudah begitu di sidebar). Jika ingin permission granular terpisah (`categories.*`,`tags.*`), tambah di `RolePermissionSeeder` + `migrate:fresh --seed`. **Default: reuse `content-types.*`** (minim perubahan).
- [ ] Editor sudah punya `posts.*`; Author `posts.deleteOwn` — verifikasi `PostPolicy` dipakai di semua aksi.

### Task K6.3: Verifikasi akhir subsistem
- [ ] `php artisan test --compact` — seluruh suite hijau (termasuk tes baru).
- [ ] `vendor/bin/pint --format agent`, `phpstan analyse`, `npm run types:check`, `npm run lint:check`, `npm run build:ssr`.
- [ ] Smoke manual: buat ContentType baru → muncul di sidebar + arsip publik; buat Post 2 bahasa (draft→publish) → tampil di `/{type}/{slug}` & `/en/...` dengan hreflang; Terjemahkan/Koreksi AI memunculkan saran; Author tak bisa mengedit post orang lain.

---

## Kriteria Selesai (Definition of Done)

- CRUD penuh untuk Jenis Konten, Kategori, Tag, Posts dari dashboard **tanpa sentuh kode**.
- Editor konten multi-bahasa (Opsi B) dengan status/jadwal/SEO/featured/tags/kategori.
- Terjemahkan (AI) + Koreksi (AI) fungsional & non-destruktif; `CONTENT_REFINEMENT` tak lagi skeleton.
- Otorisasi role benar (Author = miliknya). Sidebar dinamis konsisten dengan ContentType.
- Semua quality gate hijau; konten yang dibuat tampil benar di frontend publik (SSR/SEO/hreflang yang sudah ada).

---

## Di Luar Lingkup (fase berikutnya)

- **WYSIWYG/rich text editor** untuk `body` (butuh dependency baru → persetujuan terpisah). Sementara: textarea HTML + Sanitizer.
- **Galeri** (CRUD gallery + images) — subsistem tersendiri.
- **Custom fields per jenis konten** (PRD Open Item #3 — ditunda).
- **Halaman (Pages) + mode code + `MARKUP_CONFORM`** — subsistem berikutnya (rencana terpisah).
- **Pratinjau mobile** di editor (PRD §7.5) — enhancement.
- Bulk actions, revisi/riwayat, penjadwalan lanjutan.
