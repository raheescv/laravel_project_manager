<?php

namespace App\Helpers;

use App\Models\ApiLog;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;

class MoqSolutionsHelper
{
    protected $endpoint_sandbox;

    protected $endpoint;

    protected $username;

    protected $password;

    protected $token;

    public function __construct()
    {
        $this->username = config('moq.username');
        $this->password = config('moq.password');
        $this->token = config('moq.token');
        $this->endpoint_sandbox = config('moq.endpoint_sandbox');
        $this->endpoint = config('moq.endpoint');
    }

    /**
     * Sync dayClose amount to Moq Solutions API
     */
    public function syncDayCloseAmount($dayCloseData)
    {
        try {
            $dayCloseData['Revenue'] = intval($dayCloseData['Revenue'] ?? 0);
            // Prepare request data
            $requestData = [
                'Revenue' => $dayCloseData['Revenue'],
                'Date' => $dayCloseData['Date'] ?? date('Y-m-d'),
                'Outlet' => $dayCloseData['Outlet'] ?? null,
            ];

            // Create API log entry
            $apiLog = ApiLog::create([
                'endpoint' => $this->endpoint,
                'method' => 'POST',
                'service_name' => 'Moq DayClose Sync',
                'request' => json_encode($requestData),
                'status' => 'pending',
                'username' => $this->username,
                'token' => $this->token,
                'user_id' => Auth::id(),
                'user_name' => Auth::user()?->name,
            ]);

            // Make API call
            $httpResponse = Http::timeout(30)
                ->withHeaders([
                    'Content-Type' => 'application/json',
                    'username' => $this->username,
                    'password' => $this->password,
                    'token' => $this->token,
                ])
                ->post($this->endpoint, [$dayCloseData]);
            // Update API log with response
            $response = $httpResponse->json();

            if (! $httpResponse->successful()) {
                throw new \Exception($httpResponse->json());
            }

            if ($response['status'] != 'Posted') {
                throw new \Exception($response['status']);
            }

            $apiLog->update([
                'response' => json_encode($response),
                'status' => 'success',
            ]);

            return [
                'success' => true,
                'message' => 'DayClose amount synced successfully',
                'data' => $httpResponse->json(),
                'api_log_id' => $apiLog->id,
            ];
        } catch (\Exception $e) {
            if (isset($apiLog)) {
                $apiLog->update([
                    'status' => 'failed',
                    'description' => $e->getMessage(),
                ]);
            }

            return [
                'success' => false,
                'message' => 'Exception occurred while syncing dayClose amount',
                'error' => $e->getMessage(),
                'api_log_id' => $apiLog->id ?? null,
            ];
        }
    }

    /**
     * Get API configuration
     */
    public function getConfig()
    {
        return [
            'endpoint' => $this->endpoint,
            'endpoint_sandbox' => $this->endpoint_sandbox,
            'username' => $this->username,
            'password' => $this->password,
            'token' => $this->token,
            'has_password' => ! empty($this->password),
            'has_token' => ! empty($this->token),
        ];
    }

    /**
     * Whether a sandbox endpoint is configured that is not the live endpoint.
     */
    public function hasSeparateSandbox(): bool
    {
        return ! empty($this->endpoint_sandbox) && rtrim($this->endpoint_sandbox, '/') !== rtrim((string) $this->endpoint, '/');
    }

    /**
     * Test API connection against the sandbox endpoint
     */
    public function testConnection()
    {
        if (! $this->hasSeparateSandbox()) {
            return [
                'success' => false,
                'status_code' => 0,
                'message' => 'Test connection is disabled: the sandbox endpoint is missing or the same as the live endpoint, so a test would post real sales.',
                'response' => null,
            ];
        }

        try {
            $testData = [
                [
                    'Date' => date('Y-m-d'),
                    'Outlet' => config('app.name'),
                    'Revenue' => 1,
                ],
            ];

            $apiLog = ApiLog::create([
                'endpoint' => $this->endpoint_sandbox,
                'method' => 'POST',
                'service_name' => 'Moq Test Connection',
                'request' => json_encode($testData),
                'status' => 'pending',
                'username' => $this->username,
                'token' => $this->token,
                'user_id' => Auth::id(),
                'user_name' => Auth::user()?->name,
            ]);

            $response = Http::timeout(10)
                ->withHeaders([
                    'Content-Type' => 'application/json',
                    'Username' => $this->username,
                    'Password' => $this->password,
                    'Token' => $this->token,
                ])
                ->post($this->endpoint_sandbox, $testData);

            $apiLog->update([
                'response' => json_encode($response->json()),
                'status' => $response->successful() ? 'success' : 'failed',
                'description' => $response->successful() ? null : 'HTTP '.$response->status(),
            ]);

            return [
                'success' => $response->successful(),
                'status_code' => $response->status(),
                'message' => $response->successful() ? 'Connection successful' : 'Connection failed',
                'response' => $response->json(),
                'api_log_id' => $apiLog->id,
            ];
        } catch (\Exception $e) {
            if (isset($apiLog)) {
                $apiLog->update([
                    'status' => 'failed',
                    'description' => $e->getMessage(),
                ]);
            }

            return [
                'success' => false,
                'status_code' => 0,
                'message' => 'Connection failed: '.$e->getMessage(),
                'response' => null,
                'api_log_id' => $apiLog->id ?? null,
            ];
        }
    }
}
