# Sizerun Showcase

A read-only product showcase for the shop floor. A customer (or a colleague
helping one) walks the funnel — **category → size → brand → results → product** —
and lands on a product page with the gallery, the size run, per-store stock and a
360° spin viewer.

Design direction: **Pearl** (direction 06 of
[`docs/showcase-app-3-samples.html`](../docs/showcase-app-3-samples.html)) — cool
pearl and graphite, hairlines instead of fills, **no accent hue at all**.
Selection is signalled by an ink block and nothing else.

Built against the same public `/api/v1` catalog the Vue storefront in
[`showcase_website/`](../showcase_website/) uses. Architecture mirrors
[`mobileApp/`](../mobileApp/): flutter_bloc + get_it, repository/service pairs
over one `HttpService`.

## Running it

```bash
cp env.example.json env.json      # then edit for your host/tenant
flutter run --dart-define-from-file=env.json
```

`env.json` carries three values:

| key | meaning |
|-----|---------|
| `API_BASE_URL` | site root, no trailing slash, no `/api` |
| `API_TENANT` | tenant subdomain — sent as `X-Tenant-Subdomain` **and** `?tenant=` |
| `API_HOST` | optional `Host` override when `API_BASE_URL` is a LAN IP |

Debug builds accept a self-signed certificate so a local `.test` host works from
a simulator; release builds keep full validation.

`--dart-define=START_AT=/product/437/spin` opens the app on a given route instead
of the funnel — for working on one screen without walking to it. Empty in real
builds.

## Tablet first

The primary device is an 11″ tablet in landscape. The phone layout is the
fallback, and the two differ structurally, not just in scale:

| | Tablet | Phone |
|---|---|---|
| Navigation | persistent 68px left rail | top-bar controls |
| Funnel steps | pinned left column, each step reopens on tap | breadcrumb strip |
| Consequence of a choice | live count + preview in a right column | next screen |
| Filters | permanent beside the grid | bottom sheet |
| Product page | gallery ‖ standing info panel, related rail below | stacked |

One widget tree throughout, branching on `context.isTablet` at the layout level —
never a forked screen class.

## What it will not do

No cart, no checkout, no accounts, no admin, and **no write request of any kind**.
"Reserve in store" writes a note to the tablet's own storage and shows the
customer which store to go to; it posts nothing. There is no reservation endpoint
and this app must never create a sale. `cost` is never displayed.

## Notes for whoever picks this up

- **The branch gates everything.** Every stock figure is scoped by `branch_id`,
  and without one the server sums across every shop — which, with the negative
  counts a live catalogue accumulates, makes stocked products read as sold out.
  Catalog reads `await BranchCubit.ready` rather than racing it.
- **Absolute image URLs are left alone.** The catalogue stores fully-qualified
  photo URLs on the tenant's asset host; rewriting those onto a development
  `API_BASE_URL` points every photo at a machine that holds none of the files.
- **Related products are composed client-side.** There is no
  `/products/{id}/related`; `CatalogService.related` filters the list endpoint by
  category + brand and widens to the category when a brand is too thin.
- **360 frames are whatever the tenant uploaded.** Frame count is never
  hardcoded, all frames preload before the viewer is interactive, and declared
  angles are ignored unless they span at least 180° — tenants often leave them at
  0 or use them as an upload counter.
- **Badges that sit on a photo are filled, not outlined.** Catalogue photos are
  shot on white; an outlined badge taking its colour from the page theme vanished
  against them in dark mode.
- **Never run `dart format`.** This repo uses a compact hand style; verify with
  `flutter analyze lib`.

## Backend change this app shipped

`GET /api/v1/sizes` now returns `product_count`, `stock_total` and `in_stock` per
size, and accepts `branch_id`. The size screen renders a count under every chip
and strikes through the sizes with nothing on the shelf — one request rather than
one per size. Additive: the existing `{size}` keys are unchanged, so
`showcase_website` is unaffected.

## Not yet verified

Walked on an iPad simulator against the live catalogue: browse, size, results,
product, the 360 viewer (preload → drag → frame advance) and its gallery
fallback, plus dark mode. **Not yet exercised on a device:** the brand step, the
phone layout, the barcode scanner (needs a real camera), and the reserve sheet.
