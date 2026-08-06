---
name: print-and-pdf
description: "Use when building or changing anything printable — invoices, receipts, vouchers, statements, lease and reservation forms, checklists, KYC forms, LPO/GRN documents, day-session reports — or any view under resources/views/print/, any PrintController method, or a route in routes/print.php. Covers the Browsershot-by-default rule (Arabic and rich text must never go through DomPDF), the UsesBrowsershot trait, logo embedding, page-count layout passes, print permissions, and mobile on-device receipt printing. Read before writing PDF markup or debugging garbled Arabic, missing logos, or broken layout in a PDF."
---

# Printing and PDF

Routes live in `routes/print.php` under the `print::` name prefix; the rendering methods are in `app/Http/Controllers/PrintController.php`; the Blade lives in `resources/views/print/<module>/`.

## Browsershot is the default — DomPDF is the exception

**Write new documents with Browsershot.** Almost every document here carries tenant-entered free text (customer name, company name, property address, remarks), and in this market that text is routinely Arabic — which DomPDF renders unshaped and right-to-left reversed. If a field is tenant-editable, assume Arabic will appear in it eventually.

`PrintController` contains **no DomPDF at all**: every document goes through its private `browsershotPdf()` helper, which embeds the logo and renders through Chrome. The rent-out statement, utilities statement, payment receipt/voucher, purchase-vendor statement/voucher, checklist and residential lease are all Browsershot — do not follow older references that describe them as DomPDF documents.

DomPDF (`barryvdh/laravel-dompdf`) survives in exactly two places, both fixed-size layouts driven by hardware or a scheduler rather than by readable prose:

| File | Why it is still DomPDF |
| --- | --- |
| `app/Http/Controllers/BarcodeController.php` | 142×85pt physical sticker for label printers; swapping renderers risks misaligning labels on real hardware and cannot be verified without printing one |
| `app/Console/Commands/SendDailySaleSummaryCommand.php` | 80mm thermal-width PDF emailed from a scheduled command; Browsershot would add a Chrome dependency to the cron host |

Both still print product and customer names, so both are latent Arabic bugs — treat converting them as pending work that needs a physical print test and a check that Chrome exists on the scheduler host, not as a settled decision.

If you do write a DomPDF document, it supports roughly CSS 2.1: lay out with **tables**, use **hex colours** (no `var()`, `color-mix()`, or custom properties), no `box-shadow` or `transform`, no unembedded web fonts, and control breaks with `<thead>` plus `page-break-inside: avoid`.

## Browsershot documents

Use the `App\Traits\UsesBrowsershot` trait — never construct `Browsershot` directly. It resolves the node/npm/chrome binaries from `config('browsershot')` or `which`, sets the sandbox/crash-dump flags the servers need, and blocks all external domains. Documents live outside `PrintController` too, so pull the trait into whatever class renders them — an action (`Account\Customer\GenerateKycFormAction`, `Account\Customer\GenerateStatementAction`, `Package\GeneratePackageStatementAction`), a module controller (`Tailoring\OrderController`), or a Livewire report (`Report\TailoringNonDeliveryReport`):

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

`showBackground()` is required for any coloured panel or header band, otherwise Chrome prints it white. Landscape is `->landscape()`, not a paper argument.

`->pdf()` returns raw bytes, not a response object — there is no `stream()` as with DomPDF, so wrap it yourself:

```php
return response($pdf)
    ->header('Content-Type', 'application/pdf')
    ->header('Content-Disposition', 'inline; filename="'.$filename.'"');
```

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

**Every route in `routes/print.php` carries a `->can()`** — a print route without one lets any authenticated user pull another department's lease, statement or receipt straight from the URL. Reuse the module's existing action where one fits (`sale.receipts`, `tailoring order.receipts`, `rent out lease booking.residential lease`) and add a `print` action to the group only when nothing fits. Note that rent-out documents are shared between the `rent out` and `rent out lease` agreement types, so they are gated on a single `rent out.print` — `->can()` checks one ability and cannot express an OR.

Screens open documents by dispatching from Livewire after commit:

```php
$this->dispatch('print-invoice', ['link' => route('print::sale::invoice', $id), 'print' => $print]);
```

## Print design

Premium print layouts (LPO "Bronze", vendor statement "Graphite") use a masthead, meta cards, panels, a striped table, and a totals box. For a substantial redesign, build a `docs/*.html` preview with light/dark and direction toggles and get it approved **before** editing the real Blade.

## Mobile receipts are different

The Flutter app prints **on-device** via `buildReceiptPdf` — instant and offline — not through these server routes. Arabic shapes correctly there only when IBM Plex Sans Arabic is the **base** font (as a fallback it reverses and disconnects). Web Sale Configuration is the source of truth for the print options; only paper width is device-local. Changing a server receipt view does not change the mobile receipt, and vice versa.
