<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use App\Models\MeasurementSubCategory;
use Illuminate\Http\Request;

class MeasurementSubCategoryController extends Controller
{
    public function index()
    {
        return view('settings.measurement-sub-category.index');
    }

    public function get(Request $request)
    {
        return MeasurementSubCategory::with('category')
            ->latest()
            ->get();
    }

    public function store(Request $request)
    {
        $request->validate([
            'measurement_category_id' => ['required'],
            'name' => ['required', 'unique:measurement_sub_categories,name'],
        ]);

        $subcategory = MeasurementSubCategory::create($request->all());

        return response()->json([
            'success' => true,
            'message' => 'Sub Category created successfully',
            'data' => $subcategory,
        ]);
    }

    public function update(Request $request, $id)
    {
        $request->validate([
            'measurement_category_id' => ['required'],
            'name' => ['required', 'unique:measurement_sub_categories,name,' . $id . ',id'],
        ]);

        $subcategory = MeasurementSubCategory::findOrFail($id);
        $subcategory->update($request->all());

        return response()->json([
            'success' => true,
            'message' => 'Sub Category updated successfully',
            'data' => $subcategory,
        ]);
    }

    public function destroy($id)
    {
        MeasurementSubCategory::findOrFail($id)->delete();

        return response()->json([
            'success' => true,
            'message' => 'Sub Category deleted successfully',
        ]);
    }
}
