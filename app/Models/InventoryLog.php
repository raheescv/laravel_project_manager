<?php

namespace App\Models;

use App\Models\Scopes\TenantScope;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InventoryLog extends Model
{
    protected static function booted()
    {
        static::addGlobalScope(new TenantScope());
    }

    protected $fillable = [
        'tenant_id',
        'branch_id',
        'employee_id',
        'product_id',
        'quantity_in',
        'quantity_out',
        'balance',
        'barcode',
        'batch',
        'cost',

        'model',
        'model_id',
        'remarks',

        'user_id',
        'user_name',
    ];

    public static function rules($id = 0, $merge = [])
    {
        return array_merge([
            'branch_id' => ['required'],
            'product_id' => ['required'],
            'quantity_in' => ['required'],
            'quantity_out' => ['required'],
            'balance' => ['required'],
            'barcode' => ['required'],
            'batch' => ['required'],
            'cost' => ['required'],
            'user_id' => ['required'],
        ], $merge);
    }

    public function branch()
    {
        return $this->belongsTo(Branch::class);
    }

    public function employee()
    {
        return $this->belongsTo(User::class, 'employee_id');
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class, 'tenant_id');
    }

    public static function monthly_summary($start_date, $end_date, $product_id, $branch_id = null)
    {
        $start_date = $start_date ?: now()->subMonth()->startOfMonth();
        $end_date = $end_date ?: now()->endOfMonth();
        $start = Carbon::parse($start_date)->startOfMonth();
        $end = Carbon::parse($end_date)->endOfMonth();

        // Create base array with all months initialized to zero
        $allMonths = collect();
        $current = $start->copy();

        while ($current <= $end) {
            $monthKey = $current->format('Y-m');
            $allMonths[$monthKey] = [
                'month' => $monthKey,
                'month_name' => $current->format('M Y'),
                'total_in' => 0,
                'total_out' => 0,
            ];
            $current->addMonth();
        }

        // Get actual data from database
        $query = self::selectRaw('
                DATE_FORMAT(created_at, "%Y-%m") as month,
                SUM(quantity_in) as total_in,
                SUM(quantity_out) as total_out
            ')
            ->whereBetween('created_at', [$start, $end])
            ->when($product_id, function ($q, $value) {
                return $q->where('product_id', $value);
            })
            ->when($branch_id, function ($q, $value) {
                return $q->where('branch_id', $value);
            })
            ->groupBy('month')
            ->orderBy('month', 'asc');

        // Merge actual data with base array
        $data = $query->get()->mapWithKeys(function ($item) {
            return [$item->month => [
                'month' => $item->month,
                'month_name' => \Carbon\Carbon::createFromFormat('Y-m', $item->month)->format('M Y'),
                'total_in' => (float) $item->total_in,
                'total_out' => (float) $item->total_out,
            ]];
        });

        // Merge and ensure all months are present
        return $allMonths->merge($data)->sortKeys()->values();
    }

    public static function daily_summary($start_date, $end_date, $product_id, $branch_id = null)
    {
        $start_date = $start_date ?: now()->subDay()->startOfDay();
        $end_date = $end_date ?: now()->endOfDay();
        $start = Carbon::parse($start_date)->startOfDay();
        $end = Carbon::parse($end_date)->endOfDay();

        // Create base array with all days initialized to zero
        $allDays = collect();
        $current = $start->copy();

        while ($current <= $end) {
            $dayKey = $current->format('Y-m-d');
            $allDays[$dayKey] = [
                'day' => $dayKey,
                'day_name' => $current->format('d M'),
                'total_in' => 0,
                'total_out' => 0,
            ];
            $current->addDay();
        }

        // Get actual data from database
        $query = self::selectRaw('
                DATE_FORMAT(created_at, "%Y-%m-%d") as day,
                SUM(quantity_in) as total_in,
                SUM(quantity_out) as total_out
            ')
            ->whereBetween('created_at', [$start, $end])
            ->when($product_id, function ($q, $value) {
                return $q->where('product_id', $value);
            })
            ->when($branch_id, function ($q, $value) {
                return $q->where('branch_id', $value);
            })
            ->groupBy('day')
            ->orderBy('day', 'asc');

        // Merge actual data with base array
        $data = $query->get()->mapWithKeys(function ($item) {
            return [$item->day => [
                'day' => $item->day,
                'day_name' => Carbon::parse($item->day)->format('d M'),
                'total_in' => (float) $item->total_in,
                'total_out' => (float) $item->total_out,
            ]];
        });

        // Merge and ensure all days are present
        return $allDays->merge($data)->sortKeys()->values();
    }
}
