<?php

namespace App\Console\Commands;

use App\Actions\Purchase\CreateAction as PurchaseCreateAction;
use App\Actions\Sale\CreateAction as SaleCreateAction;
use App\Jobs\BranchProductCreationJob;
use App\Models\Account;
use App\Models\Inventory;
use App\Models\Product;
use App\Models\User;
use App\Models\UserHasBranch;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Spatie\Permission\Models\Role;

class MigrateDataCommand extends Command
{
    protected $signature = 'migrate:database-data';

    protected $description = 'Migrate data from mysql2 to mysql database';

    public function handle()
    {
        $this->info('Starting data migration...');

        $this->accounts();
        $this->customer();
        $this->vendor();
        $this->service();
        $this->products();
        $this->employees();
        $this->users();
        $this->sales();
        $this->purchases();

        $this->info('Data migration completed successfully!');
    }

    private function accounts()
    {
        $account_heads = DB::connection('mysql2')
            ->table('account_heads')
            ->whereIn('id', [1, 16, 94, 336])
            ->get();
        foreach ($account_heads as $value) {
            $name = ucfirst(strtolower($value->name));
            $data = [
                'second_reference_no' => $value->id,
                'account_type' => 'asset',
                'name' => $name,
            ];
            Account::updateOrCreate([
                    'account_type' => $data['account_type'],
                    'name' => $data['name'],
                ], $data
            );
        }
    }

    private function purchases()
    {
        $this->info('Migrating purchases...');

        // Get total count for progress bar
        $totalPurchases = DB::connection('mysql2')
            ->table('purchases')
            ->whereNull('deleted_at')
            ->count();

        $progressBar = $this->output->createProgressBar($totalPurchases);
        $progressBar->start();

        DB::connection('mysql2')
            ->table('purchases')
            ->whereNull('purchases.deleted_at')
            ->orderBy('purchases.id')
            ->chunk(100, function ($purchases) use ($progressBar) {
                foreach ($purchases as $purchase) {
                    $progressBar->advance();
                    try {
                        DB::transaction(function () use ($purchase) {
                            $account = Account::where('second_reference_no', $purchase->vendor_id)->first();
                            $created_by = User::where('type', 'user')->where('second_reference_no', $purchase->created_by)->value('id');
                            $updated_by = User::where('type', 'user')->where('second_reference_no', $purchase->updated_by)->value('id');
                            $data = [
                                'branch_id' => 1,
                                'date' => $purchase->date,
                                'delivery_date' => $purchase->delivery_date,
                                'invoice_no' => $purchase->invoice_no,
                                'account_id' => $account->id,
                                'other_discount' => $purchase->other_discount,
                                'freight' => 0,
                                'status' => 'completed',
                                'created_by' => $created_by,
                                'updated_by' => $updated_by,
                            ];
                            $data['items'] = [];
                            $purchase_items = DB::connection('mysql2')
                                ->table('purchase_items')
                                ->whereNull('deleted_at')
                                ->where('purchase_id', $purchase->id)
                                ->get();
                            foreach ($purchase_items as $value) {
                                $product_id = Product::where('type', 'product')->where('second_reference_no', $value->product_id)->value('id');
                                $inventory_id = Inventory::where('product_id', $product_id)->value('id');
                                $item = [
                                    'inventory_id' => $inventory_id,
                                    'product_id' => $product_id,
                                    'batch' => $value->batch,
                                    'unit_price' => $value->unit_price,
                                    'net_amount' => $value->unit_price * $value->quantity,
                                    'quantity' => $value->quantity,
                                    'discount' => $value->discount,
                                    'tax' => 0,
                                    'total' => ($value->unit_price * $value->quantity) - $value->discount,
                                ];
                                $data['items'][] = $item;
                            }
                            $data['items'] = collect($data['items']);
                            $data['gross_amount'] = $data['items']->sum('net_amount');
                            $data['item_discount'] = $data['items']->sum('discount');
                            $data['total_quantity'] = $data['items']->sum('quantity');
                            $data['total'] = $data['items']->sum('total');
                            $journals = DB::connection('mysql2')
                                ->table('journals')
                                ->whereNull('deleted_at')
                                ->where('purchase_id', $purchase->id)
                                ->whereIn('credit', [1, 16, 94, 336])
                                ->get();
                            $data['payments'] = [];
                            foreach ($journals as $value) {
                                $account_id = Account::where('second_reference_no', $value->credit)->value('id');
                                $journal = [
                                    'payment_method_id' => $account_id,
                                    'amount' => $value->amount,
                                ];
                                $data['payments'][] = $journal;
                            }
                            $data['payments'] = collect($data['payments']);
                            $data['paid'] = $data['payments']->sum('amount');
                            $response = (new PurchaseCreateAction())->execute($data, 1);
                            if (! $response['success']) {
                                $this->error('Failed to create purchase: '.$response['message']);
                                Log::error('Failed to create purchase: '.$response['message']);
                                Log::error($data);
                            }
                        });
                    } catch (\Exception $e) {
                        Log::error('Purchase migration error: '.$e->getMessage());
                    }
                }
            });
        $progressBar->finish();
        $this->newLine();
        $this->info('Purchase migration completed successfully!');
    }

    private function sales()
    {
        $this->info('Migrating sales...');

        // Get total count for progress bar
        $totalSales = DB::connection('mysql2')
            ->table('sales')
            ->whereNull('deleted_at')
            ->count();

        $progressBar = $this->output->createProgressBar($totalSales);
        $progressBar->start();

        DB::connection('mysql2')
            ->table('sales')
            ->whereNull('sales.deleted_at')
            ->orderBy('sales.id')
            ->chunk(100, function ($sales) use ($progressBar) {
                foreach ($sales as $sale) {
                    $progressBar->advance();
                    try {
                        DB::transaction(function () use ($sale) {
                            $account = Account::where('second_reference_no', $sale->customer_id)->first();
                            $created_by = User::where('type', 'user')->where('second_reference_no', $sale->created_by)->value('id');
                            $updated_by = User::where('type', 'user')->where('second_reference_no', $sale->updated_by)->value('id');
                            $data = [
                                'branch_id' => 1,
                                'date' => $sale->date,
                                'due_date' => $sale->due_date,
                                'invoice_no' => $sale->invoice_no,
                                'sale_type' => 'normal',
                                'account_id' => $account->id,
                                'customer_name' => $sale->customer_name,
                                'customer_mobile' => $sale->customer_mobile,
                                'tax_amount' => 0,
                                'other_discount' => $sale->other_discount ? $sale->other_discount : 0,
                                'freight' => 0,
                                'grand_total' => $sale->grand_total,
                                'paid' => $sale->paid ? $sale->paid : 0,
                                'balance' => $sale->balance,
                                'address' => null,
                                'status' => 'completed',
                                'created_by' => $created_by,
                                'updated_by' => $updated_by,
                            ];
                            $data['comboOffers'] = [];
                            $data['items'] = [];
                            $sale_service_items = DB::connection('mysql2')
                                ->table('sale_service_items')
                                ->whereNull('deleted_at')
                                ->where('sale_id', $sale->id)
                                ->get();
                            foreach ($sale_service_items as $value) {
                                $product_id = Product::where('type', 'service')->where('second_reference_no', $value->spa_service_id)->value('id');
                                $employee_id = User::where('type', 'employee')->where('second_reference_no', $value->employee_id)->value('id');
                                $inventory_id = Inventory::where('product_id', $product_id)->value('id');
                                $item = [
                                    'inventory_id' => $inventory_id,
                                    'employee_id' => $employee_id,
                                    'product_id' => $product_id,
                                    'unit_price' => $value->unit_price,
                                    'quantity' => $value->quantity,
                                    'gross_total' => $value->unit_price * $value->quantity,
                                    'discount' => $value->discount,
                                    'tax' => 0,
                                    'total' => $value->unit_price * $value->quantity,
                                    ];
                                $data['items'][] = $item;
                            }

                            $sale_items = DB::connection('mysql2')
                                ->table('sale_items')
                                ->select(
                                    'product_id',
                                    'employee_id',
                                    'unit_price',
                                    DB::raw('SUM(quantity) as total_quantity'),
                                    DB::raw('SUM(discount) as total_discount')
                                )
                                ->whereNull('deleted_at')
                                ->where('sale_id', $sale->id)
                                ->groupBy('product_id', 'employee_id', 'unit_price')
                                ->get();

                            foreach ($sale_items as $value) {
                                $product_id = Product::where('type', 'product')
                                    ->where('second_reference_no', $value->product_id)
                                    ->value('id');
                                $employee_id = User::where('type', 'employee')
                                    ->where('second_reference_no', $value->employee_id)
                                    ->value('id');
                                $inventory_id = Inventory::where('product_id', $product_id)
                                    ->value('id');

                                $item = [
                                    'inventory_id' => $inventory_id,
                                    'employee_id' => $employee_id,
                                    'product_id' => $product_id,
                                    'unit_price' => $value->unit_price,
                                    'quantity' => $value->total_quantity,
                                    'net_amount' => $value->unit_price * $value->total_quantity,
                                    'discount' => $value->total_discount,
                                    'tax' => 0,
                                    'total' => ($value->unit_price * $value->total_quantity) - $value->total_discount,
                                ];
                                $data['items'][] = $item;
                            }

                            $data['items'] = collect($data['items']);

                            $data['gross_amount'] = $data['items']->sum('net_amount');
                            $data['item_discount'] = $data['items']->sum('discount');
                            $data['total_quantity'] = $data['items']->sum('quantity');
                            $data['total'] = $data['items']->sum('total');

                            $journals = DB::connection('mysql2')
                                ->table('journals')
                                ->whereNull('deleted_at')
                                ->where('sale_id', $sale->id)
                                ->whereIn('debit', [1, 16, 94, 336])
                                ->get();
                            $data['payments'] = [];
                            foreach ($journals as $value) {
                                $account_id = Account::where('second_reference_no', $value->debit)->value('id');
                                $journal = [
                                    'payment_method_id' => $account_id,
                                    'amount' => $value->amount,
                                ];
                                $data['payments'][] = $journal;
                            }
                            $response = (new SaleCreateAction())->execute($data, 1);
                            if (! $response['success']) {
                                $this->error('Failed to create sale: '.$response['message']);
                                Log::error('Failed to create sale: '.$response['message']);
                                Log::error($data);
                            }
                        });
                    } catch (\Exception $e) {
                        $this->error('Error migrating sales: '.$e->getMessage());
                        Log::error('Sales migration error: '.$e->getMessage());
                    }
                }
            });
        $progressBar->finish();
        $this->newLine();
        $this->info('Sales migration completed successfully!');
    }

    private function users()
    {
        try {
            $this->info('Migrating users...');
            DB::connection('mysql2')
                ->table('users')
                ->join('user_types', 'user_role_id', '=', 'user_types.id')
                ->whereNull('users.deleted_at')
                ->select(['users.*', 'user_types.name as user_type_name'])
                ->orderBy('users.id')
                ->chunk(100, function ($users) {
                    foreach ($users as $item) {
                        try {
                            DB::transaction(function () use ($item) {
                                // Create user
                                $name = ucfirst(strtolower($item->name));
                                $user = User::create([
                                    'type' => 'user',
                                    'code' => rand(100000, 999999),
                                    'second_reference_no' => $item->id,
                                    'name' => $name,
                                    'email' => strtolower($item->email ?? $name.'@astra.com'),
                                    'mobile' => $item->mobile,
                                    'password' => $item->password,
                                    'created_at' => $item->created_at,
                                    'updated_at' => $item->updated_at,
                                ]);
                                Role::firstOrCreate(['name' => ucfirst(strtolower($item->user_type_name))]);

                                $user->assignRole($item->user_type_name);

                                $single = [
                                    'user_id' => $user->id,
                                    'branch_id' => 1,
                                ];
                                UserHasBranch::create($single);
                                $user->update(['default_branch_id' => 1]);
                            });
                            $this->info("Created user: {$item->name}");
                        } catch (\Exception $e) {
                            $this->error("Failed to create user {$item->name}: {$e->getMessage()}");
                            Log::error("Employee creation error for {$item->name}: {$e->getMessage()}");

                            continue;
                        }
                    }
                    $this->info('Processed '.count($users).' users');
                });

            $this->info('Employee migration completed successfully');
        } catch (\Exception $e) {
            $this->error('Error migrating employees: '.$e->getMessage());
            Log::error('Employee migration error: '.$e->getMessage());
        }
    }

    private function employees()
    {
        try {
            $this->info('Migrating employees...');
            DB::connection('mysql2')
                ->table('employees')
                ->join('designations', 'designation_id', '=', 'designations.id')
                ->whereNull('employees.deleted_at')
                ->select(['employees.*', 'designations.name as designation_name'])
                ->orderBy('employees.id')
                ->chunk(100, function ($employees) {
                    foreach ($employees as $item) {
                        try {
                            DB::transaction(function () use ($item) {
                                // Create user
                                $name = ucfirst(strtolower($item->name));
                                $user = User::create([
                                    'type' => 'employee',
                                    'code' => rand(100000, 999999),
                                    'second_reference_no' => $item->id,
                                    'name' => $name,
                                    'email' => strtolower($item->email ?? $name.'@astra.com'),
                                    'mobile' => $item->mobile,
                                    'place' => $item->place,
                                    'allowance' => $item->allowance,
                                    'salary' => $item->salary,
                                    'hra' => $item->hra,
                                    'dob' => $item->dob,
                                    'doj' => $item->doj,
                                    'pin' => $item->pin,
                                    'password' => $item->password,
                                    'created_at' => $item->created_at,
                                    'updated_at' => $item->updated_at,
                                ]);
                                Role::firstOrCreate(['name' => ucfirst(strtolower($item->designation_name))]);

                                $user->assignRole($item->designation_name);

                                $single = [
                                    'user_id' => $user->id,
                                    'branch_id' => 1,
                                ];
                                UserHasBranch::create($single);
                                $user->update(['default_branch_id' => 1]);
                            });
                            $this->info("Created user: {$item->name}");
                        } catch (\Exception $e) {
                            $this->error("Failed to create user {$item->name}: {$e->getMessage()}");
                            Log::error("Employee creation error for {$item->name}: {$e->getMessage()}");

                            continue;
                        }
                    }
                    $this->info('Processed '.count($employees).' employees');
                });

            $this->info('Employee migration completed successfully');
        } catch (\Exception $e) {
            $this->error('Error migrating employees: '.$e->getMessage());
            Log::error('Employee migration error: '.$e->getMessage());
        }
    }

    private function products()
    {
        $this->info('Migrating products...');
        $products = DB::connection('mysql2')
            ->table('products')
            ->whereNull('products.deleted_at')
            ->leftJoin('brands', 'brand_id', '=', 'brands.id')
            ->leftJoin('categories', 'category_id', '=', 'categories.id')
            ->leftJoin('units', 'unit_id', '=', 'units.id')
            ->get([
                'products.*',
                'brands.name as brand_name',
                'categories.name as category_name',
                'units.name as unit_name',
            ]);
        foreach ($products as $item) {
            $name = ucfirst(strtolower($item->name));
            if ($name == 'Shampoo') {
                $name .= '.';
            }
            if ($name == 'Eyelashes') {
                $name .= '.';
            }
            $serviceData = [
                'type' => 'product',
                'code' => $item->code,
                'second_reference_no' => $item->id,
                'name' => $name,
                'name_arabic' => $item->name_arabic,
                'department' => 'Service',
                'main_category' => ucfirst(strtolower($item->category_name)),
                'sub_category' => '',
                'cost' => $item->cost,
                'unit' => ucfirst(strtolower($item->unit_name)),
                'brand' => ucfirst(strtolower($item->brand_name)),
                'mrp' => $item->mrp,
                'tax' => $item->tax,
                'priority' => $item->priority ? $item->priority : 0,
                'size' => $item->size,
                'barcode' => $item->barcode,
                'created_at' => $item->created_at,
                'updated_at' => $item->updated_at,
            ];
            $data = Product::constructData($serviceData, 1);
            unset($data['department']);
            unset($data['unit']);
            unset($data['main_category']);
            unset($data['sub_category']);
            $product = Product::create((array) $data);
            BranchProductCreationJob::dispatch(1, 1, $product->id);
        }
    }

    private function service()
    {
        $this->info('Migrating services...');
        $services = DB::connection('mysql2')
            ->table('spa_services')
            ->join('spa_service_groups', 'spa_service_group_id', '=', 'spa_service_groups.id')
            ->get(['spa_services.*', 'spa_service_groups.name as group_name']);
        foreach ($services as $item) {
            $serviceData = [
                'type' => 'service',
                'code' => rand(100000, 999999),
                'second_reference_no' => $item->id,
                'name' => $item->name,
                'name_arabic' => $item->arabic_name,
                'description' => $item->description,
                'department' => 'Service',
                'main_category' => ucfirst(strtolower($item->group_name)),
                'sub_category' => '',
                'cost' => $item->price,
                'mrp' => $item->price,
                'time' => $item->time,
                'priority' => $item->priority ? $item->priority : 0,
                'created_at' => $item->created_at,
                'updated_at' => $item->updated_at,
            ];
            $data = Product::constructData($serviceData, 1);
            unset($data['department']);
            unset($data['unit']);
            unset($data['main_category']);
            unset($data['sub_category']);
            $exists = Product::where('name', $data['name'])->exists();
            if (! $exists) {
                $product = Product::create((array) $data);
                BranchProductCreationJob::dispatch(1, 1, $product->id);
            }
        }
    }

    private function vendor()
    {
        $this->info('Migrating vendors...');
        $vendors = DB::connection('mysql2')->table('vendors')
            ->join('account_heads', 'vendors.account_head_id', '=', 'account_heads.id')
            ->get();
        foreach ($vendors as $vendor) {
            $vendorData = [
                'account_type' => 'liability',
                'second_reference_no' => $vendor->account_head_id,
                'model' => 'vendor',
                'name' => ucfirst(strtolower($vendor->name)),
                'email' => $vendor->email,
                'mobile' => $vendor->mobile,
                'place' => $vendor->place,
                'created_at' => $vendor->created_at,
                'updated_at' => $vendor->updated_at,
            ];
            DB::table('accounts')->insertOrIgnore((array) $vendorData);
        }
    }

    private function customer()
    {
        $this->info('Migrating customers...');
        $customers = DB::connection('mysql2')->table('customers')
            ->join('account_heads', 'customers.account_head_id', '=', 'account_heads.id')
            ->where('account_heads.id', '!=', 2)
            ->get();
        foreach ($customers as $customer) {
            $name = explode('@', $customer->name);
            $nationality = $customer->nationality;
            switch ($nationality) {
                case 'INDIAN/tamel':
                case 'INDIAN/KERALA':
                case 'KERALA':
                case 'keral':
                case 'indian':
                case 'INDIAN':
                    $nationality = 'India';
                    break;
                case 'QATARI':
                case 'QATARY':
                case 'QATAR':
                    $nationality = 'Qatar';
                    break;
                case 'EGYPTIAN':
                case 'EGYP':
                case 'egyp':
                    $nationality = 'Egypt';
                    break;
                case 'NIGERIA':
                    $nationality = 'Nigeria';
                    break;
                case 'moroccan':
                    $nationality = 'Morocco';
                    break;
                case 'PHILIPINES':
                    $nationality = 'Philippines';
                    break;
                case 'SAUDI':
                    $nationality = 'Saudi Arabia';
                    break;
                case 'tunisian':
                    $nationality = 'Tunisia';
                    break;
                case 'SERIA':
                    $nationality = 'Syria';
                    break;
                case 'Pakistanis':
                    $nationality = 'Pakistan';
                    break;
            }
            $customerData = [
                'account_type' => 'asset',
                'second_reference_no' => $customer->account_head_id,
                'model' => 'customer',
                'name' => ucfirst(strtolower($name[0])),
                'email' => $customer->email,
                'mobile' => $customer->mobile,
                'whatsapp_mobile' => $customer->whatsapp_no,
                'nationality' => $nationality,
                'dob' => $customer->dob !== '0000-00-00' ? $customer->dob : null,
                'id_no' => $customer->id_no,
                'company' => $customer->company,
                'created_at' => $customer->created_at,
                'updated_at' => $customer->updated_at,
            ];
            DB::table('accounts')->insertOrIgnore((array) $customerData);
        }
    }
}
