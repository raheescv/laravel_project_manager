<?php

namespace App\Http\Controllers\ProjectContract;

use App\Actions\ProjectContract\Approval\DecideAction;
use App\Actions\ProjectContract\ChangeStatusAction;
use App\Actions\ProjectContract\CreateAction;
use App\Actions\ProjectContract\DeleteAction;
use App\Actions\ProjectContract\SubmitAction;
use App\Actions\ProjectContract\UpdateAction;
use App\Enums\Account\VendorType;
use App\Enums\ProjectContract\ContractStatus;
use App\Enums\ProjectContract\PaymentFrequency;
use App\Enums\ProjectContract\SecurityDirection;
use App\Enums\ProjectContract\SecurityForm;
use App\Enums\ProjectContract\SecurityPurpose;
use App\Http\Controllers\Controller;
use App\Http\Controllers\ProjectContract\Concerns\RespondsWithContract;
use App\Http\Requests\ProjectContract\ContractRequest;
use App\Http\Requests\ProjectContract\DecisionRequest;
use App\Http\Resources\ProjectContract\ContractDetailResource;
use App\Http\Resources\ProjectContract\ContractRowResource;
use App\Models\Account;
use App\Models\ProjectContract;
use App\Models\ProjectContractApproval;
use App\Models\ProjectContractExtension;
use App\Models\ProjectContractPayment;
use App\Models\ProjectContractPaymentTerm;
use App\Models\ProjectContractSecuritySettlement;
use App\Support\ProjectContract\ApprovalLevels;
use App\Traits\ApiResponseTrait;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class ProjectContractController extends Controller
{
    use ApiResponseTrait;
    use RespondsWithContract;

    public const PER_PAGE = 15;

    /** Every screen of the module is one Vue app; the route only picks which screen it opens on. */
    public function screen(Request $request, string $screen, ?int $id = null): View
    {
        return view('project-contract.app', [
            'screen' => $screen,
            'recordId' => $id,
            'permissions' => $this->permissions(),
            'urls' => [
                'list' => route('project-contract::index'),
                'create' => route('project-contract::create'),
                'edit' => route('project-contract::edit', ['id' => '__ID__']),
                'view' => route('project-contract::view', ['id' => '__ID__']),
                'reportPending' => route('project-contract::report::pending'),
                'statement' => route('print::project_contract::statement', ['id' => '__ID__']),
                'receipt' => route('print::project_contract::payment-receipt', ['id' => '__ID__']),
                'voucher' => route('print::project_contract::payment-voucher', ['id' => '__ID__']),
                'contractVoucher' => route('print::project_contract::voucher', ['id' => '__ID__']),
                'termInvoice' => route('print::project_contract::term', ['kind' => 'invoice', 'id' => '__ID__']),
                'termReceipt' => route('print::project_contract::term', ['kind' => 'receipt', 'id' => '__ID__']),
                'termVoucher' => route('print::project_contract::term', ['kind' => 'voucher', 'id' => '__ID__']),
                'reportPaid' => route('project-contract::report::paid'),
                'vendors' => route('project-contract::vendor::index'),
            ],
            'title' => match ($screen) {
                'create' => 'New Project Contract',
                'edit' => 'Edit Project Contract',
                'view' => 'Project Contract',
                'report-pending' => 'Pending Payments',
                'report-paid' => 'Paid Payments',
                'vendors' => 'Vendors',
                default => 'Project Contracts',
            },
        ]);
    }

    public function index(Request $request): View
    {
        return $this->screen($request, 'list');
    }

    public function create(Request $request): View
    {
        return $this->screen($request, 'create');
    }

    public function edit(Request $request, int $id): View
    {
        ProjectContract::findOrFail($id);

        return $this->screen($request, 'edit', $id);
    }

    public function view(Request $request, int $id): View
    {
        ProjectContract::findOrFail($id);

        return $this->screen($request, 'view', $id);
    }

    public function pendingReport(Request $request): View
    {
        return $this->screen($request, 'report-pending');
    }

    public function paidReport(Request $request): View
    {
        return $this->screen($request, 'report-paid');
    }

    /**
     * @return array<string, bool>
     */
    private function permissions(): array
    {
        $user = Auth::user();
        $actions = collect(config('permissions.project contract', []))
            ->mapWithKeys(fn (string $action): array => [str_replace(' ', '_', $action) => $user->can("project contract.{$action}")]);

        foreach (['view', 'create', 'edit', 'delete', 'export'] as $action) {
            $actions->put("vendor_{$action}", $user->can("vendor.{$action}"));
        }

        return $actions->all();
    }

    public function list(Request $request): JsonResponse
    {
        $filter = $request->only(['search', 'status', 'account_id', 'from_date', 'to_date']);
        $sort = in_array($request->input('sort'), ['reference_no', 'project_name', 'end_date', 'contract_amount', 'start_date'], true) ? $request->input('sort') : 'id';
        $direction = $request->input('direction') === 'asc' ? 'asc' : 'desc';

        $contracts = ProjectContract::query()
            ->filter($filter)
            ->with('vendor:id,name')
            ->withSum(['payments as paid_total' => fn (Builder $query) => $query->where('status', ProjectContractPayment::STATUS_POSTED)], 'amount')
            ->addSelect([
                'next_due_date' => ProjectContractPaymentTerm::select('due_date')->whereColumn('project_contract_id', 'project_contracts.id')->where('balance', '>', 0)->orderBy('due_date')->limit(1),
                'next_due_amount' => ProjectContractPaymentTerm::select('balance')->whereColumn('project_contract_id', 'project_contracts.id')->where('balance', '>', 0)->orderBy('due_date')->limit(1),
                'pending_level' => ProjectContractApproval::selectRaw('min(level)')
                    ->where('approvable_type', ProjectContract::class)
                    ->whereColumn('approvable_id', 'project_contracts.id')
                    ->where('status', ProjectContractApproval::PENDING),
                'level_count' => ProjectContractApproval::selectRaw('count(*)')
                    ->where('approvable_type', ProjectContract::class)
                    ->whereColumn('approvable_id', 'project_contracts.id')
                    ->where('status', '!=', ProjectContractApproval::SUPERSEDED)
                    ->whereRaw('round = (select max(a2.round) from project_contract_approvals a2 where a2.approvable_type = project_contract_approvals.approvable_type and a2.approvable_id = project_contract_approvals.approvable_id)'),
            ])
            ->orderBy($sort, $direction)
            ->paginate(min((int) $request->input('per_page', self::PER_PAGE), 100));

        $counts = ProjectContract::query()->filter(array_diff_key($filter, ['status' => true]))
            ->toBase()->selectRaw('status, COUNT(*) as aggregate')->groupBy('status')->pluck('aggregate', 'status');

        return $this->sendSuccess([
            'rows' => ContractRowResource::collection($contracts->getCollection())->resolve(),
            'pagination' => ['page' => $contracts->currentPage(), 'last_page' => $contracts->lastPage(), 'total' => $contracts->total(), 'per_page' => $contracts->perPage()],
            'counts' => ['all' => (int) $counts->sum()] + collect(ContractStatus::cases())->mapWithKeys(fn (ContractStatus $status): array => [$status->value => (int) ($counts[$status->value] ?? 0)])->all(),
        ], 'Contracts loaded.');
    }

    public function show(int $id): JsonResponse
    {
        $contract = $this->loadDetail($id);

        return $this->sendSuccess(ContractDetailResource::make($contract)->resolve(), 'Contract loaded.');
    }

    public function store(ContractRequest $request, CreateAction $action): JsonResponse
    {
        $response = $action->execute($request->validated(), Auth::id());

        return $response['success']
            ? $this->sendSuccess(['id' => $response['data']->id], $response['message'], 201)
            : $this->sendError($response['message'], [], 422);
    }

    public function update(int $id, ContractRequest $request, UpdateAction $action): JsonResponse
    {
        ProjectContract::findOrFail($id);
        $response = $action->execute($request->validated(), $id, Auth::id());

        return $response['success']
            ? $this->sendSuccess(['id' => $id], $response['message'])
            : $this->sendError($response['message'], [], 422);
    }

    public function destroy(int $id, DeleteAction $action): JsonResponse
    {
        ProjectContract::findOrFail($id);

        return $this->result($action->execute($id, Auth::id()), $id);
    }

    public function submit(int $id, SubmitAction $action): JsonResponse
    {
        ProjectContract::findOrFail($id);

        return $this->result($action->execute($id, Auth::id()), $id);
    }

    public function status(int $id, Request $request, ChangeStatusAction $action): JsonResponse
    {
        ProjectContract::findOrFail($id);
        $request->validate(['status' => ['required', 'in:completed,cancelled']]);

        return $this->result($action->execute($id, $request->input('status'), Auth::id()), $id);
    }

    public function decide(DecisionRequest $request, DecideAction $action): JsonResponse
    {
        $subject = match ($request->input('subject')) {
            'contract' => ProjectContract::findOrFail($request->integer('subject_id')),
            'extension' => ProjectContractExtension::whereHas('contract')->findOrFail($request->integer('subject_id')),
            default => ProjectContractSecuritySettlement::whereHas('security.contract')->findOrFail($request->integer('subject_id')),
        };

        $contractId = $subject instanceof ProjectContract ? $subject->id : ($subject->project_contract_id ?? $subject->security?->project_contract_id);
        $response = $action->execute($subject, $request->input('decision'), $request->input('comment'), $request->user());

        return $this->result($response, $contractId);
    }

    /** Lookups the screens need: payment methods, the enums and the approval levels. */
    public function options(): JsonResponse
    {
        $enum = fn (string $class): array => collect($class::cases())->map(fn ($case): array => ['value' => $case->value, 'label' => $case->label()])->all();

        return $this->sendSuccess([
            'payment_methods' => Account::whereIn('id', tenant_cache('payment_methods', []))->orderBy('name')->get(['id', 'name', 'is_cheque'])->values(),
            'frequencies' => $enum(PaymentFrequency::class),
            'statuses' => $enum(ContractStatus::class),
            'security_forms' => $enum(SecurityForm::class),
            'security_purposes' => $enum(SecurityPurpose::class),
            'security_directions' => $enum(SecurityDirection::class),
            'vendor_types' => $enum(VendorType::class),
            'document_types' => ['Signed contract', 'BOQ', 'Addendum', 'Vendor invoice', 'Drawing', 'Other'],
            'approval_levels' => ApprovalLevels::all(),
        ], 'Options loaded.');
    }
}
