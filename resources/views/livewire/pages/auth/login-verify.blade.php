<?php

use App\Enums\OtpResendResult;
use App\Enums\OtpVerificationResult;
use App\Models\User;
use App\Notifications\LoginSucceeded;
use App\Services\TwoFactorLoginService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Validate;
use Livewire\Volt\Component;

new #[Layout('layouts.auth-guest')] class extends Component
{
    #[Validate('required|digits:6')]
    public string $code = '';

    public ?string $resendStatus = null;

    /**
     * No pending challenge in this session means no valid password step was
     * completed here — bounce straight back to the login screen rather than
     * rendering an OTP form for nobody in particular.
     */
    public function mount(): void
    {
        if (! session()->has('auth.2fa_user_id')) {
            $this->redirectRoute('login', navigate: true);
        }
    }

    public function verify(): void
    {
        $this->validate();

        $user = User::findOrFail(session('auth.2fa_user_id'));
        $service = app(TwoFactorLoginService::class);

        $result = $service->verify($user, $this->code);

        if ($result === OtpVerificationResult::Success) {
            $remember = (bool) session('auth.2fa_remember', false);

            Auth::login($user, $remember);
            Session::regenerate();
            session()->forget(['auth.2fa_user_id', 'auth.2fa_remember']);

            $user->notify(new LoginSucceeded(now(), request()->ip() ?? 'unknown'));

            $this->redirectIntended(
                default: $user->hasRole('Admin') ? '/admin' : route('opportunities.index', absolute: false),
                navigate: true,
            );

            return;
        }

        if ($result === OtpVerificationResult::TooManyAttempts) {
            session()->forget(['auth.2fa_user_id', 'auth.2fa_remember']);

            $this->addError('code', 'Too many incorrect attempts. Please log in again.');
            $this->redirectRoute('login', navigate: true);

            return;
        }

        if ($result === OtpVerificationResult::Expired) {
            $this->addError('code', 'This code has expired. Request a new one below.');

            return;
        }

        if ($result === OtpVerificationResult::NoChallenge) {
            session()->forget(['auth.2fa_user_id', 'auth.2fa_remember']);
            $this->redirectRoute('login', navigate: true);

            return;
        }

        $remaining = $service->remainingAttempts($user);

        $this->addError('code', "Incorrect code. {$remaining} attempt(s) remaining.");
    }

    public function resend(): void
    {
        $user = User::findOrFail(session('auth.2fa_user_id'));

        $result = app(TwoFactorLoginService::class)->resend($user);

        $this->resendStatus = $result === OtpResendResult::Sent
            ? 'A new code has been sent.'
            : 'Please wait a few minutes before requesting another code.';
    }
}; ?>

<div>
    <h1 class="auth-form-title">{{ __('Enter your code') }}</h1>
    <p class="auth-intro">
        {{ __('Enter the 6-digit code we emailed you. It expires in 5 minutes.') }}
    </p>

    @if ($resendStatus)
        <div class="auth-status">{{ $resendStatus }}</div>
    @endif

    <form wire:submit="verify">
        <div class="auth-field">
            <label for="code" class="auth-label">{{ __('Verification code') }}</label>
            <input wire:model="code" id="code" class="auth-input" type="text" inputmode="numeric" pattern="[0-9]*" maxlength="6" autocomplete="one-time-code" autofocus>
            @error('code')
                <p class="auth-error">{{ $message }}</p>
            @enderror
        </div>

        <div class="auth-actions">
            <button type="button" wire:click="resend" class="auth-link auth-link-button">
                {{ __('Resend code') }}
            </button>

            <button type="submit" class="auth-button">
                {{ __('Verify') }}
            </button>
        </div>
    </form>
</div>
