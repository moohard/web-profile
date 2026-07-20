<?php

declare(strict_types=1);

namespace App\Http\Controllers\Public;

use App\Enums\PageMode;
use App\Http\Controllers\Controller;
use App\Models\Page;
use App\Models\PageTranslation;
use App\Models\WidgetPlacementTarget;
use App\Services\Html\Sanitizer;
use App\Support\LocaleUrl;
use App\Support\PublicLayoutProps;
use App\Support\Seo\SeoProps;
use Illuminate\Support\Facades\URL;
use Inertia\Inertia;
use Inertia\Response;

class PageController extends Controller
{
    /**
     * Halaman statis publik (catch-all 1 segment).
     */
    public function show(PageTranslation $translation): Response
    {
        $translation->load('page');
        $page = $translation->page;

        // Halaman induk sudah di-trash (SoftDeletes): relasi null akibat global scope → 404,
        // bukan error 500 saat mengakses $page->id di bawah.
        abort_unless($page instanceof Page, 404);

        // Defense-in-depth: sanitasi HTML sebelum dikirim ke frontend (dirender via
        // dangerouslySetInnerHTML di page-show.tsx). Profil ikut mode halaman, sama
        // seperti saat disimpan: Template → rich-text (default); Code → cms_page.
        $content = $translation->content;
        if (is_array($content) && isset($content['html']) && is_string($content['html'])) {
            $sanitizer = app(Sanitizer::class);
            $content['html'] = $page->mode === PageMode::Code
                ? $sanitizer->clean($content['html'])
                : $sanitizer->cleanRichText($content['html']);
            $translation->content = $content;
        }

        // Bridging region per-halaman: widget (scoped ke halaman ini) + hero + sidebar on/off.
        $props = PublicLayoutProps::base();
        $props['region'] = PublicLayoutProps::region(WidgetPlacementTarget::TYPE_PAGE, (string) $page->id);
        $props['region']['hero'] = [
            'enabled' => (bool) $page->hero_enabled,
            'image' => $page->getFirstMediaUrl('hero_image') ?: $page->hero_image,
            'heading' => $translation->hero_heading,
            'subheading' => $translation->hero_subheading,
            'ctaText' => $translation->hero_cta_text,
            'ctaLink' => $translation->hero_cta_link,
        ];
        $props['region']['sidebar'] = [
            'enabled' => (bool) $page->sidebar_enabled,
        ];
        $props['page'] = $translation;

        // hreflang: setiap terjemahan halaman yang published (slug bisa beda per bahasa).
        $hreflang = [];
        foreach ($page->translations()->where('status', 'Published')->with('language')->get() as $tr) {
            if ($tr->language === null) {
                continue;
            }
            $hreflang[$tr->language->code] = URL::to(LocaleUrl::for($tr->language->code, '/'.$tr->slug));
        }

        $props['seo'] = SeoProps::for(
            title: $translation->meta_title ?: $translation->title,
            description: $translation->meta_description,
            canonical: url()->current(),
            hreflang: SeoProps::withXDefault($hreflang),
            ogType: 'website',
            ogImage: $props['region']['hero']['image'] ? URL::to($props['region']['hero']['image']) : null,
        );

        return Inertia::render('public/page-show', $props);
    }
}
