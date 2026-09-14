<?php

use App\Actions\Sale\BuildDaySessionReportAction;
use App\Actions\V1\DaySession\ReportAction;
use App\Models\SaleDaySession;
use Laravel\Sanctum\Sanctum;
use Spatie\Permission\Models\Permission;
use Tests\Support\PosWorld;

/**
 * The mobile app prints the web "Sale Bill Report" for a day session: it lists
 * the operating branch's sessions to pick from, reads one session's figures to
 * lay out a thermal roll, and downloads the web A4 view as a PDF. All three sit
 * behind the web print routes' own `day session.print`.
 */
beforeEach(function (): void {
    $this->world = PosWorld::create();
    Sanctum::actingAs($this->world->user);

    $this->grantPrint = fn () => $this->world->user->givePermissionTo(
        Permission::firstOrCreate([
            'tenant_id' => $this->world->tenant->id,
            'name' => 'day session.print',
            'guard_name' => 'web',
        ])
    );

    $this->makeSession = fn (array $attributes = []) => SaleDaySession::create(array_merge([
        'tenant_id' => $this->world->tenant->id,
        'branch_id' => $this->world->branch->id,
        'opened_by' => $this->world->user->id,
        'opened_at' => now()->subHours(8),
        'closed_by' => $this->world->user->id,
        'closed_at' => now()->subHour(),
        'status' => 'closed',
    ], $attributes));
});

it('refuses every day session report endpoint without day session.print', function (): void {
    $session = ($this->makeSession)();

    $this->getJson($this->world->url('/api/v1/admin/day-sessions'))->assertForbidden();
    $this->getJson($this->world->url("/api/v1/admin/day-sessions/{$session->id}/report"))->assertForbidden();
    $this->get($this->world->url("/api/v1/admin/day-sessions/{$session->id}/report/pdf"))->assertForbidden();
});

it('lists the operating branch sessions newest first', function (): void {
    ($this->grantPrint)();
    $older = ($this->makeSession)(['opened_at' => now()->subDays(2), 'closed_at' => now()->subDays(2)->addHours(8)]);
    $newer = ($this->makeSession)(['opened_at' => now()->subHours(3), 'closed_at' => null, 'closed_by' => null, 'status' => 'open']);
    ($this->makeSession)(['branch_id' => $this->world->addBranch()->id]);

    $this->getJson($this->world->url('/api/v1/admin/day-sessions'))
        ->assertSuccessful()
        ->assertJsonPath('data.pagination.total', 2)
        ->assertJsonPath('data.data.0.id', (string) $newer->id)
        ->assertJsonPath('data.data.0.status', 'open')
        ->assertJsonPath('data.data.0.closed_at', null)
        ->assertJsonPath('data.data.1.id', (string) $older->id)
        ->assertJsonPath('data.data.1.opened_at', $older->opened_at->format('Y-m-d H:i:s'));
});

it('returns the figures the thermal report prints', function (): void {
    ($this->grantPrint)();
    $session = ($this->makeSession)();

    $this->getJson($this->world->url("/api/v1/admin/day-sessions/{$session->id}/report"))
        ->assertSuccessful()
        ->assertJsonPath('data.session.id', (string) $session->id)
        ->assertJsonPath('data.session.branch', $this->world->branch->name)
        ->assertJsonPath('data.session.status', 'closed')
        ->assertJsonPath('data.transactions', [])
        ->assertJsonStructure(['data' => [
            'due_transactions',
            'due_payments',
            'totals' => [
                'credit', 'cash', 'card', 'sale_tailoring_amount', 'payment_total',
                'due_total_cash', 'due_total_card', 'due_total',
                'card_with_due', 'cash_with_due', 'grand_total_payment',
            ],
        ]]);
});

it('answers 404 for a session that does not exist', function (): void {
    ($this->grantPrint)();

    $this->getJson($this->world->url('/api/v1/admin/day-sessions/999999/report'))->assertNotFound();
});

it('renders the web A4 view the PDF is made from', function (): void {
    $session = ($this->makeSession)();

    $html = app(BuildDaySessionReportAction::class)->executePdf($session->load(['branch', 'opener', 'closer']))->render();

    expect($html)->toContain('SALE BILL REPORT');
});

it('downloads the A4 report as a PDF', function (): void {
    ($this->grantPrint)();
    $session = ($this->makeSession)();

    // Chrome isn't part of the test run; what it renders is the view above.
    $this->mock(ReportAction::class, function ($mock) use ($session): void {
        $mock->shouldReceive('find')->andReturn($session);
        $mock->shouldReceive('pdf')->andReturn('%PDF-1.7 day session');
    });

    $this->get($this->world->url("/api/v1/admin/day-sessions/{$session->id}/report/pdf"))
        ->assertOk()
        ->assertHeader('Content-Type', 'application/pdf')
        ->assertContent('%PDF-1.7 day session');
});
