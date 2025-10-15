<?php

namespace App\Traits;

use Illuminate\Http\JsonResponse;

trait ApiResponseTrait
{
    /**
     * Send a success response.
     *
     * @param  mixed  $result
     */
    public function sendSuccess($result, string $message = 'Success', int $code = 200): JsonResponse
    {
        $response = [
            'success' => true,
            'data' => $result,
            'message' => $message,
        ];

        return response()->json($response, $code);
    }

    /**
     * Send an error response.
     */
    public function sendError(string $error = 'Error occurred', array $errorMessages = [], int $code = 404): JsonResponse
    {
        $response = [
            'success' => false,
            'message' => $error,
        ];

        if (! empty($errorMessages)) {
            $response['data'] = $errorMessages;
        }

        return response()->json($response, $code);
    }

    /**
     * Send a validation error response.
     */
    public function sendValidationError(array $errors, string $message = 'Validation failed'): JsonResponse
    {
        return $this->sendError($message, $errors, 422);
    }

    /**
     * Send a not found error response.
     */
    public function sendNotFoundError(string $message = 'Resource not found'): JsonResponse
    {
        return $this->sendError($message, [], 404);
    }

    /**
     * Send an unauthorized error response.
     */
    public function sendUnauthorizedError(string $message = 'Unauthorized'): JsonResponse
    {
        return $this->sendError($message, [], 401);
    }

    /**
     * Send a forbidden error response.
     */
    public function sendForbiddenError(string $message = 'Forbidden'): JsonResponse
    {
        return $this->sendError($message, [], 403);
    }

    /**
     * Send a server error response.
     */
    public function sendServerError(string $message = 'Internal server error'): JsonResponse
    {
        return $this->sendError($message, [], 500);
    }
}
