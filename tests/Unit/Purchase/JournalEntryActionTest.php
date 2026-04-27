<?php

namespace Tests\Unit\Purchase;

use App\Actions\Purchase\JournalEntryAction;
use Illuminate\Support\Facades\Cache;
use Mockery;
use Tests\TestCase;

class JournalEntryActionTest extends TestCase
{
    #[\PHPUnit\Framework\Attributes\Test]
    public function it_uses_unbilled_payables_for_lpo_purchase(): void
    {
        Cache::put('accounts_slug_id_map', [
            'inventory' => 1001,
            'unbilled_payables' => 1002,
            'tax_amount' => 1003,
            'discount' => 1004,
            'freight' => 1005,
        ]);

        $purchase = $this->makePurchase(localPurchaseOrderId: 33);

        $capturedData = null;
        $this->mockJournalCreateAction($capturedData);

        $result = (new JournalEntryAction())->execute($purchase, 1);

        $this->assertTrue($result['success']);
        $this->assertSame(1002, $capturedData['entries'][0]['account_id']);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function it_uses_inventory_for_non_lpo_purchase(): void
    {
        Cache::put('accounts_slug_id_map', [
            'inventory' => 1001,
            'unbilled_payables' => 1002,
            'tax_amount' => 1003,
            'discount' => 1004,
            'freight' => 1005,
        ]);

        $purchase = $this->makePurchase(localPurchaseOrderId: null);

        $capturedData = null;
        $this->mockJournalCreateAction($capturedData);

        $result = (new JournalEntryAction())->execute($purchase, 1);

        $this->assertTrue($result['success']);
        $this->assertSame(1001, $capturedData['entries'][0]['account_id']);
    }

    protected function mockJournalCreateAction(?array &$capturedData): void
    {
        $mock = Mockery::mock('overload:App\Actions\Journal\CreateAction');
        $mock->shouldReceive('execute')
            ->once()
            ->andReturnUsing(function (array $data) use (&$capturedData): array {
                $capturedData = $data;

                return [
                    'success' => true,
                    'message' => 'ok',
                ];
            });
    }

    protected function makePurchase(?int $localPurchaseOrderId): object
    {
        return (object) [
            'tenant_id' => 1,
            'date' => '2026-04-27',
            'branch_id' => 1,
            'invoice_no' => 'PUR-1',
            'reference_no' => 'REF-1',
            'id' => 1,
            'account_id' => 501,
            'local_purchase_order_id' => $localPurchaseOrderId,
            'gross_amount' => 1500,
            'tax_amount' => 0,
            'item_discount' => 0,
            'other_discount' => 0,
            'freight' => 0,
            'payments' => [],
            'account' => (object) [
                'name' => 'Test Vendor',
            ],
        ];
    }
}
