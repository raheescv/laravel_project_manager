<?php

use Illuminate\Support\Facades\Broadcast;

Broadcast::channel('App.Models.User.{id}', function ($user, $id) {
    return (int) $user->id === (int) $id;
});

// Public channel — used by NotificationCreatedEvent; auth handled server-side (job only sends to the right user)
Broadcast::channel('user-notification-channel-{id}', function ($user, $id) {
    return (int) $user->id === (int) $id;
});

// Ticket console live refresh — any user of the tenant who can see the board
Broadcast::channel('tenant.{tenantId}.tickets', function ($user, $tenantId) {
    return (int) $user->tenant_id === (int) $tenantId && $user->can('ticket.view');
});
