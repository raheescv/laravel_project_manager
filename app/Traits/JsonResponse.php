<?php

namespace App\Traits;

trait JsonResponse
{
    /**
     * Send success response with data
     *
     * @return \Illuminate\Http\JsonResponse
     */
    protected function success(string $message, mixed $data = null, int $code = 200)
    {
        $response = [
            'success' => true,
            'message' => $message,
        ];

        if ($data !== null) {
            $response['data'] = $data;
        }

        return response()->json($response, $code);
    }

    /**
     * Send error response
     *
     * @return \Illuminate\Http\JsonResponse
     */
    protected function error(string $message, mixed $errors = null, int $code = 400)
    {
        $response = [
            'success' => false,
            'message' => $message,
        ];

        if ($errors !== null) {
            $response['errors'] = $errors;
        }

        return response()->json($response, $code);
    }
}
