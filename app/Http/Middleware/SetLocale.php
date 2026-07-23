<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Support\ResolvePublicLocale;
use Closure;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SetLocale
{
    public function __construct(private ResolvePublicLocale $resolvePublicLocale) {}

    /**
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $resolution = $this->resolvePublicLocale->handle($request);

        app()->setLocale($resolution['locale']);
        $request->attributes->set('public_path', $resolution['normalizedPath']);

        abort_if($resolution['notFound'], 404);

        if ($resolution['canonical'] !== null) {
            return new RedirectResponse($resolution['canonical'], 301);
        }

        return $next($request);
    }
}
