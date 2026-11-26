<?php

it('shows product audits on the inventory product view page', function (): void {
    $projectRoot = dirname(__DIR__, 3);
    $view = file_get_contents($projectRoot.'/resources/views/livewire/inventory/view.blade.php');
    $component = file_get_contents($projectRoot.'/app/Livewire/Inventory/View.php');

    expect($component)->toContain("'audits.user:id,name'")
        ->and($view)->toContain("wire:click=\"tabSelect('audit')\"")
        ->and($view)->toContain('Product Audit');
});

it('offers table and timeline views of the product audit like the sale view', function (): void {
    $view = file_get_contents(dirname(__DIR__, 3).'/resources/views/livewire/inventory/view.blade.php');

    expect($view)->toContain("localStorage.getItem('audit-report-view')")
        ->and($view)->toContain('<x-audit.sheet :audits="$productAudits"')
        ->and($view)->toContain('<x-audit.table :audits="$productAudits"')
        ->and($view)->toContain("@click=\"auditView = 'table'\"")
        ->and($view)->toContain("@click=\"auditView = 'timeline'\"");
});
