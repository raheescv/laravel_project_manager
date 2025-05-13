<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class AiImageController extends Controller
{
    public function index()
    {
        return view('inventory.ai-image');
    }

    public function generate(Request $request)
    {
        $request->validate([
            'product_name' => 'required|string|max:1000',
        ]);

        $apiKey = config('openai.api_key');
        if (! $apiKey || ! str_starts_with($apiKey, 'sk-') || strlen($apiKey) < 40) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid OpenAI API key format. Please check your OPENAI_API_KEY configuration.',
            ], 500);
        }

        try {
            $ch = curl_init('https://api.openai.com/v1/images/generations');

            // Clean and enhance the prompt
            $prompt = trim($request->product_name);

            $payload = [
                'prompt' => $prompt,
                'size' => '1024x1024',
            ];

            info('OpenAI Request Payload:', $payload);

            curl_setopt_array($ch, [
                CURLOPT_POST => true,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_SSL_VERIFYPEER => false,
                CURLOPT_SSL_VERIFYHOST => false,
                CURLOPT_HTTPHEADER => [
                    'Authorization: Bearer '.$apiKey,
                    'Content-Type: application/json',
                ],
                CURLOPT_POSTFIELDS => json_encode($payload),
                CURLOPT_TIMEOUT => 60,
            ]);

            // Get full response info
            $rawResponse = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $contentType = curl_getinfo($ch, CURLINFO_CONTENT_TYPE);
            $curlError = curl_error($ch);
            $curlErrno = curl_errno($ch);

            info('OpenAI Raw Response Info:', [
                'http_code' => $httpCode,
                'content_type' => $contentType,
                'curl_error' => $curlError,
                'curl_errno' => $curlErrno,
                'raw_response' => $rawResponse,
            ]);

            if ($curlErrno) {
                throw new \Exception('cURL Error ('.$curlErrno.'): '.$curlError);
            }

            $responseData = json_decode($rawResponse, true);

            if ($httpCode !== 200) {
                $errorMessage = isset($responseData['error']['message']) ?
                    $responseData['error']['message'] :
                    'API Error: '.$httpCode.' - '.($responseData['error']['type'] ?? 'Unknown error');
                throw new \Exception($errorMessage);
            }

            if (! isset($responseData['data'][0]['url'])) {
                throw new \Exception('No image URL in response: '.json_encode($responseData));
            }

            curl_close($ch);

            return response()->json([
                'success' => true,
                'image_url' => $responseData['data'][0]['url'],
            ]);

        } catch (\Exception $e) {
            info('OpenAI Image Generation Error:', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            $errorMessage = $e->getMessage();
            if (str_contains(strtolower($errorMessage), 'curl error 28')) {
                $errorMessage = 'Request timed out. The image generation is taking longer than expected.';
            } elseif (str_contains($errorMessage, '401')) {
                $errorMessage = 'OpenAI API authentication failed. Please check your API key.';
            } elseif (str_contains($errorMessage, '429')) {
                $errorMessage = 'OpenAI API rate limit exceeded. Please try again later.';
            } elseif (str_contains($errorMessage, 'invalid_request_error')) {
                $errorMessage = 'Invalid request to OpenAI API. Please check your input and try again.';
            }

            return response()->json([
                'success' => false,
                'message' => $errorMessage,
                'error_details' => config('app.debug') ? [
                    'exception' => get_class($e),
                    'message' => $e->getMessage(),
                    'file' => $e->getFile(),
                    'line' => $e->getLine(),
                ] : null,
            ], 500);
        }
    }
}
