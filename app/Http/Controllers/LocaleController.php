<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class LocaleController extends Controller
{
    public function update(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'locale' => ['required', 'in:ar,en'],
        ]);

        $locale = (string) $validated['locale'];

        app()->setLocale($locale);
        $request->session()->put('locale', $locale);

        return back()->withCookie(
            cookie(
                name: 'locale',
                value: $locale,
                minutes: 60 * 24 * 365,
                path: '/',
                secure: $request->isSecure(),
                sameSite: 'lax'
            )
        );
    }
}
