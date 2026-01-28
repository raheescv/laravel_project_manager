<?php

namespace App\Http\Controllers;

use App\Actions\Journal\GeneralVoucherJournalEntryAction;
use App\Http\Requests\GeneralVoucherRequest;
use App\Models\Configuration;
use App\Models\Journal;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class GeneralVoucherController extends Controller
{
    public function index()
    {
        return view('accounts.general-voucher.index');
    }

    public function print($id)
    {
        $journal = Journal::where('id', $id)
            ->where('source', 'General Voucher')
            ->with(['entries.account', 'createdBy'])
            ->firstOrFail();

        // Determine voucher type based on Payment Methods
        $voucherType = 'general'; // Default
        $paymentMethodIds = cache('payment_methods', []);

        foreach ($journal->entries as $entry) {
            if ($entry->account && in_array($entry->account_id, $paymentMethodIds)) {
                // If Payment Method account is debited, it's a Receipt Voucher (money received)
                if ($entry->debit > 0) {
                    $voucherType = 'receipt';
                    break;
                }
                // If Payment Method account is credited, it's a Payment Voucher (money paid)
                if ($entry->credit > 0) {
                    $voucherType = 'payment';
                    break;
                }
            }
        }
        // Get company configuration
        $companyName = Configuration::where('key', 'company_name')->value('value') ?? config('app.name');
        $companyAddress = Configuration::where('key', 'company_address')->value('value') ?? '';
        $companyPhone = Configuration::where('key', 'company_phone')->value('value') ?? '';
        $companyEmail = Configuration::where('key', 'company_email')->value('value') ?? '';
        $enableLogoInPrint = Configuration::where('key', 'enable_logo_in_print')->value('value') ?? 'yes';
        $companyLogo = cache('logo', asset('assets/img/logo.svg'));

        return view('accounts.general-voucher.print', compact('journal', 'companyName', 'companyAddress', 'companyPhone', 'companyEmail', 'enableLogoInPrint', 'companyLogo', 'voucherType'));
    }

    /**
     * Get journal data for editing
     */
    public function getData($id): JsonResponse
    {
        try {
            $journal = Journal::where('id', $id)
                ->where('source', 'General Voucher')
                ->with(['entries.account'])
                ->firstOrFail();

            $entries = [];
            foreach ($journal->entries as $entry) {
                $entries[] = [
                    'account_id' => $entry->account_id,
                    'account_name' => $entry->account->name ?? null,
                    'debit' => $entry->debit,
                    'credit' => $entry->credit,
                    'description' => $entry->description ?? '',
                    'person_name' => $entry->person_name ?? null,
                ];
            }

            return response()->json([
                'success' => true,
                'journal' => [
                    'branch_id' => $journal->branch_id,
                    'source' => $journal->source,
                    'date' => $journal->date,
                    'person_name' => $journal->person_name,
                    'reference_number' => $journal->reference_number,
                    'remarks' => $journal->remarks,
                    'description' => $journal->description,
                ],
                'entries' => $entries,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 404);
        }
    }

    /**
     * Store a newly created general voucher
     */
    public function store(GeneralVoucherRequest $request): JsonResponse
    {
        try {
            DB::beginTransaction();
            $userId = Auth::id();

            // Prepare data for the action
            $data = [
                'branch_id' => $request->input('branch_id'),
                'date' => $request->input('date'),
                'source' => $request->input('source', 'General Voucher'),
                'person_name' => $request->input('person_name'),
                'reference_number' => $request->input('reference_number'),
                'remarks' => $request->input('remarks'),
                'description' => $request->input('description'),
                'entries' => $request->input('entries'),
            ];

            $response = (new GeneralVoucherJournalEntryAction())->execute($userId, $data, null);

            if (! $response['success']) {
                throw new \Exception($response['message'], 1);
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => $response['message'],
                'data' => $response['data'],
            ]);
        } catch (ValidationException $e) {
            DB::rollback();

            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $e->errors(),
            ], 422);
        } catch (\Exception $e) {
            DB::rollback();

            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Update the specified general voucher
     */
    public function update(GeneralVoucherRequest $request, $id): JsonResponse
    {
        try {
            DB::beginTransaction();
            $userId = Auth::id();

            // Verify journal exists and belongs to General Voucher
            $journal = Journal::where('id', $id)
                ->where('source', 'General Voucher')
                ->firstOrFail();

            // Prepare data for the action
            $data = [
                'branch_id' => $request->input('branch_id'),
                'date' => $request->input('date'),
                'source' => $request->input('source', 'General Voucher'),
                'person_name' => $request->input('person_name'),
                'reference_number' => $request->input('reference_number'),
                'remarks' => $request->input('remarks'),
                'description' => $request->input('description'),
                'entries' => $request->input('entries'),
            ];

            $response = (new GeneralVoucherJournalEntryAction())->execute($userId, $data, $id);

            if (! $response['success']) {
                throw new \Exception($response['message'], 1);
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => $response['message'],
                'data' => $response['data'],
            ]);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            DB::rollback();

            return response()->json([
                'success' => false,
                'message' => 'Journal not found',
            ], 404);
        } catch (ValidationException $e) {
            DB::rollback();

            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $e->errors(),
            ], 422);
        } catch (\Exception $e) {
            DB::rollback();

            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 500);
        }
    }
}
