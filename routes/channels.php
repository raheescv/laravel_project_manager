<?php

use Illuminate\Support\Facades\Broadcast;

Broadcast::channel('App.Models.User.{id}', function ($user, $id) {
    return (int) $user->id === (int) $id;
});

// Public channel — used by NotificationCreatedEvent; auth handled server-side (job only sends to the right user)
Broadcast::channel('user-notification-channel-{id}', function ($user, $id) {
    return (int) $user->id === (int) $id;
});
