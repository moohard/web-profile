# Rencana Implementasi — Subsistem Halaman (Pages) + Mode Code + MARKUP_CONFORM

> **For agentic workers:** TDD per task (test → gagal → implement → hijau → verifikasi). Step pakai checkbox.

**Goal:** Custom page dinamis dikelola dari dashboard (PRD §4.2, §5, §6, §8.4, §9.2): CRUD Halaman + **editor dua-kolom** dengan **mode Template / mode Code (HTML-saja, khusus Admin)**, region per-halaman (hero on/off, sidebar on/off), slug/SEO/status per bahasa, dan **"Sesuaikan markup (AI)" = MARKUP_CONFORM** memakai referensi komponen design system.

**Prasyarat (REUSE — sudah ada):**
- Model: `Page` (mode PageMode Code/Template, template_key, hero_enabled, hero_image + media collection `hero_image` singleFile, sidebar_enabled; HasMedia; HasTranslations; #[UsePolicy(PagePolicy)]). `PageTranslation` (fillable page_id,language_id,slug,title,content(array cast, bentuk `{html: string}`),hero_heading,hero_subheading,hero_cta_text,hero_cta_link,status(string 'Draft'/'Published'),meta_title,meta_description).
- `PagePolicy` (viewAny/view/create/update/delete via `pages.*`; Admin/Editor punya `pages.*`, Author TIDAK). Gate `use-page-code-mode` = Admin only; shared prop `auth.user.canUseCodeMode`.
- Public: `PageController::show` (catch-all) sudah sanitasi `content.html`, render hero/sidebar + SEO (hreflang). `resources/js/pages/public/page-show.tsx` render `content.html` via dangerouslySetInnerHTML (single-h1 aware).
- Sanitizer `App\Services\Html\Sanitizer` (buang script/on*). `resources/js/app/global-components.ts` (scanner mode code, skeleton).
- AI: `AiClient` (driver **openai-compatible** → chat/completions), `AiController` (translate text-based + refine + ai.markup? belum), `AiConfig::resolve`, `AiConfigSeeder` (hybrid firstOrCreate). `MarkupConformTask` SKELETON (throw) — perlu diimplementasikan. Referensi komponen: `docs/design-system/component-reference.md`.
- Konten subsystem (K1–K6) selesai: pola `PostController`/`PostRequest`, `ContentSlug::unique`, `LanguageTabs` (punya prop opsional renderPanel), `DataTable`, `MediaPicker`, `AiSuggestButton` (mode translate/refine — tambah mode markup), toast via `Inertia::flash('toast', ...)`.

**Constraint:** PHP 8.4 strict_types + full types; Wayfinder semua URL React; NO new npm deps (HTML body = textarea); TDD Pest; `pint --dirty`; `php artisan make:*`; PHPStan/tsc/eslint/prettier hijau; **mode Code khusus Admin** (server + client enforce); HTML mode-code **wajib disanitasi** sebelum simpan & render.

---

## Keputusan Desain
1. **Kedua mode menyimpan `content.html` per bahasa** (area konten). Mode **Code** = editor HTML mentah + tombol "Sesuaikan markup (AI)" (khusus Admin). Mode **Template** = pilih `template_key` dari daftar hardcode (mis. `default`, `full-width`, `landing`); tersedia untuk Editor. `template_key` disimpan (dipakai shell publik ke depan; page-show saat ini generic).
2. **Enforce mode Code khusus Admin di server**: `PageRequest` menolak `mode=Code` bila `Gate::denies('use-page-code-mode')` (Editor non-admin → validation/403). Client sembunyikan opsi Code bila `!auth.user.canUseCodeMode`.
3. **MARKUP_CONFORM**: `ai_configs` task MarkupConform, provider chat (bootstrap dari MegaNova), `system_prompt` **memuat referensi komponen** (dari `docs/design-system/component-reference.md`) — sesuai PRD §5.1/§7.4. Output AI **disanitasi** sebelum dikembalikan/disimpan. Non-destruktif (saran → Terima/Batalkan).
4. **"Tambahkan ke menu?"** (§6.1) DITUNDA (subsistem Menu belum ada). Dicatat di Di Luar Lingkup.
5. Slug halaman unik per bahasa (`ContentSlug::unique` scoped `language_id`), auto dari title.

---

## File Structure
```
app/Http/Controllers/Admin/PageController.php     [Create] index/create/store/edit/update/destroy
app/Http/Requests/Admin/PageRequest.php           [Create] + enforce mode Code Admin-only
app/Http/Controllers/Admin/AiController.php        [Modify] + markupConform()
app/Services/Ai/Tasks/MarkupConformTask.php        [Modify] implement suggest()
database/seeders/AiConfigSeeder.php                [Modify] seed MarkupConform (MegaNova + component ref system_prompt)
routes/admin.php                                   [Modify] pages resource + ai.markup-conform
resources/js/pages/admin/pages/index.tsx          [Create] daftar + hapus
resources/js/pages/admin/pages/form.tsx           [Create] editor 2-kolom (mode + hero + sidebar + per-bahasa)
resources/js/components/admin/ai-suggest-button.tsx [Modify] tambah mode 'markup'
tests/Feature/Admin/PageCrudTest.php               [Create]
tests/Feature/Admin/PageEditorModeTest.php         [Create] template/code + admin-only code
tests/Feature/AiMarkupConformTest.php              [Create]
```

---

## Urutan Task

### H1 — Admin Pages: Daftar & Hapus
- [x] **Test dulu** `PageCrudTest`: Admin/Editor GET `/admin/pages` 200 + component + posts... daftar; filter status opsional; destroy (Admin/Editor); user tanpa `pages.viewAny` (Author = `access-admin` + posts saja) → 403.
- [x] `PageController` (index + destroy dulu, sisanya H2). index: `Page::with('translations')`, map ke ringkasan (id, title current-locale, mode, status current-locale, updated_at, editUrl Wayfinder). paginate 20. destroy authorize delete.
- [x] `routes/admin.php`: ganti placeholder `pages.index` → resource pages (index/create/store/{page}/edit/update/destroy). Wayfinder generate --with-form.
- [x] `pages/index.tsx`: DataTable (title, mode badge, status badge, updated) + Edit/Hapus.

### H2 — Editor Halaman (create/edit, dua-kolom §8.4)
- [x] **Test dulu** `PageCrudTest` (store/update) + `PageEditorModeTest`:
  - store membuat Page (mode, template_key, hero_enabled, sidebar_enabled, hero_image) + PageTranslation per bahasa (slug auto, title, content.html sanitasi, hero_*, status, meta_*).
  - update upsert translations + ganti hero image.
  - **mode Code oleh non-Admin ditolak** (Editor kirim mode=Code → 403/validation); Admin boleh.
  - content.html disanitasi (script dibuang) saat store.
- [x] `PageRequest`: mode required in Code,Template; template_key string; hero_enabled/sidebar_enabled boolean; hero_image nullable string; translations.*.language_id/title/slug?/content?/hero_*?/status/meta_*. authorize: create/update via PagePolicy; DAN bila mode=Code wajib `Gate::allows('use-page-code-mode')`.
- [x] `PageController` create/store/edit/update: DB::transaction; upsert PageTranslation (content = ['html' => Sanitizer::clean(html)]); hero_image via MediaPicker (simpan url di Page.hero_image atau media collection). Flash toast. create/edit kirim: languages, canUseCodeMode, template options (hardcode list).
- [x] `pages/form.tsx`: KOLOM UTAMA = LanguageTabs; toggle mode (Template↔Code, opsi Code hanya bila canUseCodeMode); Template → select template_key; Code → `<textarea>` HTML + tombol "Sesuaikan markup (AI)" (H3). KOLOM SAMPING = hero on/off + hero_image (MediaPicker) + per-bahasa hero_heading/subheading/cta; sidebar on/off; slug; status; SEO (meta_title/meta_description). useForm + Wayfinder.

### H3 — MARKUP_CONFORM (AI sesuaikan markup)
- [x] **Test dulu** `AiMarkupConformTest`: POST `/admin/ai/markup-conform` (source_html) → {suggestion} (mock MarkupConformTask); output disanitasi (bila mock kembalikan `<script>`, controller strip); user tanpa `admin.use-page-code-mode`/bukan Admin → 403.
- [x] `MarkupConformTask::suggest(string $html, string $componentReference = ''): string` — hapus throw; prompt: "Sesuaikan HTML berikut ke design system (class/struktur). JANGAN tambah `<script>`/atribut on*. Output HANYA HTML." + `->task(AiTask::MarkupConform)->chat($prompt)`. Referensi komponen ada di `ai_configs.MarkupConform.system_prompt`.
- [x] `AiController::markupConform(Request)`: validasi source_html required; authorize `Gate::allows('use-page-code-mode')` (Admin) else 403; panggil task; **sanitasi hasil** via Sanitizer sebelum return {suggestion}. Route `POST /admin/ai/markup-conform` middleware `permission:ai.update` + `throttle:30,1` name `ai.markup-conform`.
- [x] `AiConfigSeeder`: seed task MarkupConform bila `MEGANOVA_API_KEY` ada — base_url/model MegaNova, `system_prompt` = isi `docs/design-system/component-reference.md` (via file_get_contents di seeder—boleh, ini seeder), enabled. firstOrCreate (jangan timpa edit admin).
- [x] `ai-suggest-button.tsx`: tambah dukungan mode markup (endpoint ai.markup-conform, payload {source_html}). Wire ke `pages/form.tsx` mode Code (tombol "Sesuaikan markup (AI)") → onAccept set content.html.

### H4 — Nav & Verifikasi
- [x] Sidebar "Halaman" (`pages.index`) sudah Wayfinder → arahkan ke controller nyata (otomatis). Hapus placeholder pages.
- [x] Verifikasi akhir: `php artisan test --compact` hijau; pint/phpstan/tsc/eslint/prettier/build:ssr hijau. Smoke: buat page Template (Editor) + page Code (Admin) → tampil di `/{slug}`; "Sesuaikan markup" memunculkan saran.

---

## Kriteria Selesai
- CRUD Halaman dari dashboard; editor dua-kolom mode Template/Code; mode Code khusus Admin (server+client); hero/sidebar per-halaman; slug/SEO/status per bahasa.
- MARKUP_CONFORM fungsional (provider chat), non-destruktif, output disanitasi, memakai referensi komponen.
- Halaman yang dibuat tampil benar di frontend publik (SSR/SEO/hreflang/sanitasi sudah ada).
- Semua quality gate hijau.

## Di Luar Lingkup
- **"Tambahkan ke menu?"** + subsistem Menu (rencana terpisah).
- Widget management UI, Galeri, custom fields (Open Item #3).
- WYSIWYG (butuh dep); pratinjau mobile; interaktivitas komponen global penuh (global-components tetap skeleton).
- Template rendering bervariasi di publik (template_key disimpan; page-show tetap generic untuk sekarang).
