<?php

namespace App\Livewire\RentOut;

use App\Exports\Templates\RentOutImportTemplate;
use App\Jobs\Property\ImportRentOutJob;
use App\Support\RentOutConfig;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Livewire\Component;
use Livewire\WithFileUploads;
use Maatwebsite\Excel\Facades\Excel;
use Maatwebsite\Excel\HeadingRowImport;

class Import extends Component
{
    use WithFileUploads;

    public $file;

    public $step = 1;

    public $headers = [];

    public $mappings = [];

    public $previewData = [];

    public $filePath;

    public string $agreementType = 'rental';

    public $availableFields = [
        'id' => 'ID (only for update)',
        'property_id' => 'Property (ID or Number) (*)',
        'account_id' => 'Customer / Account (ID or Name) (*)',
        'salesman_id' => 'Salesman (ID or Name)',
        'agreement_type' => 'Agreement Type (rental/lease)',
        'booking_type' => 'Booking Type',
        'status' => 'Status',
        'booking_status' => 'Booking Status',
        'start_date' => 'Start Date (*)',
        'end_date' => 'End Date (*)',
        'vacate_date' => 'Vacate Date',
        'rent' => 'Rent (*)',
        'discount' => 'Discount',
        'total' => 'Total',
        'no_of_terms' => 'No of Terms',
        'free_month' => 'Free Months',
        'payment_frequency' => 'Payment Frequency',
        'collection_starting_day' => 'Collection Starting Day (1-31)',
        'collection_payment_mode' => 'Collection Payment Mode',
        'collection_bank_name' => 'Collection Bank Name',
        'collection_cheque_no' => 'Collection Cheque No',
        'management_fee' => 'Management Fee',
        'down_payment' => 'Down Payment',
        'remark' => 'Remark',
        'upload_type' => 'Upload Type (new/update)',
    ];

    public function mount(string $agreementType = 'rental'): void
    {
        $this->agreementType = in_array($agreementType, ['rental', 'lease'], true) ? $agreementType : 'rental';
    }

    public function getConfigProperty(): RentOutConfig
    {
        return RentOutConfig::make($this->agreementType);
    }

    private function getHeaderAliases(): array
    {
        return [
            'id' => ['id', 'rentoutid', 'rent_out_id', 'agreementid', 'agreement_id'],
            'property_id' => ['property_id', 'property', 'propertynumber', 'property number', 'propertyno', 'unit', 'unitno'],
            'account_id' => ['account_id', 'account', 'customer', 'customer_id', 'customername', 'customer name', 'tenant', 'tenantname'],
            'salesman_id' => ['salesman_id', 'salesman', 'salesmanname', 'sales person', 'salesperson'],
            'agreement_type' => ['agreement_type', 'agreementtype', 'agreement type', 'type'],
            'booking_type' => ['booking_type', 'bookingtype', 'booking type'],
            'status' => ['status'],
            'booking_status' => ['booking_status', 'bookingstatus', 'booking status'],
            'start_date' => ['start_date', 'startdate', 'start date', 'from', 'fromdate'],
            'end_date' => ['end_date', 'enddate', 'end date', 'to', 'todate'],
            'vacate_date' => ['vacate_date', 'vacatedate', 'vacate date'],
            'rent' => ['rent', 'rentamount', 'monthlyrent', 'monthly rent'],
            'discount' => ['discount'],
            'total' => ['total', 'totalamount', 'amount'],
            'no_of_terms' => ['no_of_terms', 'noofterms', 'no of terms', 'terms'],
            'free_month' => ['free_month', 'freemonth', 'free month', 'free months'],
            'payment_frequency' => ['payment_frequency', 'paymentfrequency', 'payment frequency', 'frequency'],
            'collection_starting_day' => ['collection_starting_day', 'collectionstartingday', 'collection starting day', 'startingday'],
            'collection_payment_mode' => ['collection_payment_mode', 'paymentmode', 'payment mode', 'collection payment mode'],
            'collection_bank_name' => ['collection_bank_name', 'bankname', 'bank name'],
            'collection_cheque_no' => ['collection_cheque_no', 'chequeno', 'cheque no', 'cheque'],
            'management_fee' => ['management_fee', 'managementfee', 'management fee'],
            'down_payment' => ['down_payment', 'downpayment', 'down payment'],
            'remark' => ['remark', 'remarks', 'note', 'notes'],
            'upload_type' => ['upload_type', 'uploadtype', 'upload type'],
        ];
    }

    private function normalizeHeader(string $value): string
    {
        return strtolower(str_replace(['_', ' ', '-'], '', $value));
    }

    public function updatedFile()
    {
        $this->validate([
            'file' => 'required|file|mimes:csv,xlsx,xls|max:10240',
        ]);

        $this->filePath = $this->file->store('temp-imports', 'public');

        $headings = (new HeadingRowImport())->toArray(Storage::disk('public')->path($this->filePath));
        $this->headers = $headings[0][0] ?? [];

        $aliases = $this->getHeaderAliases();

        foreach ($this->availableFields as $field => $label) {
            $allowed = $aliases[$field] ?? [$this->normalizeHeader($field)];
            $normalizedAllowed = array_map(fn ($a) => $this->normalizeHeader($a), $allowed);
            foreach ($this->headers as $header) {
                $normalizedHeader = $this->normalizeHeader((string) $header);
                if (in_array($normalizedHeader, $normalizedAllowed, true)) {
                    $this->mappings[$field] = $header;
                    break;
                }
            }
        }

        $this->step = 2;
        $this->loadPreview();
    }

    public function loadPreview()
    {
        $rows = Excel::toArray(new \stdClass(), Storage::disk('public')->path($this->filePath));
        $this->previewData = array_slice($rows[0], 1, 10);
    }

    public function goToStep($step)
    {
        $this->step = $step;
    }

    public function sample()
    {
        $filename = $this->agreementType.'_import_template.xlsx';

        return Excel::download(new RentOutImportTemplate($this->agreementType), $filename);
    }

    public function save()
    {
        $this->validate([
            'mappings.property_id' => 'required',
            'mappings.account_id' => 'required',
            'mappings.start_date' => 'required',
            'mappings.end_date' => 'required',
            'mappings.rent' => 'required',
        ], [
            'mappings.property_id.required' => 'The Property field must be mapped.',
            'mappings.account_id.required' => 'The Customer field must be mapped.',
            'mappings.start_date.required' => 'The Start Date field must be mapped.',
            'mappings.end_date.required' => 'The End Date field must be mapped.',
            'mappings.rent.required' => 'The Rent field must be mapped.',
        ]);

        ImportRentOutJob::dispatch(
            Auth::id(),
            $this->filePath,
            session('branch_id'),
            session('tenant_id'),
            $this->agreementType,
            $this->mappings
        );

        $this->dispatch('success', ['message' => 'Import started in background']);
        $this->step = 4;
    }

    public function render()
    {
        return view('livewire.rent-out.import', [
            'config' => $this->config,
        ]);
    }
}
