# Build Prompt — Product Showcase App

> **How to use this file:** a complete, self-contained build spec. Hand it to an implementer
> (human or agent) working inside this repo. It has three parts: **Part 1** is the data
> contract (what already exists in `/api/v1` and the two small gaps to close), **Part 2** is
> the app itself, screen by screen, **Part 3** is the design system.
>
> **Two authoritative references — respect the precedence:**
> 1. **Flow, filters and payload shapes** → the existing Vue storefront in
>    [`showcase_website/src/`](../showcase_website/src/). It already implements this exact
>    funnel against these exact endpoints. Port its logic; do not re-derive it.
> 2. **Architecture, state, DI, networking, models** → [`mobileApp/lib/`](../mobileApp/lib/)
>    (flutter_bloc + get_it + repository/service three-layer). See the `flutter-apps` and
>    `flutter-code-standards` skills.
>
> **Visual design is neither of those** — it comes from the chosen direction in
> [`docs/showcase-app-3-samples.html`](showcase-app-3-samples.html). See Part 3.

---

## 0. Goal

A **read-only, no-login** product showcase for walk-in customers and floor staff. It funnels
down a fixed path and ends on a rich product page:

```
CATEGORY  →  SIZE  →  BRAND  →  PRODUCT LIST  →  PRODUCT VIEW
                                                  ├─ image gallery
                                                  ├─ 360° spin viewer
                                                  └─ related products
```

**Non-goals.** No cart, no checkout, no payment, no customer accounts, no admin. "Reserve in
store" is a local intent only (see §2.8) — it must not write to the sales tables. This app
never authenticates a user and never calls a Sanctum-protected route.

**Where it lives.** A new top-level folder, `showcaseApp/`, alongside `mobileApp/` and
`technicianApp/`. It is a **separate application** — it shares the backend and the code
*patterns*, never the same Flutter package. Do not add screens to `mobileApp/`.

**Platform.** Flutter (`package: showcase`), same toolchain and folder anatomy as
`technicianApp/`. See Appendix A if this ships as a web/PWA storefront instead.

**Form factor: tablet-first.** The primary device is an **11&Prime; tablet in landscape**
(≈1194×834) standing on the shop floor. Design and build every screen for that first, then
let it collapse to the phone layout — not the other way round. The two layouts differ
structurally, not just in scale (§2.10).

---

# PART 1 — Data contract

Everything the funnel needs is **already live** in `routes/api_v1.php` under the public,
tenant-scoped block (`IdentifyTenant::class.':required'` — open, but a tenant *must* resolve).
Do not add auth to these routes.

### 1.1 Endpoints the app uses

| Method | Path | Query it must send | Returns |
|--------|------|--------------------|---------|
| `GET` | `/api/v1/categories` | — | `[{id, name, product_count}]` |
| `GET` | `/api/v1/sizes` | `main_category_id`, `sub_category_id?`, `brand_id?` | `{young_sizes:[{size}], adult_sizes:[{size}]}` |
| `GET` | `/api/v1/brands` | `main_category_id`, `size`, `available_products_only=1` | `[{id, name, product_count}]` |
| `GET` | `/api/v1/products` | `main_category_id`, `size`, `brand_id`, `branch_id`, `in_stock_only`, `min_price`, `max_price`, `color`, `search`, `sort_by`, `sort_direction`, `per_page`, `page` | `{data:[], pagination:{}, filters_applied:{}}` |
| `GET` | `/api/v1/products/{id}` | — | full detail (see 1.2) |
| `GET` | `/api/v1/products/single` | `barcode` | full detail — powers the scanner |
| `GET` | `/api/v1/colors` | `code?` | `[{color, product_count}]` |
| `GET` | `/api/v1/branches` | `query?` | `[{id, name, code, location, mobile}]` |
| `GET` | `/api/v1/settings/branding` | — | `{primary_color, logo}` |

**Tenant is mandatory on every call.** Send the `X-Tenant-Subdomain` header *and* a `?tenant=`
query param, exactly as [`showcase_website/src/api/client.js`](../showcase_website/src/api/client.js)
does — the query param is the fallback for IP/localhost hosts. A missing tenant returns 404
with a `tenant`-flavoured message; surface that as a first-run configuration error, not a
generic failure.

**Envelope.** Every response is `{success, data, message}`. Unwrap to `data` in the HTTP layer
and throw a typed `ApiException` when `success` is false — mirror the interceptor in
`client.js`. Relative image paths resolve against the API origin (`resolveImage()`).

### 1.2 What `GET /products/{id}` gives you

From `App\Http\Resources\V1\ProductResource` — note these fields are emitted **only** on the
single-product routes, never on the list (deliberate; the list skips them to avoid N+1):

- `images[]` — normal photos (`method = 'normal'`), each `{id, path, url, name, type}`
- `images360[]` — **spin frames** (`method = 'angle'`), already sorted by `degree`, each
  carrying `{degree, sort_order, url}`. This is the 360° viewer's entire input.
- `available_sizes` / `related_sizes` — the size run for the same style
- `inventories[]` — per-branch `{branch:{id,name}, quantity, is_low_stock, is_out_of_stock}`
- `total_stock`, `is_out_of_stock`, `stock_quantity_availability_status`
- `brand`, `main_category`, `sub_category`, `unit`, `mrp`, `tax`, `color`, `size`, `code`,
  `name_arabic`

**Never render `cost`.** It is commented out of the resource on purpose — do not re-enable it.

### 1.3 The two gaps to close

**Gap 1 — related products.** No endpoint exists. **Ship v1 without touching the backend:**
call `GET /products?main_category_id={p.main_category.id}&brand_id={p.brand.id}&per_page=12`
and drop the current product from the results. Only if ranking needs to be smarter, add
`GET /products/{id}/related` following the existing V1 pattern (thin controller →
`app/Actions/V1/Product/GetRelatedProductsAction` → `ProductResource`) — same main category,
then same brand, in-stock first, current product excluded, capped at 12.

**Gap 2 — per-size stock counts.** The size screen wants a small count under each chip and a
disabled state for sizes with no stock. `GET /sizes` returns sizes only. Either extend
`GetSizesAction` to include `product_count` / `in_stock` per size (preferred — one request),
or derive it client-side from a single `/products?main_category_id=…&per_page=100` sweep.
**Do not fire one request per size.**

If you extend either endpoint, follow the `mobile-api-v1` skill: thin controller, action
class, Form Request, Scramble annotations, `sendSuccess`/`sendServerError` envelope, and keep
the routes inside the public `IdentifyTenant:required` group.

---

# PART 2 — The app

### 2.0 Structure and the two layouts

Mirror `mobileApp/lib` exactly (see the `flutter-apps` skill):

```
showcaseApp/lib/
  app.dart, main.dart, flavors.dart
  core/            http_service.dart, endpoints.dart, service_locator.dart, exceptions
  features/
    catalog/       data/ (repository, models)  presentation/ (cubits, screens, widgets)
    product/       detail cubit, gallery, spin viewer, related
    branch/        branch picker + persisted selection
    settings/      theme, language, branch, about
  shared/          widgets, utils, theme/
```

Rules that are non-negotiable in this repo: immutable Cubit state with `copyWith` +
`DataFetchStatus`; repository/service pairs over `HttpService` + `EndPoints`; defensive JSON
parsing via `asStr`/`asNum`; controllers disposed in `initState`/`dispose`, **never** from a
sheet's `.whenComplete`; route and string constants, no inline literals; lazy list building.
**Never run `dart format`** — this codebase uses a compact hand style; verify with
`flutter analyze lib`.

Follow the pattern `mobileApp` already uses for its tablet work: one widget tree, a
`context.isTablet` branch at the layout level, and shared leaf widgets. Do **not** fork the
feature into two screen classes.

### 2.1 Screen 01 — Category (entry)

- Branch chip in the header; tapping it opens the branch picker (2.9). Branch is applied as
  `branch_id` on every product query and drives all stock badges.
- Search field at the top is a **global** product search — it jumps straight past the funnel
  into the list with `?search=`.
- Grid/list of categories from `/categories` with `product_count`.
- Barcode scan button → `/products/single?barcode=` → straight to the product view. Reuse the
  permission-first `ContinuousScannerScreen` pattern from `mobileApp` (permission_handler
  primer → settings flow; never let `mobile_scanner` self-request permission).
- Skeleton placeholders while loading — never a bare spinner on a full screen.

### 2.2 Screen 02 — Size

- `GET /sizes?main_category_id=` — render **two groups**, `young_sizes` then `adult_sizes`,
  with the group headings from the design ("Young · 10C–3Y", "Adult · UK 5–13"). The API may
  also return the legacy `kids_sizes`/`other_sizes` keys or a flat array; normalise all three
  shapes exactly as `showcase_website/src/api/resources.js` does.
- Out-of-stock sizes render disabled (struck through), not hidden — customers ask for them.
- A **Skip** affordance goes straight to the list with no `size` filter.
- Selecting a size advances immediately (click-and-go — no Save button anywhere in this app).

### 2.3 Screen 03 — Brand

- `GET /brands?main_category_id=&size=&available_products_only=1`, showing `product_count`
  per brand in the context of the size already chosen.
- Also skippable — "Show all brands" is a first-class button, not a hidden link.

### 2.4 Screen 04 — Product list

- `GET /products` with every accumulated filter plus `branch_id`.
- **Infinite scroll** off `pagination.has_more_pages`, `per_page: 20`. Never load page N+1
  until page N has rendered.
- Grid/list toggle, persisted per device.
- Filter sheet: colour (`/colors`), price range (`min_price`/`max_price`), in-stock-only, and
  sort (`sort_by ∈ name|price|mrp`, `sort_direction`). The active filter count shows on the
  chip.
- Card shows thumbnail, brand, name, MRP, a stock/discount badge, and a **360 badge** when the
  product has spin frames.
- Breadcrumb strip is tappable — each crumb pops back to that step.
- Empty state offers "clear filters" and "change branch", not a dead end.

### 2.5 Screen 05 — Product view

- Hero gallery from `images[]` — swipeable, paged dots, pinch-to-zoom, thumbnail strip below
  with a `+N` overflow tile.
- Brand · name · code · price (with MRP strike + computed discount when applicable) ·
  stock badge from `stock_quantity_availability_status`.
- **Colour swatches**: the other products in the same style (same `code` prefix / same style
  group) — tapping one swaps the whole product.
- **Size run** from `related_sizes` / `available_sizes`; the current product's `size` is
  pre-selected, out-of-stock sizes disabled. Tapping a different size navigates to that
  product.
- A **360° entry point** — badge on the hero *and* a tap on the image itself — shown only when
  `images360` is non-empty.
- Arabic: show `name_arabic` when the app language is Arabic, and mirror the whole layout
  (the previews have an RTL toggle — check every screen in it).

### 2.6 Screen 06 — Product view, lower half

Specs table (category, colour, material, code, unit), per-branch availability from
`inventories[]` (current branch first, zero-stock branches dimmed but visible), then
**related products** as a horizontal rail (Gap 1). Each related card carries its own 360 badge.

### 2.7 Screen 07 — 360° viewer

The signature screen. Get it right.

- Input is `images360[]`, already ordered by `degree`. Frame count is whatever the tenant
  uploaded (commonly 24 or 36) — **never hardcode it**.
- **Preload every frame before the viewer becomes interactive.** Show a determinate
  "Loading 360° · 14/24" progress; a spin that stutters on an un-cached frame reads as broken.
  Decode at display width (`cacheWidth: decodeWidthFor(context, w)`) — full-res decodes thrash
  the image cache, which is a mistake this codebase has already made once.
- Horizontal drag maps to frame index with wrap-around; flick carries inertia and eases out.
  Optional slow auto-spin on entry that stops on first touch.
- Show the current degree, a frame ticker, and a one-time "drag to spin" hint.
- Pinch-to-zoom within a frame; double-tap resets.
- Modes: **360° · Gallery · Zoom** as a segmented control, so the viewer is one screen, not three.
- Falls back to the plain gallery when `images360` is empty — no empty spin stage, ever.

### 2.8 "Reserve in store"

Local intent only. Records `{product_id, size, branch_id, timestamp}` in local storage, shows
a confirmation with the branch phone number from `/branches`, and optionally opens a
share/WhatsApp message. **It writes nothing to the server** — there is no reservation table and
this app must not create sales.

### 2.10 Tablet layout — what actually changes

On the big screen the funnel stops being a corridor. The steps are the same and the API calls
are the same; the arrangement is not:

- **Persistent left rail (68 px)** — Browse / Brands / Stores / Saved, plus the language chip.
  Replaces the phone's bottom tab bar.
- **A top bar that stays put** — wordmark, wide search, branch pill, scan button. Search and
  branch are reachable from every screen instead of only from Browse.
- **Choices stay on screen.** Category / Size / Brand / Results live in a 252 px left column as
  a four-step list: completed steps show their value and are tappable to reopen, the current
  step is highlighted, later steps are dimmed. Nothing about going back is destructive.
- **Results stay on screen.** The Size screen shows a live "in size 9 right now" preview and a
  running count in a 296 px right column, so a customer sees the consequence of the choice
  before committing to it. Brand cards carry three in-stock thumbnails each.
- **Filters are permanent, not a sheet.** Colour, price, in-stock and has-360 sit in the left
  column on the list screen; the grid is 4-up.
- **Product view splits.** Full-height gallery (vertical thumbnail strip + large image + spin
  entry) on the left, a standing info panel on the right whose CTA is pinned to the bottom and
  never scrolls away, and the related rail spanning the full width beneath both.
- **The 360° viewer goes full-bleed** with floating chrome: degree readout top-right, frame
  ticker and mode switch bottom-centre, and a small product card top-right so price and
  "Reserve in store" stay reachable while spinning.
- **Touch targets stay phone-sized.** The tablet is used standing up, often at arm's length —
  bigger canvas means more content per screen, not smaller controls.

Phone keeps the sequential funnel, the bottom tab bar, the filter sheet and the stacked product
view. Both are in the preview — use the Tablet/Phone toggle.

### 2.9 Branch, offline and caching

- Branch selection persists per device (`LocalStorageService`) and is required before the first
  product query; default to the first branch when only one exists.
- Cache `/categories`, `/sizes`, `/brands`, `/branches` and `/settings/branding` with a short
  TTL — they change rarely and the funnel hits them on every pass.
- Offline: serve the last successful catalog response and show a non-blocking "offline —
  showing last known stock" banner. Stock numbers are the one thing that must be labelled stale.
- Deep link `/{tenant}/product/{id}` opens the product view directly.

---

# PART 3 — Design

The three directions are in **[`docs/showcase-app-3-samples.html`](showcase-app-3-samples.html)**
(six directions, **tablet/phone toggle**, light/dark toggle, LTR/RTL toggle — every screen in
each combination). Directions 4–6 are the newer, more premium set:

| # | Direction | Character |
|---|-----------|-----------|
| 4 | **Vitrine** | Obsidian ground, each product in its own pool of light, gilt hairline along every top edge. Bodoni Moda + Manrope, oxblood accent used only on decisions. **Dark-first** — the light theme is a daylit display case, not an inversion. |
| 5 | **Court** | Ink navy and volt, corners notched at 45°, Archivo set expanded and uppercase, tabular numbers, a volt bar leading each section title. Sport rather than retail — the sneaker-drop language. |
| 6 | **Pearl** | Cool pearl and graphite, hairlines instead of fills, **no accent hue at all** — selection is an ink block and nothing else. Jost, small and wide-tracked, uppercase, with far more air than any other direction. |
| 1 | **Atelier Noir** | Ivory paper, ink serif headlines (Fraunces), brass hairline, square corners, uppercase micro-labels. Editorial lookbook. |
| 2 | **Aurora Glass** | Indigo→cyan light behind frosted panels, large radii, glowing selected states. Closest to the current Astra mobile app. |
| 3 | **Studio Mono** | Pure white, hairline grid, zero radius, Inter Tight + JetBrains Mono, one signal-orange accent. Fastest to scan on a shop floor. |

**Pearl is the chosen direction** and is implemented in `showcaseApp/` — see
[`showcaseApp/README.md`](../showcaseApp/README.md). Its tokens live in
`lib/shared/utils/components/theme/pearl_theme.dart` and nothing outside that file
names a colour. The notes below are what that implementation follows:

- Lift its token set (background, surface, stage, ink, muted, faint, line, accent, accent-ink,
  radius, spacing, type scale) into `shared/theme/` as an `AstraPalette`-style class with
  light and dark variants — both are already specified in the preview.
- `/settings/branding` returns the tenant's `primary_color`. It overrides the theme accent at
  boot, exactly as `showcase_website/src/branding.js` does. The theme must survive an
  arbitrary tenant hex — never hardcode the accent into a gradient. Note this cuts against
  **Pearl**, whose whole idea is having no accent hue: if Pearl is chosen, decide up front
  whether the tenant colour is honoured or deliberately ignored.
- Every screen ships light **and** dark, LTR **and** RTL. The preview's toggles are the
  acceptance test.
- Product photography is the loudest thing on every screen. Chrome stays quiet.

---

## Status

Built and walked against the live catalogue on an iPad simulator (2026-08-27):
browse, size, results, product, the 360° viewer and its gallery fallback, plus
dark mode. `flutter analyze lib` clean. Gap 2 (per-size stock) was closed on the
backend; Gap 1 (related products) is composed client-side as specified.

Not yet exercised on a device: the brand step, the phone layout, the barcode
scanner, and the reserve sheet.

## Acceptance checklist

- [ ] Full funnel works end to end, and Size and Brand are both skippable.
- [ ] Breadcrumb pops back to any earlier step with filters intact.
- [ ] Barcode scan lands on the product view.
- [ ] List paginates on scroll and never double-fetches a page.
- [ ] 360° viewer preloads all frames, spins with inertia, wraps, and is absent when there are
      no spin frames.
- [ ] Related products exclude the current product.
- [ ] `cost` appears nowhere.
- [ ] Every screen correct in light, dark, LTR and RTL, on tablet **and** phone.
- [ ] On tablet the CTA never scrolls out of the product view, and earlier funnel steps stay
      visible and tappable.
- [ ] No login prompt anywhere; no write request of any kind leaves the app.
- [ ] `flutter analyze lib` clean; `dart format` never run.

---

## Appendix A — if this ships as a web storefront instead

Copy `showcase_website/` to a new folder, keep Vue 3 + Pinia + the existing
`api/client.js`/`resources.js` (they already implement Part 1 verbatim), and rebuild the views
against the chosen direction from Part 3 as a mobile-first PWA. Parts 1 and 2 apply unchanged;
only §2.0 (Flutter structure) and the Flutter-specific rules drop away. Note the constraint
recorded for the existing landing page: it must be served **same-origin** with the API, or CORS
has to be opened for the new origin.
