<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Http\Resources\UserResource;
use App\Models\User;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AuthController extends Controller
{
    public function login(LoginRequest $request)
    {
        try {
            $request->authenticate();
            $user = User::where('email', $request->validated('email'))->first();

            if (! $user || ! $user->is_active) {
                Auth::guard('web')->logout();
                throw new Exception('The provided credentials do not match our records or the account is inactive.');
            }

            $data = new UserResource($user);

            return $this->success('Successfully logged in', $data);
        } catch (Exception $e) {
            return $this->error($e->getMessage(), null, 401);
        }
    }

    public function logout(Request $request)
    {
        $request->user()->tokens()->delete();

        return $this->success('Successfully logged out');
    }
}
