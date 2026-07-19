# Rencana Fondasi Teknis — Website Company Profile CMS

**Pendamping:** PRD v1.0
**Stack:** Laravel 13 + Inertia + React · PostgreSQL + Eloquent · Tailwind + shadcn/ui + Magic UI · Laravel AI SDK · paket Spatie (permission, medialibrary, sitemap)
**Tanggal:** 19 Juli 2026

> **Lingkup dokumen:** "Fondasi" = kerangka teknis yang harus berdiri **sebelum** membangun fitur — setup proyek, skema database, autentikasi, routing, shell admin & publik, serta layanan lintas-fungsi (media, AI, design system). Pembangunan fitur menyusul (lihat bagian akhir).

---

## Ikhtisar Fase

1. **Setup Proyek & Tooling** — kerangka aplikasi, paket, linting.
2. **Skema Database & Model** — migrasi + Eloquent + seeder dari model data PRD.
3. **Autentikasi & Otorisasi** — login admin, role, gate.
4. **Fondasi Routing & Locale** — resolusi rute berlapis + multi-bahasa.
5. **Shell Admin** — layout dashboard + navigasi (termasuk jenis konten dinamis).
6. **Shell Publik & SEO** — layout publik server-rendered + fondasi SEO/aksesibilitas.
7. **Layanan Lintas-Fungsi** — media (WebP), AI (tiga agen), design system.

Urutan: Fase 1 → 2 → 3 berurutan. Fase 4–6 bisa sebagian paralel setelah Fase 3. Fase 7 disiapkan sebelum fitur yang membutuhkannya.

---

## Fase 1 — Setup Proyek & Tooling

- [ ] Inisialisasi proyek Laravel 13 (PHP 8.3+).
- [ ] Pasang starter Inertia + React + TypeScript (Laravel Breeze varian Inertia-React) + Tailwind CSS.
- [ ] Aktifkan **Inertia SSR** (wajib untuk SEO halaman publik).
- [ ] Setup **shadcn/ui** + **Magic UI** (konfigurasi Tailwind, komponen dasar).
- [ ] Pasang paket: `spatie/laravel-permission`, `spatie/laravel-medialibrary`, `spatie/laravel-sitemap`, `laravel/ai`.
- [ ] Konfigurasi koneksi **PostgreSQL** (`.env`). Siapkan `pgvector` opsional untuk kebutuhan RAG masa depan.
- [ ] Git repo, `.env.example`, Pint (PHP), ESLint + Prettier (TS).
- [ ] (Opsional) CI dasar: lint + test.

---

## Fase 2 — Skema Database & Model

- [ ] **PHP Enums:** `PostStatus`, `UserRole`, `AiTask`, `LinkType`, `WidgetPosition`, `PlacementScope`, `MenuLocation`, `PageMode`.
- [ ] **Migrasi — Konten:** `languages`, `writing_styles`, `content_types` (+ `content_type_translations`), `posts` (+ `post_translations`), `categories`/`category_translations`, `tags`/`tag_translations`, `post_tags`, `galleries`/`gallery_translations`, `gallery_images`/`gallery_image_translations`.
- [ ] **Migrasi — Halaman & Tata Letak:** `pages` (+ `page_translations`), `menus`, `menu_items` (+ `menu_item_translations`), `widgets` (+ `widget_translations`), `widget_placements`, `widget_placement_targets`.
- [ ] **Migrasi — Pendukung & Interaksi:** `media` (medialibrary), `users`, `site_settings`, `ai_configs`, `contact_messages`, `testimonials`, `rating_criteria` (+ `rating_criteria_translations`), `ratings`, `rating_scores`.
- [ ] **Model Eloquent + relasi:** termasuk pola terjemahan `*_translations`; cast JSON untuk `content`/`config`; **enkripsi** `ai_configs.api_key` (cast encrypted).
- [ ] **Helper terjemahan:** ambil translasi per-locale dengan fallback ke bahasa default.
- [ ] **Seeder:** bahasa default (ID `is_default`), contoh `content_types` (Artikel/Berita/Pengumuman), preset `writing_styles`, `rating_criteria` (5 kriteria rekomendasi), user Admin awal.

---

## Fase 3 — Autentikasi & Otorisasi

- [ ] Auth admin (Breeze/Fortify): login, session.
- [ ] `spatie/permission`: role **Admin / Editor / Author** + permission; seeder role.
- [ ] Policy/Gate per resource + middleware role (lihat matriks visibilitas PRD §8.3).
- [ ] **Gate khusus:** mode code hanya untuk Admin.

---

## Fase 4 — Fondasi Routing & Locale

- [ ] Middleware **locale** (prefix `/en/...`, ID default).
- [ ] **Resolusi rute berlapis:** locale → slug jenis konten (arsip `/{type}` & single `/{type}/{post}`) → **custom page catch-all** → 404.
- [ ] Controller skeleton: arsip, single, custom page, homepage.

---

## Fase 5 — Shell Admin (Dashboard)

- [ ] Layout admin: **sidebar** (grup Konten / Halaman / Tampilan / Interaksi / Sistem) + topbar.
- [ ] **Visibilitas menu per role.**
- [ ] **Navigasi jenis konten dinamis** (dibangkitkan dari `content_types` aktif).
- [ ] Halaman Inertia kerangka per bagian (placeholder CRUD).
- [ ] Dashboard ringkasan (hitung konten/halaman/media).

---

## Fase 6 — Shell Publik & SEO

- [ ] Layout publik: **header nav + footer** (global) + slot region (hero, sidebar, widget).
- [ ] **Pemilih bahasa.**
- [ ] **Fondasi SEO:** komponen meta/OG per-bahasa + `hreflang`, sitemap (`spatie/laravel-sitemap`), structured data (JSON-LD).
- [ ] Pastikan halaman publik **server-rendered** (Inertia SSR / Blade).
- [ ] Baseline **aksesibilitas** (HTML semantik, landmark, fokus, kontras) & audit awal **Core Web Vitals**.

---

## Fase 7 — Layanan Lintas-Fungsi

- [ ] **Media:** konfigurasi medialibrary — konversi otomatis **WebP** + varian responsif, simpan file asli, **kecualikan SVG**.
- [ ] **AI:** penyimpanan `ai_configs` (api_key terenkripsi); tiga **agen** via Laravel AI SDK — `TRANSLATION`, `CONTENT_REFINEMENT`, `MARKUP_CONFORM` (base URL kustom OpenAI-compatible); service pemanggil sisi server; pola **"saran → tinjau"** (Terima/Sunting/Batalkan).
- [ ] **Design system & referensi komponen:** katalog class Tailwind + komponen shadcn/Magic UI, dijadikan konteks untuk `MARKUP_CONFORM`.
- [ ] **Sanitasi HTML** untuk mode code (buang `<script>` dan atribut `on*`).

---

## Definisi Selesai (Fondasi)

Aplikasi berjalan dengan:
- Skema DB + model lengkap dan ter-seed.
- Login admin + role berfungsi (mode code ter-gate Admin).
- Routing berlapis + locale jalan.
- Shell admin (sidebar dinamis) dan shell publik (SSR + SEO dasar) tampil.
- Media (WebP), AI (tiga agen), dan design system tersambung.

→ Siap dibangun fitur di atasnya, tanpa lagi menyentuh pondasi.

---

## Setelah Fondasi — Urutan Fitur yang Disarankan

1. **CRUD Konten** (`posts`) + kategori/tag + editor + UI terjemahan + galeri.
2. **Custom page:** mode template, lalu mode code (HTML + AI markup conform + pratinjau).
3. **Region:** hero, sidebar, widget + penempatan (scope + target polimorfik).
4. **Menu builder** + opsi "tambahkan ke menu".
5. **Fitur AI di editor** (terjemahkan/koreksi) + pengaturan `ai_configs` / `writing_styles` / `languages`.
6. **Interaksi pengunjung:** WhatsApp mengambang, form kontak, testimoni, penilaian.
7. **Pengaturan situs**, media library UI, manajemen user.

*(Fitur ditunda — custom fields per jenis konten — dibahas kembali setelah inti selesai; lihat PRD Lampiran A.)*

---

## Catatan Dependensi

- **Fase 1 → 2 → 3** wajib berurutan (pondasi berdiri di atasnya).
- **Fase 4, 5, 6** bisa jalan sebagian paralel setelah Fase 3.
- **Fase 7** disiapkan sebelum fitur terkait: media sebelum CRUD gambar; AI + design system sebelum mode code dan fitur AI di editor.
