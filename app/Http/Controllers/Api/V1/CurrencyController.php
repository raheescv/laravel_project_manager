<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Traits\ApiResponseTrait;
use Dedoc\Scramble\Attributes\Group;
use Illuminate\Http\JsonResponse;

#[Group('Mobile - Settings')]
class CurrencyController extends Controller
{
    use ApiResponseTrait;

    /**
     * List configured currencies.
     *
     * Returns the active currencies and the base currency code configured under
     * Settings → Currencies, so the mobile app can cache them and format /
     * convert amounts offline. Rates are expressed as `rate_to_base` (how many
     * base units one unit of the currency is worth).
     */
    public function index(): JsonResponse
    {
        try {
            $active = array_values(array_filter(currencies(), fn ($c) => ! empty($c['active'])));

            $currencies = array_map(fn ($c) => [
                'code' => $c['code'] ?? '',
                'symbol' => $c['symbol'] ?? ($c['code'] ?? ''),
                'name' => $c['name'] ?? ($c['code'] ?? ''),
                'decimals' => (int) ($c['decimals'] ?? 2),
                'rate_to_base' => (float) ($c['rate_to_base'] ?? 1),
                'is_base' => (bool) ($c['is_base'] ?? false),
            ], $active);

            return $this->sendSuccess([
                'base_currency_code' => cache('base_currency_code'),
                'currencies' => $currencies,
            ], 'Currencies retrieved successfully');
        } catch (\Exception $e) {
            return $this->sendServerError('Failed to retrieve currencies: '.$e->getMessage());
        }
    }
}
