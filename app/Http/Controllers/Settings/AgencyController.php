<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use App\Http\Requests\Settings\AgencyUpdateRequest;
use App\Models\Agency;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class AgencyController extends Controller
{
    public function edit(): Response
    {
        $agency = $this->currentAgency();

        return Inertia::render('settings/agency', [
            'agency' => [
                'name' => $agency->name,
                'city' => $agency->city,
                'plan' => $agency->plan->value,
                'monthly_quota' => $agency->monthly_quota,
                'used_this_month' => $agency->used_this_month,
                'quota_remaining' => $agency->quotaRemaining(),
                'quota_resets_at' => $agency->quota_resets_at?->toISOString(),
            ],
        ]);
    }

    public function update(AgencyUpdateRequest $request): RedirectResponse
    {
        $this->currentAgency()->update($request->validated());

        return to_route('agency.edit');
    }

    private function currentAgency(): Agency
    {
        $tenant = tenancy()->tenant;

        if (! $tenant instanceof Agency) {
            abort(404);
        }

        return $tenant;
    }
}
