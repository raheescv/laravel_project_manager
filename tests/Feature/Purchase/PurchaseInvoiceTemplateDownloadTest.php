<?php

use App\Livewire\Purchase\Import;
use Livewire\Livewire;
use Tests\Support\PosWorld;

/**
 * The template button on step 2. `sample()` used to share its name with the
 * `$sample` property; Livewire's $wire proxy resolves state before methods, so
 * wire:click evaluated `$wire.sample()` on an array and did nothing.
 */
it('serves the item template as a real xlsx download', function (): void {
    $world = PosWorld::create();
    $this->actingAs($world->user);

    Livewire::test(Import::class)
        ->call('downloadTemplate')
        ->assertFileDownloaded('purchase_invoice_items.xlsx');
});

it('keeps no public property that shadows an action method', function (): void {
    $reflection = new ReflectionClass(Import::class);

    $properties = collect($reflection->getProperties(ReflectionProperty::IS_PUBLIC))
        ->reject->isStatic()
        ->map->getName();

    $methods = collect($reflection->getMethods(ReflectionMethod::IS_PUBLIC))
        ->reject->isStatic()
        ->map->getName();

    expect($properties->intersect($methods))->toBeEmpty();
});
