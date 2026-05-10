<?php

it('shows product audits on the inventory product view page', function (): void {
    $projectRoot = dirname(__DIR__, 3);
    $view = file_get_contents($projectRoot.'/resources/views/livewire/inventory/view.blade.php');
    $component = file_get_contents($projectRoot.'/app/Livewire/Inventory/View.php');

    expect($component)->toContain("'audits.user:id,name'")
        ->and($view)->toContain("wire:click=\"tabSelect('audit')\"")
        ->and($view)->toContain('Product Audit')
        ->and($view)->toContain('$audit->old_values')
        ->and($view)->toContain('$audit->new_values');
});
