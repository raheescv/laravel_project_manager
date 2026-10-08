<?php

namespace App\Http\Controllers\Property;

use App\Http\Controllers\Controller;
use App\Models\Journal;
use App\Models\RentOutPaymentTerm;
use App\Models\RentOutTransaction;
use App\Support\RentOutConfig;
use Illuminate\Http\Request;

class RentOutController extends Controller
{
    /**
     * Infer agreement type from route name prefix.
     * Routes named property::rent::* are rental, property::sale::* are lease.
     */
    protected function getConfig(Request $request): RentOutConfig
    {
        $routeName = $request->route()->getName() ?? '';
        $type = str_contains($routeName, 'property::rent::') ? 'rental' : 'lease';

        return RentOutConfig::make($type);
    }

    public function index(Request $request)
    {
        $config = $this->getConfig($request);

        return view('property.rent-out.index', compact('config'));
    }

    public function page(Request $request, $id = null)
    {
        $config = $this->getConfig($request);

        return view('property.rent-out.create', compact('config', 'id'));
    }

    public function view(Request $request, $id)
    {
        $config = $this->getConfig($request);

        return view('property.rent-out.view', compact('config', 'id'));
    }

    public function booking(Request $request)
    {
        $config = $this->getConfig($request);

        return view('property.rent-out.booking', compact('config'));
    }

    public function bookingPage(Request $request, $id = null)
    {
        $config = $this->getConfig($request);

        return view('property.rent-out.booking-create', compact('config', 'id'));
    }

    public function bookingView(Request $request, $id)
    {
        $config = $this->getConfig($request);

        return view('property.rent-out.booking-view', compact('config', 'id'));
    }

    /**
     * One payment term with the receipts that paid it, the journals those
     * receipts posted, and the audit trail of all three. Reversed (soft
     * deleted) receipts and journals stay visible so the history is complete.
     */
    public function paymentTerm(Request $request, $id)
    {
        $config = $this->getConfig($request);

        $term = RentOutPaymentTerm::withTrashed()
            ->with(['rentOut.customer', 'rentOut.property', 'audits.user'])
            ->findOrFail($id);

        abort_unless($term->rentOut?->agreement_type === $config->agreementType, 404);

        // @phpstan-ignore larastan.relationExistence (Audit::user() is a real MorphTo in owen-it/laravel-auditing; the vendor method has no return type)
        $transactions = RentOutTransaction::withTrashed()
            ->forPaymentTerm($term)
            ->with(['account', 'audits.user'])
            ->orderBy('date')
            ->orderBy('id')
            ->get();

        $canViewJournals = $request->user()->can($config->viewJournalPermission);

        $journals = collect();
        if ($canViewJournals) {
            // @phpstan-ignore larastan.relationExistence (Audit::user() is a real MorphTo in owen-it/laravel-auditing; the vendor method has no return type)
            $journals = Journal::withTrashed()
                ->with(['entries' => fn ($query) => $query->withTrashed()->with('account'), 'audits.user'])
                ->whereIn('id', $transactions->pluck('journal_id')->filter())
                ->orderBy('date')
                ->orderBy('id')
                ->get()
                ->each(fn (Journal $journal) => $journal->setRelation(
                    'entries',
                    $journal->trashed() ? $journal->entries : $journal->entries->whereNull('deleted_at')->values()
                ));
        }

        return view('property.rent-out.payment-term', [
            'config' => $config,
            'term' => $term,
            'rentOut' => $term->rentOut,
            'transactions' => $transactions,
            'transactionsByJournal' => $transactions->whereNotNull('journal_id')->keyBy('journal_id'),
            'journals' => $journals,
            'canViewJournals' => $canViewJournals,
        ]);
    }

    public function import(Request $request)
    {
        $config = $this->getConfig($request);

        return view('property.rent-out.import', compact('config'));
    }
}
