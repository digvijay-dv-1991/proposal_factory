<?php

use Illuminate\Support\Facades\Broadcast;

Broadcast::channel('App.Models.User.{id}', function ($user, $id) {
    return (int) $user->id === (int) $id;
});

// The board and every opportunity's bid-comment chat are visible to any
// authenticated user today (no per-opportunity ACL exists) — /opportunities
// already requires the `auth` middleware, so this just matches that.
Broadcast::channel('opportunities-board', function ($user) {
    return (bool) $user;
});

Broadcast::channel('opportunity.{id}.comments', function ($user, $id) {
    return (bool) $user;
});
