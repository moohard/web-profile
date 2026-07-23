# Penyelarasan CMS dengan PRD v1.1 — Gap Closure Fase 7-10

## TL;DR

> **Quick Summary**: Menutup seluruh gap PRD v1.1 yang tersisa (fase 7-10) — mengganti 9 route admin placeholder dengan modul fungsional (Users, Site Settings, Menu, Widget, Gallery, Contact, Testimonial, Rating) plus fitur interaksi publik (form kontak, testimoni, rating, floating WhatsApp) dan penutupan kualitas (dokumentasi + dual-DB CI).
>
> **Deliverables**:
> - Modul admin: Users CRUD, Site Settings (umum/SEO/kontak/sosial/footer/WhatsApp), Menu builder, Widget + placement, Gallery CRUD
> - Fitur interaksi publik: form kontak (+notifikasi email+anti-spam), submit testimoni, submit rating multi-kriteria, floating WhatsApp button
> - Moderasi admin: inbox pesan kontak, kurasi testimoni, kelola kriteria rating + agregasi
> - Penutupan: factory yang hilang, dokumentasi rekonsiliasi, dual-DB CI (SQLite + PostgreSQL) hijau
>
> **Estimated Effort**: XL
> **Parallel Execution**: YES - 4 waves
> **Critical Path**: Factory & Policy foundation → Settings hybrid extend → Admin CRUD modules → Public interaction endpoints → Quality closure

---

## KEY DECISIONS (locked / default)

> Diangkat dari review Metis. Default diterapkan; user dapat override sebelum eksekusi.

- **Anti-spam kontak**: honeypot + `RateLimiter` (named limiter baru) + validasi ketat. TANPA CAPTCHA (out of scope PRD).
- **Contact mail**: `Mailable implements ShouldQueue`, tujuan `contact_notification_email` (field settings baru) `?? config('mail.from.address')`.
- **Rating dedup**: `visitor_hash` server-side (hash IP+UA+salt), unique seumur hidup (bukan reset harian).
- **Testimonial**: pengunjung boleh submit publik → status default `Pending` → moderasi admin.
- **Menu builder**: hierarki maksimal 2 level + kontrol reorder (tombol naik/turun, BUKAN library drag-and-drop).
- **Widget MVP**: hanya `HtmlWidget` type; Template registry read-only (daftar template hardcode, tidak buat template baru dari UI).
- **Site Settings**: WAJIB perluas settings hybrid (`SiteSettings`/`SeoSettings`/`WhatsappSettings` + `setting_translations` untuk footer i18n) — 3 field existing tidak cukup untuk PRD.

### Out of Scope (permanen — dari Global Constraints roadmap)
Custom fields, revisioning, scheduling, bulk actions, comments, CAPTCHA, table editor, media translations penuh.

---

## Context

### Original Request
"review project, sesuaikan dengan PRD dan plan dan referensinya ada pada folder docs/" — user memilih lingkup: SEMUA fase 7-10 dalam satu plan penuh.

### Interview Summary
**Key Discussions**:
- Kondisi proyek: fase 1-6 roadmap PRD v1.1 SUDAH SELESAI (git `b9a4fa7`, 77 file test hijau). Fondasi, auth, konten (Posts/Pages/Kategori/Tag/ContentTypes), soft delete, media, AI, publik SEO semua terbangun.
- Gap = fase 7-10: 9 route admin masih `Inertia::render('admin/placeholder')`.
- Model + migrasi + enum + permission seeder untuk semua modul gap SUDAH ADA. Yang kurang: controller, FormRequest, Policy (kecuali Testimonial), UI React, endpoint publik.

**Research Findings**:
- Konvensi controller/request/action/policy terpetakan di `PostController`/`CategoryController` (lihat References tiap task).
- Konvensi UI admin kanonik: `resources/js/pages/admin/categories/index.tsx` + `languages/index.tsx` (useForm + Wayfinder `@/routes/*` + DataTable + Dialog + `Component.layout` breadcrumbs).
- Public layout region + shared props di `app/Support/PublicLayoutProps.php`; belum ada POST endpoint interaksi publik.

### Metis Review
**Identified Gaps** (addressed dalam plan):
- Factory hilang (`Gallery*`, `Rating`, `RatingScore`, `WidgetPlacement*`) → task factory di awal tiap slice.
- `AdminPlaceholderRoutesTest` harus di-update seiring penggantian route.
- Rate limit publik butuh named limiter baru (bukan reuse throttle AI).
- Form publik = named route eksplisit SEBELUM catch-all `PublicPathResolver`.
- `UserPolicy` dengan proteksi self-delete + last-admin WAJIB sebelum UI Users.
- Flush cache `PublicLayoutProps` saat mutasi menu/widget/settings layout.
- `rating_criteria_translations` (nama tabel non-standar) diikuti apa adanya, jangan "diperbaiki".
- Permission string pakai yang sudah ada di seeder (`users.*`, `menus.*`, dst) — jangan invent baru.

---

## Work Objectives

### Core Objective
Menyelaraskan implementasi CMS 100% dengan PRD v1.1 dengan menutup fase 7-10 sehingga tidak ada route admin placeholder tersisa dan seluruh fitur interaksi pengunjung berfungsi end-to-end.

### Concrete Deliverables
- Controller + FormRequest + Policy + Action untuk: User, SiteSettings, Menu, Widget, Gallery, ContactMessage, Testimonial, Rating/RatingCriterion
- Page React admin untuk tiap modul (ganti placeholder)
- Endpoint publik POST: `/kontak`, `/testimoni`, `/rating` + komponen form React
- Floating WhatsApp button ter-share ke `PublicLayout`
- Factory lengkap untuk semua model gap
- Dokumentasi rekonsiliasi + traceability matrix; CI dual-DB hijau

### Definition of Done
- [ ] `grep -r "admin/placeholder" routes/` → 0 hasil (kecuali test yang sengaja assert)
- [ ] `composer ci:check` → hijau (Pint, PHPStan, tests)
- [ ] `php artisan test` (SQLite) DAN `DB_CONNECTION=pgsql php artisan test` → hijau
- [ ] `npm run types:check` + `npm run build:ssr` → hijau (tsc, Vite client + SSR)

### Must Have
- Semua route admin fase 7-10 berfungsi (bukan placeholder)
- Form interaksi publik dengan anti-spam + notifikasi
- Matrix test 3 role (Admin/Editor/Author) per modul admin
- TDD RED-GREEN-REFACTOR tiap perubahan perilaku
- Komunikasi & komentar kode Bahasa Indonesia

### Must NOT Have (Guardrails)
- TIDAK invent permission string baru (pakai seeder existing)
- TIDAK bangun ulang modul fase 1-6 yang sudah selesai
- TIDAK pakai CAPTCHA, custom fields, revisioning, scheduling, bulk actions, comments, table editor
- TIDAK pakai library drag-and-drop untuk menu (tombol reorder saja)
- TIDAK buat widget type selain HtmlWidget di MVP ini
- TIDAK "perbaiki" nama tabel `rating_criteria_translations`
- TIDAK timpa settings AI/languages yang sudah real

### Spec Framework Integration
- **Detected Framework**: None (tidak ada `openspec/` atau `.specify/`)
- Sumber kebenaran: `docs/PRD-Website-Company-Profile-CMS-v1.1.md` + `docs/superpowers/plans/2026-07-23-prd-v1-1-remediation-roadmap.md`

---

## Verification Strategy (MANDATORY)

> **ZERO HUMAN INTERVENTION** - Semua verifikasi agent-executed.

### Test Decision
- **Infrastructure exists**: YES (Pest 4, 77 test files)
- **Automated tests**: TDD (RED → GREEN → REFACTOR) — konvensi proyek + mandat roadmap
- **Framework**: Pest 4 (`php artisan test --compact`)

### QA Policy
Setiap task menyertakan QA scenario agent-executed. Evidence ke `.omo/evidence/task-{N}-{slug}.{ext}`.
- **Admin UI**: Playwright — login role, navigasi, isi form, submit, assert DOM + toast
- **Public form**: Playwright + Bash (curl) — submit valid/invalid, assert DB + response
- **Backend/API**: Bash (curl) + `php artisan test` — status code + payload
- **Model/Action**: `php artisan test --filter` — unit assertion

---

## Execution Strategy

### Parallel Execution Waves

```
Wave 1 (Fondasi — factory, policy, settings extend, infra publik):
├── Task 1: Factory yang hilang (Gallery/Rating/Widget placement) [quick]
├── Task 2: UserPolicy (self-delete + last-admin guard) [ultrabrain]
├── Task 3: Policy Menu/Widget/Gallery/Contact/Rating [unspecified-high]
├── Task 4: Perluas SiteSettings hybrid + setting_translations [unspecified-high]
├── Task 5: Named RateLimiter publik + honeypot util [quick]
└── Task 6: Share WhatsappSettings ke PublicLayoutProps (backend/types-only) [unspecified-high]

Wave 2 (Admin fase 7-8 — semua modul admin, MAX PARALLEL):
├── Task 7: Users CRUD admin (controller + request + page React) [unspecified-high]
├── Task 8: Site Settings UI admin (controller + page tab groups) [unspecified-high]
├── Task 9: Gallery CRUD admin (galeri + gambar + caption i18n) [unspecified-high]
├── Task 10: Menu builder admin (item hierarkis + reorder) [unspecified-high]
├── Task 11: Widget CRUD + placement admin (Joomla-style) [unspecified-high]
└── Task 12: Template registry admin (read-only listing) [quick]

Wave 3 (Interaksi fase 9 — admin moderasi + publik):
├── Task 13: Contact form (publik submit + admin inbox + notif + anti-spam) [unspecified-high]
├── Task 14: Testimonial (publik submit → Pending + moderasi + tampil) [unspecified-high]
├── Task 15: Rating (bintang per kriteria + agregasi + kriteria admin) [unspecified-high]
└── Task 16: Floating WhatsApp button (komponen publik global + mount) [visual-engineering]

Wave 4 (Penutupan fase 10):
├── Task 17: Bersihkan placeholder + update AdminPlaceholderRoutesTest [unspecified-high]
└── Task 18: Dual-DB CI + quality closure + rekonsiliasi dokumentasi [unspecified-high]

Wave FINAL (setelah SEMUA task — 4 review paralel, lalu user okay):
├── Task F1: Plan compliance audit (oracle)
├── Task F2: Code quality review (unspecified-high)
├── Task F3: Real manual QA (unspecified-high)
└── Task F4: Scope fidelity check (deep)
-> Present results -> Get explicit user okay

Critical Path (cabang konvergen): Task 3/4/5 + Task 12 → Task 13 (Wave 3) → Task 17 → Task 18 → F1-F4 → user okay
Cabang paralel lain: Task 1/3/5 → Task 15; Task 6 → Task 16; Task 2 → Task 7. Seluruh Task 7-12 berjalan PARALEL di Wave 2 (tanpa dependency antar-task Wave 2); semua Task 7-16 harus selesai sebelum Task 17.
```

### Dependency Matrix

- **1**: Wave 1, no deps → blocks 9, 11, 15
- **2**: Wave 1, no deps → blocks 7
- **3**: Wave 1, no deps → blocks 9, 10, 11, 13, 15
- **4**: Wave 1, no deps → blocks 8, 13
- **5**: Wave 1, no deps → blocks 13, 14, 15
- **6**: Wave 1, no deps → blocks 16
- **7**: deps 2 → blocks 17
- **8**: deps 4 → blocks 17
- **9**: deps 1, 3 → blocks 17
- **10**: deps 3 → blocks 17
- **11**: deps 1, 3 → blocks 17
- **12**: Wave 2, no deps → blocks 13, 17
- **13**: deps 3, 4, 5, 12 → blocks 17
- **14**: deps 5 (TestimonialPolicy sudah ada) → blocks 17
- **15**: deps 1, 3, 5 → blocks 17
- **16**: deps 6 → blocks 17
- **17**: deps 7-16 (semua route diganti) → blocks 18
- **18**: deps 17

### Agent Dispatch Summary

- **Wave 1**: 6 tasks — T1/T5 → `quick`, T2 → `ultrabrain`, T3/T4/T6 → `unspecified-high`
- **Wave 2**: 6 tasks — T7/T8/T9/T10/T11 → `unspecified-high`, T12 → `quick`
- **Wave 3**: 4 tasks — T13/T14/T15 → `unspecified-high`, T16 → `visual-engineering`
- **Wave 4**: 2 tasks — T17/T18 → `unspecified-high`
- **FINAL**: 4 tasks — F1 → `oracle`, F2/F3 → `unspecified-high`, F4 → `deep`

---

## TODOs

- [ ] 1. Buat factory yang hilang untuk model gap

  **What to do**:
  - Buat `GalleryFactory`, `GalleryTranslationFactory`, `GalleryImageFactory`, `GalleryImageTranslationFactory`, `RatingFactory`, `RatingScoreFactory`, `WidgetPlacementFactory`, `WidgetPlacementTargetFactory`.
  - Ikuti pola factory existing (`PostFactory`, `CategoryFactory` di `database/factories/`).
  - Untuk model dengan translation (`Gallery`, `GalleryImage`): definisikan state helper bernama `withTranslation(?string $locale = null)` yang men-seed satu translation locale default (mirip pola factory Post/Category). QA memanggil helper ini secara eksplisit, jadi factory dasar TIDAK wajib auto-seed translation.
  - Tambahkan test di `tests/Feature/FactorySmokeTest.php` yang mem-`create()` tiap factory baru.

  **Must NOT do**:
  - Jangan ubah factory existing yang sudah hijau.
  - Jangan buat factory untuk model yang sudah punya (cek `database/factories/` dulu).

  **Recommended Agent Profile**:
  - **Category**: `quick` — Reason: Pembuatan factory mekanis mengikuti pola existing, low ambiguity.
  - **Skills**: [`pest-testing`, `laravel-best-practices`] — pest untuk smoke test, best-practices untuk konvensi factory Eloquent.

  **Parallelization**:
  - **Can Run In Parallel**: YES — **Parallel Group**: Wave 1
  - **Blocks**: Task 9 (Gallery), Task 15 (Rating), Task 11 (Widget placement)
  - **Blocked By**: None

  **References**:
  - `database/factories/PostFactory.php` — pola factory dengan translation state (copy struktur `definition()` + `configure()`).
  - `database/factories/CategoryFactory.php` — pola factory taksonomi + translation.
  - Struktur model (dari riset): `Gallery` fillable `['slug','is_active']`; `GalleryImage` `['gallery_id','path','sort_order']`; `Rating` `['comment','visitor_hash']`; `RatingScore` `['rating_id','criterion_id','score']` (`$timestamps=false`); `WidgetPlacement` `['widget_id','position','scope','sort_order']`; `WidgetPlacementTarget` `['placement_id','target_type','target_ref']`.
  - `tests/Feature/FactorySmokeTest.php` — tambahkan assert untuk factory baru.

  **Acceptance Criteria**:
  - [ ] `php artisan test --filter=FactorySmokeTest` → PASS (semua factory baru ter-`create()`)

  **QA Scenarios**:

  ```
  Scenario: Semua factory baru menghasilkan record valid
    Tool: Bash (php artisan test)
    Preconditions: Migrasi fresh (RefreshDatabase)
    Steps:
      1. Jalankan: php artisan test --filter=FactorySmokeTest --compact
      2. Assert output berisi "PASS" dan 0 failures
    Expected Result: Semua factory (Gallery*, Rating*, WidgetPlacement*) create tanpa error FK/constraint
    Failure Indicators: SQLSTATE constraint violation, "Class factory not found"
    Evidence: .omo/evidence/task-1-factory-smoke.txt

  Scenario: Factory state helper withTranslation menghasilkan translation
    Tool: Bash (php artisan tinker --execute)
    Preconditions: Language default ter-seed
    Steps:
      1. Jalankan tinker: 'Gallery::factory()->withTranslation()->create(); echo Gallery::first()->translations()->count();'
      2. Assert output >= 1
    Expected Result: State helper withTranslation() membuat translation locale default bersama parent
    Evidence: .omo/evidence/task-1-factory-translation.txt
  ```

  **Commit**: YES — `test(factory): add missing factories for gallery, rating, widget placement`
  - Pre-commit: `vendor/bin/pint --dirty && php artisan test --filter=FactorySmokeTest`

- [ ] 2. Buat UserPolicy dengan guard self-delete dan last-admin

  **What to do**:
  - Buat `app/Policies/UserPolicy.php` dengan method: `viewAny`, `view`, `create`, `update`, `delete`.
  - `delete`: TOLAK bila target adalah user sendiri (self-delete) DAN tolak bila target adalah Admin terakhir (last-admin guard — hitung jumlah user berrole Admin).
  - Pakai permission string existing dari seeder (`users.viewAny`, `users.create`, dst — verifikasi nama persis di `RolePermissionSeeder.php`).
  - RED dulu: tulis `tests/Feature/PostPolicyTest.php`-style untuk UserPolicy (self-delete ditolak, last-admin ditolak, admin biasa boleh hapus non-admin).

  **Must NOT do**:
  - Jangan invent permission baru; verifikasi ke seeder.
  - Jangan izinkan Editor/Author akses user management.

  **Recommended Agent Profile**:
  - **Category**: `ultrabrain` — Reason: Logika last-admin + self-delete punya edge case (race, hitung admin aktif) yang butuh reasoning teliti.
  - **Skills**: [`laravel-permission-development`, `pest-testing`, `laravel-best-practices`] — permission untuk role check, pest untuk TDD.

  **Parallelization**:
  - **Can Run In Parallel**: YES — **Parallel Group**: Wave 1
  - **Blocks**: Task 7 (UserController)
  - **Blocked By**: None

  **References**:
  - `app/Policies/PostPolicy.php:16,24,37,49,84` — pola policy role+owns; PostPolicy punya private `owns()`. Tiru struktur signature `(User $user, User $target)`.
  - `app/Policies/TestimonialPolicy.php:16,24,32,40,52` — pola permission-based murni via `$user->can()`.
  - `database/seeders/RolePermissionSeeder.php:59-86` — matrix permission; verifikasi string `users.*`.
  - `app/Models/User.php:36` — `HasRoles` trait; gunakan `$user->hasRole()` / `role()->where()` untuk hitung admin.
  - `app/Enums/UserRole.php` — enum role untuk konstanta Admin.

  **Acceptance Criteria**:
  - [ ] `tests/Feature/Admin/UserPolicyTest.php` dibuat
  - [ ] `php artisan test --filter=UserPolicyTest` → PASS

  **QA Scenarios**:

  ```
  Scenario: Admin tidak bisa hapus dirinya sendiri
    Tool: Bash (php artisan test)
    Preconditions: 2 user Admin ter-seed
    Steps:
      1. Jalankan test: assert UserPolicy@delete(adminA, adminA) === false
      2. Assert PASS
    Expected Result: Self-delete ditolak (false)
    Evidence: .omo/evidence/task-2-self-delete.txt

  Scenario: Admin terakhir tidak bisa dihapus
    Tool: Bash (php artisan test)
    Preconditions: Hanya 1 user berrole Admin
    Steps:
      1. Jalankan test: assert UserPolicy@delete(otherAdmin_simulated, lastAdmin) === false
      2. Assert PASS
    Expected Result: Last-admin guard mencegah penghapusan
    Failure Indicators: Policy return true saat hanya 1 admin
    Evidence: .omo/evidence/task-2-last-admin.txt
  ```

  **Commit**: YES — `feat(policy): add user policy with self-delete and last-admin guard`
  - Pre-commit: `vendor/bin/pint --dirty && php artisan test --filter=UserPolicyTest`

- [ ] 3. Buat Policy untuk Menu, Widget, Gallery, ContactMessage, Rating

  **What to do**:
  - Buat `MenuPolicy`, `WidgetPolicy`, `GalleryPolicy`, `ContactMessagePolicy`, `RatingCriterionPolicy` (TestimonialPolicy sudah ada — jangan buat ulang).
  - Pakai pola permission-based murni (`$user->can('menus.viewAny')` dst) mengikuti `TestimonialPolicy`.
  - Verifikasi string permission ke `RolePermissionSeeder.php` (`menus.*`, `widgets.*`, `galleries.*`, `contact.*`/`contact-messages.*`, `ratings.*`/`rating-criteria.*`).
  - RED: test tiap policy — Admin boleh, Editor/Author sesuai matrix (Menu/Widget = appearance → hanya Admin; Gallery = content → Editor boleh).

  **Must NOT do**:
  - Jangan buat ulang `TestimonialPolicy`.
  - Jangan beri Editor/Author akses appearance (menus/widgets).

  **Recommended Agent Profile**:
  - **Category**: `unspecified-high` — Reason: Lima policy sekaligus, butuh cross-check matrix permission tapi pola berulang.
  - **Skills**: [`laravel-permission-development`, `pest-testing`] — permission mapping + TDD.

  **Parallelization**:
  - **Can Run In Parallel**: YES — **Parallel Group**: Wave 1
  - **Blocks**: Task 9, 10, 11, 13, 15
  - **Blocked By**: None

  **References**:
  - `app/Policies/TestimonialPolicy.php:16-52` — template lengkap policy permission-based (copy per modul).
  - `app/Policies/CategoryPolicy.php:12-30` — pola semua-via-`can()`.
  - `database/seeders/RolePermissionSeeder.php:59-86` — matrix; verifikasi string permission.
  - Model target: `Menu`, `Widget`, `Gallery`, `ContactMessage`, `RatingCriterion` (semua sudah ada di `app/Models/`).

  **Acceptance Criteria**:
  - [ ] 5 file policy dibuat di `app/Policies/`
  - [ ] `php artisan test --filter=PolicyTest` → PASS untuk kelima modul

  **QA Scenarios**:

  ```
  Scenario: Editor ditolak akses Menu (appearance-only)
    Tool: Bash (php artisan test)
    Preconditions: User Editor ter-seed
    Steps:
      1. Test: assert MenuPolicy@viewAny(editor) === false
      2. Assert PASS
    Expected Result: Editor tidak punya akses appearance
    Evidence: .omo/evidence/task-3-editor-menu-denied.txt

  Scenario: Editor boleh kelola Gallery (content)
    Tool: Bash (php artisan test)
    Preconditions: User Editor ter-seed
    Steps:
      1. Test: assert GalleryPolicy@viewAny(editor) === true
      2. Assert PASS
    Expected Result: Editor punya akses konten
    Evidence: .omo/evidence/task-3-editor-gallery-allowed.txt
  ```

  **Commit**: YES — `feat(policy): add policies for menu, widget, gallery, contact, rating`
  - Pre-commit: `vendor/bin/pint --dirty && php artisan test --filter=Policy`

- [ ] 4. Perluas SiteSettings hybrid untuk kontak, sosial, Maps, footer i18n

  **What to do**:
  - Perluas `app/Settings/SiteSettings.php` dengan properti kontak (`address`, `phone`, `email`), sosial (`social_links` array), Google Maps (`maps_embed`), sesuai PRD §7 site_settings.
  - Tambah field teks i18n via `setting_translations` (footer text, tagline) — ikuti pola hybrid existing (`spatie/laravel-settings` non-teks + `setting_translations` teks).
  - Tambah `contact_notification_email` ke settings (dipakai Task 13).
  - Buat migrasi settings (`php artisan make:settings-migration` atau pola existing di `database/settings/`).
  - RED: perluas `tests/Feature/SettingsHybridTest.php`.

  **Must NOT do**:
  - Jangan ganti mekanisme hybrid yang sudah ada.
  - Jangan hapus properti settings existing (SEO/AI/WhatsApp).

  **Recommended Agent Profile**:
  - **Category**: `unspecified-high` — Reason: Menyentuh settings class + migrasi + i18n translation, butuh ketelitian pola hybrid.
  - **Skills**: [`laravel-best-practices`, `pest-testing`]

  **Parallelization**:
  - **Can Run In Parallel**: YES — **Parallel Group**: Wave 1
  - **Blocks**: Task 8 (Settings UI), Task 13 (Contact notification)
  - **Blocked By**: None

  **References**:
  - `app/Settings/SiteSettings.php` — properti existing; tambah, jangan ganti.
  - `app/Settings/SeoSettings.php`, `app/Settings/WhatsappSettings.php` — pola settings group.
  - `app/Models/SettingTranslation.php` — mekanisme teks i18n.
  - `tests/Feature/SettingsHybridTest.php` — pola test hybrid.
  - `database/settings/` — migrasi settings existing sebagai pola.
  - PRD v1.1 §7 (site_settings) — daftar field kontak/sosial/Maps/footer.

  **Acceptance Criteria**:
  - [ ] Migrasi settings dibuat & `php artisan migrate` sukses
  - [ ] `php artisan test --filter=SettingsHybridTest` → PASS

  **QA Scenarios**:

  ```
  Scenario: Settings kontak & sosial tersimpan dan terbaca
    Tool: Bash (php artisan tinker --execute)
    Preconditions: Migrasi settings jalan
    Steps:
      1. Set: app(SiteSettings::class)->phone = '021-xxx'; ->save();
      2. Baca ulang: echo app(SiteSettings::class)->phone;
    Expected Result: Output '021-xxx'
    Evidence: .omo/evidence/task-4-settings-contact.txt

  Scenario: Footer text i18n tersimpan per bahasa
    Tool: Bash (php artisan test)
    Steps:
      1. Jalankan SettingsHybridTest yang assert setting_translations footer per locale
    Expected Result: PASS
    Evidence: .omo/evidence/task-4-footer-i18n.txt
  ```

  **Commit**: YES — `feat(settings): expand site settings for contact, social, maps, footer i18n`
  - Pre-commit: `vendor/bin/pint --dirty && php artisan test --filter=SettingsHybridTest`

- [ ] 5. Definisikan named rate limiter untuk endpoint interaksi publik

  **What to do**:
  - Daftarkan named RateLimiter baru di `app/Providers/` (cek `AppServiceProvider` atau `RouteServiceProvider` existing) untuk: `contact-submit`, `rating-submit`, `testimonial-submit`.
  - Batas TERKUNCI (keputusan plan, bukan requirement PRD — PRD tidak menentukan angka; default konservatif anti-spam): `contact-submit` = 5/menit per-IP, `testimonial-submit` = 3/menit per-IP, `rating-submit` = 3/menit per-IP. Pisah dari throttle Fortify/AI.
  - RED: `tests/Feature/RateLimiterTest.php` yang mendaftarkan route test-only sementara (via `Route::post(...)->middleware('throttle:contact-submit')` di dalam test) untuk memverifikasi limiter terdaftar & mengembalikan 429 saat melewati batas — TANPA bergantung pada route Task 13.

  **Must NOT do**:
  - Jangan reuse limiter AI/Fortify.
  - Jangan pakai CAPTCHA (out of scope).
  - Jangan buat test yang bergantung pada route dari Task 13/14/15 (uji limiter terisolasi).

  **Recommended Agent Profile**:
  - **Category**: `quick` — Reason: Registrasi limiter singkat, pola standar Laravel.
  - **Skills**: [`laravel-best-practices`, `pest-testing`]

  **Parallelization**:
  - **Can Run In Parallel**: YES — **Parallel Group**: Wave 1
  - **Blocks**: Task 13 (Contact), Task 14 (Testimonial), Task 15 (Rating)
  - **Blocked By**: None

  **References**:
  - Cari limiter existing: `grep -rn "RateLimiter::for" app/` — verifikasi lokasi throttle Fortify/AI dan tiru struktur.
  - Laravel 13 docs (via Context7) — `RateLimiter::for()` + `Limit::perMinute()->by()`.

  **Acceptance Criteria**:
  - [ ] 3 named limiter terdaftar
  - [ ] Test 429 saat over-limit → PASS

  **QA Scenarios**:

  ```
  Scenario: Limiter contact-submit tolak request ke-6 dalam 1 menit (terisolasi, tanpa Task 13)
    Tool: Bash (php artisan test)
    Preconditions: Test mendefinisikan route sementara di dalam test body (`Route::post('/__throttle-test', fn () => response()->noContent())->middleware('throttle:contact-submit')`) — TIDAK bergantung route kontak Task 13
    Steps:
      1. Dalam `tests/Feature/RateLimiterTest.php`: daftarkan route sementara di atas dengan middleware `throttle:contact-submit`
      2. Kirim 5 POST ke `/__throttle-test` → assert semua 204
      3. POST ke-6 → assert status 429
    Expected Result: Request ke-6 ditolak 429 (limiter terdaftar & berfungsi, independen dari Task 13)
    Evidence: .omo/evidence/task-5-contact-throttle.txt
  ```

  **Commit**: YES — `feat(security): add named rate limiters for public interaction endpoints`
  - Pre-commit: `vendor/bin/pint --dirty && php artisan test --filter=RateLimit`

- [ ] 6. Share WhatsappSettings ke PublicLayoutProps (backend props saja)

  **What to do**:
  - Share `WhatsappSettings` (`number`, `enabled`, `default_message`) ke frontend publik via `PublicLayoutProps::base()` sebagai prop `whatsapp`.
  - Tambah tipe TypeScript `whatsapp` pada `PublicLayoutProps` (types) agar Task 16 dapat mengonsumsinya.
  - RED: test PHP bahwa prop `whatsapp` ter-share di response publik.
  - **BATAS SCOPE**: HANYA sisi backend + tipe. Komponen React + mount di layout = Task 16 (jangan buat komponen di sini).

  **Must NOT do**:
  - Jangan buat komponen `floating-whatsapp.tsx` (itu Task 16).
  - Jangan render apa pun di `public-layout.tsx` (itu Task 16).
  - Jangan hardcode nomor.

  **Recommended Agent Profile**:
  - **Category**: `unspecified-high` — Reason: Backend shared props + tipe TS + test; bukan pekerjaan UI.
  - **Skills**: [`laravel-best-practices`, `inertia-react-development`, `pest-testing`]

  **Parallelization**:
  - **Can Run In Parallel**: YES — **Parallel Group**: Wave 1
  - **Blocks**: Task 16 (komponen mengonsumsi prop `whatsapp`)
  - **Blocked By**: None

  **References**:
  - `app/Support/PublicLayoutProps.php:47-86` — `base()` untuk tambah shared props.
  - `app/Settings/WhatsappSettings.php` — field `number`, `enabled`, `default_message`.
  - `tests/Feature/PublicLayoutRegionTest.php` — pola test shared props publik.

  **Acceptance Criteria**:
  - [ ] Props `whatsapp` (`number`/`enabled`/`default_message`) ter-share di response publik
  - [ ] Tipe TS `whatsapp` tersedia di `PublicLayoutProps`
  - [ ] `php artisan test --filter=PublicLayout` → PASS

  **QA Scenarios**:

  ```
  Scenario: Props whatsapp ter-share di response publik saat enabled
    Tool: Bash (php artisan test)
    Preconditions: WhatsappSettings enabled=true, number='6281234'
    Steps:
      1. Jalankan test yang GET '/' lalu assert Inertia shared prop `whatsapp.number==='6281234'` & `whatsapp.enabled===true`
    Expected Result: Prop whatsapp hadir dengan nilai benar
    Evidence: .omo/evidence/task-6-whatsapp-props-enabled.txt

  Scenario: Prop whatsapp menandai disabled saat setting off
    Tool: Bash (php artisan test)
    Preconditions: WhatsappSettings enabled=false
    Steps:
      1. Jalankan test yang GET '/' lalu assert shared prop `whatsapp.enabled===false`
    Expected Result: Prop enabled=false (rendering ditangani Task 16)
    Evidence: .omo/evidence/task-6-whatsapp-props-disabled.txt
  ```

  **Commit**: YES — `feat(public): share whatsapp settings to public layout props`
  - Pre-commit: `vendor/bin/pint --dirty && php artisan test --filter=PublicLayout && npm run build`

- [ ] 7. Users CRUD admin (dengan UserPolicy self-delete & last-admin guard)

  **What to do**:
  - **CATATAN**: `UserPolicy` sudah dibuat penuh di Task 2 (dengan logika self-delete + last-admin). Task ini HANYA memakainya via `authorize()` — jangan buat ulang policy.
  - Buat `UserController` (index/create/store/edit/update/destroy) di `app/Http/Controllers/Admin/`.
  - Buat `UserRequest` (validasi email unik, password saat create, assign role via `syncRoles`).
  - Ganti placeholder route `/users` di `routes/admin.php` dengan resource controller (middleware `permission:admin.access-system`).
  - Buat page `resources/js/pages/admin/users/index.tsx` (meniru categories) + assign role.
  - RED: `tests/Feature/Admin/UserCrudTest.php` — CRUD + matrix 3 role + last-admin guard + self-delete guard.

  **Must NOT do**:
  - Jangan izinkan self-delete atau hapus admin terakhir.
  - Jangan invent permission baru (pakai `users.*` dari seeder).
  - Jangan expose ke Editor/Author.

  **Recommended Agent Profile**:
  - **Category**: `unspecified-high` — Reason: Logika authorization sensitif (last-admin, self-delete) + role assignment.
  - **Skills**: [`laravel-best-practices`, `laravel-permission-development`, `inertia-react-development`, `pest-testing`]

  **Parallelization**:
  - **Can Run In Parallel**: YES — **Parallel Group**: Wave 2
  - **Blocks**: None
  - **Blocked By**: Task 2 (UserPolicy dari Task 2, bukan Task 3)

  **References**:
  - `app/Http/Controllers/Admin/CategoryController.php` — pola controller CRUD.
  - `app/Http/Requests/Admin/PostRequest.php:18-27` — pola authorize().
  - `app/Policies/TestimonialPolicy.php` — pola policy permission-based.
  - `app/Models/User.php:33-50` — HasRoles trait, fillable, casts.
  - `database/seeders/RolePermissionSeeder.php:59-86` — permission `users.*` + matrix role.
  - `resources/js/pages/admin/categories/index.tsx` — pola page admin lengkap.
  - `routes/admin.php` — placeholder `/users` untuk diganti.
  - `tests/Feature/AdminPlaceholderRoutesTest.php` — hapus/update assert placeholder users.

  **Acceptance Criteria**:
  - [ ] Route `/admin/users` render controller (bukan placeholder)
  - [ ] `php artisan test --filter=UserCrudTest` → PASS
  - [ ] `AdminPlaceholderRoutesTest` diupdate & hijau
  - [ ] `npm run build` sukses

  **QA Scenarios**:

  ```
  Scenario: Admin buat user baru + assign role Editor
    Tool: Playwright
    Preconditions: Login sebagai Admin
    Steps:
      1. Navigate '/admin/users'
      2. Klik tombol 'Tambah', isi name='Budi', email='budi@example.com', password, role='Editor'
      3. Submit, assert toast sukses + baris 'budi@example.com' muncul di tabel
    Expected Result: User tersimpan dengan role Editor
    Evidence: .omo/evidence/task-7-user-create.png

  Scenario: Admin gagal hapus admin terakhir
    Tool: Bash (php artisan test)
    Steps:
      1. Test: 1 admin tersisa, coba destroy → assert 403/redirect error + user tetap ada
    Expected Result: Penghapusan ditolak, pesan error Bahasa Indonesia
    Evidence: .omo/evidence/task-7-last-admin-guard.txt

  Scenario: Editor tidak bisa akses /admin/users
    Tool: Bash (php artisan test)
    Steps:
      1. Login Editor, GET /admin/users → assert 403
    Expected Result: 403 Forbidden
    Evidence: .omo/evidence/task-7-editor-forbidden.txt
  ```

  **Commit**: YES — `feat(admin): users CRUD with role assignment and last-admin guard`
  - Pre-commit: `vendor/bin/pint --dirty && php artisan test --filter=UserCrudTest && npm run build`

- [ ] 8. Site Settings UI admin (umum, SEO, kontak, sosial, footer, WhatsApp)

  **What to do**:
  - Buat `SettingsController` (show + update) untuk grup settings non-AI/non-language, di `app/Http/Controllers/Admin/`.
  - Buat `SiteSettingsRequest` validasi field kontak/sosial/Maps/footer i18n + WhatsApp.
  - Ganti placeholder route `/settings` dengan controller (middleware `permission:admin.access-system`).
  - Buat page `resources/js/pages/admin/settings/index.tsx` — tab/section: Umum, SEO, Kontak & Sosial, Footer (i18n via LanguageTabs), WhatsApp.
  - Flush cache `PublicLayoutProps` saat settings layout berubah.
  - **Share + render footer publik (tanggung jawab task ini)**: tambahkan prop `footer` (footer text i18n + kontak + `social_links`) ke `PublicLayoutProps::base()`, lalu render di region footer `resources/js/layouts/public-layout.tsx` (konsumsi prop, jangan hardcode). Ini yang membuat footer text dari settings tampil di semua halaman publik.
  - RED: `tests/Feature/Admin/SiteSettingsUpdateTest.php` (update admin) + perluas `tests/Feature/PublicLayoutRegionTest.php` (assert prop `footer` ter-share di response publik).

  **Must NOT do**:
  - Jangan timpa settings AI/Languages (sudah punya UI sendiri).
  - Jangan expose ke non-Admin.

  **Recommended Agent Profile**:
  - **Category**: `unspecified-high` — Reason: Form multi-section + i18n + validasi + cache flush.
  - **Skills**: [`laravel-best-practices`, `inertia-react-development`, `tailwindcss-development`, `pest-testing`]

  **Parallelization**:
  - **Can Run In Parallel**: YES — **Parallel Group**: Wave 2
  - **Blocks**: None
  - **Blocked By**: Task 4 (settings fields expanded)

  **References**:
  - `app/Http/Controllers/Admin/AiConfigController.php` — pola controller settings existing.
  - `resources/js/pages/admin/settings/` — struktur settings existing (AI/languages).
  - `resources/js/components/admin/` — `LanguageTabs` untuk footer i18n.
  - `app/Support/PublicLayoutProps.php` — cache yang perlu di-flush + titik tambah prop `footer`.
  - `resources/js/layouts/public-layout.tsx:135-189` — region footer publik untuk mengonsumsi prop `footer`.
  - `tests/Feature/PublicLayoutRegionTest.php` — pola test shared props publik (assert prop `footer`).
  - `tests/Feature/SettingsHybridTest.php` — pola test settings.
  - `routes/admin.php` — placeholder `/settings`.

  **Acceptance Criteria**:
  - [ ] Route `/admin/settings` render controller
  - [ ] Prop `footer` ter-share di response publik + dirender di `public-layout.tsx`
  - [ ] `php artisan test --filter=SiteSettingsUpdateTest` → PASS
  - [ ] `php artisan test --filter=PublicLayoutRegion` → PASS
  - [ ] `npm run build` sukses

  **QA Scenarios**:

  ```
  Scenario: Admin update alamat kontak & footer text
    Tool: Playwright
    Preconditions: Login Admin
    Steps:
      1. Navigate '/admin/settings'
      2. Isi field phone='021-555', footer text ID='Hak cipta 2026'
      3. Submit, assert toast sukses
      4. Navigate '/' → assert footer memuat 'Hak cipta 2026'
    Expected Result: Settings tersimpan & tampil di publik
    Evidence: .omo/evidence/task-8-settings-update.png

  Scenario: Editor ditolak akses settings
    Tool: Bash (php artisan test)
    Steps:
      1. Login Editor, GET /admin/settings → assert 403
    Expected Result: 403
    Evidence: .omo/evidence/task-8-editor-forbidden.txt
  ```

  **Commit**: YES — `feat(admin): site settings management UI`
  - Pre-commit: `vendor/bin/pint --dirty && php artisan test --filter=SiteSettingsUpdateTest && npm run build`

- [ ] 9. Gallery CRUD admin (galeri + gambar + caption i18n)

  **What to do**:
  - Buat `GalleryController` (index/create/store/edit/update/destroy) + `GalleryRequest`.
  - Action `SyncGalleryImages` (tambah/hapus/urut gambar + caption i18n) dengan transaksi.
  - Ganti placeholder route `/galleries` dengan controller (middleware konten).
  - Page `resources/js/pages/admin/galleries/index.tsx` + editor gambar (MediaPicker existing) + reorder.
  - RED: `tests/Feature/Admin/GalleryCrudTest.php`.

  **Must NOT do**:
  - Jangan bangun ulang MediaPicker (pakai existing).
  - Jangan tambah media translations penuh (out of scope) — caption saja.

  **Recommended Agent Profile**:
  - **Category**: `unspecified-high` — Reason: CRUD + relasi gambar + i18n caption + reorder.
  - **Skills**: [`laravel-best-practices`, `medialibrary-development`, `inertia-react-development`, `pest-testing`]

  **Parallelization**:
  - **Can Run In Parallel**: YES — **Parallel Group**: Wave 2
  - **Blocks**: None
  - **Blocked By**: Task 1 (Gallery factories), Task 3 (GalleryPolicy)

  **References**:
  - `app/Models/Gallery.php`, `app/Models/GalleryImage.php` — fillable, relasi, HasTranslations.
  - `app/Actions/Categories/UpdateCategory.php:23-53` — pola Action transaksi + lockForUpdate.
  - `app/Http/Controllers/Admin/PostController.php` — pola controller kompleks + media.
  - `resources/js/components/` — MediaPicker existing (dipakai posts).
  - `routes/admin.php` — placeholder `/galleries`.

  **Acceptance Criteria**:
  - [ ] Route `/admin/galleries` render controller
  - [ ] `php artisan test --filter=GalleryCrudTest` → PASS
  - [ ] `npm run build` sukses

  **QA Scenarios**:

  ```
  Scenario: Admin buat galeri dengan 2 gambar + caption
    Tool: Playwright
    Preconditions: Login Admin, ada media di library
    Steps:
      1. Navigate '/admin/galleries/create'
      2. Isi slug='acara-2026', pilih 2 gambar via MediaPicker, isi caption ID
      3. Submit, assert toast + galeri muncul di index
    Expected Result: Galeri + 2 gambar + caption tersimpan
    Evidence: .omo/evidence/task-9-gallery-create.png

  Scenario: Hapus galeri membersihkan gambar terkait
    Tool: Bash (php artisan test)
    Steps:
      1. Buat galeri 2 gambar, destroy, assert gallery_images terkait terhapus
    Expected Result: Cascade bersih
    Evidence: .omo/evidence/task-9-gallery-delete.txt
  ```

  **Commit**: YES — `feat(admin): gallery CRUD with images and i18n captions`
  - Pre-commit: `vendor/bin/pint --dirty && php artisan test --filter=GalleryCrudTest && npm run build`

- [ ] 10. Menu builder admin (menu + item hierarkis + reorder)

  **What to do**:
  - Buat `MenuController` + `MenuItemController` (atau nested), `MenuRequest`/`MenuItemRequest`. **`MenuPolicy` dikonsumsi dari Task 3 via `authorize()` — jangan buat ulang.**
  - Action `SyncMenuItems` (item hierarkis max 2 level + reorder via `sort_order` + label i18n) transaksi.
  - Target item via `link_type` + `link_ref`: PAGE / CONTENT_ARCHIVE / CONTENT_SINGLE / URL (enum `LinkType` existing).
  - Ganti placeholder route `/menus` dengan controller (middleware `permission:admin.access-appearance`).
  - Page `resources/js/pages/admin/menus/index.tsx` — nested list + tombol reorder (bukan DnD library).
  - Flush cache `PublicLayoutProps` saat menu berubah.
  - RED: `tests/Feature/Admin/MenuCrudTest.php`.

  **Must NOT do**:
  - Jangan pakai library drag-and-drop (reorder pakai tombol naik/turun).
  - Jangan lebihi hierarki 2 level.
  - Jangan invent permission (pakai `menus.*`).

  **Recommended Agent Profile**:
  - **Category**: `unspecified-high` — Reason: Struktur hierarkis + polymorphic link target + i18n + cache.
  - **Skills**: [`laravel-best-practices`, `inertia-react-development`, `pest-testing`]

  **Parallelization**:
  - **Can Run In Parallel**: YES — **Parallel Group**: Wave 2
  - **Blocks**: None
  - **Blocked By**: Task 3 (MenuPolicy skeleton)

  **References**:
  - `app/Models/Menu.php`, `app/Models/MenuItem.php` — fillable, relasi children/parent, LinkType cast.
  - `app/Enums/LinkType.php` — 4 case target.
  - `app/Support/PublicLayoutProps.php:47-86` — base() consume headerMenu/footerMenu + cache.
  - `tests/Feature/Public/PublicMenuTest.php` — pola test menu publik (jangan regresi).
  - `app/Actions/Categories/UpdateCategory.php` — pola Action + ensureNoCycle (relevan untuk hierarki).
  - `routes/admin.php` — placeholder `/menus`.

  **Acceptance Criteria**:
  - [ ] Route `/admin/menus` render controller
  - [ ] `php artisan test --filter=MenuCrudTest` → PASS
  - [ ] `PublicMenuTest` tetap hijau (tanpa regresi)
  - [ ] `npm run build` sukses

  **QA Scenarios**:

  ```
  Scenario: Admin buat menu header + item nested ke Page
    Tool: Playwright
    Preconditions: Login Admin, ada Page terbit
    Steps:
      1. Navigate '/admin/menus'
      2. Buat menu location='Header', tambah item link_type='PAGE' pilih page, tambah sub-item
      3. Submit, assert toast + struktur nested tampil
      4. Navigate '/' → assert header menu memuat item baru
    Expected Result: Menu + item hierarkis tersimpan & tampil publik
    Evidence: .omo/evidence/task-10-menu-create.png

  Scenario: Reorder item mengubah urutan tampil
    Tool: Bash (php artisan test)
    Steps:
      1. Buat 2 item, panggil reorder, assert sort_order tertukar
    Expected Result: Urutan berubah
    Evidence: .omo/evidence/task-10-menu-reorder.txt
  ```

  **Commit**: YES — `feat(admin): menu builder with hierarchical items`
  - Pre-commit: `vendor/bin/pint --dirty && php artisan test --filter=MenuCrudTest && npm run build`

- [ ] 11. Widget CRUD + placement admin (Joomla-style)

  **What to do**:
  - Buat `WidgetController` + `WidgetRequest`. **`WidgetPolicy` dikonsumsi dari Task 3 via `authorize()` — jangan buat ulang.**
  - Action `SyncWidgetPlacements` (position + scope + target via `WidgetPlacementTarget`) transaksi.
  - MVP widget type: **HtmlWidget only** (title/content i18n).
  - Ganti placeholder route `/widgets` dengan controller (middleware appearance).
  - Page `resources/js/pages/admin/widgets/index.tsx` — CRUD widget + placement (position/scope/target).
  - Flush cache `PublicLayoutProps` saat widget/placement berubah.
  - RED: `tests/Feature/Admin/WidgetCrudTest.php`.

  **Must NOT do**:
  - Jangan tambah widget type selain HtmlWidget (MVP).
  - Jangan invent permission (pakai `widgets.*`).

  **Recommended Agent Profile**:
  - **Category**: `unspecified-high` — Reason: CRUD + placement matrix (position/scope/target) + i18n + cache.
  - **Skills**: [`laravel-best-practices`, `inertia-react-development`, `pest-testing`]

  **Parallelization**:
  - **Can Run In Parallel**: YES — **Parallel Group**: Wave 2
  - **Blocks**: None
  - **Blocked By**: Task 1 (WidgetPlacement factories), Task 3 (WidgetPolicy skeleton)

  **References**:
  - `app/Models/Widget.php`, `app/Models/WidgetPlacement.php`, `app/Models/WidgetPlacementTarget.php` — fillable, enum cast.
  - `app/Enums/WidgetPosition.php`, `app/Enums/PlacementScope.php` — case position/scope.
  - `resources/js/components/public/widget-renderer.tsx:14-19` — HtmlWidget renderer existing.
  - `tests/Feature/Public/WidgetScopeTest.php` — pola test scope (jangan regresi).
  - `app/Support/PublicLayoutProps.php` — region() consume widget + cache.
  - `routes/admin.php` — placeholder `/widgets`.

  **Acceptance Criteria**:
  - [ ] Route `/admin/widgets` render controller
  - [ ] `php artisan test --filter=WidgetCrudTest` → PASS
  - [ ] `WidgetScopeTest` tetap hijau
  - [ ] `npm run build` sukses

  **QA Scenarios**:

  ```
  Scenario: Admin buat HtmlWidget + tempatkan di sidebar semua halaman
    Tool: Playwright
    Preconditions: Login Admin
    Steps:
      1. Navigate '/admin/widgets'
      2. Buat widget type='Html', title/content i18n, placement position='Sidebar' scope='All'
      3. Submit, assert toast
      4. Navigate '/' → assert widget tampil di sidebar
    Expected Result: Widget tersimpan & tampil sesuai placement
    Evidence: .omo/evidence/task-11-widget-create.png

  Scenario: Widget scoped ke archive tidak muncul di home
    Tool: Bash (php artisan test)
    Steps:
      1. Buat widget scope CONTENT_ARCHIVE, GET home → assert tidak ada; GET archive → assert ada
    Expected Result: Scope dihormati
    Evidence: .omo/evidence/task-11-widget-scope.txt
  ```

  **Commit**: YES — `feat(admin): widget CRUD with joomla-style placements`
  - Pre-commit: `vendor/bin/pint --dirty && php artisan test --filter=WidgetCrudTest && npm run build`

- [ ] 12. Template registry admin (read-only listing)

  **What to do**:
  - Verifikasi/lengkapi template registry existing (dari remediation-06) — expose daftar template arsip/single/page read-only di admin.
  - **Route (LOCKED)**: `GET /admin/templates` (name `admin.templates.index`, middleware `permission:admin.access-system`), controller `Admin/TemplateRegistryController@index`, page `resources/js/pages/admin/templates/index.tsx` (read-only). Tambah item sidebar grup `system`.
  - RED: `tests/Feature/Admin/TemplateRegistryTest.php` (jika belum tercakup PageTemplateUiContractTest).

  **Must NOT do**:
  - Jangan buat editor template (registry read-only saja).
  - Jangan duplikasi logika PageTemplate existing.

  **Recommended Agent Profile**:
  - **Category**: `quick` — Reason: Sebagian besar sudah ada dari remediation-06; tinggal expose read-only.
  - **Skills**: [`laravel-best-practices`, `inertia-react-development`, `pest-testing`]

  **Parallelization**:
  - **Can Run In Parallel**: YES — **Parallel Group**: Wave 2
  - **Blocks**: Task 13 (menyediakan template registry key `contact`)
  - **Blocked By**: None

  **References**:
  - `tests/Feature/PageTemplateUiContractTest.php` — kontrak template existing.
  - `tests/Feature/Admin/PageEditorModeTest.php` — pemilihan template di editor page.
  - `app/Http/Controllers/Admin/PageController.php` — konsumen template key.
  - `docs/superpowers/plans/2026-07-23-remediation-06-page-template-ai-preview.md` — konteks registry.

  **Acceptance Criteria**:
  - [ ] Route `GET /admin/templates` (name `admin.templates.index`, middleware `permission:admin.access-system`) terdaftar & render read-only
  - [ ] Daftar template tampil read-only di admin
  - [ ] `php artisan test --filter=Template` → PASS
  - [ ] `npm run build` sukses

  **QA Scenarios**:

  ```
  Scenario: Admin lihat daftar template tersedia
    Tool: Playwright
    Preconditions: Login Admin
    Steps:
      1. Navigate ke '/admin/templates'
      2. Assert daftar template arsip/single/page tampil dengan key & label
    Expected Result: Registry read-only tampil lengkap
    Evidence: .omo/evidence/task-12-template-registry.png

  Scenario: Registry konsisten dengan template yang dipakai editor page
    Tool: Bash (php artisan test)
    Steps:
      1. Assert daftar registry == template key yang valid di PageController
    Expected Result: Konsisten
    Evidence: .omo/evidence/task-12-template-consistency.txt
  ```

  **Commit**: YES — `feat(admin): read-only template registry listing`
  - Pre-commit: `vendor/bin/pint --dirty && php artisan test --filter=Template && npm run build`

- [ ] 13. Contact form (publik submit + admin inbox + notifikasi + anti-spam)

  **What to do**:
  - Public: `ContactController@store` (route POST eksplisit sebelum catch-all di `routes/web.php`), `ContactRequest` (validasi ketat + honeypot), throttle via named limiter `contact-submit` (Task 5).
  - Action `StoreContactMessage` transaksi → simpan `ContactMessage` status `New`.
  - Notifikasi email: `Mailable` class `ContactMessageMail implements ShouldQueue` (queued), dikirim via `Mail::to($alamat)->queue(new ContactMessageMail(...))` (WAJIB `queue()`, BUKAN `send()` — agar benar-benar masuk antrean) ke `contact_notification_email ?? config('mail.from.address')`. Test assert `Mail::fake()` + `Mail::assertQueued(ContactMessageMail::class)`.
  - Komponen publik form kontak `resources/js/components/public/contact-form.tsx`.
  - **Lokasi mount (LOCKED)**: form dirender di halaman publik `page-show.tsx` bila `PageTranslation` memakai template registry key `contact` (Task 12). Seeder menyediakan satu Page publik ber-slug `kontak` dengan template `contact` untuk QA deterministik. Endpoint form POST ke named route `contact.store` (`/kontak` POST), independen dari lokasi mount.
  - Admin: `Admin/ContactMessageController` (index inbox + show + update status + destroy), ganti placeholder `/contact-messages`, page `resources/js/pages/admin/contact-messages/index.tsx`.
  - Authorization: **konsumsi `ContactMessagePolicy` dari Task 3** via `authorize()` — jangan buat ulang policy.
  - RED: `tests/Feature/Public/ContactSubmitTest.php` + `tests/Feature/Admin/ContactInboxTest.php`.

  **Must NOT do**:
  - Jangan pakai CAPTCHA (honeypot + throttle + validasi saja).
  - Jangan kirim notifikasi sinkron (wajib `ShouldQueue`).
  - Jangan taruh route setelah catch-all publik.

  **Recommended Agent Profile**:
  - **Category**: `unspecified-high` — Reason: Lintas publik+admin, notifikasi queue, anti-spam, policy.
  - **Skills**: [`laravel-best-practices`, `inertia-react-development`, `pest-testing`]

  **Parallelization**:
  - **Can Run In Parallel**: YES — **Parallel Group**: Wave 3
  - **Blocks**: None
  - **Blocked By**: Task 3 (ContactMessagePolicy), Task 4 (settings field email), Task 5 (rate limiter), Task 12 (template registry key `contact`)

  **References**:
  - `app/Models/ContactMessage.php` — fillable, ContactStatus cast.
  - `app/Enums/ContactStatus.php` — New/Read/Archived.
  - `routes/web.php:40-56` — catch-all + `$reserved` (tambah route sebelum ini).
  - `app/Http/Controllers/Admin/CategoryController.php` — pola controller inline admin.
  - `app/Http/Controllers/Public/HomeController.php:67-80` — pola merge PublicLayoutProps.
  - `app/Http/Controllers/Admin/DashboardController.php` — sudah hitung Contact `New` (jangan regresi).

  **Acceptance Criteria**:
  - [ ] POST kontak simpan `ContactMessage` + `Mail::fake()->assertQueued(ContactMessageMail::class)`
  - [ ] Honeypot terisi → ditolak; throttle → 429 setelah limit
  - [ ] Admin inbox list + ubah status + hapus
  - [ ] `php artisan test --filter=Contact` → PASS
  - [ ] `npm run build` sukses

  **QA Scenarios**:

  ```
  Scenario: Pengunjung kirim pesan kontak valid
    Tool: Playwright
    Preconditions: Seeder Page ber-slug 'kontak' template 'contact' ada
    Steps:
      1. Navigate ke '/kontak'
      2. Isi input[name='name']='Budi', input[name='email']='budi@example.com', textarea[name='message']='Halo', biarkan input[name='website'] (honeypot) kosong
      3. Klik button[type='submit'], assert teks sukses tampil
    Expected Result: Pesan tersimpan, ContactMessageMail ter-queue
    Evidence: .omo/evidence/task-13-contact-submit.png

  Scenario: Bot mengisi honeypot ditolak
    Tool: Bash (curl/php artisan test)
    Steps:
      1. POST dengan field honeypot terisi → assert 422/redirect tanpa simpan
    Expected Result: Ditolak, tidak tersimpan
    Evidence: .omo/evidence/task-13-contact-honeypot.txt

  Scenario: Submit kontak valid meng-queue ContactMessageMail
    Tool: Bash (php artisan test)
    Preconditions: Test pakai Mail::fake() di ContactSubmitTest
    Steps:
      1. Jalankan `php artisan test --filter=ContactSubmitTest --compact`
      2. Test POST /kontak data valid → assert redirect/200 + ContactMessage tersimpan
      3. Test assert `Mail::assertQueued(ContactMessageMail::class)` (1x) ke alamat contact_notification_email
    Expected Result: Mail ter-queue tepat 1x, record tersimpan, test PASS
    Failure Indicators: assertQueued gagal (0 mail), mail dikirim sinkron (bukan queued)
    Evidence: .omo/evidence/task-13-contact-mail-queued.txt
  ```

  **Commit**: YES — `feat(contact): public form, admin inbox, queued notification, anti-spam`
  - Pre-commit: `vendor/bin/pint --dirty && php artisan test --filter=Contact && npm run build`

- [ ] 14. Testimonial (publik submit → Pending + admin moderasi + tampil publik)

  **What to do**:
  - Public: `TestimonialController@store` (route POST eksplisit) → status `Pending`, throttle via named limiter `testimonial-submit` (Task 5).
  - Action `StoreTestimonial` (+ optional photo via media collection existing).
  - Admin: `Admin/TestimonialController` (index + approve + destroy + reorder), ganti placeholder `/testimonials`, page admin. **Semantik moderasi (LOCKED, karena enum hanya `Pending`/`Approved`)**: "approve" = ubah status `Pending`→`Approved`; "reject" = `destroy()` (hapus submission permanen). TIDAK ada status `Rejected` — jangan tambah enum case baru.
  - `TestimonialPolicy` sudah ada — pakai (permission `testimonials.*`).
  - Public display + form: komponen `resources/js/components/public/testimonials.tsx` (list hanya status `Approved`) + `testimonial-form.tsx`. Lokasi kanonik: seed Page ber-slug `testimoni` (template `testimonials`) yang me-render kedua komponen. Route POST publik: `/testimoni`.
  - RED: `tests/Feature/Public/TestimonialSubmitTest.php` + `tests/Feature/Admin/TestimonialModerationTest.php`.

  **Must NOT do**:
  - Jangan tampilkan testimoni non-Approved di publik.
  - Jangan bypass moderasi (submit publik selalu Pending).

  **Recommended Agent Profile**:
  - **Category**: `unspecified-high` — Reason: Publik+admin+media+moderasi state machine.
  - **Skills**: [`laravel-best-practices`, `medialibrary-development`, `inertia-react-development`, `pest-testing`]

  **Parallelization**:
  - **Can Run In Parallel**: YES — **Parallel Group**: Wave 3
  - **Blocks**: None
  - **Blocked By**: Task 5 (rate limiter)

  **References**:
  - `app/Models/Testimonial.php` — fillable, TestimonialStatus, media collection `photo`.
  - `app/Enums/TestimonialStatus.php` — Pending/Approved (hanya dua status ini).
  - `app/Policies/TestimonialPolicy.php` — sudah ada, pakai.
  - `app/Http/Controllers/Admin/PostController.php` — pola media + status.
  - `routes/web.php` — catch-all (route sebelum ini).

  **Acceptance Criteria**:
  - [ ] Submit publik → status `Pending`
  - [ ] Admin approve → `Approved`, tampil publik
  - [ ] Admin reject → submission `destroy()` (hilang dari DB), tidak tampil publik
  - [ ] Non-`Approved` tidak tampil publik
  - [ ] `php artisan test --filter=Testimonial` → PASS
  - [ ] `npm run build` sukses

  **QA Scenarios**:

  ```
  Scenario: Pengunjung submit testimoni lalu admin approve
    Tool: Playwright
    Preconditions: Login Admin di tab terpisah
    Steps:
      1. (Publik) Navigate `/testimoni`, submit testimoni author='Sari' content='Bagus'
      2. (Admin) Navigate '/admin/testimonials', assert item Pending, klik Approve
      3. (Publik) Navigate `/testimoni`, assert 'Sari' tampil di daftar
    Expected Result: Alur moderasi Pending→Approved berhasil
    Evidence: .omo/evidence/task-14-testimonial-flow.png

  Scenario: Testimoni Pending tak tampil publik
    Tool: Bash (php artisan test)
    Steps:
      1. Buat testimoni Pending, GET halaman publik → assert tidak muncul
    Expected Result: Hanya Approved tampil
    Evidence: .omo/evidence/task-14-testimonial-pending.txt

  Scenario: Admin reject menghapus submission
    Tool: Bash (php artisan test)
    Steps:
      1. Buat testimoni Pending (id=X)
      2. Admin panggil aksi reject (DELETE /admin/testimonials/X)
      3. Assert assertDatabaseMissing('testimonials', ['id' => X])
    Expected Result: Reject = destroy; record hilang dari DB, tidak tampil publik
    Evidence: .omo/evidence/task-14-testimonial-reject.txt
  ```

  **Commit**: YES — `feat(testimonial): public submit, admin moderation, public display`
  - Pre-commit: `vendor/bin/pint --dirty && php artisan test --filter=Testimonial && npm run build`

- [ ] 15. Rating (penilaian bintang per kriteria + agregasi + kriteria admin)

  **What to do**:
  - Public: `RatingController@store` (route POST eksplisit `/rating` sebelum catch-all) → simpan `Rating` + `RatingScore` per kriteria, dedup via `visitor_hash` (server-side, unique lifetime), throttle via named limiter `rating-submit` (Task 5).
  - Objek yang dinilai: **website secara keseluruhan** (site-wide rating, bukan per-post) — agregasi global per kriteria. (Keputusan terkunci: PRD §10 menyebut "penilaian website", bukan penilaian konten.)
  - Action `StoreRating` transaksi (hitung/simpan skor, cegah duplikat visitor_hash).
  - Admin (kriteria): `Admin/RatingCriterionController` (kelola kriteria i18n + aktif/urutan), ganti placeholder `/rating-criteria`, page admin. Kriteria seed 5 sudah ada — kelola bukan seed ulang.
  - Admin (ratings dashboard): `Admin/RatingController@index` (read-only) — ganti placeholder route `/ratings` (`ratings.index`, middleware `permission:ratings.viewAny`), page `resources/js/pages/admin/ratings/index.tsx` menampilkan agregasi rata-rata per kriteria + jumlah total penilai + daftar submission terbaru. Read-only (tanpa create/edit — rating berasal dari publik).
  - Public display + form: komponen `resources/js/components/public/rating-summary.tsx` (rata-rata bintang per kriteria + form submit), **di-share global ke `PublicLayout` footer region** via `PublicLayoutProps::base()` (agregasi rata-rata per kriteria), sehingga tampil di semua halaman publik. QA target region footer agar deterministik.
  - Authorization: **konsumsi `RatingCriterionPolicy` dari Task 3** via `authorize()` — jangan buat ulang policy.
  - RED: `tests/Feature/Public/RatingSubmitTest.php` + `tests/Feature/Admin/RatingCriterionCrudTest.php` + `tests/Feature/Admin/RatingDashboardTest.php` (dashboard agregasi + guard role).

  **Must NOT do**:
  - Jangan izinkan rating ganda dari visitor_hash sama (unique lifetime).
  - Jangan ubah nama tabel `rating_criteria_translations` (non-standar tapi existing).
  - Jangan seed ulang kriteria.

  **Recommended Agent Profile**:
  - **Category**: `unspecified-high` — Reason: Agregasi multi-kriteria, dedup hash, i18n kriteria, publik+admin.
  - **Skills**: [`laravel-best-practices`, `inertia-react-development`, `pest-testing`]

  **Parallelization**:
  - **Can Run In Parallel**: YES — **Parallel Group**: Wave 3
  - **Blocks**: None
  - **Blocked By**: Task 1 (Rating/RatingScore factories), Task 3 (RatingCriterionPolicy), Task 5 (rate limiter)

  **References**:
  - `app/Models/Rating.php`, `app/Models/RatingScore.php` (no timestamps, unique rating+criterion), `app/Models/RatingCriterion.php`, `app/Models/RatingCriterionTranslation.php` (`$table` non-standar).
  - `database/seeders/` — seeder 5 kriteria existing (jangan duplikasi).
  - `tests/Feature/InteractionModelTest.php` — pola model interaksi existing.
  - `routes/web.php` — catch-all (route sebelum ini).

  **Acceptance Criteria**:
  - [ ] Submit rating simpan skor per kriteria
  - [ ] visitor_hash sama tidak bisa rating ulang
  - [ ] Rata-rata per kriteria tampil publik
  - [ ] Admin kelola kriteria (aktif/urutan/i18n) via `/admin/rating-criteria`
  - [ ] Admin `/admin/ratings` tampil agregasi rata-rata per kriteria + total responden (read-only, Admin-only)
  - [ ] `php artisan test --filter=Rating` → PASS
  - [ ] `npm run build` sukses

  **QA Scenarios**:

  ```
  Scenario: Pengunjung beri rating 5 kriteria
    Tool: Playwright
    Preconditions: Kriteria aktif tersedia; rating-summary ter-share di footer PublicLayout (semua halaman publik)
    Steps:
      1. Navigate ke beranda '/', scroll ke region footer (rating-summary)
      2. Pilih bintang tiap kriteria, submit
      3. Assert rata-rata terupdate tampil
    Expected Result: Skor tersimpan, agregasi tampil
    Evidence: .omo/evidence/task-15-rating-submit.png

  Scenario: Rating ganda dari visitor sama ditolak
    Tool: Bash (php artisan test)
    Steps:
      1. Submit rating dgn visitor_hash X, submit lagi hash X → assert ditolak/idempoten
    Expected Result: Tidak ada duplikat
    Evidence: .omo/evidence/task-15-rating-dedup.txt
  ```

  **Commit**: YES — `feat(rating): star rating per criterion, aggregation, criteria admin`
  - Pre-commit: `vendor/bin/pint --dirty && php artisan test --filter=Rating && npm run build`

- [ ] 16. Floating WhatsApp button (komponen publik global)

  **What to do**:
  - Komponen `resources/js/components/public/whatsapp-float.tsx` — baca shared props `whatsapp` (Task 6), tampil jika `enabled`.
  - Pasang di akhir `PublicLayout` (setelah `</footer>`), link `wa.me` + pesan default.
  - RED (PHP): perluas `tests/Feature/PublicLayoutRegionTest.php` HANYA untuk assert shared prop `whatsapp.enabled`/`whatsapp.number` ter-share benar (bukan cek markup React — markup client tidak muncul di response Inertia feature test).
  - Kondisi render komponen (tampil saat enabled / tersembunyi saat disabled) diverifikasi via Playwright, bukan PHP test.

  **Must NOT do**:
  - Jangan tampilkan jika `enabled=false` atau nomor kosong.
  - Jangan hardcode nomor (baca dari settings).

  **Recommended Agent Profile**:
  - **Category**: `visual-engineering` — Reason: Komponen UI publik mengambang + aksesibilitas + kondisi settings.
  - **Skills**: [`inertia-react-development`, `tailwindcss-development`, `pest-testing`]

  **Parallelization**:
  - **Can Run In Parallel**: YES — **Parallel Group**: Wave 3
  - **Blocks**: None
  - **Blocked By**: Task 6 (WhatsApp shared props)

  **References**:
  - `app/Settings/WhatsappSettings.php` — number/enabled/default_message.
  - `resources/js/layouts/public-layout.tsx:135-189` — titik pasang akhir layout.
  - `resources/js/components/public/` — pola komponen publik existing.

  **Acceptance Criteria**:
  - [ ] Tombol tampil saat `enabled=true` + nomor terisi
  - [ ] Tersembunyi saat `enabled=false`
  - [ ] Link `wa.me` benar + pesan default ter-encode
  - [ ] `npm run build` sukses

  **QA Scenarios**:

  ```
  Scenario: WhatsApp float tampil & mengarah benar
    Tool: Playwright
    Preconditions: WhatsappSettings enabled=true number='628123'
    Steps:
      1. Navigate '/'
      2. Assert tombol WhatsApp visible di pojok
      3. Assert href berisi 'wa.me/628123'
    Expected Result: Tombol tampil & link benar
    Evidence: .omo/evidence/task-16-whatsapp-visible.png

  Scenario: WhatsApp float tersembunyi saat disabled
    Tool: Playwright
    Preconditions: WhatsappSettings enabled=false
    Steps:
      1. Navigate '/'
      2. Assert selector 'a[href*="wa.me"]' count === 0 (komponen tidak ter-render)
    Expected Result: Tidak tampil
    Evidence: .omo/evidence/task-16-whatsapp-hidden.png

  Scenario: Shared prop whatsapp ter-share benar (PHP)
    Tool: Bash (php artisan test)
    Steps:
      1. Set enabled=false, GET '/' → assert Inertia prop `whatsapp.enabled === false`
    Expected Result: Prop ter-share sesuai settings
    Evidence: .omo/evidence/task-16-whatsapp-prop.txt
  ```

  **Commit**: YES — `feat(public): floating whatsapp button from settings`
  - Pre-commit: `vendor/bin/pint --dirty && php artisan test --filter=PublicLayoutRegion && npm run build`

- [ ] 17. Bersihkan placeholder + update AdminPlaceholderRoutesTest

  **What to do**:
  - Hapus SEMUA route closure placeholder tersisa di `routes/admin.php` (menus, widgets, galleries, contact-messages, testimonials, ratings, rating-criteria, users, settings) — sudah diganti controller di Task 7-15.
  - Update/hapus `tests/Feature/AdminPlaceholderRoutesTest.php` agar tidak lagi assert route placeholder yang sudah jadi controller nyata.
  - Hapus `resources/js/pages/admin/placeholder.tsx` + `coming-soon.tsx` jika tak terpakai lagi.
  - Verifikasi tidak ada `Inertia::render('admin/placeholder')` tersisa.

  **Must NOT do**:
  - Jangan hapus route yang belum punya controller pengganti (cek dulu).
  - Jangan biarkan test menguji halaman yang sudah dihapus.

  **Recommended Agent Profile**:
  - **Category**: `unspecified-high` — Reason: Cross-cutting cleanup + test alignment, butuh verifikasi menyeluruh.
  - **Skills**: [`laravel-best-practices`, `pest-testing`]

  **Parallelization**:
  - **Can Run In Parallel**: NO (sequential) — **Parallel Group**: Wave 4
  - **Blocks**: Task 18
  - **Blocked By**: Task 7-16 (semua modul harus jadi dulu)

  **References**:
  - `routes/admin.php:47-70` — blok placeholder.
  - `tests/Feature/AdminPlaceholderRoutesTest.php` — assert existing.
  - `resources/js/pages/admin/placeholder.tsx`, `resources/js/components/admin/coming-soon.tsx`.

  **Acceptance Criteria**:
  - [ ] Zero route closure placeholder di `routes/admin.php`
  - [ ] Zero referensi `admin/placeholder` di kode
  - [ ] `grep -r "placeholder" routes/admin.php` kosong
  - [ ] `php artisan test` → PASS (suite penuh)

  **QA Scenarios**:

  ```
  Scenario: Tidak ada route placeholder tersisa
    Tool: Bash
    Steps:
      1. Jalankan `php artisan route:list --path=admin | grep -i placeholder` → assert kosong
      2. Jalankan `grep -rn "admin/placeholder" resources/js routes` → assert kosong
    Expected Result: Zero placeholder
    Evidence: .omo/evidence/task-17-no-placeholder.txt
  ```

  **Commit**: YES — `refactor(admin): remove all placeholder routes and coming-soon page`
  - Pre-commit: `php artisan test`

- [ ] 18. Dual-DB CI + quality closure + rekonsiliasi dokumentasi

  **What to do**:
  - Pastikan `composer ci:check` (Pint + PHPStan + test) hijau; tambah job PostgreSQL di CI bila belum (SQLite feature suite + PostgreSQL integration suite keduanya hijau).
  - Jalankan seluruh gerbang dengan script yang tersedia di proyek: `vendor/bin/pint --test`, `composer types:check` (PHPStan/Larastan), `npm run types:check` (tsc), `npm run lint:check` (ESLint), `npm run format:check` (Prettier), `php artisan wayfinder:generate`, `npm run build:ssr` (Vite client + SSR).
  - Rekonsiliasi dokumentasi: buat/lengkapi tabel traceability di `docs/superpowers/plans/2026-07-23-prd-v1-1-remediation-roadmap.md` (section baru "## Traceability Matrix (Fase 7-10)") dengan kolom: `Requirement PRD | Fase | File Implementasi | File Test`. Setiap requirement fase 7-10 (Users, Settings, Menu, Widget, Gallery, Contact, Testimonial, Rating, WhatsApp) wajib punya minimal 1 baris dengan path file+test yang benar-benar ada.
  - Konfirmasi Program Definition of Done roadmap terpenuhi.

  **Must NOT do**:
  - Jangan tambah dependency baru tanpa persetujuan.
  - Jangan longgarkan aturan lint/type demi lolos.

  **Recommended Agent Profile**:
  - **Category**: `unspecified-high` — Reason: Integrasi CI dual-DB + verifikasi seluruh quality gate + dokumentasi.
  - **Skills**: [`laravel-best-practices`, `pest-testing`]

  **Parallelization**:
  - **Can Run In Parallel**: NO (sequential, terakhir) — **Parallel Group**: Wave 4
  - **Blocks**: None (final)
  - **Blocked By**: Task 17

  **References**:
  - `composer.json` — script `ci:check` existing.
  - `.github/workflows/` — konfigurasi CI existing (cek job DB).
  - `docs/superpowers/plans/2026-07-23-prd-v1-1-remediation-roadmap.md` — Program Definition of Done.
  - `phpstan.neon`, `eslint.config.js`, `.prettierrc` — konfigurasi gate.

  **Acceptance Criteria**:
  - [ ] `composer ci:check` → PASS (menjalankan `npm run lint:check` + `npm run format:check` + `npm run types:check` + test)
  - [ ] SQLite suite hijau: `php artisan test --compact`
  - [ ] PostgreSQL suite hijau: `DB_CONNECTION=pgsql php artisan test --compact`
  - [ ] `vendor/bin/pint --test`, `phpstan analyse`, `npm run types:check`, `npm run lint:check`, `npm run format:check` → PASS
  - [ ] `npm run build:ssr` (client + SSR bundle) → PASS
  - [ ] Dokumentasi roadmap + traceability terupdate

  **QA Scenarios**:

  ```
  Scenario: Seluruh quality gate hijau
    Tool: Bash
    Steps:
      1. Jalankan `composer ci:check` → assert exit 0
      2. Jalankan `npm run build:ssr` → assert sukses (client + SSR)
      3. Jalankan `DB_CONNECTION=pgsql php artisan test --compact` → assert hijau
    Expected Result: Semua gate PASS
    Evidence: .omo/evidence/task-18-ci-green.txt

  Scenario: Verifikasi tidak ada requirement PRD inti tanpa test
    Tool: Bash
    Preconditions: Tabel "Traceability Matrix (Fase 7-10)" sudah ditambahkan ke docs/superpowers/plans/2026-07-23-prd-v1-1-remediation-roadmap.md
    Steps:
      1. Untuk tiap path file di kolom "File Implementasi" & "File Test" matriks, jalankan `test -f <path>` → assert exit 0 (semua file yang direferensikan benar-benar ada)
      2. Assert matriks memuat baris untuk 9 modul fase 7-10: grep -E "Users|Settings|Menu|Widget|Gallery|Contact|Testimonial|Rating|WhatsApp" pada section matriks → assert 9 modul hadir
      3. Jalankan tiap file test di kolom "File Test": `php artisan test <path>` → assert PASS
    Expected Result: Setiap requirement fase 7-10 punya file implementasi + file test yang ada dan hijau; tidak ada baris matriks dengan path yang tidak eksis
    Failure Indicators: `test -f` exit 1 (file tidak ada), modul hilang dari matriks, test gagal
    Evidence: .omo/evidence/task-18-traceability.txt
  ```

  **Commit**: YES — `chore(ci): dual-db pipeline, quality closure, docs reconciliation`
  - Pre-commit: `composer ci:check && npm run build:ssr`

---

## Final Verification Wave (MANDATORY — setelah SEMUA task implementasi)

> 4 review agent jalan PARALEL. SEMUA harus APPROVE. Presentasikan hasil ke user dan dapatkan "okay" eksplisit sebelum menyelesaikan.
> Jangan tandai F1-F4 checked sebelum user okay.

- [ ] F1. **Plan Compliance Audit** — `oracle`
  Baca plan end-to-end. Tiap "Must Have": verifikasi implementasi ada (baca file, curl endpoint, jalankan command). Tiap "Must NOT Have": cari pola terlarang di codebase — reject dengan file:line bila ada. Cek evidence files di `.omo/evidence/`. Bandingkan deliverables vs plan.
  Output: `Must Have [N/N] | Must NOT Have [N/N] | Tasks [N/N] | VERDICT: APPROVE/REJECT`

  **Acceptance Criteria (APPROVE bila SEMUA terpenuhi):**
  - [ ] 100% item "Must Have" terverifikasi ada (bukti: file/command per item)
  - [ ] 0 item "Must NOT Have" ditemukan di codebase (bukti: grep bersih)
  - [ ] 18/18 task implementasi punya evidence file di `.omo/evidence/`
  - [ ] VERDICT tertulis `APPROVE` (bukan REJECT/partial)

- [ ] F2. **Code Quality Review** — `unspecified-high`
  Jalankan `vendor/bin/pint --test`, PHPStan, `tsc --noEmit`, ESLint, `php artisan test`. Review file berubah: `as any`/`@ts-ignore`, empty catch, `console.log`, kode terkomentar, import tak terpakai. Cek AI slop: komentar berlebihan, over-abstraction, nama generik.
  Output: `Build [PASS/FAIL] | Lint [PASS/FAIL] | Tests [N pass/N fail] | Files [N clean/N issues] | VERDICT`

  **Acceptance Criteria (APPROVE bila SEMUA terpenuhi):**
  - [ ] `vendor/bin/pint --test` → PASS (0 file perlu format)
  - [ ] PHPStan (larastan level proyek) → 0 error
  - [ ] `tsc --noEmit` + ESLint + Prettier → 0 error
  - [ ] `php artisan test` → 0 failure
  - [ ] 0 temuan `as any`/`@ts-ignore`/empty catch/`console.log` di file berubah

- [ ] F3. **Real Manual QA** — `unspecified-high` (+ `playwright` skill)
  Mulai dari state bersih. Eksekusi SETIAP QA scenario dari SETIAP task — ikuti langkah persis, tangkap evidence. Tes integrasi lintas-task (fitur bekerja bersama). Tes edge case: empty state, input invalid, aksi cepat. Simpan ke `.omo/evidence/final-qa/`.
  Output: `Scenarios [N/N pass] | Integration [N/N] | Edge Cases [N tested] | VERDICT`

  **Acceptance Criteria (APPROVE bila SEMUA terpenuhi):**
  - [ ] 100% QA scenario dari 18 task → PASS (happy + negative)
  - [ ] Integrasi lintas-task terverifikasi (min. 3 alur: submit kontak→inbox, submit testimoni→moderasi→tampil, submit rating→agregasi)
  - [ ] Evidence tersimpan di `.omo/evidence/final-qa/` untuk tiap scenario
  - [ ] 0 scenario gagal/blocked

- [ ] F4. **Scope Fidelity Check** — `deep`
  Tiap task: baca "What to do", baca diff aktual (git log/diff). Verifikasi 1:1 — semua di spec dibangun, tidak ada di luar spec. Cek kepatuhan "Must NOT do". Deteksi kontaminasi lintas-task. Flag perubahan tak terhitung.
  Output: `Tasks [N/N compliant] | Contamination [CLEAN/N issues] | Unaccounted [CLEAN/N files] | VERDICT`

  **Acceptance Criteria (APPROVE bila SEMUA terpenuhi):**
  - [ ] 18/18 task compliant 1:1 dengan "What to do" (tidak kurang, tidak lebih)
  - [ ] 0 pelanggaran "Must NOT do" per task
  - [ ] 0 kontaminasi lintas-task (task N tidak menyentuh file milik task M tanpa alasan)
  - [ ] 0 file berubah tak terhitung dalam scope plan

---

## Commit Strategy

Commit per task dengan Conventional Commits (Bahasa Inggris untuk message, sesuai konvensi git repo existing):
- Wave 1: `feat(policy): ...`, `feat(settings): ...`, `test(factory): ...`
- Wave 2-4: `feat(admin): ...`, `feat(public): ...` per modul
- Wave 4: Task 17 → `refactor(admin): remove all placeholder routes and coming-soon page`; Task 18 → `chore(ci): dual-db pipeline, quality closure, docs reconciliation` (satu commit per task)
Pre-commit tiap task: `vendor/bin/pint --dirty` + `php artisan test --filter={ModuleTest}`

---

## Success Criteria

### Verification Commands
```bash
grep -rn "admin/placeholder" routes/admin.php   # Expected: 0 (route fungsional semua)
composer ci:check                                # Expected: hijau
php artisan test --compact                       # Expected: semua pass (SQLite)
DB_CONNECTION=pgsql php artisan test --compact   # Expected: semua pass (PostgreSQL)
npm run build:ssr                                # Expected: sukses (Vite client + SSR bundle)
```

### Final Checklist
- [ ] Semua "Must Have" hadir
- [ ] Semua "Must NOT Have" absen
- [ ] Semua test pass di dua database
- [ ] Tidak ada route admin placeholder
