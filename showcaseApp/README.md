# Sizerun Showcase

A read-only product showcase for the shop floor. A customer (or a colleague
helping one) walks the funnel — **size → brand → results → product** — and lands
on a product page with the gallery, the size run, per-store stock and a 360°
spin viewer. (There is no category step; it was tried and removed, and the
router has never had a route for one.)

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

**The dart-define is not optional.** Without it `API_BASE_URL` falls back to
`https://project_manager.test` and the tenant is empty, and every screen fails
with "Cannot reach the store" — the app is pointed at a host the device cannot
resolve. `.vscode/launch.json` passes it, so use Run rather than a bare
`flutter run`.

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

## One layout, drawn for the panel it is on

The device is a kiosk: one screen, one customer standing in front of it, one
question at a time. There is no device class and no second design — navigation
is in the top bar, the funnel's answers are a breadcrumb strip, filters are a
bottom sheet, and the product page stacks. Full bleed, no width cap, nothing
centred in the middle of the glass.

The rail, the pinned funnel column and the right-hand aside this app used to
carry on tablets are gone, along with the barcode scanner — it asked for a
camera the kiosk has no use for.

**`PanelScale` decides how large "one layout" is drawn.** Every number in this
design system — a 38pt control, a 9.5pt eyebrow, 22pt of page padding — was
drawn against a screen you hold, and Flutter's logical pixel says nothing about
how far away the glass is. A panel reporting a thousand logical pixels across
painted all of it at exactly the size a phone does, which is what made the app
look like a stretched phone screenshot on the shop floor.

So the frame is laid out on a smaller canvas and that canvas is scaled up to
the glass — the same thing a browser's zoom does, and for the same reason:
nothing has to carry a scale factor, so nothing can be missed. It is a scale
and not a letterbox, because only part of the extra width becomes scale
(`PanelScale.softness`) and the rest stays as canvas, which is what lets the
grids open up rather than showing a tablet's layout larger:

| panel (shortest side) | drawn at | canvas |
|---|---|---|
| 402pt (phone) | 1.00x | 402pt |
| 720pt and below | 1.00x | unchanged |
| 1080pt (kiosk) | 1.33x | ~813pt |
| 2160pt | 2.16x | ~1000pt |

Two consequences worth knowing before you change a layout:

- **`MediaQuery.sizeOf` is the canvas, not the panel.** That is deliberate —
  a sheet capped at 80% of "the screen" and a photo decoded for the width it
  will be painted at both want canvas units. The device pixel ratio is
  multiplied by the scale to match, so decode widths still come out in real
  pixels. A widget test that wants to measure what a customer sees has to use
  `tester.getRect` (transformed) rather than `getSize` (not).
- **Settings → Text size is unrelated and still multiplies on top.** How large
  the app is drawn is a property of the glass; how much larger than the app its
  words are is a customer's choice. The top bar caps the second one only.

The two grids answer to the canvas rather than to constants: the size run fills
its columns exactly (three across fills the width in three — the count is
Appearance's "sizes per row", nothing is capped or centred any more), and
`ProductGrid.columnsFor` puts the panel on two large tiles instead of the four
narrow ones the old thresholds reached at 1000pt.

## "All of them" is the first answer, not a button underneath

Both funnel questions offer to skip themselves, and both offer it as the first
tile in the grid — `All` leading the size run, `All brands` leading the brand
wall, counted like every other tile. Neither screen has a bottom bar any more.

A screen that asks its question with a wall of targets and then answers it
again with a differently-shaped control pinned underneath is asking twice, and
it made "I don't know my size" read as the way *out* of the screen rather than
as one of the answers on it. Marked whenever nothing has been narrowed, which
is what the screen actually means when it opens: nobody has chosen, so all of
them are still on the table.

Two consequences in `PearlChip`, and they are the only two places Pearl's
square-cornered, ink-block direction bends:

- **The plate has corners.** At a quarter of the panel a square corner reads as
  a panel seam rather than as a target. Proportional to the plate's height, so
  a 46pt chip on the product page and a 250pt plate on the funnel are the same
  shape.
- **Selection is a heavy accent outline, not a fill.** A filled plate at this
  size is a slab of accent big enough to be the loudest thing in the shop, and
  the number inside it has to be reversed out to survive. Outlined, the answer
  keeps the same ink as every other plate.

## Getting out

The panel resets itself after Settings → "Reset after" minutes of nobody
touching it. It also resets on demand: **Home** on the product page's top bar
and beside the filter button on the results. All three go through
`clearForNextCustomer()` in `funnel_navigation.dart`, so "start again" cannot
come to mean two different things depending on how you asked for it.

The controls that *move* a customer — Back, Home, and the close on the
full-screen photo — are `IconSquare(prominent: true)`: accent border, accent
icon. Nothing else in the frame is. The system bars are hidden and there is no
back gesture, so these are the only way out of a screen, and a grey hairline
square is furniture nobody sees from across a shop. The squares beside them
(Settings, sort direction) change what you are looking at rather than taking
you anywhere, and stay quiet — if everything is emphasised, nothing is.

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
reserve sheet, and everything `PanelScale` does — the scale, the two-up product
grid, the full-width size run, the half-panel gallery and the product page's
Home control are covered by `test/panel_scale_test.dart` and
`test/product_page_test.dart` at 1080x1920, but have not been seen on the
kiosk itself.
