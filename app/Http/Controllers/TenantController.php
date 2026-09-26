<?php

namespace App\Http\Controllers;

use App\Models\Tenant;
use App\Services\TenantService;
use App\Services\TenantSwitchService;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class TenantController extends Controller
{
    public function index(): View
    {
        return view('tenant.index');
    }

    public function view(int $id): View
    {
        $tenant = Tenant::withTrashed()->findOrFail($id);

        return view('tenant.view', compact('tenant'));
    }

    public function enter(string $token, TenantSwitchService $switch, TenantService $tenantService): RedirectResponse
    {
        abort_unless($switch->enter($token, $tenantService->getCurrentTenant()), 403, 'This switch link is invalid or has expired. Start again from Tenant Control.');

        return redirect()->route('dashboard');
    }
}
