<?php

use App\Filament\Resources\Users\Pages\CreateUser;
use App\Models\User;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;

test('admin can create a user with a role via the resource', function () {
    $admin = User::factory()->create();
    $adminRole = Role::firstOrCreate(['name' => 'Admin']);
    $reviewerRole = Role::firstOrCreate(['name' => 'Reviewer']);
    $admin->assignRole($adminRole);

    Livewire::actingAs($admin)
        ->test(CreateUser::class)
        ->fillForm([
            'name' => 'New Reviewer',
            'email' => 'reviewer@example.com',
            'password' => 'password',
            'roles' => [$reviewerRole->id],
        ])
        ->call('create')
        ->assertHasNoFormErrors();

    $user = User::where('email', 'reviewer@example.com')->firstOrFail();

    expect($user->hasRole('Reviewer'))->toBeTrue();
});

test('non-admin cannot reach the user create route', function () {
    $user = User::factory()->create();

    $this->actingAs($user)->get('/admin/users/create')->assertForbidden();
});
