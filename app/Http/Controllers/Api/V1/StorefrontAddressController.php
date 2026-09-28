<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Services\QnasAddressService;
use App\Traits\ApiResponseTrait;
use Dedoc\Scramble\Attributes\Group;
use Illuminate\Http\JsonResponse;

/**
 * Qatar National Address lookups for the storefront's delivery form, proxied so
 * the QNAS token stays on the server. A 503 means lookups are off or QNAS is
 * down — the storefront then lets the customer type the numbers instead.
 */
#[Group('Public - Storefront')]
class StorefrontAddressController extends Controller
{
    use ApiResponseTrait;

    public function __construct(private readonly QnasAddressService $qnas) {}

    /**
     * Address zones.
     *
     * Every zone number with its district name(s) in English and Arabic.
     */
    public function zones(): JsonResponse
    {
        return $this->respond($this->qnas->zones(), 'Zones retrieved successfully');
    }

    /**
     * Streets in a zone.
     */
    public function streets(int $zone): JsonResponse
    {
        return $this->respond($this->qnas->streets($zone), 'Streets retrieved successfully');
    }

    /**
     * Buildings on a street.
     *
     * Building numbers with their latitude / longitude.
     */
    public function buildings(int $zone, int $street): JsonResponse
    {
        return $this->respond($this->qnas->buildings($zone, $street), 'Buildings retrieved successfully');
    }

    private function respond(?array $rows, string $message): JsonResponse
    {
        if ($rows === null) {
            return $this->sendError('Address lookup is not available right now.', [], 503);
        }

        return $this->sendSuccess($rows, $message);
    }
}
