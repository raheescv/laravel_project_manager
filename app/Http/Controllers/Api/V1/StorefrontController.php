<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Configuration;
use App\Traits\ApiResponseTrait;
use Dedoc\Scramble\Attributes\Group;
use Illuminate\Http\JsonResponse;

#[Group('Public - Storefront')]
class StorefrontController extends Controller
{
    use ApiResponseTrait;

    /** SIZE RUN electric blue — default when the tenant hasn't set a color. */
    private const DEFAULT_COLOR = '#1F35E5';

    /**
     * Storefront branding.
     *
     * Returns the public showcase website's accent color, configured under
     * Settings → Storefront, and the system logo (the same `logo` configuration
     * the web app header uses). The Vue storefront reads this at boot: the color
     * drives its theme (falling back to the SIZE RUN blue), the logo replaces
     * the monogram mark. `logo` is null when the tenant hasn't uploaded one.
     */
    public function branding(): JsonResponse
    {
        $color = Configuration::where('key', 'storefront_primary_color')->value('value');
        $logo = Configuration::where('key', 'logo')->value('value');

        return $this->sendSuccess([
            'primary_color' => $color ?: self::DEFAULT_COLOR,
            'logo' => $logo ? asset($logo) : null,
        ], 'Branding retrieved successfully');
    }
}
