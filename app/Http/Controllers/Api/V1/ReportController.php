<?php

namespace App\Http\Controllers\Api\V1;

use App\Actions\V1\Report\GetAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\V1\Report\GetRequest;
use App\Traits\ApiResponseTrait;
use Dedoc\Scramble\Attributes\Group;
use Illuminate\Http\JsonResponse;

#[Group('Mobile - Admin')]
class ReportController extends Controller
{
    use ApiResponseTrait;

    /**
     * Fetch reports.
     *
     * Fetches a system-wide analytical report based on the requested breakdown type.
     */
    public function index(GetAction $action, GetRequest $request): JsonResponse
    {
        try {
            $result = $action->execute($request);

            return $this->sendSuccess($result, 'Report generated successfully');
        } catch (\Exception $e) {
            return $this->sendServerError('Failed to generate report: '.$e->getMessage());
        }
    }
}
