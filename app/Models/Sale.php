<?php

namespace App\Models;

use App\Helpers\Facades\SaleHelper;
use App\Helpers\Facades\WhatsappHelper;
use App\Models\Models\Views\Ledger;
use App\Models\Scopes\AssignedBranchScope;
use App\Models\Scopes\CurrentBranchScope;
use App\Traits\BelongsToTenant;
use Exception;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Validation\Rule;
use OwenIt\Auditing\Auditable;
use OwenIt\Auditing\Contracts\Auditable as AuditableContracts;

class Sale extends Model implements AuditableContracts
{
    use Auditable;
    use BelongsToTenant;
    use SoftDeletes;

    const ADDITIONAL_DISCOUNT_DESCRIPTION = 'Additional Discount Provided on Sales';

    protected $fillable = [
        'tenant_id',
        'invoice_no',
        'reference_no',
        'sale_type',

        'branch_id',
        'account_id',
        'date',
        'due_date',
        'sale_day_session_id',

        'customer_name',
        'customer_mobile',

        'gross_amount',
        'item_discount',
        'tax_amount',

        'other_discount',
        'freight',
        'round_off',

        'paid',

        'payment_method_ids',
        'payment_method_name',

        'address',

        'rating',
        'feedback_type',
        'feedback',

        'status',

        'created_by',
        'updated_by',
        'cancelled_by',
        'deleted_by',
    ];

    public static function rules($id = 0, $merge = [])
    {
        $tenantId = self::getCurrentTenantId();

        return array_merge(
            [
                'invoice_no' => ['required', Rule::unique(self::class, 'invoice_no')->where('tenant_id', $tenantId)->ignore($id)],
                'branch_id' => ['required'],
                'account_id' => ['required'],
                'sale_type' => ['required'],
                'date' => ['required'],
            ],
            $merge,
        );
    }

    protected static function booted()
    {
        static::addGlobalScope(new AssignedBranchScope());

        static::creating(function ($sale): void {
            // Check if sale needs to be associated with an open day session
            if ($sale->branch_id) {
                // If no sale_day_session_id is provided, find the open session for the branch
                if (empty($sale->sale_day_session_id)) {
                    $openSession = SaleDaySession::getOpenSessionForBranch($sale->branch_id);
                    if ($openSession) {
                        $sale->sale_day_session_id = $openSession->id;
                        $sale->date = $openSession->opened_at->format('Y-m-d');
                    }
                } else {
                    // If a session ID is provided, ensure it's valid and open
                    $session = SaleDaySession::find($sale->sale_day_session_id);
                    if (! $session || $session->status !== 'open' || $session->branch_id !== $sale->branch_id) {
                        throw new \Exception('Invalid or closed day session provided.');
                    }
                }
            }
        });
    }

    public function scopeDraft($query)
    {
        return $query->where('status', 'draft');
    }

    public function scopeCompleted($query)
    {
        return $query->where('status', 'completed');
    }

    public function scopeToday($query)
    {
        return $query->where('date', date('Y-m-d'));
    }

    public function scopeCurrentBranch($query)
    {
        return CurrentBranchScope::apply($query);
    }

    public function scopeLast7Days($query)
    {
        return $query->whereBetween('date', [date('Y-m-d', strtotime('-7 days')), date('Y-m-d')]);
    }

    public function scopeLast30Days($query)
    {
        return $query->whereBetween('date', [date('Y-m-d', strtotime('-30 days')), date('Y-m-d')]);
    }

    public function scopeLastYear($query)
    {
        return $query->whereBetween('date', [date('Y-m-d', strtotime('-11 month')), date('Y-m-d')]);
    }

    public function scopeCustomerSearch($query, $branch_id = null, $from = null, $to = null)
    {
        return $query->when($branch_id, fn ($q) => $q->where('branch_id', $branch_id))
            ->where('status', 'completed')
            ->when($from, fn ($q) => $q->where('date', '>=', $from))
            ->when($to, fn ($q) => $q->where('date', '<=', $to));
    }

    public function scopeFilter(Builder $query, array $filters): Builder
    {
        return $query
            ->when($filters['search'] ?? '', function ($q, $search) {
                $search = trim($search);

                return $q->where(function ($q) use ($search): void {
                    $q->where('sales.id', 'like', "%{$search}%")
                        ->orWhere('sales.invoice_no', 'like', "%{$search}%")
                        ->orWhere('sales.reference_no', 'like', "%{$search}%")
                        ->orWhere('sales.customer_name', 'like', "%{$search}%")
                        ->orWhere('sales.customer_mobile', 'like', "%{$search}%");
                });
            })
            ->when($filters['sale_type'] ?? '', fn ($q, $value) => $q->where('sales.sale_type', $value))
            ->when($filters['created_by'] ?? '', fn ($q, $value) => $q->where('sales.created_by', $value))
            ->when($filters['branch_id'] ?? '', fn ($q, $value) => $q->where('branch_id', $value))
            ->when($filters['customer_id'] ?? '', fn ($q, $value) => $q->where('account_id', $value))
            ->when($filters['payment_method_id'] ?? '', function ($q, $value) {
                return $q->whereRaw('FIND_IN_SET(?, payment_method_ids)', [$value]);
            })
            ->when($filters['sale_day_session_id'] ?? '', fn ($q, $value) => $q->where('sale_day_session_id', $value))
            ->when($filters['status'] ?? '', fn ($q, $value) => $q->where('status', $value))
            ->when($filters['from_date'] ?? '', fn ($q, $value) => $q->whereDate('sales.date', '>=', date('Y-m-d', strtotime($value))))
            ->when($filters['to_date'] ?? '', fn ($q, $value) => $q->whereDate('sales.date', '<=', date('Y-m-d', strtotime($value))));
    }

    public function employeeNames()
    {
        $employeeIds = $this->items()->whereNotNull('employee_id')->distinct()->pluck('employee_id')->filter()->toArray();
        if (empty($employeeIds)) {
            return '';
        }
        $employeeNames = User::whereIn('id', $employeeIds)->orderBy('name')->pluck('name')->filter()->unique()->toArray();

        return implode(', ', $employeeNames);
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class, 'tenant_id');
    }

    public function branch()
    {
        return $this->belongsTo(Branch::class);
    }

    public function account()
    {
        return $this->belongsTo(Account::class);
    }

    public function saleDaySession()
    {
        return $this->belongsTo(SaleDaySession::class);
    }

    public function createdUser()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updatedUser()
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public function cancelledUser()
    {
        return $this->belongsTo(User::class, 'cancelled_by');
    }

    public function items()
    {
        return $this->hasMany(SaleItem::class);
    }

    public function comboOffers()
    {
        return $this->hasMany(SaleComboOffer::class);
    }

    public function payments()
    {
        return $this->hasMany(SalePayment::class);
    }

    public function journal()
    {
        return $this->hasOne(Journal::class, 'model_id')->where('model', 'Sale');
    }

    public function journals()
    {
        return $this->hasMany(Journal::class, 'model_id')->where('model', 'Sale');
    }

    public function ledgers()
    {
        return $this->hasMany(Ledger::class, 'model_id')->where('model', 'Sale');
    }

    public function getDropDownList($request)
    {
        $self = self::orderBy('invoice_no');
        $self = $self->when($request['query'] ?? '', function ($query, $value) {
            return $query->where(function ($q) use ($value): void {
                $value = trim($value);
                $q->where('sales.invoice_no', 'like', "%{$value}%")->orWhere('sales.reference_no', 'like', "%{$value}%");
            });
        });
        $self = $self->when($request['account_id'] ?? '', function ($query, $value) {
            return $query->where('account_id', $value);
        });
        $self = $self->limit(10);
        $self = $self->get(['invoice_no', 'reference_no', 'id'])->toArray();
        array_unshift($self, [
            'invoice_no' => 'General',
            'reference_no' => null,
            'id' => 0,
        ]);
        $return['items'] = $self;

        return $return;
    }

    public static function sendToWhatsapp($table_id)
    {
        try {
            $sale = self::find($table_id);
            if ($sale['customer_mobile']) {
                $number = $sale['customer_mobile'];
            } else {
                $number = $sale->account->mobile;
            }

            $imageContent = SaleHelper::saleInvoice($table_id, 'thermal');
            $image_path = SaleHelper::convertHtmlToImage($imageContent, $sale->invoice_no);

            $image_url = asset("invoices/{$sale->invoice_no}.png");

            $templateName = config('services.meta_whatsapp.template_name');

            $response = WhatsappHelper::sendTemplateWithImage(to: $number, templateName: $templateName, imageUrl: $image_url);
            if (! $response['success']) {
                throw new Exception($response['message']);
            }

            $return['success'] = true;
            $return['message'] = 'WhatsApp message sent successfully.';
        } catch (Exception $e) {
            $return['success'] = false;
            $return['message'] = "WhatsApp server error: {$e->getMessage()}";
        }

        return $return;
    }

    public static function updateSalePaymentMethods(Sale $sale): void
    {
        $payment_method_ids = $sale->payments->pluck('payment_method_id')->toArray();
        $payment_method_name = Account::whereIn('id', $payment_method_ids)->pluck('name')->toArray();

        $data = [
            'payment_method_ids' => implode(',', $payment_method_ids),
            'payment_method_name' => implode(', ', $payment_method_name),
            'paid' => $sale->payments->sum('amount'),
        ];
        // to avoid storing the audit log
        if ($data['payment_method_ids'] == $sale->payment_method_ids) {
            unset($data['payment_method_ids']);
        }
        if ($data['payment_method_name'] == $sale->payment_method_name) {
            unset($data['payment_method_name']);
        }
        if ($data['paid'] == $sale->paid) {
            unset($data['paid']);
        }
        $sale->update($data);
    }
}
