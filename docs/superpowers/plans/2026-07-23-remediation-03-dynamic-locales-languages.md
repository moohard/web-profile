# Dynamic Locales and Languages Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use `superpowers:executing-plans` task-by-task. Every behavior change follows RED-GREEN-REFACTOR.

**Goal:** Mengganti kontrak locale hardcode dengan Languages berbasis database, resolver publik tunggal, URL canonical, serta tautan lintas bahasa yang memahami slug translation.

**Architecture:** `LanguageController` hanya mengotorisasi dan mendelegasikan perubahan invariant default ke Action transaksional. `ResolvePublicLocale` membaca segment pertama, memilih bahasa aktif, dan mengembalikan locale serta path normal tanpa memodifikasi internal Symfony Request. `PublicLocaleLinks` membangun URL server-side berdasarkan resource yang sedang ditampilkan sehingga slug terjemahan dapat berbeda. Route publik memakai catch-all terbatas setelah route sistem.

**Tech Stack:** Laravel 13.20, Eloquent, Inertia Laravel/React 3, React 19, Wayfinder 0.1, Pest 4.

## Context7 Decisions

- `/laravel/docs/__branch__13.x`: middleware menetapkan locale sebelum binding/dispatch, transaksi melindungi perubahan invariant, dan route model binding tetap digunakan pada CRUD.
- `/inertiajs/docs`: data locale dan URL dibagikan dari server sebagai props; komponen React memakai `<Link>` pada URL yang sudah selesai dibangun server.
- `/laravel/wayfinder`: seluruh aksi form dan navigasi admin memakai generated typed route functions dan `.url()`/form variants.

---

### Task 1: Kunci invariant Language dan CRUD Admin

**Files:**
- Create: `app/Actions/Languages/SaveLanguage.php`
- Create: `app/Http/Controllers/Admin/LanguageController.php`
- Create: `app/Http/Requests/Admin/LanguageRequest.php`
- Modify: `app/Models/Language.php`
- Modify: `routes/admin.php`
- Create: `tests/Feature/Admin/LanguageCrudTest.php`

- [x] **Step 1: Tulis tes RED invariant dan authorization**

Uji hanya Admin dapat CRUD; kode wajib tepat dua huruf lowercase dan unik; default wajib aktif; perubahan default meninggalkan tepat satu default; default tidak dapat dinonaktifkan/dihapus; bahasa terakhir tidak dapat dihapus.

- [x] **Step 2: Tulis tes RED immutability kode**

Uji kode masih dapat diubah sebelum bahasa direferensikan, tetapi ditolak setelah memiliki record pada salah satu tabel translation.

- [x] **Step 3: Implementasikan request, Action, dan controller tipis**

`SaveLanguage` memakai transaksi dan `lockForUpdate()`, menonaktifkan flag default lain saat default berpindah, lalu membersihkan cache Language dan public layout setelah commit berhasil.

- [x] **Step 4: Jalankan tes GREEN**

```bash
APP_KEY='base64:MDEyMzQ1Njc4OWFiY2RlZjAxMjM0NTY3ODlhYmNkZWY=' php artisan test --compact tests/Feature/Admin/LanguageCrudTest.php
```

### Task 2: Ganti locale hardcode dengan resolver publik tunggal

**Files:**
- Create: `app/Support/ResolvePublicLocale.php`
- Modify: `app/Http/Middleware/SetLocale.php`
- Modify: `routes/web.php`
- Modify: `tests/Feature/LocaleMiddlewareTest.php`
- Create: `tests/Feature/Public/DynamicLocaleRoutingTest.php`

- [x] **Step 1: Tulis tes RED routing dinamis**

Uji ID default tanpa prefix, EN/FR aktif dengan prefix, perubahan default, bahasa inactive, prefix default canonical, query string canonical, dan route sistem tidak tertangkap catch-all.

- [x] **Step 2: Implementasikan hasil resolusi tanpa mutasi Request internal**

Resolver mengembalikan `locale`, `normalizedPath`, dan target canonical opsional. Middleware menetapkan locale dan request attribute. Dispatcher membaca attribute tersebut sebelum memanggil `PublicPathResolver`.

- [x] **Step 3: Jalankan tes GREEN**

Jalankan `LocaleMiddlewareTest` dan `DynamicLocaleRoutingTest`.

### Task 3: Bangun localeLinks server-side berdasarkan resource

**Files:**
- Create: `app/Support/PublicLocaleLinks.php`
- Modify: `app/Support/PublicLayoutProps.php`
- Modify: `app/Http/Controllers/Public/HomeController.php`
- Modify: `app/Http/Controllers/Public/PostController.php`
- Modify: `app/Http/Controllers/Public/PageController.php`
- Modify: `tests/Feature/PublicLayoutRegionTest.php`
- Modify: `tests/Feature/Public/PublicSeoCoverageTest.php`

- [x] **Step 1: Tulis tes RED kontrak localeLinks**

Uji shape `{code,name,url,isCurrent,isAvailable}`, URL server-side, slug Page/Post yang berbeda antarbahasa, Draft/missing translation menjadi unavailable, dan bahasa inactive tidak dikirim.

- [x] **Step 2: Implementasikan builder per konteks**

Builder menerima konteks Home, Archive, Page, atau Post. URL hanya dibuat bila target publik tersedia; `isAvailable=false` memakai `url=null`. Canonical dan `hreflang` menggunakan sumber URL yang sama.

- [x] **Step 3: Jalankan tes GREEN**

Jalankan test layout, SEO publik, resolver, Post, dan Page.

### Task 4: Bangun UI Languages dan switcher bertipe

**Files:**
- Create: `resources/js/pages/admin/languages/index.tsx`
- Modify: `resources/js/components/locale-switcher.tsx`
- Modify: `resources/js/layouts/public-layout.tsx`
- Modify: `resources/js/components/admin/sidebar-nav-config.ts`
- Generated: `resources/js/routes/**`
- Generated: `resources/js/actions/**`

- [x] **Step 1: Generate Wayfinder setelah route CRUD tersedia**

```bash
php artisan wayfinder:generate --with-form --no-interaction
```

- [x] **Step 2: Implementasikan CRUD inline**

Form create/edit memakai route typed, menampilkan validasi, status Active/Default, dan dialog delete yang menjelaskan batasan default/referensi.

- [x] **Step 3: Ubah LocaleSwitcher**

Komponen hanya memakai `localeLinks` server-side. Link unavailable dirender disabled dan tidak menebak prefix/slug dari URL browser.

- [x] **Step 4: Jalankan frontend checks**

```bash
npm run types:check
npm run lint:check
npm run format:check
```

### Task 5: Selaraskan sitemap, cache, dan regression coverage

**Files:**
- Modify: `app/Console/Commands/GenerateSitemap.php`
- Modify: `app/Support/Seo/SeoProps.php`
- Modify: `tests/Feature/SitemapTest.php`
- Modify: `tests/Feature/AdminPlaceholderRoutesTest.php`
- Modify: `tests/Feature/WalkingSkeletonSsrTest.php`

- [x] **Step 1: Tulis tes RED sitemap dan cache**

Uji sitemap memakai semua bahasa aktif/default database, mengabaikan translation Draft/inactive, memakai slug localized, dan perubahan bahasa segera tercermin setelah cache invalidation.

- [x] **Step 2: Implementasikan penggunaan sumber locale tunggal**

Hapus asumsi `id`/`en` dari sitemap, SEO, layout, dan test placeholder Languages.

- [x] **Step 3: Jalankan tes GREEN**

Jalankan seluruh test locale, sitemap, SEO, layout, menu, Post, dan Page.

### Task 6: Quality gate dan review checkpoint

- [x] Jalankan `vendor/bin/pint --dirty --format agent`.
- [x] Jalankan PHPStan penuh.
- [x] Jalankan Pest untuk seluruh area terdampak.
- [x] Jalankan TypeScript, ESLint, Prettier, Wayfinder check, Vite build, dan SSR build.
- [x] Jalankan review kepatuhan spec lalu review kualitas kode; Critical/Important wajib ditutup.
- [x] Commit dan push hasil Fase 3 sebelum memulai Fase 4.
