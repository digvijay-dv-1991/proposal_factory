<?php

use App\Models\User;

test('guest visiting root is redirected to login', function () {
    $this->get('/')->assertRedirect(route('login'));
});

test('authenticated user visiting root is redirected to opportunities', function () {
    $this->actingAs(User::factory()->create());

    $this->get('/')->assertRedirect(route('opportunities.index'));
});

test('guest cannot reach the opportunities board', function () {
    $this->get('/opportunities')->assertRedirect(route('login'));
});

test('authenticated user can reach the opportunities board', function () {
    $this->actingAs(User::factory()->create());

    $this->get('/opportunities')->assertOk();
});
