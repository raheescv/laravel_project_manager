<?php

use App\Livewire\Settings\UniqueNoCounterConfiguration;
use App\Models\UniqueNoCounter;
use Livewire\Livewire;
use Tests\Support\PosWorld;

beforeEach(function (): void {
    $this->world = PosWorld::create();
    $this->world->user->forceFill(['is_super_admin' => true])->save();
    $this->actingAs($this->world->user->fresh());
});

it('groups counters by document and saves an edited number', function (): void {
    UniqueNoCounter::query()->create(['year' => '26', 'branch_code' => 'ZZ1', 'segment' => 'Sale', 'number' => 41]);

    $component = Livewire::test(UniqueNoCounterConfiguration::class)
        ->assertSee('Document Numbering')
        ->assertSee('ZZ1')
        ->assertSee('42');

    $index = collect($component->get('rows'))->search(fn (array $row): bool => $row['branch_code'] === 'ZZ1');

    $component->set("rows.{$index}.number", 100)->call('save')->assertHasNoErrors();

    expect(UniqueNoCounter::query()->where('branch_code', 'ZZ1')->value('number'))->toBe(100);
});
