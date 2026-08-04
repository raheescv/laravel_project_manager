---
name: mobile-api-v1
description: "Use when adding or changing any endpoint consumed by the Flutter apps — routes/api_v1.php, routes/api_v1_technician.php, app/Http/Controllers/Api/V1/, app/Actions/V1/, app/Http/Requests/V1/, app/Http/Resources/V1/. Covers the thin-controller + V1-action-delegates-to-web-action pattern, ApiResponseTrait envelopes, Sanctum auth and branch resolution without a session, Form Requests, Resources, Scramble docs annotations, and API logging. Read before exposing existing web functionality to mobile or debugging a mobile request that behaves differently from the web UI."
---

# Mobile API (v1)

`/api/v1` serves the Flutter POS app (`mobileApp/`) and the technician app (`technicianApp/`). Its defining rule: **V1 never reimplements domain logic.** A V1 action translates the mobile payload into the shape the existing web action expects and delegates, so stock movements and journal postings run identically for a mobile sale and a web sale.

## The four layers

```
routes/api_v1.php                      # sanctum-protected route groups
app/Http/Requests/V1/<Module>/…Request # validation of the mobile payload
app/Http/Controllers/Api/V1/…Controller# thin: call action, wrap response
app/Actions/V1/<Module>/…Action        # translate + delegate to App\Actions\<Module>\…
app/Http/Resources/V1/<Module>/…Resource# response shaping
```

## Controllers are thin

```php
#[Group('Mobile - Sales')]
class SaleController extends Controller
{
    use ApiResponseTrait;

    public function index(ListAction $action, IndexRequest $request): JsonResponse
    {
        try {
            $result = $action->execute($request);

            return $this->sendSuccess($result, 'Sales retrieved successfully');
        } catch (ValidationException $e) {
            return $this->sendValidationError($e->errors(), 'Validation failed');
        } catch (\Exception $e) {
            return $this->sendServerError('Failed to retrieve sales: '.$e->getMessage());
        }
    }
}
```

- Actions are **method-injected**, not `new`-ed, on this layer.
- Every response goes through `App\Traits\ApiResponseTrait`: `sendSuccess`, `sendError`, `sendValidationError` (422), `sendNotFoundError` (404), `sendUnauthorizedError` (401), `sendForbiddenError` (403), `sendServerError` (500). The envelope is always `{success, data, message}` — the Flutter clients parse exactly this.
- `respondWithActionResult($result)` converts a web action's `['success','message','data']` array straight into the envelope when you call one directly.
- Catch `ModelNotFoundException` separately so mobile gets a 404 rather than a 500.
- `#[Group('Mobile - …')]` and the docblock summary feed Scramble's generated API docs — keep them accurate.

## V1 actions translate, then delegate

```php
class CreateAction
{
    public function execute(StoreRequest $request): Sale
    {
        $user = $request->user();
        $branchId = $user->default_branch_id;
        if (! $branchId) {
            throw new RuntimeException('Your account is not assigned to a branch.');
        }

        $customer = $this->resolveCustomer(...);
        $items    = $this->buildItems(...);
        $this->guardAgainstDuplicate(...);

        $response = (new SaleCreateAction())->execute($data, $user->id);   // the web action
        // …
    }
}
```

Note the differences from the web action layer:

- **V1 actions may return a model and may throw** — the controller's try/catch is the boundary. They do not return the `['success', …]` array.
- **There is no session.** `session('branch_id')` is empty on API requests; the branch comes from `$user->default_branch_id`, and its absence is an error rather than a silent default. Same for anything else the web reads from the session.
- **Mobile retries.** Create paths guard against accidental duplicates by looking for an identical recent record (`DUPLICATE_WINDOW_MINUTES` in `app/Actions/V1/Sale/CreateAction.php`). Any new create endpoint that a user can double-tap needs the same guard.
- **Employee scoping.** Non-admin employees see only their own records — `ListAction` forces `created_by = self` when `type === 'employee' && ! is_admin`, overriding any `mine_only` flag. Apply the same rule to new list endpoints.
- Significant calls are recorded to `ApiLog` (`startApiLog`) so a failed mobile sale can be replayed.

## Requests and Resources

Validation lives in `app/Http/Requests/V1/` — this is the one place Form Requests are used; the web side validates via `Model::rules()` + `validationHelper()`. Mobile payloads use camelCase keys (`customerName`, `totalPayment`, `paymentMethod`), so read them with `$request->validated('customerName')` and map to the snake_case columns in the V1 action.

Responses shape through `app/Http/Resources/V1/`. Never return an Eloquent model directly — the Flutter models are hand-written and break on shape drift. When you add a field, add it to the Resource *and* the corresponding Dart model.

## Routes

```php
Route::middleware('auth:sanctum')->prefix('v1')->group(function (): void {
    Route::apiResource('sale', SaleController::class);
});
```

`routes/api_v1.php` is the POS app; `routes/api_v1_technician.php` is the technician app, scoped to `technician_id = auth()->id()`. Public storefront endpoints require a tenant header/param — they are not session-scoped, so tenant resolution is explicit.

## Checklist for a new endpoint

1. Does a web action already do this? Delegate to it; do not copy its logic.
2. Form Request for input, Resource for output, both under `V1/`.
3. Resolve tenant/branch/user without touching the session.
4. Duplicate guard on creates; employee self-scope on lists.
5. `#[Group]` + docblock so it appears correctly in the generated docs.
6. Update the matching Dart model/repository in `mobileApp/` or `technicianApp/` in the same change.
