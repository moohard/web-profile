# PRD v1.1 Remediation Roadmap

> **For agentic workers:** REQUIRED SUB-SKILL: Use `superpowers:executing-plans` task-by-task. Every implementation task follows RED-GREEN-REFACTOR and closes with a review checkpoint.

**Goal:** Menyelaraskan implementasi CMS dengan PRD v1.1 melalui sepuluh vertical slice yang dapat diuji dan direview secara independen.

**Architecture:** Perubahan dibangun risk-first: kontrak authorization dan validation lebih dahulu, lalu lifecycle data, locale, editor konten, serta modul CMS end-to-end. Source of truth adalah PRD v1.1, spesifikasi Posts, dan delta plan terbaru; dokumen lama hanya historis.

**Tech Stack:** Laravel 13.20, PHP 8.4, Inertia Laravel/React 3, React 19, PostgreSQL 17, Pest 4, Spatie Permission 8, Media Library 11, Laravel Settings 3, Wayfinder, Tailwind CSS 4.

## Global Constraints

- Semua komunikasi pengguna dan komentar kode menggunakan Bahasa Indonesia.
- Semua API eksternal diverifikasi melalui Context7 sebelum implementasi fase.
- Semua perubahan perilaku menggunakan TDD; tidak ada production code sebelum tes gagal.
- Controller tetap tipis; operasi multi-model memakai Action class dan transaksi.
- Custom fields, revisioning, scheduling, bulk actions, comments, CAPTCHA, table editor, dan media translations penuh tetap di luar scope.
- Database development boleh di-reset; tidak ada backfill produksi.

## Execution Order

1. Permission dan validation.
2. Soft delete dan Trash.
3. Locale dinamis dan Languages.
4. Posts D2-D3: Tiptap, sanitizer, excerpt, Writing Styles.
5. Posts D4-D5: featured media, public presentation, admin polish.
6. Page Template, AI, dan preview.
7. Users dan Site Settings.
8. Menu, Template registry, Gallery, dan Widget placement.
9. Contact, Testimonial, Rating, dan WhatsApp.
10. Rekonsiliasi dokumentasi, dual-database CI, dan quality closure.

## Program Definition of Done

- Tidak ada route admin placeholder.
- Setiap requirement inti PRD memiliki implementasi dan tes.
- Item deferred hanya yang tercatat eksplisit pada PRD Lampiran A atau Global Constraints.
- SQLite feature suite dan PostgreSQL integration suite hijau.
- Pint, PHPStan, TypeScript, ESLint, Prettier, Wayfinder, Vite client, dan SSR build hijau.
- Traceability matrix menghubungkan requirement, fase, file implementasi, dan tes.

## Traceability Matrix (Fase 7-10)

| Requirement PRD | Fase | File Implementasi | File Test |
| --- | --- | --- | --- |
| Users: pengelolaan akun pengguna dan role | 7 | `app/Http/Controllers/Admin/UserController.php` | `tests/Feature/Admin/UserCrudTest.php` |
| Settings: pembaruan pengaturan situs | 7 | `app/Http/Controllers/Admin/SettingsController.php` | `tests/Feature/Admin/SiteSettingsUpdateTest.php` |
| Menu: CRUD struktur menu | 8 | `app/Http/Controllers/Admin/MenuController.php` | `tests/Feature/Admin/MenuCrudTest.php` |
| Widget: CRUD dan penempatan widget | 8 | `app/Http/Controllers/Admin/WidgetController.php` | `tests/Feature/Admin/WidgetCrudTest.php` |
| Gallery: CRUD galeri | 8 | `app/Http/Controllers/Admin/GalleryController.php` | `tests/Feature/Admin/GalleryCrudTest.php` |
| Contact: pengiriman formulir kontak publik | 9 | `app/Http/Controllers/Public/ContactController.php` | `tests/Feature/Public/ContactSubmitTest.php` |
| Testimonial: pengiriman testimoni publik | 9 | `app/Http/Controllers/Public/TestimonialController.php` | `tests/Feature/Public/TestimonialSubmitTest.php` |
| Rating: pengiriman rating publik | 9 | `app/Http/Controllers/Public/RatingController.php` | `tests/Feature/Public/RatingSubmitTest.php` |
| WhatsApp: props layout publik untuk tombol WhatsApp | 9 | `app/Support/PublicLayoutProps.php` | `tests/Feature/PublicLayoutRegionTest.php` |
