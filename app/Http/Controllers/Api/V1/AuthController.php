<?php

namespace App\Http\Controllers\Api\V1;

use App\Actions\V1\Auth\ChangePinAction;
use App\Actions\V1\Auth\LoginAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\V1\Auth\ChangePinRequest;
use App\Http\Requests\V1\Auth\LoginRequest;
use App\Traits\ApiResponseTrait;
use Dedoc\Scramble\Attributes\Group;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

#[Group('Mobile - Authentication')]
class AuthController extends Controller
{
    use ApiResponseTrait;

    /**
     * Login.
     *
     * Authenticates an admin or employee and returns a Sanctum bearer token.
     * Accepts either a PIN (`method=pin`, the default) or username + password
     * (`method=password`). Method is inferred when omitted.
     */
    public function login(LoginAction $action, LoginRequest $request): JsonResponse
    {
        try {
            $result = $action->execute($request);

            return $this->sendSuccess($result, 'Successfully logged in');
        } catch (AuthenticationException $e) {
            return $this->sendUnauthorizedError($e->getMessage());
        } catch (\Exception $e) {
            return $this->sendServerError('Login failed: '.$e->getMessage());
        }
    }

    /**
     * Logout.
     *
     * Revokes the access token used for the current request.
     */
    public function logout(Request $request): JsonResponse
    {
        $request->user()->currentAccessToken()->delete();

        return $this->sendSuccess(null, 'Successfully logged out');
    }

    /**
     * Change Employee PIN.
     *
     * Verifies the authenticated employee's current PIN and updates it to a new one.
     */
    public function changePin(ChangePinAction $action, ChangePinRequest $request): JsonResponse
    {
        try {
            $action->execute($request);

            return $this->sendSuccess(null, 'PIN changed successfully');
        } catch (AuthenticationException $e) {
            return $this->sendUnauthorizedError($e->getMessage());
        } catch (\Exception $e) {
            return $this->sendServerError('Change PIN failed: '.$e->getMessage());
        }
    }

    /**
     * Change Password.
     *
     * Verifies the authenticated user's current password and updates it to a new one.
     */
    public function changePassword(ChangePasswordAction $action, ChangePasswordRequest $request): JsonResponse
    {
        try {
            $action->execute($request);

            return $this->sendSuccess(null, 'Password changed successfully');
        } catch (AuthenticationException $e) {
            return $this->sendUnauthorizedError($e->getMessage());
        } catch (\Exception $e) {
            return $this->sendServerError('Change password failed: '.$e->getMessage());
        }
    }
}
