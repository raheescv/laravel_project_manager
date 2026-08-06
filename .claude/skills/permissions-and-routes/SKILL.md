---
name: permissions-and-routes
description: "Use when adding a new screen, module, route, or any user-facing action that needs authorization — and when a user reports '403' or 'the menu item is missing'. Covers config/permissions.php as the source of truth, PermissionSeeder, the '<group>.<action>' naming scheme, ->can() on routes, abort_unless() in Livewire, @can in Blade and the sidebar, and the per-module route file layout with module:: name prefixes."
---

# Permissions and Routes

Authorization is Spatie Permission, but permissions are **declared in config, not created ad hoc**. A new feature is not finished until its permission exists in config, is seeded, is enforced on the route *and* in the component, and gates its menu entry.

## Permissions are configuration

`config/permissions.php` is the single source of truth — a map of group → actions:

```php
return [
    'account'  => ['create', 'view', 'edit', 'delete', 'export'],
    'customer' => ['create', 'view', 'edit', 'delete', 'export'],
    'asset'    => ['create', 'view', 'edit', 'delete', 'import', 'export', 'dispose', 'post depreciation', 'view accounting', 'dashboard overview'],
    'vendor'   => ['create', 'view', 'edit', 'delete', 'export', 'payment reverse'],
];
```

- The permission name is `"{$group}.{$action}"` — `sale.delete`, `rent out checklist.print`, `day session.create`. **Groups and actions are lowercase and may contain spaces.** Match the existing phrasing; don't introduce kebab-case or camelCase variants.
- Standard verbs are `create`, `view`, `edit`, `delete`, plus `import`/`export`/`print` where relevant. Non-CRUD capabilities get a descriptive action (`payment reverse`, `post depreciation`, `permissions`).
- After editing the config, run `php artisan db:seed --class=PermissionSeeder`. It `firstOrCreate`s each entry, so it is safe to re-run. **It hardcodes `'tenant_id' => 1`**, so it seeds one tenant only — rolling a new permission out to the others is a separate step, not something the seeder does for you.
- A permission referenced in code but missing from the config is **permanently denied**, not merely unseeded: `can()` returns false for an unknown ability and the `Gate::before` super-admin bypass in `AppServiceProvider` is commented out. The symptom is a button that never appears or a route that 403s for everyone including the owner. When a `@can` or `->can()` guard seems to do nothing, check the config before checking the roles.
- Fail-open is the nastier variant: a *negative* guard such as `if ($user->can('grn.view own') && ! $user->can('grn.view'))` silently stops narrowing when the ability is undeclared, so users see more than intended rather than less. Any new `view own`-style permission must land in the config in the same change.

## Enforce in three places

Each layer covers a hole the others leave:

**1. Route** — gates page access:

```php
Route::get('view/{id}', 'view')->name('view')->can('property.view');
```

**2. Livewire method** — gates the action itself, since the page is already open:

```php
public function delete()
{
    abort_unless(auth()->user()?->can('sale.delete'), 403);
    // …
}
```

Route-level `->can()` does **not** protect a Livewire call. Every write method needs its own guard.

**3. Blade** — hides what the user cannot do:

```blade
@can('report.monthly sale')
    <a href="{{ route('report::monthly-sale') }}">Monthly Sale</a>
@endcan
```

The sidebar and header menus (`resources/views/layouts/sidebar.blade.php`, `header.blade.php`) are entirely `@can`-wrapped. A new screen with no `@can` entry is unreachable for everyone but you.

## Route file layout

Routes are split per module and included from the app bootstrap: `sale.php`, `purchase.php`, `property.php`, `inventory.php`, `settings.php`, `report.php`, `print.php`, `accounts.php`, `api_v1.php`, `api_v1_technician.php`, and so on. Add a new module to its own file rather than growing `web.php`.

The established shape:

```php
Route::middleware('auth')->group(function (): void {
    Route::name('sale::')->prefix('sale')->controller(SaleController::class)->group(function (): void {
        Route::get('', 'index')->name('index')->can('sale.view');
        Route::get('create', 'page')->name('create')->can('sale.create');
        Route::get('edit/{id}', 'page')->name('edit')->can('sale.edit');
        Route::get('view/{id}', 'view')->name('view')->can('sale.view');
    });
});
```

- **Name prefix is `module::`** (double colon), so routes read `route('sale::edit', $id)`, `route('print::rentout::checklist', $id)`. Nested groups nest the prefix.
- `->controller(X::class)` with bare method names, not `[X::class, 'method']` array syntax, in the module groups.
- Extra middleware wraps a sub-group — e.g. `RequireOpenDaySession::class` around the POS/create routes in `routes/sale.php`.
- Lookup endpoints that feed TomSelect components live in the same file under an `api.` name prefix and return `{items: […]}`.

## Adding a module — the full checklist

1. Add the group and its actions to `config/permissions.php`; seed.
2. Create `routes/<module>.php` with the `module::` name prefix and `->can()` on every route.
3. Guard each Livewire write method with `abort_unless(...->can('<group>.<action>'), 403)`.
4. Wrap the sidebar/header entry in `@can`.
5. If the module prints, add its routes under `routes/print.php` inside the `print::` group.
6. Assign the new permissions to the relevant roles (Settings → Roles) or the feature is invisible in production.
