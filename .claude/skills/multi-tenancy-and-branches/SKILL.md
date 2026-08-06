---
name: multi-tenancy-and-branches
description: "Use whenever writing an Eloquent query, adding a model, writing a migration, defining validation rules with unique constraints, building a report or export, or debugging 'data from another tenant/branch is visible' or 'the record exists but the query returns nothing'. Covers the tenant_id column, BelongsToTenant, TenantScope, AssignedBranchScope, CurrentBranchScope, session('branch_id'), the withoutTenant/withTenant escape hatches, and tenant-safe uniqueness rules. Read this before any query that crosses tenants, branches, raw SQL, joins, or queued jobs."
---

# Multi-Tenancy and Branch Scoping

This is a multi-tenant, multi-branch ERP. Almost every table carries `tenant_id`, most transactional tables carry `branch_id`, and both are enforced by global scopes that are easy to bypass by accident. Getting this wrong leaks one customer's data into another's screen — treat it as a correctness *and* security concern.

## Two independent axes

| Axis | Column | Enforced by | Source of truth |
| --- | --- | --- | --- |
| Tenant | `tenant_id` | `TenantScope` global scope via the `BelongsToTenant` trait | `App\Services\TenantService::getCurrentTenantId()` |
| Branch | `branch_id` | `AssignedBranchScope` global scope (per-model, opt-in) | `Auth::user()->branches` |
| Active branch | `branch_id` | `CurrentBranchScope::apply($query)` — a manual helper, **not** a global scope | `session('branch_id')` |

`AssignedBranchScope` limits rows to every branch the user is assigned to. `CurrentBranchScope` narrows further to the one branch they have selected in the UI. They are different questions; don't substitute one for the other.

## Adding a model

```php
use App\Traits\BelongsToTenant;

class Thing extends Model implements AuditableContracts
{
    use Auditable;
    use BelongsToTenant;
    use SoftDeletes;

    protected $fillable = ['tenant_id', /* … */];

    protected static function booted()
    {
        static::addGlobalScope(new AssignedBranchScope());  // only if the table has branch_id
    }
}
```

`BelongsToTenant` both applies `TenantScope` and auto-fills `tenant_id` on create, so never set `tenant_id` by hand in an action. Put `tenant_id` in `$fillable` anyway — some seeders and imports pass it explicitly. 91 of the 134 models use this trait; a new transactional model almost certainly should.

Migrations for tenant tables need `tenant_id` plus an index — the global scope adds `where tenant_id = ?` to every single query against the table, so an unindexed `tenant_id` degrades every screen at once.

## Uniqueness must be scoped

A plain `Rule::unique()` compares across all tenants and will reject a value another tenant already used. Always scope it:

```php
public static function rules($id = 0, $merge = [])
{
    $tenantId = self::getCurrentTenantId();

    return array_merge([
        'invoice_no' => ['required', Rule::unique(self::class, 'invoice_no')->where('tenant_id', $tenantId)->ignore($id)],
        'branch_id' => ['required'],
        'date' => ['required'],
    ], $merge);
}
```

`getCurrentTenantId()` comes from the trait. The same applies to database-level unique indexes: make them composite with `tenant_id`.

## Escaping the scope

`TenantScope` registers two macros. Use them deliberately and never in a request-scoped read path:

```php
Sale::withoutTenant()->count();      // cross-tenant — admin/console/reporting only
Sale::withTenant($tenantId)->get();  // explicitly a different tenant
```

Reach for these in console commands, tenant-provisioning code, and platform-wide dashboards. If you find yourself wanting one inside a Livewire component or a V1 controller, the requirement is probably wrong.

## Where scoping silently disappears

These are the real failure modes in this codebase:

- **`DB::table(...)`, `DB::select(...)`, raw joins.** No model, no global scope. Add `where('tenant_id', …)` yourself for every table in the statement, including joined ones.
- **Queued jobs and scheduled commands.** They run outside the web request, so `TenantService` may resolve to nothing and `session('branch_id')` is empty. Pass `tenant_id`/`branch_id` into the job constructor and filter explicitly — see `app/Jobs/Export/`.
- **`insert()` bulk writes.** `JournalEntry::insert()` bypasses model events, so `tenant_id` is *not* auto-filled. `app/Actions/Journal/CreateAction.php` copies it onto every row by hand — do the same for any bulk insert you add.
- **Relationship queries via `withoutGlobalScope`.** If you disable one scope, you disable it for that query only; be explicit about which one.
- **Aggregates in reports.** `sum()`/`count()` over a raw builder is the most common leak. Start from the model, not `DB::table`.
- **Cache keys.** A tenant-scoped value cached under a global key leaks to every tenant — whoever warms it first defines it for everyone — and `Cache::forget` on a global key makes the value *flap* between tenants rather than fixing it. Cache per-tenant values through `tenant_cache('key')` / `App\Support\TenantCache` (keys are suffixed `:tenant:{id}` and resolved lazily), never at boot: `AppServiceProvider::boot()` runs before any tenant is resolved, so anything tenant-dependent computed there is a guess.

## Branch handling in writes

Actions default the branch from the session and let the caller override:

```php
$data['branch_id'] = $data['branch_id'] ?? session('branch_id');
```

Livewire tables seed their filter the same way in `mount()` (`$this->branch_id = session('branch_id');`). On the V1 API there is no session — the branch comes from `$user->default_branch_id`, and a user without one is an error, not a fallback (`app/Actions/V1/Sale/CreateAction.php`).

## Verifying a change

When you touch a query, ask: does this run as a logged-in web user, a queued job, or an API token? Each has a different tenant/branch source. If you cannot answer, the query is not safe yet.
