<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Artisan;

class GitController extends Controller
{
    public function pull(): JsonResponse
    {
        try {
            $output = shell_exec('git pull 2>&1');

            if (strpos($output, 'Already up to date.') !== false) {
                return response()->json([
                    'status' => 'success',
                    'message' => 'Already up to date.',
                    'output' => $output,
                ]);
            }

            // Clear cache after pull
            Artisan::call('optimize:clear');

            return response()->json([
                'status' => 'success',
                'message' => 'Code updated successfully',
                'output' => $output,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to pull updates',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}
