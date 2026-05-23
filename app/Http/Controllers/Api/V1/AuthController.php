<?php

namespace App\Http\Controllers\Api\V1;

use App\Actions\V1\Auth\LoginAction;
use App\Http\Controllers\Controller;
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
     * Login with a PIN.
     *
     * Authenticates an admin or employee by PIN and returns a Sanctum bearer token.
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
}
