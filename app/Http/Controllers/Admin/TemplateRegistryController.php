<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Support\Pages\PageTemplateRegistry;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class TemplateRegistryController extends Controller
{
    /**
     * Daftar template registry (read-only) untuk page, arsip, single.
     */
    public function index(): Response
    {
        Gate::authorize('admin.access-system');

        return Inertia::render('admin/templates/index', [
            'templates' => PageTemplateRegistry::options(),
        ]);
    }
}
