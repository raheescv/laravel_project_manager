<?php

use App\Livewire\Report\Sale\FeedbackReport;
use Illuminate\Support\Facades\DB;
use Livewire\Livewire;
use Spatie\Permission\Models\Permission;
use Tests\Support\PosWorld;

/**
 * /report/sale_feedback — the ratings and comments customers left on sales.
 *
 * The pre-filled "compliment" type alone must not count as feedback: only a
 * rating or a comment makes a sale show up.
 */
beforeEach(function (): void {
    $this->world = PosWorld::create();
    $this->world->user->givePermissionTo(Permission::firstOrCreate([
        'tenant_id' => $this->world->tenant->id, 'name' => 'report.sale feedback', 'guard_name' => 'web',
    ]));
    $this->actingAs($this->world->user);
    session(['branch_id' => $this->world->branch->id]);
});

function insertFeedbackSale(PosWorld $world, string $invoiceNo, array $overrides = []): int
{
    return DB::table('sales')->insertGetId([
        'tenant_id' => $world->tenant->id,
        'invoice_no' => $invoiceNo,
        'branch_id' => $world->branch->id,
        'account_id' => $world->accounts['general_customer'],
        'date' => now()->toDateString(),
        'status' => 'completed',
        'gross_amount' => 50,
        'paid' => 50,
        'feedback_type' => 'compliment',
        'created_by' => $world->user->id,
        'created_at' => now(),
        'updated_at' => now(),
        ...$overrides,
    ]);
}

it('lists only sales with a rating or comment and summarises them', function (): void {
    insertFeedbackSale($this->world, 'INVFIVE', ['rating' => 5, 'feedback' => 'Lovely staff']);
    insertFeedbackSale($this->world, 'INVTWO', ['rating' => 2, 'feedback_type' => 'complaint', 'feedback' => 'Long queue']);
    insertFeedbackSale($this->world, 'INVIDEA', ['feedback_type' => 'suggestion', 'feedback' => 'Open earlier']);
    insertFeedbackSale($this->world, 'INVSILENT');
    insertFeedbackSale($this->world, 'INVCANCEL', ['rating' => 1, 'status' => 'cancelled']);

    $report = Livewire::test(FeedbackReport::class)
        ->assertOk()
        ->assertSee('INVFIVE')
        ->assertSee('Long queue')
        ->assertSee('INVIDEA')
        ->assertDontSee('INVSILENT')
        ->assertDontSee('INVCANCEL');

    $summary = $report->viewData('summary');

    expect($summary['sales'])->toBe(4)
        ->and($summary['responses'])->toBe(3)
        ->and($summary['rated'])->toBe(2)
        ->and($summary['average'])->toBe(3.5)
        ->and($summary['comments'])->toBe(3)
        ->and($summary['stars'])->toBe([5 => 1, 4 => 0, 3 => 0, 2 => 1, 1 => 0])
        ->and($summary['types'])->toBe(['compliment' => 1, 'suggestion' => 1, 'complaint' => 1]);
});

it('narrows by rating, type and search while the summary keeps the full picture', function (): void {
    insertFeedbackSale($this->world, 'INVFIVE', ['rating' => 5]);
    insertFeedbackSale($this->world, 'INVTWO', ['rating' => 2, 'feedback_type' => 'complaint', 'feedback' => 'Long queue']);

    Livewire::test(FeedbackReport::class)
        ->call('filterRating', 2)
        ->assertSee('INVTWO')
        ->assertDontSee('INVFIVE')
        ->assertViewHas('summary', fn ($summary) => $summary['responses'] === 2)
        ->call('filterRating', 2)
        ->assertSee('INVFIVE')
        ->set('feedback_type', 'complaint')
        ->assertDontSee('INVFIVE')
        ->set('feedback_type', '')
        ->set('search', 'queue')
        ->assertSee('INVTWO')
        ->assertDontSee('INVFIVE');
});

it('keeps sales outside the period off the report', function (): void {
    insertFeedbackSale($this->world, 'INVOLD', ['rating' => 4, 'date' => now()->subMonths(3)->toDateString()]);

    Livewire::test(FeedbackReport::class)->assertDontSee('INVOLD');
});

it('guards the page behind report.sale feedback', function (): void {
    $this->get(route('report::sale_feedback'))->assertOk()->assertSee('Sale Feedback');

    $this->world->user->revokePermissionTo('report.sale feedback');
    $this->get(route('report::sale_feedback'))->assertForbidden();
});

it('does not hand the POS a feedback report link', function (): void {
    $this->withoutMiddleware(\App\Http\Middleware\RequireOpenDaySession::class);
    $this->world->user->givePermissionTo(Permission::firstOrCreate([
        'tenant_id' => $this->world->tenant->id, 'name' => 'sale.create', 'guard_name' => 'web',
    ]));

    $this->get(route('sale::pos'))
        ->assertInertia(fn ($page) => $page->missing('feedbackReportUrl'));
});
