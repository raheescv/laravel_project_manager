<?php

namespace App\Listeners;

use App\Models\User;
use Illuminate\Auth\Events\Login;

/**
 * Stamps users.last_login_at on every web sign-in. Impersonation and tenant
 * switching also call Auth::login, but the session already carries the
 * impersonator by then, so a super admin looking around never counts as the
 * user signing in.
 */
class RecordUserLogin
{
    public function handle(Login $event): void
    {
        if (! $event->user instanceof User) {
            return;
        }
        if (session()->has('impersonator_id')) {
            return;
        }

        User::stampLogin($event->user->getKey());
    }
}
