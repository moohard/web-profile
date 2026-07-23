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

