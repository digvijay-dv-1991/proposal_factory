<?php

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;
use Illuminate\Validation\ValidationException;
use Livewire\Volt\Component;

new class extends Component
{
    public string $current_password = '';
    public string $password = '';
    public string $password_confirmation = '';

    /**
     * Update the password for the currently authenticated user.
     */
    public function updatePassword(): void
    {
        try {
            $validated = $this->validate([
                'current_password' => ['required', 'string', 'current_password'],
                'password' => ['required', 'string', Password::defaults(), 'confirmed'],
            ]);
        } catch (ValidationException $e) {
            $this->reset('current_password', 'password', 'password_confirmation');

            throw $e;
        }

        Auth::user()->update([
            'password' => Hash::make($validated['password']),
        ]);

        $this->reset('current_password', 'password', 'password_confirmation');

        $this->dispatch('password-updated');
    }
}; ?>

<div class="panel">
    <h3>Update Password</h3>
    <p class="panel-description">Ensure your account is using a long, random password to stay secure.</p>

    <form wire:submit="updatePassword" class="field-stack">
        <div class="field">
            <label for="update_password_current_password">Current Password</label>
            <input wire:model="current_password" id="update_password_current_password" name="current_password" type="password" autocomplete="current-password">
            @error('current_password') <div class="source-required-note">{{ $message }}</div> @enderror
        </div>

        <div class="field">
            <label for="update_password_password">New Password</label>
            <input wire:model="password" id="update_password_password" name="password" type="password" autocomplete="new-password">
            @error('password') <div class="source-required-note">{{ $message }}</div> @enderror
        </div>

        <div class="field">
            <label for="update_password_password_confirmation">Confirm Password</label>
            <input wire:model="password_confirmation" id="update_password_password_confirmation" name="password_confirmation" type="password" autocomplete="new-password">
            @error('password_confirmation') <div class="source-required-note">{{ $message }}</div> @enderror
        </div>

        <div class="form-actions">
            <button type="submit" class="btn primary">Save</button>
        </div>

        <div x-data="{ shown: false, timeout: null }"
             x-init="@this.on('password-updated', () => { clearTimeout(timeout); shown = true; timeout = setTimeout(() => { shown = false }, 2500); })"
             x-show="shown"
             x-transition:enter="toast-enter" x-transition:enter-start="toast-enter-start" x-transition:enter-end="toast-enter-end"
             x-transition:leave="toast-leave" x-transition:leave-start="toast-leave-start" x-transition:leave-end="toast-leave-end"
             style="display: none;"
             class="toast">
            <span class="toast-icon">&#10003;</span>
            Password updated successfully.
        </div>
    </form>
</div>
