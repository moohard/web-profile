# Permission and Validation Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use `superpowers:executing-plans` to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Menjadikan permission Admin/Editor/Author dan validasi Post sesuai PRD v1.1 serta spesifikasi Posts.

**Architecture:** Spatie Permission menjadi sumber capability; policy hanya menggabungkan capability dengan ownership. Form Request memvalidasi struktur dasar, lalu `after()` menangani invariant lintas-translation.

**Tech Stack:** Laravel 13.20, Spatie Permission 8.3, Pest 4, Inertia React 3.

## Global Constraints

- Category dan Tag memakai permission resource sendiri, bukan `content-types.*`.
- Editor dapat memakai AI translate/refine/apply tetapi tidak dapat membuka konfigurasi AI atau mode Code.
- Author hanya mengubah Post miliknya dan tidak memperoleh permission interaksi.
- AI suggestion tidak pernah auto-save.
- Semua implementasi mengikuti RED-GREEN-REFACTOR.

---

### Task 1: Kunci matriks permission melalui tes

**Files:**
- Modify: `tests/Feature/SeedersTest.php`
- Modify: `tests/Feature/Admin/CategoryCrudTest.php`
- Modify: `tests/Feature/Admin/TagCrudTest.php`
- Modify: `tests/Feature/AiControllerTest.php`
- Modify: `tests/Feature/AdminSidebarVisibilityTest.php`

**Interfaces:**
- Produces: capability `categories.{viewAny,create,update,delete}` dan `tags.{viewAny,create,update,delete}`.
- Produces: Editor memiliki `ai.create` dan `ai.update`; Author tidak.

- [x] **Step 1: Tulis tes gagal untuk permission role**

Tambahkan assertion bahwa Editor memiliki categories/tags/AI, tidak memiliki content-types/system/appearance, dan Author tetap hanya posts/media.

- [x] **Step 2: Jalankan tes RED**

Run:

```bash
APP_KEY='base64:MDEyMzQ1Njc4OWFiY2RlZjAxMjM0NTY3ODlhYmNkZWY=' php artisan test --compact tests/Feature/SeedersTest.php tests/Feature/Admin/CategoryCrudTest.php tests/Feature/Admin/TagCrudTest.php tests/Feature/AiControllerTest.php tests/Feature/AdminSidebarVisibilityTest.php
```

Expected: gagal karena permission Category/Tag/AI belum dimiliki Editor dan tes AI lama masih mengharapkan 403.

- [x] **Step 3: Implementasikan seeder dan policy minimum**

Ubah resource permission, `syncPermissions`, CategoryPolicy, TagPolicy, dan permission sidebar tanpa mengubah capability Content Type.

- [x] **Step 4: Jalankan tes GREEN**

Run perintah Step 2.

Expected: seluruh tes terpilih lulus.

- [x] **Step 5: Commit**

```bash
git add database/seeders/RolePermissionSeeder.php app/Policies/CategoryPolicy.php app/Policies/TagPolicy.php resources/js/components/admin/sidebar-nav-config.ts tests/Feature
git commit -m "fix(auth): align editor permissions with PRD"
```

### Task 2: Validasi Published dan metadata SEO

**Files:**
- Modify: `tests/Feature/Admin/ContentEditorTranslationTest.php`
- Modify: `app/Http/Requests/Admin/PostRequest.php`

**Interfaces:**
- Consumes: `translations.*.{language_id,title,body,status,meta_title,meta_description}`.
- Produces: error pada path translation spesifik untuk title/body Published dan batas metadata.

- [x] **Step 1: Tulis tes gagal untuk Published**

Kasus wajib:

```php
it('menolak translation Published tanpa title dan body', function () {
    // kirim bahasa default valid dan bahasa EN Published tanpa title/body
    // assertSessionHasErrors pada translations.1.title dan translations.1.body
});
```

Tambahkan dataset meta title 61 karakter dan meta description 161 karakter.

- [x] **Step 2: Jalankan tes RED**

```bash
APP_KEY='base64:MDEyMzQ1Njc4OWFiY2RlZjAxMjM0NTY3ODlhYmNkZWY=' php artisan test --compact tests/Feature/Admin/ContentEditorTranslationTest.php
```

Expected: Published tanpa body diterima dan metadata panjang belum ditolak.

- [x] **Step 3: Implementasikan validation minimum**

- Gunakan `after(): array` dengan closure `Validator`.
- Title translation nullable pada rules dasar agar bahasa non-default Draft boleh kosong.
- Tambahkan error spesifik bila status Published tetapi title/body kosong.
- Pastikan bahasa default tetap memiliki title.
- Batasi `meta_title` 60 dan `meta_description` 160.

- [x] **Step 4: Jalankan tes GREEN**

Run perintah Step 2.

Expected: seluruh tes ContentEditorTranslation lulus.

- [x] **Step 5: Commit**

```bash
git add app/Http/Requests/Admin/PostRequest.php tests/Feature/Admin/ContentEditorTranslationTest.php
git commit -m "fix(posts): enforce published translation validation"
```

### Task 3: Review dan quality gate fase

**Files:**
- Review: seluruh diff sejak baseline.

- [x] **Step 1: Jalankan formatter PHP**

```bash
vendor/bin/pint --dirty --format agent
```

- [x] **Step 2: Jalankan PHPStan**

```bash
vendor/bin/phpstan analyse --no-progress
```

- [x] **Step 3: Jalankan seluruh tes area**

```bash
APP_KEY='base64:MDEyMzQ1Njc4OWFiY2RlZjAxMjM0NTY3ODlhYmNkZWY=' php artisan test --compact tests/Feature/SeedersTest.php tests/Feature/PostPolicyTest.php tests/Feature/Admin/CategoryCrudTest.php tests/Feature/Admin/TagCrudTest.php tests/Feature/AiControllerTest.php tests/Feature/Admin/ContentEditorTranslationTest.php tests/Feature/AdminSidebarVisibilityTest.php
```

- [x] **Step 4: Review kepatuhan**

Verifikasi Category/Tag tidak lagi bergantung `content-types.*`, Editor dapat AI non-Code, Author tetap dibatasi ownership, dan validasi Published menghasilkan 422/session errors.

- [x] **Step 5: Commit plan checkpoint**

```bash
git add docs/superpowers/plans/2026-07-23-prd-v1-1-remediation-roadmap.md docs/superpowers/plans/2026-07-23-remediation-01-permission-validation.md
git commit -m "docs(plan): add PRD remediation roadmap and phase one"
```
