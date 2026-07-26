<?php

use App\Livewire\Property\Property\Table as PropertyTable;
use App\Livewire\RentOut\Tabs\PaymentTab;

function resolveColumnsFor(object $component, $saved): array
{
    $method = new ReflectionMethod($component, 'resolveColumns');

    return $method->invoke($component, $saved);
}

function defaultColumnsFor(object $component): array
{
    return (new ReflectionMethod($component, 'defaultColumns'))->invoke($component);
}

it('falls back to the shipped columns when the user has no saved preference', function (): void {
    $tab = new PaymentTab();

    expect(resolveColumnsFor($tab, null))->toBe(defaultColumnsFor($tab));
});

it('honours the saved visibility map', function (): void {
    $tab = new PaymentTab();

    $resolved = resolveColumnsFor($tab, ['created_at' => true, 'remark' => false]);

    expect($resolved['created_at'])->toBeTrue()
        ->and($resolved['remark'])->toBeFalse()
        ->and($resolved['date'])->toBeTrue();
});

it('ignores preference keys for columns the screen no longer ships', function (): void {
    $tab = new PaymentTab();

    $resolved = resolveColumnsFor($tab, ['dropped_column' => true, 'category' => false]);

    expect($resolved)->not->toHaveKey('dropped_column')
        ->and($resolved['category'])->toBeFalse()
        ->and(array_keys($resolved))->toBe(array_keys(defaultColumnsFor($tab)));
});

it('coerces stored values to booleans', function (): void {
    $resolved = resolveColumnsFor(new PropertyTable(), ['type' => 0, 'group' => '1']);

    expect($resolved['type'])->toBeFalse()
        ->and($resolved['group'])->toBeTrue();
});

it('keys each table preference in its own namespace', function (): void {
    $key = fn (object $component) => (new ReflectionMethod($component, 'columnPreferenceKey'))->invoke($component);

    expect($key(new PaymentTab()))->toBe('rent-out.payment-tab.columns')
        ->and($key(new PropertyTable()))->toBe('property.table.columns');
});

it('wires the payment tab column menu to the persisted preference', function (): void {
    $view = file_get_contents(dirname(__DIR__, 3).'/resources/views/livewire/rent-out/tabs/payment-tab.blade.php');

    expect($view)
        ->toContain('columns: @js($this->columns)')
        ->toContain('labels: @js($this->columnLabels())')
        ->toContain('$wire.setColumnVisibility(key, visible)')
        ->toContain('$wire.resetColumns()')
        ->toContain('@change="setColumn(key, $event.target.checked)"');
});
