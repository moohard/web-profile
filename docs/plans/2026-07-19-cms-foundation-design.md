# Spec Desain — Pondasi CMS (Company Profile)

**Pendamping:** PRD-Website-Company-Profile-CMS-v1.0.md, Rencana-Fondasi-Teknis-CMS.md
**Tanggal:** 19 Juli 2026
**Status:** Spec desain pondasi — menunggu implementasi plan
**Stack:** Laravel 13 (PHP 8.4) + Inertia.js v3 + React 19 + TypeScript + Tailwind v4 + shadcn/ui + Magic UI · PostgreSQL (JSONB) · paket Spatie (permission, medialibrary, sitemap, settings) · Laravel AI SDK

> **Lingkup dokumen:** Spesifikasi teknis terinci untuk **7 fase pondasi** CMS. Setelah pondasi selesai, aplikasi siap dibangun fitur tanpa menyentuh struktur inti. Custom fields per jenis konten (Open Item #3 PRD) **di luar lingkup** — ditunda.

---

## 1. Konteks & Keputusan Terkunci

### Kondisi awal codebase

- Stock Laravel React starter kit (`laravel/react-starter-kit`).
- Auth sudah jalan via **Fortify + passkeys** (login, reset, 2FA, profile).
- Inertia v3 + React 19 + Tailwind v4 + Radix (shadcn) + Wayfinder + `@inertiajs/vite` (enabler SSR) sudah terkonfigurasi.
- `DB_CONNECTION=sqlite` default — perlu diganti ke PostgreSQL.
- Role starter: tabel `users` tanpa kolom role; tidak ada paket permission.
- **Belum ada** kode CMS — semua bersih.

### Keputusan desain terkunci

| Area | Keputusan |
|---|---|
| Lingkup pondasi | 7 fase penuh (setup → DB → auth → routing/locale → shell admin → shell publik/SEO → media+AI) |
| Database | PostgreSQL murni, pakai JSONB untuk field fleksibel |
| Role & permission | `spatie/laravel-permission` (3 role: Admin/Editor/Author) |
| Locale routing | ID default tanpa prefix; locale lain dengan prefix `/{locale}/...` |
| Media | Fungsional penuh — WebP otomatis + varian responsif, SVG dikecualikan, file asli disimpan |
| AI | Infrastruktur 3 agen + 1 agen (TRANSLATION) fungsional end-to-end |
| Shell publik | SSR + sample routing (arsip, single, custom page) |
| Shell admin | Sidebar dinamis + dashboard ringkasan + placeholder per bagian |
| Site settings | **Hibrida**: `spatie/laravel-settings` (non-teks) + `setting_translations` (teks i18n) |
| URL admin | Prefix `/admin/*` |
| Sanitasi HTML | `stevebauman/purify` |
| Verifikasi | Pest feature test per fase |

### Strategi eksekusi — B (Walking Skeleton)

```
Fase 1: Setup & tooling            [berdiri sendiri]
   ↓
Fase 2: Skema & model              [berdiri sendiri]
   ↓
Fase 3: Auth & otorisasi           [berdiri sendiri]
   ↓
Fase 4: Routing + locale           ← disambung langsung ke skeleton
   ↓
Walking Skeleton Vertikal          ← 1 content_type + 1 post dummy + render SSR publik
   ↓
Fase 5: Shell admin                ← dilapis di atas
   ↓
Fase 6: Region SEO + a11y          ← dilapis
   ↓
Fase 7: Media + AI (TRANSLATION)   ← dilapis terakhir
```

**Alasan:** validasi SEO/SSR/i18n adalah bagian paling berisiko — terbukti jalan lebih awal lewat skeleton vertikal, bukan menunggu sampai akhir.

### Output per milestone (bukti terlihat)

| Milestone | Bukti |
|---|---|
| Fase 1 | `composer install` + `npm install` bersih; SSR build jalan; PostgreSQL terhubung |
| Fase 2 | `php artisan migrate:fresh --seed` jalan; DB terisi bahasa, role, content_types dummy |
| Fase 3 | Login Admin bisa masuk `/admin`; Editor/Author terbatas sesuai matriks |
| **Skeleton** | `/` render SSR; `/en/` jalan; `/berita/{slug}` render single dari DB + `hreflang` |
| Fase 5 | Sidebar admin dinamis; dashboard ringkasan; placeholder per bagian |
| Fase 6 | Region hero/sidebar/widget; `sitemap.xml`; JSON-LD valid |
| Fase 7 | Upload gambar → WebP; panggil AI TRANSLATION → saran muncul |

---

## 2. Konvensi Fondasi (mengikat semua fase)

- **Namespace & folder:** ikuti struktur Laravel standar — `app/Models/*`, `app/Http/Controllers/{Admin,Public}/*`, `app/Enums/*`, `app/Services/*`, `app/Settings/*`, `app/Support/*`, `database/migrations/*`. Tidak membuat base folder baru tanpa approval.
- **PHP 8.4 + strict typing:** semua signature pakai type hint + return type. Enum `TitleCase`.
- **Tailwind v4 + shadcn/ui + Magic UI:** sudah ter-set di starter. Komponen UI dasar ditambah on-demand via `shadcn add`.
- **Wayfinder:** controller otomatis menghasilkan typed function di `resources/js/actions/` & `resources/js/routes/`. Tidak ada URL string manual di React.
- **Inertia SSR:** wajib aktif untuk semua rute publik; admin boleh CSR murni.
- **Pint:** `vendor/bin/pint --dirty --format agent` setiap modifikasi file PHP.
- **Test:** Pest; `php artisan test --compact --filter=...` untuk tes terfokus.

---

## 3. Skema Database & Model (Fase 2)

### Enum PHP (`app/Enums/*.php`)

`PostStatus` (`Draft`, `Published`) · `UserRole` (`Admin`, `Editor`, `Author`) · `AiTask` (`Translation`, `ContentRefinement`, `MarkupConform`) · `LinkType` (`Page`, `ContentArchive`, `ContentSingle`, `Url`) · `WidgetPosition` (`BeforeContent`, `AfterContent`, `Sidebar`, `Footer`) · `PlacementScope` (`All`, `Only`, `Except`) · `MenuLocation` (`Header`, `Footer`) · `PageMode` (`Code`, `Template`) · `ContactStatus` (`New`, `Read`, `Archived`) · `TestimonialStatus` (`Pending`, `Approved`)

> Disimpan sebagai string di DB (portabel & mudah dibaca); di-cast ke enum PHP.

### Perubahan dari starter

1. **`users`** — **tidak** menambah kolom `role`. Relasi ke Spatie via trait `HasRoles`. Enum `UserRole` hanya untuk label semantik.
2. **`media`** — digantikan oleh `Spatie\MediaLibrary\MediaCollections\Models\Media` (publish migration bawaan). `media` di PRD = konsep, bukan tabel custom. Model pemilik media (`Post`, `Page`, `Testimonial`, `SiteSettings`) pakai trait `HasMedia` + interface `HasMedia`.
3. **`site_settings`** — **tidak** dibuat tabel custom. Non-teks pakai `spatie/laravel-settings` (kelas `SiteSettings`, `SeoSettings`, `WhatsappSettings` di `app/Settings/*`). Teks i18n pakai tabel tunggal `setting_translations` (`key`, `language_id`, `value`).
4. **JSONB** untuk: `page_translations.content`, `widgets.config`. `$table->jsonb()->nullable()` + `$casts = ['content' => 'array']`.

### Daftar migrasi (urutan dependensi)

**Kelompok A — Fondasi:**
- `languages` (`code` unique, `is_default`, `is_active`, `sort_order`)
- `writing_styles` (`name`, `prompt`)
- `content_types` + `content_type_translations`
- `posts` + `post_translations` (slug unique per `language_id`)
- `categories` + `category_translations`
- `tags` + `tag_translations` + `post_tags`
- `galleries` + `gallery_translations` + `gallery_images` + `gallery_image_translations`

**Kelompok B — Halaman & Tata Letak:**
- `pages` + `page_translations`
- `menus` + `menu_items` + `menu_item_translations`
- `widgets` + `widget_translations` + `widget_placements` + `widget_placement_targets`

**Kelompok C — Pendukung & Interaksi:**
- `setting_translations` (untuk site settings i18n)
- `ai_configs` (kolom `api_key` cast `encrypted`)
- `contact_messages`, `testimonials`
- `rating_criteria` + `rating_criteria_translations` + `ratings` + `rating_scores`

**Kelompok D — Sudah ada dari starter (dipakai apa adanya):** users, cache, jobs, passkeys, 2FA columns, password_reset_tokens, sessions.

### Kontrak model inti

```php
// app/Support/HasTranslations.php (trait)
trait HasTranslations {
    public function translations(): HasMany { /* relasi */ }
    public function translate(?string $locale = null): ?Model {
        return $this->translations->firstWhere(
            'language_id',
            Language::idFor($locale ?? app()->getLocale())
        );
    }
}

// app/Models/Post.php
class Post extends Model {
    use HasTranslations;
    public function type(): BelongsTo { return $this->belongsTo(ContentType::class, 'type_id'); }
    public function translations(): HasMany { return $this->hasMany(PostTranslation::class); }
}
```

### Helper (`app/Support/TranslationHelpers.php` + `app/Models/Language.php`)

- `Language::idFor(string $code): int` — resolve code → ID (cache).
- `Language::default(): self` — bahasa default (`is_default = true`).
- `Language::current(): self` — bahasa aktif sesuai request locale.

### Index & constraint penting

- `post_translations`: UNIQUE(`post_id`, `language_id`); UNIQUE(`language_id`, `slug`).
- `page_translations`: UNIQUE(`page_id`, `language_id`); UNIQUE(`language_id`, `slug`).
- `menu_items`: index(`menu_id`, `parent_id`).
- `widget_placements`: index(`widget_id`).
- `ratings.visitor_hash`: index (anti-spam lookup).
- Foreign key constraint eksplisit di seluruh relasi.

### Seeder (`database/seeders/*`)

- `LanguageSeeder`: ID (`is_default`), EN, satu bahasa dummy opsional.
- `RolePermissionSeeder`: 3 role + permission dasar (resource CRUD per entitas: `{resource}.viewAny/.create/.update/.delete/.deleteOwn`).
- `ContentTypeSeeder`: Artikel, Berita, Pengumuman + template key + writing_style default.
- `WritingStyleSeeder`: 1 prompt default (gaya formal Indonesia).
- `RatingCriteriaSeeder`: 5 kriteria rekomendasi PRD (Kemudahan penggunaan, Kelengkapan informasi, Kecepatan akses, Tampilan & kenyamanan, Kepuasan keseluruhan).
- `AdminUserSeeder`: 1 admin (email dari `env('ADMIN_EMAIL')`, password dari `env('ADMIN_PASSWORD')`), assign role Admin. Skip di production (guard `app()->environment('local')`).
- `DemoPostSeeder` (untuk walking skeleton): 1 post `berita` dengan translation ID + EN, status Published.

---

## 4. Autentikasi & Otorisasi (Fase 3)

### Yang sudah ada (dipakai apa adanya)

Fortify aktif: login, reset password, 2FA, passkeys (`@laravel/passkeys`), profile, password confirmation. Views Inertia di `resources/js/pages/auth/*` & `resources/js/pages/settings/*`.

### Yang ditambahkan

**1. Konfigurasi Fortify** (`config/fortify.php`):
- `registration = false` (admin dibuat via seeder/tabel, tidak signup publik).
- `twoFactorAuthentication = true`, `resetPasswords = true`.
- `home = '/admin'` (redirect setelah login).

**2. Role via Spatie** (`app/Models/Role.php` extend `Spatie\Permission\Models\Role`):
- 3 role: Admin, Editor, Author (label dari enum `UserRole`).
- Permission per resource: `{resource}.viewAny/.create/.update/.delete/.deleteOwn`.
- Mapping role ↔ permission via `RolePermissionSeeder`.

**3. Middleware** (`app/Http/Middleware/`):
- Pakai middleware bawaan Spatie `role:...` atau `permission:...`.
- Tidak ada middleware admin terpisah — semua via permission.
- Custom middleware `permission:access-admin` untuk gerbang area admin (dimiliki ketiga role).

**4. Route group** (`routes/admin.php` baru):
```
/admin → middleware: ['auth', 'verified', 'permission:access-admin']
```

**5. Gate khusus** (di `AppServiceProvider::boot()` atau `AuthServiceProvider`):
```php
Gate::define('use-page-code-mode', fn (User $user) => $user->hasRole(UserRole::Admin));
```
Dicek di: backend (controller + form request) + frontend (toggle hidden via `auth.user.canUseCodeMode`).

**6. Policy skeleton** — 1 contoh `PostPolicy` di Fase 3 sebagai pattern. Policy lainnya dibuat saat resource CRUD-nya dibangun (Fase fitur).

### Matriks visibilitas (mapping ke permission)

| Resource | Admin | Editor | Author |
|---|---|---|---|
| Seluruh menu | ✓ | ✓ (selain Sistem) | terbatas |
| Konten | ✓ semua | ✓ semua | ✓ miliknya sendiri |
| Halaman + **mode code** | ✓ | ✓ (template saja) | ✗ |
| Media | ✓ | ✓ | ✓ |
| Interaksi (kontak/testimoni/penilaian) | ✓ | ✓ | ✗ |
| Sistem (user/pengaturan) | ✓ | ✗ | ✗ |

### Handle Inertia shared props

Tambah ke `HandleInertiaRequests::share()`:
- `auth.user.roles` (list nama role)
- `auth.user.permissions` (list permission untuk pengecekan UI)
- `auth.user.canUseCodeMode` (boolean dari Gate)
- `contentTypes` (list aktif, cache 1 jam) — saat authenticated
- `navCounts` (cache hitungan badge sidebar)

### Test Fase 3

- `LoginTest`: kredensial benar → redirect `/admin`.
- `RoleAccessTest`: Admin akses penuh; Editor tanpa menu Sistem; Author tanpa Interaksi.
- `CodeModeGateTest`: hanya Admin lulus Gate `use-page-code-mode`.

---

## 5. Routing & Locale + Walking Skeleton (Fase 4)

### Strategi locale

- **ID (default):** tanpa prefix — `/`, `/berita/slug-post`.
- **Locale lain:** prefix `/{locale}/...` — `/en/`, `/en/news/slug-post`.
- Locale valid di-cache dari tabel `languages` (`is_active = true`). Locale tidak valid → 404.
- Persistensi pilihan bahasa: cookie `locale` + link eksplisit. **Tidak** ada deteksi Accept-Language otomatis (URL harus stabil demi SEO).

### Middleware `SetLocale`

`app/Http/Middleware/SetLocale.php`:
1. Baca segment pertama URL.
2. Jika cocok locale non-default → set `app()->setLocale($code)`, simpan di request attribute.
3. Jika tidak → locale default ID.
4. Share `locale` ke Inertia.

Dipasang di luar grup admin (admin selalu default ID).

### Struktur route & urutan resolusi

```
routes/web.php:
  SetLocale middleware
  ├── /admin/*              → routes/admin.php (tanpa locale)
  ├── /{locale?}            → Public\HomeController@home
  ├── /{locale}/{type}      → Public\PostController@archive
  ├── /{locale}/{type}/{slug} → Public\PostController@show
  ├── /{locale}/{slug}      → Public\PageController@show  (catch-all custom page)
  └── fallback              → 404
```

**Urutan pencocokan catch-all:**
1. Segment-1 = locale valid non-default → strip, proses sisanya.
2. Segment-1 = `content_types.slug` → archive `/type`.
3. Segment-1 + segment-2 = type + `post_translations.slug` → single.
4. Segment-1 = `page_translations.slug` → custom page.
5. Tidak cocok → 404.

### Controller skeleton (Fase 4)

- `Public\HomeController@index` → render `public/home` dengan props: locale, site settings, post terbaru.
- `Public\PostController@index` → arsip per type + pagination.
- `Public\PostController@show` → single post + translation untuk locale aktif.
- `Public\PageController@show` → render `page_translations.content` (mode template atau HTML-sanitized untuk mode code — detail implementasi di Fase 6).

### Walking Skeleton Vertikal (gerbang pasca-Fase 4)

**Tujuan:** bukti end-to-end SEO + SSR + i18n + DB terhubung.

1. `ContentTypeSeeder` → type `berita` (slug `berita`, label EN `news`).
2. `DemoPostSeeder` → 1 post dengan translation ID + EN, status Published.
3. Route `/berita/{slug}` & `/en/news/{slug}` resolve post dari DB.
4. View SSR `resources/js/pages/public/post-show.tsx`:
   - `<Head>` meta title, description per-locale.
   - `<link rel="alternate" hreflang="id" href="...">` dan `hreflang="en"`.
   - Body: judul + isi dari DB.
5. Test vertikal `PublicPostShowSsrTest`:
   - GET `/berita/{slug}` → 200, judul ID, `<link hreflang="id">`.
   - GET `/en/news/{slug}` → 200, judul EN, `<link hreflang="en">`.
   - Post tanpa translation EN → fallback ke default ID dengan indikasi.

**Skeleton lulus** = tes di atas hijau + `curl /` mengembalikan HTML lengkap (bukan empty div Inertia).

### Penanganan locale internal

`app()->getLocale()` selalu 2-huruf (`id`, `en`). `Language::code` sama. `Language::current()` return model aktif.

### Test routing

- `LocaleRoutingTest`: `/` → locale ID; `/en/` → EN; `/fr/` → 404.
- `PostArchiveRouteTest`: `/berita` tampilkan arsip; `/en/news` sama.
- `CatchAllPageTest`: `/{slug}` yang page → render page.

---

## 6. Shell Admin (Fase 5)

### Layout

`resources/js/layouts/admin-layout.tsx` (basis `app-sidebar-layout.tsx` starter):
- **Sidebar** 6 grup: Dashboard, Konten, Halaman, Tampilan, Interaksi, Sistem.
- **Topbar**: nama user + avatar + dropdown (Profile, Appearance, Logout) — komponen starter.
- **Responsive**: sidebar collapse jadi drawer di mobile (shadcn `Sheet`).
- **Aksesibilitas**: skip-to-content, focus trap di drawer, ARIA landmark (`<nav>`, `<main>`).

### Navigasi jenis konten dinamis

Grup "Konten" membangkitkan sub-entri dari prop `contentTypes`:
```
Konten
├── Artikel         ← content_types slug=artikel
├── Berita
├── Pengumuman
├── ────────
├── Kategori
├── Tag
├── Galeri
└── Jenis konten
```
Tiap entri link `/admin/posts?type={slug}` (route `admin.posts.index` placeholder).

### Visibilitas menu per role

Filter di frontend via `auth.user.permissions`:
- **Admin**: semua grup.
- **Editor**: Dashboard, Konten, Halaman, Tampilan, Interaksi, Media (di grup Sistem).
- **Author**: Dashboard, Konten (milik sendiri), Media.
- Item "Sistem → Pengguna, Pengaturan, Bahasa, Gaya bahasa, AI configs, Kriteria penilaian" hanya Admin.

Pengecekan backend tetap via middleware `permission:` — visibilitas menu = UX, bukan security boundary.

### Konfigurasi menu

`resources/js/components/admin/sidebar-nav.ts`:
```ts
type NavItem = {
  label: string;        // i18n key
  href: string;         // via Wayfinder
  icon: LucideIcon;
  permission?: string;  // gate untuk tampil
  group: 'dashboard' | 'content' | 'pages' | 'appearance' | 'interaction' | 'system';
  dynamicFrom?: 'contentTypes';
};
```

### Dashboard ringkasan

`/admin` (`Admin\DashboardController`):
- Kartu statistik: total post, total page, total media, total contact messages (baru).
- List draft terbaru (5).
- List pesan kontak belum dibaca (5).
- Empty state jika belum ada data.

### Halaman placeholder per bagian

Komponen `ComingSoon` (ikon + label + tombol kembali) untuk semua route admin selain dashboard:

```
GET  /admin                       dashboard
GET  /admin/posts                 posts.index (placeholder)
GET  /admin/pages                 pages.index (placeholder)
GET  /admin/menus                 menus.index (placeholder)
GET  /admin/widgets               widgets.index (placeholder)
GET  /admin/contact-messages      contactMessages.index (placeholder)
GET  /admin/testimonials          testimonials.index (placeholder)
GET  /admin/ratings               ratings.index (placeholder)
GET  /admin/media                 media.index (placeholder di Fase 5; fungsional di Fase 7)
GET  /admin/users                 users.index (placeholder)
GET  /admin/settings              settings.index (placeholder)
GET  /admin/settings/ai           settings.ai.index (placeholder)
GET  /admin/settings/languages    settings.languages.index (placeholder)
```

### Test shell admin

- `AdminDashboardTest`: login Admin → `/admin` → 200, kartu statistik tampil.
- `SidebarDynamicTest`: sidebar berisi `Artikel`, `Berita`, `Pengumuman` dari seeder.
- `AdminRoleMenuVisibilityTest`: Editor tidak melihat "Sistem → Pengguna"; Author tidak melihat "Interaksi".

---

## 7. Region, SEO, Aksesibilitas (Fase 6)

### Region (cangkang halaman publik)

Layout `resources/js/layouts/public-layout.tsx`:
```
┌─────────────────────────────────────┐
│ Header (global, dari menus[HEADER]) │
├─────────────────────────────────────┤
│ Hero (per-halaman, opsional)        │
├──────────┬──────────────────────────┤
│ Widget   │  Konten utama            │
│ Sidebar  │  + Widget sebelum/sesudah│
│ (opsional│                          │
├──────────┴──────────────────────────┤
│ Footer (global, dari menus[FOOTER]) │
└─────────────────────────────────────┘
```

**Slot Inertia (props per-halaman):**
- `headerMenu`, `footerMenu` (cache 1 jam).
- `hero` opsional: `{ enabled, image, heading, subheading, ctaText, ctaLink }`.
- `sidebar` opsional: `{ enabled, widgets[] }`.
- `widgets`: `{ beforeContent[], afterContent[], sidebar[], footer[] }`.

**Hero:** gambar dari media library (referensi `media_id`), teks diterjemahkan. Mobile: tinggi berkurang, CTA full-width.

> **Catatan urutan:** Media library fungsional di Fase 7. Di Fase 6, region hero dirender dengan `hero.image` yang sudah ada di DB (kolom dari Fase 2) — gambar aktual diisi via upload Fase 7 atau dummy dari seeder. Struktur region tidak terblokir.

**Sidebar:** di mobile tumpuk di bawah konten (tidak disembunyikan).

**Widget rendering:** dispatcher `WidgetRenderer.tsx` — switch `widget.type` ke komponen. Pondasi: 1 tipe contoh (`HtmlWidget`).

### Pemilih bahasa

`LocaleSwitcher.tsx` di header — list locale aktif, link ke URL terjemahan yang sesuai. Aksesibel: `<nav aria-label="Language">`, focus ring jelas.

### SEO

**Server-side (props ke `<Head>` Inertia):**
- `meta_title`, `meta_description` per-bahasa (dari `*_translations`).
- Canonical URL absolut (pakai Boost `get-absolute-url`).
- Open Graph: `og:title/description/image/locale/type`.
- Twitter Card `summary_large_image`.
- **`hreflang`**: untuk tiap terjemahan yang ada + `hreflang="x-default"` ke default.

**Komponen:** `resources/js/components/seo/meta-head.tsx` menerima props SEO terstruktur, render semua tag via Inertia `<Head>`.

**Sitemap** (`spatie/laravel-sitemap`):
- `app/Console/Commands/GenerateSitemap.php` — generate `sitemap.xml` berisi: semua page published, semua post translation published, halaman home per locale.
- Scheduled: `->dailyAt('00:00')` di `routes/console.php`.
- URL absolut via Boost `get-absolute-url`.

**JSON-LD:**
- `Organization` di home.
- `WebSite` dengan `potentialAction` (SearchAction — untuk fitur pencarian nanti).
- `Article` di single post (headline, datePublished, author, image).
- `BreadcrumbList` di arsip & single.
- Komponen `JsonLd.tsx` render `<script type="application/ld+json">`.

**Core Web Vitals baseline:**
- SSR halaman publik.
- Image WebP + `loading="lazy"` + dimensi eksplisit.
- Font display swap.
- Tidak ada render-blocking JS besar (code-split Vite).

### Aksesibilitas (WCAG)

- **HTML semantik:** `<header>`, `<nav>`, `<main>`, `<aside>`, `<footer>`, `<article>`, `<section>`.
- **ARIA:** landmark roles, `aria-current="page"` untuk menu aktif, `aria-label` untuk nav.
- **Keyboard:** skip-to-content link, fokus terlihat (ring), trap di modal/drawer, return focus.
- **Kontras:** pakai token warna shadcn (lulus WCAG AA default); audit manual untuk warna kustom.
- **Form:** label eksplisit, `aria-describedby` untuk error, `aria-live` untuk pengumuman error.
- **Komponen:** semua interaktif pakai primitif Radix (shadcn) — aksesibel default.

### Sanitasi HTML (untuk mode code Page)

`app/Services/Html/Sanitizer.php` — memakai `stevebauman/purify`:
- Izinkan tag & atribut design system (div, section, span, class), buang `<script>`, `<style>`, atribut `on*`, `javascript:`, `data:` URLs di href/src.
- Allowlist class spesifik design system (untuk mode code + MARKUP_CONFORM).
- Dipanggil sebelum simpan `page_translations.content` saat `mode = Code`.

> Paket ditambahkan di Fase 1: `composer require stevebauman/purify`.

### Test Fase 6

- `SeoMetaTest`: GET `/berita/{slug}` → berisi `<title>`, `<meta name="description">`, `<link rel="canonical">`, `hreflang` ID + EN.
- `SitemapTest`: GET `/sitemap.xml` → 200, berisi URL post & page published.
- `JsonLdTest`: response berisi `<script type="application/ld+json">` dengan `@type: Article` di single.
- `HtmlSanitizerTest`: input `<script>` & `onclick` → dibuang; class design system tetap.
- `AccessibilitySmokeTest`: ada `<main>`, skip link, `aria-current` di menu aktif.

---

## 8. Media, AI, Design System (Fase 7)

### Media (`spatie/laravel-medialibrary`)

**Setup:**
- Publish config & migration bawaan Spatie.
- Model dengan media: `Post` (featured image), `Testimonial` (photo), `Page` (hero image), `SiteSettings` (logo, favicon) — trait `HasMedia` + interface `HasMedia`.
- Collection: `featured_image`, `hero_image`, `logo` dengan `singleFile()` untuk satu gambar.

**Konversi otomatis (WebP + responsif):**
- Trait `RegistersDefaultMediaConversions` (shareable) — daftar konversi via `registerMediaConversions()`:
  - `webp_large` 1920w, `webp_medium` 960w, `webp_small` 480w — format `webp`, quality 80.
  - `thumb` 400w webp untuk pratinjau admin.
- Trait `HasResponsiveImages` + `withResponsiveImages()` — `<picture>` srcset via `getMediaResponsiveUrls()`.
- **SVG dikecualikan:** cek `mime_type === 'image/svg+xml'` di `registerMediaConversions` → skip konversi, simpan file asli.
- **File asli disimpan:** default behavior Spatie — file asli tetap di disk.

**Disk:**
- Local: `storage/app/public` dengan `FILESYSTEM_DISK=public` + `php artisan storage:link`.
- Siap S3-compatible (opsional prod).

**UI library minimum:**
- Halaman `/admin/media` — grid thumbnail + tombol upload + alt editor inline + delete. Operasi inti saja.
- Komponen `MediaPicker.tsx` — modal picker yang mengembalikan `media_id` (dipakai editor Fase fitur).

**Upload pipeline:**
1. POST `/admin/media` (`MediaController@store`) — validasi mime (`jpg,jpeg,png,webp,svg`), size (`max:10240`).
2. Spatie `addMediaFromRequest()` → `toMediaCollection()`.
3. Job antrean `PerformMediaConversions` (Spatie default) jalankan konversi async.
4. Antrean: `database` driver (sudah ada), worker `php artisan queue:work`.

**Test:**
- `MediaUploadTest`: upload JPEG → tersimpan + konversi webp tersedia.
- `SvgExcludedTest`: upload SVG → tidak ada konversi, file asli tersimpan.
- `MediaDeletionTest`: delete → file fisik & record dihapus.

### AI (Laravel AI SDK)

**Setup:**
- `composer require laravel/ai` (Fase 1).
- Tabel `ai_configs` dengan `api_key` cast `encrypted`.
- Konfigurasi via `ai_configs` per-tugas (`task` enum):
  ```php
  AiConfig::for(AiTask::Translation) // scope where task = Translation
  → base_url, api_key (decrypted), model, system_prompt
  ```
- Service `app/Services/Ai/AiClient.php` — menerima `AiTask` enum, ambil konfigurasi dari DB, panggil Laravel AI SDK dengan base URL custom (OpenAI-compatible). Panggilan selalu di server.

**Tiga agen (infrastruktur):**
- Service class per tugas di `app/Services/Ai/Tasks/*`:
  - `TranslationTask` — **fungsional end-to-end**.
  - `ContentRefinementTask`, `MarkupConformTask` — skeleton (signature + metode `suggest()`, body return dummy atau throw `NotImplementedException`).

**TRANSLATION agent (end-to-end):**
- Endpoint admin: `POST /admin/ai/translate` (`AiController@translate`).
- Input: `{ source_locale, target_locale, entity_type, entity_id, field }`.
- Alur saran → tinjau:
  1. Ambil teks sumber dari DB.
  2. Panggil `TranslationTask::translate($text, $source, $target)`.
  3. Return hasil ke frontend sebagai **saran** (tidak auto-save).
  4. Frontend `useHttp` (Inertia v3) POST hasil review → `AiController@applyTranslation` simpan field.
- System prompt default: terjemahkan formal-natural, pertahankan markup HTML, output hanya terjemahan.
- UI editor penuh di Fase fitur; pondasi uji via **endpoint test/tinker**.

**Keamanan AI:**
- API key terenkripsi (cast).
- Hanya route admin yang bisa panggil (middleware `permission:access-admin`).
- Rate limit `throttle:30,1` di endpoint AI.
- Log panggilan (mask api_key).

**Test:**
- `AiConfigEncryptionTest`: simpan & ambil `api_key` terenkripsi.
- `TranslationTaskTest`: mock AI client → return saran teks; assert alur input → output.
- `AiControllerTest`: POST `/admin/ai/translate` dengan mock → response berisi saran, tidak auto-save.

### Design system & referensi komponen

**Katalog komponen** untuk `MARKUP_CONFORM`:
- File `docs/design-system/component-reference.md` (atau JSON `resources/docs/component-catalog.json`):
  - Daftar class design system (Tailwind token + shadcn pattern).
  - Markup contoh benar (Hero, Section, Card, Button, Grid, Quote).
  - Dipakai sebagai konteks system prompt `MARKUP_CONFORM` nanti.
- Pondasi: katalog minimal (Hero, Section, Card, Button, Grid); diperkaya saat komponen ditambah.

**Pola CSS/JS global** (untuk mode code Page, dipersiapkan):
- `resources/css/app.css` (Tailwind + theme) — sudah ada.
- `resources/js/app/global-components.ts` (skeleton): registry yang memindai DOM `[data-component="hero"]` dst. dan memasang perilaku. Body kosong untuk pondasi; dipakai penuh di Fase fitur.

### Test integrasi akhir

- `MediaUploadAndRenderTest`: upload → konversi webp → URL `srcset` tersedia untuk `<picture>`.
- `TranslationFlowTest`: proses terjemahan end-to-end (mock provider) → saran + apply ke DB.

---

## 9. Paket yang Ditambahkan di Fase 1

```
composer require spatie/laravel-permission
composer require spatie/laravel-medialibrary
composer require spatie/laravel-sitemap
composer require spatie/laravel-settings
composer require laravel/ai
composer require stevebauman/purify
```

Semua paket sesuai stack final PRD §10 (Spatie + Laravel AI SDK) dan tidak menambah dependensi di luar yang sudah ditetapkan.

---

## 10. Definisi Selesai (Pondasi)

Aplikasi berjalan dengan:
- ✅ PostgreSQL terhubung; skema DB lengkap & ter-seed.
- ✅ Login admin (3 role via Spatie) berfungsi; mode code ter-gate Admin.
- ✅ Routing berlapis (locale → type → slug → page → 404) jalan.
- ✅ Walking skeleton: 1 post dummy render SSR per-locale dengan `hreflang`.
- ✅ Shell admin: sidebar dinamis, dashboard, placeholder per bagian.
- ✅ Shell publik: header/footer global, region hero/sidebar/widget, SEO (meta/hreflang/sitemap/JSON-LD), aksesibilitas dasar.
- ✅ Media: upload + WebP otomatis + varian responsif + UI library minimum.
- ✅ AI: 3 agen ter-struktur; TRANSLATION fungsional end-to-end (pola saran → tinjau).
- ✅ Sanitasi HTML untuk mode code jalan.
- ✅ Design system katalog minimal tersedia sebagai konteks MARKUP_CONFORM.
- ✅ Seluruh test per fase hijau.

→ Pondasi siap dibangun fitur tanpa menyentuh struktur inti.

---

## 11. Di Luar Lingkup Pondasi

- Implementasi penuh tiap resource CRUD (Posts, Pages, Menus, Widgets, dll.) — Fase fitur.
- Editor visual konten & halaman, toggle bahasa di editor, tombol AI di UI editor.
- 2 agen AI lain (CONTENT_REFINEMENT, MARKUP_CONFORM) fungsional penuh — skeleton saja di pondasi.
- Komponen widget lengkap, JS global pemasang komponen untuk mode code — skeleton saja.
- **Custom fields per jenis konten (Open Item #3 PRD)** — ditunda sampai inti selesai (lihat PRD Lampiran A).
- Manajemen user UI lengkap, pengaturan AI/bahasa/gaya bahasa UI — placeholder di pondasi.
- UI editor Testimoni, Penilaian, Pesan Kontak — placeholder.
- Audit WCAG formal & optimasi Core Web Vitals lanjut — baseline saja di pondasi.

---

## 12. Referensi

- PRD v1.0: `docs/PRD-Website-Company-Profile-CMS-v1.0.md`
- Rencana fondasi tingkat tinggi: `docs/plans/Rencana-Fondasi-Teknis-CMS.md`
- Laravel Boost guidelines: `AGENTS.md`
