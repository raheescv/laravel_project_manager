<?php

namespace App\Models;

use App\Models\Scopes\AssignedBranchScope;
use App\Models\Scopes\CurrentBranchScope;
use Illuminate\Database\Eloquent\Model;
use OwenIt\Auditing\Auditable;
use OwenIt\Auditing\Contracts\Auditable as AuditableContracts;

class SaleDaySession extends Model implements AuditableContracts
{
    use Auditable;

    protected $fillable = [
        'branch_id',
        'opened_by',
        'closed_by',
        'opened_at',
        'closed_at',
        'opening_amount',
        'closing_amount',
        'expected_amount',

        'sync_amount',
        'status',
        'notes',
    ];

    protected $casts = [
        'opened_at' => 'datetime',
        'closed_at' => 'datetime',
        'opening_amount' => 'decimal:2',
        'closing_amount' => 'decimal:2',
        'expected_amount' => 'decimal:2',
    ];

    protected static function booted()
    {
        static::addGlobalScope(new AssignedBranchScope());
    }

    public function branch()
    {
        return $this->belongsTo(Branch::class);
    }

    public function opener()
    {
        return $this->belongsTo(User::class, 'opened_by');
    }

    public function closer()
    {
        return $this->belongsTo(User::class, 'closed_by');
    }

    public function sales()
    {
        return $this->hasMany(Sale::class, 'sale_day_session_id')->where('status', 'completed');
    }

    public function scopeOpen($query)
    {
        return $query->where('status', 'open');
    }

    public function scopeClosed($query)
    {
        return $query->where('status', 'closed');
    }

    public function scopeCurrentBranch($query)
    {
        return CurrentBranchScope::apply($query);
    }

    public static function getOpenSessionForBranch($branchId)
    {
        return self::where('branch_id', $branchId)
            ->where('status', 'open')
            ->orderBy('opened_at', 'desc')
            ->first();
    }

    public static function hasOpenSession($branchId)
    {
        return self::where('branch_id', $branchId)
            ->where('status', 'open')
            ->exists();
    }

    public function close($closingAmount, $syncAmount, $closedBy, $notes = null)
    {
        $this->closed_at = now();
        $this->closed_by = $closedBy;
        $this->closing_amount = $closingAmount;
        $this->sync_amount = $syncAmount;

        // Calculate total sales amount for this session
        $totalSalesAmount = $this->sales->sum('paid');
        $this->expected_amount = $this->opening_amount + $totalSalesAmount;

        $this->status = 'closed';
        $this->notes = $notes;
        $this->save();

        return $this;
    }
}
