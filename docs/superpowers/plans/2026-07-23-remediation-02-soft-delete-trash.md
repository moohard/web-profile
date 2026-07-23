# Soft Delete and Trash Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use `superpowers:executing-plans` task-by-task. Every behavior change follows RED-GREEN-REFACTOR.

**Goal:** Mengubah lifecycle Post dan Page dari hard delete menjadi soft delete, menyediakan Trash terotorisasi, serta memastikan force-delete membersihkan seluruh data terkait.

**Architecture:** `SoftDeletes` menjadi global boundary visibility. Controller hanya mengotorisasi dan mendelegasikan permanent deletion ke Action class transaksional. Implicit binding route Trash memakai `withTrashed()`. Media Library v11 mempertahankan media pada soft delete dan menghapusnya ketika model di-`forceDelete()`.

**Tech Stack:** Laravel 13.20, Eloquent SoftDeletes, Spatie Media Library 11.23, Inertia React 3, Wayfinder 0.1, Pest 4.

## Context7 Decisions

- `/laravel/docs/__branch__13.x`: gunakan `onlyTrashed()`, route `withTrashed()`, `restore()`, dan `forceDelete()`.
- `/spatie/laravel-medialibrary`: `InteractsWithMedia` melewati cleanup ketika soft delete dan menjalankan cleanup media ketika force-delete.

---

### Task 1: Tambahkan schema dan lifecycle model

**Files:**
- Create: `database/migrations/*_add_deleted_at_to_posts_table.php`
- Create: `database/migrations/*_add_deleted_at_to_pages_table.php`
- Modify: `app/Models/Post.php`
- Modify: `app/Models/Page.php`
- Create: `tests/Feature/Admin/ContentTrashLifecycleTest.php`

- [x] **Step 1: Tulis tes RED lifecycle dasar**

Uji Post dan Page: `delete()` mengisi `deleted_at`, query default tidak menemukan record, `withTrashed()` menemukannya, translation dan media tetap ada.

- [x] **Step 2: Jalankan tes RED**

```bash
APP_KEY='base64:MDEyMzQ1Njc4OWFiY2RlZjAxMjM0NTY3ODlhYmNkZWY=' php artisan test --compact tests/Feature/Admin/ContentTrashLifecycleTest.php
```

Expected: gagal karena kolom `deleted_at` dan trait `SoftDeletes` belum ada.

- [x] **Step 3: Generate migration dan implementasikan model**

Gunakan dua perintah `php artisan make:migration ... --no-interaction`, tambahkan `$table->softDeletes()`, reversible `dropSoftDeletes()`, lalu pakai trait `SoftDeletes` pada kedua model.

- [x] **Step 4: Jalankan tes GREEN dan rollback check**

```bash
php artisan migrate:fresh --seed --no-interaction
APP_KEY='base64:MDEyMzQ1Njc4OWFiY2RlZjAxMjM0NTY3ODlhYmNkZWY=' php artisan test --compact tests/Feature/Admin/ContentTrashLifecycleTest.php
```

### Task 2: Kunci policy, route, dan daftar Trash

**Files:**
- Modify: `app/Policies/PostPolicy.php`
- Modify: `app/Policies/PagePolicy.php`
- Modify: `routes/admin.php`
- Modify: `app/Http/Controllers/Admin/PostController.php`
- Modify: `app/Http/Controllers/Admin/PageController.php`
- Modify: `tests/Feature/Admin/ContentTrashLifecycleTest.php`

- [x] **Step 1: Tulis tes RED matriks akses**

Uji Admin/Editor dapat melihat, restore, dan force-delete Post/Page. Author hanya melihat dan restore Post miliknya; Post milik user lain dan seluruh Page Trash menghasilkan 403 atau tidak muncul.

- [x] **Step 2: Tambahkan policy dan route minimum**

Tambahkan ability `viewTrash`, `restore`, dan `forceDelete`. Route typed:

- `GET admin/posts/trash`
- `PATCH admin/posts/{post}/restore`
- `DELETE admin/posts/{post}/force-delete`
- ekuivalen untuk Pages

Route binding restore/force-delete wajib `withTrashed()`.

- [x] **Step 3: Implementasikan controller tipis**

Query daftar memakai `onlyTrashed()`, ownership filter untuk Author, eager load translation/author yang dibutuhkan, pagination, dan `withQueryString()`.

- [x] **Step 4: Jalankan tes GREEN**

Run file lifecycle dan `PostPolicyTest.php`.

### Task 3: Bersihkan relasi saat force-delete

**Files:**
- Create: `app/Actions/Posts/PermanentlyDeletePost.php`
- Create: `app/Actions/Pages/PermanentlyDeletePage.php`
- Modify: `app/Http/Controllers/Admin/PostController.php`
- Modify: `app/Http/Controllers/Admin/PageController.php`
- Modify: `tests/Feature/Admin/ContentTrashLifecycleTest.php`

- [x] **Step 1: Tulis tes RED cleanup**

Untuk Post: translation, pivot tag, MenuItem `ContentSingle`, WidgetPlacementTarget `ContentSingle`, media DB, dan file storage terhapus permanen.

Untuk Page: translation, MenuItem `Page`, WidgetPlacementTarget `Page`, media DB, dan file storage terhapus permanen.

- [x] **Step 2: Implementasikan Action transaksional**

Hapus target menu/widget berdasarkan `target_ref`, lalu panggil `forceDelete()` sebagai operasi terakhir. Biarkan FK cascade membersihkan translation/pivot dan Media Library membersihkan media.

- [x] **Step 3: Jalankan tes GREEN**

Run file lifecycle saja, kemudian seluruh tes Media terkait untuk regresi.

### Task 4: Pastikan seluruh permukaan publik mengecualikan Trash

**Files:**
- Modify: `app/Support/PublicPathResolver.php`
- Modify: `app/Http/Controllers/Public/HomeController.php`
- Modify: `app/Console/Commands/GenerateSitemap.php`
- Modify: `tests/Feature/Admin/ContentTrashLifecycleTest.php`
- Modify: `tests/Feature/SitemapTest.php`

- [x] **Step 1: Tulis tes RED public visibility**

Uji trashed Post/Page tidak muncul di resolver, home, archive, single, sitemap, menu URL, dan widget target.

- [x] **Step 2: Perketat query child-first**

Query yang berangkat dari `PostTranslation`/`PageTranslation` wajib `whereHas('post')`/`whereHas('page')` agar global scope SoftDeletes parent ikut berlaku.

- [x] **Step 3: Jalankan tes GREEN**

Run lifecycle, Public resolver/controller tests, menu/widget tests, dan sitemap.

### Task 5: Bangun UI Trash dengan Wayfinder

**Files:**
- Create: `resources/js/components/admin/permanent-delete-dialog.tsx`
- Create: `resources/js/pages/admin/posts/trash.tsx`
- Create: `resources/js/pages/admin/pages/trash.tsx`
- Modify: `resources/js/pages/admin/posts/index.tsx`
- Modify: `resources/js/pages/admin/pages/index.tsx`
- Generated: `resources/js/routes/**`

- [x] **Step 1: Generate Wayfinder setelah route tersedia**

```bash
php artisan wayfinder:generate --with-form --no-interaction
```

- [x] **Step 2: Implementasikan daftar Trash**

Gunakan route typed untuk link, restore, dan force-delete. Dialog harus menyatakan tindakan permanen tidak dapat dibatalkan. Author tidak menerima action force-delete dari server props.

- [x] **Step 3: Tambahkan navigation Trash**

Tambahkan tombol Trash pada index Post/Page; jangan menambah hardcoded admin URL.

- [x] **Step 4: Jalankan TypeScript, ESLint, dan Prettier**

```bash
npm run types:check
npm run lint:check
npm run format:check
```

### Task 6: Quality gate dan review checkpoint

- [x] Jalankan `vendor/bin/pint --dirty --format agent`.
- [x] Jalankan PHPStan penuh.
- [x] Jalankan seluruh tes lifecycle, policy, media, public, sitemap, Post, dan Page.
- [x] Jalankan review kepatuhan spec lalu review kualitas kode; Critical/Important wajib ditutup.
- [x] Commit plan dan hasil Fase 2 sebelum memulai Fase 3.
