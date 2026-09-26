<x-app-layout>
    <div class="container-fluid py-3">
        @livewire('tenant.view', ['tenantId' => $tenant->id])
    </div>
    <x-tenant.tenant-modal />
</x-app-layout>
