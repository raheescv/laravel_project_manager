<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\ApiLog;
use App\Traits\ApiResponseTrait;
use Dedoc\Scramble\Attributes\Group;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

#[Group('Mobile - Diagnostics')]
class ClientErrorController extends Controller
{
    use ApiResponseTrait;

    /**
     * Record an uncaught error from a mobile client.
     *
     * The Flutter apps install global error handlers (CrashReporter) but have
     * no crash-reporting SDK; this is where those reports land. Rows go into
     * `api_logs` with a `client/crash` endpoint and `failed` status, so they
     * sit alongside the server-side request log instead of needing their own
     * table.
     *
     * Fire-and-forget from the client's point of view: a failure here must
     * never surface to a cashier, so the app ignores the response.
     */
    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'message' => ['required', 'string', 'max:2000'],
            'context' => ['nullable', 'string', 'max:500'],
            'stack' => ['nullable', 'string', 'max:20000'],
            'platform' => ['nullable', 'string', 'max:120'],
            'app_version' => ['nullable', 'string', 'max:60'],
            'occurred_at' => ['nullable', 'date'],
        ]);

        $user = $request->user();

        ApiLog::create([
            'endpoint' => 'client/crash',
            'method' => 'POST',
            'service_name' => 'mobile',
            'status' => 'failed',
            'description' => mb_substr($data['message'], 0, 255),
            'request' => [
                'context' => $data['context'] ?? null,
                'platform' => $data['platform'] ?? null,
                'app_version' => $data['app_version'] ?? null,
                'occurred_at' => $data['occurred_at'] ?? now()->toIso8601String(),
            ],
            'response' => ['stack' => $data['stack'] ?? null],
            'user_id' => $user?->id,
            'user_name' => $user?->name,
        ]);

        return $this->successResponse(null, 'Recorded.');
    }
}
