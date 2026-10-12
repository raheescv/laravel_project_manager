<?php

namespace App\Http\Controllers;

use App\Http\Requests\Dashboard\DashboardWidgetRequest;
use App\Http\Requests\Dashboard\SaveDashboardLayoutRequest;
use App\Services\Dashboard\WidgetRegistry;
use Illuminate\Http\JsonResponse;

/**
 * JSON endpoints behind the Vue home dashboard: one widget's data, and the
 * signed-in user's saved layout.
 */
class DashboardController extends Controller
{
    public function __construct(protected WidgetRegistry $registry) {}

    public function widget(DashboardWidgetRequest $request, string $key): JsonResponse
    {
        $definition = $this->registry->find($key);
        abort_if($definition === null, 404);
        abort_unless($definition->isVisibleTo($request->user()), 403);

        return response()->json($this->registry->data($key, $request->validated('option'), $request->safe()->only(['date'])));
    }

    public function saveLayout(SaveDashboardLayoutRequest $request): JsonResponse
    {
        $this->registry->saveLayout($request->user(), $request->layout());

        return response()->json(['layout' => $this->registry->layoutFor($request->user())]);
    }
}
