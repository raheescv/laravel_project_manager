<?php

namespace App\Http\Controllers;

use App\Services\Dashboard\WidgetDefinition;
use App\Services\Dashboard\WidgetRegistry;
use Illuminate\Http\Request;
use Illuminate\View\View;

class HomeController extends Controller
{
    public function index(Request $request, WidgetRegistry $registry): View
    {
        $user = $request->user();
        $currency = base_currency();

        return view('dashboard', [
            'dashboard' => [
                'catalogue' => array_values(array_map(fn (WidgetDefinition $definition): array => $definition->toArray(), $registry->visibleTo($user))),
                'layout' => $registry->layoutFor($user),
                'defaultLayout' => $registry->defaultLayout(),
                'canCustomize' => $user->can('dashboard.customize'),
                'firstName' => str($user->name)->before(' ')->toString(),
                'branch' => session('branch_name'),
                'currency' => ['code' => $currency['code'] ?? null, 'decimals' => currency_decimals()],
                'urls' => [
                    'widget' => route('dashboard::widget', ['key' => '__KEY__']),
                    'layout' => route('dashboard::layout.save'),
                ],
            ],
        ]);
    }
}
