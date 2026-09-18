<?php

use App\Models\LoginOtpCode;
use App\Models\User;
use App\Notifications\LoginOtpCode as LoginOtpCodeNotification;
use Illuminate\Support\Facades\Notification;
use Livewire\Volt\Volt;
use Spatie\Permission\Models\Role;

/**
 * Only the hash is persisted, so the plaintext code has to be read back off
 * the faked notification rather than guessed.
 */
function sentOtpCodeFor(User $user): string
{
    $sentCode = null;

    Notification::assertSentTo($user, LoginOtpCodeNotification::class, function ($notification) use (&$sentCode, $user) {
        foreach ($notification->toMail($user)->introLines as $line) {
            if (preg_match('/^\*\*(\d{6})\*\*$/', $line, $matches)) {
                $sentCode = $matches[1];
            }
        }

        return true;
    });

    expect($sentCode)->not->toBeNull();

    return $sentCode;
}

function submitPassword(User $user): void
{
    Volt::test('pages.auth.login')
        ->set('form.email', $user->email)
        ->set('form.password', 'password')
        ->call('login');
}

test('the verify screen redirects guests with no pending challenge back to login', function () {
    $this->get('/login/verify')->assertRedirect(route('login'));
});

test('a valid code within the window logs the user in and redirects non-admins to the board', function () {
    Notification::fake();

    $user = User::factory()->create();
    submitPassword($user);

    $component = Volt::test('pages.auth.login-verify')
        ->set('code', sentOtpCodeFor($user))
        ->call('verify');

    $component->assertHasNoErrors();
    $component->assertRedirect(route('opportunities.index', absolute: false));

    $this->assertAuthenticated();
    expect(LoginOtpCode::where('user_id', $user->id)->exists())->toBeFalse();
});

test('wrong code decrements remaining attempts without logging in', function () {
    Notification::fake();

    $user = User::factory()->create();
    submitPassword($user);

    $component = Volt::test('pages.auth.login-verify')
        ->set('code', '000000')
        ->call('verify');

    $component->assertHasErrors('code');
    $this->assertGuest();

    $challenge = LoginOtpCode::where('user_id', $user->id)->first();
    expect($challenge->attempts)->toBe(1);
});

test('exceeding the attempt cap forces a restart from login', function () {
    Notification::fake();

    $user = User::factory()->create();
    submitPassword($user);

    $component = Volt::test('pages.auth.login-verify');

    for ($i = 0; $i < 5; $i++) {
        $component->set('code', '000000')->call('verify');
    }

    $component->assertRedirect(route('login'));
    $this->assertGuest();

    expect(LoginOtpCode::where('user_id', $user->id)->exists())->toBeFalse();
});

test('an expired code is rejected', function () {
    Notification::fake();

    $user = User::factory()->create();
    submitPassword($user);

    LoginOtpCode::where('user_id', $user->id)->update(['expires_at' => now()->subMinute()]);

    $component = Volt::test('pages.auth.login-verify')
        ->set('code', '123456')
        ->call('verify');

    $component->assertHasErrors('code');
    $this->assertGuest();

    expect(LoginOtpCode::where('user_id', $user->id)->exists())->toBeFalse();
});

test('resend is rate limited after three successful sends', function () {
    Notification::fake();

    $user = User::factory()->create();
    submitPassword($user);

    $component = Volt::test('pages.auth.login-verify');

    $component->call('resend');
    $component->call('resend');
    $component->call('resend');
    expect($component->get('resendStatus'))->toBe('A new code has been sent.');

    $component->call('resend');
    expect($component->get('resendStatus'))->toContain('Please wait');
});

test('admins are redirected to the admin panel after verifying', function () {
    Notification::fake();

    $user = User::factory()->create();
    $user->assignRole(Role::firstOrCreate(['name' => 'Admin']));
    submitPassword($user);

    $component = Volt::test('pages.auth.login-verify')
        ->set('code', sentOtpCodeFor($user))
        ->call('verify');

    $component->assertRedirect('/admin');
    $this->assertAuthenticated();
});
