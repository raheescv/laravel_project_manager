<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Inertia\Inertia;
use App\Models\MeasurementCategory;

class MeasurementCategoryController extends Controller
{
    /**
     * Page load
     */
    public function index()
    {
        return view('settings.measurement_category.index');
    }

    /**
     * Dropdown / table list (AJAX / TanStack)
     */
    public function get(Request $request)
    {
        return response()->json(
            MeasurementCategory::select('id', 'name')->latest()->get()
        );
    }

    /**
     * Store
     */
    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
        ]);

        MeasurementCategory::create([
            'name' => $request->name,
        ]);

        return back()->with('success', 'Measurement category created successfully.');
    }

    /**
     * Update
     */
    public function update(Request $request, $id)
    {
        $request->validate([
            'name' => 'required|string|max:255',
        ]);

        $category = MeasurementCategory::findOrFail($id);
        $category->update([
            'name' => $request->name,
        ]);

        return back()->with('success', 'Measurement category updated successfully.');
    }

    /**
     * Delete
     */
    public function destroy($id)
    {
        $category = MeasurementCategory::find($id);

        if (!$category) {
            return back()->with('error', 'Not found');
        }

        $category->delete();

        return back()->with('success', 'Deleted successfully');
    }

     public function measurements()
    {
        // Loads the Livewire template form
        return view('measurement_category.measurements');
    }

        public function addMeasurementField($id)
    {
        $category = MeasurementCategory::findOrFail($id);

        // Pass the category to the view
        return view('measurement_category.add_field', compact('category'));
    }

}
