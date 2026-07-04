# Treadhouse Showcase

Standalone Vue 3 storefront that renders the public product catalog of the `project_manager`
Laravel backend over its `/api/v1` REST API. It is fully independent — no code is shared with
the backend; the API is the only integration surface.

Design reference: `landing_website/Product Showcase v2.dc.html`.

## Stack

- Vue 3 (`<script setup>`) + Vite
- Vue Router (branch picker → category → brand → size → listing → product detail)
- Pinia (selected branch, wizard filters)
- Axios (single client, envelope unwrapping, tenant injection)

## Setup

```bash
cd showcase_website
cp .env.example .env   # then edit
npm install
npm run dev            # http://localhost:5180
npm run build          # static bundle in dist/ — deploy anywhere
```

## Configuration (.env)

| Variable            | Meaning                                                          |
| ------------------- | ---------------------------------------------------------------- |
| `VITE_API_BASE_URL` | Base URL of the API, e.g. `https://acme.example.com/api/v1`      |
| `VITE_TENANT`       | Tenant subdomain — sent as `X-Tenant-Subdomain` header + `?tenant=` param |
| `VITE_STORE_NAME`   | Brand name shown in the header/footer                            |
| `VITE_CURRENCY`     | ISO currency code used for price formatting (e.g. `USD`, `AED`)  |

### Tenant resolution

Every catalog route on the backend runs behind `IdentifyTenant:required`. The tenant is
resolved from the request subdomain; when the app is served from a different host
(localhost, an IP, or a static CDN), the backend falls back to the `?tenant=` query param
or `X-Tenant-Subdomain` header — this app always sends **both**. Requests without a
resolvable tenant return `404 Tenant could not be identified`, which the UI surfaces
with a retry banner.

If the backend and this app run on different origins, allow the origin in the Laravel
CORS config (`config/cors.php`) for `api/v1/*`.

## API endpoints consumed

| Endpoint                 | Used for                                              |
| ------------------------ | ----------------------------------------------------- |
| `GET /branches`          | Branch picker modal + branches page                   |
| `GET /categories`        | Step 1 — category grid                                |
| `GET /brands`            | Step 2 — brand grid                                   |
| `GET /sizes`             | Step 3 — size chips                                   |
| `GET /colors`            | Colour filter on the listing                          |
| `GET /products`          | Listing (filters, search, sort, pagination) + related |
| `GET /products/single/`  | Product detail (`images360`, `related_sizes`, …)      |

All responses use the `{ success, data, message }` envelope; the Axios client unwraps it
so callers receive `data` directly.

## Structure

```
src/
├── api/          client.js (axios + tenant + envelope), resources.js (per-endpoint fns)
├── stores/       branch.js (selection, persisted), filters.js (wizard state)
├── utils/        format.js (price/name helpers)
├── components/   TopBar, BranchModal, AppFooter, StepDots, ProductCard,
│                 SkeletonCard, EmptyState, ErrorBanner
└── views/        HomeView (hero + categories), BrandView, SizeView,
                  ListingView, ProductDetailView (gallery + 360°), BranchesView
```
