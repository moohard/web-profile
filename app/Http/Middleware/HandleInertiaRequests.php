<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Models\ContentType;
use App\Models\ContentTypeTranslation;
use App\Models\Language;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Gate;
use Inertia\Middleware;

class HandleInertiaRequests extends Middleware
{
    /**
     * The root template that's loaded on the first page visit.
     *
     * @see https://inertiajs.com/server-side-setup#root-template
     *
     * @var string
     */
    protected $rootView = 'app';

    /**
     * Determines the current asset version.
     *
     * @see https://inertiajs.com/asset-versioning
     */
    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    /**
     * Define the props that are shared by default.
     *
     * @see https://inertiajs.com/shared-data
     *
     * @return array<string, mixed>
     */
    public function share(Request $request): array
    {
        $user = $request->user();

        return [
            ...parent::share($request),
            'name' => config('app.name'),
            'locale' => app()->getLocale(),
            'auth' => [
                'user' => $user ? array_merge($user->only(['id', 'name', 'email']), [
                    'roles' => $user->getRoleNames()->toArray(),
                    'permissions' => $user->getAllPermissions()->pluck('name')->toArray(),
                    'canUseCodeMode' => Gate::forUser($user)->allows('use-page-code-mode'),
                ]) : null,
            ],
            // Cache per locale; aman jika tabel languages belum terisi (mis. test tanpa seed)
            'contentTypes' => $user ? Cache::remember(
                'inertia.content_types.'.app()->getLocale(),
                now()->addHour(),
                function () {
                    $langId = Language::query()
                        ->where('code', app()->getLocale())
                        ->value('id');

                    return ContentType::active()
                        ->when(
                            $langId !== null,
                            fn ($q) => $q->with(['translations' => function ($tq) use ($langId) {
                                $tq->where('language_id', $langId);
                            }])
                        )
                        ->get()
                        ->map(function (ContentType $ct): array {
                            $translation = $ct->relationLoaded('translations')
                                ? $ct->translations->first()
                                : null;

                            return [
                                'slug' => $ct->slug,
                                'name' => $translation instanceof ContentTypeTranslation
                                    ? $translation->name
                                    : ucfirst($ct->slug),
                            ];
                        })
                        ->values()
                        ->toArray();
                }
            ) : [],
            'sidebarOpen' => ! $request->hasCookie('sidebar_state') || $request->cookie('sidebar_state') === 'true',
        ];
    }
}
