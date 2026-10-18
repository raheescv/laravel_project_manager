<?php

use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Blade;
use OwenIt\Auditing\Models\Audit;

function sheetAudit(string $event, array $old, array $new, int $auditableId = 1): Audit
{
    $audit = new Audit([
        'event' => $event,
        'auditable_id' => $auditableId,
        'old_values' => $old,
        'new_values' => $new,
    ]);
    $audit->created_at = Carbon::parse('2026-10-04 12:04:47');
    $audit->setRelation('user', null);

    return $audit;
}

it('renders field names as column headers and each audit entry as a row', function (): void {
    $html = Blade::render('<x-audit.sheet :audits="$audits" />', ['audits' => collect([
        sheetAudit('created', [], ['status' => 'draft', 'grand_total' => 100, 'tenant_id' => 9]),
        sheetAudit('updated', ['status' => 'draft'], ['status' => 'completed']),
    ])]);

    expect($html)
        ->toContain('<th class="adx-pin">Entry</th>')
        ->toContain('>Status</th>')
        ->toContain('>Grand Total</th>')
        ->not->toContain('>Tenant Id</th>')
        ->toContain('<span class="adx-old" title="draft">draft</span>')
        ->toContain('<span class="adx-new" title="completed">completed</span>')
        ->toContain('04 Oct 2026 · 12:04 PM')
        ->toContain('System')
        ->and(substr_count($html, '<tr class="t-'))->toBe(2);
});

it('shows the record column only when asked', function (): void {
    $audits = collect([sheetAudit('created', [], ['quantity' => 2], 42)]);

    expect(Blade::render('<x-audit.sheet :audits="$audits" :showRecord="true" recordLabel="Item" />', ['audits' => $audits]))
        ->toContain('<th class="adx-rec">Item</th>')
        ->toContain('#42');

    expect(Blade::render('<x-audit.sheet :audits="$audits" />', ['audits' => $audits]))
        ->not->toContain('<th class="adx-rec">');
});

it('renders the empty message when there are no audits', function (): void {
    expect(Blade::render('<x-audit.sheet :audits="$audits" emptyMessage="Nothing logged." />', ['audits' => collect()]))
        ->toContain('Nothing logged.')
        ->not->toContain('<table');
});
