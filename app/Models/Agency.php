<?php

namespace App\Models;

use App\Enums\Plan;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Agency extends Model
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

    public function resetQuota(): void
    {
        $this->update([
            'used_this_month' => 0,
            'quota_resets_at' => now()->addMonth()->startOfMonth(),
        ]);
    }
}
