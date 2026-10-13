<?php

namespace App\Http\Controllers;

use App\Actions\Account\Vendor\DeleteAction;
use App\Actions\Account\Vendor\SaveAction;
use App\Enums\Account\VendorType;
use App\Exports\AccountExport;
use App\Http\Controllers\ProjectContract\ProjectContractController;
use App\Http\Requests\Account\VendorRequest;
use App\Models\Account;
use App\Models\JournalEntry;
use App\Models\ProjectContract;
use App\Models\Scopes\AssignedBranchScope;
use App\Support\ModuleAccess;
use App\Traits\ApiResponseTrait;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

/**
 * The vendor screen (Account → Vendors): `accounts` rows with model Vendor,
 * listed and edited with their full detail fields. Shared by every system; the
 * project-contract columns appear only when the Agreement Module is running.
 */
class VendorController extends Controller
{
    use ApiResponseTrait;

    public const SORTABLE = ['id', 'name', 'mobile', 'email', 'place', 'credit_period_days'];

    public function index(Request $request, ProjectContractController $pages): View
    {
        return $pages->screen($request, 'vendors');
    }

    public function list(Request $request): JsonResponse
    {
        $search = trim((string) $request->input('search'));
        $type = (string) $request->input('vendor_type');
        $sort = in_array($request->input('sort'), self::SORTABLE, true) ? $request->input('sort') : 'name';
        $direction = $request->input('direction') === 'desc' ? 'desc' : 'asc';
        $withContracts = ModuleAccess::agreement();

        $contracts = fn () => ProjectContract::withoutGlobalScope(AssignedBranchScope::class)->whereColumn('account_id', 'accounts.id');

        $vendors = Account::vendor()
            ->when($search !== '', fn (Builder $query) => $query->where(fn (Builder $query) => $query
                ->where('name', 'like', "%{$search}%")
                ->orWhere('alias_name', 'like', "%{$search}%")
                ->orWhere('mobile', 'like', "%{$search}%")
                ->orWhere('email', 'like', "%{$search}%")
                ->orWhere('place', 'like', "%{$search}%")
                ->orWhere('cr_number', 'like', "%{$search}%")
                ->orWhere('tax_no', 'like', "%{$search}%")))
            ->when($type !== '', fn (Builder $query) => $query->where('vendor_type', $type))
            ->select(['id', 'name', 'alias_name', 'vendor_type', 'mobile', 'email', 'place', 'credit_period_days', 'cr_number', 'tax_no'])
            ->addSelect(['ledger_balance' => JournalEntry::withoutGlobalScopes()->whereColumn('account_id', 'accounts.id')->whereNull('deleted_at')->selectRaw('coalesce(sum(credit - debit), 0)')])
            ->when($withContracts, fn (Builder $query) => $query->addSelect([
                'contracts_count' => $contracts()->selectRaw('count(*)'),
                'committed' => $contracts()->whereIn('status', ['active', 'completed'])->selectRaw('coalesce(sum(contract_amount), 0)'),
            ]))
            ->orderBy($sort, $direction)
            ->paginate(min((int) $request->input('per_page', 20), 500));

        $types = collect(VendorType::cases())->mapWithKeys(fn (VendorType $type): array => [$type->value => $type->label()]);

        return $this->sendSuccess([
            'rows' => $vendors->getCollection()->map(fn (Account $vendor): array => [
                'id' => $vendor->id,
                'name' => $vendor->name,
                'alias_name' => $vendor->alias_name,
                'vendor_type' => $vendor->vendor_type,
                'vendor_type_label' => $types[$vendor->vendor_type] ?? null,
                'mobile' => $vendor->mobile,
                'email' => $vendor->email,
                'place' => $vendor->place,
                'credit_period_days' => $vendor->credit_period_days,
                'registration' => $vendor->cr_number ?: $vendor->tax_no,
                'contracts' => (int) ($vendor->contracts_count ?? 0),
                'committed' => round((float) ($vendor->committed ?? 0), 2),
                'balance' => round((float) $vendor->ledger_balance, 2),
            ])->values(),
            'pagination' => ['page' => $vendors->currentPage(), 'last_page' => $vendors->lastPage(), 'total' => $vendors->total(), 'per_page' => $vendors->perPage()],
            'with_contracts' => $withContracts,
        ], 'Vendors loaded.');
    }

    /** Vendors for searchable selects, such as the project contract form. */
    public function search(Request $request): JsonResponse
    {
        $search = trim((string) $request->input('q'));

        $items = Account::vendor()
            ->when($search !== '', fn (Builder $query) => $query->where(fn (Builder $query) => $query
                ->where('name', 'like', "%{$search}%")
                ->orWhere('alias_name', 'like', "%{$search}%")
                ->orWhere('mobile', 'like', "%{$search}%")))
            ->orderBy('name')
            ->limit(20)
            ->get(['id', 'name', 'alias_name', 'mobile']);

        return $this->sendSuccess($items, 'Vendors loaded.');
    }

    public function show(int $id): JsonResponse
    {
        $vendor = Account::vendor()->findOrFail($id);

        return $this->sendSuccess(
            collect(SaveAction::FIELDS)->mapWithKeys(fn (string $field): array => [$field => $vendor->{$field} instanceof \DateTimeInterface ? $vendor->{$field}->format('Y-m-d') : $vendor->{$field}])->put('id', $vendor->id)->all(),
            'Vendor loaded.'
        );
    }

    public function store(VendorRequest $request, SaveAction $action): JsonResponse
    {
        $response = $action->execute($request->validated());

        return $response['success']
            ? $this->sendSuccess(['id' => $response['data']->id, 'name' => $response['data']->name, 'mobile' => $response['data']->mobile], $response['message'], 201)
            : $this->sendError($response['message'], [], 422);
    }

    public function update(int $id, VendorRequest $request, SaveAction $action): JsonResponse
    {
        Account::vendor()->findOrFail($id);
        $response = $action->execute($request->validated(), $id);

        return $response['success']
            ? $this->sendSuccess(['id' => $id, 'name' => $response['data']->name], $response['message'])
            : $this->sendError($response['message'], [], 422);
    }

    public function destroy(int $id, DeleteAction $action): JsonResponse
    {
        $response = $action->execute($id);

        return $response['success']
            ? $this->sendSuccess(null, 'Vendor deleted.')
            : $this->sendError($response['message'], [], 422);
    }

    /** Delete several vendors; each one still goes through the in-use guard. */
    public function destroyMany(Request $request, DeleteAction $action): JsonResponse
    {
        $request->validate(['ids' => ['required', 'array', 'min:1', 'max:500'], 'ids.*' => ['integer']], ['ids.required' => 'Select at least one vendor.']);

        $failed = [];
        $deleted = 0;
        foreach (array_unique($request->input('ids')) as $id) {
            $response = $action->execute((int) $id);
            $response['success'] ? $deleted++ : $failed[] = $response['message'];
        }

        $message = $deleted.' '.($deleted === 1 ? 'vendor' : 'vendors').' deleted.'.($failed ? ' '.count($failed).' kept: '.implode(' ', array_slice($failed, 0, 3)) : '');

        return $deleted || ! $failed
            ? $this->sendSuccess(['deleted' => $deleted, 'failed' => $failed], $message)
            : $this->sendError($message, [], 422);
    }

    public function export(): BinaryFileResponse
    {
        return Excel::download(new AccountExport(['account_type' => 'liability', 'model' => 'Vendor']), 'Vendor_'.now()->timestamp.'.xlsx');
    }
}
