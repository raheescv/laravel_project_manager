<?php

namespace App\Http\Controllers;

use App\Jobs\GenerateProductImageWithOpenAIJob;
use Illuminate\Http\Request;

class ImageGenComfyController extends Controller
{
    public function generate(Request $request)
    {
        $name = 'tiger ballet candy 37.5';
        GenerateProductImageWithOpenAIJob::dispatchSync($name);

        return response()->json([
            'success' => true,
            'message' => 'Image generation job has been queued',
        ]);
    }
}
