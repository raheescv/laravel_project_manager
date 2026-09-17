<?php

namespace App\Livewire\Student;

use App\Actions\Student\GetBalanceAction;
use App\Actions\Student\Guardian\SendInviteAction;
use App\Models\Account;
use App\Models\JournalEntry;
use App\Models\QpayTransaction;
use App\Models\Sale;
use App\Support\Student\StudentSettings;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;

/**
 * Student view shell: header (photo, class, card, balance) and a tab rail. Tabs
 * other than Profile are their own components, mounted the first time they open.
 */
class View extends Component
{
    public $account_id;

    public $selected_tab = 'profile';

    public $loaded_tabs = ['profile' => true];

    /** A set-password link generated for a parent, shown once so staff can copy it. */
    public $invite_link;

    public $invite_guardian_id;

    protected $listeners = [
        'Student-View-Refresh' => '$refresh',
    ];

    public function mount($account_id)
    {
        $this->account_id = $account_id;
    }

    public function selectTab($tab)
    {
        $this->selected_tab = $tab;
        $this->loaded_tabs[$tab] = true;
    }

    public function invite($guardianId)
    {
        abort_unless(auth()->user()?->can('student guardian.invite'), 403);

        $account = Account::student()->findOrFail($this->account_id);
        abort_unless($account->guardians()->whereKey($guardianId)->exists(), 404);

        $response = (new SendInviteAction())->execute((int) $guardianId, Auth::id());
        if (! $response['success']) {
            $this->dispatch('error', ['message' => $response['message']]);

            return;
        }

        $this->invite_guardian_id = (int) $guardianId;
        $this->invite_link = $response['data']['link'];
        $this->dispatch($response['data']['delivered'] ? 'success' : 'warning', ['message' => $response['message']]);
    }

    public function render()
    {
        $account = Account::student()->with(['studentDetail', 'guardians', 'accountCategory'])->findOrFail($this->account_id);
        $monthSales = Sale::withoutGlobalScopes()
            ->where('tenant_id', $account->tenant_id)
            ->where('account_id', $account->id)
            ->where('status', 'completed')
            ->where('date', '>=', now()->startOfMonth()->toDateString())
            ->selectRaw('COUNT(*) as bills, COALESCE(SUM(grand_total), 0) as total')
            ->first();

        $lastTopup = JournalEntry::query()
            ->where('account_id', $account->id)
            ->where('source', 'student_topup')
            ->where('credit', '>', 0)
            ->latest('date')
            ->latest('id')
            ->first(['id', 'date', 'credit', 'model', 'model_id']);
        $lastTopupBy = $lastTopup?->model === 'QpayTransaction'
            ? QpayTransaction::with('guardian:id,name')->find($lastTopup->model_id)?->guardian?->name
            : null;

        return view('livewire.student.view', [
            'account' => $account,
            'detail' => $account->studentDetail,
            'guardians' => $account->guardians->sortByDesc('pivot.is_primary')->values(),
            'balance' => (new GetBalanceAction())->execute($account->id),
            'overdraftLimit' => StudentSettings::current()->overdraftLimit,
            'monthSales' => $monthSales,
            'lastTopup' => $lastTopup,
            'lastTopupBy' => $lastTopupBy,
            'purchaseCount' => Sale::where('account_id', $account->id)->count(),
            'topupCount' => auth()->user()->can('student topup.view') ? QpayTransaction::where('account_id', $account->id)->count() : null,
        ]);
    }
}
