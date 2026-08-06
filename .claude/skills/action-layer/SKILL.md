---
name: action-layer
description: "Use when creating, editing, or reviewing anything under app/Actions/ — the single-purpose classes that hold this application's write logic (CreateAction, UpdateAction, DeleteAction, JournalEntryAction, StockUpdateAction, SyncAccountingAction, and their nested child actions). Also use when a Livewire component, controller, job, or console command needs to persist domain data, when deciding where a database transaction belongs, or when an action's error handling or return shape needs changing. Covers the action contract, validation, child-action composition, and the caller's responsibilities."
---

# Action Layer

Every write in this application goes through an action class under `app/Actions/`. Controllers and Livewire components never call `Model::create()` for a domain entity directly — they build an array and hand it to an action. There are ~380 of these; follow the established shape exactly.

## Directory shape

```
app/Actions/<Module>/CreateAction.php          # the aggregate root
app/Actions/<Module>/UpdateAction.php
app/Actions/<Module>/DeleteAction.php
app/Actions/<Module>/<Child>/CreateAction.php  # line items, payments, documents…
app/Actions/V1/<Module>/CreateAction.php       # mobile API — see the mobile-api-v1 skill
```

Child entities get their own subdirectory with their own `CreateAction`/`UpdateAction`/`DeleteAction` (`app/Actions/Sale/Item/`, `app/Actions/Sale/Payment/`, `app/Actions/RentOut/Cheque/`). Side effects that are not CRUD get a verb name: `JournalEntryAction`, `StockUpdateAction`, `VacateAction`, `ReverseTransactionAction`.

## The contract

An action exposes `execute()`, catches everything, and returns an array. It never returns a bare model and never lets an exception escape.

```php
class CreateAction
{
    public $model;

    public $userId;

    public function execute($data, $userId)
    {
        $this->userId = $userId;
        try {
            $data['branch_id'] = $data['branch_id'] ?? session('branch_id');
            $data['created_by'] = $this->userId;

            validationHelper(Sale::rules(), $data);
            $this->model = Sale::create($data);

            $this->items($data['items']);

            $return['success'] = true;
            $return['message'] = 'Successfully Created Sale';
            $return['data'] = $this->model;
        } catch (\Throwable $th) {
            $return['success'] = false;
            $return['message'] = $th->getMessage();
        }

        return $return;
    }
}
```

Rules that hold everywhere:

- **Return `['success' => bool, 'message' => string, 'data' => mixed]`.** This one is universal — all 216 CRUD actions do it. `message` is user-facing; it is what the caller toasts. Write it as a sentence a tenant would understand, not a stack trace.
- **The `$userId` parameter depends on whether the table records an actor.** Two shapes coexist and you must match the module you are in, not a single rule:
  - Transactional modules whose tables carry `created_by`/`updated_by`/`deleted_by` (Sale, Purchase, SaleReturn, RentOut, Journal entries…) take it explicitly: `execute($data, $userId)` / `execute($data, $id, $userId)` / `execute($id, $userId)`.
  - Settings and lookup modules whose tables have no actor columns (Unit, Brand, Category, Role, Branch, Designation, the whole of `app/Actions/Settings/`) take `execute($data)` / `execute($data, $id)` / `execute($id)`. This is the **majority** — 169 of 216. Do not add a `$userId` a table has nowhere to put.

  Check the migration before choosing. Whichever shape applies, the actor is **passed in, never read inside** — no `Auth::id()` in an action body, so jobs, console commands and API callers can drive it. Roughly 14 CRUD actions still violate this (`app/Actions/Package/*` is the worst cluster); they are bugs to fix when you touch them, not a pattern to copy.
- **`catch (\Throwable $th)`** and convert to `success => false`. Never rethrow out of `execute()`. Read-only query actions (`GetAction`, `GetListAction`, `Generate*Action`) are the documented exception — they may throw and return a value directly.
- **Validate with `validationHelper(Model::rules($id), $data)`** (`app/Helpers/helper.php:517`). It throws on the first failure, which the catch block turns into the failure message. Validation rules live on the model as `public static function rules($id = 0, $merge = [])`, not in a Form Request — Form Requests are only used on the V1 API.
- **Stamp the actor**: `created_by` on create, `updated_by` on update, `deleted_by` on delete (set it *before* the soft delete so the column survives).

## Composing child actions

Private helper methods loop the children and call the child action. A failing child throws, which unwinds to the parent's catch:

```php
private function items($data)
{
    foreach ($data as $value) {
        $value['sale_id'] = $this->model->id;
        $response = (new Item\CreateAction())->execute($value, $this->userId);
        if (! $response['success']) {
            throw new Exception($response['message'], 1);
        }
    }
}
```

Check `success` on **every** nested action call and throw its message. Silently ignoring a child failure leaves a half-written aggregate, because the transaction is not the action's to roll back.

## Who owns the transaction depends on the caller

There are two legitimate conventions in this codebase, and which one applies is decided by **who calls the action**:

| Caller | Who owns the transaction | Why |
| --- | --- | --- |
| Livewire component | **The component** | One user gesture may drive several actions; only the component knows the full unit of work |
| V1 API controller | **The action** (`DB::transaction(...)` inside `execute()`) | One request = one action; a controller-level boundary would be pure ceremony |
| Queued job / console command | **The action** | Same reason; no interactive caller exists |
| Another action | **Neither — inherit** | Nesting creates savepoints; let the outermost boundary win |

The original core (Sale, Purchase, Journal, SaleReturn) follows the first row: those actions do **not** open transactions and rely on the Livewire caller. The newer modules (all V1 actions, Maintenance/Complaint, StockCheck, Asset, several RentOut sub-actions, Issue, Tailoring, EmployeeCommission — **32 classes**) self-transact, correctly, because their callers are controllers or jobs. Two idioms are in use and both are fine: 21 wrap the body in a `DB::transaction(fn () => …)` closure, 11 open a manual `DB::beginTransaction()` / `commit()` / `rollback()` pair. (Counting these by grep overshoots twice: `Account/BankReconciliation/UpdateDeliveredDateAction` calls `beginTransaction()` on two separate lines, and `RentOut/DeleteAction` only mentions it in a comment explaining that its *caller* owns the boundary. Count distinct files, excluding comments.) Do **not** "fix" either group onto the other convention; do not open a transaction in a caller *and* in the action it calls.

Child failures throw rather than return so the owning boundary can roll back. See `app/Livewire/Sale/Page.php:1044` for the canonical Livewire shape:

```php
try {
    DB::beginTransaction();
    $response = (new CreateAction())->execute($this->sales, Auth::id());
    if (! $response['success']) {
        throw new Exception($response['message'], 1);
    }
    DB::commit();
    $this->dispatch('success', ['message' => $response['message']]);
} catch (\Throwable $th) {
    DB::rollback();
    $this->dispatch('error', ['message' => $th->getMessage()]);
}
```

Side effects that must not be rolled back — WhatsApp sends, print dispatches, notifications — go **after** `DB::commit()`.

## Status-gated side effects

Draft records touch nothing but their own tables. Stock movement and accounting only fire when the document reaches its completed state, and each is a separate action whose result is checked:

```php
if ($this->model['status'] == 'completed') {
    $response = (new StockUpdateAction())->execute($this->model, $this->userId);
    if (! $response['success']) {
        throw new Exception($response['message'], 1);
    }
    $this->model->refresh();
    $response = (new JournalEntryAction())->execute($this->model, $this->userId);
    if (! $response['success']) {
        throw new Exception($response['message'], 1);
    }
}
```

`refresh()` between steps when a later step reads columns a previous one wrote (including generated columns like `total`/`grand_total`). Deletes and reversals have mirror actions — `JournalDeleteAction`, `ReverseTransactionAction` — never delete journal rows inline.

## Before you finish

- New module? Create the whole `CreateAction`/`UpdateAction`/`DeleteAction` set even if only one is wired up today; siblings expect it.
- Guard destructive states in the action, not the UI: `DeleteAction` for Sale refuses a completed sale (`app/Actions/Sale/DeleteAction.php`).
- Run `vendor/bin/pint <file>` on anything you touch.
