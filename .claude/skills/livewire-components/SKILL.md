---
name: livewire-components
description: "Use when creating or modifying anything under app/Livewire/ or resources/views/livewire/ — list tables, create/edit pages, view pages, tabs, and modals. Covers this application's Livewire 3 conventions: mount() seeding from Configuration and session, the DB transaction + action-call save pattern, permission guards, toast dispatch, $listeners refresh events, pagination and sorting, bulk selection, column visibility, and queued exports. Read before adding a filter, a bulk action, a tab, or a modal to an existing screen."
---

# Livewire Components

Livewire 3 drives nearly every screen (~355 components). Components are the *transaction boundary and the UI*; all domain writing is delegated to `app/Actions/` (see the action-layer skill).

## Component roles and naming

```
app/Livewire/<Module>/Table.php      # list + filters + bulk actions   → livewire/<module>/table.blade.php
app/Livewire/<Module>/Page.php       # create/edit form
app/Livewire/<Module>/View.php       # read-only detail page, hosts tabs
app/Livewire/<Module>/Tabs/*Tab.php  # a tab inside View
app/Livewire/<Module>/Tabs/*Modal.php# a modal owned by a tab
```

Views mirror the class path under `resources/views/livewire/` in kebab-case. Check the sibling component before inventing anything — `app/Livewire/RentOut/` is the fullest example of the Table/Page/View/Tabs/Modal set.

## State and mount()

Filters are plain public properties; `mount()` seeds them from tenant settings, the session, and today's date:

```php
use WithPagination;

public $search = '';
public $branch_id = '';
public $status = 'draft';
public $limit = 50;
public $selected = [];
public $selectAll = false;
public $sortField = 'sales.id';
public $sortDirection = 'desc';

protected $paginationTheme = 'bootstrap';

protected $listeners = [
    'Sale-Refresh-Component' => '$refresh',
];

public function mount()
{
    $this->status = Configuration::where('key', 'default_status')->value('value');
    $this->branch_id = session('branch_id');
    $this->from_date = date('Y-m-d');
    $this->to_date = date('Y-m-d');
}
```

- `protected $paginationTheme = 'bootstrap'` on every paginated component — the UI is Bootstrap, not Tailwind.
- Sort fields are **table-qualified** (`sales.id`) because list queries join.
- Tenant settings come from `Configuration::where('key', …)->value('value')`; JSON-valued keys are `json_decode`d in `mount()`. Never hardcode tenant wording or defaults in PHP — put it in Configuration and let Settings edit it.
- Cross-component refresh uses a namespaced event mapped to `'$refresh'`. Dispatch `'<Module>-Refresh-Component'` after a modal saves.

## The save/delete pattern

Every write method has the same skeleton: permission guard → transaction → action → check `success` → commit → toast.

```php
public function delete()
{
    abort_unless(auth()->user()?->can('sale.delete'), 403);
    try {
        DB::beginTransaction();
        if (! count($this->selected)) {
            throw new \Exception('Please select any item to delete.', 1);
        }
        foreach ($this->selected as $id) {
            $response = (new DeleteAction())->execute($id, Auth::id());
            if (! $response['success']) {
                throw new \Exception($response['message'], 1);
            }
        }
        DB::commit();
        $this->dispatch('success', ['message' => 'Successfully deleted']);
    } catch (\Throwable $th) {
        DB::rollback();
        $this->dispatch('error', ['message' => $th->getMessage()]);
    }
}
```

- **Guard with `abort_unless(auth()->user()?->can('<group>.<action>'), 403)`** inside the method. Route-level `->can()` protects the page; it does not protect a Livewire action invoked from an already-open page.
- **`DB::beginTransaction()` / `DB::commit()` / `DB::rollback()`** live here, not in the action.
- **`$this->dispatch('success'|'error', ['message' => …])`.** `resources/views/layouts/app.blade.php:272` listens for these and shows a toastr. Do not build ad-hoc flash mechanisms.
- Post-commit effects (`print-invoice` dispatch, WhatsApp send, `ResetSelectBox`) go after `DB::commit()`.
- Validation failures are thrown as `\Exception` with a human sentence — the catch turns them into the error toast. `$this->validate()` is rarely used on these screens.

## Lists, sorting, selection

`render()` builds the query with `when()` chains over the filter properties, then paginates with `$this->limit`. Reuse the existing query shape in the sibling `Table.php`; several also feed the matching `App\Exports\*Export` class with the same `$filters` array so the export and the screen agree.

Bulk selection is `$selected` + `$selectAll` with an `updatedSelectAll()` hook. Column visibility uses either the `Configuration` key `<module>_visible_column` (tenant-wide, older screens) or the `HasColumnPreferences` concern in `app/Livewire/Concerns/` backed by `user_preferences` (per-user, newer screens) — match whichever the screen already uses.

## Exports

Large exports are queued, not streamed inline:

```php
ExportSaleJob::dispatch(Auth::user());
$this->dispatch('success', ['message' => 'Export queued. You will be notified when it is ready.']);
```

The job writes to the `public` disk under `exports/` and notifies with `App\Notifications\ExportCompleted`. Small exports may still use `Excel::download(new SaleExport($filters), …)` directly — follow the sibling.

## Blade side

- Bootstrap 5 markup; vendored assets are loaded with `https_asset('assets/vendors/…')` from `layouts/app.blade.php`, not through Vite. Vite only builds the POS/React/Vue bundles.
- Searchable dropdowns are the shared TomSelect components in `resources/views/components/select/` (`<x-select.customerSelect />`, `accountSelect`, `productSelect`, …). Add a new one there rather than hand-rolling a select; they fetch from a `*::list` route.
- Icons are **Font Awesome 4.3** (`fa fa-…`) only — no `fas`/`far`/`fa-solid` prefixes.
- Wrap non-trivial interactive markup in Alpine rather than adding a JS dependency.
