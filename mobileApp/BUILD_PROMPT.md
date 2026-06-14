# Build Prompt — Astra Salon Flutter App (`mobileApp/`)

> Hand this whole file to the Flutter build agent/session. It is self-contained.
> Source of truth for the API is the Laravel project one level up (`../`); the
> backend already ships a purpose-built mobile API under `routes/api_v1.php`.

---

## 1. Goal

Build a **Flutter (Dart)** point-of-sale + admin app for a **salon** ("Astra
Salon"), living entirely inside the `mobileApp/` folder of this repo. It must:

- Run as a single codebase on **phone and tablet** (responsive/adaptive layout,
  not a stretched phone UI on tablets).
- Target **Android + iOS** (and be web-runnable for quick design review).
- Consume the **existing** Laravel `api/v1` (REST + Sanctum). Do **not** rebuild
  business logic client-side — the server owns stock movements, journal postings,
  duplicate-guarding, etc. The app is a thin, beautiful client.
- Faithfully implement the provided **Astra Salon** designs (see §2), including a
  **selectable color-preset theming system**.

If an endpoint is missing for a screen, prefer adding a small endpoint to the
Laravel API (see §8 "Backend additions") over faking data — the API author
explicitly said "if needed we can add more".

---

## 2. Designs (the visual source of truth)

The look comes from these Claude design files (HTML/CSS). They are **auth-gated**,
so they cannot be fetched programmatically — the **HTML exports must be placed in
`mobileApp/designs/`** (or pasted into the session) before implementing UI.

**Master / design system**
- `Astra Salon - Signature` → **master theme**: typography scale, spacing, corner
  radii, elevation/shadows, component styles (buttons, inputs, cards, list rows,
  bottom nav, app bar, chips, sheets). Everything else inherits from this.

**Screens**
- `Astra Salon - Sale Flow` → the staff **POS sale flow** (cart → customer →
  payment → receipt).
- `Astra Salon - Admin` → **admin home/dashboard**.
- `Astra Salon - Reports` → **reports** (bill-wise & employee-wise).
- `Astra POS - Premium` → premium POS layout variant (treat as the default,
  richest POS look).

**Color presets (settings)** — same design system, different palette seed:
- `Astra Salon - Sage`
- `Astra Salon - Slate`
- `Astra Salon - Aqua`
- `Astra Salon - Premium`

> Implementation rule: extract design **tokens** from *Signature* once
> (typography, spacing, radius, shadow, component shapes). The four presets +
> Premium only swap the **color palette / seed**; they must NOT fork layout.

---

## 3. Tech stack & architecture

- **Flutter 3.x / Dart 3.x**, Material 3 (`useMaterial3: true`) with a custom
  `ThemeExtension` for the Astra design tokens + palette.
- **Adaptive layout**: use `LayoutBuilder` / breakpoints. Phone = single column +
  bottom nav; tablet = master–detail (e.g. catalog list + live cart side panel in
  the sale flow; nav rail instead of bottom bar).
- **State management**: Riverpod (`flutter_riverpod` + `riverpod_annotation`) —
  or Bloc if the agent strongly prefers; pick one and be consistent.
- **Networking**: `dio` with interceptors for (a) base URL + tenant header,
  (b) bearer token, (c) the `{success,data,message}` envelope unwrap + typed
  error mapping (401 → force re-login, 422 → field errors, 403 → "admin only").
- **Routing**: `go_router`, with an auth guard (redirect to PIN login when no
  token) and an admin guard (admin-only routes).
- **Local storage**: `flutter_secure_storage` for the Sanctum token; a simple
  `shared_preferences` for selected theme preset + tenant + base URL.
- **Models**: immutable, `freezed` + `json_serializable` (or manual fromJson if
  avoiding codegen). Mirror the exact JSON in §6.
- **Lints**: `flutter_lints`; format with `dart format`.

**Suggested folder layout inside `mobileApp/`:**
```
mobileApp/
  designs/                 # the exported Astra HTML files (input, not shipped)
  lib/
    app/                   # app root, router, theme presets, design tokens
    core/                  # dio client, env/tenant config, envelope, errors, storage
    features/
      auth/                # PIN login, change PIN, session/user state
      catalog/             # products & services, categories/brands, search, barcode
      sale/                # cart, customer, payment, receipt  (Sale Flow)
      customers/           # customer lookup
      admin/
        dashboard/         # Admin home
        reports/           # bill-wise / employee-wise
        day_session/       # open/close day
      settings/            # theme preset picker, tenant/base URL, change PIN, logout
    shared/                # reusable widgets matching the Signature components
  test/
```

---

## 4. Theming system (must-have)

- One `AstraTheme` built from Signature tokens + a `palette` parameter.
- Presets enum: `signature` (default/master look), `sage`, `slate`, `aqua`,
  `premium`. ("Astra POS Premium" = the `premium` palette applied to the POS.)
- Light **and** dark variants derived from each palette.
- Preset is chosen in **Settings**, persisted, and applied app-wide instantly
  (the existing app calls this "click-and-go" — apply on tap, no Save button).
- Bonus: the web app exposes `GET /api/theme-settings` (public) — optionally read
  it to pick a tenant default preset, but local selection wins.

---

## 5. API integration — connection basics

**Base URL:** the API is mounted under the default `api` prefix, routes live
under `v1`:
```
https://<tenant-subdomain>.<domain>/api/v1/...
```
Example prod-style host: `https://demo.astrasalon.com/api/v1/...`
Local dev host (this repo): `https://project_manager.test/api/v1/...`

**Tenant resolution (IMPORTANT):** `App\Http\Middleware\IdentifyTenant` resolves
the tenant from the **host subdomain**. Public catalog routes use
`:required` mode and **404 if no tenant resolves**. Consequences for the app:

- In production, point the app at the tenant's real subdomain host — tenant is
  implicit, no header needed.
- For dev, the middleware also accepts `?tenant=<sub>` or header
  `X-Tenant-Subdomain: <sub>` **only when the host is literally `localhost` /
  `127.0.0.1`**. Hitting a raw LAN IP or the Android emulator's `10.0.2.2` will
  mis-parse and 404.
- **Recommended:** make the Dio client always send `X-Tenant-Subdomain` from
  config, AND add the tiny backend tweak in §8 so that header is honored on any
  host. This makes emulator/device dev painless. Expose **Base URL + Tenant** as
  editable fields on a first-run / settings screen.

**Auth:** PIN login returns a **Sanctum bearer token**. Send
`Authorization: Bearer <token>` on all authenticated calls. Tokens carry an
**ability**: admins get `admin`, employees get `mobile`. Admin endpoints require
the `admin` ability (middleware `EnsureMobileAdmin`) — gate admin UI on
`user.is_admin`.

**Response envelope** (every endpoint, via `ApiResponseTrait`):
```json
{ "success": true,  "data": <payload>, "message": "..." }
{ "success": false, "message": "...", "data": <optional errors> }
```
- `401` → unauthorized (token missing/expired) → clear token, go to login.
- `422` → validation; `data` is `{ field: [messages] }`.
- `403` → forbidden (non-admin hitting admin route).
- `throttle` on login: `10/min`.

---

## 6. API contract (exact)

> All paths below are relative to `…/api/v1`. "Auth" = bearer token required.
> "Admin" = bearer token with `admin` ability.

### Auth — `#[Group Mobile - Authentication]`
| Method | Path | Body | Returns |
|---|---|---|---|
| POST | `/login` | `{ "pin": "1234" }` (string, max 6) | `{ token, token_type:"Bearer", user }` |
| POST | `/logout` (Auth) | — | `null` |
| POST | `/change-pin` (Auth) | `{ current_pin, new_pin, new_pin_confirmation }` (each max 6; new must differ) | `null` |

`user` (AuthUserResource):
```json
{
  "id": "12", "type": "...", "name": "Jane", "code": "EMP01",
  "email": "...", "mobile": "...", "is_admin": true,
  "designation": "Senior Stylist", "branch_id": "3",
  "sale_day_session_date": "2026-06-14",
  "sale_day_session_status": "open",            // open | closed
  "sale_day_session_opened_at": "2026-06-14 09:00:00",
  "last_closed_session_at": "2026-06-13 21:30:00"
}
```
> Login matches the PIN against all active users; a PIN must map to **exactly
> one** user or login fails. Use `is_admin` to decide which tabs/screens to show.
> Use `sale_day_session_status` to show "Day open/closed" and gate selling if you
> want to mirror the web POS.

### Catalog (tenant required, **no auth**) — products/services & filters
| Method | Path | Key query params | Returns |
|---|---|---|---|
| GET | `/products` | see below | `{ data:[Product], pagination, filters_applied }` |
| GET | `/products/single/` | `product_id` or `barcode` (GetProductRequest) | single Product |
| GET | `/products/{id}` | path id | single Product |
| GET | `/categories` | — | `[{ id, name, product_count }]` |
| GET | `/brands` | — | list |
| GET | `/sizes` | — | list |
| GET | `/colors` | — | list |
| GET | `/branches` | `query`, `user_id`, `assigned_only` | `[{ id, name, code, location, mobile }]` |

`/products` query params:
`product_id, barcode, main_category_id, sub_category_id, brand_id, branch_id,
size, color, min_price, max_price, search, type(product|service),
in_stock_only(bool, default true), sort_by(name|price|mrp|cost),
sort_direction(asc|desc), per_page(1–100, default 15), page`.

> **Salon note:** services (haircut, color, spa) are `type=service`; retail goods
> are `type=product`. The Sale Flow catalog should offer both, likely as two tabs
> or a filter chip. Each sale line can be attributed to a stylist (`employeeId`).

`Product` (ProductResource, key fields):
```json
{
  "id": 101, "code": "SVC-CUT", "name": "Hair Cut", "name_arabic": null,
  "description": "...", "thumbnail": "https://.../img.jpg", "barcode": "...",
  "color": null, "size": null, "model": null, "mrp": 150.0, "priority": 1,
  "time": "30", "unit": {"id":1,"name":"Pcs","code":"PCS"},
  "brand": {"id":2,"name":"..."},
  "main_category": {"id":3,"name":"Hair"}, "sub_category": {"id":9,"name":"Cut"},
  "images": [{ "id":1,"url":"https://...","name":"...","type":"..." }],
  "inventories": [
    { "id":5, "branch": {"id":3,"name":"Downtown"}, "quantity": 12,
      "is_low_stock": false, "is_out_of_stock": false }
  ],
  "total_stock": 12, "is_low_stock": false, "is_out_of_stock": false,
  "stock_quantity_availability_status": "in_stock",
  "available_sizes": [], "related_sizes": []
}
```
`pagination`:
```json
{ "current_page":1,"last_page":4,"per_page":15,"total":52,"from":1,"to":15,"has_more_pages":true }
```

### Sales — `#[Group Mobile - Sales]` (Auth)
| Method | Path | Body / query | Returns |
|---|---|---|---|
| GET | `/sale` | filters (below) | paginated sales list |
| POST | `/sale` | sale payload (below) | `Sale` (201) |
| GET | `/sale/{id}` | path id | `Sale` |

`/sale` list filters: `search, status(draft|completed|cancelled), sale_type,
customer_id, branch_id, from_date, to_date, sort_by(date|invoice_no|paid|
gross_amount|id), sort_direction(asc|desc), per_page(1–100, default 15), page,
mine_only(bool)`.

**Create sale payload (POST `/sale`):**
```json
{
  "customerName": "Walk-in",              // required, max 100
  "phoneNumber": "9876543210",            // optional, max 15 (used to find/create customer)
  "items": [                               // required, min 1
    {
      "productId": 101,                    // required, exists
      "employeeId": 12,                    // optional stylist; defaults to logged-in user
      "quantity": 1,                       // required, > 0
      "unitPrice": 150.0,                  // optional; defaults to product mrp
      "discount": 0                        // optional per-line
    }
  ],
  "discount": 0,                           // optional order-level discount
  "paymentMethod": "Cash",                 // required; matched (LIKE) to a configured method name
  "totalPayment": 150.0                    // required; amount paid
}
```
> Server resolves/creates the customer by phone, resolves the payment method by
> **name** against `cache('payment_methods')`, posts stock + journals via the web
> POS action, and **guards against duplicate** submits within 2 minutes (identical
> items + paid). Handle the duplicate error gracefully (it returns 422).
> The user's `default_branch_id` decides the branch — a user with no branch gets
> an error; surface it.

`Sale` (SaleResource):
```json
{
  "id": "5001", "invoice_no": "INV-0001", "reference_no": null,
  "date": "2026-06-14", "status": "completed", "branch": "Downtown",
  "customer": { "name": "Walk-in", "mobile": "9876543210" },
  "items": [
    { "name":"Hair Cut","type":"service","employee":"Jane","quantity":1.0,
      "unit_price":150.0,"discount":0.0,"total":150.0 }
  ],
  "payments": [ { "method":"Cash", "amount":150.0 } ],
  "summary": { "gross_amount":150.0,"item_discount":0.0,"other_discount":0.0,
               "tax_amount":0.0,"paid":150.0 },
  "created_by": "Jane"
}
```
> Use `Sale` to render the **receipt** screen after checkout and the sale detail.

### Customers — `#[Group Mobile - Customers]` (Auth)
| Method | Path | Query | Returns |
|---|---|---|---|
| GET | `/customers` | `mobile, search, sort_by(name|mobile|id), sort_direction, per_page(default 20), page` | paginated customers |

> Use for the customer lookup step in the sale flow (search by phone).

### Admin — `#[Group Mobile - Admin]` (Admin ability required)
| Method | Path | Query / body | Returns |
|---|---|---|---|
| GET | `/admin/dashboard` | `from_date, to_date, branch_id` (default today/all) | dashboard blocks |
| GET | `/admin/reports` | `type(billwise|employeewise)` req, `startDate, endDate, employee_id` | report rows |
| POST | `/admin/day-status` | `{ "date": "YYYY-MM-DD" }` (+ closing rules) | toggled session |

`/admin/dashboard` →
```json
{
  "date": "2026-06-14",
  "todaySummary": [
    { "title":"Today's Sales","value":4200.0,"type":"currency" },
    { "title":"Today's Bills","value":18,"type":"count" }
  ],
  "inventoryOverview": [
    { "title":"Active Employees","value":6,"type":"count" },
    { "title":"Customers","value":540,"type":"count" },
    { "title":"Products","value":120,"type":"count" },
    { "title":"Services","value":35,"type":"count" }
  ],
  "bussinessOverview": [
    { "title":"weekly sales","value":21000.0,"percentage":"12%" },
    { "title":"Monthly sales","value":86000.0,"percentage":"-4%" }
  ]
}
```
> Note the spelling `bussinessOverview` (keep it as-is when parsing). Render the
> `percentage` strings as up/down deltas.

`/admin/reports` →
```json
{
  "type": "billwise",
  "period": { "start_date":"2026-06-01","end_date":"2026-06-14" },
  "filters": { "employee_id": null },
  "rows": [ /* billwise: {id,invoice_no,date,customer,gross_amount,discount,paid}
               employeewise: {employee_id,employee_name,bills_count,items_count,revenue} */ ]
}
```

`/admin/day-status` toggles the branch day session (opens if closed, closes the
open one). Returns `{ message, status, session }`. Drive the button label from
`user.sale_day_session_status`.

---

## 7. Screens to build (map to designs)

1. **Onboarding / connection** — base URL + tenant subdomain (dev), then PIN.
2. **PIN login** (Signature look) → store token + user; route by `is_admin`.
3. **Staff home / Sale Flow** (Sale Flow + Astra POS Premium):
   - Catalog: services/products tabs, search, category/brand filter chips,
     barcode scan (`mobile_scanner`) → `/products?barcode=`.
   - Cart: add lines, qty, per-line discount, **assign stylist per line**.
   - Customer step: search `/customers` by phone or quick "walk-in".
   - Payment step: choose method + amount → POST `/sale`.
   - Receipt: render returned `Sale`; allow new sale. (Optional: share/print.)
   - Tablet: catalog + live cart side-by-side (master–detail).
4. **Sales history** — `/sale` list with filters + `mine_only` toggle; tap → detail.
5. **Admin dashboard** (Admin) — the three blocks above as cards/sections; branch
   + date-range filter; day open/close button (`/admin/day-status`).
6. **Reports** (Reports) — segmented bill-wise / employee-wise; date range +
   employee filter; tabular/cards; totals.
7. **Settings** — **theme preset picker** (Sage/Slate/Aqua/Premium/Signature,
   light/dark), change PIN, base URL/tenant, logout.

Empty/loading/error states everywhere (skeletons preferred), and offline-aware
errors. Currency + date formatting via `intl` (locale-aware).

---

## 8. Backend additions (add to the Laravel API as needed)

These are gaps the screens will hit; implement them in `../` following the
existing `Api/V1` controller + Action + Resource + FormRequest pattern and the
`ApiResponseTrait` envelope. Confirm with the maintainer before large changes.

1. **`GET /api/v1/payment-methods`** (Auth) — list configured payment method
   names/ids (from `cache('payment_methods')` / Accounts) so the payment step
   isn't hardcoded. The create-sale flow matches `paymentMethod` by name.
2. **`GET /api/v1/employees`** (Auth) — active employees (id, name, designation)
   for the per-line **stylist picker** and the reports `employee_id` filter.
3. **Tenant header everywhere** — tweak `IdentifyTenant` to honor
   `X-Tenant-Subdomain` (and/or `?tenant=`) on **any** host, not just localhost,
   so emulator/device/IP dev resolves the tenant. (Small, low-risk change.)
4. (Optional) **`GET /api/v1/admin/day-status`** to fetch current session without
   re-login, if the dashboard needs live refresh.

Keep all additions tenant-scoped and ability-gated exactly like the existing
routes.

---

## 9. Deliverables & acceptance

- A running Flutter project under `mobileApp/` (`flutter run` on Android + iOS,
  and `flutter run -d chrome` for design review).
- All screens in §7 implemented against the real API, matching the Astra designs
  and Signature design tokens; preset theming working and persisted.
- Dio client with tenant header, bearer auth, envelope unwrap, and the 401/403/
  422 handling in §5.
- Typed models for every payload in §6.
- Auth + admin route guards.
- `flutter analyze` clean; `dart format` applied; a few widget/unit tests for the
  envelope parser, sale-total math, and auth/role routing.
- A `mobileApp/README.md` documenting: how to set base URL + tenant, how to log
  in (PIN), the preset system, and any backend additions made.

## 10. Build order (suggested)

1. Scaffold project, theme tokens from *Signature*, preset system, Dio client +
   tenant/auth/envelope interceptors, secure storage, router with guards.
2. PIN login + session/user state + role routing. Verify against the live API.
3. Catalog + Sale Flow + receipt (the core staff loop), tablet master–detail.
4. Sales history + detail; customers lookup.
5. Admin dashboard, reports, day open/close.
6. Settings (preset picker, change PIN, base URL, logout). Polish, empty/error
   states, tests, README.

Confirm the design HTML is present in `mobileApp/designs/` before starting UI work.
