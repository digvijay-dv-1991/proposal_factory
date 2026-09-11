<?php

use App\Livewire\Actions\Logout;
use Illuminate\Support\Facades\Auth;
use Livewire\Volt\Component;

new class extends Component
{
    public string $password = '';

    public bool $confirmingDeletion = false;

    public function confirmDeleting(): void
    {
        $this->confirmingDeletion = true;
    }

    public function cancelDeleting(): void
    {
        $this->confirmingDeletion = false;
        $this->reset('password');
    }

    /**
     * Delete the currently authenticated user.
     */
    public function deleteUser(Logout $logout): void
    {
        $this->validate([
            'password' => ['required', 'string', 'current_password'],
        ]);

        tap(Auth::user(), $logout(...))->delete();

        $this->redirect('/', navigate: true);
    }
}; ?>

<div class="panel danger-panel profile-panel-full">
    <h3>Delete Account</h3>
    <p class="panel-description">Once your account is deleted, all of its resources and data will be permanently deleted. Before deleting your account, please download any data or information that you wish to retain.</p>

    @if (! $confirmingDeletion)
        <button type="button" class="btn danger" wire:click="confirmDeleting">Delete Account</button>
    @else
        <form wire:submit="deleteUser" class="field-stack">
            <div class="field">
                <label for="delete_password">Enter your password to confirm you would like to permanently delete your account</label>
                <input wire:model="password" id="delete_password" name="password" type="password" autocomplete="current-password" placeholder="Password" autofocus>
                @error('password') <div class="source-required-note">{{ $message }}</div> @enderror
            </div>

            <div class="form-actions">
                <button type="button" class="btn" wire:click="cancelDeleting">Cancel</button>
                <button type="submit" class="btn danger" wire:confirm="This is permanent and cannot be undone. Delete your account?">Delete Account</button>
            </div>
        </form>
    @endif
</div>
