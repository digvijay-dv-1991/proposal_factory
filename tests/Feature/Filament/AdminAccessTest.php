<?php

use App\Models\User;
use Spatie\Permission\Models\Role;

test('guest is redirected away from the admin panel to the shared login screen', function () {
    $this->get('/admin')->assertRedirect('/login');
});

test('non-admin authenticated user cannot access the admin panel', function () {
    $user = User::factory()->create();

    $this->actingAs($user)->get('/admin')->assertForbidden();
});

test('admin-role user can access the admin panel', function () {
    $user = User::factory()->create();
    $user->assignRole(Role::firstOrCreate(['name' => 'Admin']));

    $this->actingAs($user)->get('/admin')->assertOk();
});
