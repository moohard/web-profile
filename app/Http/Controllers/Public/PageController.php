<?php

declare(strict_types=1);

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Models\PageTranslation;
use App\Models\WidgetPlacementTarget;
use App\Services\Html\Sanitizer;
use App\Support\PublicLayoutProps;
use Inertia\Inertia;
use Inertia\Response;

class PageController extends Controller
{
    /**
     * Halaman statis publik (catch-all 1 segment).
     */
    public function show(PageTranslation $translation): Response
    {
        // Defense-in-depth: sanitasi HTML mode-code sebelum dikirim ke frontend
        // (dirender via dangerouslySetInnerHTML di page-show.tsx).
        $content = $translation->content;
        if (is_array($content) && isset($content['html']) && is_string($content['html'])) {
            $content['html'] = app(Sanitizer::class)->clean($content['html']);
            $translation->content = $content;
        }

        $translation->load('page');
        $page = $translation->page;

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

        return Inertia::render('public/page-show', $props);
    }
}
