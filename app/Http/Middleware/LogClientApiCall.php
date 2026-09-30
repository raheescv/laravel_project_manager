<?php

namespace App\Http\Middleware;

use App\Models\ApiLog;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Records every API call made by a first-party app into `api_logs`, tagged
 * with the app's name, version and platform, so support can tell which build
 * a customer is running.
 *
 * Only requests that identify themselves with an `X-App-Name` header are
 * logged; browser and third-party traffic passes through untouched. Response
 * bodies are kept only for failures — catalog payloads are too large to store
 * for every successful call.
 */
class LogClientApiCall
{
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        $appName = $this->header($request, 'X-App-Name');
        if ($appName === null) {
            return $response;
        }

        try {
            $failed = $response->getStatusCode() >= 400;

            ApiLog::create([
                'endpoint' => $request->path(),
                'method' => $request->method(),
                'service_name' => $appName,
                'app_version' => $this->header($request, 'X-App-Version'),
                'app_platform' => $this->header($request, 'X-App-Platform'),
                'request' => $request->query(),
                'response' => $failed ? $this->decodedBody($response) : null,
                'status' => $failed ? 'failed' : 'success',
                'description' => 'HTTP '.$response->getStatusCode(),
                'user_id' => $request->user()?->id,
                'user_name' => $request->user()?->name,
            ]);
        } catch (\Throwable $e) {
            // Logging must never break the request it describes.
        }

        return $response;
    }

    private function header(Request $request, string $name): ?string
    {
        $value = trim((string) $request->header($name, ''));

        return $value === '' ? null : mb_substr($value, 0, 60);
    }

    /**
     * @return array<string, mixed>|null
     */
    private function decodedBody(Response $response): ?array
    {
        $decoded = json_decode((string) $response->getContent(), true);

        return is_array($decoded) ? $decoded : null;
    }
}
