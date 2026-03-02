<?php

namespace App\Http\Controllers\Auth;

use App\Concerns\PasswordValidationRules;
use App\Contracts\OtpService;
use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class PhonePasswordResetController extends Controller
{
    use PasswordValidationRules;

    public function __construct(private OtpService $otpService) {}

    public function show(Request $request): Response
    {
        return Inertia::render('auth/reset-password', [
            'phone' => $request->query('phone', '+967'),
        ]);
    }

    public function send(Request $request): RedirectResponse
    {
        $payload = $request->validate([
            'phone' => ['required', 'string', 'regex:/^\+967\d{9}$/'],
        ]);

        $phone = $payload['phone'];
        $user = User::query()->where('phone', $phone)->first();

        if ($user && $this->otpService->canSend($phone, 'password_reset')) {
            $this->otpService->send($phone, 'password_reset');
        }

        return redirect()
            ->route('phone-password.reset', ['phone' => $phone])
            ->with('status', __('If the phone exists, we sent an OTP code.'));
    }

    public function update(Request $request): RedirectResponse
    {
        $payload = $request->validate([
            'phone' => ['required', 'string', 'regex:/^\+967\d{9}$/'],
            'code' => ['required', 'digits:6'],
            'password' => $this->passwordRules(),
        ]);

        $user = User::query()->where('phone', $payload['phone'])->first();

        if (! $user || ! $this->otpService->verify($payload['phone'], 'password_reset', $payload['code'])) {
            throw ValidationException::withMessages([
                'code' => __('The provided OTP code is invalid or expired.'),
            ]);
        }

        $this->otpService->consume($payload['phone'], 'password_reset');
        $user->forceFill(['password' => $payload['password']])->save();

        return redirect()
            ->route('login')
            ->with('status', __('Your password has been reset successfully.'));
    }
}
