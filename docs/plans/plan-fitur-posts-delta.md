# Posts Feature — DELTA Plan (rescoped)

**Rescope dari:** `docs/plans/plan-fitur-posts.md`
**Spec:** `docs/design-system/spec-fitur-posts-v1.0.md` (catatan: plan/spec asli menaruh path & versi berbeda — lihat §Rekonsiliasi)
**PRD:** `docs/PRD-Website-Company-Profile-CMS-v1.0.md` (bukan v1.1 — v1.1 tidak ada)
**Tanggal rescope:** 20 Juli 2026

> Plan & spec asli ditulis di era fondasi. Sejak itu **subsistem Konten (K1–K6)** dan **Halaman (H1–H4)** sudah dibangun & di-push, sehingga ~65% plan asli SUDAH JADI. Dokumen ini menyisakan **hanya delta** yang belum ada, selaras dengan plan + spec. Metode: subagent-driven, TDD, gate + commit per fase.

---

## SUDAH SELESAI (di luar lingkup delta — jangan bangun ulang)

Terverifikasi ada di kode & test hijau (commit di `main`):
- **CRUD Posts penuh** (index filter type/status + pagination + **scoping Author** + `author_id`=auth; create/store/edit/update/destroy) — `PostController`, `PostRequest`. *(K3+K4)*
- **Editor Post dua-kolom** + toggle bahasa (title/slug/body/status/published_at/meta per bahasa; jenis/kategori/tag/gambar bersama) + MediaPicker. Body = `<textarea>` (belum Tiptap). *(K4)*
- **Slug unik per bahasa** (`ContentSlug::unique` scoped `language_id`, auto + suffix bentrok). *(K3/K4)*
- **Kategori & Tag CRUD** + terjemahan per bahasa + **guard hapus bila dipakai**. *(K1)*
- **`PostPolicy` LENGKAP** (viewAny/create/view? update/delete/deleteOwn + `owns()`) — **bukan skeleton lagi**. Kurang `restore/forceDelete` (butuh soft delete → D1).
- **Terjemahkan AI di editor** (`AiSuggestButton`, saran→Terima/Batalkan, non-destruktif) + Koreksi (MegaNova). *(K5)*
- **Taksonomi global** (kategori/tag tanpa `type_id`). *(fondasi)*
- **Single publik**: SeoProps + **JSON-LD Article** + hreflang/canonical. Query **Published-per-locale**. *(fondasi + review)*

## SEBAGIAN JADI (partial → sisa dikerjakan di delta, JANGAN bangun ulang)

Bagian ini eksplisit agar tidak ada yang dibangun dari nol maupun dianggap 100% selesai:

| Area | Yang SUDAH ada | Sisa (delta) |
|---|---|---|
| Editor Post — body | `posts/form.tsx` dua-kolom + toggle bahasa; body = `<textarea>` HTML | Ganti field body → **Tiptap** (**D2**) |
| Editor Page — konten Template | `pages/form.tsx` (Template pakai textarea, Code raw) | Konten Template → **Tiptap**; Code tetap raw (**D2**) |
| Sanitizer | 2 profil purify (`default`, `cms_page`); service hardcode `cms_page` | Jadikan **profile-aware** (profil rich-text utk body) (**D2**) |
| PostPolicy / PagePolicy | viewAny/create/view/update/delete/deleteOwn lengkap | Tambah **`restore` + `forceDelete`** (**D1**) |
| Daftar Post admin | tabel + filter + pagination; status **locale aktif** saja | **Indikator status per-bahasa** (ID ● · EN ○) (**D5**) |
| Kategori admin | CRUD + **pemilihan parent di form sudah ada** | **Tampilan tree/indent** di daftar (**D5**) |
| Editor Post — tag | multi-select dari tag yang ada | **Create-on-type** (buat tag saat mengetik) (**D5**) |
| Arsip publik | `post-archive.tsx` = daftar `<ul><li>` (judul+link); query Published-per-locale + paginate(12) di controller | **Kartu** (excerpt/gambar/tanggal) + **UI pagination**; controller kirim excerpt/gambar/tanggal/links (**D4**) |
| Single publik | judul + body (HTML) + SEO + JSON-LD Article | Render **kategori/tag** + fallback meta→excerpt (**D4**) |
| Gambar utama | `posts.featured_image` = **string URL** (koleksi media `featured_image` ada tapi tak dipakai) | **R1 (diputuskan: ikuti spec)** → migrasi ke **media-assoc koleksi `featured`** (D1 cascade + D4 gambar WebP) |

## Prasyarat delta
Semua di atas + `Sanitizer` (purify) dengan **dua config tersedia**: `default` (mirip rich-text) & `cms_page` (mode code) — tapi service `Sanitizer::clean()` saat ini **hardcode `cms_page`**.

---

## Global Constraints
Sama seperti plan asli: TDD (Pest), `declare(strict_types=1)`, Pint + Larastan hijau, Wayfinder untuk URL, SSR rute publik, Policy+middleware sebagai security boundary, Opsi B multi-bahasa, **sanitasi HTML server-side wajib**. Dependency baru **hanya Tiptap** (disetujui). Verifikasi ke kode nyata sebelum eksekusi.

---

## DELTA yang dibangun

### D1 — Soft delete + Trash (Post & Page)  *(plan Task 1.1, 1.2-restore, Fase 5; spec §3, §4.1, §13.3)*
- [ ] **Test dulu:** post/page dihapus → `deleted_at` terisi, hilang dari query default & frontend publik, muncul di `onlyTrashed()`; restore mengembalikan; forceDelete hapus permanen (+ translations & media terkait bersih).
- [ ] Migrasi `add_soft_deletes_to_posts_and_pages` (`$table->softDeletes()` di `posts` & `pages`).
- [ ] Trait `SoftDeletes` di model `Post` & `Page`.
- [ ] `PostPolicy` & `PagePolicy`: tambah `restore()` + `forceDelete()` (Admin|Editor; Author restore miliknya opsional).
- [ ] Controller: `destroy` → soft delete (otomatis via trait); tambah `trash` (index `onlyTrashed`), `restore`, `forceDelete`. Routes admin `posts/trash`, `posts/{post}/restore`, `posts/{post}/force`; sama untuk pages. Wayfinder regen.
- [ ] `forceDelete`: pastikan `post_translations`/`page_translations` + **media koleksi `featured`** ikut terhapus (FK cascade saat delete permanen; Spatie otomatis membersihkan media saat model dihapus — lihat R1).
- [ ] Publik: verifikasi query default sudah mengecualikan trashed (resolver `PublicPathResolver` + archive).
- [ ] React: `posts/trash.tsx` & `pages/trash.tsx` (daftar ter-trash + Restore + Hapus permanen konfirmasi); tautan "Trash" di daftar.

### D2 — Editor Tiptap + profil sanitasi rich-text  *(plan Fase 2; spec §10, §4.2, §13.2)*
- [ ] Dep: `@tiptap/react`, `@tiptap/starter-kit`, `@tiptap/extension-link`, `@tiptap/extension-image` (tabel opsional). Catat perubahan dep.
- [ ] `resources/js/components/admin/rich-text-editor.tsx`: terkontrol (`value` HTML, `onChange`), toolbar shadcn (heading, bold/italic, list, link, quote, **sisip gambar via MediaPicker**), **SSR-safe** (init editor di `useEffect`/client-only, hindari `window` saat SSR).
- [ ] Pakai di **body Post** (`posts/form.tsx`) & **konten mode Template Page** (`pages/form.tsx`). **Mode Code Page tetap `<textarea>` raw.**
- [ ] **Sanitasi rich-text server-side:** buat `Sanitizer` profile-aware — mis. `cleanRichText(string $html)` pakai purify profil `default`/rich-text (izinkan h1–h3, p, ul/ol/li, a, blockquote, img, strong/em, br; **buang** `<script>`/`on*`/`javascript:`). Terapkan saat simpan `post_translations.body` & konten Template Page. **Mode Code Page tetap profil `cms_page`.** *(cek: `cms_page` belum tentu izinkan `<table>`; kalau toolbar Tiptap pakai tabel, tambahkan ke profil rich-text.)*
- [ ] Test: rich-text sanitize pertahankan tag format, buang script/on*; body Tiptap tersimpan & ter-render di single publik (HTML statis, bukan instance Tiptap).

### D3 — Helper excerpt  *(plan Task 1.4; spec §9, §13.5)*
- [ ] **Test:** strip tag HTML, collapse whitespace, potong ~160 char + elipsis; input kosong → string kosong.
- [ ] Helper `excerpt(?string $html, int $limit = 160): string` (mis. `app/Support/helpers.php` atau `Str::macro`). Tanpa migrasi.
- [ ] Pakai di kartu arsip (D4) + fallback `meta_description`/`og:description` di single & archive SeoProps bila kosong.

### D4 — Penyelesaian frontend publik (Arsip & Single) + migrasi featured (R1)  *(plan Fase 7; spec §9, §3)*
- [ ] **Featured (R1):** ubah `PostController` store/update agar melampirkan gambar utama ke **koleksi media `featured`** (dari media id `MediaPicker`), bukan menulis URL string; `Post` daftarkan koleksi `featured` (singleFile). Editor tampilkan preview dari media.
- [ ] `PostController::archive` (publik): sertakan per item **excerpt(body)**, **URL gambar** dari **koleksi `featured`** (varian **WebP responsif**), **published_at** terformat; kirim **metadata pagination** (current_page/last_page/links).
- [ ] `post-archive.tsx`: ganti `<ul><li>` → **kartu** (judul, excerpt, gambar, tanggal) + **UI pagination**.
- [ ] `post-show.tsx` + controller: render **kategori & tag** (chip); pastikan meta description fallback ke excerpt bila kosong. (JSON-LD Article + hreflang sudah ada.)
- [ ] Test: arsip hanya Published per-locale + urut `published_at` desc + pagination + excerpt tampil; single menampilkan kategori/tag + meta fallback.

### D5 — Poles admin: status per-bahasa, tag create-on-type, kategori hierarkis  *(plan Task 3.4, 4.2, 4.3; spec §4.1, §4.3)*
- [ ] **Indikator status per-bahasa** di daftar Post (mis. `ID ● · EN ○`): controller kirim status tiap bahasa; render di `posts/index.tsx`.
- [ ] **Tag create-on-type** di editor Post: multi-select bisa buat tag baru saat mengetik (endpoint quick-create `tags.store` mengembalikan id, atau sertakan nama tag baru di payload store/update lalu `firstOrCreate`). Test: tag baru tercipta & tertaut.
- [ ] **Kategori hierarkis** di admin: tampilkan parent/child (indent/tree) di daftar `categories/index.tsx`. (Data `parent_id` + **pemilihan parent di form SUDAH ada** — sisa hanya tampilan hierarkis di daftar.)

---

## Rekonsiliasi (spec/plan vs kode nyata)
- **R1 — Gambar utama `featured` → DIPUTUSKAN: ikuti spec (opsi a).** Spec §3 + plan C6: gambar utama disimpan sebagai **asosiasi media-library Spatie, koleksi `featured` (singleFile)** — **bukan** kolom string. **Kode saat ini** menyimpan **URL string** di `posts.featured_image` (koleksi media terdaftar bernama `featured_image`, tak dipakai untuk post). **Aksi (masuk D1 + D4):**
  - `Post`: selaraskan nama koleksi media ke **`featured`** (singleFile) sesuai spec (ganti registrasi `featured_image` → `featured`).
  - `PostController` store/update: **asosiasikan media** (bukan tulis URL string); `MediaPicker` mengembalikan **media id** → `Post` melampirkan/menyalin ke koleksi `featured`.
  - Tampilan (arsip/single/editor): ambil URL dari **konversi WebP responsif** koleksi media (bukan kolom string).
  - `forceDelete`: Spatie otomatis membersihkan media saat model dihapus permanen.
  - Kolom `posts.featured_image` (string) menjadi legacy — boleh dibiarkan nullable/tak dipakai; penghapusan kolom = migrasi terpisah bila diinginkan (opsional, tidak wajib di fitur ini).
- **R2 — Referensi dokumen:** plan asli menunjuk `docs/spec-fitur-posts-v1.0.md` (aktual di `docs/design-system/`), `PRD v1.1` & `foundation-design-revisi` (tidak ada; aktual v1.0 / non-revisi). Perbaiki header plan/spec agar traceable (kosmetik).
- **R3 — "PostPolicy skeleton" & "PostController lengkapi"** di plan/spec = usang; keduanya sudah penuh. Delta hanya `restore/forceDelete` (D1).
- **R4 — Sanitasi dua profil (C3):** profil `default` (rich-text) & `cms_page` sudah ada di `config/purify.php`, tapi `Sanitizer` hardcode `cms_page`. D2 menjadikannya profile-aware.

---

## Definition of Done (delta)
- Soft delete + trash/restore/forceDelete untuk Post & Page; publik & relasi abaikan trashed.
- Tiptap untuk body Post & konten Template Page (Code mode tetap raw); body tersanitasi via profil rich-text.
- Helper excerpt + dipakai di arsip & fallback meta/OG.
- Arsip publik: kartu (judul/excerpt/gambar/tanggal) + pagination; single menampilkan kategori/tag.
- Status per-bahasa di daftar admin; tag create-on-type; kategori hierarkis di admin.
- **R1 diikuti spec:** gambar utama = asosiasi media-library koleksi `featured` (bukan string), WebP responsif di publik, cascade saat forceDelete. Semua test hijau; Pint + Larastan + tsc + eslint + prettier bersih.

## Out of Scope (tetap)
Custom fields (#3), revisi/versioning, penjadwalan terbit, bulk actions, komentar, alt-text per-bahasa penuh, filter arsip per kategori/tag, "Tambahkan ke menu?" (subsistem Menu).
