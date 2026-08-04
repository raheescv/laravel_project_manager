---
name: blade-ui-conventions
description: "Use when writing or restyling any Blade view, component, or CSS in resources/views/ and resources/css/ — layouts, tables, forms, modals, tabs, detail pages. Covers the Bootstrap 5 + vendored-asset stack (not Tailwind, not Vite, for the admin UI), Font Awesome 4.3 icon rule, shared TomSelect select components, toastr/SweetAlert wiring, the scoped 'premium' design systems (.rvx/.cvx/.lpox/.empx/.gvx), theme variables and dark mode, the sidebar skins, and the preview-before-apply rule for redesigns."
---

# Blade and UI Conventions

The admin UI is server-rendered Blade + Bootstrap 5 + Livewire, with **vendored** assets. Vite/Tailwind exist in the repo but only build the POS, React, and Vue bundles — do not reach for them when working on an ordinary admin screen.

## The stack, as actually loaded

`resources/views/layouts/app.blade.php` pulls vendored files with the `https_asset()` helper:

```blade
<link rel="stylesheet" href="{{ https_asset('assets/vendors/font-awesome/font-awesome.min.css') }}">
<link rel="stylesheet" href="{{ https_asset('assets/vendors/tom-select/tom-select.min.css') }}">
<script src="{{ https_asset('assets/vendors/bootstrap/bootstrap.min.js') }}"></script>
<script src="{{ https_asset('assets/vendors/toaster/toastr.min.js') }}"></script>
<script src="{{ https_asset('assets/vendors/sweetalert/sweetalert2.js') }}"></script>
```

Consequences:

- **Icons are Font Awesome 4.3 only** — `<i class="fa fa-plus"></i>`. FA5/6 prefixes (`fas`, `far`, `fab`, `fa-solid`) render as empty boxes. No other icon library.
- **jQuery, Bootstrap 5, TomSelect, toastr, SweetAlert2 are global.** Use them; don't add an npm dependency for something already loaded.
- Interactive behaviour beyond that is **Alpine**, inline in the view.

## Shared components before new markup

`resources/views/components/` already has the pieces:

- **Selects**: `resources/views/components/select/*Select.blade.php` — `customerSelect`, `accountSelect`, `productSelect`, `branchSelect`, `employeeSelect`, `propertySelect`, and ~20 more. These are **initializer script blocks**, not input components: you drop `<x-select.customerSelect />` (or `@include('components.select.customerSelect')`) once on the page, then write plain `<select class="select-customer_id">` elements in the form — the include attaches TomSelect to every matching element. Each is wired to a `*::list` route returning `{items: […]}`, with load-on-focus and remote search, and reads options like `account_type` off the element's attributes. Add a new one here rather than hand-rolling a `<select>`.
- `<x-modal>`, `<x-luminous-card>`, `<x-sortable-header>`, `<x-rich-text-editor>` (Alpine, dependency-free, RTL + HTML source view; sanitise server-side with `App\Support\RichText`), `<x-image-preview-modal>`, `<x-barcode-sticker>`.

## Feedback to the user

Livewire dispatches; the layout listens (`layouts/app.blade.php:272`) and shows a toastr:

```php
$this->dispatch('success', ['message' => 'Successfully saved']);
$this->dispatch('error', ['message' => $th->getMessage()]);
```

Session flashes (`session('success')`, `'error'`, `'info'`, `'warning'`) are also picked up for controller redirects. Confirmations use SweetAlert2. Do not invent a third notification mechanism.

## The "premium" design systems

Several screens have been redesigned into self-contained design systems, each **scoped to a single class prefix** so it cannot leak into the rest of the app:

| Prefix | Screen |
| --- | --- |
| `.rvx` | RentOut view + its management tabs (`components/rent-out/view/premium.blade.php`) |
| `.cvx` | Customer view, "Portrait Hero" (`components/account/customer/premium.blade.php`) |
| `.lpox` | Local Purchase Order view |
| `.empx` | Add/Edit Employee modal |
| `.gvx` | General Voucher / Journal Entry modal |

They share a pattern worth copying for any new one:

- One `@once <style>` block in a dedicated `premium.blade.php` component.
- Colour derives from the **active settings theme** — `--brand: var(--bs-primary)` plus `color-mix()` ramps — never a hardcoded palette, so it tracks the tenant's chosen scheme.
- Neutral surfaces, borders, radii, and shadows are declared as custom properties on the prefix class; a `[data-bs-theme="dark"] .<prefix>` block redefines the ramp for dark mode. **Always style both themes.**
- A single `--<prefix>-fz` base font size so density can be tuned from one place.
- Tabs and modals rendered inside the page inherit the system by living under the prefix — scope new tab styles to `.rvx .something`, not bare selectors.

The left nav has three user-selectable skins (Standard / Mono / Atelier) driven by `[data-nav-skin]` on `<html>` with `.luminous-nav`; Standard is the default. Preserve collapse/rail/responsive behaviour when touching it.

## Content is configuration

Never hardcode tenant wording — warranty clauses, declarations, terms, labels — as PHP or Blade defaults. It belongs in `Configuration` and is edited in Settings, often through the rich-text editor. Build the field; let them paste the text.

## Redesigns: preview first

For any substantial visual redesign, build a standalone `docs/*.html` preview with a light/dark toggle and a direction (LTR/RTL) switcher, and get the direction approved **before** editing real views. `docs/` already holds the previews for the customer view, LPO print, stock check, and others — follow the same format.

## Responsiveness and RTL

Tables scroll inside their own container rather than making the page scroll sideways. Arabic content appears throughout, so avoid layouts that assume left-alignment, and test both directions on anything new.
