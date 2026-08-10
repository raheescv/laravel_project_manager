<?php

namespace App\Services;

use App\Models\Branch;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

/**
 * Impersonation is a *temporary* login as another user.
 *
 * The whole lifecycle lives here rather than in the Livewire component so the
 * expiry middleware, the controller and the banner all agree on what the
 * session keys mean.
 */
class ImpersonationService
{
    /**
     * How long an impersonation session stays valid before it is ended
     * automatically on the next request.
     */
    public const DURATION_MINUTES = 15;

    private const KEY_IMPERSONATOR = 'impersonator_id';

    private const KEY_BRANCH = 'impersonator_branch_id';

    private const KEY_EXPIRES = 'impersonation_expires_at';

    public function isImpersonating(): bool
    {
        return session()->has(self::KEY_IMPERSONATOR);
    }

    public function expiresAt(): ?Carbon
    {
        $timestamp = session(self::KEY_EXPIRES);

        return $timestamp ? Carbon::createFromTimestamp($timestamp) : null;
    }

    public function hasExpired(): bool
    {
        $expiresAt = $this->expiresAt();

        // A session with no expiry recorded predates this feature; treat it as
        // expired so it cannot outlive the window indefinitely.
        return ! $expiresAt || $expiresAt->isPast();
    }

    public function secondsRemaining(): int
    {
        $expiresAt = $this->expiresAt();

        return $expiresAt ? max(0, (int) now()->diffInSeconds($expiresAt, false)) : 0;
    }

    /**
     * The admin who started the impersonation, if they still exist.
     *
     * Global scopes are skipped throughout: the tenant scope currently resolves
     * against the *impersonated* user, and getting back to your own account
     * must never be the thing that fails.
     */
    public function impersonator(): ?User
    {
        $id = session(self::KEY_IMPERSONATOR);

        return $id ? User::withoutGlobalScopes()->find($id) : null;
    }

    /**
     * Log in as $target, remembering who to come back to.
     */
    public function start(User $target): void
    {
        $impersonatorId = Auth::id();

        session([
            self::KEY_IMPERSONATOR => $impersonatorId,
            self::KEY_BRANCH => session('branch_id'),
            self::KEY_EXPIRES => now()->addMinutes(self::DURATION_MINUTES)->timestamp,
        ]);

        Auth::login($target);
        $this->putBranch($target->default_branch_id);
        session()->regenerate();

        // Audit trail: the regenerated session otherwise makes an impersonation
        // indistinguishable from an ordinary login.
        Log::info('User impersonation started', [
            'impersonator_id' => $impersonatorId,
            'target_user_id' => $target->id,
            'expires_at' => $this->expiresAt()?->toDateTimeString(),
        ]);
    }

    /**
     * Restore the original admin. Returns null when the impersonation cannot be
     * unwound (not impersonating, or the original account is gone) — the caller
     * decides what to do, since being stuck as someone else is not an option.
     */
    public function stop(): ?User
    {
        $impersonator = $this->impersonator();

        if (! $impersonator) {
            return null;
        }

        $impersonatedId = Auth::id();
        $branchId = session(self::KEY_BRANCH) ?: $impersonator->default_branch_id;

        Auth::login($impersonator);

        $this->forget();
        $this->putBranch($branchId);
        session()->regenerate();

        Log::info('User impersonation ended', [
            'impersonator_id' => $impersonator->id,
            'target_user_id' => $impersonatedId,
        ]);

        return $impersonator;
    }

    public function forget(): void
    {
        session()->forget([self::KEY_IMPERSONATOR, self::KEY_BRANCH, self::KEY_EXPIRES]);
    }

    private function putBranch(?int $branchId): void
    {
        $branch = $branchId ? Branch::withoutGlobalScopes()->find($branchId) : null;

        session([
            'branch_id' => $branch?->id,
            'branch_code' => $branch?->code,
            'branch_name' => $branch?->name,
        ]);
    }
}
