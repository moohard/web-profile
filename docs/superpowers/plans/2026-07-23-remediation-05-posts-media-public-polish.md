# Posts D4–D5 Media, Public Rendering, and Admin Polish Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use `superpowers:executing-plans` task-by-task. Setiap perubahan perilaku mengikuti RED–GREEN–REFACTOR.

**Goal:** Menyelaraskan featured media Post dengan Media Library, melengkapi payload/UI publik archive dan single, serta menutup polish admin untuk status multilingual, quick-create Tag, tree Category, pagination, filter, loading, dan empty state.

**Architecture:** Form Post hanya mengirim `featured_media_id`; `SyncPostFeaturedMedia` menjadi boundary tunggal yang menyalin media terpilih ke koleksi `featured` milik Post. Serializer media publik menghasilkan kontrak responsif yang sama untuk archive, single, SEO, dan JSON-LD. Mutasi Tag cepat berada dalam action transaksional dan Category tree dibangun server-side agar seluruh client menerima urutan deterministik.

**Tech Stack:** Laravel 13.20, Inertia Laravel/React 3.1.1, React 19.2, Spatie Media Library 11.23.2, Wayfinder 0.1.20, Pest 4.7.5, Tailwind CSS 4.

## Context7 Decisions

- `/spatie/laravel-medialibrary`: gunakan koleksi `featured->singleFile()->withResponsiveImages()`, `$sourceMedia->copy($post, 'featured')`, `getUrl()`, `getUrl('webp_large')`, dan `getSrcset('webp_large')`.
- `/inertiajs/docs`: pagination/filter memakai visit GET dengan `preserveState`/`preserveScroll`; loading diturunkan dari event navigation dan empty state tetap eksplisit.
- `/laravel/wayfinder`: seluruh URL/action client memakai helper typed; query memakai `.url({ query: ... })`, bukan hardcoded admin URL.

---

### Task 1: Kontrak featured media dan lifecycle

**Files:**

- Create: `database/migrations/*_drop_featured_image_from_posts_table.php`
- Create: `app/Actions/Posts/SyncPostFeaturedMedia.php`
- Modify: `app/Models/Post.php`
- Modify: `app/Http/Requests/Admin/PostRequest.php`
- Modify: `app/Http/Controllers/Admin/PostController.php`
- Modify: `app/Http/Controllers/Admin/MediaController.php`
- Modify: `resources/js/pages/admin/posts/form.tsx`
- Modify: `resources/js/pages/admin/media/index.tsx`
- Modify: tests media/Post terkait

- [x] **Step 1: Tulis test RED migration dan form contract**

Uji kolom `featured_image` hilang, request menerima `featured_media_id`, ID harus media image valid, dan prop form mengirim `{id,url,thumb_url,alt}`.

- [x] **Step 2: Tulis test RED sync media**

Uji create/update menyalin source media tanpa menghapusnya, koleksi `featured` hanya satu file, memilih ID yang sama idempotent, dan null membersihkan featured.

- [x] **Step 3: Implementasikan action dan integrasi transaction**

Action menangani no-op, clear, dan copy. Controller tetap tipis dan memanggil action dalam transaction Post bersama tag/translations.

- [x] **Step 4: Regresi conversion dan permanent delete**

Selaraskan seluruh penggunaan koleksi lama `featured_image` menjadi `featured`; pastikan responsive conversion dan force-delete cleanup tetap lulus.

### Task 2: Payload dan UI publik archive/single

**Files:**

- Create: `app/Support/Posts/PostFeaturedImage.php`
- Modify: `app/Http/Controllers/Public/PostController.php`
- Modify: `resources/js/pages/public/post-archive.tsx`
- Modify: `resources/js/pages/public/post-show.tsx`
- Modify: tests public routing/SEO

- [x] **Step 1: Tulis test RED archive payload**

Uji title, URL, excerpt, featured responsive image, tanggal, kategori, serta metadata/links paginator dan query page yang dipertahankan.

- [x] **Step 2: Tulis test RED single payload dan SEO fallback**

Uji image, tanggal, kategori, tags, body statis, JSON-LD, serta fallback meta/OG description dari excerpt saat meta description kosong.

- [x] **Step 3: Implementasikan serializer dan controller**

Eager-load relasi untuk mencegah N+1; output tidak mengekspos model mentah dan menggunakan serializer media yang sama.

- [x] **Step 4: Implementasikan archive/single UI**

Archive memakai responsive card grid, pagination, empty/loading state; single menampilkan metadata, image responsive, tags, dan body aman.

### Task 3: Status multilingual dan filter/pagination admin

**Files:**

- Modify: `app/Http/Controllers/Admin/PostController.php`
- Modify: `resources/js/pages/admin/posts/index.tsx`
- Modify: `tests/Feature/Admin/PostCrudTest.php`

- [x] **Step 1: Tulis test RED status semua bahasa aktif**

Setiap row mengirim daftar `{code,name,status}` untuk seluruh bahasa aktif, termasuk status null bila translation belum ada.

- [x] **Step 2: Implementasikan UI dan navigation state**

Tampilkan badge per bahasa, pagination admin, loading state saat filter/page visit, empty state, dan pertahankan query type/status.

### Task 4: Quick-create Tag transaksional

**Files:**

- Create: `app/Actions/Tags/FindOrCreateTag.php`
- Create: `app/Http/Requests/Admin/QuickStoreTagRequest.php`
- Modify: `app/Http/Controllers/Admin/TagController.php`
- Modify: `routes/admin.php`
- Modify: `resources/js/pages/admin/posts/form.tsx`
- Modify: `tests/Feature/Admin/TagCrudTest.php`

- [x] **Step 1: Tulis test RED authorization, duplicate, dan transaction**

Uji permission `tags.create`, bahasa aktif, nama trimmed, duplikasi nama case-insensitive per bahasa mengembalikan Tag lama, dan nama sama pada bahasa berbeda tetap dapat diterjemahkan tanpa Tag duplikat yang tak perlu.

- [x] **Step 2: Implementasikan endpoint JSON/action**

Action memakai transaksi + lock, menghasilkan slug aman, dan mengembalikan `{id,name}`. Route dan request tetap typed melalui Wayfinder.

- [x] **Step 3: Implementasikan create-on-type**

Form Post menyediakan input/tag button dengan loading/error state; hasil baru langsung terpilih tanpa menyimpan Post otomatis.

### Task 5: Category tree dan cycle guard

**Files:**

- Modify: `app/Http/Requests/Admin/CategoryRequest.php`
- Modify: `app/Http/Controllers/Admin/CategoryController.php`
- Modify: `app/Http/Controllers/Admin/PostController.php`
- Modify: `resources/js/pages/admin/categories/index.tsx`
- Modify: `tests/Feature/Admin/CategoryCrudTest.php`

- [x] **Step 1: Tulis test RED self/descendant cycle**

Tolak parent dirinya sendiri dan parent descendant pada update.

- [x] **Step 2: Implementasikan tree deterministik**

Bangun urutan depth-first berdasarkan `sort_order`, kirim `depth`, render indent pada daftar Category dan opsi kategori/parent.

### Task 6: Quality gate dan review checkpoint

- [x] Jalankan Pint dan PHPStan penuh.
- [x] Jalankan seluruh Pest area Post/Media/Tag/Category/Public.
- [x] Jalankan TypeScript, ESLint, Prettier, Wayfinder generation/check, dependency audit.
- [x] Jalankan Vite client build, SSR build, dan public SSR/browser smoke tanpa error JavaScript.
- [x] Jalankan review kepatuhan spec lalu review kualitas; tutup seluruh Critical/Important.
- [x] Commit dan push Fase 5 sebelum memulai Fase 6.
