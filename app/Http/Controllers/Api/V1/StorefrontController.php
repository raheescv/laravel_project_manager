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
     *
     * The `company` block carries the contact details from Settings → Company
     * Profile — name, mobile, email — plus `google_review_url`, the link the
     * storefront's rate-us prompt sends customers to. Each is null when unset.
     */
    public function branding(): JsonResponse
    {
        $keys = ['storefront_primary_color', 'logo', 'company_name', 'mobile', 'email', 'google_review_url'];
        $config = Configuration::whereIn('key', $keys)->pluck('value', 'key');

        $value = fn (string $key) => filled($config[$key] ?? null) ? $config[$key] : null;

        $logo = $value('logo');

        return $this->sendSuccess([
            'primary_color' => $value('storefront_primary_color') ?: self::DEFAULT_COLOR,
            'logo' => $logo ? asset($logo) : null,
            'company' => [
                'name' => $value('company_name'),
                'mobile' => $value('mobile'),
                'email' => $value('email'),
                'google_review_url' => $value('google_review_url'),
            ],
        ], 'Branding retrieved successfully');
    }
}
