<?php

namespace App\Actions\User;

use App\Models\UserHasBranch;
use Exception;

class BranchAction
{
    public function execute($user_id, $branch_ids)
    {
        try {
            UserHasBranch::where('user_id', $user_id)->delete();
            collect($branch_ids)->map(function ($branch_id) use ($user_id): void {
                $single = [
                    'user_id' => $user_id,
                    'branch_id' => $branch_id,
                ];
                UserHasBranch::create($single);
            });
            $return['result'] = true;
            $return['data'] = [];
            $return['message'] = 'Successfully updated the User Branch';
        } catch (Exception $e) {
            $return['result'] = false;
            $return['message'] = $e->getMessage();
        }

        return $return;
    }
}
