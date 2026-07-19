<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Models\Language;
use App\Support\LocaleUrl;
use Closure;
use Illuminate\Http\Request;
use ReflectionProperty;
use Symfony\Component\HttpFoundation\Request as SymfonyRequest;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

class SetLocale
{
    /**
     * Deteksi prefix locale di segment-1 URL, set app locale, dan strip prefix
     * dari path request agar controller tidak perlu peduli locale.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Area admin tidak memakai prefix locale — biarkan apa adanya
        if ($request->is('admin') || $request->is('admin/*') || $request->segment(1) === 'admin') {
            return $next($request);
        }

        $segment = $request->segment(1) ?? '';

        if (LocaleUrl::isNonDefaultLocale($segment)) {
            app()->setLocale($segment);

            // Hapus prefix dari path supaya controller tidak perlu peduli locale
            $stripped = '/'.ltrim(substr($request->path(), strlen($segment)), '/');
            $this->setPathInfo($request, $stripped === '' ? '/' : $stripped);
        } else {
            // Fallback ke config bila tabel languages kosong / cache rusak (tes, first install)
            try {
                app()->setLocale(Language::defaultModel()->code);
            } catch (Throwable) {
                app()->setLocale(config('app.locale', 'id'));
            }
        }

        return $next($request);
    }

    /**
     * Set pathInfo request (Symfony menyimpan sebagai property protected).
     * Juga sinkronkan REQUEST_URI agar getPathInfo() konsisten.
     */
    private function setPathInfo(Request $request, string $path): void
    {
        $queryString = $request->getQueryString();
        $requestUri = $path.($queryString ? '?'.$queryString : '');

        $request->server->set('REQUEST_URI', $requestUri);
        $request->server->set('PATH_INFO', $path);

        // Reset cache path/uri di Symfony Request
        $pathInfoProp = new ReflectionProperty(SymfonyRequest::class, 'pathInfo');
        $pathInfoProp->setValue($request, $path);

        $requestUriProp = new ReflectionProperty(SymfonyRequest::class, 'requestUri');
        $requestUriProp->setValue($request, $requestUri);
    }
}
