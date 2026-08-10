<?php

namespace Tests\Support;

use App\Models\AccountCategory;
use App\Models\Branch;
use App\Models\Configuration;
use App\Models\Product;
use App\Models\Tenant;
use App\Models\User;
use App\Services\TenantService;
use App\Support\TenantCache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * The minimum world a POS sale needs: a tenant with a branch, a cashier, a
 * chart of system accounts, one configured payment method, and a sellable
 * product with stock.
 *
 * `POST /api/v1/sale` reaches deep into the domain — it resolves a customer,
 * moves inventory and posts a double-entry journal — so a test that only
 * creates a product gets a 500 from three layers down. Everything assembled
 * here is something the endpoint genuinely requires.
 */
class PosWorld
{
    public Tenant $tenant;

    public Branch $branch;

    public User $user;

    public Product $product;

    /** Account id of the configured "Cash" payment method. */
    public int $cashAccountId;

    /** System accounts by slug, as inserted. */
    public array $accounts = [];

    /**
     * Build the world and make it the current tenant.
     *
     * @param  float  $stock  On-hand quantity for [product] at the branch.
     * @param  float  $price  The product's MRP.
     * @param  float  $tax  Tax percentage on the product.
     */
    public static function create(float $stock = 100, float $price = 50, float $tax = 0): self
    {
        $world = new self();

        $world->tenant = Tenant::factory()->create();

        // Set before anything else is written: BelongsToTenant fills tenant_id
        // from the resolved tenant, and TenantCache refuses to cache without
        // one. Nothing below would be scoped correctly otherwise.
        app(TenantService::class)->setCurrentTenant($world->tenant);

        $world->branch = Branch::create([
            'tenant_id' => $world->tenant->id,
            'name' => 'Main Branch',
            'code' => 'MB',
        ]);

        $world->user = User::factory()->create([
            'tenant_id' => $world->tenant->id,
            'default_branch_id' => $world->branch->id,
            'is_admin' => 1,
        ]);

        $world->seedAccounts();
        $world->seedProduct($stock, $price, $tax);

        return $world;
    }

    /**
     * The system accounts the sale journal posts against, plus the receivable
     * category the customer resolver needs.
     *
     * Inserted through the query builder rather than the model because `slug`
     * and `is_locked` are deliberately not fillable — the same reason
     * AccountSeeder writes them this way.
     */
    private function seedAccounts(): void
    {
        AccountCategory::firstOrCreate(['tenant_id' => $this->tenant->id, 'name' => 'Account Receivable']);
        $category = AccountCategory::firstOrCreate(['tenant_id' => $this->tenant->id, 'name' => 'System']);

        $definitions = [
            ['slug' => 'cash', 'name' => 'Cash', 'account_type' => 'asset'],
            ['slug' => 'card', 'name' => 'Card', 'account_type' => 'asset'],
            ['slug' => 'general_customer', 'name' => 'General Customer', 'account_type' => 'asset', 'model' => 'customer'],
            ['slug' => 'inventory', 'name' => 'Inventory', 'account_type' => 'asset'],
            ['slug' => 'sale', 'name' => 'Sale', 'account_type' => 'income'],
            ['slug' => 'sales_returns', 'name' => 'Sales Returns', 'account_type' => 'expense'],
            ['slug' => 'cost_of_goods_sold', 'name' => 'Cost of Goods Sold', 'account_type' => 'expense'],
            ['slug' => 'discount', 'name' => 'Discount', 'account_type' => 'expense'],
            ['slug' => 'tax_amount', 'name' => 'Tax Amount', 'account_type' => 'liability'],
            ['slug' => 'round_off', 'name' => 'Round Off', 'account_type' => 'income'],
            ['slug' => 'freight', 'name' => 'Freight', 'account_type' => 'expense'],
        ];

        foreach ($definitions as $definition) {
            $id = DB::table('accounts')->insertGetId($definition + [
                'tenant_id' => $this->tenant->id,
                'account_category_id' => $category->id,
                'is_locked' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
            $this->accounts[$definition['slug']] = $id;
        }

        $this->cashAccountId = $this->accounts['cash'];

        // The V1 action only accepts payment methods listed in this config key
        // (see Account::scopePaymentMethod / tenant_cache('payment_methods')).
        Configuration::create([
            'tenant_id' => $this->tenant->id,
            'key' => 'payment_methods',
            'value' => json_encode([$this->cashAccountId]),
        ]);

        // Written after the accounts and config exist, so nothing has cached an
        // empty chart of accounts from an earlier read in this process.
        TenantCache::forget('accounts_slug_id_map');
        TenantCache::forget('payment_methods');
    }

    /**
     * A sellable product with stock at the branch.
     *
     * `inventories.barcode` is a GENERATED column (`barcode_prefix` concatenated
     * with `barcode_number`); writing to it directly is MySQL error 3105, which
     * is what breaks StockAnalysisReportTest. The two source columns are set
     * instead.
     */
    private function seedProduct(float $stock, float $price, float $tax): void
    {
        $unitId = DB::table('units')->insertGetId([
            'tenant_id' => $this->tenant->id, 'name' => 'Piece', 'code' => 'PC',
        ]);
        $departmentId = DB::table('departments')->insertGetId([
            'tenant_id' => $this->tenant->id, 'name' => 'General',
        ]);
        $categoryId = DB::table('categories')->insertGetId([
            'tenant_id' => $this->tenant->id, 'name' => 'General',
        ]);

        $this->product = Product::create([
            'tenant_id' => $this->tenant->id,
            'type' => 'product',
            'name' => 'Test Product '.Str::random(6),
            'code' => 'P'.Str::upper(Str::random(6)),
            'unit_id' => $unitId,
            'department_id' => $departmentId,
            'main_category_id' => $categoryId,
            'mrp' => $price,
            'tax' => $tax,
            'created_by' => $this->user->id,
            'updated_by' => $this->user->id,
        ]);

        DB::table('inventories')->insert([
            'tenant_id' => $this->tenant->id,
            'branch_id' => $this->branch->id,
            'product_id' => $this->product->id,
            'quantity' => $stock,
            'batch' => (string) Str::uuid(),
            'cost' => 10,
            'barcode_prefix' => '',
            'barcode_number' => (string) random_int(10000000, 99999999),
            'created_by' => $this->user->id,
            'updated_by' => $this->user->id,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    /**
     * Configure one more payment method and return its account id.
     *
     * For tests about method resolution: a real business has several accounts
     * whose names share a word ("Visa Card", "Mastercard", "Petty Cash"), which
     * is exactly when resolving a payment by NAME becomes ambiguous.
     */
    public function addPaymentMethod(string $name): int
    {
        $id = DB::table('accounts')->insertGetId([
            'tenant_id' => $this->tenant->id,
            'account_category_id' => AccountCategory::where('tenant_id', $this->tenant->id)->where('name', 'System')->value('id'),
            'name' => $name,
            'slug' => Str::slug($name, '_'),
            'account_type' => 'asset',
            'is_locked' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $configuration = Configuration::where('tenant_id', $this->tenant->id)->where('key', 'payment_methods')->first();
        $configuration->update([
            'value' => json_encode([...json_decode($configuration->value, true), $id]),
        ]);

        TenantCache::forget('payment_methods');
        TenantCache::forget('accounts_slug_id_map');

        return $id;
    }

    /**
     * A second branch on this tenant, stocked with the same product, for tests
     * about branch attribution.
     */
    public function addBranch(string $name = 'Second Branch', string $code = 'SB'): Branch
    {
        $branch = Branch::create([
            'tenant_id' => $this->tenant->id,
            'name' => $name,
            'code' => $code,
        ]);

        DB::table('inventories')->insert([
            'tenant_id' => $this->tenant->id,
            'branch_id' => $branch->id,
            'product_id' => $this->product->id,
            'quantity' => 100,
            'batch' => (string) Str::uuid(),
            'cost' => 10,
            'barcode_prefix' => '',
            'barcode_number' => (string) random_int(10000000, 99999999),
            'created_by' => $this->user->id,
            'updated_by' => $this->user->id,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return $branch;
    }

    /**
     * The Host this world's requests must be made against.
     *
     * `IdentifyTenant` runs on the API routes and resolves the tenant from the
     * host, overwriting anything the test set on TenantService. The default test
     * host comes from APP_URL (`project_manager.test`), which parses as a
     * subdomain and resolves whatever tenant happens to own it — not the one the
     * test just built. Pointing the Host at this tenant's own subdomain makes the
     * real middleware resolve the real tenant, which is also what the app does.
     *
     * Note that `withHeader('Host', …)` does NOT work: Laravel builds the test
     * request from APP_URL and that wins. Use [url] instead, which passes an
     * absolute URL so the host actually takes effect.
     */
    public function host(): string
    {
        return $this->tenant->subdomain.'.localhost';
    }

    /** An absolute URL for [path] on this tenant's host. */
    public function url(string $path): string
    {
        return 'http://'.$this->host().'/'.ltrim($path, '/');
    }

    /**
     * A valid POST /api/v1/sale body for this world. [$overrides] is merged last
     * so a test can change one field without restating the payload.
     */
    public function salePayload(array $overrides = []): array
    {
        return array_merge([
            'customerName' => 'Walk-in Customer',
            'items' => [[
                'productId' => $this->product->id,
                'quantity' => 1,
                'unitPrice' => (float) $this->product->mrp,
                'discount' => 0,
            ]],
            'discount' => 0,
            'tip' => 0,
            'paymentMethod' => 'Cash',
            'totalPayment' => (float) $this->product->mrp,
        ], $overrides);
    }
}
