<?php

declare(strict_types=1);

namespace App\Services\OtpService;

use App\Contracts\OtpService;
use App\Models\OtpCode;
use Illuminate\Support\Facades\Hash;

class FakeOtpService implements OtpService
{
    private const FIXED_CODE = '123456';

    private const TTL_MINUTES = 5;

    private const MAX_ATTEMPTS = 5;

    private const COOLDOWN_SECONDS = 60;

    public function send(string $phone, string $purpose): void
    {
        OtpCode::create([
            'phone' => $phone,
            'purpose' => $purpose,
            'code_hash' => Hash::make(self::FIXED_CODE),
            'expires_at' => now()->addMinutes(self::TTL_MINUTES),
            'ip_address' => request()?->ip(),
        ]);
    }

    public function verify(string $phone, string $purpose, string $code): bool
    {
        $otp = $this->findValid($phone, $purpose);

        if (! $otp) {
            return false;
        }

        $otp->increment('attempts');

        return Hash::check($code, $otp->code_hash);
    }

    public function consume(string $phone, string $purpose): void
    {
        $otp = $this->findValid($phone, $purpose);
        $otp?->update(['consumed_at' => now()]);
    }

    public function canSend(string $phone, string $purpose): bool
    {
        $lastSent = OtpCode::query()
            ->where('phone', $phone)
            ->where('purpose', $purpose)
            ->latest()
            ->first();

        if (! $lastSent) {
            return true;
        }

        return $lastSent->created_at->diffInSeconds(now()) >= self::COOLDOWN_SECONDS;
    }

    private function findValid(string $phone, string $purpose): ?OtpCode
    {
        return OtpCode::query()
            ->where('phone', $phone)
            ->where('purpose', $purpose)
            ->whereNull('consumed_at')
            ->where('expires_at', '>', now())
            ->where('attempts', '<', self::MAX_ATTEMPTS)
            ->latest()
            ->first();
    }
}
