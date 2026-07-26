<?php

namespace App\Actions\RentOut\Comparison;

use Illuminate\Database\ConnectionInterface;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class CompareRentOutPopulationAction
{
    private const RENT_OUT_STATUS_MAP = [
        1 => 'occupied',
        2 => 'vacated',
        3 => 'expired',
        4 => 'booked',
        5 => 'cancelled',
    ];

    private const CHEQUE_STATUS_MAP = [
        1 => 'uncleared',
        2 => 'submitted',
        3 => 'return',
        4 => 'bounce',
        5 => 'cleared',
        6 => 'terminated',
    ];

    /**
     * @param  array<int>  $ids
     * @return array<string, mixed>
     */
    public function execute(
        string $oldConnection = 'mysql2',
        string $newConnection = 'mysql',
        array $ids = [],
        ?string $type = null,
        int $chunkSize = 250,
        ?callable $progress = null,
    ): array {
        if ($oldConnection === $newConnection) {
            throw new RuntimeException('The old and new database connections must be different.');
        }

        $old = DB::connection($oldConnection);
        $new = DB::connection($newConnection);
        $this->assertRequiredTablesExist($old, $new);

        $oldHeaders = $this->headerQuery($old, 'rentouts', $ids, $type)->get()->keyBy('id');
        $newHeaders = $this->headerQuery($new, 'rent_outs', $ids, $type)->get()->keyBy('id');
        $allIds = $oldHeaders->keys()->merge($newHeaders->keys())
            ->map(fn ($id): int => (int) $id)
            ->unique()
            ->sort()
            ->values();

        $accountReferences = $new->table('accounts')
            ->whereNotNull('second_reference_no')
            ->pluck('second_reference_no', 'id')
            ->map(fn ($id): int => (int) $id)
            ->all();
        $paymentModes = $this->paymentModeMap($old);
        $records = [];

        foreach ($allIds->chunk(max($chunkSize, 1)) as $idChunk) {
            $chunkIds = $idChunk->all();
            $children = $this->loadChildComparisons($old, $new, $chunkIds, $paymentModes);
            $oldLedger = $this->oldLedger($old, $chunkIds, array_keys($paymentModes));
            $newLedger = $this->newLedger($new, $chunkIds);

            foreach ($chunkIds as $id) {
                $oldHeader = $oldHeaders->get($id);
                $newHeader = $newHeaders->get($id);
                $header = $this->compareHeader($oldHeader, $newHeader, $accountReferences, $paymentModes);
                $tabs = [];

                foreach ($children as $tab => $rowsByRentOut) {
                    $tabs[$tab] = $rowsByRentOut[$id] ?? $this->emptyTabResult();
                }

                $ledger = $this->compareLedger(
                    $oldLedger[$id] ?? ['rows' => 0, 'debit' => 0.0, 'credit' => 0.0],
                    $newLedger[$id] ?? ['rows' => 0, 'debit' => 0.0, 'credit' => 0.0],
                );
                $differenceCount = collect($header)->where('matches', false)->count()
                    + collect($tabs)->sum('difference_count')
                    + ($ledger['matches'] ? 0 : 1);
                $agreementType = strtolower((string) ($newHeader->agreement_type ?? $oldHeader->agreement_type ?? 'rental'));
                $status = $newHeader
                    ? $this->normaliseValue($newHeader->status ?? null)
                    : $this->normaliseStatus($oldHeader->status ?? null);
                $isBooking = $status === 'booked';

                $records[$id] = [
                    'id' => $id,
                    'exists_old' => $oldHeader !== null,
                    'exists_new' => $newHeader !== null,
                    'agreement_type' => $agreementType,
                    'status' => $status,
                    'is_booking' => $isBooking,
                    'category' => $this->category($agreementType, $isBooking),
                    'old_url' => $this->oldUrl($id, $agreementType, $isBooking),
                    'new_url' => $this->newUrl($id, $agreementType, $isBooking),
                    'header' => $header,
                    'tabs' => $tabs,
                    'ledger' => $ledger,
                    'difference_count' => $differenceCount,
                    'matches' => $oldHeader !== null && $newHeader !== null && $differenceCount === 0,
                ];
            }

            if ($progress) {
                $progress(count($records), $allIds->count());
            }
        }

        ksort($records);
        $recordCollection = collect($records);
        $matching = $recordCollection->where('matches', true)->count();

        return [
            'meta' => [
                'generated_at' => now()->toIso8601String(),
                'old_connection' => $oldConnection,
                'new_connection' => $newConnection,
                'type' => $type,
                'selected_ids' => $ids,
                'version' => 1,
            ],
            'summary' => [
                'total' => count($records),
                'matching' => $matching,
                'differing' => count($records) - $matching,
                'missing' => $recordCollection->where('exists_old', true)->where('exists_new', false)->count(),
                'extra' => $recordCollection->where('exists_old', false)->where('exists_new', true)->count(),
                'match_percentage' => count($records) === 0 ? 100.0 : round(($matching / count($records)) * 100, 1),
                'by_category' => $recordCollection->countBy('category')->sortKeys()->all(),
            ],
            'records' => $records,
        ];
    }

    private function assertRequiredTablesExist(ConnectionInterface $old, ConnectionInterface $new): void
    {
        foreach ([[$old, 'rentouts', 'legacy'], [$new, 'rent_outs', 'new'], [$new, 'accounts', 'new']] as [$connection, $table, $label]) {
            if (! $connection->getSchemaBuilder()->hasTable($table)) {
                throw new RuntimeException("The {$label} connection does not contain the '{$table}' table.");
            }
        }
    }

    /**
     * @param  array<int>  $ids
     */
    private function headerQuery(ConnectionInterface $connection, string $table, array $ids, ?string $type)
    {
        return $connection->table($table)
            ->when($ids !== [], fn ($query) => $query->whereIn('id', $ids))
            ->when($type, fn ($query) => $query->where('agreement_type', $type))
            ->orderBy('id');
    }

    /**
     * @return array<int, string>
     */
    private function paymentModeMap(ConnectionInterface $old): array
    {
        if (! $old->getSchemaBuilder()->hasTable('account_heads')) {
            return [];
        }

        return $old->table('account_heads')
            ->whereIn('account_category_id', [16, 17])
            ->pluck('name', 'id')
            ->map(fn ($name): string => $this->normalisePaymentMode($name) ?? 'cash')
            ->all();
    }

    /**
     * @param  array<int, int>  $accountReferences
     * @param  array<int, string>  $paymentModes
     * @return array<string, array<string, mixed>>
     */
    private function compareHeader(?object $old, ?object $new, array $accountReferences, array $paymentModes): array
    {
        $fields = [
            'ID' => ['id', 'id', 'integer'],
            'Branch' => ['branch_id', 'branch_id', 'integer'],
            'Property' => ['property_id', 'property_id', 'integer'],
            'Building' => ['property_building_id', 'property_building_id', 'integer'],
            'Property type' => ['property_type_id', 'property_type_id', 'integer'],
            'Property group' => ['property_group_id', 'property_group_id', 'integer'],
            'Customer' => ['customer_id', 'account_id', 'account'],
            'Salesman' => ['salesman_id', 'salesman_id', 'integer'],
            'Agreement type' => ['agreement_type', 'agreement_type', 'string'],
            'Booking type' => ['booking_type', 'booking_type', 'string'],
            'Status' => ['status', 'status', 'status'],
            'Booking status' => ['booking_status', 'booking_status', 'string'],
            'Start date' => ['start_date', 'start_date', 'date'],
            'End date' => ['end_date', 'end_date', 'date'],
            'Vacate date' => ['vacate_date', 'vacate_date', 'date'],
            'Rent' => ['rent', 'rent', 'decimal'],
            'Number of terms' => ['no_of_terms', 'no_of_terms', 'integer'],
            'Payment frequency' => ['payment_frequency', 'payment_frequency', 'string'],
            'Discount' => ['discount', 'discount', 'decimal'],
            'Free month' => ['free_month', 'free_month', 'decimal'],
            'Total' => ['total', 'total', 'decimal'],
            'Collection day' => ['collection_starting_day', 'collection_starting_day', 'integer'],
            'Collection payment mode' => ['collection_payment_mode_id', 'collection_payment_mode', 'payment_mode'],
            'Collection bank' => ['collection_bank_name', 'collection_bank_name', 'string'],
            'Collection cheque' => ['collection_cheque_no', 'collection_cheque_no', 'string'],
            'Management fee' => ['management_fee', 'management_fee', 'decimal'],
            'Management payment method' => ['management_fee_payment_mode_id', 'management_fee_payment_method_id', 'account'],
            'Management remarks' => ['management_fee_remarks', 'management_fee_remarks', 'string'],
            'Down payment' => ['down_payment', 'down_payment', 'decimal'],
            'Down payment method' => ['down_payment_mode_id', 'down_payment_payment_method_id', 'account'],
            'Down payment remarks' => ['down_payment_remarks', 'down_payment_remarks', 'string'],
            'Electricity/water included' => ['include_electricity_water', 'include_electricity_water', 'string'],
            'AC included' => ['include_ac', 'include_ac', 'string'],
            'Wi-Fi included' => ['include_wifi', 'include_wifi', 'string'],
            'Remark' => ['remark', 'remark', 'string'],
            'Cancellation policy EN' => ['cancellation_policy_en', 'cancellation_policy_en', 'string'],
            'Cancellation policy AR' => ['cancellation_policy_ar', 'cancellation_policy_ar', 'string'],
            'Payment terms EN' => ['payment_terms_en', 'payment_terms_en', 'string'],
            'Payment terms AR' => ['payment_terms_ar', 'payment_terms_ar', 'string'],
            'Mandatory documents' => ['mandatory_documents', 'mandatory_documents', 'csv'],
            'Created at' => ['created_at', 'created_at', 'datetime'],
            'Deleted at' => ['deleted_at', 'deleted_at', 'datetime'],
        ];
        $result = [];

        foreach ($fields as $label => [$oldColumn, $newColumn, $type]) {
            $oldRaw = $old->{$oldColumn} ?? null;
            if ($label === 'Deleted at' && (int) ($old->status ?? 0) === 4) {
                $oldRaw = null;
            }
            $oldValue = $this->normaliseField($oldRaw, $type, true, $accountReferences, $paymentModes);
            $newValue = $this->normaliseField($new->{$newColumn} ?? null, $type, false, $accountReferences, $paymentModes);
            $result[$label] = [
                'old' => $oldValue,
                'new' => $newValue,
                'matches' => $old !== null && $new !== null && $oldValue === $newValue,
            ];
        }

        return $result;
    }

    /**
     * @param  array<int>  $ids
     * @param  array<int, string>  $paymentModes
     * @return array<string, array<int, array<string, mixed>>>
     */
    private function loadChildComparisons(ConnectionInterface $old, ConnectionInterface $new, array $ids, array $paymentModes): array
    {
        $result = [];

        foreach ($this->childDefinitions() as $tab => $definition) {
            if (! $old->getSchemaBuilder()->hasTable($definition['old_table'])
                || ! $new->getSchemaBuilder()->hasTable($definition['new_table'])) {
                $result[$tab] = array_fill_keys($ids, [
                    'available' => false,
                    'old_count' => null,
                    'new_count' => null,
                    'difference_count' => 0,
                    'rows' => [],
                ]);

                continue;
            }

            $oldRows = $old->table($definition['old_table'])
                ->whereIn($definition['old_parent'], $ids)
                ->when($definition['old_table'] === 'account_head_documents', fn ($query) => $query->where('model', 'like', '%Rentout%'))
                ->get()
                ->groupBy($definition['old_parent']);
            $newRows = $new->table($definition['new_table'])
                ->whereIn($definition['new_parent'], $ids)
                ->get()
                ->groupBy($definition['new_parent']);

            foreach ($ids as $id) {
                $result[$tab][$id] = $this->compareChildRows(
                    $oldRows->get($id, collect()),
                    $newRows->get($id, collect()),
                    $definition['fields'],
                    $paymentModes,
                );
            }
        }

        $result['Services'] = $this->loadServiceComparisons($old, $new, $ids, $paymentModes);

        return $result;
    }

    /**
     * @param  array<int>  $ids
     * @param  array<int, string>  $paymentModes
     * @return array<int, array<string, mixed>>
     */
    private function loadServiceComparisons(
        ConnectionInterface $old,
        ConnectionInterface $new,
        array $ids,
        array $paymentModes,
    ): array {
        if (! $old->getSchemaBuilder()->hasTable('journals')
            || ! $new->getSchemaBuilder()->hasTable('rent_out_services')) {
            return array_fill_keys($ids, [
                'available' => false,
                'old_count' => null,
                'new_count' => null,
                'difference_count' => 0,
                'rows' => [],
            ]);
        }

        $oldRows = $old->table('journals')
            ->whereIn('rentout_id', $ids)
            ->where('category', 'Service Charge')
            ->where('payment_type', 'Services')
            ->whereNotNull('more_details')
            ->whereNull('deleted_at')
            ->get()
            ->map(function (object $row): object {
                $details = json_decode((string) ($row->more_details ?? ''), true) ?: [];
                $unitSize = is_numeric($details['unit_size'] ?? null) ? ($details['unit_size'] + 0) : null;
                $rate = is_numeric($details['per_square_meter_price'] ?? null)
                    ? ($details['per_square_meter_price'] + 0)
                    : null;

                return (object) [
                    'id' => $row->id,
                    'rentout_id' => $row->rentout_id,
                    'branch_id' => $row->branch_id ?? null,
                    'name' => 'Service Charge',
                    'amount' => $row->amount ?? 0,
                    'description' => null,
                    'start_date' => $details['start_date'] ?? null,
                    'end_date' => $details['end_date'] ?? null,
                    'no_of_days' => $details['no_of_days'] ?? null,
                    'no_of_months' => $details['no_of_months'] ?? null,
                    'unit_size' => $unitSize,
                    'per_square_meter_price' => $rate,
                    'per_day_price' => ($unitSize && $rate) ? round($unitSize * $rate * 12 / 365, 2) : null,
                    'reason' => $row->reason ?? null,
                    'remark' => $row->remark ?? null,
                    'created_by' => $row->user_id ?? null,
                    'deleted_at' => null,
                    'created_at' => $row->created_at ?? $row->date ?? null,
                    'updated_at' => $row->updated_at ?? null,
                ];
            })
            ->groupBy('rentout_id');
        $newRows = $new->table('rent_out_services')
            ->whereIn('rent_out_id', $ids)
            ->get()
            ->groupBy('rent_out_id');
        $fields = [
            'Branch' => ['branch_id', 'branch_id', 'integer'],
            'Name' => ['name', 'name', 'string'],
            'Amount' => ['amount', 'amount', 'decimal'],
            'Description' => ['description', 'description', 'string'],
            'Start date' => ['start_date', 'start_date', 'date'],
            'End date' => ['end_date', 'end_date', 'date'],
            'Number of days' => ['no_of_days', 'no_of_days', 'integer'],
            'Number of months' => ['no_of_months', 'no_of_months', 'integer'],
            'Unit size' => ['unit_size', 'unit_size', 'decimal'],
            'Square metre price' => ['per_square_meter_price', 'per_square_meter_price', 'decimal'],
            'Per-day price' => ['per_day_price', 'per_day_price', 'decimal'],
            'Reason' => ['reason', 'reason', 'string'],
            'Remark' => ['remark', 'remark', 'string'],
            'Created by' => ['created_by', 'created_by', 'integer'],
            'Deleted at' => ['deleted_at', 'deleted_at', 'datetime'],
            'Created at' => ['created_at', 'created_at', 'datetime'],
        ];
        $result = [];

        foreach ($ids as $id) {
            $result[$id] = $this->compareChildRows(
                $oldRows->get($id, collect()),
                $newRows->get($id, collect()),
                $fields,
                $paymentModes,
            );
        }

        return $result;
    }

    /**
     * @return array<string, array<string, mixed>>
     */
    private function childDefinitions(): array
    {
        return [
            'Payment terms' => [
                'old_table' => 'payment_terms', 'new_table' => 'rent_out_payment_terms',
                'old_parent' => 'rentout_id', 'new_parent' => 'rent_out_id',
                'fields' => [
                    'Amount' => ['rent', 'amount', 'decimal'], 'Discount' => ['discount', 'discount', 'decimal'],
                    'Total' => ['amount', 'total', 'decimal'], 'Paid' => ['paid', 'paid', 'decimal'],
                    'Due date' => ['date', 'due_date', 'date'], 'Remark' => ['remark', 'remarks', 'string'],
                ],
            ],
            'Cheques' => [
                'old_table' => 'rentout_cheques', 'new_table' => 'rent_out_cheques',
                'old_parent' => 'rentout_id', 'new_parent' => 'rent_out_id',
                'fields' => [
                    'Cheque number' => ['cheque_no', 'cheque_no', 'string'], 'Bank' => ['bank_name', 'bank_name', 'string'],
                    'Amount' => ['amount', 'amount', 'decimal'], 'Date' => ['date', 'date', 'date'],
                    'Status' => ['status', 'status', 'cheque_status'], 'Payee' => ['payee_name', 'payee_name', 'string'],
                    'Remark' => ['remark', 'remarks', 'string'],
                ],
            ],
            'Security' => [
                'old_table' => 'rentout_securities', 'new_table' => 'rent_out_securities',
                'old_parent' => 'rentout_id', 'new_parent' => 'rent_out_id',
                'fields' => [
                    'Amount' => ['security_amount', 'amount', 'decimal'],
                    'Payment mode' => ['security_payment_mode_id', 'payment_mode', 'payment_mode'],
                    'Status' => ['status', 'status', 'security_status'], 'Type' => ['type', 'type', 'security_type'],
                    'Due date' => ['due_date', 'due_date', 'date'],
                ],
            ],
            'Extensions' => [
                'old_table' => 'rentout_extends', 'new_table' => 'rent_out_extends',
                'old_parent' => 'rentout_id', 'new_parent' => 'rent_out_id',
                'fields' => [
                    'Start date' => ['extended_from', 'start_date', 'date'], 'End date' => ['extended_to', 'end_date', 'date'],
                    'Rent' => ['rent', 'rent_amount', 'decimal'], 'Payment mode' => ['payment_mode_id', 'payment_mode', 'payment_mode'],
                ],
            ],
            'Notes' => [
                'old_table' => 'rentout_notes', 'new_table' => 'rent_out_notes',
                'old_parent' => 'rentout_id', 'new_parent' => 'rent_out_id',
                'fields' => ['Note' => ['notes', 'note', 'string']],
            ],
            'Utilities' => [
                'old_table' => 'rentout_utility_terms', 'new_table' => 'rent_out_utility_terms',
                'old_parent' => 'rentout_id', 'new_parent' => 'rent_out_id',
                'fields' => [
                    'Utility' => ['utility_id', 'utility_id', 'integer'], 'Amount' => ['amount', 'amount', 'decimal'],
                    'Balance' => ['balance', 'balance', 'decimal'], 'Date' => ['date', 'date', 'date'],
                ],
            ],
            'Documents' => [
                'old_table' => 'account_head_documents', 'new_table' => 'rent_out_documents',
                'old_parent' => 'model_id', 'new_parent' => 'rent_out_id',
                'fields' => [
                    'Document type' => ['document_type_id', 'document_type_id', 'integer'], 'Name' => ['name', 'name', 'string'],
                    'Path' => ['path', 'path', 'string'], 'Remarks' => ['remarks', 'remarks', 'string'],
                ],
            ],
            'Checklist' => [
                'old_table' => 'rentout_checklist_lines', 'new_table' => 'rent_out_checklist_lines',
                'old_parent' => 'rentout_id', 'new_parent' => 'rent_out_id',
                'fields' => [
                    'Item' => ['item', 'item', 'string'], 'Phase' => ['phase', 'phase', 'string'],
                    'Status' => ['status', 'status', 'string'], 'Remarks' => ['remarks', 'remarks', 'string'],
                ],
            ],
            'Maintenance' => [
                'old_table' => 'maintenances', 'new_table' => 'maintenances',
                'old_parent' => 'rentout_id', 'new_parent' => 'rent_out_id',
                'fields' => [
                    'Status' => ['status', 'status', 'string'], 'Priority' => ['priority', 'priority', 'string'],
                    'Description' => ['description', 'description', 'string'],
                ],
            ],
        ];
    }

    /**
     * @param  Collection<int, object>  $oldRows
     * @param  Collection<int, object>  $newRows
     * @param  array<string, array{string, string, string}>  $fields
     * @param  array<int, string>  $paymentModes
     * @return array<string, mixed>
     */
    private function compareChildRows(Collection $oldRows, Collection $newRows, array $fields, array $paymentModes): array
    {
        $oldRows = $oldRows->keyBy('id');
        $newRows = $newRows->keyBy('id');
        $ids = $oldRows->keys()->merge($newRows->keys())->unique()->sort();
        $rows = [];
        $differenceCount = 0;

        foreach ($ids as $id) {
            $old = $oldRows->get($id);
            $new = $newRows->get($id);
            $fieldResults = [];

            foreach ($fields as $label => [$oldColumn, $newColumn, $type]) {
                $oldValue = $this->normaliseChildField($old->{$oldColumn} ?? null, $type, true, $paymentModes);
                $newValue = $this->normaliseChildField($new->{$newColumn} ?? null, $type, false, $paymentModes);
                $fieldResults[$label] = ['old' => $oldValue, 'new' => $newValue, 'matches' => $oldValue === $newValue];
            }

            $matches = $old !== null && $new !== null && collect($fieldResults)->every('matches');
            if (! $matches) {
                $differenceCount++;
            }
            $rows[] = [
                'id' => (int) $id,
                'exists_old' => $old !== null,
                'exists_new' => $new !== null,
                'matches' => $matches,
                'fields' => $fieldResults,
            ];
        }

        return [
            'available' => true,
            'old_count' => $oldRows->count(),
            'new_count' => $newRows->count(),
            'difference_count' => $differenceCount,
            'rows' => $rows,
        ];
    }

    /**
     * @param  array<int>  $ids
     * @param  array<int>  $paymentModeIds
     * @return array<int, array{rows: int, debit: float, credit: float}>
     */
    private function oldLedger(ConnectionInterface $old, array $ids, array $paymentModeIds): array
    {
        $schema = $old->getSchemaBuilder();
        if (! $schema->hasTable('journal_views') && ! $schema->hasView('journal_views')) {
            return [];
        }

        $result = [];
        foreach ($old->table('journal_views')->whereIn('rentout_id', $ids)->get(['rentout_id', 'debit', 'amount']) as $row) {
            $id = (int) $row->rentout_id;
            $result[$id] ??= ['rows' => 0, 'debit' => 0.0, 'credit' => 0.0];
            $result[$id]['rows']++;
            if (in_array((int) $row->debit, $paymentModeIds, true)) {
                $result[$id]['credit'] += (float) $row->amount;
            } else {
                $result[$id]['debit'] += (float) $row->amount;
            }
        }

        return $result;
    }

    /**
     * @param  array<int>  $ids
     * @return array<int, array{rows: int, debit: float, credit: float}>
     */
    private function newLedger(ConnectionInterface $new, array $ids): array
    {
        $result = [];
        if ($new->getSchemaBuilder()->hasTable('rent_out_transactions')) {
            foreach ($new->table('rent_out_transactions')->whereIn('rent_out_id', $ids)->whereNull('deleted_at')->get() as $row) {
                $id = (int) $row->rent_out_id;
                $result[$id] ??= ['rows' => 0, 'debit' => 0.0, 'credit' => 0.0];
                $result[$id]['rows']++;
                $result[$id]['debit'] += (float) $row->debit;
                $result[$id]['credit'] += (float) $row->credit;
            }
        }
        foreach ([['rent_out_payment_terms', 'due_date', 'total'], ['rent_out_utility_terms', 'date', 'amount']] as [$table, $dateColumn, $amountColumn]) {
            if (! $new->getSchemaBuilder()->hasTable($table)) {
                continue;
            }
            foreach ($new->table($table)->whereIn('rent_out_id', $ids)->whereNull('deleted_at')->where($amountColumn, '!=', 0)->whereDate($dateColumn, '<=', today())->get() as $row) {
                $id = (int) $row->rent_out_id;
                $result[$id] ??= ['rows' => 0, 'debit' => 0.0, 'credit' => 0.0];
                $result[$id]['rows']++;
                $result[$id]['debit'] += (float) $row->{$amountColumn};
            }
        }

        return $result;
    }

    /**
     * @param  array{rows: int, debit: float, credit: float}  $old
     * @param  array{rows: int, debit: float, credit: float}  $new
     * @return array<string, mixed>
     */
    private function compareLedger(array $old, array $new): array
    {
        $matches = $old['rows'] === $new['rows']
            && abs($old['debit'] - $new['debit']) < 0.01
            && abs($old['credit'] - $new['credit']) < 0.01;

        return [
            'old' => $old,
            'new' => $new,
            'debit_difference' => round($new['debit'] - $old['debit'], 2),
            'credit_difference' => round($new['credit'] - $old['credit'], 2),
            'matches' => $matches,
        ];
    }

    /**
     * @param  array<int, int>  $accountReferences
     * @param  array<int, string>  $paymentModes
     */
    private function normaliseField(mixed $value, string $type, bool $isOld, array $accountReferences, array $paymentModes): mixed
    {
        return match ($type) {
            'account' => $isOld ? $this->normaliseInteger($value) : ($accountReferences[(int) $value] ?? $this->normaliseInteger($value)),
            'payment_mode' => $isOld ? ($paymentModes[(int) $value] ?? 'cash') : $this->normalisePaymentMode($value),
            'status' => $isOld ? $this->normaliseStatus($value) : $this->normaliseValue($value),
            default => $this->normaliseTypedValue($value, $type),
        };
    }

    /**
     * @param  array<int, string>  $paymentModes
     */
    private function normaliseChildField(mixed $value, string $type, bool $isOld, array $paymentModes): mixed
    {
        return match ($type) {
            'payment_mode' => $isOld ? ($paymentModes[(int) $value] ?? 'cash') : $this->normalisePaymentMode($value),
            'cheque_status' => $isOld ? (self::CHEQUE_STATUS_MAP[(int) $value] ?? $this->normaliseValue($value)) : $this->normaliseValue($value),
            'security_status' => $isOld ? $this->normaliseSecurityStatus($value) : $this->normaliseValue($value),
            'security_type' => $isOld ? ($this->normaliseValue($value) === 'guarantee' ? 'guarantee' : 'deposit') : $this->normaliseValue($value),
            default => $this->normaliseTypedValue($value, $type),
        };
    }

    private function normaliseTypedValue(mixed $value, string $type): mixed
    {
        return match ($type) {
            'integer' => $this->normaliseInteger($value),
            'decimal' => $value === null || $value === '' ? 0.0 : round((float) $value, 2),
            'date' => $value ? date('Y-m-d', strtotime((string) $value)) : null,
            'datetime' => $value ? date('Y-m-d H:i:s', strtotime((string) $value)) : null,
            'csv' => $value === null || $value === '' ? null : collect(explode(',', (string) $value))->map(fn ($item) => trim($item))->filter()->unique()->sort()->implode(','),
            default => $this->normaliseValue($value),
        };
    }

    private function normaliseStatus(mixed $value): ?string
    {
        return is_numeric($value)
            ? (self::RENT_OUT_STATUS_MAP[(int) $value] ?? (string) $value)
            : $this->normaliseValue($value);
    }

    private function normaliseSecurityStatus(mixed $value): ?string
    {
        return match ($this->normaliseValue($value)) {
            'submitted', 'pending' => 'pending',
            'collected' => 'collected',
            'returned' => 'returned',
            'adjusted' => 'adjusted',
            default => 'pending',
        };
    }

    private function normalisePaymentMode(mixed $value): ?string
    {
        $value = $this->normaliseValue($value);
        if ($value === null) {
            return 'cash';
        }
        if (str_contains($value, 'cheque') || str_contains($value, 'check')) {
            return 'cheque';
        }
        if (str_contains($value, 'card') || str_contains($value, 'pos')) {
            return 'pos';
        }
        if (str_contains($value, 'bank') || str_contains($value, 'transfer')) {
            return 'bank_transfer';
        }
        if (str_contains($value, 'cash')) {
            return 'cash';
        }

        return $value;
    }

    private function normaliseValue(mixed $value): mixed
    {
        if ($value === null || $value === '') {
            return null;
        }

        return is_string($value) ? strtolower(trim(preg_replace('/\s+/', ' ', $value))) : $value;
    }

    private function normaliseInteger(mixed $value): ?int
    {
        return $value === null || $value === '' ? null : (int) $value;
    }

    /**
     * @return array<string, mixed>
     */
    private function emptyTabResult(): array
    {
        return ['available' => true, 'old_count' => 0, 'new_count' => 0, 'difference_count' => 0, 'rows' => []];
    }

    private function category(string $type, bool $isBooking): string
    {
        return ($type === 'lease' ? 'Lease/sale' : 'Rental').' '.($isBooking ? 'booking' : 'agreement');
    }

    private function oldUrl(int $id, string $type, bool $isBooking): string
    {
        $baseUrl = rtrim((string) config('rentout-comparison.site_1_url'), '/');

        if ($type === 'lease') {
            return $baseUrl.'/Property/lease/'.($isBooking ? 'Booking/' : '')."view/{$id}";
        }

        return $isBooking
            ? "{$baseUrl}/Property/Booking/view/{$id}"
            : "{$baseUrl}/Property/Rentout/view/{$id}";
    }

    private function newUrl(int $id, string $type, bool $isBooking): string
    {
        $baseUrl = rtrim((string) config('rentout-comparison.site_2_url'), '/');
        $module = $type === 'lease' ? 'sale' : 'rent';

        return "{$baseUrl}/property/{$module}/".($isBooking ? 'booking/' : '')."view/{$id}";
    }
}
