<?php

use App\Livewire\Forms\LoginForm;
use Livewire\Attributes\Layout;
use Livewire\Volt\Component;

new #[Layout('layouts.auth-guest')] class extends Component
{
    public LoginForm $form;

    /**
     * Validate credentials and move on to the OTP step — the session isn't
     * established yet, so there's nothing to regenerate or redirect
     * "intended" to until the OTP is also verified.
     */
    public function login(): void
    {
        $this->validate();

        $this->form->authenticate();

        $this->redirectRoute('login.verify', navigate: true);
    }
}; ?>

<div>
    <h1 class="auth-form-title">{{ __('Log in') }}</h1>
    <p class="auth-intro">{{ __('Enter your credentials to continue.') }}</p>

    @if (session('status'))
        <div class="auth-status">{{ session('status') }}</div>
    @endif

    <form wire:submit="login">
        <div class="auth-field">
            <label for="email" class="auth-label">{{ __('Email') }}</label>
            <input wire:model="form.email" id="email" class="auth-input" type="email" name="email" required autofocus autocomplete="username">
            @error('form.email')
                <p class="auth-error">{{ $message }}</p>
            @enderror
        </div>

        <div class="auth-field">
            <label for="password" class="auth-label">{{ __('Password') }}</label>
            <input wire:model="form.password" id="password" class="auth-input" type="password" name="password" required autocomplete="current-password">
            @error('form.password')
                <p class="auth-error">{{ $message }}</p>
            @enderror
        </div>

        <label for="remember" class="auth-remember">
            <input wire:model="form.remember" id="remember" type="checkbox" class="auth-checkbox" name="remember">
            {{ __('Remember me') }}
        </label>

        <div class="auth-actions">
            @if (Route::has('password.request'))
                <a class="auth-link" href="{{ route('password.request') }}" wire:navigate>
                    {{ __('Forgot your password?') }}
                </a>
            @else
                <span></span>
            @endif

            <button type="submit" class="auth-button">
                {{ __('Log in') }}
            </button>
        </div>
    </form>
</div>
