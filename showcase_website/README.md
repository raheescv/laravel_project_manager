# SIZE RUN Showcase

Standalone Vue 3 storefront that renders the public product catalog of the `project_manager`
Laravel backend over its `/api/v1` REST API. It is fully independent — no code is shared with
the backend; the API is the only integration surface.

Design reference: `template/sizerun-site/` — the static SIZE RUN storefront
(https://www.astra.qa/sizerun-site/site/) this app is a 1:1 Vue port of. The CSS is ported
verbatim (`src/assets/app.css`, with a short block of live-data additions at the end) and the
EN/AR copy table lives in `src/i18n.js`.

## The flow

One page, three stages, then a product page:

1. **Your size** — blue field with a ruler rail (`GET /sizes`, adult + kids runs, sold-out
   sizes struck through). "Show every size" browses the whole shop.
2. **Brand** — tiles with live counts scoped to the chosen size (`GET /brands?size=`).
3. **The pairs** — search, sort and an infinite-scroll grid (`GET /products`).

`#/product/:id` — gallery (+ 360° spin when the product has angle frames), size run with
per-size stock, per-shop availability, details, and the bag (localStorage) with online
checkout through Tap Payments (see below).

Size and brand persist per device; every selection is mirrored into the hash URL
(`#/?size=42&brand=11&q=dunk&sort=priceAsc`) so a catalogue view can be shared.

## Online checkout (Tap Payments)

The bag drawer takes payment on Tap's hosted page. No card data and no Tap key ever touch
this app — the API holds the tenant's secret key and talks to Tap.

1. **Bag** — lines + collect in shop / deliver (delivery shows only when the store set a
   delivery branch).
2. **Details** — the shop to collect from (or the delivery address), name, email, mobile.
3. **Pay** — `POST /storefront/checkout` prices the bag from the catalogue (the browser only
   sends product ids and quantities), checks the shop's stock and returns Tap's `payment_url`.
4. **Back from Tap** — Tap returns to this page with `?checkout=REF&tap_id=…` (before the `#`,
   so hash routing keeps working). The app calls `GET /storefront/checkout/REF`, which
   re-reads the charge from Tap and, once it is `CAPTURED`, records a **completed sale**
   (stock deducted, payment posted to the configured account, source "Storefront").
   Tap's webhook does the same for customers who close the tab before coming back.

Outcomes: `paid` (order confirmed, bag emptied) · `failed` (nothing taken, bag kept, try
again) · `pending` (not paid yet — continue to payment / check again) · `review` (paid, but
the sale could not be recorded automatically; staff must finish it — see the server log).

Setup is in the admin, per tenant: **Settings → Online Payments** — switch on, paste the
Tap secret key (`sk_test_…` for testing, `sk_live_…` for real money), pick the payment
method the takings are posted to, the user sales are recorded as, and optionally a delivery
branch. Until that is complete the bag shows "Online checkout isn't available yet".

## Stack

- Vue 3 (`<script setup>`) + Vite, hash routing (drops into any folder, no rewrite rules)
- Pinia (`catalog` — selections + data; `bag` — lines + checkout; `shops`)
- Axios (single client, envelope unwrapping, tenant injection)
- Hand-rolled EN/AR i18n with RTL (`src/i18n.js`)

## Setup

```bash
cd showcase_website
cp .env.example .env   # then edit
npm install
npm run dev            # http://localhost:5180
npm run build          # static bundle in dist/ — deploy anywhere
```

## Configuration (.env)

| Variable            | Meaning                                                                   |
| ------------------- | ------------------------------------------------------------------------- |
| `VITE_API_BASE_URL` | Base URL of the API, e.g. `https://acme.example.com/api/v1`               |
| `VITE_TENANT`       | Tenant subdomain — sent as `X-Tenant-Subdomain` header + `?tenant=` param |
| `VITE_STORE_NAME`   | Store name used in the page title / footer                                |
| `VITE_CURRENCY`     | ISO currency code for prices (`QAR` → `QAR 1,500` / `1,500 ر.ق`)          |
| `VITE_COUNTRY_CODE` | Dialling code pre-filled in the checkout mobile field (default `974`)     |

Never put Tap keys here: every `VITE_` value is baked into the public bundle.

### Tenant resolution

Every catalog route on the backend runs behind `IdentifyTenant:required`. The tenant is
resolved from the request subdomain; when the app is served from a different host
(localhost, an IP, or a static CDN), the backend falls back to the `?tenant=` query param
or `X-Tenant-Subdomain` header — this app always sends **both**. Requests without a
resolvable tenant return `404 Tenant could not be identified`, which the UI surfaces
with a retry box.

If the backend and this app run on different origins, allow the origin in the Laravel
CORS config (`config/cors.php`) for `api/v1/*`.

## API endpoints consumed

| Endpoint                           | Used for                                                    |
| ---------------------------------- | ----------------------------------------------------------- |
| `GET /sizes`                       | Stage 1 — the size rails (`in_stock` greys a tick out)      |
| `GET /brands?size=`                | Stage 2 — brand tiles with stock-scoped counts + logos      |
| `GET /products`                    | Stage 3 — grid (size, brand, search, sort, pagination); product page — the size run (`search=<code>`, `in_stock_only=0`) |
| `GET /products/{id}`               | Product page (`images`, `images360`, `inventories`, `related_sizes`) |
| `GET /branches`                    | Footer shop list, per-shop stock rows, pickup shop choice   |
| `GET /settings/branding`           | Accent colour (`--blue*` tokens), logo, contact details     |
| `GET /storefront/checkout/config`  | Whether checkout / delivery is on, test-mode badge          |
| `POST /storefront/checkout`        | Start a checkout → Tap `payment_url`                        |
| `GET /storefront/checkout/{ref}`   | Confirm the payment on return from Tap                      |

All responses use the `{ success, data, message }` envelope; the Axios client unwraps it
so callers receive `data` directly.

## Structure

```
src/
├── api/          client.js (axios + tenant + envelope), resources.js (per-endpoint fns)
├── stores/       catalog.js (size/brand/q/sort + sizes/brands/products), bag.js (+ checkout), shops.js
├── utils/        catalog.js (stock pill, size sorting, brand marks, images)
├── i18n.js       EN/AR strings, t(), field(), money(), setLang()
├── branding.js   accent colour → CSS vars, logo, company contact
├── toast.js      status toast
├── components/   AppHeader, AppFooter, BrandLogo, SizeStage, SizeRail, BrandStage,
│                 ProductStage, ProductCard, SkeletonCard, ErrorBox, BagDrawer, ToastHost
└── views/        CatalogueView (three stages), ProductView
```
