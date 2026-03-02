<?php

namespace App\Http\Middleware;

use App\Models\Agency;
use App\Support\Tenancy\TenantContext;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Spatie\Permission\PermissionRegistrar;
use Symfony\Component\HttpFoundation\Response;

class ResolveTenant
{
    public function __construct(
        private TenantContext $tenantContext,
        private PermissionRegistrar $permissionRegistrar,
    ) {}

    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user === null || $user->agency_id === null) {
            $this->clearTenantContext();
            $this->logout($request);

            return redirect()->route('login');
        }

        $agency = Agency::query()
            ->whereKey($user->agency_id)
            ->where('is_active', true)
            ->first();

        if ($agency === null) {
            $this->clearTenantContext();
            $this->logout($request);

            return redirect()->route('login');
        }

        $this->tenantContext->setAgency($agency);
        $this->permissionRegistrar->setPermissionsTeamId($agency->id);

        return $next($request);
    }

    private function logout(Request $request): void
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();
    }

    private function clearTenantContext(): void
    {
        $this->tenantContext->clear();
        $this->permissionRegistrar->setPermissionsTeamId(null);
    }
}
