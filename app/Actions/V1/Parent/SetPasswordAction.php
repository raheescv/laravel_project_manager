<?php

namespace App\Actions\V1\Parent;

use App\Actions\Student\Guardian\SetPasswordAction as GuardianSetPasswordAction;
use App\Exceptions\ParentPortalException;
use App\Http\Requests\V1\Parent\SetPasswordRequest;

/**
 * Redeem an invite / reset link and sign the parent straight in.
 */
class SetPasswordAction
{
    public function execute(SetPasswordRequest $request): array
    {
        $response = (new GuardianSetPasswordAction())->execute((string) $request->validated('token'), (string) $request->validated('password'));
        if (! $response['success']) {
            throw new ParentPortalException($response['message']);
        }

        return (new IssueTokenAction())->execute($response['data'], true);
    }
}
