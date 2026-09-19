<?php

namespace App\Actions\V1\Parent;

use App\Actions\Student\Guardian\SendInviteAction;
use App\Http\Requests\V1\Parent\ForgotPasswordRequest;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\ValidationException;

/**
 * Sends a fresh set-password link to the parent's email / WhatsApp on file. The
 * parent names themselves by mobile number or by email, like at sign-in. The
 * answer is the same whether or not they are registered.
 */
class ForgotPasswordAction
{
    public function execute(ForgotPasswordRequest $request): void
    {
        $login = trim((string) ($request->validated('login') ?: $request->validated('mobile')));

        $key = 'parent-forgot:'.$request->ip().'|'.mb_strtolower($login);
        if (RateLimiter::tooManyAttempts($key, 3)) {
            throw ValidationException::withMessages(['login' => 'Please wait a few minutes before asking again.'])->status(429);
        }
        RateLimiter::hit($key, 600);

        $guardian = LoginAction::findGuardian($login);
        if ($guardian?->isActive()) {
            // After the response: the email is sent synchronously, and waiting on
            // it would let response time reveal who is registered.
            // (defer() is not an option — bootstrap/app.php replaces the global
            // middleware stack, which drops InvokeDeferredCallbacks.)
            $guardianId = $guardian->id;
            app()->terminating(fn () => (new SendInviteAction())->execute($guardianId));
        }
    }
}
