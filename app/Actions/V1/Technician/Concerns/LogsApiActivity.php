<?php

namespace App\Actions\V1\Technician\Concerns;

use App\Models\ApiLog;

/**
 * Records an `api_logs` row for a mutating action and finalises it with the
 * outcome — the same pattern the Sale / SaleReturn V1 actions use.
 */
trait LogsApiActivity
{
    /**
     * Run a mutating action's body under an api_logs entry: opens the log,
     * runs $action, marks the log `success` (with $context) on the happy path
     * or `failed` (with the exception message) before re-throwing. Every
     * technician write action calls this instead of hand-rolling its own
     * try/catch, so the logging plumbing lives in exactly one place.
     *
     * @template T
     *
     * @param  array<string, mixed>  $context  Known-at-entry identifiers (e.g. ['complaint_id' => $id]) recorded on success.
     * @param  \Closure(): T  $action
     * @return T
     */
    protected function withApiLog(string $serviceName, array $context, \Closure $action): mixed
    {
        $apiLog = $this->startApiLog($serviceName);

        try {
            $result = $action();
            $this->completeApiLog($apiLog, 'success', $context);

            return $result;
        } catch (\Throwable $e) {
            $this->completeApiLog($apiLog, 'failed', null, $e->getMessage());
            throw $e;
        }
    }

    /**
     * Open a pending api_logs row for the current request. Never throws —
     * logging must not break the request it describes.
     */
    private function startApiLog(string $serviceName): ?ApiLog
    {
        try {
            $request = request();

            return ApiLog::create([
                'endpoint' => $request->path(),
                'method' => $request->method(),
                'service_name' => $serviceName,
                // Exclude binary uploads so the log stays a readable JSON blob.
                'request' => $request->except(['attachments', 'attachments.*']),
                'status' => 'pending',
                'user_id' => $request->user()?->id,
                'user_name' => $request->user()?->name,
            ]);
        } catch (\Throwable $e) {
            return null;
        }
    }

    /**
     * Finalise the api_logs row with the outcome of the request.
     *
     * @param  array<string, mixed>|null  $response
     */
    private function completeApiLog(?ApiLog $apiLog, string $status, ?array $response = null, ?string $description = null): void
    {
        if (! $apiLog) {
            return;
        }

        try {
            $apiLog->update([
                'status' => $status,
                'response' => $response,
                'description' => $description,
            ]);
        } catch (\Throwable $e) {
            // Logging must never mask the real outcome of the request.
        }
    }
}
