<?php

namespace App\Actions\User;

use App\Models\User;
use App\Models\UserHasBranch;
use Exception;
use Illuminate\Support\Collection;

class BranchAction
{
    /** Wipe the user's branches and keep only the given ones. */
    public const MODE_REPLACE = 'replace';

    /** Keep what the user already has and add the given ones on top. */
    public const MODE_APPEND = 'append';

    public function execute($user_id, $branch_ids, string $mode = self::MODE_REPLACE, $default_branch_id = null)
    {
        try {
            $branch_ids = collect($branch_ids)->filter()->map(fn ($branch_id) => (int) $branch_id);
            if ($mode === self::MODE_APPEND) {
                $branch_ids = $branch_ids->merge(UserHasBranch::where('user_id', $user_id)->pluck('branch_id'));
            }
            $branch_ids = $branch_ids->unique()->values();

            UserHasBranch::where('user_id', $user_id)->delete();
            $branch_ids->map(function ($branch_id) use ($user_id): void {
                $single = [
                    'user_id' => $user_id,
                    'branch_id' => $branch_id,
                ];
                UserHasBranch::create($single);
            });
            $this->syncDefaultBranch($user_id, $branch_ids, $default_branch_id);
            $return['result'] = true;
            $return['data'] = [];
            $return['message'] = 'Successfully updated the User Branch';
        } catch (Exception $e) {
            $return['result'] = false;
            $return['message'] = $e->getMessage();
        }

        return $return;
    }

    /**
     * Assign the same branches to many users in one go.
     *
     * Every user still goes through execute(), so bulk assignment and the
     * single-user screen can never drift apart.
     */
    public function bulk($user_ids, $branch_ids, string $mode = self::MODE_REPLACE, $default_branch_id = null)
    {
        try {
            $user_ids = collect($user_ids)->filter()->unique()->values();
            if ($user_ids->isEmpty()) {
                throw new Exception('Please select at least one user to assign branches.', 1);
            }
            if (! collect($branch_ids)->filter()->count()) {
                throw new Exception('Please select at least one branch.', 1);
            }
            foreach ($user_ids as $user_id) {
                $response = $this->execute($user_id, $branch_ids, $mode, $default_branch_id);
                if (! $response['result']) {
                    throw new Exception($response['message'], 1);
                }
            }
            $return['result'] = true;
            $return['data'] = [];
            $return['message'] = 'Successfully updated branches for '.$user_ids->count().' '.str('user')->plural($user_ids->count());
        } catch (Exception $e) {
            $return['result'] = false;
            $return['message'] = $e->getMessage();
        }

        return $return;
    }

    /**
     * Keep default_branch_id pointing at a branch the user actually has.
     *
     * A stale default silently pins the login session (and the mobile app) to a
     * branch the user is no longer assigned to, so it follows the assignment:
     * the requested branch wins, an already valid default is kept, and an
     * orphaned one falls back to the first branch left. A user who never had a
     * default keeps not having one.
     */
    protected function syncDefaultBranch($user_id, Collection $branch_ids, $default_branch_id = null): void
    {
        $user = User::find($user_id);
        if (! $user) {
            return;
        }
        $default_branch_id = $default_branch_id ? (int) $default_branch_id : null;
        $current = $user->default_branch_id ? (int) $user->default_branch_id : null;

        if ($default_branch_id && $branch_ids->contains($default_branch_id)) {
            $branch_id = $default_branch_id;
        } elseif ($current && $branch_ids->contains($current)) {
            $branch_id = $current;
        } elseif ($current || $default_branch_id) {
            $branch_id = $branch_ids->first();
        } else {
            return;
        }

        if ($branch_id != $current) {
            $user->update(['default_branch_id' => $branch_id]);
        }
    }
}
