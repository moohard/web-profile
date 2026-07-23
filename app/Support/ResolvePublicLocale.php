<?php

declare(strict_types=1);

namespace App\Support;

use App\Models\Language;
use Illuminate\Http\Request;
use Throwable;

class ResolvePublicLocale
{
    /**
     * @return array{locale: string, normalizedPath: string, canonical: ?string, notFound: bool}
     */
    public function handle(Request $request): array
    {
        $requestPath = trim($request->path(), '/');
        $segments = $requestPath === '' ? [] : explode('/', $requestPath);

        try {
            $languages = Language::query()->get(['code', 'is_active', 'is_default']);
            $default = $languages->where('is_active', true)->firstWhere('is_default', true)
                ?? Language::defaultModel();
        } catch (Throwable) {
            return [
                'locale' => (string) config('app.locale', 'id'),
                'normalizedPath' => $requestPath,
                'canonical' => null,
                'notFound' => false,
            ];
        }

        $prefixedLanguage = $languages->firstWhere('code', $segments[0] ?? null);

        if (! $prefixedLanguage instanceof Language) {
            return [
                'locale' => $default->code,
                'normalizedPath' => $requestPath,
                'canonical' => null,
                'notFound' => false,
            ];
        }

        if (! $prefixedLanguage->is_active) {
            return [
                'locale' => $default->code,
                'normalizedPath' => $requestPath,
                'canonical' => null,
                'notFound' => true,
            ];
        }

        $normalizedPath = implode('/', array_slice($segments, 1));

        if ($prefixedLanguage->is_default) {
            $canonical = $normalizedPath === '' ? '/' : '/'.$normalizedPath;
            $queryString = $request->getQueryString();

            return [
                'locale' => $default->code,
                'normalizedPath' => $normalizedPath,
                'canonical' => $canonical.($queryString ? '?'.$queryString : ''),
                'notFound' => false,
            ];
        }

        return [
            'locale' => $prefixedLanguage->code,
            'normalizedPath' => $normalizedPath,
            'canonical' => null,
            'notFound' => false,
        ];
    }
}
