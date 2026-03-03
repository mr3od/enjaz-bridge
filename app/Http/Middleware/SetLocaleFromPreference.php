<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SetLocaleFromPreference
{
    public function handle(Request $request, Closure $next): Response
    {
        $supportedLocales = ['ar', 'en'];
        $preferredLocale = (string) (
            $request->session()->get('locale')
            ?? $request->cookie('locale')
            ?? config('app.locale', 'ar')
        );

        if (! in_array($preferredLocale, $supportedLocales, true)) {
            $preferredLocale = (string) config('app.locale', 'ar');
        }

        app()->setLocale($preferredLocale);

        return $next($request);
    }
}
