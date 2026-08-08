# Offline New Sale — implementation guide (mobileApp)

**Goal:** ring up a sale on the New Sale screen with no network, print the receipt, and
have the sale land on the server exactly once when connectivity returns.

**Status:** plan only — no code written yet.
**Date:** 2026-08-09

---

## 1. What the New Sale screen does today

### Files in the flow

| Layer | File |
|---|---|
| Catalog grid | `mobileApp/lib/features/sale/screens/v3/new_sale_screen.dart`, `new_sale_catalog_views.dart` |
| Catalog state | `mobileApp/lib/features/sale/logic/catalog_cubit/catalog_cubit.dart` |
| Ticket state | `mobileApp/lib/features/sale/logic/cart_cubit/cart_cubit.dart` |
| Checkout | `mobileApp/lib/features/sale/screens/v3/review_pay_screen.dart` → `logic/sale_ops_cubit/sale_ops_cubit.dart` |
| Transport | `mobileApp/lib/features/sale/domain/services/sale_service.dart`, `shared/domain/services/lookup_service.dart`, `shared/utils/router/http_utils/http_service.dart` |
| Server | `app/Http/Controllers/Api/V1/SaleController.php` → `app/Actions/V1/Sale/CreateAction.php` → `app/Actions/Sale/CreateAction.php` |

### Every network call the screen makes

1. `GET /products` — paged, 20/page; refetched on search (350 ms debounce), category change,
   type change, branch change. Also `?barcode=` on every scan.
2. `GET /categories` — per type.
3. `GET /settings/sale` — default qty, tip enabled, default product type, **and the thermal
   print config** (`cart_cubit.dart:233`, `syncSaleSettings()`).
4. `GET /customers?mobile=` / `?search=` — client sheet.
5. `GET /employees` — stylist sheet.
6. `GET /payment-methods` — custom split sheet.
7. `POST /sale` — the charge itself.
8. Receipt: **already on-device** (`shared/widgets/receipt_pdf.dart`) — no call.
   Logo is cached in prefs (`astra.print.logoData`).

### What already works offline

App boot (`auth_cubit.dart:59` restores from cached token + cached user JSON), theme,
currency rates, print settings, receipt PDF generation.

**Nothing else on this screen does.** The catalog is fetched every time and never persisted.

---

## 2. Recommended approach

> **Local snapshot + durable outbox, with an offline-first decorator on `SaleRepository`,
> backed by one sqflite database.**

### Three decisions, and why

**Storage: add `sqflite`, one `invo_offline.db`. Not `shared_preferences`.**
The outbox is the part that must never lose a sale, and the prefs model is
"decode blob → mutate → re-encode → rewrite whole blob" — a process kill between two
queued sales rewrites the file with only one of them. The catalog is the other half: a
3–8 MB JSON blob decoded on every cold start and held in RAM is the wrong shape when the
grid already pages. sqflite gives an indexed `LIKE` search, so `CatalogCubit`'s existing
paging/search/category UI works unchanged against local rows. One dependency solves both,
and it gets reused later for offline returns and stock check.
Keep `LocalStorageService` exactly as-is for scalars.

**Offline detection: outcome-based, not `connectivity_plus`-based.**
A device on shop wifi with a dead uplink still reports "connected". Treat any
`DioException` of type `connectionError` / `connectionTimeout` from `POST /sale` as
"queue it". Add `connectivity_plus` only as a *hint to try syncing sooner*, with a 60 s
periodic timer as the real trigger.

**Wiring: decorate, don't rewrite.**
Register `SaleRepository` in `shared/utils/service_locator_setup/setup.dart` as
`OfflineFirstSaleService(SaleService())`. `review_pay_screen._charge()` keeps calling
`_ops.createSale(...)` and keeps getting a `Sale` back — one built locally when the POST
couldn't land. Same idea for `CatalogCubit` via a `CatalogSnapshotService`.
**The two cubits and the screens barely change.** That is the point of the decorator here.

### The pieces

#### Catalog snapshot
Full pull of products + categories + payment methods + employees + sale settings, stored in
sqflite. Refreshed on login, on branch change, and on app resume when older than N hours.
Cache **per `branch_id`** — the catalog is branch-scoped today.

Product images: keep `Image.network` with the existing `cacheWidth` handling. Flutter's HTTP
cache covers most repeat views and a missing thumbnail is cosmetic — do **not** build an
image downloader in v1.

Show a "Catalog from 14:20 today" chip whenever stale data is being served.

#### Outbox
One row per pending sale:

| Column | Purpose |
|---|---|
| `client_uuid` | v4, generated the moment Charge is tapped — the idempotency key |
| `payload` | the exact `cart.toPayload()` JSON, verbatim |
| `client_created_at` | true till timestamp (display/ordering only) |
| `branch_id`, `user_id` | who/where |
| `attempts`, `last_error` | retry bookkeeping |
| `status` | `pending` \| `syncing` \| `failed` \| `synced` |
| denormalised totals, lines, customer | so the invoice/receipt screens can render it |
| `server_sale_id`, `invoice_no` | filled in at sync |

`cart.toPayload()` already produces the exact server contract — persist it verbatim so the
sync engine stays a dumb replayer.

#### Idempotency
The `client_uuid` goes to the server, which returns the existing sale if it has already seen
that uuid. **This is the single most important backend change** — see §3.

#### Invoice numbering
Server-assigned, always. `getNextSaleInvoiceNo()` (`app/Helpers/helper.php:746`) is a
tenant-wide counter; a device cannot safely guess it.

Offline, print a **provisional** reference (`OFF-<device>-0042`) with a visible
"PROVISIONAL — tax invoice to follow" line. Store the real `invoice_no` on the outbox row at
sync and offer "Reprint with invoice no.".

> ⚠️ **Confirm before shipping Phase 2:** if this tenant is under GCC e-invoicing rules, a
> provisional number on a handed-over receipt may not be acceptable.

#### Stock
`prevent_out_of_stock_sales` defaults to `yes` (`app/Support/Sale/OutOfStockSales.php:19`),
so an offline sale that oversells is **rejected at sync — after the goods have left the shop**.

Mitigation: cache `total_stock` per product in the snapshot, decrement it locally per queued
sale, and **warn (don't block)** the cashier at add-to-cart. At sync, a stock rejection must
land in a "Needs attention" list, never be silently dropped.

#### Explicitly out of scope offline
State this in the UI:

- editing or returning an unsynced sale
- drafts
- day open / close
- Sales list, Reports

All are `editingSaleId` / server-id dependent and would need a second reconciliation layer
for very little value on the floor.

---

## 3. Backend changes required

1. **Migration** — `sales.client_uuid` (nullable char(36), unique per `tenant_id`),
   `sales.client_created_at` (nullable timestamp).
2. **`app/Http/Requests/V1/Sale/StoreRequest.php`** — accept `clientUuid` (`nullable|uuid`)
   and `clientCreatedAt` (`nullable|date`).
3. **`app/Actions/V1/Sale/CreateAction.php`** — before anything else,
   `Sale::where('client_uuid', $uuid)->first()` → return it as a 200 with the normal
   `SaleResource`. Idempotent replay, not an error.
4. **Bypass `guardAgainstDuplicate()` when `client_uuid` is present.**
   This one will bite if missed. That guard rejects any byte-identical sale from the same
   user within 2 minutes (`CreateAction.php:23`). Syncing a backlog posts ten sales in ten
   seconds — two customers who each bought the same single item would collide and one gets
   refused. The uuid makes the guard redundant and harmful.
5. **`SaleResource`** — emit `client_uuid` so the app can match a synced sale back to its
   outbox row.
6. **Day session / date — the sharpest constraint.**
   `Sale::creating` (`app/Models/Sale.php:91`) attaches the branch's currently-open session
   and **overwrites `date` with that session's `opened_at`**. A sale rung Monday and synced
   Tuesday is stamped Tuesday and lands in Tuesday's cash-up.

   Recommendation: do **not** change that hook — web relies on it. Instead:
   - **block day-close while the outbox is non-empty** (mobile guard + server check in the
     day-close action), and
   - store `client_created_at` for audit.

   Genuine cross-day offline becomes a separate decision about session-date resolution.
7. `POST /sale/{id}/receipt` is unaffected — the app prints locally.

**No batch endpoint.** Sequential single POSTs are idempotent, resumable, and give per-sale
error attribution. A batch endpoint invents partial-failure semantics you then have to own.

---

## 4. Phased plan

### Phase 0 — Don't crash offline *(no queue yet; ships alone)*

- `pubspec.yaml`: add `sqflite`, `path`, `uuid`, `connectivity_plus`.
- **New** `mobileApp/lib/shared/utils/local_storage/offline_db.dart` — open/migrate, schema v1.
- **New** `mobileApp/lib/shared/domain/services/catalog_snapshot_service.dart`
  \+ `shared/domain/repository/catalog_snapshot_repository.dart` — write snapshot; read
  paged / searched / category-filtered / by-barcode.
- **Edit** `catalog_cubit.dart` — `load()`, `loadMore()`, `findByBarcode()` fall back to the
  snapshot on network failure.
- **Edit** `catalog_state.dart` — add `servingCached`, `cachedAt`.
- **Edit** `review_pay_screen.dart` — "You're offline — this sale can't be saved yet"
  instead of a generic error.

**Testable:** airplane mode → catalog browses, scans resolve, cart maths works, charge
refuses cleanly.

### Phase 1 — Backend idempotency *(ships alone; no app change)*

- Migration + `StoreRequest` + V1 `CreateAction` uuid lookup + duplicate-guard bypass +
  `SaleResource` field.

**Testable:** POST the same payload with the same uuid twice → one sale, two 200s.

### Phase 2 — Outbox + local commit

- **New** `mobileApp/lib/features/sale/domain/services/offline_sale_service.dart` — the
  `SaleRepository` decorator.
- **New** `mobileApp/lib/shared/domain/repository/outbox_repository.dart`
  \+ `shared/domain/services/outbox_service.dart`.
- **Edit** `cart_cubit.dart` — `toPayload()` gains `clientUuid` / `clientCreatedAt`.
- **Edit** `shared/domain/models/sale.dart` — locally-built `Sale` carries a provisional ref
  and `isPending`.
- **Edit** `invoice_screen.dart` + `receipt_pdf.dart` — provisional banner; hide Edit/Return.
- **Edit** `setup.dart` — register the decorator.

**Testable:** offline charge → receipt prints, ticket clears, row survives a force-quit.

### Phase 3 — Sync engine + visibility

- **New** `mobileApp/lib/shared/logic/sync_cubit/sync_cubit.dart` + `sync_state.dart` —
  serial drain (one at a time, oldest first), exponential backoff; triggered on connectivity
  regain, app resume, 60 s timer, and manual pull.
- **New** `mobileApp/lib/features/sale/screens/v3/pending_sales_screen.dart` — queued +
  failed sales, per-row retry, error text.
- **Edit** app bar / `astra_drawer.dart` — pending badge.
- On success: store real `id` + `invoice_no`, mark `synced`, offer "Reprint with invoice no.".

**Testable:** queue 5 offline, restore network → all 5 land exactly once. Kill the app
mid-drain → nothing duplicates or vanishes.

### Phase 4 — Guards

- Block day-close (mobile **and** server) while pending sales exist.
- Local stock decrement + soft warning; "Needs attention" treatment for stock/validation
  rejections — these are 422s and must **never** be auto-retried.
- Auto-purge `synced` rows older than 7 days.

---

## 5. Risks that will actually bite

| Risk | Detail | Mitigation |
|---|---|---|
| **Day-session date drift** | Offline sale stamped with the sync-day's session | Day-close block (§3.6) — not optional |
| **Two tills, same last unit** | Both sell it offline, one is rejected at sync | Unavoidable; must surface as a task, not a toast |
| **Clock skew** | `client_created_at` comes from a device clock a cashier can change | Use for ordering/display only, never for accounting periods |
| **App killed mid-write** | Cart cleared but sale not queued | One sqflite transaction; clear the cart only after the insert commits |
| **422 retried forever** | Burns battery, fills `api_logs` | Classify terminal vs retryable by status code, never `catch (_)` |
| **Silent auth expiry** | Token expires while offline → whole backlog 401s | `HttpService.onUnauthorized` force-signs-out today; it must **not** wipe the outbox — verify in Phase 3 |
| **Catalog staleness** | Price changed on web 6 h ago; server takes `unitPrice` from the payload, so the wrong price commits silently | "Catalog from …" chip; optionally a hard staleness cutoff that blocks offline selling |

---

## Open questions for the business

1. Is a **provisional** receipt number acceptable to hand a customer, given local tax rules?
2. Should offline selling be **blocked** past a staleness threshold (e.g. catalog > 24 h old)?
3. Should an offline sale that **oversells** be allowed to commit at sync (negative stock), or
   held for manual resolution?
