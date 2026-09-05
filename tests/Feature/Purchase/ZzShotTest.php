<?php

use App\Livewire\Purchase\Import;
use App\Models\AccountCategory;
use App\Models\Product;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Livewire\Livewire;
use Tests\Support\PosWorld;

it('dumps the ambiguous row', function (): void {
    $world = PosWorld::create();
    $this->actingAs($world->user);
    session(['branch_id' => $world->branch->id]);

    $category = AccountCategory::firstOrCreate(['tenant_id' => $world->tenant->id, 'name' => 'Sundry Creditors']);
    $vendorId = DB::table('accounts')->insertGetId([
        'tenant_id' => $world->tenant->id, 'account_category_id' => $category->id,
        'name' => 'NATURES SUPPLIER', 'slug' => 'natures', 'account_type' => 'liability',
        'model' => 'vendor', 'created_at' => now(), 'updated_at' => now(),
    ]);

    foreach ([
        ['24877-028', 'NATURES WHITENING PEARL 800', 813.56],
        ['24877-029', 'NATURES WHITENING PEARL 900', 338.98],
        ['24877-031', 'NATURES WHITENING PEARL 950', 474.58],
        ['24877-032', 'NATURES WHITENING PEARL 1100', 745.76],
        ['24877-033', 'NATURES WHITENING PEARL 1200', 813.56],
        ['24877-034', 'NATURES WHITENING PEARL 1300', 900.00],
        ['24877-035', 'NATURES WHITENING PEARL 1400', 1000.00],
    ] as [$code, $name, $cost]) {
        Product::create([
            'tenant_id' => $world->tenant->id, 'type' => 'product', 'name' => $name, 'code' => $code,
            'unit_id' => $world->product->unit_id, 'cost' => $cost,
            'created_by' => $world->user->id, 'updated_by' => $world->user->id,
        ]);
    }

    $csv = "Description,Qty,Rate,VAT %\nNATURES WHITENING PEARL,1,338.98,18\nNATURES WHITENING PEARL,1,400,18";

    $c = Livewire::test(Import::class)
        ->set('account_id', $vendorId)
        ->set('invoice_no', '24877')
        ->call('goToUpload')
        ->set('file', UploadedFile::fake()->createWithContent('24877.csv', $csv))
        ->call('buildRows');

    file_put_contents('/private/tmp/claude-501/-Users-Shared-sites-personal-main-projects-project-manager/c721dc4d-4a9b-4056-8cc7-a907f2d83289/scratchpad/pix-amb.body.html', $c->html());
    expect(true)->toBeTrue();
});
