<?php

namespace App\Actions\V1\Parent;

use App\Actions\Student\Guardian\SendInviteAction;
use App\Actions\Student\Guardian\SyncAction;
use App\Http\Requests\V1\Parent\ForgotPasswordRequest;
use App\Models\Guardian;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\ValidationException;

/**
 * Sends a fresh set-password link to the parent's email / WhatsApp on file.
 * The answer is the same whether or not the number is registered.
 */
class ForgotPasswordAction
{
    public function execute(ForgotPasswordRequest $request): void
    {
        $mobile = SyncAction::normalizeMobile((string) $request->validated('mobile'));

        $key = 'parent-forgot:'.$request->ip().'|'.$mobile;
        if (RateLimiter::tooManyAttempts($key, 3)) {
            throw ValidationException::withMessages(['mobile' => 'Please wait a few minutes before asking again.'])->status(429);
        }
        RateLimiter::hit($key, 600);

        $guardian = Guardian::where('mobile', $mobile)->where('status', 'active')->first();
        if ($guardian) {
            // After the response: the email is sent synchronously, and waiting on
            // it would let response time reveal which numbers are registered.
            // (defer() is not an option — bootstrap/app.php replaces the global
            // middleware stack, which drops InvokeDeferredCallbacks.)
            $guardianId = $guardian->id;
            app()->terminating(fn () => (new SendInviteAction())->execute($guardianId));
        }
    }
}
