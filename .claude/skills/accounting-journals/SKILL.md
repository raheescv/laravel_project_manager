---
name: accounting-journals
description: "Use whenever money moves — sales, purchases, GRN/LPO bills, rent-out rent, security deposits, utilities, service charges, payments, refunds, expenses, income, vouchers, asset depreciation, or any reversal or deletion of those. Covers double-entry journal posting via App\\Actions\\Journal\\CreateAction, the makeEntryPair helper, the accounts_slug_id_map cache, source/model/model_id linkage, ledger and statement reads, and the delete/reverse rules. Read before writing a *JournalEntryAction, changing an amount that lands in the ledger, or debugging an unbalanced or missing ledger entry."
---

# Accounting and Journals

This ERP posts real double-entry accounting. Every completed financial document writes a `Journal` with balanced `JournalEntry` rows, and the ledger, statements, trial balance, and P&L all read from those rows. A posting bug is a bug in the customer's books — be conservative.

## Where posting lives

Each module owns a `JournalEntryAction` next to its CRUD actions, and a matching delete/reverse action:

```
app/Actions/Sale/JournalEntryAction.php       app/Actions/Sale/JournalDeleteAction.php
app/Actions/RentOut/JournalEntryAction.php
app/Actions/RentOut/Security/SyncAccountingAction.php
app/Actions/Journal/CreateAction.php          # the shared writer — always go through this
```

Posting is triggered from the parent action, only when the document reaches its completed state, and its `success` is checked:

```php
if ($this->model['status'] == 'completed') {
    $response = (new JournalEntryAction())->execute($this->model, $this->userId);
    if (! $response['success']) {
        throw new Exception($response['message'], 1);
    }
}
```

Never call `Journal::create()` or `JournalEntry::insert()` from a Livewire component, a controller, or a model observer.

## The journal header

```php
$data = [
    'tenant_id'   => $sale->tenant_id,
    'date'        => $sale->date,
    'branch_id'   => $sale->branch_id,
    'description' => 'Sale:'.$sale->invoice_no,
    'reference_no'=> $sale->reference_no,
    'source'      => 'sale',      // groups the entry in reports/statements
    'model'       => 'Sale',      // class basename of the originating document
    'model_id'    => $sale->id,
    'created_by'  => $this->userId,
    'entries'     => [/* … */],
];
(new Journal\CreateAction())->execute($data);
```

`source` + `model` + `model_id` are how a ledger row is traced back to its document and how deletes find what to remove. Always set all three. For rent-out, `source` is derived from the agreement type (`AgreementType::sourceSlug` → `sale` or `rent_out`) while `model` stays `'RentOut'`.

## Writing entries — always in pairs

Accounts are resolved from a cached slug map, not by name lookup:

```php
$accounts = Cache::get('accounts_slug_id_map', []);
$accounts['sale'], $accounts['cost_of_goods_sold'], $accounts['inventory'],
$accounts['tax_amount'], $accounts['discount'], $accounts['freight'], $accounts['round_off']
```

Each economic event produces **two rows** via `makeEntryPair()`, which mirrors debit/credit and sets `counter_account_id` on both sides:

```php
protected function makeEntryPair($accountId1, $accountId2, $debit, $credit, $remarks, $model, $modelId)
{
    $base = ['created_by' => $this->userId, 'remarks' => $remarks, 'model' => $model, 'model_id' => $modelId];

    return [
        array_merge($base, ['account_id' => $accountId1, 'counter_account_id' => $accountId2, 'debit' => $debit,  'credit' => $credit]),
        array_merge($base, ['account_id' => $accountId2, 'counter_account_id' => $accountId1, 'debit' => $credit, 'credit' => $debit]),
    ];
}
```

Then flatten the pairs into the header:

```php
$data['entries'] = array_merge(...$entries);
```

Copy this helper into a new `JournalEntryAction` rather than inventing a different entry builder — reports rely on both sides existing with `counter_account_id` populated.

## Rules for new postings

- **Guard every leg with `if ($amount > 0)`.** Zero-amount pairs pollute the ledger. Signed values (`round_off`) get an explicit sign branch that swaps debit and credit rather than posting a negative.
- **Per-line `model`/`model_id`.** A sale's payment legs carry `'SalePayment'` + the payment id, not the sale id, so payment reversals can find their rows.
- **Remarks are user-facing** — they appear in statements. Write them as sentences (`'Sales tax collected on sale'`, `$paymentMethod->name.' payment made by '.$account->name`).
- **Cost of goods sold** uses `inventory->cost × quantity` filtered to `product?->type === 'product'`; services carry no COGS.
- **The payment method *is* the account.** `payment_method_id` is an account id — debit it directly; there is no separate mapping table.

## Changing, deleting, reversing

- Editing a posted document deletes and re-posts its journal (`JournalDeleteAction` then `JournalEntryAction`), inside the caller's transaction. Do not mutate `journal_entries` rows in place.
- Rent-out security deposits reconcile on every save through `Security\SyncAccountingAction` — status drives the posting (`collected` → Dr payment method / Cr security-deposit liability; `returned` adds the refund leg). Change the status handling there, not at the call sites.
- Vendor and rent-out payments have explicit `ReverseTransactionAction`s that post a contra entry and flip the document to a reversed status. Reversal, not deletion, is the rule for anything already delivered to a customer or vendor.
- GRN and LPO bills clear GRNI (goods-received-not-invoiced) — a GRN posts into GRNI and the bill clears it. Reversing either must unwind that clearing; check the three-way-match guard before changing quantities or amounts.

## Verifying

After any change to a posting path, confirm on a scratch record that (a) total debits equal total credits for the journal, (b) each pair has both legs with mirrored `counter_account_id`, and (c) the ledger/statement screen for the affected account shows the document. `App\Models\Models\Views\Ledger` is a database view over the entries — if a row is missing there, the posting is wrong, not the report.
