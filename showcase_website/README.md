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
per-size stock, per-shop availability, details, and a demo bag (localStorage, no payment).

Size and brand persist per device; every selection is mirrored into the hash URL
(`#/?size=42&brand=11&q=dunk&sort=priceAsc`) so a catalogue view can be shared.

## Stack

- Vue 3 (`<script setup>`) + Vite, hash routing (drops into any folder, no rewrite rules)
- Pinia (`catalog` — selections + data; `bag`; `shops`)
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

| Endpoint                 | Used for                                                              |
| ------------------------ | --------------------------------------------------------------------- |
| `GET /sizes`             | Stage 1 — the size rails (`in_stock` greys a tick out)                |
| `GET /brands?size=`      | Stage 2 — brand tiles with stock-scoped counts + logos                |
| `GET /products`          | Stage 3 — grid (size, brand, search, sort, pagination); product page — the size run (`search=<code>`, `in_stock_only=0`) |
| `GET /products/{id}`     | Product page (`images`, `images360`, `inventories`, `related_sizes`)  |
| `GET /branches`          | Footer shop list + ordering of the per-shop stock rows                |
| `GET /settings/branding` | Accent colour (`--blue*` tokens), logo, contact details               |

All responses use the `{ success, data, message }` envelope; the Axios client unwraps it
so callers receive `data` directly.

## Structure

```
src/
├── api/          client.js (axios + tenant + envelope), resources.js (per-endpoint fns)
├── stores/       catalog.js (size/brand/q/sort + sizes/brands/products), bag.js, shops.js
├── utils/        catalog.js (stock pill, size sorting, brand marks, images)
├── i18n.js       EN/AR strings, t(), field(), money(), setLang()
├── branding.js   accent colour → CSS vars, logo, company contact
├── toast.js      status toast
├── components/   AppHeader, AppFooter, BrandLogo, SizeStage, SizeRail, BrandStage,
│                 ProductStage, ProductCard, SkeletonCard, ErrorBox, BagDrawer, ToastHost
└── views/        CatalogueView (three stages), ProductView
```
