<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\V1\Auth\AuthUserResource;
use App\Traits\ApiResponseTrait;
use App\Traits\OptimizesUploadedImage;
use Dedoc\Scramble\Attributes\Group;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

#[Group('Mobile - Profile')]
class ProfileController extends Controller
{
    use ApiResponseTrait;
    use OptimizesUploadedImage;

    /**
     * Update profile.
     *
     * Updates the authenticated user's own name, email and mobile. Returns the
     * refreshed user resource so the app can re-cache it locally.
     */
    public function update(Request $request): JsonResponse
    {
        try {
            $user = $request->user();

            $validated = $request->validate([
                'name' => ['required', 'string', 'max:255'],
                'email' => [
                    'required', 'email', 'max:255',
                    Rule::unique('users', 'email')
                        ->where('tenant_id', $user->tenant_id)
                        ->ignore($user->id),
                ],
                'mobile' => ['nullable', 'string', 'max:30'],
            ]);

            $user->update([
                'name' => $validated['name'],
                'email' => $validated['email'],
                'mobile' => $validated['mobile'] ?? null,
            ]);

            return $this->sendSuccess(new AuthUserResource($user->fresh()), 'Profile updated successfully');
        } catch (\Illuminate\Validation\ValidationException $e) {
            return $this->sendValidationError($e->errors());
        } catch (\Exception $e) {
            return $this->sendServerError('Profile update failed: '.$e->getMessage());
        }
    }

    /**
     * Update profile photo.
     *
     * Accepts a multipart `photo` upload, optimises it to a square WEBP, stores
     * it on the public disk and returns the refreshed user resource.
     */
    public function updatePhoto(Request $request): JsonResponse
    {
        try {
            $request->validate([
                'photo' => ['required', 'image', 'mimes:jpeg,jpg,png,webp', 'max:5120'],
            ]);

            $user = $request->user();
            $old = $user->image;

            $path = $this->storeOptimizedImage($request->file('photo'), 'users');
            $user->update(['image' => $path]);

            // Remove the previous file only after the new one is safely stored.
            if ($old && $old !== $path) {
                Storage::disk('public')->delete($old);
            }

            return $this->sendSuccess(new AuthUserResource($user->fresh()), 'Profile photo updated');
        } catch (\Illuminate\Validation\ValidationException $e) {
            return $this->sendValidationError($e->errors());
        } catch (\Exception $e) {
            return $this->sendServerError('Profile photo update failed: '.$e->getMessage());
        }
    }
}
