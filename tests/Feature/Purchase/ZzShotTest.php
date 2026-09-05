<?php

use App\Livewire\Purchase\Import;
use App\Models\AccountCategory;
use App\Models\Product;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Livewire\Livewire;
use Spatie\Permission\Models\Permission;
use Tests\Support\PosWorld;

it('dumps the review grid', function (): void {
    $world = PosWorld::create();
    foreach (['purchase.create', 'purchase.import'] as $name) {
        $world->user->givePermissionTo(Permission::firstOrCreate(['tenant_id' => $world->tenant->id, 'name' => $name, 'guard_name' => 'web']));
    }
    $this->actingAs($world->user);
    session(['branch_id' => $world->branch->id]);

    $category = AccountCategory::firstOrCreate(['tenant_id' => $world->tenant->id, 'name' => 'Sundry Creditors']);
    $vendorId = DB::table('accounts')->insertGetId([
        'tenant_id' => $world->tenant->id, 'account_category_id' => $category->id,
        'name' => 'MAKEIDA MIDAS LLP', 'slug' => 'makeida', 'account_type' => 'liability',
        'model' => 'vendor', 'created_at' => now(), 'updated_at' => now(),
    ]);

    foreach ([
        ['HYD-001', 'HYDRA FACIAL 10 IN 1 WELLNESS MACHINE', 21186.44],
        ['CHR-001', 'CUTTING CHAIR ALPHA BLACK LEATHER', 3100.00],
        ['26288-003', 'LEEPOSH HYDRA FACIAL SERUM 30ML', 700],
        ['26288-004', 'LEEPOSH HYDRA FACIAL SERUM 50ML', 889.83],
        ['26288-005', 'LEEPOSH HYDRA FACIAL SERUM 100ML', 1200],
        ['TIS-001', 'MAKEIDA TISSUE', 22.03],
    ] as [$code, $name, $cost]) {
        Product::create([
            'tenant_id' => $world->tenant->id, 'type' => 'product', 'name' => $name, 'code' => $code,
            'unit_id' => $world->product->unit_id, 'cost' => $cost,
            'created_by' => $world->user->id, 'updated_by' => $world->user->id,
        ]);
    }

    $csv = implode("\n", [
        'Description,HSN,Qty,Rate,Disc,VAT %',
        'HYDRA FACIAL 10 IN 1 WEL,85437093,1,21186.44,0,18',
        'CUTTING CHAIR ALPHA BLAC,82123542,2,3305.08,0,18',
        'LEEPOSH HYDRA FACIAL SER,33049910,3,889.83,0,18',
        'FIXIN MICRO FIBER TOWE 6,63071090,10,209.52,0,5',
        'MAKEIDA TISSUE,48182000,60,22.03,0,18',
    ]);

    $c = Livewire::test(Import::class)
        ->set('account_id', $vendorId)
        ->set('invoice_no', '26288')
        ->call('goToUpload')
        ->set('file', UploadedFile::fake()->createWithContent('26288.csv', $csv))
        ->call('buildRows')
        ->set('rowFilter', 'all');

    file_put_contents('/private/tmp/claude-501/-Users-Shared-sites-personal-main-projects-project-manager/c721dc4d-4a9b-4056-8cc7-a907f2d83289/scratchpad/pix-review.body.html', $c->html());
    expect(true)->toBeTrue();
});
