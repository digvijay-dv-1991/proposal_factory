<?php

namespace Tests\Feature\Auth;

test('registration screen is not publicly reachable', function () {
    $response = $this->get('/register');

    $response->assertNotFound();
});
