<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use App\Models\TailoringMeasurementOption;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class TailoringMeasurementOptionController extends Controller
{
    public function index()
    {
        return view('settings.tailoring-measurement-option.index');
    }

    public function getByType(Request $request): JsonResponse
    {
        $type = $request->get('type');
        if (! $type || ! array_key_exists($type, TailoringMeasurementOption::OPTION_TYPES)) {
            return response()->json(['items' => []]);
        }

        $items = TailoringMeasurementOption::getOptionsByType($type);
        $list = [];
        foreach ($items as $id => $name) {
            $list[] = ['id' => $id, 'name' => $name];
        }

        return response()->json(['items' => $list]);
    }
}
