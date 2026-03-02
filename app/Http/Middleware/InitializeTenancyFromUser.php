<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Models\Agency;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class InitializeTenancyFromUser
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user === null || $user->agency_id === null) {
            $this->logout($request);

            return redirect()->route('login');
        }

        $agency = Agency::query()
            ->whereKey($user->agency_id)
            ->where('is_active', true)
            ->first();

        if ($agency === null) {
            $this->logout($request);

            return redirect()->route('login');
        }

        tenancy()->initialize($agency);

        try {
            return $next($request);
        } finally {
            if (tenancy()->initialized) {
                tenancy()->end();
            }
        }
    }

    public function terminate(Request $request, Response $response): void
    {
        if (tenancy()->initialized) {
            tenancy()->end();
        }
    }

    private function logout(Request $request): void
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();
    }
}
