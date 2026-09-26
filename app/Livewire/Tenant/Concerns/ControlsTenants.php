<?php

namespace App\Livewire\Tenant\Concerns;

use App\Actions\Tenant\ToggleStatusAction;
use App\Models\Tenant;
use App\Services\TenantAnalyticsService;
use App\Services\TenantSwitchService;

/**
 * Row actions shared by the Tenant Control list and the tenant view page.
 * Both apply immediately — there is no Save step for a switch.
 */
trait ControlsTenants
{
    public function toggleStatus(int $id, bool $isActive): void
    {
        abort_unless(auth()->user()?->is_super_admin, 403);

        $response = (new ToggleStatusAction())->execute($id, $isActive);
        if (! $response['success']) {
            $this->dispatch('error', ['message' => $response['message']]);

            return;
        }
        TenantAnalyticsService::forget($id);
        $this->dispatch('success', ['message' => $response['message']]);
    }

    public function switchInto(int $id, TenantSwitchService $switch)
    {
        abort_unless(auth()->user()?->is_super_admin, 403);

        try {
            $tenant = Tenant::findOrFail($id);

            return $this->redirect($switch->entryUrl($tenant, auth()->user(), route('tenants::view', $tenant->id)));
        } catch (\Throwable $e) {
            $this->dispatch('error', ['message' => $e->getMessage()]);
        }
    }
}
