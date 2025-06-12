<?php

namespace App\Http\Controllers\Sale;

use App\Actions\Sale\CreateAction;
use App\Actions\Sale\Item\DeleteAction as ItemDeleteAction;
use App\Actions\Sale\UpdateAction;
use App\Helpers\Facades\SaleHelper;
use App\Helpers\Facades\WhatsappHelper;
use App\Http\Controllers\Controller;
use App\Models\Account;
use App\Models\Configuration;
use App\Models\Inventory;
use App\Models\Sale;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redis;
use Inertia\Inertia;

class PageController extends Controller
{
    protected const CACHE_PREFIX = 'sale_page_';

    protected const CACHE_TTL = 3600; // 1 hour

    protected const CACHE_TTL_SHORT = 300; // 5 minutes

    protected const CACHE_TTL_VERY_SHORT = 60; // 1 minute

    public function index(Request $request, $table_id = null)
    {
        $initialData = $this->loadInitialData();

        if ($table_id) {
            $saleData = $this->loadSaleData($table_id);
            if ($saleData === null) {
                return redirect()->route('sale::index');
            }
        } else {
            $saleData = $this->initializeNewSale();
        }

        return Inertia::render('Sale/Page', [
            'initialData' => $initialData,
            'saleData' => $saleData,
            'categories' => $this->getCategories(),
            'customerDetails' => $this->getCustomerDetails($saleData['account_id'] ?? null),
        ]);
    }

    protected function loadInitialData()
    {
        $data = Redis::pipeline(function ($pipe) {
            $pipe->get(self::CACHE_PREFIX.'payment_methods');
            $pipe->get(self::CACHE_PREFIX.'employees');
            $pipe->get(self::CACHE_PREFIX.'default_payment_method_id');
        });

        return [
            'paymentMethods' => $this->getCachedData(
                'payment_methods',
                self::CACHE_TTL,
                fn () => Account::select('id', 'name')
                    ->where('id', $this->getDefaultPaymentMethodId())
                    ->pluck('name', 'id')
                    ->toArray(),
                ['accounts']
            ),
            'employees' => $this->getCachedData(
                'employees',
                self::CACHE_TTL,
                fn () => User::employee()
                    ->select('id', 'name')
                    ->pluck('name', 'id')
                    ->toArray(),
                ['users']
            ),
            'defaultPaymentMethodId' => $this->getDefaultPaymentMethodId(),
        ];
    }

    protected function loadSaleData($table_id)
    {
        $sale = $this->getCachedData(
            "sale_{$table_id}",
            self::CACHE_TTL_SHORT,
            fn () => Sale::with([
                'account:id,name,mobile',
                'branch:id,name',
                'items' => function ($query) {
                    $query->select([
                        'id', 'employee_id', 'assistant_id', 'inventory_id',
                        'product_id', 'sale_combo_offer_id', 'name', 'employee_name',
                        'assistant_name', 'tax_amount', 'unit_price', 'quantity',
                        'gross_amount', 'discount', 'tax', 'total', 'effective_total',
                        'created_by',
                    ])->with([
                        'product:id,name,mrp',
                        'employee:id,name',
                        'assistant:id,name',
                    ]);
                },
                'comboOffers.comboOffer:id,name',
                'createdUser:id,name',
                'updatedUser:id,name',
                'cancelledUser:id,name',
                'payments.paymentMethod:id,name',
            ])->find($table_id),
            ['sales']
        );

        if (! $sale) {
            return;
        }

        return $this->processSaleData($sale);
    }

    protected function initializeNewSale()
    {
        return [
            'date' => now()->format('Y-m-d'),
            'due_date' => now()->format('Y-m-d'),
            'sale_type' => 'normal',
            'account_id' => 3,
            'customer_name' => '',
            'customer_mobile' => '',
            'gross_amount' => 0,
            'total_quantity' => 0,
            'item_discount' => 0,
            'tax_amount' => 0,
            'total' => 0,
            'other_discount' => 0,
            'freight' => 0,
            'grand_total' => 0,
            'paid' => 0,
            'balance' => 0,
            'address' => null,
            'rating' => 0,
            'feedback_type' => 'compliment',
            'feedback' => null,
            'status' => 'draft',
            'items' => [],
            'payments' => [],
            'comboOffers' => [],
        ];
    }

    public function addItem(Request $request)
    {
        $inventory = Inventory::with(['product:id,name,mrp'])->find($request->inventory_id);

        if (! $inventory) {
            return response()->json(['error' => 'Product not found'], 404);
        }

        $item = $this->createItemData($inventory, $request->employee_id);

        return response()->json([
            'item' => $item,
            'totals' => $this->calculateTotals([$item]),
        ]);
    }

    public function updateItem(Request $request)
    {
        $item = $this->updateItemData($request->all());

        return response()->json([
            'item' => $item,
            'totals' => $this->calculateTotals([$item]),
        ]);
    }

    public function removeItem(Request $request)
    {
        try {
            if ($request->id) {
                $response = (new ItemDeleteAction())->execute($request->id);
                if (! $response['success']) {
                    throw new \Exception($response['message']);
                }
            }

            return response()->json(['success' => true]);
        } catch (\Throwable $th) {
            return response()->json(['error' => $th->getMessage()], 500);
        }
    }

    public function save(Request $request)
    {
        $request->validate([
            'account_id' => 'required',
            'date' => 'required',
            'sale_type' => 'required',
        ]);

        try {
            DB::beginTransaction();

            if (empty($request->items)) {
                throw new \Exception('Please add any item');
            }

            $saleData = $this->prepareSaleData($request->all());

            if ($saleData['balance'] < 0) {
                throw new \Exception('Please check the payment');
            }

            $this->clearCaches(['sales', 'accounts', 'ledgers']);

            $user_id = Auth::id();
            $response = $request->table_id
                ? (new UpdateAction())->execute($saleData, $request->table_id, $user_id)
                : (new CreateAction())->execute($saleData, $user_id);

            if (! $response['success']) {
                throw new \Exception($response['message']);
            }

            DB::commit();

            $this->handlePostSaveOperations($response['data']['id'], $request->type, $request->print);

            return response()->json([
                'success' => true,
                'message' => $response['message'],
                'sale_id' => $response['data']['id'],
            ]);

        } catch (\Throwable $th) {
            DB::rollback();

            return response()->json(['error' => $th->getMessage()], 500);
        }
    }

    public function sendWhatsapp(Request $request)
    {
        try {
            $sale = Sale::find($request->table_id);
            $number = $sale['customer_mobile'] ?: $sale->account->mobile;

            if (! $number) {
                throw new \Exception('Invalid Number');
            }

            $imageContent = SaleHelper::saleInvoice($request->table_id, 'thermal');
            $image_path = SaleHelper::convertHtmlToImage($imageContent, $sale->invoice_no);

            $response = WhatsappHelper::send([
                'number' => $number,
                'message' => 'Please Check Your Invoice : '.currency($sale->grand_total),
                'filePath' => $image_path,
            ]);

            return response()->json($response);

        } catch (\Throwable $th) {
            return response()->json(['error' => $th->getMessage()], 500);
        }
    }

    // Protected helper methods...

    protected function getCachedData($key, $ttl, $callback, $tags = [])
    {
        $cacheKey = self::CACHE_PREFIX.$key;

        $serialized = Redis::get($cacheKey);
        if ($serialized) {
            return unserialize($serialized);
        }

        if ($this->cacheSupportsTagging()) {
            return Cache::tags($tags)->remember($cacheKey, $ttl, $callback);
        }

        return Cache::remember($cacheKey, $ttl, $callback);
    }

    protected function cacheSupportsTagging()
    {
        $store = Cache::getStore();

        return method_exists($store, 'tags') && (
            $store instanceof \Illuminate\Cache\RedisStore ||
            $store instanceof \Illuminate\Cache\MemcachedStore
        );
    }

    protected function clearCaches($tags = [])
    {
        if ($this->cacheSupportsTagging() && ! empty($tags)) {
            Cache::tags($tags)->flush();
        } else {
            $this->clearSpecificCacheKeys($tags);
        }
    }

    protected function clearSpecificCacheKeys($tags = [])
    {
        $keysToFlush = [
            self::CACHE_PREFIX.'payment_methods',
            self::CACHE_PREFIX.'employees',
            self::CACHE_PREFIX.'default_payment_method_id',
            self::CACHE_PREFIX.'accounts_default',
            self::CACHE_PREFIX.'categories_with_products',
        ];

        foreach ($keysToFlush as $key) {
            Cache::forget($key);
        }
    }

    protected function getDefaultPaymentMethodId()
    {
        return $this->getCachedData(
            'default_payment_method_id',
            self::CACHE_TTL,
            fn () => Configuration::where('key', 'default_payment_method_id')->value('value') ?? 1,
            ['configurations']
        );
    }

    protected function calculateTotals($items)
    {
        return collect($items)->reduce(function ($carry, $item) {
            $carry['gross_amount'] += $item['gross_amount'];
            $carry['total_quantity'] += $item['quantity'];
            $carry['item_discount'] += $item['discount'];
            $carry['tax_amount'] += $item['tax_amount'];
            $carry['total'] += $item['total'];

            return $carry;
        }, [
            'gross_amount' => 0,
            'total_quantity' => 0,
            'item_discount' => 0,
            'tax_amount' => 0,
            'total' => 0,
        ]);
    }

    protected function handlePostSaveOperations($table_id, $type, $print)
    {
        if ($type === 'completed' && $print) {
            // Handle printing logic
        }
    }

    protected function getCategories()
    {
        return $this->getCachedData(
            'categories_with_products',
            self::CACHE_TTL,
            fn () => \App\Models\Category::withCount('products')
                ->having('products_count', '>', 0)
                ->orderBy('name')
                ->get()
                ->toArray(),
            ['categories']
        );
    }

    protected function getCustomerDetails($account_id = null)
    {
        if (! $account_id) {
            return;
        }

        return $this->getCachedData(
            "account_balance_{$account_id}",
            self::CACHE_TTL_VERY_SHORT,
            fn () => Account::find($account_id)
                ->ledger()
                ->latest('id')
                ->value('balance'),
            ['accounts', 'ledgers']
        );
    }

    protected function processSaleData($sale)
    {
        $saleData = $sale->toArray();

        // Process items
        $saleData['items'] = collect($sale->items)->mapWithKeys(function ($item) {
            $key = $item['employee_id'].'-'.$item['inventory_id'];

            return [$key => $this->formatItem($item)];
        })->toArray();

        // Process combo offers
        $saleData['comboOffers'] = collect($sale->comboOffers)->map(function ($package) use ($saleData) {
            $items = collect($saleData['items'])
                ->filter(fn ($item) => $item['sale_combo_offer_id'] == $package['id'])
                ->map(fn ($item) => array_merge($item, [
                    'combo_offer_price' => $item['unit_price'] - $item['discount'],
                ]))
                ->toArray();

            return [
                'id' => $package['id'],
                'combo_offer_id' => $package['combo_offer_id'],
                'amount' => $package['amount'],
                'combo_offer_name' => $package->comboOffer?->name,
                'items' => $items,
            ];
        })->toArray();

        // Process payments
        $saleData['payments'] = $sale->payments->map(function ($payment) {
            return [
                'id' => $payment->id,
                'amount' => $payment->amount,
                'payment_method_id' => $payment->payment_method_id,
                'name' => $payment->paymentMethod->name,
            ];
        })->toArray();

        // Calculate totals
        $totals = $this->calculateTotals($saleData['items']);
        $saleData = array_merge($saleData, $totals);

        // Calculate grand total
        $saleData['grand_total'] = $this->calculateGrandTotal($saleData['total']);
        $saleData['paid'] = collect($saleData['payments'])->sum('amount');
        $saleData['balance'] = $saleData['grand_total'] - $saleData['paid'];

        return $saleData;
    }

    protected function formatItem($item)
    {
        return [
            'id' => $item['id'],
            'key' => $item['employee_id'].'-'.$item['inventory_id'],
            'employee_id' => $item['employee_id'],
            'assistant_id' => $item['assistant_id'],
            'inventory_id' => $item['inventory_id'],
            'product_id' => $item['product_id'],
            'sale_combo_offer_id' => $item['sale_combo_offer_id'],
            'name' => $item['name'],
            'employee_name' => $item['employee_name'],
            'assistant_name' => $item['assistant_name'],
            'tax_amount' => $item['tax_amount'],
            'unit_price' => $item['unit_price'],
            'quantity' => round($item['quantity'], 3),
            'gross_amount' => $item['gross_amount'],
            'discount' => $item['discount'],
            'tax' => $item['tax'],
            'total' => $item['total'],
            'effective_total' => $item['effective_total'],
            'created_by' => $item['created_by'],
        ];
    }

    protected function calculateGrandTotal($total)
    {
        $grandTotal = $total;

        // Note: other_discount and freight would need to be passed in or retrieved from somewhere
        // For now, we'll just return the total
        return round($grandTotal, 2);
    }

    protected function createItemData($inventory, $employee_id)
    {
        $product = $inventory->product;
        $employee = User::find($employee_id);

        if (! $employee) {
            throw new \Exception('Employee not found');
        }

        $saleTypePrice = $product->saleTypePrice('normal'); // Default to normal sale type
        $discount = $product->mrp - $saleTypePrice;
        $key = $employee_id.'-'.$inventory->id;

        return [
            'key' => $key,
            'inventory_id' => $inventory->id,
            'barcode' => $inventory->barcode,
            'employee_id' => $employee_id,
            'employee_name' => $employee->name,
            'assistant_name' => '',
            'product_id' => $product->id,
            'name' => $product->name,
            'unit_price' => $discount > 0 ? $product->mrp : $saleTypePrice,
            'discount' => $discount > 0 ? $discount : 0,
            'quantity' => 1,
            'tax' => 0,
            'gross_amount' => $discount > 0 ? $product->mrp : $saleTypePrice,
            'tax_amount' => 0,
            'total' => $discount > 0 ? $product->mrp : $saleTypePrice,
            'effective_total' => $discount > 0 ? $product->mrp : $saleTypePrice,
        ];
    }

    protected function updateItemData($data)
    {
        // This method would handle updating item data
        // For now, returning the data as is
        return $data;
    }

    protected function prepareSaleData($data)
    {
        // This method prepares the sale data for saving
        // For now, returning the data as is
        return $data;
    }
}
