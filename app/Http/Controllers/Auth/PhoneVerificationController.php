<?php

namespace App\Http\Controllers\Auth;

use App\Contracts\OtpService;
use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class PhoneVerificationController extends Controller
{
    public function __construct(private OtpService $otpService) {}

    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'code' => ['required', 'digits:6'],
        ]);

        $user = $request->user();

        if ($user->hasVerifiedEmail()) {
            return redirect()->intended(route('dashboard', absolute: false));
        }

        if (! $this->otpService->verify($user->phone, 'phone_verification', $request->string('code')->toString())) {
            throw ValidationException::withMessages([
                'code' => __('The provided OTP code is invalid or expired.'),
            ]);
        }

        $this->otpService->consume($user->phone, 'phone_verification');
        $user->markEmailAsVerified();

        return redirect()->intended(route('dashboard', absolute: false).'?verified=1');
    }
}
