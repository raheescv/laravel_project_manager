<?php

namespace App\Http\Controllers;

use App\Actions\Account\Customer\GenerateStatementAction;
use App\Actions\Account\Customer\GetCustomerDetailsAction;
use App\Actions\Account\GetJournalEntriesAction;
use App\Models\Account;
use Illuminate\Http\Request;

class AccountController extends Controller
{
    public function index()
    {
        return view('accounts.index');
    }

    public function customer($id = null)
    {
        if ($id) {
            return view('accounts.customer_details', compact('id'));
        } else {
            return view('accounts.customer');
        }
    }

    public function vendor()
    {
        return view('accounts.vendor');
    }

    public function notes($id = null)
    {
        return view('accounts.notes', compact('id'));
    }

    public function get(Request $request)
    {
        $list = (new Account())->getDropDownList($request->all());

        return response()->json($list);
    }

    public function view($id)
    {
        $account = Account::findOrFail($id);

        return view('accounts.view', compact('id', 'account'));
    }

    public function getCustomerDetails($id)
    {
        $result = (new GetCustomerDetailsAction())->execute($id);

        if (! $result['success']) {
            return response()->json($result, 404);
        }

        return response()->json($result);
    }

    public function statement($id, Request $request)
    {
        $fromDate = $request->input('from_date');
        $toDate = $request->input('to_date');

        return (new GenerateStatementAction())->execute($id, $fromDate, $toDate);
    }

    public function bankReconciliation()
    {
        return view('accounts.bank_reconciliation');
    }

    public function getJournalEntries($journalId)
    {
        $result = (new GetJournalEntriesAction())->execute($journalId);

        return response()->json($result);
    }
}
