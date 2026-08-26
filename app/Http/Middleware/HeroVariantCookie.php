<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Persists a ?hero=old|new override so the chosen hero survives navigation.
 * ?hero=default clears it and hands the visitor back to the admin setting.
 */
class HeroVariantCookie
{
    public function handle(Request $request, Closure $next): Response
    {
        $override = strtolower(trim((string) $request->query('hero', '')));
        $response = $next($request);
        $cookieName = (string) config('hero.cookie', 'hero_variant');

        if ($override === 'default') {
            $response->headers->setCookie(cookie()->forget($cookieName));

            return $response;
        }

        if (! in_array($override, (array) config('hero.variants', []), true)) {
            return $response;
        }

        if ($request->cookie($cookieName) === $override) {
            return $response;
        }

        $response->headers->setCookie(
            cookie($cookieName, $override, 60 * 24 * 30)
        );

        return $response;
    }
}
