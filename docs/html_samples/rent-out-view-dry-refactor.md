# RentOut Module Migration — Complete Conversation Log

This document captures the full conversation covering the Sale form page updates, interactive management tabs for View pages, and the DRY refactor of Rent/View and Sale/View components.

---

## Table of Contents

1. [Part 1: Sale Form Page Updates](#part-1-sale-form-page-updates)
2. [Part 2: Interactive Management Tabs for View Pages](#part-2-interactive-management-tabs-for-view-pages)
3. [Part 3: DRY Refactor of Rent/View and Sale/View](#part-3-dry-refactor-of-rentview-and-saleview)
4. [Supporting Changes](#supporting-changes)
5. [Final File Tree & Results](#final-file-tree--results)

---

## Part 1: Sale Form Page Updates

### Context

The Rent form page (`rent/page.blade.php` and `Rent/Page.php`) had already been built with `html()->select()` from Spatie Laravel HTML, TomSelect for searchable AJAX dropdowns, `PropertyAutoFill` and `RentOutSelectValues` Livewire dispatch events, and a `vacant_only` filter. The Sale form page needed to be updated to match.

### Changes to `app/Livewire/RentOut/Sale/Page.php`

#### Added `$vacant_only` Property

```php
public $vacant_only = true;
```

#### Updated `loadData()` Method

Added eager loading of relationships and dispatch of `RentOutSelectValues` event so TomSelect dropdowns are pre-filled in edit mode:

```php
$item = RentOut::with(['property.building.group', 'property.type', 'customer', 'salesman'])->find($this->table_id);
// ... after setting rentouts array ...
$this->dispatch('RentOutSelectValues', [
    'property_group_id' => $item->property_group_id,
    'group_name' => $item->property?->building?->group?->name,
    'property_building_id' => $item->property_building_id,
    'building_name' => $item->property?->building?->name,
    'property_type_id' => $item->property_type_id,
    'type_name' => $item->property?->type?->name,
    'property_id' => $item->property_id,
    'property_name' => $item->property ? $item->property->number . ($item->property->building ? ' - ' . $item->property->building->name : '') : '',
    'account_id' => $item->account_id,
    'customer_name' => $item->customer?->name,
    'salesman_id' => $item->salesman_id,
    'salesman_name' => $item->salesman?->name,
]);
```

#### Updated `propertyCheck()` Method

Loads property relationships and dispatches `PropertyAutoFill` event so dependent dropdowns (group, building, type) auto-populate when a property is selected:

```php
public function propertyCheck()
{
    $property = Property::with(['building.group', 'type'])->find($this->rentouts['property_id']);
    if ($property) {
        $this->rentouts['rent'] = $property->rent ?? 0;
        $this->rentouts['property_building_id'] = $property->property_building_id;
        $this->rentouts['property_group_id'] = $property->property_group_id ?? $property->building?->property_group_id;
        $this->rentouts['property_type_id'] = $property->property_type_id;
        $this->dispatch('PropertyAutoFill', [
            'property_group_id' => $this->rentouts['property_group_id'],
            'group_name' => $property->building?->group?->name,
            'property_building_id' => $property->property_building_id,
            'building_name' => $property->building?->name,
            'property_type_id' => $property->property_type_id,
            'type_name' => $property->type?->name,
        ]);
        $this->rentCalculator();
    }
}
```

### Changes to `resources/views/livewire/rent-out/sale/page.blade.php`

#### Replaced Manual `<select>` Elements

All 6 manual `<select>` elements were replaced with `html()->select()` calls from Spatie Laravel HTML. This provides consistent rendering and integration with TomSelect.

#### Added `@php` Block for Edit Mode Options

When editing an existing agreement, the TomSelect dropdowns need pre-populated options. An `@php` block at the top computes these:

```blade
@php
    $groupOptions = [];
    $buildingOptions = [];
    $typeOptions = [];
    $propertyOptions = [];
    $customerOptions = [];
    $salesmanOptions = [];

    if ($table_id && isset($rentouts['property_group_id'])) {
        // ... build option arrays from loaded relationships
    }
@endphp
```

#### Added `vacant_only` Checkbox

A checkbox that filters the property TomSelect dropdown to show only vacant/empty properties:

```blade
<div class="form-check mt-2">
    <input class="form-check-input" type="checkbox" wire:model.live="vacant_only" id="vacant_only_check">
    <label class="form-check-label small" for="vacant_only_check">Show vacant only</label>
</div>
```

#### Added Customer & Employee Select Components

```blade
<x-select.customerSelect />
<x-select.employeeSelect />
```

#### Added JavaScript Event Listeners

Four JS event listeners were added:

1. **`RentOutSelectValues`** — Pre-fills all TomSelect dropdowns when loading an existing agreement for edit
2. **`PropertyAutoFill`** — Auto-fills group, building, and type dropdowns when a property is selected
3. **Edit Customer button** — Opens customer edit in new tab
4. **`vacant_only` checkbox** — Updates the property TomSelect URL to filter by vacancy status

#### Changed Element IDs

Element IDs were changed from `sale_*` prefix to standard IDs matching the TomSelect component conventions (e.g., `property_group_id`, `property_building_id`, etc.).

---

## Part 2: Interactive Management Tabs for View Pages

### User Request

The user provided 4 screenshots from the accounts project showing:
1. Payment Terms tab with DataTable and action buttons
2. Single Payment Term modal with label dropdown
3. Multiple Payment Term modal with generated preview table
4. Pay Selected modal with Non-Cheque and Cheque tables

User said: *"this have these button for every tab contains these kinds of actions there so consider that also"*

### What Was Built

#### Management Tabs Section

A tabbed navigation with 9 tabs inside a card component:

| Tab | Icon | Content |
|-----|------|---------|
| Payment | `fa-credit-card` | Journal entries table (date, category, due date, payment mode, credit/debit, remark) |
| Payment Terms | `fa-calendar` | Action buttons + full CRUD table with checkboxes and row coloring |
| Utilities | `fa-bolt` | Utility terms table (conditional on rental agreements only) |
| Services | `fa-cogs` | Services table (name, amount, description) |
| Cheques | `fa-check-square-o` | Cheques table with status badges |
| Security | `fa-shield` | Security deposits table with type/status badges |
| Extend | `fa-plus-circle` | Extensions table (start/end date, rent, payment mode) |
| Notes | `fa-file-text-o` | Add note input + notes list with creator name and delete |
| Transactions | `fa-exchange` | Transaction journal table |

#### Payment Terms Tab — Action Buttons

```blade
<div class="d-flex flex-wrap gap-2 mb-3">
    <button onclick="toggleSelectAllTerms()">Select All</button>
    <button onclick="deselectAllTerms()">Deselect All</button>
    <button onclick="deleteSelectedTerms()">Delete Selected</button>
    <button wire:click="openSingleTermModal">Add Single Term</button>
    <button wire:click="openMultipleTermModal">Add Multiple Term</button>
    <button onclick="paySelectedTerms()">Pay Selected</button>
    <span class="disabled">{{ count }} rows</span>
</div>
```

#### Payment Terms Table Features

- Header checkbox for select all
- Per-row checkboxes with class `.term-checkbox`
- Row coloring based on `$term->paid_flag`:
  - **Paid** → `table-success` (green)
  - **Partially Paid** → `table-info` (light blue)
  - **Pending** (overdue) → `table-danger` (red)
  - **Current Pending** → default (no color)
- Columns: checkbox, #, Date, Label, Rent/Installment, Discount, Amount, Paid, Balance, Action (edit/delete)
- Footer row with totals in red bold

#### Single Payment Term Modal

Fields:
- **Date** (required) — `wire:model="singleTerm.due_date"`
- **Label** — Dropdown from `paymentTermLabels()` helper
- **Rent/Amount** (required) — `wire:model="singleTerm.amount"`
- **Discount** — `wire:model="singleTerm.discount"`
- **Remark** — `wire:model="singleTerm.remarks"`

Handles both create and edit modes. Title shows "Edit Payment Term" or "Single Payment Term" based on `$editingTermId`.

#### Multiple Payment Term Modal

Top section shows agreement info table:
- Rent/Sale Price
- No Of Terms
- Payment Frequency
- Start Date
- End Date (with note: "GENERATE DATES ONLY UP TO THIS DATE")

Form fields (all `wire:model.live` for real-time regeneration):
- **From Date** — Auto-calculated from last existing term + 1 frequency unit, or agreement start date
- **No Of Terms** — Defaults to `$rentOut->no_of_terms`
- **Rent/Amount** — Defaults to `$rentOut->rent`

Generated preview table (scrollable, max-height 300px):
- #, Date, Rent/Amount, Discount
- Updates in real-time as form fields change
- Respects end date (won't generate terms past it)
- Save button disabled when no terms generated

#### Pay Selected Modal

Header fields:
- **Date** — Defaults to today
- **Payment Mode** — Dropdown from `paymentModeOptions()`
- **Remark** — Free text

Table per selected term:
- #, Date, Customer, Property, Balance, Payment Mode (editable dropdown), Amount (editable input), Remark (editable input)
- Each row defaults amount to full balance

#### JavaScript Helpers

```javascript
function getSelectedTermIds() {
    // Collects checked .term-checkbox values as integer array
}

function toggleSelectAllTerms() {
    // Toggles all checkboxes + syncs header checkbox
}

function deselectAllTerms() {
    // Unchecks all checkboxes + header checkbox
}

function deleteSelectedTerms() {
    // Validates selection, confirms, calls @this.deleteSelectedTerms(ids)
}

function paySelectedTerms() {
    // Validates selection, calls @this.openPaySelectedModal(ids)
}
```

### Design Decisions

| Accounts Project | Project Manager |
|-----------------|-----------------|
| jQuery DataTables with server-side processing | Livewire-native tables |
| Bootstrap jQuery `modal('show')` calls | Livewire boolean state properties (`$showSingleTermModal`, etc.) |
| Separate Livewire sub-components per modal | Single component with trait, modals in blade partials |
| `@this.deleteSelectedTerms(ids)` via jQuery | Same pattern but with vanilla JS |

---

## Part 3: DRY Refactor of Rent/View and Sale/View

### User Request

*"use dry method both rent/view and sale view should share the same component but have the variable different right?"*

### Approach

Since both `Rent/View.php` and `Sale/View.php` share ~95% identical logic, the solution uses:
1. **PHP Trait** for shared Livewire component logic
2. **Blade `@include` partials** for shared view templates
3. **`$isRental` variable** for conditional rendering in shared partials

### Created: Shared Trait `HasPaymentTermManagement`

**File:** `app/Livewire/RentOut/Concerns/HasPaymentTermManagement.php` (365 lines)

Contains ALL shared properties and methods. Each child class only needs to:
1. `use HasPaymentTermManagement;`
2. Call `$this->loadRentOut($id)` and `$this->resetSingleTerm()` in `mount()`
3. Override `defaultTermLabel()` to return the appropriate label

#### Properties

```php
public $rentOut;

// Single Term Modal
public $showSingleTermModal = false;
public $editingTermId = null;
public $singleTerm = [];

// Multiple Term Modal
public $showMultipleTermModal = false;
public $multipleTermFromDate;
public $multipleTermNoOfTerms;
public $multipleTermRent;
public $multipleTermList = [];

// Pay Selected Modal
public $showPaySelectedModal = false;
public $selectedTermIds = [];
public $payDate;
public $payPaymentMode = 'cash';
public $payRemark = '';
public $cashTerms = [];

// Note
public $newNote = '';
```

#### Methods Summary

| Category | Methods |
|----------|---------|
| Data Loading | `loadRentOut($id)` |
| Single Term | `resetSingleTerm()`, `openSingleTermModal()`, `editPaymentTerm($id)`, `saveSingleTerm()` |
| Multiple Terms | `openMultipleTermModal()`, `updatedMultipleTermFromDate()`, `updatedMultipleTermNoOfTerms()`, `updatedMultipleTermRent()`, `generateMultipleTermList()`, `getPaymentFrequency($frequency)`, `saveMultipleTerms()` |
| Delete | `deletePaymentTerm($id)`, `deleteSelectedTerms($ids)` |
| Pay Selected | `openPaySelectedModal($ids)`, `submitPayment()` |
| Notes | `addNote()`, `deleteNote($id)` |

#### `loadRentOut()` — Eager Loading

```php
public function loadRentOut($id = null)
{
    $this->rentOut = RentOut::with([
        'customer', 'property', 'building', 'group', 'type', 'salesman',
        'paymentTerms', 'securities', 'cheques', 'extends',
        'notes.creator', 'services', 'utilities',
        'utilityTerms.utility', 'journals',
    ])->find($id ?? $this->rentOut->id);
}
```

#### `generateMultipleTermList()` — Date Generation Logic

```php
public function generateMultipleTermList()
{
    $this->multipleTermList = [];
    $frequency = $this->rentOut->payment_frequency ?? 'Monthly';
    [$unit, $multiplier] = $this->getPaymentFrequency($frequency);

    for ($i = 0; $i < $this->multipleTermNoOfTerms; $i++) {
        $increment = $i * $multiplier;
        $date = date('Y-m-d', strtotime("+{$increment} {$unit}", strtotime($this->multipleTermFromDate)));

        if (strtotime($date) <= strtotime($this->rentOut->end_date)) {
            $this->multipleTermList[] = [
                'date' => $date,
                'rent' => $this->multipleTermRent,
                'discount' => 0,
            ];
        }
    }
}
```

#### `getPaymentFrequency()` — Frequency Mapping

```php
private function getPaymentFrequency($frequency): array
{
    return match ($frequency) {
        'Daily' => ['days', 1],
        'Weekly' => ['weeks', 1],
        'Bi-Weekly' => ['weeks', 2],
        'Monthly' => ['months', 1],
        'Quarterly' => ['months', 3],
        'Half Yearly' => ['months', 6],
        'Yearly' => ['years', 1],
        'One Time' => ['years', 100],
        default => ['months', 1],
    };
}
```

#### `openMultipleTermModal()` — Smart From-Date Calculation

```php
public function openMultipleTermModal()
{
    $this->multipleTermNoOfTerms = $this->rentOut->no_of_terms ?? 12;
    $this->multipleTermRent = $this->rentOut->rent ?? 0;

    // If terms exist, start from last term + 1 frequency unit
    $lastTerm = $this->rentOut->paymentTerms->sortByDesc('due_date')->first();
    if ($lastTerm) {
        $this->multipleTermFromDate = date('Y-m-d', strtotime('+1 month', strtotime($lastTerm->due_date)));
    } else {
        // Otherwise use agreement start date with collection starting day
        $day = str_pad($this->rentOut->collection_starting_day ?? 1, 2, '0', STR_PAD_LEFT);
        $this->multipleTermFromDate = date("Y-m-{$day}", strtotime($this->rentOut->start_date));
    }

    $this->generateMultipleTermList();
    $this->showMultipleTermModal = true;
}
```

#### `submitPayment()` — Payment Processing

```php
public function submitPayment()
{
    try {
        DB::beginTransaction();
        foreach ($this->cashTerms as $cashTerm) {
            $term = RentOutPaymentTerm::find($cashTerm['id']);
            if ($term && $cashTerm['amount'] > 0) {
                $term->paid = ($term->paid ?? 0) + $cashTerm['amount'];
                $term->payment_mode = $cashTerm['payment_mode'] ?? $this->payPaymentMode;
                $term->paid_date = $this->payDate;
                $term->save();
                // Model's saving hook auto-calculates balance and sets status
            }
        }
        DB::commit();
        $this->showPaySelectedModal = false;
        $this->loadRentOut();
        $this->dispatch('success', ['message' => 'Payment submitted successfully.']);
    } catch (\Exception $e) {
        DB::rollback();
        $this->dispatch('error', ['message' => $e->getMessage()]);
    }
}
```

### Refactored Livewire Components

#### `app/Livewire/RentOut/Rent/View.php` (27 lines)

```php
<?php

namespace App\Livewire\RentOut\Rent;

use App\Livewire\RentOut\Concerns\HasPaymentTermManagement;
use Livewire\Component;

class View extends Component
{
    use HasPaymentTermManagement;

    public function mount($id)
    {
        $this->loadRentOut($id);
        $this->resetSingleTerm();
    }

    protected function defaultTermLabel(): string
    {
        return 'rent payment';
    }

    public function render()
    {
        return view('livewire.rent-out.rent.view');
    }
}
```

#### `app/Livewire/RentOut/Sale/View.php` (27 lines)

```php
<?php

namespace App\Livewire\RentOut\Sale;

use App\Livewire\RentOut\Concerns\HasPaymentTermManagement;
use Livewire\Component;

class View extends Component
{
    use HasPaymentTermManagement;

    public function mount($id)
    {
        $this->loadRentOut($id);
        $this->resetSingleTerm();
    }

    protected function defaultTermLabel(): string
    {
        return 'installment';
    }

    public function render()
    {
        return view('livewire.rent-out.sale.view');
    }
}
```

### Created: Shared Blade Partials

#### `partials/management-tabs.blade.php` (440 lines)

Full management tabs card with all 9 tab panes. Uses `$isRental` variable (set in the parent view's `@php` block) for:
- Conditionally showing the Utilities tab
- Switching "Rent" vs "Installment" column header in Payment Terms

#### `partials/payment-term-modals.blade.php` (212 lines)

All 3 modals. Uses `$isRental` for:
- "Rent" vs "Amount" label in Single Term modal
- "RENT" vs "SALE PRICE" in Multiple Term agreement info
- "Rent" vs "Amount" column header in generated preview

#### `partials/payment-term-scripts.blade.php` (47 lines)

JavaScript checkbox helper functions wrapped in `@push('scripts')`.

### Refactored View Blade Files

Both `rent/view.blade.php` (347 lines) and `sale/view.blade.php` (324 lines) now follow this structure:

```blade
<div>
    @if($rentOut)
        @php
            $isRental = $rentOut->agreement_type?->value === 'rental';
            // ... compute totals, days remaining, etc.
        @endphp

        {{-- Header (breadcrumbs, status badge, edit button) --}}
        {{-- UNIQUE per module: routes, permissions, edit links --}}

        {{-- 3-Column Overview Cards --}}
        {{-- UNIQUE per module: different fields, labels, print links --}}

        {{-- Remarks Section --}}

        {{-- SHARED via @include --}}
        @include('livewire.rent-out.partials.management-tabs')
        @include('livewire.rent-out.partials.payment-term-modals')
    @else
        {{-- Not found message --}}
    @endif
</div>

@include('livewire.rent-out.partials.payment-term-scripts')
```

#### Differences Between Rent and Sale Views

| Aspect | Rent View | Sale View |
|--------|-----------|-----------|
| Breadcrumb routes | `property::rent::index` | `property::sale::index` |
| Breadcrumb label | "Rental Agreements" / dynamic | "Sale Agreements" |
| Permission gate | `rent out.edit` | `rent out lease.edit` |
| Edit routes | `property::rent::booking.create` / `property::rent::create` | `property::sale::booking.create` / `property::sale::create` |
| Agreement type row label | "Booking Type:" / "Agreement Type:" (dynamic) | "Agreement Type:" |
| Agreement type row value | `$rentOut->booking_type` / `$rentOut->agreement_type?->label()` | `$rentOut->agreement_type?->label()` |
| Free Months row | Shown | Not shown |
| Price label | "Monthly Rent:" / "Sale Price:" (dynamic) | "Sale Price:" |
| SOA Utilities print link | Shown | Not shown |
| Payment Status "Rent" label | Dynamic: "Rent" / "Sale" | "Sale" |
| Utilities row in summary | Shown when `$isRental` | Not shown |
| Not found message | "Rental agreement not found." | "Sale agreement not found." |

---

## Supporting Changes

### Migration: Add Columns to `rent_out_payment_terms`

**File:** `database/migrations/2026_03_12_000001_add_label_and_paid_to_rent_out_payment_terms_table.php`

```php
Schema::table('rent_out_payment_terms', function (Blueprint $table) {
    $table->string('label')->nullable()->after('rent_out_id');
    $table->decimal('paid', 16, 2)->default(0)->after('total');
    $table->decimal('balance', 16, 2)->default(0)->after('paid');
    $table->string('payment_mode')->nullable()->after('status');
    $table->string('cheque_no')->nullable()->after('payment_mode');
});
```

### Model: `RentOutPaymentTerm`

**File:** `app/Models/RentOutPaymentTerm.php`

#### Full Fillable List

```php
protected $fillable = [
    'tenant_id', 'branch_id', 'rent_out_id', 'label',
    'amount', 'discount', 'total', 'paid', 'balance',
    'due_date', 'paid_date', 'status', 'payment_mode',
    'cheque_no', 'remarks', 'created_by',
];
```

#### Casts

```php
protected $casts = [
    'due_date' => 'date',
    'paid_date' => 'date',
    'amount' => 'decimal:2',
    'discount' => 'decimal:2',
    'total' => 'decimal:2',
    'paid' => 'decimal:2',
    'balance' => 'decimal:2',
];
```

#### Auto-Calculation Saving Hook

```php
protected static function booted(): void
{
    static::saving(function (self $model) {
        $model->total = ($model->amount ?? 0) - ($model->discount ?? 0);
        $model->balance = $model->total - ($model->paid ?? 0);
        if ($model->balance <= 0 && $model->total > 0) {
            $model->status = 'paid';
            $model->paid_date = $model->paid_date ?? now();
        }
    });
}
```

#### Paid Flag Accessor

```php
public function getPaidFlagAttribute(): string
{
    if ($this->status === 'paid') return 'Paid';
    if ($this->paid > 0) return 'Partially Paid';
    if ($this->due_date && $this->due_date->isPast()) return 'Pending';
    return 'Current Pending';
}
```

#### Scopes

```php
public function scopePending($query)  { return $query->where('status', 'pending'); }
public function scopePaid($query)     { return $query->where('status', 'paid'); }
public function scopeOverdue($query)  { return $query->where('status', 'pending')->where('due_date', '<', now()); }
```

### Helper Functions

**File:** `app/Helpers/helper.php`

#### `paymentTermLabels()`

```php
if (! function_exists('paymentTermLabels')) {
    function paymentTermLabels(): array
    {
        return [
            'rent payment' => 'Rent Payment',
            'installment' => 'Installment',
            'down payment' => 'Down Payment',
            'handover payment' => 'Handover Payment',
            'balloon payment' => 'Balloon Payment',
            'booking amount' => 'Booking Amount',
            'registration' => 'Registration',
            'maintenance' => 'Maintenance',
            'other' => 'Other',
        ];
    }
}
```

#### `paymentModeOptions()`

Used in the Pay Selected modal for payment mode dropdowns.

### Action Classes

All payment term operations go through Action classes following the project's pattern:

| Action | File | Purpose |
|--------|------|---------|
| `CreateAction` | `app/Actions/RentOut/PaymentTerm/CreateAction.php` | Create a new payment term |
| `UpdateAction` | `app/Actions/RentOut/PaymentTerm/UpdateAction.php` | Update an existing payment term |
| `DeleteAction` | `app/Actions/RentOut/PaymentTerm/DeleteAction.php` | Soft-delete a payment term |

All actions return: `['success' => bool, 'message' => string, 'data' => $model]`

---

## Final File Tree & Results

### File Tree

```
app/
├── Livewire/RentOut/
│   ├── Concerns/
│   │   └── HasPaymentTermManagement.php        ← Shared trait (365 lines)
│   ├── Rent/
│   │   ├── Page.php                            ← Rent list/form page
│   │   └── View.php                            ← 27 lines, uses trait
│   └── Sale/
│       ├── Page.php                            ← Sale list/form page (UPDATED)
│       └── View.php                            ← 27 lines, uses trait
├── Actions/RentOut/PaymentTerm/
│   ├── CreateAction.php
│   ├── UpdateAction.php
│   └── DeleteAction.php
├── Models/
│   └── RentOutPaymentTerm.php                  ← Model with auto-calc + paid_flag
└── Helpers/
    └── helper.php                              ← paymentTermLabels(), paymentModeOptions()

resources/views/livewire/rent-out/
├── partials/
│   ├── management-tabs.blade.php               ← 440 lines (9 tabs, shared)
│   ├── payment-term-modals.blade.php           ← 212 lines (3 modals, shared)
│   └── payment-term-scripts.blade.php          ← 47 lines (JS helpers, shared)
├── rent/
│   ├── page.blade.php                          ← Rent form page
│   └── view.blade.php                          ← 347 lines (unique header + @includes)
└── sale/
    ├── page.blade.php                          ← Sale form page (UPDATED)
    └── view.blade.php                          ← 324 lines (unique header + @includes)

database/migrations/
└── 2026_03_12_000001_add_label_and_paid_to_rent_out_payment_terms_table.php
```

### Before vs After — View Files

| File | Before (lines) | After (lines) |
|------|----------------|---------------|
| `rent/view.blade.php` | ~1,040 | 347 |
| `sale/view.blade.php` | ~970 | 324 |
| `Rent/View.php` | Large with inline methods | 27 |
| `Sale/View.php` | Large with duplicated methods | 27 |
| `partials/management-tabs.blade.php` | — | 440 (shared) |
| `partials/payment-term-modals.blade.php` | — | 212 (shared) |
| `partials/payment-term-scripts.blade.php` | — | 47 (shared) |
| `HasPaymentTermManagement.php` | — | 365 (shared) |
| **Total** | **~2,010** | **~1,370 (~32% reduction)** |

### Key Design Decisions

1. **Trait over base class** — Livewire components already extend `Component`, so a trait is the correct PHP pattern for horizontal code sharing.

2. **`$isRental` variable** — Computed in each view's `@php` block from `$rentOut->agreement_type?->value === 'rental'`. Passed implicitly to shared partials via Blade's variable scoping.

3. **`defaultTermLabel()` method** — Overridden by each child class to set the label for bulk-generated terms: `'rent payment'` for rental, `'installment'` for sale.

4. **Header sections kept separate** — The overview cards have enough meaningful differences (routes, permissions, conditional fields, print links) that sharing them via another partial would add complexity without proportional benefit.

5. **Livewire modal state** — Instead of Bootstrap's jQuery `modal('show')` pattern from the accounts project, modals are controlled via boolean properties (`$showSingleTermModal`, `$showMultipleTermModal`, `$showPaySelectedModal`). This integrates cleanly with Livewire's reactivity.

6. **Action class pattern** — All database operations delegate to Action classes (`CreateAction`, `UpdateAction`, `DeleteAction`) that return standardized response arrays. The trait wraps these in try/catch with DB transactions.

7. **Model auto-calculation** — The `RentOutPaymentTerm` model's `saving` hook automatically computes `total`, `balance`, and `status`, ensuring data consistency regardless of which code path creates/updates a term.

---

## Pending Items

- Run `php artisan migrate` to apply the new migration
- Test the full flow through the UI for both Rent and Sale views
- Verify all tabs render correctly
- Test single term create/edit, multiple term generation, and pay selected workflows
- Verify row coloring works correctly based on payment status
