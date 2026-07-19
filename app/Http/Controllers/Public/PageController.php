<?php

declare(strict_types=1);

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Models\PageTranslation;
use Inertia\Inertia;
use Inertia\Response;

class PageController extends Controller
{
    /**
     * Halaman statis publik (catch-all 1 segment).
     */
    public function show(PageTranslation $translation): Response
    {
        return Inertia::render('public/page-show', [
            'page' => $translation->load('page'),
        ]);
    }
}
