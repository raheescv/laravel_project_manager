---
name: print-and-pdf
description: "Use when building or changing anything printable — invoices, receipts, vouchers, statements, lease and reservation forms, checklists, LPO/GRN documents, day-session reports — or any view under resources/views/print/, any PrintController method, or a route in routes/print.php. Covers the DomPDF vs Browsershot decision (critically, Arabic and rich text must use Browsershot), the UsesBrowsershot trait, CSS constraints per engine, logo embedding, page-count layout passes, and mobile on-device receipt printing. Read before writing PDF markup or debugging garbled Arabic, missing logos, or broken layout in a PDF."
---

# Printing and PDF

Two engines are in use and picking the wrong one produces silently broken documents. Routes live in `routes/print.php` under the `print::` name prefix; the rendering methods are in `app/Http/Controllers/PrintController.php`; the Blade lives in `resources/views/print/<module>/`.

## Choosing the engine

| Use | Engine | Why |
| --- | --- | --- |
| Content that can contain **Arabic**, or HTML authored in the rich-text editor | **Browsershot** (headless Chrome) | DomPDF renders Arabic unshaped and right-to-left reversed |
| Tabular statements, vouchers, receipts with fixed ASCII/Latin content | DomPDF (`Pdf::loadView`) | Fast, no Chrome dependency |

If a field is tenant-editable free text, assume Arabic will eventually appear in it and use Browsershot. This is not hypothetical — it is why the rent-out checklist and handover declaration were moved off DomPDF.

## DomPDF documents

```php
$pdf = Pdf::loadView('print.rentout.statement', $data);
return $pdf->stream('statement.pdf');
```

DomPDF supports roughly CSS 2.1. In these views:

- Lay out with **tables**, not flexbox or grid.
- Use **hex colours** — no `var()`, no `color-mix()`, no `rgba()` custom properties.
- No `box-shadow`, no `transform`, no web fonts you have not embedded.
- Repeat headers with `<thead>` and control breaks with `page-break-inside: avoid`.

`app/Http/Controllers/PrintController.php` (rent-out statement, purchase-vendor statement/voucher) shows the working idiom.

## Browsershot documents

Use the `App\Traits\UsesBrowsershot` trait — never construct `Browsershot` directly. It resolves the node/npm/chrome binaries from `config('browsershot')` or `which`, sets the sandbox/crash-dump flags the servers need, and blocks all external domains:

```php
use UsesBrowsershot;

$html = view('print.rentout.checklist', compact('rentOut', 'companyLogo', 'pages'))->render();

return $this->makeBrowsershot($html)
    ->format('A4')
    ->margins(10, 10, 10, 10)
    ->showBackground()
    ->pdf();
```

Because external requests are blocked (`blockDomains(['*'])`, `disableJavascript()`), everything must be inline:

- **Images**: embed as data URIs. Use `CompanyLogoResolver::dataUri()` for the tenant logo — a `<img src="/storage/…">` will render blank.
- **CSS**: inline `<style>` in the view. Modern CSS *is* available here (flex, grid, `var()`, `color-mix()`), unlike DomPDF.
- **Fonts**: embed or rely on system fonts; no Google Fonts link.
- **No JavaScript** — render the final DOM server-side.

`showBackground()` is required for any coloured panel or header band, otherwise Chrome prints it white.

## Layout that depends on page count

When an element must sit at the foot of the *last* page, render once to count pages, then re-render at that height — and fall back to the first pass if stretching adds a page. `PrintController::rentOutChecklist()` is the reference implementation; copy that two-pass shape rather than guessing a fixed height.

## Route and permission

```php
Route::name('print::')->prefix('print')->controller(PrintController::class)->group(function (): void {
    Route::name('rentout::')->prefix('rentout')->group(function (): void {
        Route::get('checklist/{id}', 'rentOutChecklist')->name('checklist')->can('rent out checklist.print');
    });
});
```

Documents that a user can trigger get a `.print` permission. Screens open them by dispatching from Livewire after commit:

```php
$this->dispatch('print-invoice', ['link' => route('print::sale::invoice', $id), 'print' => $print]);
```

## Print design

Premium print layouts (LPO "Bronze", vendor statement "Graphite") use a masthead, meta cards, panels, a striped table, and a totals box. For a substantial redesign, build a `docs/*.html` preview with light/dark and direction toggles and get it approved **before** editing the real Blade.

## Mobile receipts are different

The Flutter app prints **on-device** via `buildReceiptPdf` — instant and offline — not through these server routes. Arabic shapes correctly there only when IBM Plex Sans Arabic is the **base** font (as a fallback it reverses and disconnects). Web Sale Configuration is the source of truth for the print options; only paper width is device-local. Changing a server receipt view does not change the mobile receipt, and vice versa.
