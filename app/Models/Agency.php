<?php

namespace App\Models;

use App\Enums\Plan;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Stancl\Tenancy\Contracts\Tenant;

class Agency extends Model implements Tenant
{
    use HasFactory, HasUlids;

    protected $fillable = [
        'name',
        'slug',
        'city',
        'plan',
        'monthly_quota',
        'used_this_month',
        'quota_resets_at',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'plan' => Plan::class,
            'is_active' => 'boolean',
            'quota_resets_at' => 'datetime',
        ];
    }

    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }

    public function quotaRemaining(): int
    {
        return max(0, $this->monthly_quota - $this->used_this_month);
    }

    public function hasQuota(): bool
    {
        $this->ensureQuotaWindowIsCurrent();

        return $this->quotaRemaining() > 0;
    }

    public function tryConsumeQuota(): bool
    {
        $this->ensureQuotaWindowIsCurrent();

        $updated = static::query()
            ->whereKey($this->getKey())
            ->whereColumn('used_this_month', '<', 'monthly_quota')
            ->increment('used_this_month');

        $this->refresh();

        return $updated > 0;
    }

    public function resetQuota(): void
    {
        $this->update([
            'used_this_month' => 0,
            'quota_resets_at' => now()->addMonth()->startOfMonth(),
        ]);
    }

    private function ensureQuotaWindowIsCurrent(): void
    {
        if ($this->quota_resets_at === null || $this->quota_resets_at->lessThanOrEqualTo(now())) {
            $this->resetQuota();
        }
    }

    public function getTenantKeyName(): string
    {
        return 'id';
    }

    public function getTenantKey(): mixed
    {
        return $this->getKey();
    }

    public function getInternal(string $key): mixed
    {
        return $this->getAttribute($key);
    }

    public function setInternal(string $key, mixed $value): void
    {
        $this->setAttribute($key, $value);
    }

    public function run(callable $callback): mixed
    {
        $originalTenant = tenant();

        tenancy()->initialize($this);

        try {
            return $callback($this);
        } finally {
            if ($originalTenant !== null) {
                tenancy()->initialize($originalTenant);
            } else {
                tenancy()->end();
            }
        }
    }
}
