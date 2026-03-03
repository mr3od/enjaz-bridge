<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\Agency;
use Illuminate\Console\Command;

class ResetMonthlyQuotas extends Command
{
    protected $signature = 'agencies:reset-quotas';

    protected $description = 'Reset monthly extraction quotas for all agencies';

    public function handle(): int
    {
        $count = Agency::query()
            ->where(function ($query): void {
                $query->whereNull('quota_resets_at')
                    ->orWhere('quota_resets_at', '<=', now());
            })
            ->get()
            ->each(fn (Agency $agency) => $agency->resetQuota())
            ->count();

        $this->info("Reset quotas for {$count} agencies.");

        return self::SUCCESS;
    }
}
