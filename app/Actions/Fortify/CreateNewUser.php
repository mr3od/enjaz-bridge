<?php

namespace App\Actions\Fortify;

use App\Concerns\PasswordValidationRules;
use App\Concerns\ProfileValidationRules;
use App\Models\Agency;
use App\Models\User;
use App\Services\Tenancy\AgencyRoleManager;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Laravel\Fortify\Contracts\CreatesNewUsers;

class CreateNewUser implements CreatesNewUsers
{
    use PasswordValidationRules, ProfileValidationRules;

    public function __construct(private AgencyRoleManager $agencyRoleManager) {}

    /**
     * Validate and create a newly registered user.
     *
     * @param  array<string, string>  $input
     */
    public function create(array $input): User
    {
        Validator::make($input, [
            ...$this->profileRules(),
            'agency_name' => ['required', 'string', 'max:255'],
            'agency_city' => ['nullable', 'string', 'max:255'],
            'password' => $this->passwordRules(),
        ])->validate();

        try {
            return DB::transaction(function () use ($input): User {
                $agency = Agency::query()->create([
                    'name' => $input['agency_name'],
                    'slug' => $this->uniqueSlug($input['agency_name']),
                    'city' => $input['agency_city'] ?? null,
                    'plan' => 'free',
                    'monthly_quota' => 10,
                    'used_this_month' => 0,
                    'quota_resets_at' => now()->addMonth()->startOfMonth(),
                    'is_active' => true,
                ]);

                $user = User::query()->create([
                    'name' => $input['name'],
                    'phone' => $input['phone'],
                    'agency_id' => $agency->id,
                    'email' => $this->passwordBrokerEmail($input['phone']),
                    'password' => $input['password'],
                ]);

                tenancy()->initialize($agency);
                $this->agencyRoleManager->assignOwner($user, $agency->id);

                return $user;
            });
        } finally {
            if (tenancy()->initialized) {
                tenancy()->end();
            }
        }
    }

    private function passwordBrokerEmail(string $phone): string
    {
        return sprintf('%s@phone.enjaz.local', $phone);
    }

    private function uniqueSlug(string $agencyName): string
    {
        $baseSlug = Str::slug($agencyName);
        $slug = $baseSlug;
        $counter = 2;

        while (Agency::query()->where('slug', $slug)->exists()) {
            $slug = "{$baseSlug}-{$counter}";
            $counter++;
        }

        return $slug;
    }
}
