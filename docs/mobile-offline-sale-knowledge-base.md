# Offline POS — knowledge base

**Purpose.** Everything a fresh conversation needs to continue this work without re-deriving it:
the decisions and *why* they were made, the traps already hit, the invariants that must not be
broken, and exactly what is and isn't verified.

**How to use it.** Point a new session at this file first. Read §3 (invariants) and §8 (traps)
before touching any of the code in §6 — most of the expensive mistakes in this feature were made
once already and are recorded there.

**Companions:** [mobile-offline-sale-plan.md](mobile-offline-sale-plan.md) is the design;
[mobile-offline-sale-tasks.md](mobile-offline-sale-tasks.md) is the task ledger.

**Last updated:** 2026-08-09

---

## 1. State in one paragraph

The Flutter POS (`mobileApp/`, package `invo`) can ring up a sale and park a draft with no network,
**on the New Sale screen only**. The catalog and the reference lists are served from a sqflite
snapshot; a sale that cannot reach the server is queued in a durable outbox and replayed until
acknowledged, then deleted from the device. Held sales appear in the Sales list and can be corrected
there. The server treats a device-generated `client_uuid` as an idempotency key, so a replay never
creates a second sale. An app-wide strip tells the cashier the till is offline, and escalates when
the cached catalog ages. **Verified by automated tests only — nothing has run against a live server
or a real device.**

**Sale returns are online-only.** An offline-return stack was built and rolled back — §7.13.

| | Value |
|---|---|
| `flutter analyze lib test` | clean |
| `pint --test app/ database/ routes/ tests/` | pass |
| Mobile tests | 322 pass, 1 skipped (135 of them for this feature) |
| Backend tests for this feature | 19 pass |
| PHP Feature suite | **16 failed** — the same pre-existing failures as the baseline. Passing count drifts as other sessions add tests |

The 16 remaining failures are pre-existing and in files this work never touched — see §9.

### The governing constraints (set 2026-08-09 — read these first)

**Offline is a matter of minutes or hours, never days. It covers the POS New Sale screen only. And
it belongs to the app.**

Laravel is unchanged apart from the two things only a server can do:

1. recognise a replayed `client_uuid` and return the row it already committed, and
2. honour `clientUserId`, so a shared till's takings are filed under the cashier who served the
   customer rather than whoever happened to sync them.

Everything else — deciding whether the device is online, what the queue holds, what date a capture
happened on — is the app's.

Two rounds of work were written, tested and then **reverted** under these constraints: server-side
day-session/date resolution plus an oversell allowance plus a cross-device till register (§7.12), and
the entire offline **sale-return** stack (§7.13). Both are recorded so they do not get rebuilt by
accident.

---

## 2. How the user works (carry this forward)

- **Prompt-generation pattern.** The user often writes `generate prompt "<rough idea>"`. That means:
  turn the rough idea into a polished, paste-able prompt — **do not start implementing**. They then
  usually follow with "implement it".
- **Terse, typo-heavy instructions.** e.g. `no need clientBranchId only use the logged user last
  branch id therer`. Read for intent; the intent is usually a real simplification and usually right.
  That particular one removed a whole validation path and made the design better.
- **They want the work finished, not surveyed.** "do the pending", "implement it". Deliver, then
  report honestly.
- **They interrupt mid-turn.** Messages arrive alongside tool results. Address them in the same turn.
- **Docs are expected deliverables**, not extras. They asked for a plan doc, then a task list, then
  this KB.

### Hard project rules (from repo skills + memory)

- **Never run `dart format`** on `mobileApp/` — the codebase uses a compact hand style and formatting
  produces ~6000 lines of churn. Verify with `flutter analyze lib test`.
- **Never blind-tap the running simulator** — it writes to the *real* backend. A mis-tap has charged
  a real sale. Screenshot before every tap; never drive the New Sale flow to "test" something.
- **A background auto-committer commits mid-session.** Never `git stash` for a baseline; use a
  worktree. Also: the working tree routinely contains other sessions' work — do not claim it.
- Follow the flutter-apps / flutter-code-standards / mobile-api-v1 / multi-tenancy skills.

---

## 3. Invariants — break these and money is lost

1. **Every outbox row is money already collected.** Losing one, or committing one twice, is the worst
   possible outcome. Weight every design decision that way.
2. **Only an *unreachable* server queues a sale.** A server that answered — any `ApiException`, any
   5xx — is a real answer and is surfaced. Queuing on a 5xx would risk a duplicate the cashier never
   sees, because the sale may already have committed. Single definition:
   `shared/utils/router/http_utils/reachability.dart → isServerUnreachable()`.
3. **The idempotency key belongs to the TICKET, not to a press of Charge.** `CartCubit` holds
   `_chargeUuid` and retires it in `emit` only when the ticket actually changes. A fresh key per
   press would turn a second press (after a visible error) into a second committed sale — see §7.2.
4. **The cart is cleared only after the outbox row is durably written.** Nothing after the durable
   write may throw its way back to the caller, or a captured sale is reported as failed and gets rung
   up again.
5. **Sign-out clears the catalog but NEVER the outbox.** An unsynced sale must outlive the session
   that took it, including a forced sign-out on a 401.
6. **A schema upgrade must never touch `outbox_sales`.** A device upgrading mid-shift may be holding
   money in it. Every migration so far only ADDS tables; new capability gets a new table rather than
   an ALTER that could fail halfway.
7. **The branch is never accepted from the request.** It follows the resolved cashier's own
   assignment. (The outbox stores `branchId` locally to scope cached stock; it never leaves the
   device.)
8. **An empty fetch never wipes a working cached list.** An empty page is far likelier to be a failed
   fetch than a business with no staff/products.
9. **`failed` is the only status that offers Discard** — the one action that can lose takings. So
   misclassifying a transient failure as terminal is not merely a slow retry; it invites deletion.
10. **A correction to a queued sale keeps its identity** — same `clientUuid`, same provisional
    reference, same capture time. A new key would let the server commit both the version being
    replaced and its replacement; a new reference would orphan the receipt the customer is holding.
    This is why `CartCubit.emit` *pins* the key while a correction is loaded, inverting its normal
    retire-on-change behaviour.
11. **A draft is not takings.** It moves no stock and does not block a day close. Treating it as
    money would leave a cashier unable to close up because somebody left a quote on the screen.
12. **An offline sign-in only ever restores a session this device already had.** A server that
    *answered* and refused is never second-guessed from the cache — only an unreachable one falls
    back to the roster. The restored session carries exactly the permissions the last online sign-in
    cached, and the roster is wiped when the device is pointed at another tenant.
13. **An acknowledged row is deleted immediately.** The outbox holds only what the server does not
    have. Keeping synced rows left a second, permanently-staling copy of a sale the Sales list now
    serves — and the device is online by definition at that moment.
14. **The reference printed on the customer's receipt must reach the server on the syncing POST.**
    An offline sale is receipted under a device-minted `OFF-<tag>-<seq>`, and invariant 13 destroys
    the local copy of it at the exact moment the sale becomes real. So `_push` sends it as
    `offlineRef`, read off the row (not the payload, which a correction rebuilds), and the server
    stores it in `sales.reference_no` — already displayed and already searchable. Without it, a
    customer returning with a receipt from an outage could not be matched to their own sale.

---

## 4. Architecture and the reasoning behind it

### 4.1 The decorator pattern (used twice)

`SaleRepository` and `LookupRepository` are each registered as an **offline-first decorator wrapping
the online service**. This is why the screens barely changed: `review_pay_screen._charge()` still
calls `_ops.createSale(...)` and still gets a `Sale` back — one built locally when the POST couldn't
land.

```
setup.dart
  SaleRepository    -> OfflineFirstSaleService(SaleService())
  LookupRepository  -> OfflineFirstLookupService(LookupService())
  OfflineSyncCubit  -> OfflineSyncCubit(onlineSales)   // the UNWRAPPED SaleService
```

**`OfflineSyncCubit` must get the unwrapped `SaleService`.** Posting through the decorator would
re-queue the sale it is trying to drain.

**Products/categories are deliberately NOT decorated.** `CatalogCubit` does its own fallback because
it has to *show* that the grid is a stored copy (`servingCached` / `cachedAt` → the "Offline · catalog
from 12 min ago" chip). A silent fallback would take that away from it.

### 4.2 Why `beginCharge()` returns three things

The POST body has no product names, employee names or tax rates, so it cannot render a receipt on its
own. `CartCubit.beginCharge()` returns `(clientUuid, payload, offlineSale)` — minting the key in two
places would queue a sale under an id the server never saw, and the retry would ring it up twice.

Passing `offlineSale` is also **how a caller opts into offline capture**. A draft opts in too —
`beginCharge(status: 'draft')` — because losing a parked ticket to a dropped connection means retyping
it in front of the customer. Editing a *committed* sale omits it and stays online-only, since it needs
a server id on both ends; correcting a *held* sale goes through `editPending` instead (§7 / invariant
10), which rewrites the outbox row rather than posting anything.

### 4.3 What actually triggers a sync

Four things, and the fourth is the one that matters most in a shop:

| Trigger | Backoff | Note |
|---|---|---|
| `bootstrap()` after sign-in | respected | Also recovers rows left `syncing` by a process kill |
| `Timer.periodic(60s)` | **respected** | The floor that guarantees progress. Also how the dead-uplink case is discovered at all, since it is the only trigger that makes a request while nothing else thinks anything changed |
| OS `onConnectivityChanged` (an interface appeared) | ignored | Only a hint — and it **never fires** for a till that kept its wifi the whole time |
| App resumed (`didChangeAppLifecycleState`) | ignored | Dart timers are throttled while backgrounded, so this is what covers a phone that was in a pocket |
| `ConnectivityCubit` flipping to **online** | ignored | The strongest signal there is: it flips only when a request actually got an answer, which is proof of reach |

The last one is subscribed on the transition only. `ConnectivityCubit` also emits when
`hasInterface` changes, and every successful request keeps it online — draining on each of those
would post the queue from inside its own drain.

"Ignored backoff" is deliberate wherever the *condition that caused the failures has changed*:
making a row wait out fifteen minutes it earned while the network was down strands takings for no
reason. The periodic timer respects backoff, because there nothing has changed.

### 4.3b Signing in with no network

A shared till hands over between cashiers all day. An offline till that cannot authenticate the next
cashier can take no further sales the moment anyone locks or signs out — so the queue would hold what
was already rung up and nothing more, which is the failure offline selling exists to prevent.

`DeviceAccountStore` keeps a roster (secure storage, capped at 12) of everyone who has signed in on
this device: their cached `ApiUser` payload, their API token, and the PIN or credential they used.
`_runLogin` falls back to it **only** when the server was unreachable — an `ApiException` is a real
verdict and is surfaced untouched.

The boundaries are the whole design:

| | |
|---|---|
| Who can get in | Only a user who has signed in **on this device** before |
| When | Only when the server could not be reached at all |
| With what | Exactly the permissions their last **online** sign-in cached |
| Token | The one that sign-in issued, so the outbox drain posts under the right user |
| Sign-out | Keeps the roster — signing out *is* the handover |
| Tenant change | Wipes it, because a PIN for one business must not open another |

A user deactivated server-side since their last sign-in does get in, and keeps selling into the
outbox. Their first request on reconnect 401s onto the existing forced-sign-out path, and the sale
they took is attributed via `clientUserId`, which the server validates (active + same tenant) and
falls back on if it no longer holds. The secrets sit in `flutter_secure_storage` — the same store this
app already trusts with the PIN it replays for biometric sign-in.

### 4.4 How "offline" is decided

`ConnectivityCubit` weights two sources unevenly, on purpose:

| Signal | Verdict |
|---|---|
| A request got **any** response (even a 500) | **Online** — the server was reached |
| A request got **no** answer | **Offline** |
| OS reports **no network interface** | **Offline**, immediately |
| OS reports an interface is present | **Nothing** |

The asymmetry is the whole design: a till on shop wifi with a dead uplink reports "connected" all
day. Before the first signal the status is `unknown` and no banner shows, so a cold start never cries
wolf. Every request reports through **one Dio interceptor** in `HttpService`.

### 4.5 Employee attribution (the subtlest part)

A shared till is signed in and out all day, so the cashier draining the queue is often not the one who
served the customer.

- The queued payload carries **`clientUserId`** only.
- `V1\Sale\CreateAction::resolveCashier()` honours it only if the named user is **active and on this
  tenant** (the tenant scope on the lookup is what stops one business claiming another's staff).
- An unrecognised claim **falls back to the poster** — it must never cost the sale.
- The **branch follows that cashier**, never the request.

An earlier version gated the drain on ownership instead. That stopped misfiling but stranded an
employee's takings until they signed back in, and deadlocked day-close. Rejected.

### 4.6 Provisioning order (first run)

`products → categories → payment methods → staff → customers → sale settings + currencies`

Each step is independent: losing the staff list must not also cost the payment methods. Failures are
collected into `provisionIncomplete` and surfaced as a persistent strip, because **the screen
underneath looks ready either way**.

**Recent sales are deliberately NOT cached.** Offline reprint of a queued sale is already served by
the outbox, and offline returns are out of scope — so it would earn nothing today while adding a
large, constantly-stale table.

---

## 5. The contract

### `POST /api/v1/sale` — the whole offline contract

Four optional fields, and nothing else changed for offline. `POST /api/v1/sale-return` takes none
of them: returns are online-only.

| Field | Type | Meaning |
|---|---|---|
| `clientUuid` | uuid, nullable | Idempotency key, minted per ticket / per draft. Presence also **skips the duplicate heuristic**, which would otherwise refuse two genuinely different customers during a backlog drain. |
| `clientCreatedAt` | date, nullable | The till clock. Audit + queue ordering **only** — never the accounting date. |
| `clientUserId` | integer, nullable | The cashier who took the sale. Honoured only if active + same tenant; anything unrecognised silently falls back to the poster, because a bad claim must not cost the sale. |
| `offlineRef` | string ≤40, nullable | The reference the device printed on the customer's receipt (`OFF-7K2-0042`). Stored in **`sales.reference_no`**. Sent only by the drain, so an online sale leaves that field free for the back office. |

Response: `SaleResource` echoes `client_uuid`; both resources return `reference_no`, and
`SaleListResource` returns `client_uuid` too — that pair is what tells the app the reference is a
printed receipt number rather than something typed in the back office.
**Success status is `201`, not `200`** — use `assertSuccessful()` in tests.

There is deliberately **no** `offlineCaptured` flag, no client-supplied `items.*.tax`, and no
day-session resolution from the till clock. All three existed briefly and were reverted — §7.12.

### `sales` table

`client_uuid` (uuid, nullable) + `client_created_at` (timestamp, nullable), unique on
`(tenant_id, client_uuid)`.
Migration: `2026_08_09_000001_add_client_uuid_to_sales_table.php`.

The printed offline reference reuses the **existing `reference_no`** column — no new column and no
migration. That was a deliberate call (2026-08-10): a dedicated `offline_ref` was built first and
replaced on instruction. It works cleanly because `reference_no` is already in `$fillable`, already in
`scopeFilter`'s search, already on both V1 resources, already on the web sale view and already an
optional web list column — so one write made the number visible and searchable everywhere at once.
The one thing to know: `App\Actions\Sale\JournalEntryAction` and `ReceiptAction` copy `reference_no`
onto the journal entry, so an offline sale's journal now references the customer's receipt number
(desirable). `BuildDaySessionReportAction` is **unaffected** — its `reference_no` key is populated
from `invoice_no`, not from this column. Residual risk: the web sale form binds this field, so
someone editing a synced offline sale on the web can overwrite the receipt number.

`sale_returns` carries **neither** — it briefly did, and the columns were dropped with the
offline-return rollback (§7.13). Migrations `…000002` (offline till states) and `…000003`
(sale-return client_uuid) were both created and then removed, so the gap in the sequence is
deliberate, not a lost file.

### `OfflineDb` — sqflite, schema **v2**

| Table | Holds |
|---|---|
| `catalog_products` | per-branch product snapshot; index columns + the server's raw JSON in `payload` |
| `catalog_categories` | per-branch, per-type-filter category lists |
| `catalog_meta` | when each branch's snapshot was taken |
| `outbox_sales` | queued sales — **never dropped by an upgrade or a sign-out** |
| `snapshot_lookups` | payment methods / staff / customers, one table with a `kind` discriminator |

Rows store the server's own JSON and lift out only what must be queryable, so a cached row parses
through exactly the same `fromJson` as a live response. **Exception:** `total_stock` is read from the
column, not the payload, because it is the one field the device writes back (see §7.9).

---

## 6. File map

### Backend

Deliberately small — see the governing constraint in §1.

| File | Role |
|---|---|
| `database/migrations/2026_08_09_000001_add_client_uuid_to_sales_table.php` | schema |
| `app/Models/Sale.php` | `client_uuid`, `client_created_at` fillable |
| `app/Http/Requests/V1/Sale/StoreRequest.php` | accepts the three client fields |
| `app/Actions/V1/Sale/CreateAction.php` | replay lookup, guard bypass, `resolveCashier()` |
| `app/Http/Resources/V1/Sale/SaleResource.php` | echoes `client_uuid` |

Nothing under `SaleReturn` is involved — returns are online-only.

### Mobile — new

```
lib/shared/utils/local_storage/offline_db.dart              sqflite schema + migrations
lib/shared/utils/router/http_utils/reachability.dart        isServerUnreachable + networkErrorMessage
lib/shared/domain/repository/catalog_snapshot_repository.dart
lib/shared/domain/services/catalog_snapshot_service.dart    catalog + lookup snapshot
lib/shared/domain/services/offline_first_lookup_service.dart decorator: payment methods/staff/customers
lib/shared/domain/services/sale_settings_sync.dart          pullAndCacheSaleSettings()
lib/shared/logic/connectivity_cubit/                        online/offline truth
lib/shared/widgets/offline_banner.dart                      offline + provisioning + incomplete strips
lib/features/sale/domain/models/pending_sale.dart
lib/features/sale/domain/repository/outbox_repository.dart
lib/features/sale/domain/services/outbox_service.dart
lib/features/sale/domain/services/offline_sale_service.dart decorator: queue-or-rethrow
lib/features/sale/logic/offline_sync_cubit/                 drain + catalog pull + provisioning
lib/features/sale/screens/v3/pending_sales_screen.dart      list + PendingSalesBadge
```

`CatalogFreshness` / `catalogAgeLabel` live in `catalog_snapshot_repository.dart` — one definition of
the staleness thresholds and one wording of the age, so the strip and the grid chip cannot disagree.

### Mobile — modified

`app.dart` (sync bootstrap, lifecycle, banner), `setup.dart`, `cart_cubit.dart` (+`cart_state.dart`),
`catalog_cubit.dart` (+state), `sale_repository/service`, `sale_ops_cubit.dart`,
`review_pay_screen.dart`, `invoice_screen.dart`, `new_sale_screen.dart` (+`new_sale_catalog_views`),
`day_session_screen.dart`, `auth_cubit.dart`, `receipt_pdf.dart`, `sale.dart`,
`lookup_repository/service`, `local_storage_service.dart` + `keys.dart`, `routes.dart`,
`app_router.dart`, `http_service.dart`, `paginated_list_cubit.dart`, `admin_cubit.dart`,
`stock_check_cubit.dart`, `pubspec.yaml`.

### Test infrastructure (new, and reusable well beyond this feature)

| File | Role |
|---|---|
| `tests/Support/PosWorld.php` | builds the whole world a sale needs (see §8.1) |
| `database/factories/TenantFactory.php` | new |
| `database/factories/UserFactory.php` | **fixed** — see §8.2 |
| `mobileApp/test/support/offline_harness.dart` | sqflite FFI in-memory + mocked prefs + branch context |

**Not from this work** (present in the tree from other sessions — do not claim or delete without
checking): `SaleDraftCompletionTest.php`, `ZzTempReviewProbeTest.php` (looks like a leftover probe),
`app/Actions/V1/Sale/UpdateAction.php`, `SaleController.php`, `UpdateRequest.php`,
`app/Livewire/*`, `docs/code-audit.html`, several `mobileApp` chart/report files.

---

## 7. Every bug found and fixed — the expensive knowledge

Found by an adversarial review (4 lenses, each finding independently refuted before being accepted),
and then by the tests themselves.

### Critical

**7.1 — The snapshot could never be written.** Page size was 200; `GetProductsRequest` caps
`per_page` at **100** and 422s above it — and `refreshCatalog` swallowed the error. Offline mode
would have silently never worked, with nothing on screen to say so.
→ Page size 100; an `ApiException` during refresh is now recorded on state.

### High

**7.2 — Double charge.** A fresh key per press of Charge, combined with the server-side guard bypass,
meant re-pressing Charge after a visible error committed the sale twice — a *regression* against the
guard it replaced. → Key held per ticket, retired in `emit` on real change. `OutboxService.enqueue`
returns the existing row for a repeated key.

**7.3 — Queue drained under the wrong cashier.** The row's cashier/branch were stored and never read.
→ See §4.4.

**7.4 — `retry()` bypassed the drain mutex** and the UI offered Retry on rows mid-POST → concurrent
double-post, and the two writes clobbered each other. → Same mutex; `syncing` rows refused; button
gated.

### Medium

**7.5 — Dead exception handler.** `catch (UniqueConstraintViolationException)` never fired:
`App\Actions\Sale\CreateAction` catches `\Throwable` and returns `['success' => false]`, so the typed
exception never escapes. → Resolve by uuid in the generic `\Throwable` catch instead.

**7.6 — Branch switch mid-snapshot** wrote the new branch's products under the old branch's key
(HttpService stamps `branch_id` at send time, not capture time). → Re-check before `replace()`.

**7.7 — `_syncStarted` never reset on sign-out**, so after re-login the wiped snapshot was never
re-taken. → Latch released on `signedOut`.

**7.8 — 408/425/429 and proxy 404s classified terminal.** → Narrowed to `{400, 403, 404, 409, 422}`.
Note `SaleController::store()` answers **422 for every domain refusal**, which is why 422 stays
terminal.

### Found by writing the tests

**7.9 — `reduceStock` was invisible.** It updated the indexed column, but reads rebuilt `Product`
from the stored JSON — so the local stock decrement had no effect and the low-stock warning could
never fire. → `_toProduct` overlays the live column onto the payload.

**7.10 — A missing DI registration lost the sale.** `OfflineFirstSaleService` resolved
`AuthCubit`/`BranchCubit` unconditionally; a missing registration aborted the *capture* and surfaced
as "could not save the sale". → Anything that only supplies display attribution resolves leniently.
`BranchCubit` stays required for the sync engine (it genuinely can't work without it) and is
registered in the test harness instead.

**7.11 — `PendingSalesBadge` hard-threw in `build`** when the locator had no `OfflineSyncCubit`,
taking New Sale down with it. → Renders nothing when unregistered.

**7.12 — A whole round of server-side offline handling, reverted.** Written, tested, then removed
once the "offline belongs to the app" constraint was set. Do not rebuild any of it without that
decision being revisited:

| Removed | What it did | Why it went |
|---|---|---|
| `resolveDaySession()` in both V1 create actions; `Sale::creating` / `SaleReturn::creating` accepting a **closed** session for a backdated capture; the `client_created_at` datetime cast | Filed a queued sale/refund against the day session its own till clock fell inside, so takings could not be swept onto the day the queue happened to drain on | Date logic on the server. Offline lasts hours, so it lands on the right day anyway |
| `App\Support\Sale\OfflineCapture`, its hook in `OutOfStockSales::prevented()`, `offlineCaptured` on both StoreRequests | Let a captured sale commit with negative stock, on the grounds that the goods had already left the shop | Server-side special-casing. **This leaves a real open risk — §10** |
| `offline_till_states` table + model + action + controller + route; `OfflineTillRepository` / `OfflineTillService`; `branchTills` on the sync state; the multi-till day-close dialog | Each device reported its queue so day close could see another till's held sales | A reporting feature on the server; not wanted |
| `items.*.tax` on `StoreRequest` / `UpdateRequest` and both create actions | Committed the tax rate the customer was charged instead of the product's current one | Tax rates are fixed here, so nothing can drift |

**7.13 — The entire offline sale-return stack, reverted.** Built in full, tested (29 mobile + 13
backend), then removed when offline was scoped to the POS New Sale screen. Recorded so it is not
rebuilt by accident, and so the reasoning survives if it is ever taken on again:

| Removed | What it did |
|---|---|
| `outbox_returns` (sqflite v3) + `PendingReturn` + `ReturnOutboxRepository`/`Service` | A second durable queue with the sale outbox's exact lifecycle and `RET-<tag>-<seq>` references on their own counter |
| `OfflineFirstSaleReturnService` | Queue on unreachable, surface anything the server answered |
| `snapshot_returnables` + `returnable_cache.dart` | Cached returnable-line data so a refund had something to be raised against, filled from every sale the till committed |
| Restock + returnable-allowance spend on capture | Stopped one device refunding the same line twice before the first refund synced |
| `_drainReturns` / `retryReturn` / `discardReturn`; `returnRows` and `unbankedRefunds` on the state | Sales drained first, then the refunds raised against them |
| `sale_returns.client_uuid` + `client_created_at` + replay lookup + guard bypass + `clientUserId` | Server-side replay safety for refunds |

Both schemas were returned to their previous state — sqflite back to **v2**, the `sale_returns`
columns dropped — so nothing of it remains.

**The one design note worth keeping.** A refund is money **out** of the drawer, so if offline returns
are ever taken on again they need the same replay safety a sale has. The line-level
returnable-quantity cap does **not** provide it: that cap compares against refunds already committed,
so it catches a genuine over-return but cannot tell a retry of a refund it has not finished recording
apart from a new one.

### Low (all fixed)

Inverted `discard()` guard (blocked the safe status, allowed the live one) · a throw after the
durable write reported a captured sale as failed · an empty first page wiped a working catalog ·
`servingCached` never cleared once the network returned · `refreshCatalog`'s re-entrancy flag read
across an await · a throw while reading the outbox left `status: waiting` forever, permanently
disabling Sync · a restored connection didn't clear a backoff earned while offline · replay lookup
ignored soft-deleted sales although the unique index counts them.

---

## 8. Repo traps (hit once already — don't repeat)

### 8.1 Writing a Feature test that POSTs to `/api/v1/sale`

Use `Tests\Support\PosWorld`. Two traps it encodes:

- **`IdentifyTenant` resolves the tenant from the HOST**, overwriting whatever the test set on
  `TenantService`. The default test host comes from `APP_URL` (`project_manager.test`), which parses
  as a subdomain and resolves a leftover "Default Tenant" — *not* the one the test just built.
  **`withHeader('Host', …)` does NOT work** (Laravel builds the request from `APP_URL`).
  → Post to an absolute URL: `$this->postJson($world->url('/api/v1/sale'), …)`.
- **`inventories.barcode` is a GENERATED column** (`barcode_prefix` ‖ `barcode_number`). Writing to
  it directly is MySQL error 3105. Set the two source columns instead.

A sale also needs: tenant, branch, user with `default_branch_id`, an "Account Receivable"
`AccountCategory`, the system accounts (`sale`, `cost_of_goods_sold`, `inventory`, `tax_amount`,
`discount`, `cash`, …) inserted with `slug` + `is_locked` **via the query builder** (both are
deliberately not fillable), a `Configuration` row `payment_methods` = JSON array of account ids,
unit/department/category, product, and inventory at the branch.

### 8.2 `User::factory()` was broken repo-wide

`users.tenant_id` is NOT NULL with a FK to `tenants`, and `BelongsToTenant` can only auto-fill it
when a tenant is already resolved — never true in a bare test. Every call died on the constraint.
Fixed by adding `'tenant_id' => Tenant::factory()`. **That one fix repaired 14 pre-existing
failures.**

### 8.3 Server `per_page` ceilings — all `max:100`

`GetProductsRequest`, `Sale/IndexRequest`, `SaleReturn/IndexRequest`, `Report/GetRequest`,
`Customer/IndexRequest`, `Employee/IndexRequest`. Exceeding one 422s. This caused §7.1.

### 8.4 Mobile test harness

`setUpOfflineHarness()` gives sqflite FFI in-memory + mocked prefs + a clean locator.
`registerBranchContext()` adds `HttpService` + a fake `LookupRepository` + `BranchCubit`, and
**returns the branch id that actually became active** — which is whichever branch the fake offered,
*not* the one asked for. Seed snapshots against the returned id or you will write to one branch and
read from another.

### 8.5 `flutter analyze lib` does not cover `test/`

Contract changes silently break the test fakes. Always run **`flutter analyze lib test`**.

---

## 9. Verification — commands and expected results

```bash
# Mobile
cd mobileApp
flutter analyze lib test          # expect: No issues found
flutter test test/                # expect: 322 pass, 1 skipped

# Backend
cd ..
./vendor/bin/pint --test app/ database/ routes/ tests/   # expect: pass
./vendor/bin/pest tests/Feature/Api/V1/SaleIdempotencyTest.php \
                  tests/Feature/Api/V1/SaleCashierAttributionTest.php   # expect: 19 passed
./vendor/bin/pest --testsuite=Feature              # expect: 16 failed (the pre-existing set)
```

### The 16 pre-existing failures (NOT regressions)

- **11 × `StockAnalysisReportTest`** — its own helper writes to the generated column
  `inventories.barcode`. One-line fix in that file (`barcode_prefix` / `barcode_number`); left alone
  as it belongs to another feature.
- **5 × Laravel auth tests** — one asserts against a developer's hardcoded credentials
  (`rahees@astra.com`); the others expect notifications/routes that don't fire on a fresh database.

### Test inventory (135 mobile + 19 backend)

| File | Tests | Covers |
|---|---|---|
| `test/offline_sync_cubit_test.dart` | 19 | drain order, mutexes, terminal vs retryable, backoff, retry/discard guards, purge-on-sync |
| `test/offline_pending_edit_test.dart` | 14 | correction preserves identity, refuses `syncing`/`synced`, stock reconciliation, key pinning, offline drafts |
| `test/catalog_snapshot_service_test.dart` | 10 | atomic replace, branch isolation, search, paging, `reduceStock` clamp |
| `test/offline_sale_service_test.dart` | 10 | queue-vs-rethrow for every Dio type and every server answer |
| `test/sales_list_offline_test.dart` | 20 | held rows on page 1, offline-only fallback, filter honesty, search (server + local), Edit-from-list key handling |
| `test/offline_login_test.dart` | 15 | device roster, offline sign-in boundaries, refusal-vs-unreachable, tenant switch clears |
| `test/outbox_service_test.dart` | 9 | durability, duplicate enqueue, provisional sequence, purge, stuck recovery |
| `test/cart_charge_key_test.dart` | 9 | key stable across presses, retired by any ticket change |
| `test/connectivity_cubit_test.dart` | 9 | the online/offline asymmetry, `since`, error classification |
| `test/lookup_snapshot_test.dart` | 9 | lookup storage/search, empty-fetch guard, decorator fallback |
| `test/catalog_freshness_test.dart` | 5 | staleness thresholds and the single age wording |
| `test/offline_auto_sync_test.dart` | 6 | drains on the reachable-again transition, ignores earned backoff, does not re-drain per request |
| `SaleIdempotencyTest.php` | 12 | replay = one sale, guard bypass, tenant isolation, stock once, voided refusal |
| `SaleCashierAttributionTest.php` | 7 | cashier claim honoured/ignored, branch from cashier, cross-cashier drain |

---

## 10. Not verified / not done

- **Nothing has run against a live server or a real device.** No end-to-end replay against a real
  backend; no manual airplane-mode pass. **Do both before shipping.**
- No per-screen offline classification was completed. What *was* done: connection failures now say
  "could not reach the server" instead of the screen's generic copy, via `networkErrorMessage()` in
  `PaginatedListCubit`, `AdminCubit` and `StockCheckCubit`, plus the Sales list falling back to the
  outbox. A full screen-by-screen audit is still open.

### The one open money risk — an oversold offline sale

A queued sale whose stock has run out by sync time is refused by the server's existing out-of-stock
guard: 422 → the row goes `failed` → and `failed` is the only status that offers **Discard**, the
one action that can lose recorded takings. The goods left the shop and the money is in the drawer, so
the refusal cannot un-sell it; it only strands the record next to a delete button.

The `OfflineCapture` scope that fixed this was reverted with the rest of the server-side offline
handling (§7.12). Reinstating it is a one-file change plus the hook in `OutOfStockSales::prevented()`.
Until then the mitigation is procedural, and whoever resolves a failed row must correct the stock
rather than discard the sale.

### Deliberately not built

- **Offline sale returns.** Offline is the POS New Sale screen only. A full implementation was built
  and rolled back — §7.13.
- **Cross-day offline.** Offline lasts minutes or hours, so a queued sale lands on the day it was
  taken. Sales are dated by the open day session exactly as they always were.
- **Multi-till awareness.** A till cannot see another till's queue, and there is no server-side
  register of what each device holds. The day-close guard covers *this* device; that is the limit.
- **Client-supplied line tax.** Rates are fixed in this business, so a queued total cannot drift.
- **Recent sales cached wholesale.** The Sales list serves committed sales from the server and held
  ones from the outbox; only returnable-line data is cached, and only for sales the device has seen.

### Business decisions — answered 2026-08-09

1. A **provisional receipt number** (`OFF-<tag>-<seq>`) is acceptable to hand a customer. This
   unblocked shipping offline capture.
2. Stale catalog: **warn, never block.** Thresholds live in `CatalogFreshness`.
3. Oversell: answered "commit with negative stock", built, then reverted with the server-side work —
   see the open risk above.

---

## 11. Quick orientation for a new session

```
Charge pressed                          (or Save Draft → beginCharge(status: 'draft'))
  └─ CartCubit.beginCharge()            → (clientUuid, payload, offlineSale)
      └─ SaleOpsCubit.createSale(payload, offlineSale:)
          └─ OfflineFirstSaleService
              ├─ server reachable → SaleService → POST /api/v1/sale
              └─ unreachable      → OutboxService.enqueue()
                                  → reduceStock()   (skipped for a draft)
                                  → returns a `pending` Sale with OFF-XXX-0001

Correcting a held sale                  (Sales list → invoice → Edit)
  └─ CartCubit.seedFromPendingSale(row) → key PINNED to row.clientUuid
      └─ OfflineSyncCubit.editPending() → replaces the row in place, then drains

Later
  OfflineSyncCubit.drain()              (60s timer | connectivity | app resume | manual)
      └─ oldest first, serial, one mutex
          └─ SaleService.createSale({...payload, clientUserId})
              └─ server: replay by client_uuid? → return existing
                         else create, guard bypassed
          └─ acknowledged → row DELETED from the device, ref collected into
                            state.lastSyncedRefs
```
