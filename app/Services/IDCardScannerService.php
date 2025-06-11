<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class IDCardScannerService
{
    protected $scannerUrl;

    public function __construct()
    {
        $this->scannerUrl = config('services.id_scanner.url', 'http://localhost:5000');
    }

    public function scanIDCard(UploadedFile $image)
    {
        try {
            $response = Http::timeout(30)
                ->attach('image', file_get_contents($image), $image->getClientOriginalName())
                ->post($this->scannerUrl.'/scan');
            if ($response->successful()) {
                return $response->json();
            }

            Log::error('ID Scanner Error', [
                'status' => $response->status(),
                'body' => $response->body(),
            ]);

            throw new \Exception('Failed to process ID card: '.($response->json()['error'] ?? 'Unknown error'));
        } catch (\Exception $e) {
            Log::error('ID Scanner Exception', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            throw $e;
        }
    }

    public function isHealthy()
    {
        try {
            $response = Http::timeout(5)
                ->get($this->scannerUrl.'/health');

            return $response->successful() && $response->json()['status'] === 'healthy';
        } catch (\Exception $e) {
            return false;
        }
    }
}
