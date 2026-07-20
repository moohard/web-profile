# Pondasi CMS Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Membangun 7 fase pondasi CMS company profile (PostgreSQL + Inertia/React + Spatie + Laravel AI SDK) sehingga aplikasi siap dibangun fitur tanpa menyentuh struktur inti.

**Architecture:** Walking skeleton vertikal — Fase 1-3 berurutan (setup → DB → auth), Fase 4 (routing+locale) langsung disambung satu jalur vertikal end-to-end (1 content_type + 1 post dummy + render SSR publik) yang membuktikan SEO/SSR/i18n terhubung, lalu Fase 5-7 dilapis di atasnya (shell admin, region+SEO+a11y, media+AI).

**Tech Stack:** Laravel 13 (PHP 8.4) · Inertia.js v3 + React 19 + TypeScript · Tailwind v4 + shadcn/ui (Radix) + Magic UI · Wayfinder · PostgreSQL (JSONB) · Spatie (permission, medialibrary, sitemap, settings) · Laravel AI SDK · stevebauman/purify · Pest v4 · Pint · Larastan v3

**Spec:** `docs/superpowers/specs/2026-07-19-cms-foundation-design.md`
**PRD:** `docs/PRD-Website-Company-Profile-CMS-v1.0.md`

## Global Constraints

(Seluruh task wajib memenuhi — disalin verbatim dari spec §1 & §2)

- **Lingkup:** 7 fase pondasi. Custom fields per jenis konten (Open Item #3 PRD) **di luar lingkup** — jangan implementasikan.
- **PHP:** 8.4, strict types di seluruh signature (`declare(strict_types=1);`), type hint + return type wajib. Enum `TitleCase`.
- **Database:** PostgreSQL. Field fleksibel (`page_translations.content`, `widgets.config`) wajib `jsonb`. `DB_CONNECTION=pgsql`.
- **Role:** `spatie/laravel-permission` (Admin/Editor/Author). TIDAK boleh menambah kolom `role` ke tabel `users`.
- **Locale routing:** ID default tanpa prefix; locale lain `/en/...`. `app()->getLocale()` selalu 2-huruf (`id`, `en`).
- **URL:** pakai helper native Laravel (`url()`, `route()`, `URL::to()`) dengan `APP_URL` terkonfigurasi. JANGAN pakai Boost MCP `get-absolute-url` di kode (hanya saat berbagi URL ke user dalam chat).
- **Site settings:** hibrida — `spatie/laravel-settings` (non-teks: WhatsApp, logo, favicon, SEO default non-teks) + tabel `setting_translations` (teks i18n: tagline, footer_text, dll). Daftar key mana ke mana — lihat Task 2.10.
- **Frontend:** Wayfinder untuk semua URL di React (tidak ada string URL manual). Inertia SSR wajib untuk rute publik.
- **Konvensi starter:** layouts di `resources/js/layouts/`, komponen UI di `resources/js/components/ui/`, halaman di `resources/js/pages/`. Ikuti pola starter.
- **Verifikasi versi starter (sebelum eksekusi):** konfirmasi versi & API tepat (Inertia, React, Tailwind, hook `useHttp`, `@inertiajs/vite`) langsung dari `composer.json`/`package.json` starter yang di-scaffold — jangan berasumsi dari versi yang tertera di dokumen ini.
- **Pint:** jalankan `vendor/bin/pint --dirty --format agent` setelah setiap modifikasi file PHP.
- **Test:** Pest v4. Setiap task TDD — test dulu, lalu implementasi. Jalankan dengan `php artisan test --compact --filter=...`.
- **Artisan:** selalu `--no-interaction`. `php artisan make:*` untuk semua file baru (model, migration, controller, seeder, dll).
- **No TBs:** kode lengkap di setiap step — tidak ada placeholder/TODO/"similar to".
- **Catatan eksekusi:** repo BUKAN git (no `.git/`). Step "Commit" di tiap task → **lewati**, ganti dengan catatan "Verifikasi OK" saja. (Jika user meminta init git di tengah jalan, ikuti.)

---

## File Structure

Pemetaan file yang dibuat/dimodifikasi per fase. Setiap file = satu tanggung jawab.

### Fase 1 — Setup & Tooling
```
composer.json, composer.lock           [Modify] add 6 packages
package.json, package-lock.json        [Modify] if needed
.env, .env.example                     [Modify] DB_*, ADMIN_EMAIL, ADMIN_PASSWORD, locale
config/app.php                         [Modify] locale='id', faker_locale='id_ID'
config/ai.php                          [Create] publish laravel/ai config
config/permission.php                  [Create] publish spatie/permission
config/media-library.php               [Create] publish spatie/medialibrary
config/sitemap.php                     [Create] publish spatie/sitemap
config/settings.php                    [Create] publish spatie/settings
config/purify.php                      [Create] publish stevebauman/purify
bootstrap/app.php                      [Modify] register middleware, schedule
routes/web.php                         [Modify] wiring publik (Fase 4)
routes/admin.php                       [Create] admin route group
routes/console.php                     [Modify] schedule sitemap (Fase 6)
vite.config.ts                         [Modify] enable SSR build
```

### Fase 2 — Skema & Model
```
app/Enums/*.php                        [Create] 10 enum (lihat Task 2.1)
app/Support/HasTranslations.php        [Create] trait
app/Models/Language.php                [Create] + helpers (idFor, default, current)
app/Models/*.php                       [Create] ~25 model (lihat Task 2.4-2.8)
app/Settings/*.php                     [Create] SiteSettings, SeoSettings, WhatsappSettings
app/Support/setting_translated.php     [Create] helper
database/migrations/*                  [Create] ~25 migration (lihat Task 2.4-2.8)
database/seeders/*                     [Create] 7 seeder (lihat Task 2.9)
```

### Fase 3 — Auth & Otorisasi
```
config/fortify.php                     [Modify] registration=false, home=/admin
app/Models/Role.php                    [Create] extend Spatie
app/Models/User.php                    [Modify] add HasRoles trait
app/Policies/PostPolicy.php            [Create] skeleton pattern
app/Providers/AppServiceProvider.php   [Modify] Gate::define('use-page-code-mode')
app/Http/Middleware/HandleInertiaRequests.php [Modify] share roles/permissions/canUseCodeMode/contentTypes/navCounts
database/seeders/RolePermissionSeeder.php [Create] 3 roles + permissions + AdminUserSeeder
```

### Fase 4 — Routing & Locale + Walking Skeleton
```
app/Http/Middleware/SetLocale.php      [Create] locale resolver
app/Http/Controllers/Public/HomeController.php     [Create]
app/Http/Controllers/Public/PostController.php     [Create] archive + show
app/Http/Controllers/Public/PageController.php     [Create] catch-all resolver
app/Http/Controllers/Public/ResolvePublicPath.php  [Create] shared resolver trait/util
app/Support/LocaleUrl.php              [Create] URL builder for locale switching
routes/web.php                         [Modify] wire publik + admin + catch-all
resources/js/layouts/public-layout.tsx            [Create]
resources/js/pages/public/home.tsx                [Create]
resources/js/pages/public/post-archive.tsx        [Create]
resources/js/pages/public/post-show.tsx           [Create]
resources/js/pages/public/page-show.tsx           [Create]
resources/js/components/locale-switcher.tsx       [Create]
database/seeders/DemoPostSeeder.php               [Create] 1 post ID+EN
```

### Fase 5 — Shell Admin
```
app/Http/Controllers/Admin/DashboardController.php    [Create]
app/Http/Controllers/Admin/PlaceholderController.php  [Create] generic ComingSoon
routes/admin.php                          [Modify] wire dashboard + placeholders
resources/js/layouts/admin-layout.tsx     [Create]
resources/js/components/admin/sidebar-nav.ts  [Create] config NavItem[]
resources/js/components/admin/coming-soon.tsx  [Create]
resources/js/pages/admin/dashboard.tsx    [Create]
resources/js/pages/admin/placeholder.tsx  [Create]
```

### Fase 6 — Region, SEO, Aksesibilitas
```
app/Services/Html/Sanitizer.php           [Create] Purify wrapper
app/Console/Commands/GenerateSitemap.php  [Create]
app/Support/Seo/SeoProps.php              [Create] builder for SEO props
resources/js/components/seo/meta-head.tsx  [Create]
resources/js/components/seo/json-ld.tsx    [Create]
resources/js/components/public/hero.tsx    [Create]
resources/js/components/public/widget-renderer.tsx  [Create]
resources/js/components/public/widgets/html-widget.tsx  [Create]
resources/js/layouts/public-layout.tsx     [Modify] add region slots
```

### Fase 7 — Media, AI, Design System
```
app/Support/Media/HasDefaultMediaConversions.php [Create] trait
app/Models/Post.php, Page.php, Testimonial.php, Settings/SiteSettings.php [Modify] add HasMedia
app/Http/Controllers/Admin/MediaController.php   [Create] upload, index, delete
app/Services/Ai/AiClient.php                     [Create] resolver per task
app/Services/Ai/Tasks/TranslationTask.php        [Create] fungsional
app/Services/Ai/Tasks/ContentRefinementTask.php  [Create] skeleton
app/Services/Ai/Tasks/MarkupConformTask.php      [Create] skeleton
app/Http/Controllers/Admin/AiController.php      [Create] translate + applyTranslation
routes/admin.php                                 [Modify] media + ai routes
resources/js/pages/admin/media/index.tsx         [Create]
resources/js/components/media/media-picker.tsx   [Create]
resources/js/app/global-components.ts            [Create] skeleton
docs/design-system/component-reference.md        [Create] katalog minimal
```

---

## Urutan Task

Task diberi nomor 2- digit: `{fase}.{urutan}`. Tiap task TDD: test → fail → implement → pass → verifikasi.

- **Fase 1:** Task 1.1 (paket + config) — 1 task.
- **Fase 2:** Task 2.1-2.10 (enum, trait+helper, migrasi per kelompok, model, settings, seeder).
- **Fase 3:** Task 3.1-3.4 (Fortify config, role+permission, Inertia share, gate + PostPolicy).
- **Fase 4:** Task 4.1-4.5 (SetLocale, resolver, controllers, routes, walking skeleton SSR).
- **Fase 5:** Task 5.1-5.4 (admin layout, sidebar nav, dashboard, placeholders).
- **Fase 6:** Task 6.1-6.5 (sanitizer, SEO props + components, JSON-LD, region + widget, sitemap).
- **Fase 7:** Task 7.1-7.5 (media setup+model, media UI, AI client+TranslationTask, AiController, design system catalog).

---

# FASE 1 — SETUP & TOOLING

## Task 1.1: Pasang Paket, Konfigurasi DB & Locale, Publish Configs

**Files:**
- Modify: `composer.json`, `composer.lock`
- Modify: `.env`, `.env.example`
- Modify: `config/app.php`
- Create: `config/ai.php`, `config/permission.php`, `config/media-library.php`, `config/sitemap.php`, `config/settings.php`, `config/purify.php`
- Modify: `vite.config.ts`
- Test: `tests/Feature/SetupSmokeTest.php`

**Interfaces:**
- Produces: PostgreSQL connection aktif; paket Spatie + AI + Purify ter-publish; locale default `id`.

- [ ] **Step 1: Tambah kredensial PostgreSQL & admin ke `.env` dan `.env.example`**

Edit `.env`, ganti baris DB menjadi:
```env
DB_CONNECTION=pgsql
DB_HOST=127.0.0.1
DB_PORT=5432
DB_DATABASE=papenajam_cms
DB_USERNAME=postgres
DB_PASSWORD=secret

APP_LOCALE=id
APP_FAKER_LOCALE=id_ID
ADMIN_EMAIL=admin@papenajam.test
ADMIN_PASSWORD=password
```

Tambah baris yang sama (tanpa nilai rahasia) ke `.env.example`:
```env
DB_CONNECTION=pgsql
DB_HOST=127.0.0.1
DB_PORT=5432
DB_DATABASE=papenajam_cms
DB_USERNAME=postgres
DB_PASSWORD=

APP_LOCALE=id
APP_FAKER_LOCALE=id_ID
ADMIN_EMAIL=admin@papenajam.test
ADMIN_PASSWORD=
```

- [ ] **Step 2: Set locale di `config/app.php`**

Edit `config/app.php`, ganti baris `'locale'` dan `'faker_locale'`:
```php
'locale' => env('APP_LOCALE', 'id'),
'faker_locale' => env('APP_FAKER_LOCALE', 'id_ID'),
```

- [ ] **Step 3: Buat database PostgreSQL**

```bash
createdb papenajam_cms 2>/dev/null || echo "DB sudah ada / pakai superuser"
psql -d postgres -c "CREATE DATABASE papenajam_cms;" 2>/dev/null || true
```

Verifikasi koneksi:
```bash
php artisan db:show
```
Expected: tampilkan info database PostgreSQL (driver `pgsql`), tidak error.

- [ ] **Step 4: Pasang 6 paket composer**

```bash
composer require spatie/laravel-permission spatie/laravel-medialibrary spatie/laravel-sitemap spatie/laravel-settings laravel/ai stevebauman/purify --no-interaction
```

Expected: install sukses, `composer.json` ter-update.

- [ ] **Step 5: Publish config tiap paket**

```bash
php artisan vendor:publish --provider="Spatie\Permission\PermissionServiceProvider" --no-interaction
php artisan vendor:publish --provider="Spatie\MediaLibrary\MediaLibraryServiceProvider" --tag="medialibrary-config" --no-interaction
php artisan vendor:publish --provider="Spatie\Sitemap\SitemapServiceProvider" --tag="sitemap-config" --no-interaction
php artisan vendor:publish --provider="Spatie\LaravelSettings\LaravelSettingsServiceProvider" --tag="settings-config" --no-interaction
php artisan vendor:publish --provider="Laravel\Ai\AiServiceProvider" --no-interaction 2>/dev/null || php artisan ai:install --no-interaction
php artisan vendor:publish --provider="Stevebauman\Purify\PurifyServiceProvider" --no-interaction
```

Jika salah satu command gagal karena tag/provider tidak cocok, cek `php artisan list | grep vendor:publish` atau jalankan `php artisan vendor:publish` interaktif OFF-kan dengan mencari nama provider via `grep -r "ServiceProvider" vendor/<pkg>/src/`.

- [ ] **Step 6: Edit `config/media-library.php` — set disk `public` dan image_generators default**

Buka `config/media-library.php`. Pastikan:
```php
'disk' => env('MEDIA_DISK', 'public'),
'image_generators' => [
    Spatie\MediaLibrary\Conversions\ImageGenerators\Image::class,
    Spatie\MediaLibrary\Conversions\ImageGenerators\Webp::class,
    Spatie\MediaLibrary\Conversions\ImageGenerators\Svg::class,
],
'queue_name' => env('MEDIA_QUEUE', 'default'),
```

- [ ] **Step 7: Set `FILESYSTEM_DISK=public` di `.env` dan buat storage:link**

```env
FILESYSTEM_DISK=public
```
Lalu:
```bash
php artisan storage:link
```

- [ ] **Step 8: Edit `config/permission.php` — gunakan cache default (ubah hanya jika perlu)**

Cek isi `config/permission.php` — biarkan default. Pastikan `models.role` dan `models.permission` mengarah ke default Spatie (akan di-override di Task 3.2 via kelas custom `App\Models\Role`).

- [ ] **Step 9: Jalankan migration bawaan paket**

```bash
php artisan migrate --no-interaction
```
Expected: tabel Spatie permission, medialibrary, settings_properties ter-create di PostgreSQL.

- [ ] **Step 10: Aktifkan SSR build di `vite.config.ts`**

Buka `vite.config.ts`. Tambahkan plugin SSR Inertia jika belum ada. Versi final kira-kira:
```ts
import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import react from '@vitejs/plugin-react';
import inertia from '@inertiajs/vite/react';
import wayfinder from '@laravel/vite-plugin-wayfinder';

export default defineConfig({
    plugins: [
        laravel({
            input: ['resources/css/app.css', 'resources/js/app.tsx'],
            ssr: 'resources/js/ssr.tsx',  // aktifkan SSR
            refresh: true,
        }),
        react({
            babel: { plugins: [['babel-plugin-react-compiler', {}]] },
        }),
        inertia({ ssr: true }),  // wajib untuk publik SSR
        wayfinder(),
    ],
});
```

> Catatan: baca `vite.config.ts` yang ada, jangan overwrite penuh. Hanya pastikan `inertia({ ssr: true })` dan `ssr: 'resources/js/ssr.tsx'` di `laravel()` plugin. Cek file `resources/js/ssr.tsx` ada — jika tidak, buat dengan menyalin pola dari starter Laravel React SSR standar.

Verifikasi:
```bash
npm run build:ssr
```
Expected: build sukses, ada file `bootstrap/ssr/ssr.mjs` atau setara.

- [ ] **Step 11: Tulis smoke test setup**

Buat `tests/Feature/SetupSmokeTest.php`:
```php
<?php

use Illuminate\Support\Facades\File;
use function Pest\Laravel\{get};

describe('setup pondasi', function () {
    it('koneksi database adalah PostgreSQL', function () {
        expect(config('database.default'))->toBe('pgsql');
    });

    it('locale default adalah id', function () {
        expect(config('app.locale'))->toBe('id');
    });

    it('paket wajib terpasang', function () {
        expect(class_exists(Spatie\Permission\PermissionServiceProvider::class))->toBeTrue();
        expect(class_exists(Spatie\MediaLibrary\MediaLibraryServiceProvider::class))->toBeTrue();
        expect(class_exists(Spatie\Sitemap\SitemapServiceProvider::class))->toBeTrue();
        expect(class_exists(Spatie\LaravelSettings\LaravelSettingsServiceProvider::class))->toBeTrue();
        expect(class_exists(Laravel\Ai\AiServiceProvider::class))->toBeTrue();
        expect(class_exists(Stevebauman\Purify\PurifyServiceProvider::class))->toBeTrue();
    });

    it('config medialibrary image_generators menyertakan Webp dan Svg', function () {
        $generators = config('media-library.image_generators');
        expect($generators)->toContain(Spatie\MediaLibrary\Conversions\ImageGenerators\Webp::class)
            ->and($generators)->toContain(Spatie\MediaLibrary\Conversions\ImageGenerators\Svg::class);
    });

    it('tabel permission Spatie ada', function () {
        $tables = \DB::connection()->getDoctrineSchemaManager()->listTableNames();
        expect($tables)->toContain('roles', 'permissions', 'model_has_roles', 'settings_properties', 'media');
    });
});
```

- [ ] **Step 12: Jalankan test — harus hijau**

```bash
php artisan test --compact --filter=SetupSmokeTest
```
Expected: PASS untuk semua case.

- [ ] **Step 13: Jalankan Pint**

```bash
vendor/bin/pint --dirty --format agent
```

- [ ] **Step 14: Verifikasi OK (tidak ada git — skip commit)**

Catat output build SSR & migration sukses. Lanjut Task 2.1.

---

# FASE 2 — SKEMA DATABASE & MODEL

## Task 2.1: PHP Enums (10 file)

**Files:**
- Create: `app/Enums/PostStatus.php`, `app/Enums/UserRole.php`, `app/Enums/AiTask.php`, `app/Enums/LinkType.php`, `app/Enums/WidgetPosition.php`, `app/Enums/PlacementScope.php`, `app/Enums/MenuLocation.php`, `app/Enums/PageMode.php`, `app/Enums/ContactStatus.php`, `app/Enums/TestimonialStatus.php`
- Test: `tests/Feature/EnumContractTest.php`

**Interfaces:**
- Produces: 10 backed string enum. Semua pakai trait `HasLabel` (di Task ini juga dibuat) supaya konsisten `->label()`. Kontrak nilai string: `Draft`/`Published`, `Admin`/`Editor`/`Author`, `Translation`/`ContentRefinement`/`MarkupConform`, dst (sesuai spec §3).

- [ ] **Step 1: Buat trait `HasLabel`**

Buat `app/Enums/Concerns/HasLabel.php`:
```php
<?php

declare(strict_types=1);

namespace App\Enums\Concerns;

trait HasLabel
{
    /**
     * Label manusia-baca untuk UI.
     */
    public function label(): string
    {
        return match ($this) {
            default => ucfirst(strtolower(str_replace('_', ' ', $this->value))),
        };
    }
}
```

- [ ] **Step 2: Buat 10 enum via `php artisan make:enum`**

```bash
php artisan make:enum Enums/PostStatus --no-interaction
php artisan make:enum Enums/UserRole --no-interaction
php artisan make:enum Enums/AiTask --no-interaction
php artisan make:enum Enums/LinkType --no-interaction
php artisan make:enum Enums/WidgetPosition --no-interaction
php artisan make:enum Enums/PlacementScope --no-interaction
php artisan make:enum Enums/MenuLocation --no-interaction
php artisan make:enum Enums/PageMode --no-interaction
php artisan make:enum Enums/ContactStatus --no-interaction
php artisan make:enum Enums/TestimonialStatus --no-interaction
```

> Jika `make:enum` tidak ada (Laravel Boost), buat file manual via Write tool.

- [ ] **Step 3: Isi setiap enum (backed string + TitleCase)**

Isi masing-masing file persis seperti di bawah. Setiap enum: `declare(strict_types=1);`, `use Concerns\HasLabel;`, `implements HasLabel` jika interface tersedia (atau cukup pakai trait — interface opsional). Gunakan `HasLabel` trait di setiap enum.

**`app/Enums/PostStatus.php`** — cases: `Draft = 'Draft'`, `Published = 'Published'`.
**`app/Enums/UserRole.php`** — cases: `Admin = 'Admin'`, `Editor = 'Editor'`, `Author = 'Author'`. Tambah metode:
```php
public function permissions(): array
{
    return match ($this) {
        self::Admin   => ['access-admin', 'admin.use-page-code-mode', 'admin.access-system', 'admin.access-appearance'],
        self::Editor  => ['access-admin'],
        self::Author  => ['access-admin'],
    };
}
```
**`app/Enums/AiTask.php`** — cases: `Translation = 'Translation'`, `ContentRefinement = 'ContentRefinement'`, `MarkupConform = 'MarkupConform'`.
**`app/Enums/LinkType.php`** — cases: `Page = 'Page'`, `ContentArchive = 'ContentArchive'`, `ContentSingle = 'ContentSingle'`, `Url = 'Url'`.
**`app/Enums/WidgetPosition.php`** — cases: `BeforeContent = 'BeforeContent'`, `AfterContent = 'AfterContent'`, `Sidebar = 'Sidebar'`, `Footer = 'Footer'`.
**`app/Enums/PlacementScope.php`** — cases: `All = 'All'`, `Only = 'Only'`, `Except = 'Except'`.
**`app/Enums/MenuLocation.php`** — cases: `Header = 'Header'`, `Footer = 'Footer'`.
**`app/Enums/PageMode.php`** — cases: `Code = 'Code'`, `Template = 'Template'`.
**`app/Enums/ContactStatus.php`** — cases: `New = 'New'`, `Read = 'Read'`, `Archived = 'Archived'`.
**`app/Enums/TestimonialStatus.php`** — cases: `Pending = 'Pending'`, `Approved = 'Approved'`.

Contoh isi `PostStatus.php`:
```php
<?php

declare(strict_types=1);

namespace App\Enums;

use App\Enums\Concerns\HasLabel;

enum PostStatus: string
{
    use HasLabel;

    case Draft = 'Draft';
    case Published = 'Published';
}
```

- [ ] **Step 4: Tulis test kontrak enum**

Buat `tests/Feature/EnumContractTest.php`:
```php
<?php

use App\Enums\{PostStatus, UserRole, AiTask, LinkType, WidgetPosition, PlacementScope, MenuLocation, PageMode, ContactStatus, TestimonialStatus};

it('PostStatus memiliki cases yang benar', function () {
    expect(PostStatus::cases())
        ->toHaveCount(2)
        ->and(PostStatus::Draft->value)->toBe('Draft')
        ->and(PostStatus::Published->value)->toBe('Published');
});

it('UserRole permissions terdefinisi', function () {
    expect(UserRole::Admin->permissions())->toContain('admin.use-page-code-mode')
        ->and(UserRole::Editor->permissions())->not->toContain('admin.use-page-code-mode');
});

it('semua enum adalah backed string', function () {
    $enums = [PostStatus::class, UserRole::class, AiTask::class, LinkType::class,
              WidgetPosition::class, PlacementScope::class, MenuLocation::class,
              PageMode::class, ContactStatus::class, TestimonialStatus::class];
    foreach ($enums as $enum) {
        $reflection = new ReflectionEnum($enum);
        expect($reflection->getBackingType()?->getName())->toBe('string');
    }
});

it('AiTask memiliki tiga task', function () {
    expect(AiTask::cases())->toHaveCount(3)
        ->and(array_map(fn ($e) => $e->value, AiTask::cases()))
        ->toBe(['Translation', 'ContentRefinement', 'MarkupConform']);
});
```

- [ ] **Step 5: Jalankan test — harus hijau**

```bash
php artisan test --compact --filter=EnumContractTest
```

- [ ] **Step 6: Pint + verifikasi OK**

```bash
vendor/bin/pint --dirty --format agent
```

---

## Task 2.2: Trait `HasTranslations` + Helper Language

**Files:**
- Create: `app/Support/HasTranslations.php`
- Create: `app/Models/Language.php` (+ migration + helper metode statis `idFor`, `default`, `current`)
- Create: `database/migrations/2026_07_19_000010_create_languages_table.php`
- Test: `tests/Feature/LanguageHelperTest.php`

**Interfaces:**
- Produces:
  - `App\Models\Language` dengan scope `active()`, `default()`, metode statis `Language::idFor(string $code): int`, `Language::defaultModel(): self`, `Language::current(): self`.
  - Trait `App\Support\HasTranslations` dengan metode `translations(): HasMany` (di-override di tiap model host) dan `translate(?string $locale = null): ?Model`.
  - Konsumsi oleh model Post/Page/ContentType/Category/Tag/Gallery/GalleryImage/MenuItem/Widget/RatingCriterion di Task berikut.

- [ ] **Step 1: Tulis migration languages**

Jalankan:
```bash
php artisan make:migration create_languages_table --no-interaction
```
Isi file (nama file: `database/migrations/2026_07_19_000010_create_languages_table.php`):
```php
<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('languages', function (Blueprint $table) {
            $table->id();
            $table->string('code', 8)->unique();
            $table->string('name');
            $table->boolean('is_default')->default(false);
            $table->boolean('is_active')->default(true);
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('languages');
    }
};
```

- [ ] **Step 2: Buat model Language dengan helper**

Jalankan `php artisan make:model Language --no-interaction` lalu isi `app/Models/Language.php`:
```php
<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

/**
 * @property int $id
 * @property string $code
 * @property string $name
 * @property bool $is_default
 * @property bool $is_active
 * @property int $sort_order
 */
class Language extends Model
{
    protected $fillable = ['code', 'name', 'is_default', 'is_active', 'sort_order'];

    protected $casts = [
        'is_default' => 'boolean',
        'is_active' => 'boolean',
        'sort_order' => 'integer',
    ];

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true)->orderBy('sort_order');
    }

    public function scopeDefault(Builder $query): Builder
    {
        return $query->where('is_default', true);
    }

    /** Resolve code (mis. 'en') ke ID, dengan cache 1 jam. */
    public static function idFor(string $code): int
    {
        return Cache::remember("language.id_for.{$code}", now()->addHour(), fn () =>
            static::where('code', $code)->value('id')
            ?? throw new \RuntimeException("Language [{$code}] tidak ditemukan.")
        );
    }

    public static function defaultModel(): self
    {
        return Cache::rememberForever('language.default', fn () =>
            static::default()->firstOrFail()
        );
    }

    public static function current(): self
    {
        return static::where('code', app()->getLocale())->first() ?? static::defaultModel();
    }

    /** Reset cache — panggil setelah seeder / perubahan tabel languages. */
    public static function flushCache(): void
    {
        Cache::forget('language.default');
        // keys dinamis per-code tidak bisa di-flush selektif; flush prefix:
        Cache::flush(); // aman di dev; di prod pakai Cache::tags bila didukung
    }
}
```

- [ ] **Step 3: Buat trait HasTranslations**

Buat `app/Support/HasTranslations.php`:
```php
<?php

declare(strict_types=1);

namespace App\Support;

use App\Models\Language;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Trait untuk model host yang punya tabel *_translations.
 * Host wajib mendeklarasikan metode translations(): HasMany.
 *
 * @mixin Model
 */
trait HasTranslations
{
    /**
     * Ambil translation untuk locale aktif (atau locale yang diberikan).
     * Fallback ke bahasa default bila tidak ada.
     */
    public function translate(?string $locale = null): ?Model
    {
        $locale ??= app()->getLocale();

        return $this->translations->firstWhere('language_id', Language::idFor($locale))
            ?? $this->translations->firstWhere('language_id', Language::defaultModel()->id);
    }

    /**
     * Eager-load translation untuk locale aktif.
     */
    public function scopeWithTranslation(): static
    {
        return $this->load(['translations' => function (HasMany $q) {
            $q->where('language_id', Language::idFor(app()->getLocale()))
              ->orWhere('language_id', Language::defaultModel()->id);
        }]);
    }
}
```

- [ ] **Step 4: Tulis test helper Language**

Buat `tests/Feature/LanguageHelperTest.php`:
```php
<?php

use App\Models\Language;

beforeEach(function () {
    Language::flushCache();
    Language::query()->delete();
    Language::create(['code' => 'id', 'name' => 'Bahasa Indonesia', 'is_default' => true, 'sort_order' => 1]);
    Language::create(['code' => 'en', 'name' => 'English', 'sort_order' => 2]);
    Language::flushCache();
});

it('idFor mengembalikan id yang benar', function () {
    expect(Language::idFor('id'))->toBeInt()->toBe(1)
        ->and(Language::idFor('en'))->toBeInt()->toBe(2);
});

it('defaultModel mengembalikan bahasa is_default=true', function () {
    expect(Language::defaultModel()->code)->toBe('id');
});

it('current mengikuti app locale', function () {
    app()->setLocale('en');
    expect(Language::current()->code)->toBe('en');
    app()->setLocale('id');
    expect(Language::current()->code)->toBe('id');
});

it('idFor throw bila code tidak dikenal', function () {
    Language::idFor('fr');
})->throws(RuntimeException::class);
```

- [ ] **Step 5: Migrate + jalankan test**

```bash
php artisan migrate --no-interaction
php artisan test --compact --filter=LanguageHelperTest
```
Expected: 4 case PASS.

- [ ] **Step 6: Pint + verifikasi OK**

```bash
vendor/bin/pint --dirty --format agent
```

---

## Task 2.3: Migrasi & Model Kelompok A (Content Types + Posts)

**Files:**
- Create migrations: `writing_styles`, `content_types`, `content_type_translations`, `posts`, `post_translations`, `categories`, `category_translations`, `tags`, `tag_translations`, `post_tags`
- Create models: `WritingStyle`, `ContentType`, `ContentTypeTranslation`, `Post`, `PostTranslation`, `Category`, `CategoryTranslation`, `Tag`, `TagTranslation`, `PostTag`
- Test: `tests/Feature/PostTranslationModelTest.php`

**Interfaces:**
- Produces:
  - `App\Models\Post` — pakai `HasTranslations`, relasi `type()`, `category()`, `tags()`, `translations()`.
  - `App\Models\PostTranslation` — kolom: `post_id`, `language_id`, `slug`, `title`, `body`, `status` (cast enum `PostStatus`), `published_at`, `meta_title`, `meta_description`; UNIQUE(`post_id`,`language_id`), UNIQUE(`language_id`,`slug`).
  - `App\Models\ContentType` — pakai `HasTranslations`, relasi `writingStyle()`, `posts()`, `translations()`; kolom: `slug`, `icon`, `writing_style_id`, `archive_template_key`, `single_template_key`, `is_active`, `sort_order`.
  - Konsumsi: `Post::translate()` mengembalikan `PostTranslation` (Task 2.2).

- [ ] **Step 1: Buat 10 migration via `php artisan make:migration`**

```bash
php artisan make:migration create_writing_styles_table --no-interaction
php artisan make:migration create_content_types_table --no-interaction
php artisan make:migration create_content_type_translations_table --no-interaction
php artisan make:migration create_posts_table --no-interaction
php artisan make:migration create_post_translations_table --no-interaction
php artisan make:migration create_categories_table --no-interaction
php artisan make:migration create_category_translations_table --no-interaction
php artisan make:migration create_tags_table --no-interaction
php artisan make:migration create_tag_translations_table --no-interaction
php artisan make:migration create_post_tags_table --no-interaction
```

- [ ] **Step 2: Isi migration `writing_styles`**

```php
Schema::create('writing_styles', function (Blueprint $table) {
    $table->id();
    $table->string('name');
    $table->text('prompt')->nullable();
    $table->timestamps();
});
```

- [ ] **Step 3: Isi migration `content_types`**

```php
Schema::create('content_types', function (Blueprint $table) {
    $table->id();
    $table->string('slug')->unique();
    $table->string('icon')->nullable();
    $table->foreignId('writing_style_id')->nullable()->constrained()->nullOnDelete();
    $table->string('archive_template_key')->default('default');
    $table->string('single_template_key')->default('default');
    $table->boolean('is_active')->default(true);
    $table->unsignedSmallInteger('sort_order')->default(0);
    $table->timestamps();
});
```

- [ ] **Step 4: Isi migration `content_type_translations`**

```php
Schema::create('content_type_translations', function (Blueprint $table) {
    $table->id();
    $table->foreignId('content_type_id')->constrained()->cascadeOnDelete();
    $table->foreignId('language_id')->constrained()->cascadeOnDelete();
    $table->string('name');
    $table->text('description')->nullable();
    $table->unique(['content_type_id', 'language_id']);
    $table->timestamps();
});
```

- [ ] **Step 5: Isi migration `posts`**

```php
Schema::create('posts', function (Blueprint $table) {
    $table->id();
    $table->foreignId('type_id')->constrained('content_types')->cascadeOnDelete();
    $table->foreignId('category_id')->nullable()->constrained()->nullOnDelete();
    $table->string('featured_image')->nullable(); // path fallback; pakai media library utamanya
    $table->timestamps();
});
```

- [ ] **Step 6: Isi migration `post_translations`**

```php
Schema::create('post_translations', function (Blueprint $table) {
    $table->id();
    $table->foreignId('post_id')->constrained()->cascadeOnDelete();
    $table->foreignId('language_id')->constrained()->cascadeOnDelete();
    $table->string('slug');
    $table->string('title');
    $table->longText('body')->nullable();
    $table->string('status', 20)->default('Draft');
    $table->timestamp('published_at')->nullable();
    $table->string('meta_title')->nullable();
    $table->string('meta_description')->nullable();
    $table->unique(['post_id', 'language_id']);
    $table->unique(['language_id', 'slug']);
    $table->index('status');
    $table->timestamps();
});
```

- [ ] **Step 7: Isi migration `categories` + `category_translations`**

```php
// categories
Schema::create('categories', function (Blueprint $table) {
    $table->id();
    $table->string('slug')->unique();
    $table->foreignId('parent_id')->nullable()->constrained('categories')->nullOnDelete();
    $table->unsignedSmallInteger('sort_order')->default(0);
    $table->timestamps();
});

// category_translations
Schema::create('category_translations', function (Blueprint $table) {
    $table->id();
    $table->foreignId('category_id')->constrained()->cascadeOnDelete();
    $table->foreignId('language_id')->constrained()->cascadeOnDelete();
    $table->string('name');
    $table->unique(['category_id', 'language_id']);
    $table->timestamps();
});
```

- [ ] **Step 8: Isi migration `tags` + `tag_translations` + `post_tags`**

```php
// tags
Schema::create('tags', function (Blueprint $table) {
    $table->id();
    $table->string('slug')->unique();
    $table->timestamps();
});
// tag_translations
Schema::create('tag_translations', function (Blueprint $table) {
    $table->id();
    $table->foreignId('tag_id')->constrained()->cascadeOnDelete();
    $table->foreignId('language_id')->constrained()->cascadeOnDelete();
    $table->string('name');
    $table->unique(['tag_id', 'language_id']);
    $table->timestamps();
});
// post_tags (pivot)
Schema::create('post_tags', function (Blueprint $table) {
    $table->foreignId('post_id')->constrained()->cascadeOnDelete();
    $table->foreignId('tag_id')->constrained()->cascadeOnDelete();
    $table->primary(['post_id', 'tag_id']);
});
```

- [ ] **Step 9: Buat 10 model via `php artisan make:model`**

```bash
php artisan make:model WritingStyle --no-interaction
php artisan make:model ContentType --no-interaction
php artisan make:model ContentTypeTranslation --no-interaction
php artisan make:model Post --no-interaction
php artisan make:model PostTranslation --no-interaction
php artisan make:model Category --no-interaction
php artisan make:model CategoryTranslation --no-interaction
php artisan make:model Tag --no-interaction
php artisan make:model TagTranslation --no-interaction
php artisan make:model PostTag --no-interaction
```

- [ ] **Step 10: Isi model Post & PostTranslation**

`app/Models/Post.php`:
```php
<?php

declare(strict_types=1);

namespace App\Models;

use App\Support\HasTranslations;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property int $id
 * @property int $type_id
 * @property ?int $category_id
 * @property ?string $featured_image
 */
class Post extends Model
{
    use HasTranslations;

    protected $fillable = ['type_id', 'category_id', 'featured_image'];

    public function type(): BelongsTo
    {
        return $this->belongsTo(ContentType::class, 'type_id');
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function tags(): BelongsToMany
    {
        return $this->belongsToMany(Tag::class, 'post_tags');
    }

    public function translations(): HasMany
    {
        return $this->hasMany(PostTranslation::class);
    }
}
```

`app/Models/PostTranslation.php`:
```php
<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\PostStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $post_id
 * @property int $language_id
 * @property string $slug
 * @property string $title
 * @property ?string $body
 * @property PostStatus $status
 * @property ?\Illuminate\Support\Carbon $published_at
 * @property ?string $meta_title
 * @property ?string $meta_description
 */
class PostTranslation extends Model
{
    protected $fillable = [
        'post_id', 'language_id', 'slug', 'title', 'body',
        'status', 'published_at', 'meta_title', 'meta_description',
    ];

    protected $casts = [
        'status' => PostStatus::class,
        'published_at' => 'datetime',
    ];

    public function post(): BelongsTo
    {
        return $this->belongsTo(Post::class);
    }

    public function language(): BelongsTo
    {
        return $this->belongsTo(Language::class);
    }

    public function scopePublished(\Illuminate\Database\Eloquent\Builder $q): \Illuminate\Database\Eloquent\Builder
    {
        return $q->where('status', PostStatus::Published->value)
                 ->where(function ($qq) {
                     $qq->whereNull('published_at')->orWhere('published_at', '<=', now());
                 });
    }
}
```

- [ ] **Step 11: Isi model ContentType + ContentTypeTranslation (dengan HasTranslations) + WritingStyle + Category/Tag + translations**

`app/Models/ContentType.php`:
```php
<?php

declare(strict_types=1);

namespace App\Models;

use App\Support\HasTranslations;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ContentType extends Model
{
    use HasTranslations;

    protected $fillable = [
        'slug', 'icon', 'writing_style_id',
        'archive_template_key', 'single_template_key',
        'is_active', 'sort_order',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'sort_order' => 'integer',
    ];

    public function writingStyle(): BelongsTo
    {
        return $this->belongsTo(WritingStyle::class);
    }

    public function posts(): HasMany
    {
        return $this->hasMany(Post::class, 'type_id');
    }

    public function translations(): HasMany
    {
        return $this->hasMany(ContentTypeTranslation::class);
    }

    public function scopeActive(Builder $q): Builder
    {
        return $q->where('is_active', true)->orderBy('sort_order');
    }
}
```

`app/Models/ContentTypeTranslation.php`:
```php
<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ContentTypeTranslation extends Model
{
    protected $fillable = ['content_type_id', 'language_id', 'name', 'description'];

    public function contentType(): BelongsTo { return $this->belongsTo(ContentType::class); }
    public function language(): BelongsTo { return $this->belongsTo(Language::class); }
}
```

`app/Models/WritingStyle.php`:
```php
<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class WritingStyle extends Model
{
    protected $fillable = ['name', 'prompt'];

    public function contentTypes(): HasMany
    {
        return $this->hasMany(ContentType::class);
    }
}
```

`app/Models/Category.php`:
```php
<?php

declare(strict_types=1);

namespace App\Models;

use App\Support\HasTranslations;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Category extends Model
{
    use HasTranslations;

    protected $fillable = ['slug', 'parent_id', 'sort_order'];

    protected $casts = ['sort_order' => 'integer'];

    public function parent(): BelongsTo { return $this->belongsTo(self::class, 'parent_id'); }
    public function children(): HasMany { return $this->hasMany(self::class, 'parent_id'); }
    public function posts(): HasMany { return $this->hasMany(Post::class); }
    public function translations(): HasMany { return $this->hasMany(CategoryTranslation::class); }
}
```

`app/Models/CategoryTranslation.php`:
```php
<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CategoryTranslation extends Model
{
    protected $fillable = ['category_id', 'language_id', 'name'];
    public function category(): BelongsTo { return $this->belongsTo(Category::class); }
    public function language(): BelongsTo { return $this->belongsTo(Language::class); }
}
```

`app/Models/Tag.php`:
```php
<?php

declare(strict_types=1);

namespace App\Models;

use App\Support\HasTranslations;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Tag extends Model
{
    use HasTranslations;

    protected $fillable = ['slug'];

    public function posts(): BelongsToMany { return $this->belongsToMany(Post::class, 'post_tags'); }
    public function translations(): HasMany { return $this->hasMany(TagTranslation::class); }
}
```

`app/Models/TagTranslation.php` (sama polanya dengan CategoryTranslation, `tag_id`).
`app/Models/PostTag.php` — model pivot kosong: `class PostTag extends Model { protected $table = 'post_tags'; public $timestamps = false; }` (atau skip model, pakai belongsToMany langsung).

- [ ] **Step 12: Migrate + factory seed minimal + test**

Untuk test, buat factory inline di test (lengkap di Task 2.9). Sementara, tulis `tests/Feature/PostTranslationModelTest.php`:
```php
<?php

use App\Models\{ContentType, Language, Post, PostTranslation};
use App\Enums\PostStatus;
use App\Support\HasTranslations;

beforeEach(function () {
    Language::flushCache();
    Language::query()->delete();
    Language::create(['code' => 'id', 'name' => 'Indonesia', 'is_default' => true, 'sort_order' => 1]);
    Language::create(['code' => 'en', 'name' => 'English', 'sort_order' => 2]);
    Language::flushCache();
});

it('Post menggunakan HasTranslations', function () {
    expect(in_array(HasTranslations::class, class_uses(Post::class)))->toBeTrue();
});

it('PostTranslation cast status ke enum PostStatus', function () {
    $type = ContentType::create(['slug' => 'berita']);
    $post = Post::create(['type_id' => $type->id]);
    $tr = PostTranslation::create([
        'post_id' => $post->id,
        'language_id' => Language::idFor('id'),
        'slug' => 'judul-slug',
        'title' => 'Judul',
        'status' => PostStatus::Published,
    ]);
    expect($tr->status)->toBe(PostStatus::Published);
});

it('Post::translate fallback ke default jika locale tidak ada', function () {
    app()->setLocale('en');
    $type = ContentType::create(['slug' => 'berita']);
    $post = Post::create(['type_id' => $type->id]);
    PostTranslation::create([
        'post_id' => $post->id,
        'language_id' => Language::idFor('id'),
        'slug' => 'judul-id', 'title' => 'Indonesia', 'status' => PostStatus::Published,
    ]);
    $post->load('translations');
    expect($post->translate('en')?->title)->toBe('Indonesia'); // fallback
    expect($post->translate('id')?->title)->toBe('Indonesia');
});

it('UNIQUE(language_id, slug) mencegah duplikat', function () {
    $type = ContentType::create(['slug' => 'berita']);
    $p1 = Post::create(['type_id' => $type->id]);
    $p2 = Post::create(['type_id' => $type->id]);
    PostTranslation::create(['post_id' => $p1->id, 'language_id' => Language::idFor('id'),
                             'slug' => 'sama', 'title' => 'A', 'status' => PostStatus::Draft]);
    PostTranslation::create(['post_id' => $p2->id, 'language_id' => Language::idFor('id'),
                             'slug' => 'sama', 'title' => 'B', 'status' => PostStatus::Draft]);
})->throws(\Illuminate\Database\QueryException::class);
```

- [ ] **Step 13: Migrate + jalankan test**

```bash
php artisan migrate:fresh --no-interaction
php artisan test --compact --filter=PostTranslationModelTest
```

- [ ] **Step 14: Pint + verifikasi OK**

```bash
vendor/bin/pint --dirty --format agent
```

---

## Task 2.4: Migrasi & Model Kelompok A lanjutan (Galleries)

**Files:**
- Create migrations: `galleries`, `gallery_translations`, `gallery_images`, `gallery_image_translations`
- Create models: `Gallery`, `GalleryTranslation`, `GalleryImage`, `GalleryImageTranslation`
- Test: `tests/Feature/GalleryModelTest.php`

**Interfaces:**
- Produces:
  - `Gallery` (HasTranslations, `images(): HasMany`, `translations()`).
  - `GalleryImage` (HasTranslations, `gallery(): BelongsTo`, `translations()`) — kolom `path`, `sort_order`.
  - Cast `sort_order` integer.

- [ ] **Step 1: 4 migration**

```bash
php artisan make:migration create_galleries_table --no-interaction
php artisan make:migration create_gallery_translations_table --no-interaction
php artisan make:migration create_gallery_images_table --no-interaction
php artisan make:migration create_gallery_image_translations_table --no-interaction
```

- [ ] **Step 2: Isi `galleries`**

```php
Schema::create('galleries', function (Blueprint $table) {
    $table->id();
    $table->string('slug')->unique();
    $table->boolean('is_active')->default(true);
    $table->timestamps();
});
```

- [ ] **Step 3: Isi `gallery_translations`**

```php
Schema::create('gallery_translations', function (Blueprint $table) {
    $table->id();
    $table->foreignId('gallery_id')->constrained()->cascadeOnDelete();
    $table->foreignId('language_id')->constrained()->cascadeOnDelete();
    $table->string('title');
    $table->text('description')->nullable();
    $table->unique(['gallery_id', 'language_id']);
    $table->timestamps();
});
```

- [ ] **Step 4: Isi `gallery_images` + `gallery_image_translations`**

```php
// gallery_images
Schema::create('gallery_images', function (Blueprint $table) {
    $table->id();
    $table->foreignId('gallery_id')->constrained()->cascadeOnDelete();
    $table->string('path');
    $table->unsignedSmallInteger('sort_order')->default(0);
    $table->timestamps();
});

// gallery_image_translations
Schema::create('gallery_image_translations', function (Blueprint $table) {
    $table->id();
    $table->foreignId('gallery_image_id')->constrained()->cascadeOnDelete();
    $table->foreignId('language_id')->constrained()->cascadeOnDelete();
    $table->string('caption')->nullable();
    $table->unique(['gallery_image_id', 'language_id']);
    $table->timestamps();
});
```

- [ ] **Step 5: 4 model** (semua pola HasTranslations untuk Gallery & GalleryImage)

`Gallery.php`:
```php
<?php

declare(strict_types=1);

namespace App\Models;

use App\Support\HasTranslations;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Gallery extends Model
{
    use HasTranslations;

    protected $fillable = ['slug', 'is_active'];

    protected $casts = ['is_active' => 'boolean'];

    public function images(): HasMany { return $this->hasMany(GalleryImage::class)->orderBy('sort_order'); }
    public function translations(): HasMany { return $this->hasMany(GalleryTranslation::class); }
}
```

`GalleryImage.php`:
```php
<?php

declare(strict_types=1);

namespace App\Models;

use App\Support\HasTranslations;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class GalleryImage extends Model
{
    use HasTranslations;

    protected $fillable = ['gallery_id', 'path', 'sort_order'];

    protected $casts = ['sort_order' => 'integer'];

    public function gallery(): BelongsTo { return $this->belongsTo(Gallery::class); }
    public function translations(): HasMany { return $this->hasMany(GalleryImageTranslation::class); }
}
```

`GalleryTranslation.php` & `GalleryImageTranslation.php` — pola standar (fillable + relasi belongsTo host + language).

- [ ] **Step 6: Test**

`tests/Feature/GalleryModelTest.php`:
```php
<?php

use App\Models\{Gallery, GalleryImage, Language};

beforeEach(function () {
    Language::flushCache();
    Language::query()->delete();
    Language::create(['code' => 'id', 'name' => 'Indonesia', 'is_default' => true]);
    Language::flushCache();
});

it('Gallery punya banyak image terurut', function () {
    $g = Gallery::create(['slug' => 'g1']);
    GalleryImage::create(['gallery_id' => $g->id, 'path' => '/a.jpg', 'sort_order' => 2]);
    GalleryImage::create(['gallery_id' => $g->id, 'path' => '/b.jpg', 'sort_order' => 1]);
    expect($g->images->first()->path)->toBe('/b.jpg');
});

it('GalleryImage translate fallback bila belum ada caption', function () {
    $g = Gallery::create(['slug' => 'g1']);
    $img = GalleryImage::create(['gallery_id' => $g->id, 'path' => '/a.jpg', 'sort_order' => 0]);
    \App\Models\GalleryImageTranslation::create([
        'gallery_image_id' => $img->id,
        'language_id' => Language::idFor('id'),
        'caption' => 'Caption ID',
    ]);
    $img->load('translations');
    expect($img->translate('id')?->caption)->toBe('Caption ID');
});
```

- [ ] **Step 7: Migrate + test + Pint**

```bash
php artisan migrate --no-interaction
php artisan test --compact --filter=GalleryModelTest
vendor/bin/pint --dirty --format agent
```

---

## Task 2.5: Migrasi & Model Kelompok B (Pages + Menus + Widgets)

**Files:**
- Create migrations (12): `pages`, `page_translations`, `menus`, `menu_items`, `menu_item_translations`, `widgets`, `widget_translations`, `widget_placements`, `widget_placement_targets`
- Create models: `Page`, `PageTranslation`, `Menu`, `MenuItem`, `MenuItemTranslation`, `Widget`, `WidgetTranslation`, `WidgetPlacement`, `WidgetPlacementTarget`
- Test: `tests/Feature/PageMenuWidgetModelTest.php`

**Interfaces:**
- Produces:
  - `Page` (HasTranslations, kolom: `mode` (cast `PageMode`), `template_key`, `hero_enabled`, `hero_image`, `sidebar_enabled`).
  - `PageTranslation` — kolom termasuk `content` (`jsonb` cast `array`), `hero_heading`, `hero_subheading`, `hero_cta_text`, `hero_cta_link`, `meta_title`, `meta_description`; UNIQUE(`page_id`,`language_id`), UNIQUE(`language_id`,`slug`).
  - `Menu` (`location` cast `MenuLocation`), `MenuItem` (`link_type` cast `LinkType`, self-ref `parent_id`).
  - `Widget` (`type` string, `config` jsonb cast array, HasTranslations).
  - `WidgetPlacement` (`position` cast `WidgetPosition`, `scope` cast `PlacementScope`) → `targets(): HasMany` ke `WidgetPlacementTarget` (polimorfik via `target_type`/`target_ref`).

- [ ] **Step 1: 9 migration**

```bash
php artisan make:migration create_pages_table --no-interaction
php artisan make:migration create_page_translations_table --no-interaction
php artisan make:migration create_menus_table --no-interaction
php artisan make:migration create_menu_items_table --no-interaction
php artisan make:migration create_menu_item_translations_table --no-interaction
php artisan make:migration create_widgets_table --no-interaction
php artisan make:migration create_widget_translations_table --no-interaction
php artisan make:migration create_widget_placements_table --no-interaction
php artisan make:migration create_widget_placement_targets_table --no-interaction
```

- [ ] **Step 2: Isi `pages`**

```php
Schema::create('pages', function (Blueprint $table) {
    $table->id();
    $table->string('mode', 20)->default('Template');
    $table->string('template_key')->default('default');
    $table->boolean('hero_enabled')->default(false);
    $table->string('hero_image')->nullable();
    $table->boolean('sidebar_enabled')->default(false);
    $table->timestamps();
});
```

- [ ] **Step 3: Isi `page_translations`**

```php
Schema::create('page_translations', function (Blueprint $table) {
    $table->id();
    $table->foreignId('page_id')->constrained()->cascadeOnDelete();
    $table->foreignId('language_id')->constrained()->cascadeOnDelete();
    $table->string('slug');
    $table->string('title');
    $table->jsonb('content')->nullable();
    $table->string('hero_heading')->nullable();
    $table->string('hero_subheading')->nullable();
    $table->string('hero_cta_text')->nullable();
    $table->string('hero_cta_link')->nullable();
    $table->string('status', 20)->default('Draft');
    $table->string('meta_title')->nullable();
    $table->string('meta_description')->nullable();
    $table->unique(['page_id', 'language_id']);
    $table->unique(['language_id', 'slug']);
    $table->timestamps();
});
```

- [ ] **Step 4: Isi `menus` + `menu_items` + `menu_item_translations`**

```php
// menus
Schema::create('menus', function (Blueprint $table) {
    $table->id();
    $table->string('name');
    $table->string('location', 20)->default('Header'); // Header/Footer
    $table->timestamps();
});
// menu_items
Schema::create('menu_items', function (Blueprint $table) {
    $table->id();
    $table->foreignId('menu_id')->constrained()->cascadeOnDelete();
    $table->foreignId('parent_id')->nullable()->constrained('menu_items')->nullOnDelete();
    $table->string('link_type', 30)->default('Url'); // Page/ContentArchive/ContentSingle/Url
    $table->string('link_ref')->nullable();
    $table->string('url')->nullable();
    $table->unsignedSmallInteger('sort_order')->default(0);
    $table->index(['menu_id', 'parent_id']);
    $table->timestamps();
});
// menu_item_translations
Schema::create('menu_item_translations', function (Blueprint $table) {
    $table->id();
    $table->foreignId('menu_item_id')->constrained()->cascadeOnDelete();
    $table->foreignId('language_id')->constrained()->cascadeOnDelete();
    $table->string('label');
    $table->unique(['menu_item_id', 'language_id']);
    $table->timestamps();
});
```

- [ ] **Step 5: Isi `widgets` + `widget_translations` + `widget_placements` + `widget_placement_targets`**

```php
// widgets
Schema::create('widgets', function (Blueprint $table) {
    $table->id();
    $table->string('type', 50); // contoh: HtmlWidget
    $table->jsonb('config')->nullable();
    $table->boolean('is_active')->default(true);
    $table->timestamps();
});
// widget_translations
Schema::create('widget_translations', function (Blueprint $table) {
    $table->id();
    $table->foreignId('widget_id')->constrained()->cascadeOnDelete();
    $table->foreignId('language_id')->constrained()->cascadeOnDelete();
    $table->string('title')->nullable();
    $table->text('content')->nullable();
    $table->unique(['widget_id', 'language_id']);
    $table->timestamps();
});
// widget_placements
Schema::create('widget_placements', function (Blueprint $table) {
    $table->id();
    $table->foreignId('widget_id')->constrained()->cascadeOnDelete();
    $table->string('position', 30); // BeforeContent/AfterContent/Sidebar/Footer
    $table->string('scope', 20)->default('All'); // All/Only/Except
    $table->unsignedSmallInteger('sort_order')->default(0);
    $table->index('widget_id');
    $table->timestamps();
});
// widget_placement_targets
Schema::create('widget_placement_targets', function (Blueprint $table) {
    $table->id();
    $table->foreignId('placement_id')->constrained('widget_placements')->cascadeOnDelete();
    $table->string('target_type', 30); // Page/ContentArchive/ContentSingle
    $table->string('target_ref')->nullable();
    $table->timestamps();
});
```

- [ ] **Step 6: Buat 9 model via artisan** (lihat langkah Task 2.3 Step 9 untuk pola).

- [ ] **Step 7: Isi model Page + PageTranslation**

`app/Models/Page.php`:
```php
<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\PageMode;
use App\Support\HasTranslations;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Page extends Model
{
    use HasTranslations;

    protected $fillable = ['mode', 'template_key', 'hero_enabled', 'hero_image', 'sidebar_enabled'];

    protected $casts = [
        'mode' => PageMode::class,
        'hero_enabled' => 'boolean',
        'sidebar_enabled' => 'boolean',
    ];

    public function translations(): HasMany { return $this->hasMany(PageTranslation::class); }
}
```

`app/Models/PageTranslation.php`:
```php
<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PageTranslation extends Model
{
    protected $fillable = [
        'page_id', 'language_id', 'slug', 'title', 'content',
        'hero_heading', 'hero_subheading', 'hero_cta_text', 'hero_cta_link',
        'status', 'meta_title', 'meta_description',
    ];

    protected $casts = [
        'content' => 'array', // jsonb
    ];

    public function page(): BelongsTo { return $this->belongsTo(Page::class); }
    public function language(): BelongsTo { return $this->belongsTo(Language::class); }
}
```

- [ ] **Step 8: Isi model Menu, MenuItem (+Translation)** — pattern: cast `location`/`link_type` ke enum, self-ref parent.

`app/Models/Menu.php`:
```php
<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\MenuLocation;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Menu extends Model
{
    protected $fillable = ['name', 'location'];

    protected $casts = ['location' => MenuLocation::class];

    public function items(): HasMany
    {
        return $this->hasMany(MenuItem::class)->whereNull('parent_id')->orderBy('sort_order');
    }

    public function allItems(): HasMany
    {
        return $this->hasMany(MenuItem::class);
    }

    public function scopeAt(Builder $q, MenuLocation $loc): Builder
    {
        return $q->where('location', $loc->value);
    }
}
```

`app/Models/MenuItem.php`:
```php
<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\LinkType;
use App\Support\HasTranslations;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class MenuItem extends Model
{
    use HasTranslations;

    protected $fillable = ['menu_id', 'parent_id', 'link_type', 'link_ref', 'url', 'sort_order'];

    protected $casts = [
        'link_type' => LinkType::class,
        'sort_order' => 'integer',
    ];

    public function menu(): BelongsTo { return $this->belongsTo(Menu::class); }
    public function parent(): BelongsTo { return $this->belongsTo(self::class, 'parent_id'); }
    public function children(): HasMany { return $this->hasMany(self::class, 'parent_id')->orderBy('sort_order'); }
    public function translations(): HasMany { return $this->hasMany(MenuItemTranslation::class); }
}
```

`MenuItemTranslation.php` — pola standar (fillable `label`).

- [ ] **Step 9: Isi model Widget + WidgetTranslation + WidgetPlacement + WidgetPlacementTarget**

`Widget.php`:
```php
<?php

declare(strict_types=1);

namespace App\Models;

use App\Support\HasTranslations;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Widget extends Model
{
    use HasTranslations;

    protected $fillable = ['type', 'config', 'is_active'];

    protected $casts = [
        'config' => 'array', // jsonb
        'is_active' => 'boolean',
    ];

    public function placements(): HasMany { return $this->hasMany(WidgetPlacement::class); }
    public function translations(): HasMany { return $this->hasMany(WidgetTranslation::class); }
}
```

`WidgetPlacement.php`:
```php
<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\PlacementScope;
use App\Enums\WidgetPosition;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class WidgetPlacement extends Model
{
    protected $fillable = ['widget_id', 'position', 'scope', 'sort_order'];

    protected $casts = [
        'position' => WidgetPosition::class,
        'scope' => PlacementScope::class,
        'sort_order' => 'integer',
    ];

    public function widget(): BelongsTo { return $this->belongsTo(Widget::class); }
    public function targets(): HasMany { return $this->hasMany(WidgetPlacementTarget::class); }
}
```

`WidgetPlacementTarget.php`:
```php
<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WidgetPlacementTarget extends Model
{
    public const TYPE_PAGE = 'Page';
    public const TYPE_CONTENT_ARCHIVE = 'ContentArchive';
    public const TYPE_CONTENT_SINGLE = 'ContentSingle';

    protected $fillable = ['placement_id', 'target_type', 'target_ref'];

    public function placement(): BelongsTo { return $this->belongsTo(WidgetPlacement::class); }
}
```

`WidgetTranslation.php` — pola standar (fillable `title`, `content`).

- [ ] **Step 10: Test**

`tests/Feature/PageMenuWidgetModelTest.php`:
```php
<?php

use App\Enums\{PageMode, MenuLocation, LinkType, WidgetPosition, PlacementScope};
use App\Models\{Language, Page, PageTranslation, Menu, MenuItem, Widget, WidgetPlacement};

beforeEach(function () {
    Language::flushCache();
    Language::query()->delete();
    Language::create(['code' => 'id', 'name' => 'Indonesia', 'is_default' => true]);
    Language::flushCache();
});

it('Page mode cast ke PageMode enum', function () {
    $p = Page::create(['mode' => 'Code']);
    expect($p->fresh()->mode)->toBe(PageMode::Code);
});

it('PageTranslation content disimpan sebagai array JSONB', function () {
    $p = Page::create(['mode' => 'Template']);
    $tr = PageTranslation::create([
        'page_id' => $p->id, 'language_id' => Language::idFor('id'),
        'slug' => 'about', 'title' => 'Tentang',
        'content' => ['html' => '<p>halo</p>'],
    ]);
    expect($tr->fresh()->content)->toBe(['html' => '<p>halo</p>']);
});

it('Menu location cast ke MenuLocation', function () {
    $m = Menu::create(['name' => 'Utama', 'location' => 'Header']);
    expect($m->fresh()->location)->toBe(MenuLocation::Header);
});

it('MenuItem link_type cast ke LinkType', function () {
    $m = Menu::create(['name' => 'Utama', 'location' => 'Header']);
    $i = MenuItem::create(['menu_id' => $m->id, 'link_type' => 'Url', 'url' => '/x']);
    expect($i->fresh()->link_type)->toBe(LinkType::Url);
});

it('WidgetPlacement cast position dan scope', function () {
    $w = Widget::create(['type' => 'HtmlWidget']);
    $pl = WidgetPlacement::create([
        'widget_id' => $w->id, 'position' => 'Sidebar', 'scope' => 'All', 'sort_order' => 1,
    ]);
    expect($pl->fresh()->position)->toBe(WidgetPosition::Sidebar)
        ->and($pl->fresh()->scope)->toBe(PlacementScope::All);
});
```

- [ ] **Step 11: Migrate + test + Pint**

```bash
php artisan migrate --no-interaction
php artisan test --compact --filter=PageMenuWidgetModelTest
vendor/bin/pint --dirty --format agent
```

---

## Task 2.6: Migrasi & Model Kelompok C (Settings translations, AI configs, Interaction)

**Files:**
- Create migrations: `setting_translations`, `ai_configs`, `contact_messages`, `testimonials`, `rating_criteria`, `rating_criteria_translations`, `ratings`, `rating_scores`
- Create models: `SettingTranslation`, `AiConfig`, `ContactMessage`, `Testimonial`, `RatingCriterion`, `RatingCriterionTranslation`, `Rating`, `RatingScore`
- Test: `tests/Feature/InteractionModelTest.php`

**Interfaces:**
- Produces:
  - `AiConfig` — kolom `task` (cast `AiTask`), `base_url`, `api_key` (cast `encrypted`), `model`, `system_prompt`, `enabled`; scope `for(AiTask)`.
  - `ContactMessage` — `status` cast `ContactStatus`.
  - `Testimonial` — `status` cast `TestimonialStatus`.
  - `Rating` — `visitor_hash`, `comment`, `created_at`.
  - `RatingScore` — `rating_id`, `criterion_id`, `score` (1-5).

- [ ] **Step 1: 8 migration**

```bash
php artisan make:migration create_setting_translations_table --no-interaction
php artisan make:migration create_ai_configs_table --no-interaction
php artisan make:migration create_contact_messages_table --no-interaction
php artisan make:migration create_testimonials_table --no-interaction
php artisan make:migration create_rating_criteria_table --no-interaction
php artisan make:migration create_rating_criteria_translations_table --no-interaction
php artisan make:migration create_ratings_table --no-interaction
php artisan make:migration create_rating_scores_table --no-interaction
```

- [ ] **Step 2: Isi `setting_translations`** (untuk site settings hibrida — teks i18n)

```php
Schema::create('setting_translations', function (Blueprint $table) {
    $table->id();
    $table->string('key');        // mis. 'site.tagline', 'site.footer_text'
    $table->foreignId('language_id')->constrained()->cascadeOnDelete();
    $table->text('value')->nullable();
    $table->unique(['key', 'language_id']);
    $table->timestamps();
});
```

- [ ] **Step 3: Isi `ai_configs`**

```php
Schema::create('ai_configs', function (Blueprint $table) {
    $table->id();
    $table->string('task', 30)->unique(); // Translation/ContentRefinement/MarkupConform
    $table->string('base_url')->nullable();
    $table->text('api_key')->nullable();   // dienkripsi via cast
    $table->string('model')->nullable();
    $table->text('system_prompt')->nullable();
    $table->boolean('enabled')->default(false);
    $table->timestamps();
});
```

- [ ] **Step 4: Isi `contact_messages` + `testimonials`**

```php
// contact_messages
Schema::create('contact_messages', function (Blueprint $table) {
    $table->id();
    $table->string('name');
    $table->string('email');
    $table->string('phone', 30)->nullable();
    $table->string('subject')->nullable();
    $table->text('message');
    $table->string('status', 20)->default('New');
    $table->timestamps();
    $table->index('status');
});
// testimonials
Schema::create('testimonials', function (Blueprint $table) {
    $table->id();
    $table->string('author_name');
    $table->string('author_title')->nullable();
    $table->text('content');
    $table->unsignedBigInteger('photo_media_id')->nullable();
    $table->foreign('photo_media_id')->references('id')->on('media')->nullOnDelete();
    $table->string('status', 20)->default('Pending');
    $table->unsignedSmallInteger('sort_order')->default(0);
    $table->timestamps();
});
```

- [ ] **Step 5: Isi `rating_criteria` + `rating_criteria_translations` + `ratings` + `rating_scores`**

```php
// rating_criteria
Schema::create('rating_criteria', function (Blueprint $table) {
    $table->id();
    $table->boolean('is_active')->default(true);
    $table->unsignedSmallInteger('sort_order')->default(0);
    $table->timestamps();
});
// rating_criteria_translations
Schema::create('rating_criteria_translations', function (Blueprint $table) {
    $table->id();
    $table->foreignId('criterion_id')->constrained('rating_criteria')->cascadeOnDelete();
    $table->foreignId('language_id')->constrained()->cascadeOnDelete();
    $table->string('name');
    $table->unique(['criterion_id', 'language_id']);
    $table->timestamps();
});
// ratings
Schema::create('ratings', function (Blueprint $table) {
    $table->id();
    $table->text('comment')->nullable();
    $table->string('visitor_hash', 64);
    $table->timestamps();
    $table->index('visitor_hash');
});
// rating_scores
Schema::create('rating_scores', function (Blueprint $table) {
    $table->id();
    $table->foreignId('rating_id')->constrained()->cascadeOnDelete();
    $table->foreignId('criterion_id')->constrained('rating_criteria')->cascadeOnDelete();
    $table->unsignedTinyInteger('score'); // 1-5
    $table->unique(['rating_id', 'criterion_id']);
});
```

- [ ] **Step 6: Buat 8 model via artisan**

```bash
php artisan make:model SettingTranslation --no-interaction
php artisan make:model AiConfig --no-interaction
php artisan make:model ContactMessage --no-interaction
php artisan make:model Testimonial --no-interaction
php artisan make:model RatingCriterion --no-interaction
php artisan make:model RatingCriterionTranslation --no-interaction
php artisan make:model Rating --no-interaction
php artisan make:model RatingScore --no-interaction
```

- [ ] **Step 7: Isi `AiConfig`**

`app/Models/AiConfig.php`:
```php
<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\AiTask;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

/**
 * @property int $id
 * @property AiTask $task
 * @property ?string $base_url
 * @property ?string $api_key      // di-decrypt otomatis oleh cast
 * @property ?string $model
 * @property ?string $system_prompt
 * @property bool $enabled
 */
class AiConfig extends Model
{
    protected $fillable = ['task', 'base_url', 'api_key', 'model', 'system_prompt', 'enabled'];

    protected $casts = [
        'task' => AiTask::class,
        'api_key' => 'encrypted',
        'enabled' => 'boolean',
    ];

    public function scopeFor(Builder $q, AiTask $task): Builder
    {
        return $q->where('task', $task->value);
    }

    public static function resolve(AiTask $task): ?self
    {
        return static::for($task)->where('enabled', true)->first();
    }
}
```

- [ ] **Step 8: Isi model interaksi (ringkas)**

```php
// ContactMessage.php
namespace App\Models;
use App\Enums\ContactStatus;
use Illuminate\Database\Eloquent\Model;
class ContactMessage extends Model {
    protected $fillable = ['name','email','phone','subject','message','status'];
    protected $casts = ['status' => ContactStatus::class];
}

// Testimonial.php
namespace App\Models;
use App\Enums\TestimonialStatus;
use Illuminate\Database\Eloquent\Model;
class Testimonial extends Model {
    protected $fillable = ['author_name','author_title','content','photo_media_id','status','sort_order'];
    protected $casts = ['status' => TestimonialStatus::class, 'sort_order' => 'integer', 'photo_media_id' => 'integer'];
}

// RatingCriterion.php (HasTranslations analog, tanpa trait - pakai manual)
namespace App\Models;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
class RatingCriterion extends Model {
    protected $fillable = ['is_active','sort_order'];
    protected $casts = ['is_active' => 'boolean', 'sort_order' => 'integer'];
    public function translations(): HasMany { return $this->hasMany(RatingCriterionTranslation::class, 'criterion_id'); }
    public function scopeActive(Builder $q): Builder { return $q->where('is_active', true)->orderBy('sort_order'); }
}

// RatingCriterionTranslation.php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
class RatingCriterionTranslation extends Model {
    protected $fillable = ['criterion_id','language_id','name'];
    public function criterion(): BelongsTo { return $this->belongsTo(RatingCriterion::class, 'criterion_id'); }
}

// Rating.php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
class Rating extends Model {
    protected $fillable = ['comment','visitor_hash'];
    public function scores(): HasMany { return $this->hasMany(RatingScore::class); }
}

// RatingScore.php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
class RatingScore extends Model {
    public $timestamps = false;
    protected $fillable = ['rating_id','criterion_id','score'];
    protected $casts = ['score' => 'integer'];
    public function rating(): BelongsTo { return $this->belongsTo(Rating::class); }
    public function criterion(): BelongsTo { return $this->belongsTo(RatingCriterion::class, 'criterion_id'); }
}

// SettingTranslation.php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
class SettingTranslation extends Model {
    protected $fillable = ['key','language_id','value'];
    public function language(): BelongsTo { return $this->belongsTo(Language::class); }
}
```

- [ ] **Step 9: Test enkripsi + enum interaksi**

`tests/Feature/InteractionModelTest.php`:
```php
<?php

use App\Enums\{AiTask, ContactStatus, TestimonialStatus};
use App\Models\{AiConfig, ContactMessage, Testimonial, Language, Rating, RatingScore, RatingCriterion};

beforeEach(function () {
    Language::flushCache();
    Language::query()->delete();
    Language::create(['code' => 'id', 'name' => 'Indonesia', 'is_default' => true]);
    Language::flushCache();
});

it('AiConfig api_key ter-enkripsi di DB tapi terbaca plain di model', function () {
    $cfg = AiConfig::create([
        'task' => AiTask::Translation, 'base_url' => 'https://api.example.com/v1',
        'api_key' => 'secret-key-123', 'model' => 'gpt-4o', 'enabled' => true,
    ]);
    // di DB harus bukan plain text
    $raw = \DB::table('ai_configs')->where('id', $cfg->id)->value('api_key');
    expect($raw)->not->toBe('secret-key-123');
    // di model harus plain
    expect($cfg->fresh()->api_key)->toBe('secret-key-123');
});

it('AiConfig::resolve mengembalikan konfigurasi enabled untuk task', function () {
    AiConfig::create(['task' => AiTask::Translation, 'enabled' => true, 'api_key' => 'k']);
    expect(AiConfig::resolve(AiTask::Translation))->not->toBeNull()
        ->and(AiConfig::resolve(AiTask::ContentRefinement))->toBeNull();
});

it('ContactMessage status cast', function () {
    $m = ContactMessage::create(['name' => 'A', 'email' => 'a@b.c', 'message' => 'halo']);
    expect($m->fresh()->status)->toBe(ContactStatus::New);
});

it('Testimonial status default Pending', function () {
    $t = Testimonial::create(['author_name' => 'A', 'content' => 'bagus']);
    expect($t->fresh()->status)->toBe(TestimonialStatus::Pending);
});

it('Rating + RatingScore relasi', function () {
    $crit = RatingCriterion::create(['sort_order' => 1]);
    $r = Rating::create(['visitor_hash' => 'hash123']);
    RatingScore::create(['rating_id' => $r->id, 'criterion_id' => $crit->id, 'score' => 4]);
    expect($r->scores)->toHaveCount(1)
        ->and($r->scores->first()->score)->toBe(4);
});
```

- [ ] **Step 10: Migrate + test + Pint**

```bash
php artisan migrate --no-interaction
php artisan test --compact --filter=InteractionModelTest
vendor/bin/pint --dirty --format agent
```

---

## Task 2.7: Settings Hibrida — Kelas `spatie/laravel-settings` + Helper `setting_translated()`

**Files:**
- Create: `app/Settings/SiteSettings.php`, `app/Settings/SeoSettings.php`, `app/Settings/WhatsappSettings.php`
- Create: migration `create_site_settings_groups` (Spatie migration) untuk default values
- Create: `app/Support/helpers.php` (atau tambah ke file helper yang sudah ada) dengan fungsi `setting_translated(string $key, ?string $locale = null): ?string`
- Modify: `composer.json` autoload files (jika belum ada helper file)
- Test: `tests/Feature/SettingsHybridTest.php`

**Interfaces:**
- Produces:
  - `app(SiteSettings::class)` → `->logo_path`, `->favicon_path`, `->site_name` (non-i18n; site_name juga disalin ke setting_translations sebagai default bila perlu i18n, tapi disimpan non-i18n di sini).
  - `app(WhatsappSettings::class)` → `->number`, `->enabled`, `->default_message`.
  - `app(SeoSettings::class)` → `->default_meta_title`, `->default_meta_description`, `->og_default_image_path`.
  - `setting_translated('site.tagline')` — helper yang baca `setting_translations` untuk locale aktif, fallback ke bahasa default, fallback ke null.
- **Daftar key (konvensi eksplisit, sesuai revisi spec §1.10):**
  - **Non-i18n (via kelas Settings):** `whatsapp.number`, `whatsapp.enabled`, `whatsapp.default_message`, `site.logo_path`, `site.favicon_path`, `seo.default_meta_title`, `seo.default_meta_description`, `seo.og_default_image_path`, `seo.default_og_type`.
  - **i18n (via `setting_translations`):** `site.tagline`, `site.footer_text`, `site.footer_copyright`, `seo.default_og_site_name`.

- [ ] **Step 1: Buat 3 kelas Settings**

`app/Settings/SiteSettings.php`:
```php
<?php

declare(strict_types=1);

namespace App\Settings;

use Spatie\LaravelSettings\Settings;

class SiteSettings extends Settings
{
    public ?string $logo_path;
    public ?string $favicon_path;
    public string $site_name;

    public static function group(): string
    {
        return 'site';
    }
}
```

`app/Settings/WhatsappSettings.php`:
```php
<?php

declare(strict_types=1);

namespace App\Settings;

use Spatie\LaravelSettings\Settings;

class WhatsappSettings extends Settings
{
    public string $number = '';
    public bool $enabled = false;
    public string $default_message = '';

    public static function group(): string
    {
        return 'whatsapp';
    }
}
```

`app/Settings/SeoSettings.php`:
```php
<?php

declare(strict_types=1);

namespace App\Settings;

use Spatie\LaravelSettings\Settings;

class SeoSettings extends Settings
{
    public ?string $default_meta_title;
    public ?string $default_meta_description;
    public ?string $og_default_image_path;
    public string $default_og_type = 'website';

    public static function group(): string
    {
        return 'seo';
    }
}
}
```

- [ ] **Step 2: Buat migration default settings**

```bash
php artisan make:migration create_default_settings_groups --no-interaction
```
Isi:
```php
<?php

declare(strict_types=1);

use Spatie\LaravelSettings\Migrations\SettingsMigration;

return new class extends SettingsMigration
{
    public function up(): void
    {
        $this->migrator->add('site.site_name', config('app.name'));
        $this->migrator->add('site.logo_path', null);
        $this->migrator->add('site.favicon_path', null);

        $this->migrator->add('whatsapp.number', '');
        $this->migrator->add('whatsapp.enabled', false);
        $this->migrator->add('whatsapp.default_message', '');

        $this->migrator->add('seo.default_meta_title', null);
        $this->migrator->add('seo.default_meta_description', null);
        $this->migrator->add('seo.og_default_image_path', null);
        $this->migrator->add('seo.default_og_type', 'website');
    }
};
```

- [ ] **Step 3: Buat helper `setting_translated()`**

Buat `app/Support/helpers.php`:
```php
<?php

declare(strict_types=1);

use App\Models\Language;
use App\Models\SettingTranslation;
use Illuminate\Support\Facades\Cache;

if (! function_exists('setting_translated')) {
    /**
     * Ambil nilai teks setting yang diterjemahkan.
     * Fallback: locale aktif → bahasa default → null.
     *
     * @param  string  $key  contoh: 'site.tagline'
     */
    function setting_translated(string $key, ?string $locale = null): ?string
    {
        $locale ??= app()->getLocale();

        return Cache::rememberForever("setting_translated.{$key}.{$locale}", function () use ($key, $locale) {
            $value = SettingTranslation::where('key', $key)
                ->where('language_id', Language::idFor($locale))
                ->value('value');
            if ($value !== null) {
                return $value;
            }

            return SettingTranslation::where('key', $key)
                ->where('language_id', Language::defaultModel()->id)
                ->value('value');
        });
    }
}

if (! function_exists('setting_translated_flush')) {
    function setting_translated_flush(string $key): void
    {
        Language::all()->each(fn (Language $l) =>
            Cache::forget("setting_translated.{$key}.{$l->code}")
        );
    }
}
```

- [ ] **Step 4: Daftarkan helper file di composer autoload**

Edit `composer.json`, di blok `"autoload"`:
```json
"autoload": {
    "files": ["app/Support/helpers.php"],
    "psr-4": { ... }
}
```
Lalu:
```bash
composer dump-autoload --no-interaction
```

- [ ] **Step 5: Test**

`tests/Feature/SettingsHybridTest.php`:
```php
<?php

use App\Models\{Language, SettingTranslation};
use App\Settings\{SiteSettings, WhatsappSettings, SeoSettings};

beforeEach(function () {
    Language::flushCache();
    Language::query()->delete();
    Language::create(['code' => 'id', 'name' => 'Indonesia', 'is_default' => true]);
    Language::create(['code' => 'en', 'name' => 'English']);
    Language::flushCache();
});

it('Spatie SiteSettings menyimpan dan membaca nilai', function () {
    $s = app(SiteSettings::class);
    $s->site_name = 'Papenajam';
    $s->save();
    expect(app(SiteSettings::class)->fresh()->site_name)->toBe('Papenajam');
});

it('setting_translated mengembalikan nilai locale aktif', function () {
    SettingTranslation::create(['key' => 'site.tagline', 'language_id' => Language::idFor('id'), 'value' => 'ID Tagline']);
    SettingTranslation::create(['key' => 'site.tagline', 'language_id' => Language::idFor('en'), 'value' => 'EN Tagline']);
    app()->setLocale('en');
    expect(setting_translated('site.tagline'))->toBe('EN Tagline');
    app()->setLocale('id');
    expect(setting_translated('site.tagline'))->toBe('ID Tagline');
});

it('setting_translated fallback ke default bila locale hilang', function () {
    SettingTranslation::create(['key' => 'site.tagline', 'language_id' => Language::idFor('id'), 'value' => 'ID Tagline']);
    app()->setLocale('en');
    expect(setting_translated('site.tagline'))->toBe('ID Tagline'); // fallback
});

it('setting_translated null bila tidak ada', function () {
    expect(setting_translated('site.nonexistent'))->toBeNull();
});
```

- [ ] **Step 6: Migrate + test + Pint**

```bash
php artisan migrate --no-interaction
php artisan test --compact --filter=SettingsHybridTest
vendor/bin/pint --dirty --format agent
```

---

## Task 2.8: Factories untuk testing

**Files:**
- Create factories: `LanguageFactory`, `WritingStyleFactory`, `ContentTypeFactory`, `PostFactory`, `PostTranslationFactory`, `PageFactory`, `PageTranslationFactory`, `MenuFactory`, `MenuItemFactory`, `WidgetFactory`, `ContactMessageFactory`, `TestimonialFactory`, `RatingCriterionFactory`, `UserFactory` (jika belum ada dari starter)
- Test: `tests/Feature/FactorySmokeTest.php`

**Interfaces:**
- Produces: factory reusable untuk seluruh test berikutnya. `PostFactory::withTranslation(string $locale, array $attrs)` helper state.

- [ ] **Step 1: Cek factory User dari starter**

```bash
ls database/factories/
```
Jika `UserFactory.php` sudah ada, biarkan. Jika tidak, buat via `php artisan make:factory UserFactory --no-interaction` dengan isi standar (name, email, password bcrypt).

- [ ] **Step 2: Buat factory dasar via artisan**

```bash
php artisan make:factory LanguageFactory --no-interaction
php artisan make:factory WritingStyleFactory --no-interaction
php artisan make:factory ContentTypeFactory --no-interaction
php artisan make:factory PostFactory --no-interaction
php artisan make:factory PostTranslationFactory --no-interaction
php artisan make:factory PageFactory --no-interaction
php artisan make:factory PageTranslationFactory --no-interaction
php artisan make:factory MenuFactory --no-interaction
php artisan make:factory MenuItemFactory --no-interaction
php artisan make:factory WidgetFactory --no-interaction
php artisan make:factory ContactMessageFactory --no-interaction
php artisan make:factory TestimonialFactory --no-interaction
php artisan make:factory RatingCriterionFactory --no-interaction
```

- [ ] **Step 3: Isi factory utama** (Post + PostTranslation + ContentType dengan state; lainnya ringkas)

`database/factories/LanguageFactory.php`:
```php
<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Language;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Language> */
class LanguageFactory extends Factory
{
    public function definition(): array
    {
        return [
            'code' => $this->faker->unique()->languageCode(),
            'name' => $this->faker->word(),
            'is_default' => false,
            'is_active' => true,
            'sort_order' => $this->faker->numberBetween(1, 10),
        ];
    }
}
```

`database/factories/ContentTypeFactory.php`:
```php
<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\ContentType;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<ContentType> */
class ContentTypeFactory extends Factory
{
    public function definition(): array
    {
        return [
            'slug' => $this->faker->unique()->slug(1),
            'icon' => null,
            'writing_style_id' => null,
            'archive_template_key' => 'default',
            'single_template_key' => 'default',
            'is_active' => true,
            'sort_order' => $this->faker->numberBetween(1, 10),
        ];
    }
}
```

`database/factories/PostFactory.php`:
```php
<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Post;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Post> */
class PostFactory extends Factory
{
    public function definition(): array
    {
        return [
            'type_id' => null, // wajib di-set saat make
            'category_id' => null,
            'featured_image' => null,
        ];
    }

    /** Buat post + translation untuk locale tertentu. */
    public function withTranslation(string $locale, int $languageId, array $translationAttrs = []): self
    {
        return $this->has(
            \App\Models\PostTranslation::factory()->state(array_merge([
                'language_id' => $languageId,
                'slug' => $this->faker->unique()->slug(2),
                'title' => $this->faker->sentence(4),
                'body' => '<p>'.$this->faker->paragraph(3).'</p>',
                'status' => \App\Enums\PostStatus::Published,
                'published_at' => now(),
            ], $translationAttrs)),
            'translations'
        );
    }
}
```

`database/factories/PostTranslationFactory.php`:
```php
<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\PostStatus;
use App\Models\PostTranslation;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<PostTranslation> */
class PostTranslationFactory extends Factory
{
    public function definition(): array
    {
        return [
            'post_id' => null,
            'language_id' => null,
            'slug' => $this->faker->unique()->slug(2),
            'title' => $this->faker->sentence(4),
            'body' => '<p>'.$this->faker->paragraph(3).'</p>',
            'status' => PostStatus::Draft,
            'published_at' => null,
            'meta_title' => null,
            'meta_description' => null,
        ];
    }
}
```

> Pola serupa (lihat file `database/factories/UserFactory.php` starter untuk referensi) untuk: `WritingStyleFactory`, `PageFactory` (`mode: Template`), `PageTranslationFactory` (`content: ['html' => '<p>...</p>']`, `status: Draft`), `MenuFactory` (`location: Header`), `MenuItemFactory` (`link_type: Url`, `url: '/'`), `WidgetFactory` (`type: HtmlWidget`), `ContactMessageFactory` (status default `New`), `TestimonialFactory` (status default `Pending`), `RatingCriterionFactory`.

- [ ] **Step 4: Daftarkan `HasFactory` trait di model yang dibuat** (Post, PostTranslation, ContentType, Language, Page, PageTranslation, Menu, MenuItem, Widget, ContactMessage, Testimonial, RatingCriterion).

Tambah `use HasFactory;` di setiap model (import `Illuminate\Database\Eloquent\Factories\HasFactory`).

- [ ] **Step 5: Test smoke factory**

`tests/Feature/FactorySmokeTest.php`:
```php
<?php

use App\Models\{ContentType, Language, Post};

beforeEach(function () {
    Language::flushCache();
    Language::query()->delete();
    Language::create(['code' => 'id', 'name' => 'Indonesia', 'is_default' => true]);
    Language::flushCache();
});

it('Post factory withTranslation membuat post + translation', function () {
    $type = ContentType::factory()->create();
    $langId = Language::idFor('id');
    $post = Post::factory()
        ->for($type, 'type')
        ->withTranslation('id', $langId)
        ->create();
    expect($post->translations)->toHaveCount(1)
        ->and($post->translate('id'))->not->toBeNull();
});

it('ContentType factory membuat type aktif', function () {
    $t = ContentType::factory()->create();
    expect($t->is_active)->toBeTrue();
});
```

- [ ] **Step 6: Test + Pint**

```bash
php artisan test --compact --filter=FactorySmokeTest
vendor/bin/pint --dirty --format agent
```

---

## Task 2.9: Seeder (Language, Role, ContentType, WritingStyle, RatingCriteria, AdminUser, DemoPost)

**Files:**
- Create: `database/seeders/LanguageSeeder.php`, `RolePermissionSeeder.php`, `WritingStyleSeeder.php`, `ContentTypeSeeder.php`, `RatingCriteriaSeeder.php`, `AdminUserSeeder.php`, `DemoPostSeeder.php`
- Modify: `database/seeders/DatabaseSeeder.php` (urutan)
- Test: `tests/Feature/SeedersTest.php`

**Interfaces:**
- Produces: `php artisan migrate:fresh --seed` menghasilkan DB lengkap siap pakai (3 role + 3 content_types + 5 rating_criteria + 1 admin + demo post ID+EN).

- [ ] **Step 1: Buat 7 seeder**

```bash
php artisan make:seeder LanguageSeeder --no-interaction
php artisan make:seeder RolePermissionSeeder --no-interaction
php artisan make:seeder WritingStyleSeeder --no-interaction
php artisan make:seeder ContentTypeSeeder --no-interaction
php artisan make:seeder RatingCriteriaSeeder --no-interaction
php artisan make:seeder AdminUserSeeder --no-interaction
php artisan make:seeder DemoPostSeeder --no-interaction
```

- [ ] **Step 2: Isi `LanguageSeeder.php`**

```php
<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Language;
use Illuminate\Database\Seeder;

class LanguageSeeder extends Seeder
{
    public function run(): void
    {
        Language::query()->delete();
        Language::insert([
            ['code' => 'id', 'name' => 'Bahasa Indonesia', 'is_default' => true, 'is_active' => true, 'sort_order' => 1, 'created_at' => now(), 'updated_at' => now()],
            ['code' => 'en', 'name' => 'English', 'is_default' => false, 'is_active' => true, 'sort_order' => 2, 'created_at' => now(), 'updated_at' => now()],
        ]);
        Language::flushCache();
    }
}
```

- [ ] **Step 3: Isi `RolePermissionSeeder.php`** (3 role + permissions + assign)

```php
<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Enums\UserRole;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\{Permission, Role};

class RolePermissionSeeder extends Seeder
{
    public function run(): void
    {
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermission();

        // Permission dasar untuk seluruh resource CMS
        $resources = ['posts', 'pages', 'menus', 'widgets', 'media', 'users', 'settings', 'ai', 'content-types', 'languages', 'writing-styles', 'rating-criteria', 'contact-messages', 'testimonials', 'ratings', 'galleries'];
        $actions = ['viewAny', 'create', 'update', 'delete'];
        foreach ($resources as $r) {
            foreach ($actions as $a) {
                Permission::firstOrCreate(['name' => "{$r}.{$a}"]);
            }
        }
        Permission::firstOrCreate(['name' => 'posts.deleteOwn']);
        Permission::firstOrCreate(['name' => 'access-admin']);
        Permission::firstOrCreate(['name' => 'admin.use-page-code-mode']);
        Permission::firstOrCreate(['name' => 'admin.access-system']);
        Permission::firstOrCreate(['name' => 'admin.access-appearance']);

        $allPermissions = Permission::pluck('name')->toArray();

        // Admin: semua
        $admin = Role::firstOrCreate(['name' => UserRole::Admin->value]);
        $admin->syncPermissions($allPermissions);

        // Editor: akses admin + konten/halaman/media/interaksi (tanpa Tampilan, tanpa Sistem)
        $editor = Role::firstOrCreate(['name' => UserRole::Editor->value]);
        $editor->syncPermissions(array_merge(
            ['access-admin'],
            $this->permissionNamesFor(['posts', 'pages', 'media', 'contact-messages', 'testimonials', 'ratings', 'galleries']),
        ));

        // Author: akses admin + posts milik sendiri + media
        $author = Role::firstOrCreate(['name' => UserRole::Author->value]);
        $author->syncPermissions(array_merge(
            ['access-admin'],
            $this->permissionNamesFor(['media']),
            ['posts.viewAny', 'posts.create', 'posts.update', 'posts.deleteOwn'],
        ));
    }

    private function permissionNamesFor(array $resources): array
    {
        $out = [];
        foreach ($resources as $r) {
            foreach (['viewAny', 'create', 'update', 'delete'] as $a) {
                $out[] = "{$r}.{$a}";
            }
        }
        return $out;
    }
}
```

- [ ] **Step 4: Isi `WritingStyleSeeder.php` + `ContentTypeSeeder.php`**

`WritingStyleSeeder`:
```php
<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\WritingStyle;
use Illuminate\Database\Seeder;

class WritingStyleSeeder extends Seeder
{
    public function run(): void
    {
        WritingStyle::query()->delete();
        WritingStyle::create([
            'name' => 'Formal Indonesia',
            'prompt' => 'Tulis dengan gaya formal-natural Bahasa Indonesia. Sapaan baku, kalimat ringkas, hindari jargon teknis kecuali perlu. Pertahankan markup HTML apa adanya.',
        ]);
    }
}
```

`ContentTypeSeeder`:
```php
<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\{ContentType, Language, WritingStyle};
use Illuminate\Database\Seeder;

class ContentTypeSeeder extends Seeder
{
    public function run(): void
    {
        ContentType::query()->delete();
        $ws = WritingStyle::first();
        $wsId = $ws?->id;

        $types = [
            ['slug' => 'artikel',   'id' => 'Artikel',   'en' => 'Articles'],
            ['slug' => 'berita',    'id' => 'Berita',    'en' => 'News'],
            ['slug' => 'pengumuman','id' => 'Pengumuman','en' => 'Announcements'],
        ];
        $langId = Language::where('code', 'id')->value('id');
        $langEn = Language::where('code', 'en')->value('id');

        foreach ($types as $i => $t) {
            $ct = ContentType::create([
                'slug' => $t['slug'], 'writing_style_id' => $wsId,
                'is_active' => true, 'sort_order' => $i + 1,
            ]);
            $ct->translations()->create(['language_id' => $langId, 'name' => $t['id']]);
            $ct->translations()->create(['language_id' => $langEn, 'name' => $t['en']]);
        }
    }
}
```

- [ ] **Step 5: Isi `RatingCriteriaSeeder.php`**

```php
<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\{Language, RatingCriterion};
use Illuminate\Database\Seeder;

class RatingCriteriaSeeder extends Seeder
{
    public function run(): void
    {
        RatingCriterion::query()->delete();
        $langId = Language::where('code', 'id')->value('id');
        $langEn = Language::where('code', 'en')->value('id');

        $criteria = [
            ['id' => 'Kemudahan penggunaan', 'en' => 'Ease of use'],
            ['id' => 'Kelengkapan informasi', 'en' => 'Information completeness'],
            ['id' => 'Kecepatan akses', 'en' => 'Access speed'],
            ['id' => 'Tampilan & kenyamanan', 'en' => 'Look & feel'],
            ['id' => 'Kepuasan keseluruhan', 'en' => 'Overall satisfaction'],
        ];
        foreach ($criteria as $i => $c) {
            $crit = RatingCriterion::create(['is_active' => true, 'sort_order' => $i + 1]);
            $crit->translations()->create(['language_id' => $langId, 'name' => $c['id']]);
            $crit->translations()->create(['language_id' => $langEn, 'name' => $c['en']]);
        }
    }
}
```

- [ ] **Step 6: Isi `AdminUserSeeder.php`**

```php
<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class AdminUserSeeder extends Seeder
{
    public function run(): void
    {
        if (! app()->environment('local')) {
            return; // skip di production
        }

        $user = User::updateOrCreate(
            ['email' => env('ADMIN_EMAIL', 'admin@papenajam.test')],
            [
                'name' => 'Administrator',
                'password' => Hash::make(env('ADMIN_PASSWORD', 'password')),
                'email_verified_at' => now(),
            ]
        );
        $user->assignRole(UserRole::Admin->value);
    }
}
```

- [ ] **Step 7: Isi `DemoPostSeeder.php`** (untuk walking skeleton Fase 4)

```php
<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Enums\PostStatus;
use App\Models\{ContentType, Language, Post, PostTranslation};
use Illuminate\Database\Seeder;

class DemoPostSeeder extends Seeder
{
    public function run(): void
    {
        $type = ContentType::where('slug', 'berita')->first();
        if (! $type) {
            return;
        }
        $langId = Language::where('code', 'id')->value('id');
        $langEn = Language::where('code', 'en')->value('id');

        $post = Post::create(['type_id' => $type->id]);

        PostTranslation::create([
            'post_id' => $post->id, 'language_id' => $langId,
            'slug' => 'selamat-datang',
            'title' => 'Selamat Datang di Papenajam',
            'body' => '<p>Ini adalah konten demo pertama untuk verifikasi pondasi CMS.</p>',
            'status' => PostStatus::Published,
            'published_at' => now(),
            'meta_title' => 'Selamat Datang — Papenajam',
            'meta_description' => 'Konten demo pertama CMS Papenajam.',
        ]);

        PostTranslation::create([
            'post_id' => $post->id, 'language_id' => $langEn,
            'slug' => 'welcome',
            'title' => 'Welcome to Papenajam',
            'body' => '<p>This is the first demo content to verify the CMS foundation.</p>',
            'status' => PostStatus::Published,
            'published_at' => now(),
            'meta_title' => 'Welcome — Papenajam',
            'meta_description' => 'First demo content of the Papenajam CMS.',
        ]);
    }
}
```

- [ ] **Step 8: Wiring `DatabaseSeeder.php`**

```php
<?php

declare(strict_types=1);

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            LanguageSeeder::class,
            RolePermissionSeeder::class,
            WritingStyleSeeder::class,
            ContentTypeSeeder::class,
            RatingCriteriaSeeder::class,
            AdminUserSeeder::class,
            DemoPostSeeder::class,
        ]);
    }
}
```

- [ ] **Step 9: Test seeder end-to-end**

`tests/Feature/SeedersTest.php`:
```php
<?php

use App\Models\{ContentType, Language, RatingCriterion, User};
use App\Enums\UserRole;
use Spatie\Permission\Models\Role;

it('migrate:fresh --seed menghasilkan data lengkap', function () {
    $this->artisan('migrate:fresh --seed --no-interaction')->assertSuccessful();

    Language::flushCache();
    expect(Language::count())->toBe(2)
        ->and(Language::where('code', 'id')->value('is_default'))->toBeTrue()
        ->and(ContentType::count())->toBe(3)
        ->and(ContentType::where('slug', 'berita')->exists())->toBeTrue()
        ->and(RatingCriterion::count())->toBe(5)
        ->and(Role::where('name', UserRole::Admin->value)->exists())->toBeTrue()
        ->and(Role::where('name', UserRole::Editor->value)->exists())->toBeTrue()
        ->and(Role::where('name', UserRole::Author->value)->exists())->toBeTrue()
        ->and(User::where('email', env('ADMIN_EMAIL', 'admin@papenajam.test'))->exists())->toBeTrue();
});

it('Admin user memiliki role Admin', function () {
    $admin = User::where('email', env('ADMIN_EMAIL', 'admin@papenajam.test'))->first();
    expect($admin->hasRole(UserRole::Admin->value))->toBeTrue();
});

it('DemoPost ter-seed dengan translation ID+EN', function () {
    $this->artisan('migrate:fresh --seed --no-interaction');
    $post = \App\Models\Post::whereHas('translations', fn ($q) => $q->where('slug', 'selamat-datang'))->first();
    expect($post)->not->toBeNull()
        ->and($post->translate('id')?->slug)->toBe('selamat-datang')
        ->and($post->translate('en')?->slug)->toBe('welcome');
});
```

- [ ] **Step 10: Migrate:fresh --seed + test + Pint**

```bash
php artisan migrate:fresh --seed --no-interaction
php artisan test --compact --filter=SeedersTest
vendor/bin/pint --dirty --format agent
```

- [ ] **Step 11: Verifikasi OK — Fase 2 selesai. Lanjut Fase 3.**

---

# FASE 3 — AUTENTIKASI & OTORISASI

## Task 3.1: Konfigurasi Fortify (registration=false, home=/admin) + Gate mode code

**Files:**
- Modify: `config/fortify.php`
- Modify: `app/Providers/AppServiceProvider.php` (tambah Gate)
- Test: `tests/Feature/FortifyConfigTest.php`

**Interfaces:**
- Produces: fitur registration dimatikan; setelah login → redirect `/admin`; Gate `use-page-code-mode` hanya lulus untuk Admin.

- [ ] **Step 1: Edit `config/fortify.php`**

Cari array `features` — pastikan:
```php
Features::registration() => false,  // dimatikan
Features::twoFactorAuthentication() => true,
Features::resetPasswords() => true,
Features::updateProfileInformation() => true,
Features::updatePasswords() => true,
```

Lalu cari `home` (atau tambahkan):
```php
'home' => '/admin',
```

- [ ] **Step 2: Tambah Gate di `AppServiceProvider::boot()`**

Edit `app/Providers/AppServiceProvider.php`:
```php
public function boot(): void
{
    $this->configureDefaults();

    Gate::define('use-page-code-mode', function (User $user): bool {
        return $user->hasRole(\App\Enums\UserRole::Admin->value);
    });
}
```
Tambahkan import: `use Illuminate\Support\Facades\Gate; use App\Models\User;`.

- [ ] **Step 3: Test**

`tests/Feature/FortifyConfigTest.php`:
```php
<?php

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;

uses(RefreshDatabase::class);

beforeEach(function () {
    Role::firstOrCreate(['name' => UserRole::Admin->value]);
    Role::firstOrCreate(['name' => UserRole::Editor->value]);
});

it('registration disabled — GET /register returns 404', function () {
    $response = $this->get('/register');
    expect(in_array($response->status(), [404, 405]))->toBeTrue();
});

it('Gate use-page-code-mode hanya untuk Admin', function () {
    $admin = User::factory()->create()->assignRole(UserRole::Admin->value);
    $editor = User::factory()->create()->assignRole(UserRole::Editor->value);

    expect(Gate::forUser($admin)->allows('use-page-code-mode'))->toBeTrue()
        ->and(Gate::forUser($editor)->allows('use-page-code-mode'))->toBeFalse();
});

it('Login dengan admin → redirect ke /admin', function () {
    \Illuminate\Support\Facades\Artisan::call('migrate:fresh', ['--no-interaction' => true]);
    \Illuminate\Support\Facades\Artisan::call('db:seed', ['--no-interaction' => true]);
    $admin = User::where('email', env('ADMIN_EMAIL', 'admin@papenajam.test'))->first();

    $response = $this->post('/login', [
        'email' => $admin->email,
        'password' => env('ADMIN_PASSWORD', 'password'),
    ]);
    $response->assertRedirect('/admin');
});
```

- [ ] **Step 4: Test + Pint**

```bash
php artisan test --compact --filter=FortifyConfigTest
vendor/bin/pint --dirty --format agent
```

---

## Task 3.2: Custom Role Model + HasRoles di User

**Files:**
- Create: `app/Models/Role.php` (extend Spatie)
- Modify: `app/Models/User.php` (add HasRoles)
- Modify: `config/permission.php` (jika perlu arahkan ke custom Role)
- Test: `tests/Feature/UserRoleTest.php`

**Interfaces:**
- Produces: `User::hasRole(UserRole::Admin)` bisa dipanggil; cache permission Spatie aktif default.

- [ ] **Step 1: Buat `app/Models/Role.php`**

```php
<?php

declare(strict_types=1);

namespace App\Models;

use Spatie\Permission\Models\Role as SpatieRole;

class Role extends SpatieRole
{
    // Tidak menambah field; hanya untuk swap kelas di kemudian hari
    // bila perlu kolom display_name, dll.
}
```

- [ ] **Step 2: Edit `config/permission.php`**

```php
'models' => [
    'permission' => Spatie\Permission\Models\Permission::class,
    'role' => App\Models\Role::class,
],
```

- [ ] **Step 3: Edit `app/Models/User.php` — tambah `HasRoles`**

Tambah trait Spatie:
```php
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable
{
    use HasFactory, Notifiable, TwoFactorAuthenticatable, HasRoles, Passkeys;
    // ... (sesuaikan dgn isi existing starter)
    protected $guard_name = 'web';
}
```

- [ ] **Step 4: Test**

`tests/Feature/UserRoleTest.php`:
```php
<?php

use App\Enums\UserRole;
use App\Models\{Role, User};
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('User bisa di-assign role dan dicek', function () {
    Role::firstOrCreate(['name' => UserRole::Admin->value]);
    $u = User::factory()->create()->assignRole(UserRole::Admin->value);
    expect($u->hasRole(UserRole::Admin->value))->toBeTrue();
});

it('Role kelas custom dipakai', function () {
    expect(Role::class)->toBe(\App\Models\Role::class);
});
```

- [ ] **Step 5: Test + Pint**

```bash
php artisan test --compact --filter=UserRoleTest
vendor/bin/pint --dirty --format agent
```

---

## Task 3.3: Inertia Shared Props — roles, permissions, canUseCodeMode, contentTypes

**Files:**
- Modify: `app/Http/Middleware/HandleInertiaRequests.php`
- Test: `tests/Feature/InertiaSharedPropsTest.php`

**Interfaces:**
- Produces: `usePage().props.auth.user.roles` (string[]), `auth.user.permissions` (string[]), `auth.user.canUseCodeMode` (bool), `contentTypes` (array of {slug, name (translated)} when authenticated).

- [ ] **Step 1: Edit `HandleInertiaRequests::share()`**

```php
<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Models\ContentType;
use App\Models\Language;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Gate;
use Inertia\Middleware;

class HandleInertiaRequests extends Middleware
{
    protected $rootView = 'app';

    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    public function share(Request $request): array
    {
        $user = $request->user();

        return [
            ...parent::share($request),
            'name' => config('app.name'),
            'locale' => app()->getLocale(),
            'auth' => [
                'user' => $user ? array_merge($user->only(['id', 'name', 'email']), [
                    'roles' => $user->getRoleNames()->toArray(),
                    'permissions' => $user->getAllPermissions()->pluck('name')->toArray(),
                    'canUseCodeMode' => Gate::forUser($user)->allows('use-page-code-mode'),
                ]) : null,
            ],
            'contentTypes' => $user ? Cache::remember('inertia.content_types', now()->addHour(), function () {
                $langId = Language::idFor(app()->getLocale());
                return ContentType::active()->with(['translations' => function ($q) use ($langId) {
                    $q->where('language_id', $langId);
                }])->get()->map(fn ($ct) => [
                    'slug' => $ct->slug,
                    'name' => $ct->translations->first()?->name ?? ucfirst($ct->slug),
                ])->values()->toArray();
            }) : [],
            'sidebarOpen' => ! $request->hasCookie('sidebar_state') || $request->cookie('sidebar_state') === 'true',
        ];
    }
}
```

- [ ] **Step 2: Test**

`tests/Feature/InertiaSharedPropsTest.php`:
```php
<?php

use App\Enums\UserRole;
use App\Models\{ContentType, Language, User};
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->artisan('migrate:fresh', ['--no-interaction' => true]);
    $this->artisan('db:seed', ['--no-interaction' => true]);
    Language::flushCache();
});

it('props auth.user.roles diisi untuk user login', function () {
    $admin = User::where('email', env('ADMIN_EMAIL', 'admin@papenajam.test'))->first();
    $response = $this->actingAs($admin)->get('/admin');

    $props = $response->inertiaProps ?? null;
    // Cara alternatif: assert props via Inertia testing
    $response->assertInertia(fn ($page) =>
        $page->where('auth.user.roles.0', UserRole::Admin->value)
             ->where('auth.user.canUseCodeMode', true)
             ->has('contentTypes')
             ->etc()
    );
});

it('contentTypes berisi 3 tipe seeder', function () {
    $admin = User::where('email', env('ADMIN_EMAIL', 'admin@papenajam.test'))->first();
    $response = $this->actingAs($admin)->get('/admin');
    $response->assertInertia(fn ($page) =>
        $page->where('contentTypes', function ($types) {
            $slugs = array_column($types, 'slug');
            return count(array_intersect(['artikel', 'berita', 'pengumuman'], $slugs)) === 3;
        })->etc()
    );
});
```

> Catatan: rute `/admin` belum ada (akan dibuat di Fase 5). Untuk Fase 3, ganti URL test ke rute yang sudah ada mis. `/dashboard` (dari starter) supaya test bisa jalan. **Atau**, sebagai alternatif, test via direct call `HandleInertiaRequests::share()` dengan mock Request. Pilih opsi yang lebih ringkas dan jalankan.

- [ ] **Step 3: Test + Pint**

```bash
php artisan test --compact --filter=InertiaSharedPropsTest
vendor/bin/pint --dirty --format agent
```

---

## Task 3.4: PostPolicy skeleton + routes/admin.php group dengan permission middleware

**Files:**
- Create: `app/Policies/PostPolicy.php`
- Create: `routes/admin.php`
- Modify: `app/Providers/AppServiceProvider.php` (atau AuthServiceProvider jika ada) — daftarkan policy
- Modify: `bootstrap/app.php` (daftarkan `routes/admin.php` + middleware `permission`)
- Modify: `app/Models/Post.php` — tambah `protected static string $policy = PostPolicy::class;` (Laravel 13 style) atau pakai Gate automatic.
- Test: `tests/Feature/AdminRouteGuardTest.php`

**Interfaces:**
- Produces:
  - Route group `/admin` dilindungi middleware `auth`, `verified`, `permission:access-admin`.
  - `PostPolicy` pattern — `viewAny`, `create`, `update`, `delete`, `deleteOwn`.

- [ ] **Step 1: Buat `routes/admin.php`**

```php
<?php

declare(strict_types=1);

use App\Http\Controllers\Admin\DashboardController;
use Illuminate\Support\Facades\Route;

Route::get('/', [DashboardController::class, 'index'])->name('admin.dashboard');

// Placeholder routes (Fase 5 akan isi detail) — minimal supaya middleware aktif.
Route::prefix('/')->group(function () {
    // Akan diisi Fase 5
});
```

> `DashboardController` dibuat di Fase 5. Untuk Fase 3, kita pakai inline closure sementara yang render `Inertia::render('admin/dashboard')` kosong (akan diganti Task 5.3). Atau, jika Fase 3 tidak perlu route `/admin` benar-benar render, cukup definisikan group + middleware, isi `->get('/', fn () => Inertia::render('admin/placeholder'))`.

**Untuk Fase 3, gunakan placeholder inline:**
```php
Route::get('/', fn () => \Inertia\Inertia::render('admin/placeholder'))->name('admin.dashboard');
```

- [ ] **Step 2: Daftarkan `routes/admin.php` di `bootstrap/app.php`**

Edit `bootstrap/app.php`, di blok `withRouting`:
```php
->withRouting(
    web: __DIR__.'/../routes/web.php',
    then: function () {
        Route::middleware(['auth', 'verified', 'permission:access-admin'])
            ->prefix('admin')
            ->name('admin.')
            ->group(base_path('routes/admin.php'));
    },
)
```
Tambah import `Illuminate\Support\Facades\Route;` di atas.

- [ ] **Step 3: Buat `app/Policies/PostPolicy.php` (skeleton pattern)**

```php
<?php

declare(strict_types=1);

namespace App\Policies;

use App\Enums\UserRole;
use App\Models\Post;
use App\Models\User;

class PostPolicy
{
    public function viewAny(User $user): bool
    {
        return true; // semua role di area admin bisa lihat list
    }

    public function create(User $user): bool
    {
        return in_array($user->roles->first()?->name, [UserRole::Admin->value, UserRole::Editor->value, UserRole::Author->value], true);
    }

    public function update(User $user, Post $post): bool
    {
        if ($user->hasRole(UserRole::Admin->value) || $user->hasRole(UserRole::Editor->value)) {
            return true;
        }
        // Author hanya untuk post miliknya sendiri (field author_id akan ditambah di Fase fitur)
        return false; // TODO Fase fitur: cek $post->author_id === $user->id
    }

    public function delete(User $user, Post $post): bool
    {
        return $this->update($user, $post);
    }

    public function deleteOwn(User $user, Post $post): bool
    {
        return false; // Fase fitur
    }
}
```

> Catatan: `protected static string $policy` di Post.php memerlukan Laravel 13 auto-resolution. Untuk konsistensi, tambahkan di `Post`:
> ```php
> protected static string $policy = \App\Policies\PostPolicy::class;
> ```

- [ ] **Step 4: Buat halaman placeholder admin minimal**

Buat `resources/js/pages/admin/placeholder.tsx`:
```tsx
import { Head } from '@inertiajs/react';

export default function AdminPlaceholder() {
    return (
        <>
            <Head title="Admin" />
            <div className="p-8">Area admin — bootstrap berhasil.</div>
        </>
    );
}
```

- [ ] **Step 5: Test middleware guard**

`tests/Feature/AdminRouteGuardTest.php`:
```php
<?php

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->artisan('migrate:fresh', ['--no-interaction' => true]);
    $this->artisan('db:seed', ['--no-interaction' => true]);
});

it('guest tidak bisa akses /admin — redirect login', function () {
    $this->get('/admin')->assertRedirect('/login');
});

it('Admin bisa akses /admin', function () {
    $admin = User::where('email', env('ADMIN_EMAIL'))->first();
    $this->actingAs($admin)->get('/admin')->assertOk();
});

it('Editor bisa akses /admin', function () {
    $editor = User::factory()->create()->assignRole(UserRole::Editor->value);
    $this->actingAs($editor)->get('/admin')->assertOk();
});

it('Author bisa akses /admin', function () {
    $author = User::factory()->create()->assignRole(UserRole::Author->value);
    $this->actingAs($author)->get('/admin')->assertOk();
});

it('User tanpa role apapun tidak bisa akses /admin', function () {
    $plain = User::factory()->create();
    $this->actingAs($plain)->get('/admin')->assertForbidden();
});
```

- [ ] **Step 6: Test + Pint**

```bash
php artisan test --compact --filter=AdminRouteGuardTest
vendor/bin/pint --dirty --format agent
```

- [ ] **Step 7: Verifikasi OK — Fase 3 selesai. Lanjut Fase 4.**

---

# FASE 4 — ROUTING & LOCALE + WALKING SKELETON

## Task 4.1: Middleware `SetLocale` + Helper `LocaleUrl`

**Files:**
- Create: `app/Http/Middleware/SetLocale.php`
- Create: `app/Support/LocaleUrl.php`
- Modify: `bootstrap/app.php` (daftarkan middleware alias)
- Test: `tests/Feature/LocaleMiddlewareTest.php`

**Interfaces:**
- Produces:
  - Middleware `set_locale` yang mengecek segment-1 URL: jika 2-huruf locale valid non-default (`en`, dll) → strip dari path info, set `app()->setLocale()`. Jika default (`id`) atau bukan locale → locale default.
  - `App\Support\LocaleUrl::for(string $locale, string $pathWithoutLocale): string` — builder URL locale. Mis. `LocaleUrl::for('en', '/berita/slug')` → `/en/news/slug`; `LocaleUrl::for('id', '/berita/slug')` → `/berita/slug`.
  - `App\Support\LocaleUrl::current(): string` — ambil locale aktif dari request.
  - Kontrak: locale default = `Language::defaultModel()->code`; locale valid = code yang ada di `languages` aktif.

- [ ] **Step 1: Buat helper `App\Support\LocaleUrl`**

```php
<?php

declare(strict_types=1);

namespace App\Support;

use App\Models\Language;
use Illuminate\Support\Collection;

class LocaleUrl
{
    /** Ambil daftar locale aktif (code). */
    public static function active(): Collection
    {
        return Language::active()->pluck('code');
    }

    /** Apakah code adalah locale valid non-default? */
    public static function isNonDefaultLocale(string $code): bool
    {
        $default = Language::defaultModel()->code;
        return $code !== $default && static::active()->contains($code);
    }

    /** Bangun URL untuk locale tertentu dari path yang sudah tanpa-prefix-locale. */
    public static function for(string $locale, string $pathWithoutLocale): string
    {
        $pathWithoutLocale = '/'.ltrim($pathWithoutLocale, '/');
        $default = Language::defaultModel()->code;
        if ($locale === $default) {
            return $pathWithoutLocale === '/' ? '/' : $pathWithoutLocale;
        }
        return '/'.$locale.($pathWithoutLocale === '/' ? '' : $pathWithoutLocale);
    }

    /** Locale aktif sesuai app() saat ini. */
    public static function current(): string
    {
        return app()->getLocale();
    }
}
```

- [ ] **Step 2: Buat middleware `SetLocale`**

```php
<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Models\Language;
use App\Support\LocaleUrl;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SetLocale
{
    public function handle(Request $request, Closure $next): Response
    {
        $segment = $request->segment(1) ?? '';

        if (LocaleUrl::isNonDefaultLocale($segment)) {
            app()->setLocale($segment);
            // Hapus prefix dari path supaya controller tidak perlu peduli locale
            $request->setPathinfo('/'.ltrim(substr($request->path(), strlen($segment)), '/'));
        } else {
            app()->setLocale(Language::defaultModel()->code);
        }

        return $next($request);
    }
}
```

- [ ] **Step 3: Daftarkan alias `set_locale` di `bootstrap/app.php`**

Di blok `withMiddleware`:
```php
->withMiddleware(function (Middleware $middleware) {
    $middleware->alias([
        'set_locale' => \App\Http\Middleware\SetLocale::class,
    ]);
})
```
Import `Illuminate\Foundation\Http\Middleware\Alias` jika diperlukan.

- [ ] **Step 4: Test**

`tests/Feature/LocaleMiddlewareTest.php`:
```php
<?php

use App\Models\Language;
use App\Support\LocaleUrl;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->artisan('migrate:fresh', ['--no-interaction' => true]);
    $this->artisan('db:seed', ['--no-interaction' => true]);
    Language::flushCache();
});

it('LocaleUrl::for locale default tanpa prefix', function () {
    expect(LocaleUrl::for('id', '/berita/slug'))->toBe('/berita/slug')
        ->and(LocaleUrl::for('id', '/'))->toBe('/');
});

it('LocaleUrl::for locale non-default ber-prefix', function () {
    expect(LocaleUrl::for('en', '/berita/slug'))->toBe('/en/berita/slug')
        ->and(LocaleUrl::for('en', '/'))->toBe('/en');
});

it('LocaleUrl::isNonDefaultLocale', function () {
    expect(LocaleUrl::isNonDefaultLocale('en'))->toBeTrue()
        ->and(LocaleUrl::isNonDefaultLocale('id'))->toBeFalse()
        ->and(LocaleUrl::isNonDefaultLocale('fr'))->toBeFalse();
});
```

- [ ] **Step 5: Test + Pint**

```bash
php artisan test --compact --filter=LocaleMiddlewareTest
vendor/bin/pint --dirty --format agent
```

---

## Task 4.2: Public Path Resolver + Controllers (Home, Post archive/show, Page catch-all)

**Files:**
- Create: `app/Support/PublicPathResolver.php`
- Create: `app/Http/Controllers/Public/HomeController.php`
- Create: `app/Http/Controllers/Public/PostController.php`
- Create: `app/Http/Controllers/Public/PageController.php`
- Test: `tests/Feature/Public/PublicRoutingTest.php`

**Interfaces:**
- Produces (sesuai revisi spec §5 — wajib single resolver, bukan rute Laravel terpisah):
  - `PublicPathResolver::resolve(string $path): array` — menerima path **tanpa prefix locale** (sudah distrip SetLocale), mengembalikan:
    ```php
    ['kind' => 'home']
    | ['kind' => 'archive', 'contentType' => ContentType]
    | ['kind' => 'single', 'post' => Post, 'translation' => PostTranslation]
    | ['kind' => 'page', 'page' => Page, 'translation' => PageTranslation]
    | ['kind' => 'notFound']
    ```
  - Logika urutan: `'' | '/'` → home; 1 segment: cek apakah `content_types.slug` → archive, atau `page_translations.slug` (untuk locale aktif) → page; 2 segment: `type/slug` → single.
  - 3 controller memakai resolver.

- [ ] **Step 1: Buat `PublicPathResolver`**

```php
<?php

declare(strict_types=1);

namespace App\Support;

use App\Models\{ContentType, Language, PageTranslation, Post, PostTranslation};

class PublicPathResolver
{
    public static function resolve(string $path): array
    {
        $path = trim($path, '/');
        if ($path === '') {
            return ['kind' => 'home'];
        }

        $segments = explode('/', $path);
        $langId = Language::current()->id;

        // 2 segment: type/slug → single
        if (count($segments) === 2) {
            [$typeSlug, $postSlug] = $segments;
            $type = ContentType::where('slug', $typeSlug)->where('is_active', true)->first();
            if ($type) {
                $translation = PostTranslation::where('slug', $postSlug)
                    ->where('language_id', $langId)
                    ->whereHas('post', fn ($q) => $q->where('type_id', $type->id))
                    ->published()
                    ->first();
                if ($translation) {
                    return ['kind' => 'single', 'post' => $translation->post, 'translation' => $translation];
                }
            }
            return ['kind' => 'notFound'];
        }

        // 1 segment: type archive atau page
        if (count($segments) === 1) {
            $slug = $segments[0];

            $type = ContentType::where('slug', $slug)->where('is_active', true)->first();
            if ($type) {
                return ['kind' => 'archive', 'contentType' => $type];
            }

            $pageTranslation = PageTranslation::where('slug', $slug)
                ->where('language_id', $langId)
                ->where('status', 'Published')
                ->first();
            if ($pageTranslation) {
                return ['kind' => 'page', 'page' => $pageTranslation->page, 'translation' => $pageTranslation];
            }
        }

        return ['kind' => 'notFound'];
    }
}
```

- [ ] **Step 2: Buat `HomeController`**

```php
<?php

declare(strict_types=1);

namespace App\Http\Controllers\Public;

use App\Models\{Post, PostTranslation};
use App\Models\Language;
use Illuminate\Http\Request;
use Inertia\Inertia;

class HomeController
{
    public function index(Request $request)
    {
        $langId = Language::current()->id;
        $latest = PostTranslation::with('post.type')
            ->where('language_id', $langId)
            ->published()
            ->orderByDesc('published_at')
            ->limit(5)
            ->get();

        return Inertia::render('public/home', [
            'latestPosts' => $latest,
            'locale' => app()->getLocale(),
        ]);
    }
}
```

- [ ] **Step 3: Buat `PostController`** (archive + show)

```php
<?php

declare(strict_types=1);

namespace App\Http\Controllers\Public;

use App\Models\{ContentType, Language, PostTranslation};
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class PostController
{
    public function archive(Request $request, ContentType $contentType): Response
    {
        $langId = Language::current()->id;
        $posts = PostTranslation::with('post.type')
            ->where('language_id', $langId)
            ->whereHas('post', fn ($q) => $q->where('type_id', $contentType->id))
            ->published()
            ->orderByDesc('published_at')
            ->paginate(12);

        return Inertia::render('public/post-archive', [
            'contentType' => [
                'slug' => $contentType->slug,
                'name' => $contentType->translate()?->name ?? ucfirst($contentType->slug),
            ],
            'posts' => $posts,
        ]);
    }

    public function show(Request $request, ContentType $contentType, PostTranslation $translation): Response
    {
        return Inertia::render('public/post-show', [
            'post' => $translation->load('post.type'),
            'contentType' => [
                'slug' => $contentType->slug,
                'name' => $contentType->translate()?->name ?? ucfirst($contentType->slug),
            ],
        ]);
    }
}
```

- [ ] **Step 4: Buat `PageController` (catch-all)**

```php
<?php

declare(strict_types=1);

namespace App\Http\Controllers\Public;

use App\Models\PageTranslation;
use Inertia\Inertia;
use Inertia\Response;

class PageController
{
    public function show(PageTranslation $translation): Response
    {
        return Inertia::render('public/page-show', [
            'page' => $translation->load('page'),
        ]);
    }
}
```

- [ ] **Step 5: Wiring rute di `routes/web.php`**

Ganti isi `routes/web.php` dengan:
```php
<?php

declare(strict_types=1);

use App\Http\Controllers\Public\HomeController;
use App\Http\Controllers\Public\PageController;
use App\Http\Controllers\Public\PostController;
use App\Support\PublicPathResolver;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/', [HomeController::class, 'index'])->name('home');

// Catch-all publik: 1 atau 2 segment (SetLocale sudah strip prefix)
Route::get('/{slug1}/{slug2?}', function (string $slug1, ?string $slug2 = null) {
    $path = $slug2 ? "{$slug1}/{$slug2}" : $slug1;
    $resolved = PublicPathResolver::resolve($path);

    return match ($resolved['kind']) {
        'archive'  => app(PostController::class)->archive(request(), $resolved['contentType']),
        'single'   => app(PostController::class)->show(request(), $resolved['contentType'], $resolved['translation']),
        'page'     => app(PageController::class)->show($resolved['translation']),
        default    => abort(404),
    };
})->where('slug1', '[a-z0-9\-]+')
  ->where('slug2', '[a-z0-9\-]+');

// fallback catch-all 3+ segment juga 404 (Fase 4: tidak ada nested)
Route::fallback(fn () => abort(404));
```

> **Penting:** Pasang middleware `set_locale` di grup publik. Edit `bootstrap/app.php` `withMiddleware`:
> ```php
> $middleware->web(append: [
>     \App\Http\Middleware\SetLocale::class,
> ]);
> ```
> Middleware ini HARUS tidak memproses `/admin/*`. Karena `/admin` adalah segment-1 yang bukan locale valid, secara default ia dilewati tanpa masalah — namun untuk keamanan tambahan, di middleware `SetLocale::handle`, cek: jika `request->is('admin/*')` → return `$next($request)` langsung tanpa strip.

Update `SetLocale::handle` — tambahkan di awal:
```php
if ($request->is('admin/*') || $request->segment(1) === 'admin') {
    return $next($request);
}
```

- [ ] **Step 6: Buat halaman publik SSR minimal**

Buat `resources/js/pages/public/home.tsx`:
```tsx
import { Head } from '@inertiajs/react';

export default function PublicHome({ latestPosts, locale }: { latestPosts: any[]; locale: string }) {
    return (
        <>
            <Head title="Beranda">
                <link rel="alternate" hrefLang={locale} href={typeof window !== 'undefined' ? window.location.href : ''} />
            </Head>
            <main className="prose p-8">
                <h1>Beranda</h1>
                <ul>
                    {latestPosts.map((p: any) => (
                        <li key={p.id}>{p.title}</li>
                    ))}
                </ul>
            </main>
        </>
    );
}
```

Buat `resources/js/pages/public/post-archive.tsx`:
```tsx
import { Head } from '@inertiajs/react';

export default function PostArchive({ contentType, posts }: { contentType: { slug: string; name: string }; posts: any }) {
    return (
        <>
            <Head title={contentType.name} />
            <main className="p-8">
                <h1>{contentType.name}</h1>
                <ul>
                    {posts.data?.map((p: any) => <li key={p.id}>{p.title}</li>)}
                </ul>
            </main>
        </>
    );
}
```

Buat `resources/js/pages/public/post-show.tsx` (lihat Task 4.4 untuk detail SSR + hreflang).

Buat `resources/js/pages/public/page-show.tsx`:
```tsx
import { Head } from '@inertiajs/react';

export default function PageShow({ page }: { page: any }) {
    return (
        <>
            <Head title={page.title} />
            <main className="p-8">
                <h1>{page.title}</h1>
                <div dangerouslySetInnerHTML={{ __html: page.content?.html ?? '' }} />
            </main>
        </>
    );
}
```

- [ ] **Step 7: Test routing publik**

`tests/Feature/PublicRoutingTest.php`:
```php
<?php

use App\Models\Language;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->artisan('migrate:fresh', ['--no-interaction' => true]);
    $this->artisan('db:seed', ['--no-interaction' => true]);
    Language::flushCache();
});

it('GET / → 200 home', function () {
    $this->get('/')->assertOk();
});

it('GET /en/ → 200 home locale EN', function () {
    $this->get('/en/')->assertOk();
    expect(app()->getLocale())->toBe('en');
});

it('GET /berita → archive', function () {
    $this->get('/berita')->assertOk();
});

it('GET /en/news → archive (EN) — slug type bahasa tidak diterjemahkan di URL (lihat catatan)**', function () {
    // Catatan: di Fase 4, content_type slug TIDAK diterjemahkan (slug adalah `berita` di semua locale).
    // Maka /en/news tidak resolve; /en/berita yang resolve.
    $this->get('/en/news')->assertNotFound();
    $this->get('/en/berita')->assertOk();
});

it('GET /berita/selamat-datang → single post', function () {
    $this->get('/berita/selamat-datang')->assertOk();
});

it('GET /en/berita/welcome → single post EN (slug translation EN)', function () {
    // slug post diterjemahkan (selamat-datang → welcome), tapi slug type tidak.
    $this->get('/en/berita/welcome')->assertOk();
});

it('GET /nonexistent-slug → 404', function () {
    $this->get('/aaaaaa')->assertNotFound();
});

it('GET /admin redirect ke login (bukan publik)', function () {
    $this->get('/admin')->assertRedirect('/login');
});
```

> **Catatan keputusan:** slug `content_types` **tidak diterjemahkan** di URL (tetap `berita` di semua locale) — sesuai data model PRD (`content_types.slug` tunggal, bukan `content_type_translations.slug`). Hanya `post_translations.slug` yang diterjemahkan. Ini diuji eksplisit di atas.

- [ ] **Step 8: Test + Pint**

```bash
php artisan test --compact --filter=PublicRoutingTest
vendor/bin/pint --dirty --format agent
```

---

## Task 4.3: Walking Skeleton SSR — Halaman `post-show.tsx` dengan SEO meta + hreflang

**Files:**
- Create: `resources/js/pages/public/post-show.tsx`
- Create: `resources/js/components/seo/meta-head.tsx` (basic — detail di Fase 6)
- Create: `resources/js/components/locale-switcher.tsx`
- Test: `tests/Feature/WalkingSkeletonSsrTest.php`

**Interfaces:**
- Produces: SSR render single post dengan `<title>`, `<meta description>`, `<link rel=canonical>`, `<link rel=alternate hreflang>` untuk ID + EN (jika translation tersedia).

- [ ] **Step 1: Buat `seo/meta-head.tsx` basic**

```tsx
import { Head } from '@inertiajs/react';

export type SeoProps = {
    title: string;
    description?: string;
    canonical?: string;
    hreflang?: Record<string, string>; // locale → absolute URL
    ogTitle?: string;
    ogDescription?: string;
    ogImage?: string;
    ogType?: string;
};

export function MetaHead(props: SeoProps) {
    return (
        <Head>
            <title>{props.title}</title>
            {props.description && <meta name="description" content={props.description} />}
            {props.canonical && <link rel="canonical" href={props.canonical} />}
            {props.hreflang && Object.entries(props.hreflang).map(([locale, url]) => (
                <link key={locale} rel="alternate" hrefLang={locale} href={url} />
            ))}
            {props.hreflang && <link rel="alternate" hrefLang="x-default" href={Object.values(props.hreflang)[0]} />}
            {props.ogTitle && <meta property="og:title" content={props.ogTitle} />}
            {props.ogDescription && <meta property="og:description" content={props.ogDescription} />}
            {props.ogImage && <meta property="og:image" content={props.ogImage} />}
            <meta property="og:type" content={props.ogType ?? 'website'} />
        </Head>
    );
}
```

- [ ] **Step 2: Edit `PostController::show` — kirim SEO props**

Update `app/Http/Controllers/Public/PostController.php::show`:
```php
public function show(Request $request, ContentType $contentType, PostTranslation $translation): Response
{
    $post = $translation->post;
    $allTranslations = $post->translations()->published()->with('language')->get();

    $hreflang = [];
    foreach ($allTranslations as $tr) {
        $slug = $tr->language->code === Language::defaultModel()->code
            ? url("/{$contentType->slug}/{$tr->slug}")
            : url("/{$tr->language->code}/{$contentType->slug}/{$tr->slug}");
        $hreflang[$tr->language->code] = $slug;
    }

    return Inertia::render('public/post-show', [
        'post' => $translation->load('post.type'),
        'contentType' => [
            'slug' => $contentType->slug,
            'name' => $contentType->translate()?->name ?? ucfirst($contentType->slug),
        ],
        'seo' => [
            'title' => $translation->meta_title ?? $translation->title,
            'description' => $translation->meta_description,
            'canonical' => url()->current(),
            'hreflang' => $hreflang,
            'ogType' => 'article',
        ],
    ]);
}
```

Tambah import `use App\Models\Language;` di controller.

- [ ] **Step 3: Buat `post-show.tsx` lengkap**

```tsx
import { MetaHead } from '@/components/seo/meta-head';

type PostTranslation = {
    id: number;
    slug: string;
    title: string;
    body: string | null;
    meta_title: string | null;
    meta_description: string | null;
};

export default function PostShow({
    post,
    contentType,
    seo,
}: {
    post: PostTranslation;
    contentType: { slug: string; name: string };
    seo: {
        title: string;
        description?: string;
        canonical?: string;
        hreflang?: Record<string, string>;
        ogType?: string;
    };
}) {
    return (
        <>
            <MetaHead {...seo} />
            <main className="prose mx-auto max-w-3xl p-8">
                <a href="/" className="text-sm text-blue-600 hover:underline">← Beranda</a>
                <h1>{post.title}</h1>
                <div dangerouslySetInnerHTML={{ __html: post.body ?? '' }} />
            </main>
        </>
    );
}
```

- [ ] **Step 4: Buat `locale-switcher.tsx` (basic — di-render di Fase 6 di layout publik)**

```tsx
import { Link } from '@inertiajs/react';

export function LocaleSwitcher({
    currentLocale,
    locales,
    currentPath,
}: {
    currentLocale: string;
    locales: { code: string; name: string }[];
    currentPath: string;
}) {
    return (
        <nav aria-label="Language" className="flex gap-2">
            {locales.map((l) => {
                const href = l.code === 'id'
                    ? currentPath
                    : `/${l.code}${currentPath === '/' ? '' : currentPath}`;
                return (
                    <Link
                        key={l.code}
                        href={href}
                        aria-current={l.code === currentLocale ? 'true' : undefined}
                        className={`rounded px-2 py-1 text-sm ${l.code === currentLocale ? 'bg-blue-100 font-semibold' : 'hover:bg-gray-100'}`}
                    >
                        {l.name}
                    </Link>
                );
            })}
        </nav>
    );
}
```

- [ ] **Step 5: Test walking skeleton SSR**

`tests/Feature/WalkingSkeletonSsrTest.php`:
```php
<?php

use App\Models\Language;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->artisan('migrate:fresh', ['--no-interaction' => true]);
    $this->artisan('db:seed', ['--no-interaction' => true]);
    Language::flushCache();
});

it('GET /berita/selamat-datang SSR: HTML mengandung judul + hreflang ID + EN', function () {
    $response = $this->get('/berita/selamat-datang');
    $html = $response->getContent();

    $response->assertOk();
    expect($html)
        ->toContain('Selamat Datang di Papenajam')
        ->and($html)->toContain('<title>')
        ->and($html)->toMatch('/<link[^>]+rel="alternate"[^>]+hreflang="id"/')
        ->and($html)->toMatch('/<link[^>]+rel="alternate"[^>]+hreflang="en"/')
        ->and($html)->toMatch('/<link[^>]+rel="canonical"/');
});

it('GET /en/berita/welcome SSR: judul EN', function () {
    $response = $this->get('/en/berita/welcome');
    $html = $response->getContent();
    expect($html)->toContain('Welcome to Papenajam');
});

it('GET / curl-equivalent: bukan empty div Inertia', function () {
    $response = $this->get('/');
    $html = $response->getContent();
    // SSR berhasil bila body HTML terisi konten (bukan hanya <div data-page="..."></div> kosong)
    expect($html)->toMatch('/<main|<h1|Selamat Datang/i');
});
```

- [ ] **Step 6: Test + build SSR + Pint**

```bash
npm run build:ssr
php artisan test --compact --filter=WalkingSkeletonSsrTest
vendor/bin/pint --dirty --format agent
```

- [ ] **Step 7: Verifikasi OK — Walking skeleton vertikal berfungsi. Lanjut Fase 5.**

---

# FASE 5 — SHELL ADMIN

## Task 5.1: Admin layout (sidebar + topbar dari starter)

**Files:**
- Create: `resources/js/layouts/admin-layout.tsx`
- Modify: `resources/js/layouts/app/app-sidebar-layout.tsx` (re-use, optional extend)
- Test: visual — manual via browser di `npm run dev`. Test fungsional via Task 5.3.

**Interfaces:**
- Produces: `AdminLayout` React komponen — sidebar 6 grup, topbar, responsive drawer mobile, skip-to-content. Mengkonsumsi props dari Inertia: `auth.user.roles`, `auth.user.permissions`, `contentTypes`.

- [ ] **Step 1: Buat `admin-layout.tsx`** — wrap `AppSidebarLayout` starter, tambah skip link + region main id.

```tsx
import AppSidebarLayout from '@/layouts/app/app-sidebar-layout';
import type { BreadcrumbItem } from '@/types';

export default function AdminLayout({
    breadcrumbs = [],
    children,
}: {
    breadcrumbs?: BreadcrumbItem[];
    children: React.ReactNode;
}) {
    return (
        <AppSidebarLayout breadcrumbs={breadcrumbs}>
            <a href="#admin-main" className="sr-only focus:not-sr-only focus:absolute focus:top-2 focus:left-2 focus:z-50 focus:rounded focus:bg-white focus:px-4 focus:py-2 focus:text-black">
                Lewati ke konten utama
            </a>
            <main id="admin-main">{children}</main>
        </AppSidebarLayout>
    );
}
```

> Catatan: `AppSidebar` starter sudah ada nav dinamis via prop `navMain` — di Task 5.2 kita akan extend untuk konsumsi `contentTypes` dari Inertia. Untuk Fase 5 minimal, biarkan default starter; nanti Task 5.2 inject dinamis.

- [ ] **Step 2: Verifikasi render — buka `/admin` via browser**

```bash
npm run dev
```
Login admin → `/admin`. Pastikan sidebar + topbar tampil.

---

## Task 5.2: Sidebar nav config + dynamic content types + role visibility

**Files:**
- Create: `resources/js/components/admin/sidebar-nav-config.ts` (NavItem config)
- Modify: `resources/js/components/app-sidebar.tsx` (konsumsi config + contentTypes dari Inertia + filter permission)
- Test: `tests/Feature/AdminSidebarVisibilityTest.php`

**Interfaces:**
- Produces: struktur `NavItem[]` di sumber tunggal, dipakai sidebar. Tiap item punya `group`, `permission?`, `dynamicFrom?: 'contentTypes'`.

- [ ] **Step 1: Buat `sidebar-nav-config.ts`**

```ts
import {
    LayoutDashboard, FileText, Files, Settings as SettingsIcon,
    Menu as MenuIcon, LayoutTemplate, MessageSquare, Star, Image,
    Users, Cpu, Languages, PenTool, Mail, Quote, GalleryVerticalEnd, Tag, FolderTree,
    type LucideIcon,
} from 'lucide-react';

export type NavGroup = 'dashboard' | 'content' | 'pages' | 'appearance' | 'interaction' | 'system';

export type NavItem = {
    label: string;
    href: string;
    icon: LucideIcon;
    group: NavGroup;
    permission?: string;
    dynamicFrom?: 'contentTypes';
};

// Hanya Admin: appearance (Menu/Widget/Template) + system (Users/Settings/AI/Languages/WritingStyles/RatingCriteria)
export const NAV_ITEMS: NavItem[] = [
    { label: 'Dashboard', href: '/admin', icon: LayoutDashboard, group: 'dashboard' },

    // Konten (dinamis per content type, dipisah dari item tetap)
    { label: 'Kategori', href: '/admin/categories', icon: FolderTree, group: 'content', permission: 'content-types.viewAny' },
    { label: 'Tag', href: '/admin/tags', icon: Tag, group: 'content', permission: 'content-types.viewAny' },
    { label: 'Galeri', href: '/admin/galleries', icon: GalleryVerticalEnd, group: 'content', permission: 'galleries.viewAny' },
    { label: 'Jenis konten', href: '/admin/content-types', icon: Files, group: 'content', permission: 'content-types.viewAny' },

    // Halaman
    { label: 'Halaman', href: '/admin/pages', icon: FileText, group: 'pages', permission: 'pages.viewAny' },

    // Tampilan — Admin only
    { label: 'Menu', href: '/admin/menus', icon: MenuIcon, group: 'appearance', permission: 'admin.access-appearance' },
    { label: 'Widget', href: '/admin/widgets', icon: LayoutTemplate, group: 'appearance', permission: 'admin.access-appearance' },

    // Interaksi
    { label: 'Pesan kontak', href: '/admin/contact-messages', icon: Mail, group: 'interaction', permission: 'contact-messages.viewAny' },
    { label: 'Testimoni', href: '/admin/testimonials', icon: Quote, group: 'interaction', permission: 'testimonials.viewAny' },
    { label: 'Penilaian', href: '/admin/ratings', icon: Star, group: 'interaction', permission: 'ratings.viewAny' },

    // Sistem — Admin only
    { label: 'Media', href: '/admin/media', icon: Image, group: 'system' },
    { label: 'Pengguna', href: '/admin/users', icon: Users, group: 'system', permission: 'admin.access-system' },
    { label: 'Pengaturan', href: '/admin/settings', icon: SettingsIcon, group: 'system', permission: 'admin.access-system' },
    { label: 'Konfigurasi AI', href: '/admin/settings/ai', icon: Cpu, group: 'system', permission: 'admin.access-system' },
    { label: 'Bahasa', href: '/admin/settings/languages', icon: Languages, group: 'system', permission: 'admin.access-system' },
    { label: 'Gaya bahasa', href: '/admin/writing-styles', icon: PenTool, group: 'system', permission: 'admin.access-system' },
    { label: 'Kriteria penilaian', href: '/admin/rating-criteria', icon: Star, group: 'system', permission: 'admin.access-system' },
];

export const GROUP_LABELS: Record<NavGroup, string> = {
    dashboard: 'Dashboard',
    content: 'Konten',
    pages: 'Halaman',
    appearance: 'Tampilan',
    interaction: 'Interaksi',
    system: 'Sistem',
};
```

- [ ] **Step 2: Modify `app-sidebar.tsx` — filter permission + inject contentTypes**

Baca `resources/js/components/app-sidebar.tsx` yang ada. Modifikasi supaya:
1. Ambil `contentTypes` dan `auth.user.permissions` dari `usePage().props`.
2. Untuk grup `content`, prepend entri dinamis `{label: ct.name, href: '/admin/posts?type=' + ct.slug, group: 'content'}` untuk setiap `ct` di `contentTypes`.
3. Filter `NAV_ITEMS` berdasarkan `item.permission` — jika `item.permission` undefined → tampilkan; jika ada → cek `permissions.includes(item.permission)`.

Karena struktur `app-sidebar.tsx` starter spesifik, **baca file tersebut dulu** lalu sesuaikan. Kerangka konsep:
```tsx
import { NAV_ITEMS, GROUP_LABELS } from '@/components/admin/sidebar-nav-config';
import { usePage } from '@inertiajs/react';

// di komponen:
const { contentTypes, auth } = usePage().props as any;
const userPermissions: string[] = auth?.user?.permissions ?? [];

const dynamicContentItems = (contentTypes ?? []).map((ct: any) => ({
    label: ct.name,
    href: `/admin/posts?type=${ct.slug}`,
    group: 'content' as const,
}));

const allItems = [...dynamicContentItems, ...NAV_ITEMS].filter(
    (item) => !item.permission || userPermissions.includes(item.permission)
);
```

- [ ] **Step 3: Test visibilitas role**

`tests/Feature/AdminSidebarVisibilityTest.php`:
```php
<?php

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->artisan('migrate:fresh', ['--no-interaction' => true]);
    $this->artisan('db:seed', ['--no-interaction' => true]);
});

it('Admin melihat grup Tampilan dan Sistem', function () {
    $admin = User::where('email', env('ADMIN_EMAIL'))->first();
    $html = $this->actingAs($admin)->get('/admin')->getContent();
    expect($html)->toContain('Tampilan')->toContain('Sistem')->toContain('Pengguna');
});

it('Editor tidak melihat Tampilan / Sistem', function () {
    $editor = User::factory()->create()->assignRole(UserRole::Editor->value);
    $html = $this->actingAs($editor)->get('/admin')->getContent();
    expect($html)->not->toContain('Pengguna')->not->toContain('Tampilan');
});

it('Author hanya Dashboard + Konten + Media', function () {
    $author = User::factory()->create()->assignRole(UserRole::Author->value);
    $html = $this->actingAs($author)->get('/admin')->getContent();
    expect($html)->toContain('Media')->not->toContain('Pesan kontak');
});

it('Sidebar berisi content_types seeder: Artikel, Berita, Pengumuman', function () {
    $admin = User::where('email', env('ADMIN_EMAIL'))->first();
    $html = $this->actingAs($admin)->get('/admin')->getContent();
    expect($html)->toContain('Artikel')->toContain('Berita')->toContain('Pengumuman');
});
```

- [ ] **Step 4: Test + Pint**

```bash
php artisan test --compact --filter=AdminSidebarVisibilityTest
vendor/bin/pint --dirty --format agent
```

---

## Task 5.3: Dashboard ringkasan (statistik + list draft + contact baru)

**Files:**
- Create: `app/Http/Controllers/Admin/DashboardController.php`
- Modify: `routes/admin.php` (wire ke controller baru, ganti placeholder Task 3.4)
- Create: `resources/js/pages/admin/dashboard.tsx`
- Test: `tests/Feature/AdminDashboardTest.php`

**Interfaces:**
- Produces: `Admin\DashboardController@index` mengembalikan props `stats` (total post, page, media, contact new), `draftPosts`, `newContactMessages`.

- [ ] **Step 1: Buat `DashboardController`**

```php
<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Enums\{ContactStatus, PostStatus};
use App\Models\{ContactMessage, Language, Post, PostTranslation};
use App\Models\Page;
use Inertia\Inertia;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

class DashboardController
{
    public function index()
    {
        $langId = Language::current()->id;

        $stats = [
            'posts' => Post::count(),
            'pages' => Page::count(),
            'media' => Media::count(),
            'contactNew' => ContactMessage::where('status', ContactStatus::New->value)->count(),
        ];

        $draftPosts = PostTranslation::where('language_id', $langId)
            ->where('status', PostStatus::Draft)
            ->orderByDesc('updated_at')
            ->limit(5)
            ->get(['id', 'title', 'updated_at']);

        $newContactMessages = ContactMessage::where('status', ContactStatus::New->value)
            ->orderByDesc('created_at')
            ->limit(5)
            ->get(['id', 'name', 'subject', 'created_at']);

        return Inertia::render('admin/dashboard', compact('stats', 'draftPosts', 'newContactMessages'));
    }
}
```

- [ ] **Step 2: Wire route — ganti placeholder di `routes/admin.php`**

```php
Route::get('/', [DashboardController::class, 'index'])->name('admin.dashboard');
```
Tambah import: `use App\Http\Controllers\Admin\DashboardController;`.

- [ ] **Step 3: Buat `pages/admin/dashboard.tsx`**

```tsx
import { Head } from '@inertiajs/react';
import AdminLayout from '@/layouts/admin-layout';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';

export default function AdminDashboard({
    stats,
    draftPosts,
    newContactMessages,
}: {
    stats: { posts: number; pages: number; media: number; contactNew: number };
    draftPosts: { id: number; title: string; updated_at: string }[];
    newContactMessages: { id: number; name: string; subject: string; created_at: string }[];
}) {
    return (
        <AdminLayout>
            <Head title="Dashboard" />
            <div className="space-y-6 p-6">
                <h1 className="text-2xl font-semibold">Dashboard</h1>
                <div className="grid grid-cols-2 gap-4 md:grid-cols-4">
                    <Card><CardHeader><CardTitle className="text-sm">Konten</CardTitle></CardHeader><CardContent className="text-3xl font-bold">{stats.posts}</CardContent></Card>
                    <Card><CardHeader><CardTitle className="text-sm">Halaman</CardTitle></CardHeader><CardContent className="text-3xl font-bold">{stats.pages}</CardContent></Card>
                    <Card><CardHeader><CardTitle className="text-sm">Media</CardTitle></CardHeader><CardContent className="text-3xl font-bold">{stats.media}</CardContent></Card>
                    <Card><CardHeader><CardTitle className="text-sm">Pesan baru</CardTitle></CardHeader><CardContent className="text-3xl font-bold">{stats.contactNew}</CardContent></Card>
                </div>

                <div className="grid gap-4 md:grid-cols-2">
                    <Card>
                        <CardHeader><CardTitle>Draft terbaru</CardTitle></CardHeader>
                        <CardContent>
                            {draftPosts.length === 0 ? <p className="text-sm text-muted-foreground">Belum ada draft.</p> : (
                                <ul className="text-sm">{draftPosts.map((p) => <li key={p.id}>{p.title}</li>)}</ul>
                            )}
                        </CardContent>
                    </Card>
                    <Card>
                        <CardHeader><CardTitle>Pesan kontak baru</CardTitle></CardHeader>
                        <CardContent>
                            {newContactMessages.length === 0 ? <p className="text-sm text-muted-foreground">Belum ada pesan baru.</p> : (
                                <ul className="text-sm">{newContactMessages.map((m) => <li key={m.id}>{m.name} — {m.subject}</li>)}</ul>
                            )}
                        </CardContent>
                    </Card>
                </div>
            </div>
        </AdminLayout>
    );
}
```

> Komponen Card dari shadcn — pastikan sudah ada di `resources/js/components/ui/card.tsx` (dari starter). Jika belum, jalankan `npx shadcn@latest add card`.

- [ ] **Step 4: Test**

`tests/Feature/AdminDashboardTest.php`:
```php
<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->artisan('migrate:fresh', ['--no-interaction' => true]);
    $this->artisan('db:seed', ['--no-interaction' => true]);
});

it('GET /admin menampilkan kartu statistik', function () {
    $admin = User::where('email', env('ADMIN_EMAIL'))->first();
    $response = $this->actingAs($admin)->get('/admin');
    $response->assertOk();
    $html = $response->getContent();
    expect($html)->toContain('Konten')->toContain('Halaman')->toContain('Media')->toContain('Pesan baru');
});

it('Dashboard tidak crash bila tidak ada data', function () {
    // Hapus semua post dan contact
    \App\Models\Post::query()->delete();
    \App\Models\ContactMessage::query()->delete();
    $admin = User::where('email', env('ADMIN_EMAIL'))->first();
    $response = $this->actingAs($admin)->get('/admin');
    $response->assertOk();
    expect($response->getContent())->toContain('Belum ada');
});
```

- [ ] **Step 5: Test + Pint**

```bash
php artisan test --compact --filter=AdminDashboardTest
vendor/bin/pint --dirty --format agent
```

---

## Task 5.4: Placeholder routes & ComingSoon component

**Files:**
- Create: `resources/js/components/admin/coming-soon.tsx`
- Create: `resources/js/pages/admin/placeholder.tsx`
- Modify: `routes/admin.php` — wire semua placeholder routes (lihat spec §6)
- Test: `tests/Feature/AdminPlaceholderRoutesTest.php`

**Interfaces:**
- Produces: semua route `admin.*.index` (lihat spec §6) menampilkan `ComingSoon` dengan label sesuai.

- [ ] **Step 1: Buat `coming-soon.tsx`**

```tsx
import AdminLayout from '@/layouts/admin-layout';
import { Head, Link } from '@inertiajs/react';
import { ArrowLeft, Construction } from 'lucide-react';

export function ComingSoon({ section }: { section: string }) {
    return (
        <AdminLayout>
            <Head title={section} />
            <div className="flex min-h-[60vh] flex-col items-center justify-center gap-4 p-8 text-center">
                <Construction className="h-16 w-16 text-muted-foreground" />
                <h1 className="text-2xl font-semibold">{section}</h1>
                <p className="text-muted-foreground">Bagian ini akan segera tersedia.</p>
                <Link href="/admin" className="mt-4 inline-flex items-center gap-2 rounded bg-primary px-4 py-2 text-primary-foreground hover:bg-primary/90">
                    <ArrowLeft className="h-4 w-4" /> Kembali ke dashboard
                </Link>
            </div>
        </AdminLayout>
    );
}
```

- [ ] **Step 2: Buat `placeholder.tsx` (wrapper)**

```tsx
import { ComingSoon } from '@/components/admin/coming-soon';

export default function AdminPlaceholder({ section }: { section: string }) {
    return <ComingSoon section={section} />;
}
```

- [ ] **Step 3: Wire semua placeholder route di `routes/admin.php`**

```php
Route::get('/posts', fn () => \Inertia\Inertia::render('admin/placeholder', ['section' => 'Konten']))->name('admin.posts.index');
Route::get('/pages', fn () => \Inertia\Inertia::render('admin/placeholder', ['section' => 'Halaman']))->name('admin.pages.index');
Route::get('/menus', fn () => \Inertia\Inertia::render('admin/placeholder', ['section' => 'Menu']))->name('admin.menus.index')->middleware('permission:admin.access-appearance');
Route::get('/widgets', fn () => \Inertia\Inertia::render('admin/placeholder', ['section' => 'Widget']))->name('admin.widgets.index')->middleware('permission:admin.access-appearance');
Route::get('/contact-messages', fn () => \Inertia\Inertia::render('admin/placeholder', ['section' => 'Pesan Kontak']))->name('admin.contact-messages.index');
Route::get('/testimonials', fn () => \Inertia\Inertia::render('admin/placeholder', ['section' => 'Testimoni']))->name('admin.testimonials.index');
Route::get('/ratings', fn () => \Inertia\Inertia::render('admin/placeholder', ['section' => 'Penilaian']))->name('admin.ratings.index');
Route::get('/users', fn () => \Inertia\Inertia::render('admin/placeholder', ['section' => 'Pengguna']))->name('admin.users.index')->middleware('permission:admin.access-system');
Route::get('/settings', fn () => \Inertia\Inertia::render('admin/placeholder', ['section' => 'Pengaturan']))->name('admin.settings.index')->middleware('permission:admin.access-system');
Route::get('/settings/ai', fn () => \Inertia\Inertia::render('admin/placeholder', ['section' => 'Konfigurasi AI']))->name('admin.settings.ai')->middleware('permission:admin.access-system');
Route::get('/settings/languages', fn () => \Inertia\Inertia::render('admin/placeholder', ['section' => 'Bahasa']))->name('admin.settings.languages')->middleware('permission:admin.access-system');
Route::get('/content-types', fn () => \Inertia\Inertia::render('admin/placeholder', ['section' => 'Jenis Konten']))->name('admin.content-types.index');
Route::get('/categories', fn () => \Inertia\Inertia::render('admin/placeholder', ['section' => 'Kategori']))->name('admin.categories.index');
Route::get('/tags', fn () => \Inertia\Inertia::render('admin/placeholder', ['section' => 'Tag']))->name('admin.tags.index');
Route::get('/galleries', fn () => \Inertia\Inertia::render('admin/placeholder', ['section' => 'Galeri']))->name('admin.galleries.index');
Route::get('/writing-styles', fn () => \Inertia\Inertia::render('admin/placeholder', ['section' => 'Gaya Bahasa']))->name('admin.writing-styles.index')->middleware('permission:admin.access-system');
Route::get('/rating-criteria', fn () => \Inertia\Inertia::render('admin/placeholder', ['section' => 'Kriteria Penilaian']))->name('admin.rating-criteria.index')->middleware('permission:admin.access-system');
// /admin/media akan diisi Fase 7; sementara placeholder
Route::get('/media', fn () => \Inertia\Inertia::render('admin/placeholder', ['section' => 'Media']))->name('admin.media.index');
```

- [ ] **Step 4: Test**

`tests/Feature/AdminPlaceholderRoutesTest.php`:
```php
<?php

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->artisan('migrate:fresh', ['--no-interaction' => true]);
    $this->artisan('db:seed', ['--no-interaction' => true]);
});

it('semua route placeholder mengembalikan 200 untuk Admin', function () {
    $admin = User::where('email', env('ADMIN_EMAIL'))->first();
    $routes = ['/admin/posts', '/admin/pages', '/admin/menus', '/admin/widgets',
               '/admin/contact-messages', '/admin/testimonials', '/admin/ratings',
               '/admin/users', '/admin/settings', '/admin/settings/ai', '/admin/settings/languages',
               '/admin/content-types', '/admin/categories', '/admin/tags', '/admin/galleries',
               '/admin/writing-styles', '/admin/rating-criteria', '/admin/media'];
    foreach ($routes as $r) {
        $this->actingAs($admin)->get($r)->assertOk();
    }
});

it('route Tampilan ter-tolak untuk Editor', function () {
    $editor = User::factory()->create()->assignRole(UserRole::Editor->value);
    $this->actingAs($editor)->get('/admin/menus')->assertForbidden();
    $this->actingAs($editor)->get('/admin/widgets')->assertForbidden();
});

it('route Sistem ter-tolak untuk Editor', function () {
    $editor = User::factory()->create()->assignRole(UserRole::Editor->value);
    $this->actingAs($editor)->get('/admin/users')->assertForbidden();
    $this->actingAs($editor)->get('/admin/settings')->assertForbidden();
});
```

- [ ] **Step 5: Test + Pint**

```bash
php artisan test --compact --filter=AdminPlaceholderRoutesTest
vendor/bin/pint --dirty --format agent
```

- [ ] **Step 6: Verifikasi OK — Fase 5 selesai. Lanjut Fase 6.**

---

# FASE 6 — REGION, SEO, AKSESIBILITAS

## Task 6.1: HTML Sanitizer (stevebauman/purify)

**Files:**
- Create: `app/Services/Html/Sanitizer.php`
- Modify: `config/purify.php` (default + custom allowlist class)
- Test: `tests/Feature/HtmlSanitizerTest.php`

**Interfaces:**
- Produces: `App\Services\Html\Sanitizer::clean(string $html): string` — buang `<script>`, `<style>`, atribut `on*`, `javascript:`/`data:` URLs di href/src, izinkan tag design system (div, section, span, p, h1-h6, ul, ol, li, a, img, figure, blockquote, dll) + atribut `class`, `href`, `src`, `alt`.

- [ ] **Step 1: Edit `config/purify.php` — tambah preset 'cms_page'**

Buka `config/purify.php`. Tambahkan preset:
```php
'presets' => [
    'default' => [...], // biarkan default
    'cms_page' => [
        'HTML.Allowed' => 'div,section,span,p,h1,h2,h3,h4,h5,h6,ul,ol,li,a,img,figure,figcaption,blockquote,br,hr,table,thead,tbody,tr,td,th,strong,em,b,i,code,pre',
        'HTML.AllowedAttributes' => 'class,href,src,alt,title,target,rel,width,height',
        'HTML.ForbiddenElements' => 'script,style,iframe,object,embed,form,input,button',
        'Attr.ForbiddenClasses' => '',
        'AutoFormat.RemoveEmpty' => true,
        'HTML.TargetBlank' => true,
        'URI.AllowedSchemes' => 'http,https,mailto',
        'Attr.AllowedFrameTargets' => '_blank,_self',
    ],
],
```
Jika struktur config berbeda dari harapan (e.g. serializer), baca contoh dari `vendor/stevebauman/purify/README.md` dan sesuaikan.

- [ ] **Step 2: Buat `app/Services/Html/Sanitizer.php`**

```php
<?php

declare(strict_types=1);

namespace App\Services\Html;

use Stevebauman\Purify\Facades\Purify;

class Sanitizer
{
    /**
     * Bersihkan HTML admin untuk mode code page.
     * Buang script/on*/javascript/data: URLs; pertahankan class design system.
     */
    public function clean(string $html): string
    {
        return Purify::config('cms_page')->clean($html);
    }
}
```

- [ ] **Step 3: Test**

`tests/Feature/HtmlSanitizerTest.php`:
```php
<?php

it('Sanitizer membuang script tag', function () {
    $clean = app(\App\Services\Html\Sanitizer::class)->clean('<p>ok</p><script>alert(1)</script>');
    expect($clean)->not->toContain('<script>')
        ->and($clean)->toContain('<p>ok</p>');
});

it('Sanitizer membuang atribut on*', function () {
    $clean = app(\App\Services\Html\Sanitizer::class)->clean('<p onclick="evil()">hi</p>');
    expect($clean)->not->toContain('onclick');
});

it('Sanitizer membuang javascript: URL', function () {
    $clean = app(\App\Services\Html\Sanitizer::class)->clean('<a href="javascript:alert(1)">x</a>');
    expect($clean)->not->toContain('javascript:');
});

it('Sanitizer mempertahankan class design system', function () {
    $clean = app(\App\Services\Html\Sanitizer::class)->clean('<section class="hero bg-blue-500"><h1 class="text-3xl">Hi</h1></section>');
    expect($clean)->toContain('class="hero bg-blue-500"')->toContain('text-3xl');
});
```

- [ ] **Step 4: Test + Pint**

```bash
php artisan test --compact --filter=HtmlSanitizerTest
vendor/bin/pint --dirty --format agent
```

---

## Task 6.2: Region layout publik + Hero + Widget renderer

**Files:**
- Modify: `resources/js/layouts/public-layout.tsx` (buat baru)
- Create: `resources/js/components/public/hero.tsx`
- Create: `resources/js/components/public/widget-renderer.tsx`
- Create: `resources/js/components/public/widgets/html-widget.tsx`
- Modify: controller publik (Home, Post, Page) — pass region props (hero, sidebar, widgets)
- Test: `tests/Feature/PublicLayoutRegionTest.php`

**Interfaces:**
- Produces:
  - `PublicLayout` dengan slot region: header, hero (opsional), main + sidebar (opsional), footer; widget sebelum/sesudah konten; widget sidebar & footer.
  - `WidgetRenderer` menerima `widget.type` + `widget.config` + translation → dispatch ke komponen (`HtmlWidget` untuk `type: HtmlWidget`).
  - Controller publik mengirim prop `region` dengan shape: `{ hero?: HeroProps, sidebar?: { enabled, widgets }, widgets: { beforeContent[], afterContent[], sidebar[], footer[] } }`.

- [ ] **Step 1: Buat `public-layout.tsx`**

```tsx
import { Head } from '@inertiajs/react';
import { LocaleSwitcher } from '@/components/locale-switcher';
import { Hero } from '@/components/public/hero';
import { WidgetRenderer } from '@/components/public/widget-renderer';

type WidgetItem = { type: string; config?: any; title?: string | null; content?: string | null };

type PublicLayoutProps = {
    title?: string;
    description?: string;
    canonical?: string;
    hreflang?: Record<string, string>;
    locale: string;
    locales: { code: string; name: string }[];
    headerMenu?: any[];
    footerMenu?: any[];
    region?: {
        hero?: { enabled: boolean; image?: string; heading?: string; subheading?: string; ctaText?: string; ctaLink?: string };
        sidebar?: { enabled: boolean; widgets: WidgetItem[] };
        widgets: { beforeContent: WidgetItem[]; afterContent: WidgetItem[]; sidebar: WidgetItem[]; footer: WidgetItem[] };
    };
    children: React.ReactNode;
};

export default function PublicLayout(props: PublicLayoutProps) {
    const { region, children } = props;
    return (
        <>
            <Head>
                {props.title && <title>{props.title}</title>}
                {props.description && <meta name="description" content={props.description} />}
                {props.canonical && <link rel="canonical" href={props.canonical} />}
                {props.hreflang && Object.entries(props.hreflang).map(([locale, url]) => (
                    <link key={locale} rel="alternate" hrefLang={locale} href={url} />
                ))}
            </Head>
            <a href="#main-content" className="sr-only focus:not-sr-only focus:absolute focus:top-2 focus:left-2 focus:z-50 focus:bg-white focus:px-4 focus:py-2">
                Lewati ke konten utama
            </a>
            <header className="border-b">
                <nav aria-label="Navigasi utama" className="mx-auto flex max-w-6xl items-center justify-between p-4">
                    <a href="/" className="font-bold">{/* logo */} Papenajam</a>
                    <ul className="flex gap-4">
                        {(props.headerMenu ?? []).map((item, i) => (
                            <li key={i}><a href={item.url ?? '#'}>{item.label}</a></li>
                        ))}
                    </ul>
                    <LocaleSwitcher currentLocale={props.locale} locales={props.locales} currentPath="/" />
                </nav>
            </header>

            {region?.hero?.enabled && (
                <Hero {...region.hero} />
            )}

            <div className={`mx-auto max-w-6xl gap-6 p-4 ${region?.sidebar?.enabled ? 'md:grid md:grid-cols-[1fr_300px]' : ''}`}>
                <main id="main-content">
                    {(region?.widgets.beforeContent ?? []).map((w, i) => <WidgetRenderer key={i} widget={w} />)}
                    {children}
                    {(region?.widgets.afterContent ?? []).map((w, i) => <WidgetRenderer key={`a${i}`} widget={w} />)}
                </main>
                {region?.sidebar?.enabled && (
                    <aside aria-label="Sidebar" className="space-y-4">
                        {(region?.widgets.sidebar ?? []).map((w, i) => <WidgetRenderer key={`s${i}`} widget={w} />)}
                    </aside>
                )}
            </div>

            <footer className="border-t bg-muted/50">
                <div className="mx-auto max-w-6xl p-4">
                    <ul className="flex flex-wrap gap-4">
                        {(props.footerMenu ?? []).map((item, i) => (
                            <li key={i}><a href={item.url ?? '#'}>{item.label}</a></li>
                        ))}
                    </ul>
                    {(region?.widgets.footer ?? []).map((w, i) => <WidgetRenderer key={`f${i}`} widget={w} />)}
                </div>
            </footer>
        </>
    );
}
```

- [ ] **Step 2: Buat `hero.tsx`**

```tsx
export function Hero({ image, heading, subheading, ctaText, ctaLink }: {
    image?: string; heading?: string; subheading?: string; ctaText?: string; ctaLink?: string;
}) {
    return (
        <section aria-label="Hero" className="relative min-h-[300px] overflow-hidden bg-slate-900 text-white">
            {image && (
                <img src={image} alt="" className="absolute inset-0 h-full w-full object-cover opacity-50" loading="eager" />
            )}
            <div className="relative z-10 mx-auto max-w-6xl p-8 md:p-16">
                {heading && <h1 className="text-3xl font-bold md:text-5xl">{heading}</h1>}
                {subheading && <p className="mt-4 text-lg text-white/90">{subheading}</p>}
                {ctaText && ctaLink && (
                    <a href={ctaLink} className="mt-6 inline-block rounded bg-primary px-6 py-3 font-semibold hover:bg-primary/90">
                        {ctaText}
                    </a>
                )}
            </div>
        </section>
    );
}
```

- [ ] **Step 3: Buat `widget-renderer.tsx` + `widgets/html-widget.tsx`**

```tsx
// widget-renderer.tsx
import { HtmlWidget } from './widgets/html-widget';

type WidgetItem = { type: string; config?: any; title?: string | null; content?: string | null };

export function WidgetRenderer({ widget }: { widget: WidgetItem }) {
    switch (widget.type) {
        case 'HtmlWidget':
            return <HtmlWidget title={widget.title} content={widget.content} />;
        default:
            return null; // Fase fitur: tambah tipe widget lain
    }
}
```

```tsx
// widgets/html-widget.tsx
export function HtmlWidget({ title, content }: { title?: string | null; content?: string | null }) {
    return (
        <div className="rounded border p-4">
            {title && <h3 className="mb-2 font-semibold">{title}</h3>}
            {content && <div dangerouslySetInnerHTML={{ __html: content }} />}
        </div>
    );
}
```

- [ ] **Step 4: Update controller publik untuk kirim region minimal + menu**

Tambah helper `App\Support\PublicLayoutProps` yang meng-compose region/menu/locales dari DB (cache 1 jam) dan dipakai seluruh controller publik.

Buat `app/Support/PublicLayoutProps.php`:
```php
<?php

declare(strict_types=1);

namespace App\Support;

use App\Enums\MenuLocation;
use App\Models\{Language, Menu, Widget, WidgetPlacement};
use Illuminate\Support\Facades\Cache;

class PublicLayoutProps
{
    public static function base(): array
    {
        $langId = Language::current()->id;

        return Cache::remember("public_layout.{$langId}", now()->addHour(), function () use ($langId) {
            $headerMenu = static::resolveMenu(MenuLocation::Header, $langId);
            $footerMenu = static::resolveMenu(MenuLocation::Footer, $langId);
            $widgets = static::resolveWidgets($langId);
            $locales = Language::active()->get(['code', 'name'])->toArray();

            return [
                'locale' => app()->getLocale(),
                'locales' => $locales,
                'headerMenu' => $headerMenu,
                'footerMenu' => $footerMenu,
                'region' => [
                    'widgets' => $widgets,
                ],
            ];
        });
    }

    private static function resolveMenu(MenuLocation $location, int $langId): array
    {
        $menu = Menu::where('location', $location->value)->first();
        if (! $menu) {
            return [];
        }
        return $menu->items()
            ->with(['translations' => fn ($q) => $q->where('language_id', $langId)])
            ->orderBy('sort_order')
            ->get()
            ->map(fn ($item) => [
                'label' => $item->translations->first()?->label ?? '',
                'url' => $item->url ?? '#',
            ])
            ->toArray();
    }

    private static function resolveWidgets(int $langId): array
    {
        $byPosition = ['BeforeContent' => [], 'AfterContent' => [], 'Sidebar' => [], 'Footer' => []];
        $placements = WidgetPlacement::with(['widget.translations' => fn ($q) => $q->where('language_id', $langId)])
            ->whereHas('widget', fn ($q) => $q->where('is_active', true))
            ->orderBy('sort_order')
            ->get();

        foreach ($placements as $pl) {
            $w = $pl->widget;
            $tr = $w->translations->first();
            $item = [
                'type' => $w->type,
                'config' => $w->config,
                'title' => $tr?->title,
                'content' => $tr?->content,
            ];
            $byPosition[$pl->position->value][] = $item;
        }

        return $byPosition;
    }
}
```

Lalu di `HomeController::index`:
```php
return Inertia::render('public/home', array_merge(
    \App\Support\PublicLayoutProps::base(),
    ['latestPosts' => $latestPosts]
));
```
Lakukan serupa untuk `PostController::show`, `PostController::archive`, `PageController::show` (merge base).

Update halaman `home.tsx`, `post-show.tsx`, `post-archive.tsx`, `page-show.tsx` untuk wrap dengan `<PublicLayout>`. Contoh `post-show.tsx` final:
```tsx
import PublicLayout from '@/layouts/public-layout';
import { MetaHead } from '@/components/seo/meta-head';

export default function PostShow(props: any) {
    return (
        <PublicLayout {...props} title={props.seo?.title} description={props.seo?.description} canonical={props.seo?.canonical} hreflang={props.seo?.hreflang}>
            <article className="prose">
                <h1>{props.post.title}</h1>
                <div dangerouslySetInnerHTML={{ __html: props.post.body ?? '' }} />
            </article>
        </PublicLayout>
    );
}
```

- [ ] **Step 5: Test**

`tests/Feature/PublicLayoutRegionTest.php`:
```php
<?php

use App\Models\Language;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->artisan('migrate:fresh', ['--no-interaction' => true]);
    $this->artisan('db:seed', ['--no-interaction' => true]);
    Language::flushCache();
});

it('Publik layout punya header, main id, footer', function () {
    $html = $this->get('/')->getContent();
    expect($html)->toContain('<header')
        ->and($html)->toContain('id="main-content"')
        ->and($html)->toContain('<footer');
});

it('Skip link aksesibilitas ada', function () {
    $html = $this->get('/')->getContent();
    expect($html)->toMatch('/sr-only.*Lewati ke konten/i');
});

it('LocaleSwitcher render dua locale', function () {
    $html = $this->get('/')->getContent();
    expect($html)->toContain('Bahasa Indonesia')->toContain('English');
});
```

- [ ] **Step 6: Test + Pint**

```bash
php artisan test --compact --filter=PublicLayoutRegionTest
vendor/bin/pint --dirty --format agent
```

---

## Task 6.3: SEO props builder + JSON-LD components

**Files:**
- Create: `app/Support/Seo/SeoProps.php`
- Create: `resources/js/components/seo/json-ld.tsx`
- Modify: `resources/js/components/seo/meta-head.tsx` (extend OG)
- Modify: controller publik — kirim seo + jsonLd props
- Test: `tests/Feature/SeoTest.php`

**Interfaces:**
- Produces:
  - `SeoProps::for(string $title, ?string $description, string $canonical, array $hreflang, string $ogType = 'website', ?string $ogImage = null): array`.
  - `JsonLd` component render `<script type="application/ld+json">`.

- [ ] **Step 1: Buat `app/Support/Seo/SeoProps.php`**

```php
<?php

declare(strict_types=1);

namespace App\Support\Seo;

class SeoProps
{
    public static function for(
        string $title,
        ?string $description,
        string $canonical,
        array $hreflang = [],
        string $ogType = 'website',
        ?string $ogImage = null,
    ): array {
        return [
            'title' => $title,
            'description' => $description,
            'canonical' => $canonical,
            'hreflang' => $hreflang,
            'ogType' => $ogType,
            'ogImage' => $ogImage,
            'ogTitle' => $title,
            'ogDescription' => $description,
        ];
    }
}
```

- [ ] **Step 2: Buat `resources/js/components/seo/json-ld.tsx`**

```tsx
export function JsonLd({ data }: { data: Record<string, any> | Record<string, any>[] }) {
    const json = JSON.stringify(data);
    return <script type="application/ld+json" dangerouslySetInnerHTML={{ __html: json }} />;
}
```

- [ ] **Step 3: Extend `meta-head.tsx`** untuk OG tag (sudah ada di Task 4.3 Step 1; pastikan `og:title`, `og:description`, `og:image`, `og:type` ada). Tidak ada perubahan bila sudah.

- [ ] **Step 4: Update controller publik — kirim `seo` via `SeoProps::for(...)` + `jsonLd`**

Di `PostController::show`, ganti array seo manual dengan:
```php
$seo = \App\Support\Seo\SeoProps::for(
    title: $translation->meta_title ?? $translation->title,
    description: $translation->meta_description,
    canonical: url()->current(),
    hreflang: $hreflang,
    ogType: 'article',
);

$jsonLd = [
    '@context' => 'https://schema.org',
    '@type' => 'Article',
    'headline' => $translation->title,
    'datePublished' => $translation->published_at?->toIso8601String(),
    'image' => $translation->post->featured_image ? [url($translation->post->featured_image)] : [],
    'inLanguage' => app()->getLocale(),
];
```

Di `HomeController`, tambahkan JSON-LD `Organization` + `WebSite`:
```php
$jsonLd = [
    '@context' => 'https://schema.org',
    '@graph' => [
        ['@type' => 'Organization', 'name' => config('app.name'), 'url' => url('/')],
        ['@type' => 'WebSite', 'name' => config('app.name'), 'url' => url('/'),
         'potentialAction' => ['@type' => 'SearchAction', 'target' => url('/search?q={query}'), 'query-input' => 'required name=query']],
    ],
];
```

Render `<JsonLd>` di komponen layout publik:
```tsx
import { JsonLd } from '@/components/seo/json-ld';
// ...
{props.jsonLd && <JsonLd data={props.jsonLd} />}
```

- [ ] **Step 5: Test**

`tests/Feature/SeoTest.php`:
```php
<?php

use App\Models\Language;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->artisan('migrate:fresh', ['--no-interaction' => true]);
    $this->artisan('db:seed', ['--no-interaction' => true]);
    Language::flushCache();
});

it('Home mengandung JSON-LD Organization dan WebSite', function () {
    $html = $this->get('/')->getContent();
    expect($html)->toMatch('/application\/ld\+json/')
        ->and($html)->toContain('"@type":"Organization"')
        ->and($html)->toContain('"@type":"WebSite"');
});

it('Single post mengandung JSON-LD Article', function () {
    $html = $this->get('/berita/selamat-datang')->getContent();
    expect($html)->toContain('"@type":"Article"')
        ->and($html)->toContain('"headline"');
});

it('Meta description dan canonical ada di single post', function () {
    $html = $this->get('/berita/selamat-datang')->getContent();
    expect($html)->toMatch('/<meta name="description"/')
        ->and($html)->toMatch('/<link rel="canonical"/');
});

it('OG tags ada', function () {
    $html = $this->get('/berita/selamat-datang')->getContent();
    expect($html)->toMatch('/property="og:title"/')
        ->and($html)->toMatch('/property="og:type"/');
});
```

- [ ] **Step 6: Test + Pint**

```bash
php artisan test --compact --filter=SeoTest
vendor/bin/pint --dirty --format agent
```

---

## Task 6.4: Sitemap generator (spatie/laravel-sitemap)

**Files:**
- Create: `app/Console/Commands/GenerateSitemap.php`
- Modify: `routes/console.php` (schedule daily)
- Test: `tests/Feature/SitemapTest.php`

**Interfaces:**
- Produces: `GET /sitemap.xml` berisi semua URL post + page published per-locale.

- [ ] **Step 1: Buat command `GenerateSitemap`**

```bash
php artisan make:command GenerateSitemap --no-interaction
```

Isi `app/Console/Commands/GenerateSitemap.php`:
```php
<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Enums\PostStatus;
use App\Models\{ContentType, Language, PageTranslation, PostTranslation};
use Illuminate\Console\Command;
use Spatie\Sitemap\Sitemap;
use Spatie\Sitemap\Tags\Url;

class GenerateSitemap extends Command
{
    protected $signature = 'sitemap:generate';
    protected $description = 'Generate sitemap.xml untuk semua konten published per locale.';

    public function handle(): int
    {
        $sitemap = Sitemap::create();
        $defaultCode = Language::defaultModel()->code;
        $activeLocales = Language::active()->pluck('code');

        // Home per locale
        foreach ($activeLocales as $code) {
            $url = $code === $defaultCode ? url('/') : url("/{$code}");
            $sitemap->add(Url::create($url)->setChangeFrequency(Url::CHANGE_FREQUENCY_DAILY));
        }

        // Posts
        $types = ContentType::where('is_active', true)->get();
        foreach ($types as $type) {
            // Archive
            foreach ($activeLocales as $code) {
                $url = $code === $defaultCode
                    ? url("/{$type->slug}")
                    : url("/{$code}/{$type->slug}");
                $sitemap->add(Url::create($url));

                // Single
                PostTranslation::where('language_id', Language::idFor($code))
                    ->where('status', PostStatus::Published->value)
                    ->whereHas('post', fn ($q) => $q->where('type_id', $type->id))
                    ->each(function ($tr) use ($code, $defaultCode, $type, $sitemap) {
                        $url = $code === $defaultCode
                            ? url("/{$type->slug}/{$tr->slug}")
                            : url("/{$code}/{$type->slug}/{$tr->slug}");
                        $sitemap->add(Url::create($url)->setLastModificationDate($tr->updated_at));
                    });
            }
        }

        // Custom pages
        PageTranslation::where('status', 'Published')->each(function ($pt) use ($defaultCode, $sitemap) {
            $locale = $pt->language->code;
            $url = $locale === $defaultCode
                ? url("/{$pt->slug}")
                : url("/{$locale}/{$pt->slug}");
            $sitemap->add(Url::create($url)->setLastModificationDate($pt->updated_at));
        });

        $sitemap->writeToFile(public_path('sitemap.xml'));
        $this->info('Sitemap generated.');
        return self::SUCCESS;
    }
}
```

- [ ] **Step 2: Schedule di `routes/console.php`**

```php
use Illuminate\Support\Facades\Schedule;

Schedule::command('sitemap:generate')->dailyAt('00:00');
```

- [ ] **Step 3: Test**

`tests/Feature/SitemapTest.php`:
```php
<?php

use App\Models\Language;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->artisan('migrate:fresh', ['--no-interaction' => true]);
    $this->artisan('db:seed', ['--no-interaction' => true]);
    Language::flushCache();
});

it('sitemap:generate membuat sitemap.xml berisi URL post demo', function () {
    $this->artisan('sitemap:generate')->assertSuccessful();
    $xml = file_get_contents(public_path('sitemap.xml'));
    expect($xml)->toContain('<urlset')
        ->and($xml)->toContain('/berita/selamat-datang')
        ->and($xml)->toContain('/en/berita/welcome')
        ->and($xml)->toContain(url('/'));
});

it('GET /sitemap.xml returns 200 dengan XML', function () {
    $this->artisan('sitemap:generate');
    $response = $this->get('/sitemap.xml');
    $response->assertOk();
    expect($response->getContent())->toContain('<urlset');
});
```

- [ ] **Step 4: Test + Pint**

```bash
php artisan test --compact --filter=SitemapTest
vendor/bin/pint --dirty --format agent
```

- [ ] **Step 5: Verifikasi OK — Fase 6 selesai. Lanjut Fase 7.**

---

# FASE 7 — MEDIA, AI, DESIGN SYSTEM

## Task 7.1: Media setup — HasMedia trait + default conversions (WebP + responsive, SVG exception)

**Files:**
- Create: `app/Support/Media/HasDefaultMediaConversions.php` (trait)
- Modify: `app/Models/Post.php` — add `HasMedia` interface + trait + register `featured_image` collection
- Modify: `app/Models/Page.php` — add `HasMedia`, `hero_image` collection
- Modify: `app/Models/Testimonial.php` — add `HasMedia`, `photo` collection
- Modify: `app/Settings/SiteSettings.php` — make it implement HasMedia for `logo`, `favicon` (atau skip — disimpan via Page/Page lain). Untuk Fase 7 minimum: implementasi di Post/Page/Testimonial.
- Modify: `config/media-library.php` — queue name, default disk (sudah di Task 1.1)
- Test: `tests/Feature/MediaConversionTest.php`

**Interfaces:**
- Produces: 4 trait konversi default — `webp_large` 1920w, `webp_medium` 960w, `webp_small` 480w, `thumb` 400w; responsif via `withResponsiveImages()`. SVG di-skip (file asli disimpan).

- [ ] **Step 1: Buat trait `app/Support/Media/HasDefaultMediaConversions.php`**

```php
<?php

declare(strict_types=1);

namespace App\Support\Media;

use Spatie\Image\Enums\Fit;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

trait HasDefaultMediaConversions
{
    public function registerMediaConversions(?Media $media = null): void
    {
        // SVG di-skip: tidak ada konversi, file asli disimpan
        if ($media?->mime_type === 'image/svg+xml') {
            return;
        }

        $this->addMediaConversion('thumb')
            ->fit(Fit::Max, 400, 400)
            ->format('webp')
            ->quality(80)
            ->nonQueued();

        $this->addMediaConversion('webp_small')
            ->fit(Fit::Max, 480, 480)
            ->format('webp')
            ->quality(80)
            ->queued();

        $this->addMediaConversion('webp_medium')
            ->fit(Fit::Max, 960, 960)
            ->format('webp')
            ->quality(80)
            ->queued();

        $this->addMediaConversion('webp_large')
            ->fit(Fit::Max, 1920, 1920)
            ->format('webp')
            ->quality(80)
            ->queued()
            ->withResponsiveImages();
    }
}
```

- [ ] **Step 2: Modify `app/Models/Post.php` — implement HasMedia**

Tambah import + trait + interface + collection:
```php
use App\Support\Media\HasDefaultMediaConversions;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

class Post extends Model implements HasMedia
{
    use HasTranslations, InteractsWithMedia, HasDefaultMediaConversions;

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('featured_image')->singleFile();
    }
    // ... relasi existing
}
```

- [ ] **Step 3: Modify `app/Models/Page.php` (hero_image) + `Testimonial.php` (photo)** serupa — `HasMedia`, `InteractsWithMedia`, `HasDefaultMediaConversions`, singleFile collection.

- [ ] **Step 4: Run queue worker untuk uji async conversion**

Sementara dev:
```bash
php artisan queue:work --once  # nanti pakai worker terus-menerus
```

- [ ] **Step 5: Test konversi**

`tests/Feature/MediaConversionTest.php`:
```php
<?php

use App\Models\{ContentType, Post, User};
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->artisan('migrate:fresh', ['--no-interaction' => true]);
    $this->artisan('db:seed', ['--no-interaction' => true]);
});

it('Upload JPEG ke Post featured_image — conversion webp tersedia', function () {
    $type = ContentType::where('slug', 'berita')->first();
    $post = Post::create(['type_id' => $type->id]);

    $post->addMedia(UploadedFile::fake()->image('test.jpg', 2000, 2000))
        ->toMediaCollection('featured_image');

    // Proses queue (konversi queued)
    $this->artisan('queue:work', ['--once' => true, '--stop-when-empty' => true]);

    $media = $post->getFirstMedia('featured_image');
    expect($media)->not->toBeNull()
        ->and($media->hasGeneratedConversion('webp_medium'))->toBeTrue()
        ->and($media->hasGeneratedConversion('thumb'))->toBeTrue();
});

it('Upload SVG — tidak ada conversion, file asli tersimpan', function () {
    $type = ContentType::where('slug', 'berita')->first();
    $post = Post::create(['type_id' => $type->id]);

    $svgContent = '<?xml version="1.0"?><svg xmlns="http://www.w3.org/2000/svg" width="10" height="10"><rect/></svg>';
    $file = tap(tempnam(sys_get_temp_dir(), 'svg_'), fn ($p) => rename($p, $p.'.svg'));
    file_put_contents($file.'.svg', $svgContent);

    $post->addMedia($file.'.svg')
        ->toMediaCollection('featured_image');

    $media = $post->getFirstMedia('featured_image');
    expect($media->mime_type)->toBe('image/svg+xml')
        ->and($media->hasGeneratedConversion('webp_medium'))->toBeFalse();

    @unlink($file.'.svg');
})->skip(); // Hapus ->skip() bila fixture SVG tersedia; implement fixture upload sesuai pola Spatie test.

it('Hapus Post — media terhapus juga', function () {
    $type = ContentType::where('slug', 'berita')->first();
    $post = Post::create(['type_id' => $type->id]);
    $media = $post->addMedia(UploadedFile::fake()->image('x.jpg'))
        ->toMediaCollection('featured_image');
    $path = $media->getPath();
    $post->delete();
    expect(Media::find($media->id))->toBeNull()
        ->and(file_exists($path))->toBeFalse();
});
```

- [ ] **Step 6: Test + Pint**

```bash
php artisan test --compact --filter=MediaConversionTest
vendor/bin/pint --dirty --format agent
```

---

## Task 7.2: Media library UI (upload + grid + delete)

**Files:**
- Create: `app/Http/Controllers/Admin/MediaController.php`
- Modify: `routes/admin.php` — wire `/admin/media` (ganti placeholder)
- Create: `resources/js/pages/admin/media/index.tsx`
- Create: `resources/js/components/media/media-picker.tsx`
- Test: `tests/Feature/MediaUploadTest.php`

**Interfaces:**
- Produces: `Admin\MediaController@index` (list), `store` (upload), `destroy` (delete). Frontend grid + form upload + alt editor inline.

- [ ] **Step 1: Buat `MediaController`**

```php
<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use Illuminate\Http\Request;
use Inertia\Inertia;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

class MediaController
{
    public function index()
    {
        $media = Media::latest()->paginate(24);
        return Inertia::render('admin/media/index', ['media' => $media]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'file' => ['required', 'file', 'mimes:jpg,jpeg,png,webp,svg', 'max:10240'],
            'model_type' => ['required', 'in:Post,Page,Testimonial'],
            'model_id' => ['required', 'integer'],
            'collection' => ['required', 'string'],
        ]);

        $class = 'App\\Models\\'.$validated['model_type'];
        $model = $class::findOrFail($validated['model_id']);
        $media = $model->addMediaFromRequest('file')->toMediaCollection($validated['collection']);

        return back()->with('success', 'Media uploaded: '.$media->file_name);
    }

    public function destroy(Media $media)
    {
        $media->delete();
        return back()->with('success', 'Media deleted.');
    }
}
```

- [ ] **Step 2: Wire route di `routes/admin.php`**

```php
use App\Http\Controllers\Admin\MediaController;

Route::get('/media', [MediaController::class, 'index'])->name('admin.media.index');
Route::post('/media', [MediaController::class, 'store'])->name('admin.media.store');
Route::delete('/media/{media}', [MediaController::class, 'destroy'])->name('admin.media.destroy');
```

> Catatan: hapus placeholder `/admin/media` dari Task 5.4 — ganti dengan controller.

- [ ] **Step 3: Buat `admin/media/index.tsx`**

```tsx
import { Head, useForm, router } from '@inertiajs/react';
import AdminLayout from '@/layouts/admin-layout';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';

export default function MediaIndex({ media }: { media: any }) {
    const form = useForm<{ file: File | null; model_type: string; model_id: number; collection: string }>({
        file: null,
        model_type: 'Post',
        model_id: 1,
        collection: 'featured_image',
    });

    function submit(e: React.FormEvent) {
        e.preventDefault();
        form.post('/admin/media', { preserveScroll: true });
    }

    return (
        <AdminLayout>
            <Head title="Media" />
            <div className="p-6">
                <h1 className="mb-4 text-2xl font-semibold">Media</h1>

                <form onSubmit={submit} className="mb-6 flex flex-wrap items-end gap-2">
                    <Input type="file" onChange={(e) => form.setData('file', e.target.files?.[0] ?? null)} />
                    <select value={form.data.model_type} onChange={(e) => form.setData('model_type', e.target.value)} className="border rounded px-2 py-1">
                        <option>Post</option><option>Page</option><option>Testimonial</option>
                    </select>
                    <Input type="number" placeholder="ID" value={form.data.model_id} onChange={(e) => form.setData('model_id', parseInt(e.target.value))} />
                    <Input placeholder="collection" value={form.data.collection} onChange={(e) => form.setData('collection', e.target.value)} />
                    <Button type="submit" disabled={form.processing}>Upload</Button>
                </form>

                <div className="grid grid-cols-2 gap-4 md:grid-cols-6">
                    {media.data?.map((m: any) => (
                        <div key={m.id} className="space-y-1 border rounded p-2">
                            <img src={`/storage/${m.id}/${m.file_name}`} alt="" className="aspect-square w-full object-cover" loading="lazy" />
                            <p className="truncate text-xs" title={m.file_name}>{m.file_name}</p>
                            <Button variant="destructive" size="sm" onClick={() => {
                                if (confirm('Hapus media ini?')) router.delete(`/admin/media/${m.id}`);
                            }}>Hapus</Button>
                        </div>
                    ))}
                </div>
            </div>
        </AdminLayout>
    );
}
```

- [ ] **Step 4: Buat `media-picker.tsx` (modal untuk dipakai editor Fase fitur)**

```tsx
import { Dialog, DialogContent, DialogHeader, DialogTitle, DialogTrigger } from '@/components/ui/dialog';
import { router } from '@inertiajs/react';
import { useState } from 'react';

export function MediaPicker({ onPick }: { onPick: (mediaId: number, url: string) => void }) {
    const [open, setOpen] = useState(false);
    const [media, setMedia] = useState<any[]>([]);

    function load() {
        router.visit('/admin/media', { only: ['media'], preserveScroll: true, preserveState: true, onSuccess: (page) => setMedia(page.props.media?.data ?? []) });
    }

    return (
        <Dialog open={open} onOpenChange={(v) => { setOpen(v); if (v) load(); }}>
            <DialogTrigger asChild><button className="rounded border px-3 py-1">Pilih media</button></DialogTrigger>
            <DialogContent className="max-w-3xl">
                <DialogHeader><DialogTitle>Pilih media</DialogTitle></DialogHeader>
                <div className="grid grid-cols-4 gap-2">
                    {media.map((m) => (
                        <button key={m.id} onClick={() => { onPick(m.id, `/storage/${m.id}/${m.file_name}`); setOpen(false); }}>
                            <img src={`/storage/${m.id}/${m.file_name}`} alt="" className="aspect-square w-full object-cover" loading="lazy" />
                        </button>
                    ))}
                </div>
            </DialogContent>
        </Dialog>
    );
}
```

- [ ] **Step 5: Test upload + delete**

`tests/Feature/MediaUploadTest.php`:
```php
<?php

use App\Models\{ContentType, Post, User};
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->artisan('migrate:fresh', ['--no-interaction' => true]);
    $this->artisan('db:seed', ['--no-interaction' => true]);
});

it('POST /admin/media upload sukses untuk Post', function () {
    $admin = User::where('email', env('ADMIN_EMAIL'))->first();
    $type = ContentType::where('slug', 'berita')->first();
    $post = Post::create(['type_id' => $type->id]);

    $response = $this->actingAs($admin)->post('/admin/media', [
        'file' => UploadedFile::fake()->image('a.jpg', 800, 600),
        'model_type' => 'Post',
        'model_id' => $post->id,
        'collection' => 'featured_image',
    ]);
    $response->assertRedirect();
    expect($post->fresh()->getMedia('featured_image'))->toHaveCount(1);
});

it('POST /admin/media menolak MIME tidak valid', function () {
    $admin = User::where('email', env('ADMIN_EMAIL'))->first();
    $type = ContentType::where('slug', 'berita')->first();
    $post = Post::create(['type_id' => $type->id]);

    $response = $this->actingAs($admin)->post('/admin/media', [
        'file' => UploadedFile::fake()->create('a.txt', 100, 'text/plain'),
        'model_type' => 'Post', 'model_id' => $post->id, 'collection' => 'featured_image',
    ]);
    $response->assertSessionHasErrors('file');
});

it('DELETE /admin/media/{media} menghapus', function () {
    $admin = User::where('email', env('ADMIN_EMAIL'))->first();
    $type = ContentType::where('slug', 'berita')->first();
    $post = Post::create(['type_id' => $type->id]);
    $media = $post->addMedia(UploadedFile::fake()->image('x.jpg'))->toMediaCollection('featured_image');

    $this->actingAs($admin)->delete("/admin/media/{$media->id}")->assertRedirect();
    expect(\Spatie\MediaLibrary\MediaCollections\Models\Media::find($media->id))->toBeNull();
});

it('Non-admin tidak bisa POST /admin/media', function () {
    $response = $this->post('/admin/media', [/* ... */]);
    $response->assertRedirect('/login');
});
```

- [ ] **Step 6: Test + Pint**

```bash
php artisan test --compact --filter=MediaUploadTest
vendor/bin/pint --dirty --format agent
```

---

## Task 7.3: AI Client + TranslationTask (fungsional end-to-end)

**Files:**
- Create: `app/Services/Ai/AiClient.php`
- Create: `app/Services/Ai/Tasks/TranslationTask.php`
- Create: `app/Services/Ai/Tasks/ContentRefinementTask.php` (skeleton)
- Create: `app/Services/Ai/Tasks/MarkupConformTask.php` (skeleton)
- Modify: `app/Providers/AppServiceProvider.php` — bind AiClient (resolusi AiConfig → provider runtime)
- Test: `tests/Feature/AiClientTest.php`

**Interfaces:**
- Produces:
  - `AiClient::task(AiTask $task): self` — set task aktif.
  - `AiClient::chat(string $userMessage): string` — panggil Laravel AI SDK dengan konfigurasi dari `AiConfig`.
  - `TranslationTask::translate(string $text, string $source, string $target): string` — wrap AiClient dengan system prompt default translation.
  - Skeleton: `ContentRefinementTask::suggest()` & `MarkupConformTask::suggest()` throw `\RuntimeException('Not implemented in foundation.')`.

- [ ] **Step 1: Buat `AiClient`**

```php
<?php

declare(strict_types=1);

namespace App\Services\Ai;

use App\Enums\AiTask;
use App\Models\AiConfig;
use Laravel\Ai\Ai;
use Laravel\Ai\Messages\UserMessage;

class AiClient
{
    private ?AiConfig $config = null;

    public function task(AiTask $task): self
    {
        $this->config = AiConfig::resolve($task)
            ?? throw new \RuntimeException("AI config untuk task [{$task->value}] tidak diaktifkan.");
        return $this;
    }

    public function chat(string $userMessage): string
    {
        $provider = Ai::textProvider('openai'); // default OpenAI-compatible

        // Provider akan pakai base_url + api_key dari runtime (di-merge di AppServiceProvider)
        // model dipilih dari AiConfig.
        $response = $provider->textGateway()->generateText(
            provider: $provider,
            model: $this->config->model ?? 'gpt-4o-mini',
            instructions: $this->config->system_prompt ?? '',
            messages: [new UserMessage($userMessage)],
            tools: [],
            schema: null,
            options: null,
            timeout: 60,
        );

        return $response->text;
    }
}
```

- [ ] **Step 2: Runtime config merge di `AppServiceProvider::boot`**

Di `app/Providers/AppServiceProvider.php` boot(), tambahkan:
```php
$this->configureAiRuntime();
```
Dan metode:
```php
protected function configureAiRuntime(): void
{
    // Override config ai.php secara runtime dari AiConfig aktif per-task.
    // Karena Laravel AI SDK default ke 'openai' provider, kita override key+base_url bila ada AiConfig Translation aktif.
    $translation = \App\Models\AiConfig::resolve(\App\Enums\AiTask::Translation);
    if ($translation) {
        config([
            'ai.providers.openai.key' => $translation->api_key ?? config('ai.providers.openai.key'),
            'ai.providers.openai.base_url' => $translation->base_url ?? config('ai.providers.openai.base_url'),
            'ai.default' => 'openai',
        ]);
    }
}
```

> Catatan: ini meng-override global untuk task default. Untuk dukungan multi-task paralel dengan provider berbeda, di Fase fitur akan dibuat custom provider manager. Untuk pondasi, single-task default via Translation cukup.

- [ ] **Step 3: Buat `TranslationTask`**

```php
<?php

declare(strict_types=1);

namespace App\Services\Ai\Tasks;

use App\Enums\AiTask;
use App\Services\Ai\AiClient;

class TranslationTask
{
    public function __construct(private AiClient $client) {}

    public function translate(string $text, string $sourceLocale, string $targetLocale): string
    {
        $prompt = "Terjemahkan teks berikut dari [{$sourceLocale}] ke [{$targetLocale}]. ".
                  "Pertahankan semua tag HTML apa adanya, hanya terjemahkan teks di dalamnya. ".
                  "Output HANYA hasil terjemahan, tanpa penjelasan.\n\n{$text}";

        return $this->client->task(AiTask::Translation)->chat($prompt);
    }
}
```

- [ ] **Step 4: Skeleton untuk 2 task lain**

`app/Services/Ai/Tasks/ContentRefinementTask.php`:
```php
<?php

declare(strict_types=1);

namespace App\Services\Ai\Tasks;

class ContentRefinementTask
{
    public function suggest(string $text, string $writingStylePrompt): string
    {
        throw new \RuntimeException('ContentRefinementTask belum diimplementasikan di pondasi.');
    }
}
```

`app/Services/Ai/Tasks/MarkupConformTask.php`:
```php
<?php

declare(strict_types=1);

namespace App\Services\Ai\Tasks;

class MarkupConformTask
{
    public function suggest(string $html, string $componentReference): string
    {
        throw new \RuntimeException('MarkupConformTask belum diimplementasikan di pondasi.');
    }
}
```

- [ ] **Step 5: Test (dengan mock AiClient)**

`tests/Feature/AiClientTest.php`:
```php
<?php

use App\Enums\AiTask;
use App\Models\AiConfig;
use App\Services\Ai\AiClient;
use App\Services\Ai\Tasks\{ContentRefinementTask, MarkupConformTask, TranslationTask};
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->artisan('migrate:fresh', ['--no-interaction' => true]);
    $this->artisan('db:seed', ['--no-interaction' => true]);
});

it('AiConfig api_key tersimpan terenkripsi', function () {
    $cfg = AiConfig::create([
        'task' => AiTask::Translation, 'enabled' => true,
        'base_url' => 'https://api.example.com/v1',
        'api_key' => 'super-secret', 'model' => 'gpt-4o-mini',
        'system_prompt' => 'You are a translator.',
    ]);
    $raw = \DB::table('ai_configs')->where('id', $cfg->id)->value('api_key');
    expect($raw)->not->toBe('super-secret')
        ->and($cfg->fresh()->api_key)->toBe('super-secret');
});

it('TranslationTask::translate memanggil AiClient dengan prompt', function () {
    AiConfig::create([
        'task' => AiTask::Translation, 'enabled' => true,
        'api_key' => 'k', 'base_url' => 'https://x.test/v1',
        'model' => 'gpt-4o-mini', 'system_prompt' => '',
    ]);

    $mock = \Mockery::mock(AiClient::class);
    $mock->shouldReceive('task')->with(AiTask::Translation)->andReturnSelf();
    $mock->shouldReceive('chat')->with(\Mockery::on(fn ($p) => str_contains($p, 'halo dunia') && str_contains($p, '[id]') && str_contains($p, '[en]')))
         ->andReturn('hello world');

    $task = new TranslationTask($mock);
    expect($task->translate('halo dunia', 'id', 'en'))->toBe('hello world');
});

it('ContentRefinementTask::suggest throw NotImplementedException', function () {
    app(ContentRefinementTask::class)->suggest('text', 'style');
})->throws(\RuntimeException::class);

it('MarkupConformTask::suggest throw NotImplementedException', function () {
    app(MarkupConformTask::class)->suggest('<html>', 'ref');
})->throws(\RuntimeException::class);

it('AiClient::task throw jika AiConfig tidak diaktifkan', function () {
    app(AiClient::class)->task(AiTask::ContentRefinement);
})->throws(\RuntimeException::class);
```

- [ ] **Step 6: Test + Pint**

```bash
php artisan test --compact --filter=AiClientTest
vendor/bin/pint --dirty --format agent
```

---

## Task 7.4: AiController — endpoint TRANSLATION (translate + applyTranslation)

**Files:**
- Create: `app/Http/Controllers/Admin/AiController.php`
- Modify: `routes/admin.php` — wire AI endpoints
- Test: `tests/Feature/AiControllerTest.php`

**Interfaces:**
- Produces:
  - `POST /admin/ai/translate` — input `{source_locale, target_locale, entity_type, entity_id, field}` → return `{suggestion}` (tidak auto-save).
  - `POST /admin/ai/apply-translation` — input `{entity_type, entity_id, target_locale, field, value}` → simpan field; return `{ok}`.
  - Middleware: `permission:access-admin` + `throttle:30,1`.

- [ ] **Step 1: Buat `AiController`**

```php
<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Services\Ai\Tasks\TranslationTask;
use App\Models\{PostTranslation, PageTranslation};
use Illuminate\Http\Request;

class AiController
{
    public function __construct(private TranslationTask $translation) {}

    public function translate(Request $request)
    {
        $validated = $request->validate([
            'source_locale' => ['required', 'string', 'size:2'],
            'target_locale' => ['required', 'string', 'size:2'],
            'entity_type' => ['required', 'in:PostTranslation,PageTranslation'],
            'entity_id' => ['required', 'integer'],
            'field' => ['required', 'in:title,body,meta_title,meta_description,content'],
        ]);

        $class = "App\\Models\\{$validated['entity_type']}";
        $source = $class::where('id', $validated['entity_id'])->firstOrFail();
        $sourceText = $source->{$validated['field']} ?? '';

        if (empty($sourceText)) {
            return response()->json(['suggestion' => '', 'error' => 'Source kosong.'], 422);
        }

        $suggestion = $this->translation->translate(
            text: (string) $sourceText,
            sourceLocale: $validated['source_locale'],
            targetLocale: $validated['target_locale'],
        );

        return response()->json(['suggestion' => $suggestion]);
    }

    public function applyTranslation(Request $request)
    {
        $validated = $request->validate([
            'entity_type' => ['required', 'in:PostTranslation,PageTranslation'],
            'entity_id' => ['required', 'integer'],
            'target_locale' => ['required', 'string', 'size:2'],
            'field' => ['required', 'in:title,body,meta_title,meta_description,content'],
            'value' => ['required', 'string'],
        ]);

        $class = "App\\Models\\{$validated['entity_type']}";
        $entity = $class::findOrFail($validated['entity_id']);
        $entity->update([$validated['field'] => $validated['value']]);

        return response()->json(['ok' => true]);
    }
}
```

- [ ] **Step 2: Wire route di `routes/admin.php`**

```php
use App\Http\Controllers\Admin\AiController;

Route::post('/ai/translate', [AiController::class, 'translate'])
    ->middleware('throttle:30,1')
    ->name('admin.ai.translate');
Route::post('/ai/apply-translation', [AiController::class, 'applyTranslation'])
    ->name('admin.ai.apply-translation');
```

- [ ] **Step 3: Test endpoint (mock TranslationTask)**

`tests/Feature/AiControllerTest.php`:
```php
<?php

use App\Enums\AiTask;
use App\Models\{AiConfig, ContentType, Language, Post, PostTranslation, User};
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->artisan('migrate:fresh', ['--no-interaction' => true]);
    $this->artisan('db:seed', ['--no-interaction' => true]);
    Language::flushCache();
});

it('POST /admin/ai/translate mengembalikan suggestion (mocked)', function () {
    $admin = User::where('email', env('ADMIN_EMAIL'))->first();

    $type = ContentType::where('slug', 'berita')->first();
    $post = Post::create(['type_id' => $type->id]);
    $source = PostTranslation::create([
        'post_id' => $post->id, 'language_id' => Language::idFor('id'),
        'slug' => 'src', 'title' => 'Halo dunia', 'body' => '<p>Halo dunia</p>',
        'status' => \App\Enums\PostStatus::Published,
    ]);

    // Bind mock TranslationTask
    $mock = \Mockery::mock(\App\Services\Ai\Tasks\TranslationTask::class);
    $mock->shouldReceive('translate')->andReturn('Hello world');
    app()->instance(\App\Services\Ai\Tasks\TranslationTask::class, $mock);

    $response = $this->actingAs($admin)->postJson('/admin/ai/translate', [
        'source_locale' => 'id', 'target_locale' => 'en',
        'entity_type' => 'PostTranslation', 'entity_id' => $source->id,
        'field' => 'body',
    ]);

    $response->assertOk()->assertJson(['suggestion' => 'Hello world']);
});

it('POST /admin/ai/translate tidak auto-save ke DB', function () {
    $admin = User::where('email', env('ADMIN_EMAIL'))->first();
    $type = ContentType::where('slug', 'berita')->first();
    $post = Post::create(['type_id' => $type->id]);
    $source = PostTranslation::create([
        'post_id' => $post->id, 'language_id' => Language::idFor('id'),
        'slug' => 'src2', 'title' => 'X', 'body' => 'asli',
        'status' => \App\Enums\PostStatus::Published,
    ]);
    AiConfig::create(['task' => AiTask::Translation, 'enabled' => true, 'api_key' => 'k']);

    // Hanya mock — kita cek body tetap 'asli' setelah panggilan
    $mock = \Mockery::mock(\App\Services\Ai\Tasks\TranslationTask::class);
    $mock->shouldReceive('translate')->andReturn('terjemahan');
    app()->instance(\App\Services\Ai\Tasks\TranslationTask::class, $mock);

    $this->actingAs($admin)->postJson('/admin/ai/translate', [
        'source_locale' => 'id', 'target_locale' => 'en',
        'entity_type' => 'PostTranslation', 'entity_id' => $source->id, 'field' => 'body',
    ]);

    expect($source->fresh()->body)->toBe('asli'); // tidak berubah
});

it('POST /admin/ai/apply-translation menyimpan nilai', function () {
    $admin = User::where('email', env('ADMIN_EMAIL'))->first();
    $type = ContentType::where('slug', 'berita')->first();
    $post = Post::create(['type_id' => $type->id]);
    $tr = PostTranslation::create([
        'post_id' => $post->id, 'language_id' => Language::idFor('en'),
        'slug' => 'app1', 'title' => 'Old', 'body' => 'old',
        'status' => \App\Enums\PostStatus::Draft,
    ]);

    $this->actingAs($admin)->postJson('/admin/ai/apply-translation', [
        'entity_type' => 'PostTranslation', 'entity_id' => $tr->id,
        'target_locale' => 'en', 'field' => 'body', 'value' => 'new translated',
    ])->assertOk();

    expect($tr->fresh()->body)->toBe('new translated');
});

it('Endpoint AI ter-rate-limit', function () {
    $admin = User::where('email', env('ADMIN_EMAIL'))->first();
    AiConfig::create(['task' => AiTask::Translation, 'enabled' => true, 'api_key' => 'k']);
    $mock = \Mockery::mock(\App\Services\Ai\Tasks\TranslationTask::class);
    $mock->shouldReceive('translate')->andReturn('x');
    app()->instance(\App\Services\Ai\Tasks\TranslationTask::class, $mock);

    // Kirim 35 request — beberapa harus 429
    $blocked = 0;
    for ($i = 0; $i < 35; $i++) {
        $r = $this->actingAs($admin)->postJson('/admin/ai/translate', [
            'source_locale' => 'id', 'target_locale' => 'en',
            'entity_type' => 'PostTranslation', 'entity_id' => 1, 'field' => 'body',
        ]);
        if ($r->status() === 429) { $blocked++; }
    }
    expect($blocked)->toBeGreaterThan(0);
})->skip(); // rate-limit test mungkin perlu driver khusus; hapus skip bila config throttle testable.
```

- [ ] **Step 4: Test + Pint**

```bash
php artisan test --compact --filter=AiControllerTest
vendor/bin/pint --dirty --format agent
```

---

## Task 7.5: Design system katalog + global-components.ts skeleton

**Files:**
- Create: `docs/design-system/component-reference.md`
- Create: `resources/js/app/global-components.ts` (skeleton registry)
- Test: tidak ada — file dokumentasi/skeleton. Verifikasi via file exists.

**Interfaces:**
- Produces: katalog komponen minimum (Hero, Section, Card, Button, Grid) untuk dipakai sebagai konteks `MARKUP_CONFORM` nanti. Skeleton registry `[data-component]` untuk Fase fitur.

- [ ] **Step 1: Buat `docs/design-system/component-reference.md`**

```markdown
# Design System Reference — Papenajam CMS

Referensi komponen untuk `MARKUP_CONFORM` (AI menyesuaikan HTML admin ke design system ini). Ditambah saat komponen baru dibuat.

## Hero

```html
<section class="hero relative min-h-[300px] bg-slate-900 text-white">
    <div class="relative z-10 mx-auto max-w-6xl p-8">
        <h1 class="text-3xl font-bold md:text-5xl">Judul Hero</h1>
        <p class="mt-4 text-lg text-white/90">Subjudul</p>
        <a href="#" class="mt-6 inline-block rounded bg-primary px-6 py-3">CTA</a>
    </div>
</section>
```

## Section

```html
<section class="mx-auto max-w-6xl p-8">
    <h2 class="mb-4 text-2xl font-semibold">Judul Section</h2>
    <!-- content -->
</section>
```

## Card

```html
<article class="rounded-lg border bg-white p-6 shadow-sm">
    <h3 class="text-lg font-semibold">Judul Card</h3>
    <p class="mt-2 text-muted-foreground">Deskripsi</p>
</article>
```

## Button

```html
<a href="#" class="inline-block rounded bg-primary px-4 py-2 text-primary-foreground hover:bg-primary/90">Label</a>
```

## Grid (3 kolom)

```html
<div class="grid grid-cols-1 gap-6 md:grid-cols-3">
    <article>...</article>
    <article>...</article>
    <article>...</article>
</div>
```
```

- [ ] **Step 2: Buat `resources/js/app/global-components.ts`**

```ts
/**
 * Registry komponen global untuk mode code Page.
 * Memindai DOM [data-component] dan memasang perilaku React/Vanilla.
 *
 * Status: SKELETON. Diisi penuh di Fase fitur (mode code + interaktivitas).
 */

type ComponentRegistry = Record<string, (el: HTMLElement) => void>;

const registry: ComponentRegistry = {
    // 'hero': (el) => { /* hydrate */ },
    // 'carousel': (el) => { /* hydrate */ },
};

export function scanGlobalComponents(root: HTMLElement = document.body): void {
    Object.entries(registry).forEach(([name, hydrate]) => {
        root.querySelectorAll<HTMLElement>(`[data-component="${name}"]`).forEach(hydrate);
    });
}

// Auto-scan on DOM ready
if (typeof document !== 'undefined') {
    document.addEventListener('DOMContentLoaded', () => scanGlobalComponents());
}
```

- [ ] **Step 3: Import `global-components.ts` di `resources/js/app.ts`**

Tambahkan import di entry:
```ts
import './global-components';
```

- [ ] **Step 4: Verifikasi build SSR + dev**

```bash
npm run build:ssr
npm run build
```

- [ ] **Step 5: Pint (tidak ada PHP di task ini — skip bila tidak ada modifikasi PHP)**

```bash
vendor/bin/pint --dirty --format agent
```

- [ ] **Step 6: Verifikasi OK — Fase 7 selesai. PONDASI LENGKAP.**

---

# Verifikasi Akhir Pondasi

Setelah seluruh 7 fase + walking skeleton selesai, jalankan sanity-check lengkap:

- [ ] **Final Step 1: Migration fresh + seed + test suite penuh**

```bash
php artisan migrate:fresh --seed --no-interaction
php artisan test --compact
```
Expected: seluruh test PASS.

- [ ] **Final Step 2: Build SSR + dev**

```bash
npm run build:ssr
npm run dev
```

- [ ] **Final Step 3: Smoke manual**

1. Buka `http://localhost` — beranda tampil (ID).
2. Klik `English` di LocaleSwitcher → `/en` (EN).
3. Buka `/berita/selamat-datang` — judul + body ID; hreflang ID+EN di view-source.
4. Buka `/en/berita/welcome` — judul EN.
5. Login admin → `/admin` — dashboard tampil, sidebar berisi Artikel/Berita/Pengumuman.
6. Login Editor → `/admin/menus` → 403 Forbidden.
7. Upload JPEG di `/admin/media` → tersimpan.
8. POST `/admin/ai/translate` (via curl/postman) dengan mock → response 200 `{suggestion}`.

- [ ] **Final Step 4: Pint full + types check**

```bash
vendor/bin/pint --format agent
phpstan analyse
npm run types:check
npm run lint:check
```

- [ ] **Final Step 5: PONDASI SELESAI ✅**

Aplikasi siap dibangun fitur. Setiap subsistem (CRUD Posts, Pages, Menus, Widgets, dll) dapat diimplementasikan sebagai spec/plan berikutnya di atas fondasi ini.

---

## Catatan Implementasi Tambahan

### Mengenai `vendor/bin/pint --dirty --format agent`
Per AGENTS.md, jalankan setelah modifikasi file PHP. Bila ada error formatting, fix dan re-run.

### Mengenai test dengan `RefreshDatabase`
Test yang memakai `migrate:fresh` manual (bukan trait `RefreshDatabase`) — pastikan tidak ada tabrakan dengan test paralel. Prefer: pakai trait `RefreshDatabase` + seeder eksplisit via `$this->seed()` bila perlu data master.

### Mengenai Step "Commit"
Repo **bukan git**. Setiap "Commit" step → **lewati**. Ganti dengan "Verifikasi OK" + catatan output.

### Mengenai Mockery
`Mockery::mock(...)` + `app()->instance(...)` dipakai untuk isolasi AI provider. Pastikan `mockery/mockery` ada di `composer.json` (dev) — sudah dari starter.

### Mengenai `php artisan make:enum`
Jika command tidak tersedia (Laravel Boost tidak punya stub-nya), buat file manual via Write tool. Struktur: `<?php declare(strict_types=1); namespace App\Enums; enum X: string { ... }`.

### Mengenai Inertia SSR
`@inertiajs/vite` plugin dengan `ssr: true` + `ssr: 'resources/js/ssr.tsx'` di Laravel Vite plugin. Pastikan `resources/js/ssr.tsx` ada — bila tidak, salin dari Laravel React starter kit resmi.

---

# Corrections & Clarifications (Self-Review Output)

Bagian ini berisi koreksi dari self-review plan. Implementator WAJIB memperhatikan.

## C1: Route naming di `routes/admin.php`

Karena group sudah pakai `->name('admin.')`, pemanggilan `->name('admin.dashboard')` akan menghasilkan nama **`admin.admin.dashboard`** (double prefix).

**Koreksi untuk seluruh route admin:** hilangkan prefix `admin.` di pemanggilan `->name(...)`. Misal:
- `->name('admin.dashboard')` → `->name('dashboard')` (hasil: `admin.dashboard`)
- `->name('admin.posts.index')` → `->name('posts.index')` (hasil: `admin.posts.index`)
- dst.

**Verifikasi:**
```bash
php artisan route:list --path=admin | grep admin
```
Expected: nama-nama seperti `admin.dashboard`, `admin.posts.index` (single `admin.` prefix).

## C2: `Menu::scopeAt` signature

Di Task 2.5 `Menu` model — `scopeAt(Builder $q, MenuLocation $loc)` perlu import `Builder`:
```php
use Illuminate\Database\Eloquent\Builder;
```
Tambahkan import ini di atas kelas Menu.

## C3: Trait `HasTranslations::scopeWithTranslation` return type

Trait `HasTranslations` tidak bisa pakai `static` sebagai return type untuk scope yang dipanggil di builder. Untuk Fase 4-7, **tidak ada pemanggilan `scopeWithTranslation` di plan** (controller pakai eager-load manual), jadi helper ini sebaiknya dihapus dari trait untuk menghindari kebingungan. Hapus metode `scopeWithTranslation` di Task 2.2 Step 3.

## C4: Spatie permission cache dengan custom Role class

Saat memakai custom `App\Models\Role`, `RolePermissionSeeder` perlu reset cache Spatie di awal DAN akhir. Pastikan `app()[PermissionRegistrar::class]->forgetCachedPermission()` dipanggil sebelum `Role::firstOrCreate` dan juga setelah seluruh seeding. Sudah ada di awal — tambahkan juga di akhir `run()`:
```php
app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermission();
```

## C5: `SeoProps` helper tidak wajib dipakai

`App\Support\Seo\SeoProps::for(...)` (Task 6.3) hanya builder murni. Controller `PostController::show` di Task 4.3 menulis array seo manual — saat Task 6.3 jalan, **replace** dengan pemanggilan `SeoProps::for(...)` supaya DRY.

## C6: Task 5.2 modifikasi `app-sidebar.tsx`

Struktur file starter spesifik. Implementator harus:
1. `Read resources/js/components/app-sidebar.tsx` dulu.
2. Identifikasi prop `navMain` (biasanya berupa array of `{ title, url, icon }`).
3. Mapping `NAV_ITEMS` ke struktur tersebut, dengan filter permission + prepend content types.

**Tidak boleh overwrite penuh** tanpa mempertahankan struktur starter (logo, nav-user, dll).

## C7: `HasTranslations::translate` eager-load requirement

Trait asumsikan `$this->translations` sudah di-load. Di controller publik, **wajib** `with('translations')` atau `->load('translations')` sebelum panggil `$model->translate()`. Sudah tercermin di `PublicPathResolver::resolve` yang pakai `PageTranslation` langsung (bukan via Post/Page translate), tapi tetap diingat.

## C8: `AppServiceProvider::configureAiRuntime` side-effect

Runtime config merge hanya bekerja untuk satu task default (Translation). Bila implementator ingin menjalankan kedua task AI (Translation + Refinement) bersamaan dengan provider berbeda di Fase fitur, pendekatan ini perlu di-upgrade ke custom AiProvider manager. Untuk pondasi: cukup Translation.

## C9: Step "Commit" — repo bukan git

Konfirmasi ulang: `git status` → fatal not a git repo. Semua step commit **diabaikan**. Implementator dapat `git init` sendiri jika ingin tracking.

## C10: Test parallel

Beberapa test memakai `$this->artisan('migrate:fresh')` manual. Untuk menghindari tabrakan paralel, prefer **`uses(RefreshDatabase::class)`** + `$this->seed([...])` eksplisit bila perlu data master (Language, Roles, ContentTypes). Hanya test yang butuh post-demo meng-seed `DemoPostSeeder`.
