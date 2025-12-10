<?php

namespace App\Http\Controllers\Settings;
use Inertia\Inertia;
use App\Http\Controllers\Controller;
use App\Models\Category;
use Illuminate\Http\Request;
use App\Models\User;
use App\Models\MeasurementTemplate;
use App\Models\MeasurementValue;

class CategoryController extends Controller
{
    public function index()
    {
        return view('settings.category.index');
    }

    public function get(Request $request)
    {
        $list = (new Category())->getDropDownList($request->all());

        return response()->json($list);
    }

    public function measurements()
   {

    return view('category.measurements'); // view path in next step

   }
    public function measurementdata()
    {
        // REQUIRED FOR INERTIA + REACT
         Inertia::setRootView('app-react');


        $customers = User::select('id', 'name')->get();
        $categories = Category::select('id', 'name')->get();

         $templates = MeasurementTemplate::get();

        // List customer name & category name also
        $savedMeasurements = MeasurementValue::with([
            'template:id,name,category_id',
            'template.category:id,name',
            'customer:id,name'
        ])->get();

        return Inertia::render('Measurements/Index', [
            'customers' => $customers,
            'categories' => $categories,
            'templates' => $templates,
            'savedMeasurements' => $savedMeasurements,
            'flash' => ['success' => session('success')],
        ]);
    }


public function storeMeasurements(Request $request)
{
    $data = $request->validate([
        'customer_id' => 'required|exists:users,id',
        'category_id' => 'required|exists:categories,id',
        'values' => 'required|array',
    ]);

    foreach ($data['values'] as $templateId => $value) {
        MeasurementValue::updateOrCreate(
            [
                'customer_id' => $data['customer_id'],
                'measurement_template_id' => $templateId
            ],
            [
                'category_id' => $data['category_id'],
                'values' => ['value' => $value], // wrap as array
            ]
        );
    }

    return redirect()->back()->with('success', 'Measurements saved!');
}





}
