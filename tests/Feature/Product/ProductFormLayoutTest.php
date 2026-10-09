<?php

use App\Livewire\Product\Page;
use Illuminate\Support\Facades\Queue;
use Livewire\Livewire;
use Tests\Support\PosWorld;

/**
 * The form's top block is two aligned rows: both names, then code beside status.
 */
beforeEach(function (): void {
    Queue::fake();
    $this->world = PosWorld::create();
    $this->actingAs($this->world->user);
    session(['branch_id' => $this->world->branch->id]);
});

it('pairs the two names on the first row and code with status on the second', function (): void {
    $html = Livewire::test(Page::class, ['type' => 'product'])->html();

    $html = str($html)->after('<div class="card-body p-4">')->before('for="department_id"')->toString();
    $rows = collect(explode('<div class="row g-3">', $html))->filter(fn (string $row): bool => str_contains($row, 'for="'))->values();

    expect($rows)->toHaveCount(2)
        ->and($rows[0])->toContain('for="name"')->toContain('for="name_arabic"')->not->toContain('for="code"')
        ->and(substr_count($rows[0], 'col-md-6'))->toBe(2)
        ->and($rows[1])->toContain('for="code"')->toContain('for="status"');
});
