<?php

use App\Http\Middleware\EnsureBranchSelected;
use App\Http\Middleware\RequireOpenDaySession;
use App\Livewire\General\BranchSelection;
use App\Models\UserHasBranch;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\Request as RequestFacade;
use Livewire\Livewire;
use Tests\Support\PosWorld;

/**
 * With no branch in the session the branch popup is forced open on every admin
 * page, and pages outside the admin layout send the user to it — then back.
 */
beforeEach(function (): void {
    $this->world = PosWorld::create();
    UserHasBranch::create(['user_id' => $this->world->user->id, 'branch_id' => $this->world->branch->id]);

    $this->actingAs($this->world->user);
});

it('is required until both the branch id and name are in the session', function (): void {
    session()->forget(['branch_id', 'branch_name']);
    expect(BranchSelection::isRequired())->toBeTrue();

    session(['branch_id' => $this->world->branch->id]);
    expect(BranchSelection::isRequired())->toBeTrue();

    session(['branch_name' => $this->world->branch->name]);
    expect(BranchSelection::isRequired())->toBeFalse();
});

it('renders the required popup without a close button', function (): void {
    Livewire::test(BranchSelection::class, ['required' => true])
        ->assertSee('Select a Branch')
        ->assertDontSeeHtml('data-bs-dismiss="modal"');

    Livewire::test(BranchSelection::class)
        ->assertSee('Branch Selection')
        ->assertSeeHtml('data-bs-dismiss="modal"');
});

it('opens the popup on page load when no branch is selected', function (): void {
    session()->forget(['branch_id', 'branch_name']);
    $html = Blade::render("<x-branch-selection-modal />@stack('scripts')");

    expect($html)
        ->toContain('data-bs-backdrop="static"')
        ->toContain("bootstrap.Modal.getOrCreateInstance(document.getElementById('branch_selection_modal')).show()");
});

it('leaves the popup closed when a branch is selected', function (): void {
    session(['branch_id' => $this->world->branch->id, 'branch_name' => $this->world->branch->name]);
    $html = Blade::render("<x-branch-selection-modal />@stack('scripts')");

    expect($html)
        ->not->toContain('data-bs-backdrop="static"')
        ->not->toContain('.show()');
});

it('sends pages outside the admin layout to the dashboard and remembers where to return', function (): void {
    session()->forget(['branch_id', 'branch_name']);
    $request = Request::create('http://tenant.localhost/inventory/search?q=shoe');

    $response = (new EnsureBranchSelected())->handle($request, fn () => response('page'));

    expect($response->isRedirect(route('dashboard')))->toBeTrue()
        ->and(session(BranchSelection::RETURN_TO))->toBe('http://tenant.localhost/inventory/search?q=shoe');
});

it('uses a full-page location visit for Inertia requests', function (): void {
    session()->forget(['branch_id', 'branch_name']);
    $request = Request::create('http://tenant.localhost/tailoring/job-completion');
    $request->headers->set('X-Inertia', 'true');
    RequestFacade::swap($request); // Inertia::location() reads the bound request

    $response = (new EnsureBranchSelected())->handle($request, fn () => response('page'));

    expect($response->getStatusCode())->toBe(409)
        ->and($response->headers->get('X-Inertia-Location'))->toBe(route('dashboard'));
});

it('lets the request through once a branch is selected', function (): void {
    session(['branch_id' => $this->world->branch->id, 'branch_name' => $this->world->branch->name]);

    $response = (new EnsureBranchSelected())->handle(Request::create('/inventory/search'), fn () => response('page'));

    expect($response->getContent())->toBe('page')
        ->and(session()->has(BranchSelection::RETURN_TO))->toBeFalse();
});

it('remembers the POS url when the day session guard bounces for a missing branch', function (): void {
    session()->forget(['branch_id', 'branch_name']);

    (new RequireOpenDaySession())->handle(Request::create('http://tenant.localhost/sale/pos'), fn () => response('page'));

    expect(session(BranchSelection::RETURN_TO))->toBe('http://tenant.localhost/sale/pos');
});

it('returns to the remembered page after a branch is picked', function (): void {
    session()->forget(['branch_id', 'branch_name']);
    session([BranchSelection::RETURN_TO => 'http://tenant.localhost/inventory/search']);

    Livewire::test(BranchSelection::class, ['required' => true])
        ->call('select', $this->world->branch->id)
        ->assertRedirect('http://tenant.localhost/inventory/search');

    expect(session('branch_id'))->toBe($this->world->branch->id)
        ->and(session('branch_name'))->toBe('Main Branch')
        ->and(session()->has(BranchSelection::RETURN_TO))->toBeFalse();
});
