# Page Template, AI, and Preview Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use `superpowers:executing-plans` task-by-task. Setiap perubahan perilaku mengikuti RED–GREEN–REFACTOR.

**Goal:** Menjadikan Page mode Template editor rich text yang lengkap, mempertahankan Code mode khusus Admin, menyediakan AI Translation/Refinement non-destruktif, preview draft tersanitasi, dan registry template publik yang tetap.

**Architecture:** `PageTemplateRegistry` menjadi sumber tunggal key `default`, `full-width`, dan `landing` untuk validasi admin, preview, serta renderer publik. Preview memakai endpoint JSON/Inertia standalone yang hanya merender draft tervalidasi dan tersanitasi tanpa persistensi. AI tetap memakai endpoint suggestion yang sudah ada; hasil baru masuk form setelah pengguna memilih Terima.

**Tech Stack:** Laravel 13.20, Laravel AI 0.9.1, Inertia Laravel/React 3.1.1, React 19.2, Tiptap 3.28, Wayfinder 0.1.20, Pest 4.7.5, Tailwind CSS 4.

## Context7 Decisions

- `/laravel/ai`: endpoint AI diuji dengan fake/mock response dan tidak pernah menyimpan hasil suggestion secara otomatis.
- `/inertiajs/docs`: preview dan saran AI memakai standalone HTTP request agar tidak memicu navigation lifecycle.
- `/ueberdosis/tiptap-docs`: mode Template memakai editor dengan `immediatelyRender: false`; sinkronisasi tab bahasa tetap `setContent(value, { emitUpdate: false })`.
- `/laravel/wayfinder`: endpoint preview dan AI di client selalu berasal dari helper action/route typed.

---

### Task 1: Registry template dan validation contract

**Files:**

- Create: `app/Support/Pages/PageTemplateRegistry.php`
- Modify: `app/Http/Controllers/Admin/PageController.php`
- Modify: `app/Http/Requests/Admin/PageRequest.php`
- Modify: `tests/Feature/Admin/PageCrudTest.php`

- [ ] **Step 1: Tulis test RED registry tetap**

Uji hanya key `default`, `full-width`, dan `landing` yang diterima; key lain ditolak dan tidak ada path upload/eksekusi template dinamis.

- [ ] **Step 2: Implementasikan registry tunggal**

Controller mengambil option dari registry, request memakai `Rule::in(PageTemplateRegistry::keys())`, dan Code mode selalu menyimpan key aman.

- [ ] **Step 3: Lengkapi validasi Published dan SEO**

Translation Published wajib title+content; Draft bahasa default wajib title; `meta_title` maksimal 60 dan `meta_description` maksimal 160.

### Task 2: RichTextEditor untuk mode Template

**Files:**

- Modify: `resources/js/pages/admin/pages/form.tsx`
- Reuse: `resources/js/components/admin/rich-text-editor.tsx`
- Create/Modify: UI contract tests Page

- [ ] **Step 1: Tulis test RED mode-specific editor**

Mode Template wajib memakai `RichTextEditor`; Mode Code tetap `<textarea>` HTML mentah dan tidak tersedia bagi Editor.

- [ ] **Step 2: Implementasikan editor terkontrol per bahasa**

Perubahan tab menyinkronkan value tanpa update palsu; toolbar/media mengikuti editor Post.

### Task 3: AI Translation dan Content Refinement non-destruktif

**Files:**

- Modify: `resources/js/pages/admin/pages/form.tsx`
- Modify: `resources/js/components/admin/ai-suggest-button.tsx` bila diperlukan
- Modify: `tests/Feature/AiControllerTest.php`
- Modify/Create: UI contract tests Page

- [ ] **Step 1: Tulis test RED Page AI flow**

Uji translation/refinement menerima Page content, hasil tidak mengubah DB, dan sanitasi baru terjadi saat diterima/disimpan sesuai mode.

- [ ] **Step 2: Implementasikan tombol suggestion**

Terjemahkan memakai bahasa sumber/target aktif; Koreksi memakai content aktif. Panel tinjau menyediakan Terima/Batalkan dan tidak memanggil autosave.

### Task 4: Preview draft tersanitasi

**Files:**

- Create: `app/Http/Requests/Admin/PreviewPageRequest.php`
- Create: `app/Http/Controllers/Admin/PagePreviewController.php`
- Modify: `routes/admin.php`
- Modify: `resources/js/pages/admin/pages/form.tsx`
- Create: `resources/js/components/admin/page-preview-dialog.tsx`
- Create: `tests/Feature/Admin/PagePreviewTest.php`

- [ ] **Step 1: Tulis test RED authorization/validation/sanitizer**

Uji Editor dapat preview Template, hanya Admin dapat preview Code, registry key wajib valid, Published/Draft draft tervalidasi, dan script/event/javascript URL dibuang.

- [ ] **Step 2: Implementasikan endpoint tanpa persistence**

Request menghasilkan payload typed; controller memilih profil `rich_text` untuk Template dan `cms_page` untuk Code lalu mengembalikan JSON preview.

- [ ] **Step 3: Implementasikan dialog preview**

Form mengirim draft aktif melalui endpoint Wayfinder dan merender hasil tersanitasi dalam dialog; loading/error/empty state eksplisit.

### Task 5: Renderer template publik dan pending Menu

**Files:**

- Modify: `app/Http/Controllers/Public/PageController.php`
- Modify: `resources/js/pages/public/page-show.tsx`
- Modify: `resources/js/pages/admin/pages/form.tsx`
- Modify/Create: public/UI contract tests

- [ ] **Step 1: Tulis test RED template prop**

Publik menerima `templateKey` hanya dari registry dan fallback ke `default`; class/layout berbeda untuk `default`, `full-width`, dan `landing`.

- [ ] **Step 2: Implementasikan renderer registry**

Tidak ada include PHP, upload template, atau eksekusi JS dinamis. Mode Code tetap memakai shell aman yang sama.

- [ ] **Step 3: Tampilkan pending integration Menu**

Opsi “Tambahkan ke menu” tampil disabled dengan penjelasan akan aktif setelah API Menu Fase 8 tersedia.

### Task 6: Quality gate dan review checkpoint

- [ ] Jalankan Pint dan PHPStan penuh.
- [ ] Jalankan seluruh Pest area Page, AI, sanitizer, public routing, dan UI contract.
- [ ] Jalankan TypeScript, ESLint, Prettier, Wayfinder generation/check, dependency audit.
- [ ] Jalankan Vite client build, SSR build, dan browser smoke Page tanpa error JavaScript.
- [ ] Jalankan review kepatuhan spec lalu review kualitas; tutup seluruh Critical/Important.
- [ ] Commit dan push Fase 6 sebelum memulai Fase 7.
