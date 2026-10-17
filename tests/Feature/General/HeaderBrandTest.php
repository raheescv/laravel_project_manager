<?php

use Tests\Support\PosWorld;

/**
 * The tenant logo lives in the top header brand (Nifty hides the brand title
 * when the sidebar collapses, leaving only the logo) — not in a sidebar card.
 */
beforeEach(function (): void {
    $this->world = PosWorld::create();
    $this->actingAs($this->world->user);
    session(['branch_id' => $this->world->branch->id, 'branch_name' => $this->world->branch->name]);
});

it('shows the logo next to the app name in the header brand', function (): void {
    $html = view('layouts.header')->render();

    expect($html)
        ->toContain('class="brand-img header-brand-logo stretched-link"')
        ->toContain(config('app.name', 'Astra').' logo')
        ->toContain('class="brand-title d-none d-sm-block"');
});

it('no longer renders the workspace brand card in the sidebar', function (): void {
    $html = view('layouts.navigation')->render();

    expect($html)
        ->not->toContain('_dm-mainnavProfile')
        ->not->toContain('mainnav__brand');
});
