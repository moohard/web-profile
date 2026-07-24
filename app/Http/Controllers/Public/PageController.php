<?php

declare(strict_types=1);

namespace App\Http\Controllers\Public;

use App\Enums\PageMode;
use App\Enums\TestimonialStatus;
use App\Http\Controllers\Controller;
use App\Models\PageTranslation;
use App\Models\Testimonial;
use App\Models\WidgetPlacementTarget;
use App\Services\Html\Sanitizer;
use App\Support\Pages\PageTemplateRegistry;
use App\Support\PublicLayoutProps;
use App\Support\PublicLocaleLinks;
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

        // Defense-in-depth: sanitasi HTML mode-code sebelum dikirim ke frontend
        // (dirender via dangerouslySetInnerHTML di page-show.tsx).
        $content = $translation->content;
        if (is_array($content) && isset($content['html']) && is_string($content['html'])) {
            $content['html'] = $page->mode === PageMode::Code
                ? app(Sanitizer::class)->cleanCmsPage($content['html'])
                : app(Sanitizer::class)->cleanRichText($content['html']);
            $translation->content = $content;
        }

        // Bridging region per-halaman: widget (scoped ke halaman ini) + hero + sidebar on/off.
        $localeLinks = PublicLocaleLinks::page($page);
        $props = PublicLayoutProps::base($localeLinks);
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
        $props['templateKey'] = PageTemplateRegistry::resolve($page->template_key);
        $props['testimonials'] = $props['templateKey'] === 'testimonials'
            ? Testimonial::query()
                ->where('status', TestimonialStatus::Approved)
                ->orderBy('sort_order')
                ->latest('id')
                ->get()
                ->map(fn (Testimonial $testimonial): array => [
                    'id' => $testimonial->id,
                    'author_name' => $testimonial->author_name,
                    'author_title' => $testimonial->author_title,
                    'content' => $testimonial->content,
                    'photo_url' => $testimonial->getFirstMediaUrl('photo') ?: null,
                ])
                ->all()
            : [];

        $props['seo'] = SeoProps::for(
            title: $translation->meta_title ?: $translation->title,
            description: $translation->meta_description,
            canonical: url()->current(),
            hreflang: SeoProps::withXDefault(PublicLocaleLinks::hreflang($localeLinks)),
            ogType: 'website',
            ogImage: $props['region']['hero']['image'] ? URL::to($props['region']['hero']['image']) : null,
        );

        return Inertia::render('public/page-show', $props);
    }
}
