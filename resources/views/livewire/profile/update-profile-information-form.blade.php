<?php

use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;
use Illuminate\Validation\Rule;
use Livewire\Volt\Component;

new class extends Component
{
    public string $name = '';
    public string $email = '';

    /**
     * Mount the component.
     */
    public function mount(): void
    {
        $this->name = Auth::user()->name;
        $this->email = Auth::user()->email;
    }

    /**
     * Update the profile information for the currently authenticated user.
     */
    public function updateProfileInformation(): void
    {
        $user = Auth::user();

        $validated = $this->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', Rule::unique(User::class)->ignore($user->id)],
        ]);

        $user->fill($validated);

        if ($user->isDirty('email')) {
            $user->email_verified_at = null;
        }

        $user->save();

        $this->dispatch('profile-updated', name: $user->name);
    }

    /**
     * Send an email verification notification to the current user.
     */
    public function sendVerification(): void
    {
        $user = Auth::user();

        if ($user->hasVerifiedEmail()) {
            $this->redirectIntended(default: route('dashboard', absolute: false));

            return;
        }

        $user->sendEmailVerificationNotification();

        Session::flash('status', 'verification-link-sent');
    }
}; ?>

<div class="panel">
    <h3>Profile Information</h3>
    <p class="panel-description">Update your account's profile information and email address.</p>

    <form wire:submit="updateProfileInformation" class="field-stack">
        <div class="field">
            <label for="name">Name</label>
            <input wire:model="name" id="name" name="name" type="text" required autofocus autocomplete="name">
            @error('name') <div class="source-required-note">{{ $message }}</div> @enderror
        </div>

        <div class="field">
            <label for="email">Email</label>
            <input wire:model="email" id="email" name="email" type="email" required autocomplete="username">
            @error('email') <div class="source-required-note">{{ $message }}</div> @enderror

            @if (auth()->user() instanceof \Illuminate\Contracts\Auth\MustVerifyEmail && ! auth()->user()->hasVerifiedEmail())
                <div class="panel-description">
                    Your email address is unverified.
                    <button type="button" wire:click.prevent="sendVerification" class="inline-link">Click here to re-send the verification email.</button>

                    @if (session('status') === 'verification-link-sent')
                        <div class="source-required-note ok">A new verification link has been sent to your email address.</div>
                    @endif
                </div>
            @endif
        </div>

        <div class="form-actions">
            <button type="submit" class="btn primary">Save</button>
        </div>

        <div x-data="{ shown: false, timeout: null }"
             x-init="@this.on('profile-updated', () => { clearTimeout(timeout); shown = true; timeout = setTimeout(() => { shown = false }, 2500); })"
             x-show="shown"
             x-transition:enter="toast-enter" x-transition:enter-start="toast-enter-start" x-transition:enter-end="toast-enter-end"
             x-transition:leave="toast-leave" x-transition:leave-start="toast-leave-start" x-transition:leave-end="toast-leave-end"
             style="display: none;"
             class="toast">
            <span class="toast-icon">&#10003;</span>
            Profile updated successfully.
        </div>
    </form>
</div>
