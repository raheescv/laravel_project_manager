# Kashperia Website

Standalone Vue 3 storefront for the House of Kashperia. Like `showcase_website`, it is fully
independent of the Laravel app — the `/api/v1` REST API is the only integration surface.

Design reference: `template/kashperia-site/` — the static concept site
(https://qloudpos.com/kashperia_new/) this app is a Vue port of, with its notes. Its CSS is
ported verbatim to `src/assets/reference.css`; live-site additions are in `src/assets/app.css`.

## What is live and what is content

| Part | Source |
| --- | --- |
| Header / footer / menu drawer | Vue components with the reference markup |
| Home, The Maison, Stories, Boutiques, Contact and every Maison/Client-care page | `src/content/pages/*.html` — the reference pages' own markup, rendered by `PageView` |
| Collections (`/collection/men`), Bestsellers, search | `GET /categories`, `GET /products` |
| Product page (`/product/emerald`) | `GET /products/{id|slug}` + its size/colour family |
| Bag + checkout (`/checkout`) | Tap Payments through `/storefront/checkout` (same flow as showcase_website) |

**Collections** — the nav's MEN / WOMEN / KIDS / GIFTING resolve to the tenant's main
categories named *Men*, *Women*, *Kids* and *Gift Boxes* (also *Gifting* / *Gifts*). Any other
online-visible main category is listed after them. The CATEGORY refinement is built from the
sub-categories of the products in the collection (Shawls, Ghutra, Fabrics, Perfumes …), so
set sub-categories on products in the ERP. Listings are one card per size/colour family.

**Product page** — colour swatches and dimension buttons come from the product's variant
family (`parent_id`). Prices are MRP, or the online offer price while a Product Offer runs.
The story, material passport and care copy written for the concept site (`src/content/products.json`)
appear when the product's name contains the reference name (e.g. "Emerald", "Sea Island Shumagh").
Without it the ERP description is shown.

**Photos** — `public/assets/` holds the client's photographs (WeTransfer "For website
Photos-Kashperia", resized to web JPEGs). Page banners and model shots illustrate the editorial
pages. Product shots live in `public/assets/products/` and are shown for a product **only while
it has no image in the ERP** (`src/content/product-photos.json`, matched by name). Boteh, Sea Island
White, Sea Island Shumagh, Shumagh Classics and Italy Shumagh use the reference's curated, ordered
galleries (`public/assets/products/curated/`, from the sample `Maison_Pages/assets`) — upload them
to the products in the admin and the ERP images take over.
The home hero plays `public/assets/film/kashperia-film.mp4` (muted, looped; the poster shows for
visitors who prefer reduced motion).

**Forms** — enquiries and the newsletter open a prefilled email to info@kashperia.com, as in the
reference (no mailing-list provider connected).

## Routes

Clean URLs, so the server must answer every unknown path with `index.html`:

- **Apache**: `public/.htaccess` is copied into `dist/` and does this.
- **nginx**: `try_files $uri $uri/ /kashperia_new/index.html;` (use the site's folder).

Old links still work:

- The reference's anchors: `index.html#kmu-about` → `/maison/about` (also `#kmu-craft`, `#kmu-materials`).
- Earlier hash links: `#/collection/women` → `/collection/women`.

```
/                          home                   /collection/:slug     collection
/maison[/about|/craftsmanship|/materials]         /curated/bestsellers  whole House by priority
/stories  /boutiques  /contact                    /product/:slug        product page
/page/:slug  (kashperia-prive, standards, bespoke, garment-care, shipping, privacy, …)
/checkout                  (Tap returns here with ?checkout=REF)
```

## Setup

```bash
cd other_portals/kashperia_website
cp .env.example .env   # then edit (VITE_TENANT = the Kashperia tenant)
npm install
npm run dev            # http://localhost:5182
npm run build          # static bundle in dist/, served from the domain root
VITE_BASE_PATH=/kashperia_new/ npm run build   # …or from a sub-folder
```

| Variable | Meaning |
| --- | --- |
| `VITE_API_BASE_URL` | Base URL of the API, e.g. `https://kashperia.example.com/api/v1` |
| `VITE_TENANT` | Tenant subdomain — sent as `X-Tenant-Subdomain` header + `?tenant=` param |
| `VITE_CURRENCY` | Currency code printed with prices (`QAR 1,500`) |
| `VITE_COUNTRY_CODE` | Dialling code pre-filled in the checkout mobile field (default `974`) |
| `VITE_BASE_PATH` | Folder the site is served from, e.g. `/kashperia_new/` (default `/`). Build-time only |

Never put Tap keys here: every `VITE_` value is baked into the public bundle. Online payment is
configured per tenant in the admin under **Settings → Online Payments**; until then the
checkout offers to complete the purchase over WhatsApp.

In dev, `VITE_PROXY_TARGET` (default `https://project_manager.test`) is where Vite proxies
`/api` and `/storage`; run with `VITE_API_BASE_URL=/api/v1` to avoid CORS.

## Regenerating the content from the reference

The page fragments were produced from the reference's rendered pages: every `onclick` became a
`data-to="/route"` hook, the concept placeholder images were swapped for the client's
photographs, and forms got `data-form` hooks (see the comment at the top of `PageView.vue`).
Edit the fragments directly for copy changes.
