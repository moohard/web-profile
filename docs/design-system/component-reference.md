# Design System Reference — Papenajam CMS

Referensi komponen untuk `MARKUP_CONFORM` (AI menyesuaikan HTML admin ke design system ini). Ditambah saat komponen baru dibuat.

## Hero

```html
<section class="hero relative min-h-[300px] bg-slate-900 text-white">
    <div class="relative z-10 mx-auto max-w-6xl p-8">
        <h1 class="text-3xl font-bold md:text-5xl">Judul Hero</h1>
        <p class="mt-4 text-lg text-white/90">Subjudul</p>
        <a href="#" class="mt-6 inline-block rounded bg-primary px-6 py-3">CTA</a>
    </div>
</section>
```

## Section

```html
<section class="mx-auto max-w-6xl p-8">
    <h2 class="mb-4 text-2xl font-semibold">Judul Section</h2>
    <!-- content -->
</section>
```

## Card

```html
<article class="rounded-lg border bg-white p-6 shadow-sm">
    <h3 class="text-lg font-semibold">Judul Card</h3>
    <p class="mt-2 text-muted-foreground">Deskripsi</p>
</article>
```

## Button

```html
<a href="#" class="inline-block rounded bg-primary px-4 py-2 text-primary-foreground hover:bg-primary/90">Label</a>
```

## Grid (3 kolom)

```html
<div class="grid grid-cols-1 gap-6 md:grid-cols-3">
    <article>...</article>
    <article>...</article>
    <article>...</article>
</div>
```
