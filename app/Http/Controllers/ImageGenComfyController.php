<?php

namespace App\Http\Controllers;

use App\Jobs\GenerateServiceImageJob;
use App\Models\Product;
use Illuminate\Http\Request;

class ImageGenComfyController extends Controller
{
    public function generate(Request $request)
    {
        // $products = Product::service()->limit(1)->get();
        // foreach ($products as $key => $value) {
        //     $category = $value->mainCategory?->name;
        //     $serviceName = $value->name;
        //     GenerateServiceImageJob::dispatch($category, $serviceName);
        // }

        $category = 'Cutting';
        // $category = 'Wax';
        $serviceName = 'Hair Cutting';
        // $serviceName = 'Full body with brazilian';
        GenerateServiceImageJob::dispatch($category, $serviceName);

        return response()->json([
            'success' => true,
            'message' => 'Image generation job has been queued',
        ]);
    }
}
