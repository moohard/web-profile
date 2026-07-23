# Posts D2–D3 Editor, Sanitizer, Excerpt, and Writing Styles Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use `superpowers:executing-plans` task-by-task. Every behavior change follows RED-GREEN-REFACTOR.

**Goal:** Menyediakan editor rich text Post yang SSR-safe, sanitasi HTML berbasis profile, helper excerpt Unicode, serta CRUD Writing Styles yang dapat dipakai AI refinement.

**Architecture:** `RichTextEditor` menjadi komponen terkontrol yang hanya memancarkan HTML ke form Post. Server tetap menjadi security boundary melalui `Sanitizer::cleanRichText()`, sementara Page mode Code dan markup AI mempertahankan profile `cms_page`. CRUD Writing Styles memakai Form Request dan controller resource tipis; relasi `ContentType::writingStyle()` tetap menjadi satu-satunya sumber gaya AI.

**Tech Stack:** Laravel 13.20, Inertia React 3, React 19.2, Tiptap 3.x, Purify/HTMLPurifier, Wayfinder 0.1, Pest 4.

## Context7 Decisions

- `/ueberdosis/tiptap-docs`: gunakan `useEditor`, `EditorContent`, `immediatelyRender: false`, serta `editor.commands.setContent(value, { emitUpdate: false })` untuk sinkronisasi tab bahasa tanpa loop update.
- `/stevebauman/purify`: profile bernama didefinisikan di `config/purify.php` dan dipanggil melalui `Purify::config('rich_text')->clean($html)`.
- `/laravel/docs/__branch__13.x`: authorization dan validasi berada di Form Request; mutasi CRUD memakai transaksi Eloquent; pembatasan excerpt memakai utility string Unicode-safe.
- `/pestphp/docs`: fixtures XSS dan edge case excerpt memakai dataset; acceptance HTTP memakai response/database assertions Laravel.
- `/inertiajs/docs`: `useHttp` dipakai untuk request JSON mandiri dari `MediaPicker`, sehingga modal tidak melakukan navigasi Inertia dan state draft editor tetap utuh.

---

### Task 1: Profile sanitizer dan helper excerpt

**Files:**
- Modify: `config/purify.php`
- Modify: `app/Services/Html/Sanitizer.php`
- Modify: `app/Support/helpers.php`
- Modify: `app/Http/Requests/Admin/PostRequest.php`
- Modify: `app/Http/Controllers/Admin/PostController.php`
- Modify: `app/Http/Controllers/Public/PostController.php`
- Modify: `tests/Feature/HtmlSanitizerTest.php`
- Create: `tests/Unit/ExcerptHelperTest.php`

- [x] **Step 1: Tulis test RED profile rich text**

Uji heading, paragraph, list, link HTTP/HTTPS/mailto, blockquote, image, strong/em/br dipertahankan; script, event attributes, style/class yang tak perlu, serta `javascript:`/`data:` dibuang. Pastikan profile `cms_page` tetap mempertahankan class design system.

- [x] **Step 2: Tulis test RED excerpt Unicode**

Uji null/kosong, strip tag, decode entity, collapse whitespace, batas panjang Unicode, tidak memotong grapheme/word bila memungkinkan, dan elipsis hanya saat terpotong.

- [x] **Step 3: Implementasikan API sanitizer eksplisit dan helper**

Tambahkan `cleanRichText()` dan `cleanCmsPage()`. Pertahankan `clean()` sebagai alias `cleanCmsPage()` untuk kompatibilitas sementara. Ubah semua jalur Post ke profile rich text; Page Code/widget/markup AI tetap profile CMS Page.

- [x] **Step 4: Jalankan test GREEN terfokus**

Jalankan `HtmlSanitizerTest`, `ExcerptHelperTest`, `PostValidationTest`, dan `PublicHtmlSanitizationTest`.

### Task 2: CRUD Writing Styles end-to-end

**Files:**
- Create: `app/Http/Controllers/Admin/WritingStyleController.php`
- Create: `app/Http/Requests/Admin/WritingStyleRequest.php`
- Modify: `routes/admin.php`
- Create: `resources/js/pages/admin/writing-styles/index.tsx`
- Modify: `tests/Feature/AdminPlaceholderRoutesTest.php`
- Create: `tests/Feature/Admin/WritingStyleCrudTest.php`

- [x] **Step 1: Tulis test RED authorization dan validasi**

Uji hanya Admin yang dapat CRUD, `name` wajib/unik/maksimal 255, `prompt` nullable dengan batas aman, dan index mengirim status pemakaian Content Type.

- [x] **Step 2: Tulis test RED lifecycle**

Uji create/update/delete, tolak delete bila direferensikan Content Type agar konfigurasi AI tidak berubah diam-diam, dan hapus placeholder expectation.

- [x] **Step 3: Implementasikan controller, request, route, dan UI**

Gunakan Wayfinder untuk semua action. UI inline menyediakan create/edit/delete dengan pesan validasi dan disabled delete saat dipakai.

- [x] **Step 4: Jalankan test GREEN dan regenerate Wayfinder**

Jalankan test CRUD, placeholder regression, TypeScript, dan generator Wayfinder.

### Task 3: Dependency dan komponen RichTextEditor

**Files:**
- Modify: `package.json`
- Modify: `package-lock.json`
- Create: `resources/js/components/admin/rich-text-editor.tsx`
- Modify: `resources/js/pages/admin/posts/form.tsx`

- [x] **Step 1: Tambahkan dependency Tiptap yang disetujui**

Pasang `@tiptap/react`, `@tiptap/starter-kit`, `@tiptap/extension-link`, dan `@tiptap/extension-image` tanpa extension tabel.

- [x] **Step 2: Implementasikan editor terkontrol SSR-safe**

Gunakan `immediatelyRender: false`; sinkronkan perubahan `value` dengan `setContent(value, { emitUpdate: false })`. Toolbar meliputi paragraph/H2/H3, bold, italic, bullet/ordered list, link aman, blockquote, dan gambar dari `MediaPicker`.

- [x] **Step 3: Integrasikan body Post**

Ganti hanya `<textarea>` body Post dengan `RichTextEditor`. Setiap panel bahasa tetap memiliki value independen; AI suggestion hanya memperbarui state form dan tidak menyimpan otomatis.

- [x] **Step 4: Verifikasi SSR dan switching locale**

Jalankan TypeScript, ESLint, Prettier, client build, SSR build, lalu pastikan kontrak source memuat `immediatelyRender: false` dan `emitUpdate: false`.

### Task 4: Persistence dan rendering HTML statis

**Files:**
- Modify: `tests/Feature/Admin/PostCrudTest.php`
- Modify: `tests/Feature/Public/PublicHtmlSanitizationTest.php`
- Modify: `tests/Feature/AiControllerTest.php`

- [x] **Step 1: Tulis test RED persistence**

POST rich HTML melalui endpoint admin, verifikasi HTML aman tersimpan dan fixture XSS dibuang. Uji Public single menerima HTML statis yang sudah disanitasi.

- [x] **Step 2: Pertahankan kontrak AI non-destruktif**

Pastikan endpoint AI hanya mengembalikan suggestion; tidak ada Post translation yang berubah sebelum submit form diterima.

- [x] **Step 3: Jalankan regression GREEN**

Jalankan seluruh test Post, AI, Page sanitizer, dan public HTML.

### Task 5: Quality gate dan review checkpoint

- [x] Jalankan `vendor/bin/pint --dirty --format agent`.
- [x] Jalankan PHPStan penuh.
- [x] Jalankan Pest untuk seluruh area terdampak.
- [x] Jalankan TypeScript, ESLint, Prettier, Wayfinder check, Vite client build, dan SSR build.
- [x] Jalankan review kepatuhan spec lalu review kualitas kode; Critical/Important wajib ditutup.
- [x] Commit dan push hasil Fase 4 sebelum memulai Fase 5.
