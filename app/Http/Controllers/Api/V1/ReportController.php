<?php

namespace App\Http\Controllers\Api\V1;

use App\Actions\V1\Report\GetAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\V1\Report\GetRequest;
use App\Traits\ApiResponseTrait;
use Dedoc\Scramble\Attributes\Group;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;

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
        } catch (\Throwable $e) {
            // Catch \Throwable (not just \Exception) so TypeErrors/Errors are logged
            // too; sendServerError otherwise swallows the real cause with no trace.
            Log::error('API v1 report failed', [
                'type' => $request->input('type'),
                'exception' => $e,
            ]);

            return $this->sendServerError('Failed to generate report: '.$e->getMessage());
        }
    }
}
