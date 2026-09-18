<?php

namespace App\Services;

use App\Enums\OtpResendResult;
use App\Enums\OtpVerificationResult;
use App\Models\LoginOtpCode;
use App\Models\User;
use App\Notifications\LoginOtpCode as LoginOtpCodeNotification;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;

class TwoFactorLoginService
{
    private const CODE_LIFETIME_MINUTES = 5;

    private const MAX_VERIFY_ATTEMPTS = 5;

    private const MAX_RESENDS = 3;

    private const RESEND_DECAY_SECONDS = 300;

    public function issueChallenge(User $user, bool $remember): void
    {
        $this->generateAndSend($user);

        session([
            'auth.2fa_user_id' => $user->id,
            'auth.2fa_remember' => $remember,
        ]);
    }

    public function verify(User $user, string $code): OtpVerificationResult
    {
        $challenge = LoginOtpCode::where('user_id', $user->id)->first();

        if (! $challenge) {
            return OtpVerificationResult::NoChallenge;
        }

        if ($challenge->isExpired()) {
            $challenge->delete();

            return OtpVerificationResult::Expired;
        }

        if ($challenge->attempts >= self::MAX_VERIFY_ATTEMPTS) {
            $challenge->delete();

            return OtpVerificationResult::TooManyAttempts;
        }

        if (! Hash::check($code, $challenge->code_hash)) {
            $challenge->increment('attempts');

            if ($challenge->attempts >= self::MAX_VERIFY_ATTEMPTS) {
                $challenge->delete();

                return OtpVerificationResult::TooManyAttempts;
            }

            return OtpVerificationResult::InvalidCode;
        }

        $challenge->delete();

        return OtpVerificationResult::Success;
    }

    public function resend(User $user): OtpResendResult
    {
        $sent = RateLimiter::attempt(
            "otp-resend:{$user->id}",
            self::MAX_RESENDS,
            function () use ($user): true {
                $this->generateAndSend($user);

                return true;
            },
            self::RESEND_DECAY_SECONDS,
        );

        return $sent ? OtpResendResult::Sent : OtpResendResult::RateLimited;
    }

    public function remainingAttempts(User $user): int
    {
        $challenge = LoginOtpCode::where('user_id', $user->id)->first();

        return $challenge ? max(0, self::MAX_VERIFY_ATTEMPTS - $challenge->attempts) : 0;
    }

    private function generateAndSend(User $user): void
    {
        LoginOtpCode::where('user_id', $user->id)->delete();

        $code = (string) random_int(100000, 999999);

        LoginOtpCode::create([
            'user_id' => $user->id,
            'code_hash' => Hash::make($code),
            'expires_at' => now()->addMinutes(self::CODE_LIFETIME_MINUTES),
        ]);

        $user->notify(new LoginOtpCodeNotification($code));
    }
}
