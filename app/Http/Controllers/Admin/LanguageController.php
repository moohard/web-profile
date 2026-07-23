<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Actions\Languages\DeleteLanguage;
use App\Actions\Languages\SaveLanguage;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\LanguageRequest;
use App\Models\Language;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class LanguageController extends Controller
{
    public function index(): Response
    {
        Gate::authorize('admin.access-system');

        return Inertia::render('admin/languages/index', [
            'languages' => Language::query()
                ->orderByDesc('is_default')
                ->orderBy('sort_order')
                ->orderBy('name')
                ->get(['id', 'code', 'name', 'is_active', 'is_default', 'sort_order'])
                ->map(fn (Language $language): array => [
                    'id' => $language->id,
                    'code' => $language->code,
                    'name' => $language->name,
                    'is_active' => $language->is_active,
                    'is_default' => $language->is_default,
                    'sort_order' => $language->sort_order,
                    'is_in_use' => $language->isInUse(),
                ]),
        ]);
    }

    public function store(LanguageRequest $request, SaveLanguage $saveLanguage): RedirectResponse
    {
        $saveLanguage->handle($request->languageData());

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Bahasa berhasil dibuat.']);

        return to_route('admin.settings.languages.index');
    }

    public function update(
        LanguageRequest $request,
        Language $language,
        SaveLanguage $saveLanguage,
    ): RedirectResponse {
        $saveLanguage->handle($request->languageData(), $language);

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Bahasa berhasil diperbarui.']);

        return to_route('admin.settings.languages.index');
    }

    public function destroy(Language $language, DeleteLanguage $deleteLanguage): RedirectResponse
    {
        Gate::authorize('admin.access-system');
        $deleteLanguage->handle($language);

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Bahasa berhasil dihapus.']);

        return to_route('admin.settings.languages.index');
    }
}
