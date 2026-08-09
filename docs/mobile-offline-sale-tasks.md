# Offline New Sale — task list

Companion to [mobile-offline-sale-plan.md](mobile-offline-sale-plan.md). That file is the design;
this one tracks the work.

**Started:** 2026-08-09 · **Last updated:** 2026-08-09

**Continuing this work in a new conversation?** Start with
[mobile-offline-sale-knowledge-base.md](mobile-offline-sale-knowledge-base.md) — decisions, invariants,
repo traps and what is/isn't verified.

**Where it stands:** implementation, review, fixes and tests are done, plus a second round
covering server sync, employee attribution, offline behaviour per screen, and first-run
provisioning — and a **third round (below, §H)** that closed every deferred item.

`flutter analyze lib test` clean · **300 mobile tests pass** · **19 backend offline tests pass** ·
PHP Feature suite: **16 failed**, unchanged — the same pre-existing failures as the baseline, in
files this work never touched. The *passing* count drifts upward as other sessions add tests (93 at
last check), so the failure count is the number to compare.

**The two governing constraints, set 2026-08-09:**

1. Offline is a matter of **minutes or hours, never days**.
2. Offline is the **POS New Sale screen only**, and it lives in the **app**. Laravel is unchanged
   apart from the two things only a server can do — recognise a replayed key, and attribute a sale
   to the cashier who took it. Dates, connectivity and the queue are the app's business.

§H records what those ruled out, including a full offline-returns implementation that was built,
tested and then rolled back.

---

## A. Implementation — done

- [x] **A1** Backend: migration adding `sales.client_uuid` + `client_created_at`, unique on `(tenant_id, client_uuid)`
- [x] **A2** Backend: `StoreRequest` accepts `clientUuid` / `clientCreatedAt`
- [x] **A3** Backend: `V1\Sale\CreateAction` returns the existing sale on replay instead of creating a second
- [x] **A4** Backend: `guardAgainstDuplicate()` bypassed when a `clientUuid` is present
- [x] **A5** Backend: a lost unique-index race resolves to the winning row
- [x] **A6** Backend: `SaleResource` emits `client_uuid`
- [x] **A7** Mobile: sqflite `OfflineDb` (catalog snapshot + outbox), schema v1
- [x] **A8** Mobile: `CatalogSnapshotRepository` / `CatalogSnapshotService` with indexed search
- [x] **A9** Mobile: `CatalogCubit` falls back to the snapshot; `servingCached` + `cachedAt` on state
- [x] **A10** Mobile: barcode scanning falls back to the snapshot
- [x] **A11** Mobile: `PendingSale` model + `OutboxRepository` / `OutboxService`
- [x] **A12** Mobile: `OfflineFirstSaleService` decorator; only an unreachable server queues
- [x] **A13** Mobile: `CartCubit.beginCharge()` — key + payload + receipt snapshot in one call
- [x] **A14** Mobile: provisional reference (`OFF-<tag>-<seq>`) + boxed PROVISIONAL banner on the receipt
- [x] **A15** Mobile: `OfflineSyncCubit` — serial drain, backoff, connectivity/timer/resume triggers
- [x] **A16** Mobile: catalog snapshot pull (login, branch change, stale > 6h)
- [x] **A17** Mobile: `PendingSalesScreen` + `PendingSalesBadge` (retry / discard with confirmation)
- [x] **A18** Mobile: invoice screen hides Edit / Return / Delete for a queued sale
- [x] **A19** Mobile: day-close guard while the outbox is non-empty
- [x] **A20** Mobile: offline low-stock warning at add-to-cart and on scan
- [x] **A21** Mobile: sign-out clears the catalog but **never** the outbox
- [x] **A22** Mobile: `purgeSynced` (7 days) + `resetStuckSyncing` at boot
- [x] **A23** Mobile: **app-wide offline banner** — a strip above every screen saying the till is
      running offline and that sales are being held on the device, with the pending count

### A23 — how "offline" is decided

`ConnectivityCubit` (`shared/logic/connectivity_cubit/`) combines two sources, weighted
deliberately unevenly:

| Signal | Meaning |
|---|---|
| A request got **any** response (even a 500) | **Online** — the server was reached |
| A request got **no** answer (`isServerUnreachable`) | **Offline** |
| OS reports **no network interface** | **Offline**, immediately |
| OS reports an interface is present | **Nothing** — a till on shop wifi with a dead uplink reports "connected" |

So the banner appears the instant the device knows it is disconnected, and clears only once
something has genuinely reached the server. Until the first signal the status is `unknown` and no
banner shows, so a cold start never cries wolf.

Every request reports through a single Dio interceptor in `HttpService`, so no call site has to
remember to. The "server unreachable" test now lives in one place
(`shared/utils/router/http_utils/reachability.dart`) and is shared with `OfflineFirstSaleService`,
which previously carried its own copy.

The strip distinguishes **"No network"** from **"can't reach the server"** — different problems with
different fixes — and consumes the status-bar inset itself, with the top padding removed from the
page beneath so screens don't inset twice.

## B. Review and fixes — done

- [x] **B1** Adversarial review across four lenses (data-loss, concurrency, sync semantics, backend + conventions), each finding then refuted by an independent verifier
- [x] **B2** Applied the confirmed fixes — see the table below
- [x] **B3** `flutter analyze lib test` and `pint --test` clean afterwards

### What the review found and what changed

| Sev | Defect | Fix |
|---|---|---|
| **Critical** | Snapshot page size was 200; `GetProductsRequest` caps `per_page` at **100** and 422s above it — and `refreshCatalog` swallowed the error, so **offline mode would silently never work** | Page size 100; an `ApiException` during refresh is now recorded on state instead of swallowed |
| **High** | A fresh key was minted per press of Charge while the server's duplicate guard is skipped whenever a key is present — so re-pressing Charge after a visible error **committed the sale twice**. A net regression against the old guard | `CartCubit` holds one key per *ticket*; `emit` retires it only when the ticket actually changes. `OutboxService.enqueue` returns the existing row for a repeated key |
| **High** | The drain posted every queued sale under **whoever is signed in now** — the row's cashier and branch were stored but never read. On a shared till that misfiles takings | Superseded by G1 below: the payload now carries the originating cashier and the server honours it, so any session can drain anyone's queue without misfiling it |
| **High** | `retry()` bypassed the drain mutex and the UI offered Retry on rows mid-POST → concurrent double-post, and the two writes clobbered each other | `retry()` takes the same mutex and refuses `syncing` rows; the button is gated too |
| **Medium** | `catch (UniqueConstraintViolationException)` was **dead code** — the inner web action catches `\Throwable` and returns an array, so the typed exception never escapes | Resolve by uuid in the generic `\Throwable` catch instead of type-matching |
| **Medium** | A branch switch mid-snapshot wrote the **new** branch's products under the **old** branch's key | Re-check the active branch before `replace()` and abandon a superseded run |
| **Medium** | `_syncStarted` never reset on sign-out, so after re-login the wiped snapshot was never re-taken | Latch released on `signedOut` |
| **Medium** | 408/425/429 and proxy 404s were classified terminal — and `failed` is the only state offering the money-losing Discard button | Terminal narrowed to `{400, 403, 404, 409, 422}` |
| **Low** | `discard()`'s guard was inverted: it blocked `pending` (safe) and allowed `syncing` (a live request) | Only `failed` / `synced` can be discarded |
| **Low** | A throw *after* the durable write reported a captured sale as failed, leaving the cart loaded for a second charge | Everything past the write is wrapped |
| **Low** | An empty first page replaced a working catalog with nothing and stamped it fresh for 6 hours | Skipped when the previous snapshot had rows |
| **Low** | `servingCached` never cleared once the network returned — grid kept paging the snapshot and claiming "Offline" | `clearCached` flag on the `ApiException` path |
| **Low** | `refreshCatalog`'s re-entrancy flag was read across an await, so two full downloads could run at once | Synchronous `_refreshingCatalog` mutex |
| **Low** | A throw while reading the outbox left `status: waiting` forever, permanently disabling Sync | `drain()` body wrapped, terminal status emitted |
| **Low** | A restored connection did not clear a backoff earned while offline | `drain(ignoreBackoff: true)` from connectivity and resume |
| **Low** | Replay lookup ignored soft-deleted sales although the unique index counts them → constraint failure with no explanation | `withTrashed()`, with a clear refusal for a voided sale |

### Found by the tests themselves

| Defect | Fix |
|---|---|
| `reduceStock` wrote the indexed column but reads rebuilt `Product` from the **stored JSON**, so the local decrement was invisible and the low-stock warning could never fire | `_toProduct` overlays the live `total_stock` column onto the payload |
| `PendingSalesBadge` hard-threw in `build` when the locator had no `OfflineSyncCubit`, taking New Sale down | Renders nothing when unregistered |
| `CartCubit.beginCharge` and `OfflineFirstSaleService` resolved `AuthCubit`/`BranchCubit` unconditionally. In the decorator that meant a missing registration **lost the sale** — the capture aborted and the failure surfaced as "could not save" | Both resolve leniently; these only supply display attribution |

## C. Test coverage — done

### C0. Unblock the harness

- [x] **C1** Baseline recorded: **30 failed / 44 passed** before any of this work
- [x] **C2** `UserFactory` now establishes a tenant (`users.tenant_id` is NOT NULL with a FK, and the
      `BelongsToTenant` trait cannot auto-fill it with no resolved tenant). **This alone fixed 14
      pre-existing failures.** New `TenantFactory` supports it
- [x] **C3** `tests/Support/PosWorld.php` builds the world a sale needs: tenant, branch, cashier,
      chart of system accounts, `payment_methods` configuration, unit/department/category, product,
      inventory. Also documents two traps — `IdentifyTenant` resolves the tenant from the **host**
      (so tests post to an absolute URL on the tenant's own subdomain; `withHeader('Host', …)` does
      **not** work), and `inventories.barcode` is a generated column

### C4. Backend — `tests/Feature/Api/V1/SaleIdempotencyTest.php` (12 tests, all passing)

- [x] **C4** Same key twice → **one** sale, both requests successful, same id
- [x] **C5** Two byte-identical sales with different keys → **two** sales (the guard bypass)
- [x] **C6** No key → the 2-minute duplicate guard still refuses a repeat
- [x] **C7** `SaleResource` echoes `client_uuid`
- [x] **C8** `client_created_at` is stored and does **not** become the accounting `date`
- [x] **C9** A replay returns the fully-loaded resource, identical to the original
- [x] **C10** Tenant isolation: tenant B's key never resolves tenant A's sale
- [x] Stock moves **exactly once** across a replay
- [x] A malformed key is rejected (422)
- [x] A voided sale refuses resurrection with an explanation
- [x] Two presses with different keys are *not* protected — documents why the client holds the key

### C11. Mobile — 217 tests passing (was 179)

- [x] **C11** `test/outbox_service_test.dart` (9) — durability, duplicate enqueue, provisional
      sequence, `unsynced` ordering, `resetStuckSyncing`, `purgeSynced` keeping owed rows
- [x] **C12** `test/catalog_snapshot_service_test.dart` (11) — atomic replace, branch isolation,
      search across name/code/barcode, literal `%`, type/category filters, paging, barcode lookup,
      `reduceStock` clamping
- [x] **C13** `test/offline_sale_service_test.dart` (10) — queues on every unreachable Dio type,
      **rethrows** on `ApiException`/5xx/422, never queues without a snapshot or a key
- [x] **C14** Covered inside the outbox tests (JSON round-trip, `soldQuantities`, `displayRef`)
- [x] **C15** `test/cart_charge_key_test.dart` (9) — key stable across presses, retired by any
      ticket change, present in both payload and snapshot, totals match
- [x] `test/support/offline_harness.dart` — sqflite FFI in-memory + mocked prefs (new dev dep
      `sqflite_common_ffi`)
- [x] `test/connectivity_cubit_test.dart` (9) — the online/offline asymmetry, `since` not resetting
      on repeat failures, and exactly which errors count as unreachable

## D. Verification — done

- [x] **D1** `flutter analyze lib test` — no issues
- [x] **D2** `pint --test app/ database/ tests/` — pass; `php -l` clean
- [x] **D3** PHP Feature suite **16 failed / 84 passed** vs a **30 / 44** baseline. The 16 are a
      strict subset of the original 30, all in files this work never touched
- [ ] **D4** End-to-end replay against a **running** server (post the same key twice) — not done
- [ ] **D5** Manual: airplane mode → browse, scan, charge, print → restore network → syncs once — not done

### Still-failing tests, all pre-existing and unrelated

- **11 × `StockAnalysisReportTest`** — its own helper writes to `inventories.barcode`, a generated
  column (MySQL error 3105). One-line fix in that test file: set `barcode_prefix` /
  `barcode_number` instead. Left alone as it belongs to another feature — **say the word and I'll fix it**
- **5 × Laravel auth tests** — one asserts against a developer's own hardcoded credentials
  (`rahees@astra.com`), the others expect notifications/routes that don't fire on a fresh database

## G. Second round — sync, attribution, offline behaviour, provisioning

### G1. Employee sales are never stranded and never misfiled

The first fix for the drain-attribution bug made the drain skip rows belonging to another cashier.
That stopped the misfiling but created a worse problem: on a shared till an employee's takings could
not sync until that same employee signed back in, and the day-close guard blocked on them meanwhile.

- [x] **G1** The queued payload now carries `clientUserId` — the cashier who served the customer.
      `V1\Sale\CreateAction` honours it only after checking the named user is **active and on this
      tenant** (the tenant scope on the lookup is what stops one business claiming another's staff);
      anything unrecognised falls back to the poster rather than costing the sale.
      The drain's ownership gate is gone, so any signed-in user can drain anyone's queue.
- [x] **G2** **The branch is never sent.** It follows the resolved cashier's own assignment, which
      removes any way to post a sale into a branch you have no business in. (The outbox still records
      `branchId` locally — that scopes the cached stock, and never leaves the device.)
- [x] **G3** The pending-sales screen labels another cashier's row rather than blocking it.

### G4. First-run provisioning

Only products and categories were snapshotted. Everything else arrived lazily from whichever screen
happened to open first — so a till that went straight offline after sign-in had no payment methods,
no staff list, no customers, and factory-default sale settings.

- [x] **G4** `OfflineDb` schema **v2** adds `snapshot_lookups` (one table, `kind` discriminator) for
      payment methods, staff and customers. The upgrade adds only that table — the catalog and, above
      all, the **outbox** are untouched, because a device upgrading mid-shift may be holding sales.
- [x] **G5** `OfflineFirstLookupService` decorates `LookupRepository`, so the Custom-payment sheet,
      the stylist sheet and the client lookup all keep working offline **with no screen changes**.
      Products/categories are deliberately NOT decorated — `CatalogCubit` does its own fallback
      because it has to *show* that the grid is a stored copy.
- [x] **G6** Provisioning pulls, in order: products → categories → payment methods → staff →
      customers → sale settings + currencies. Each step is independent, so losing the staff list does
      not also cost the payment methods.
- [x] **G7** Sale settings/print config caching moved out of `CartCubit` into
      `pullAndCacheSaleSettings()` so provisioning and the New Sale screen share one implementation.
- [x] **G8** A **first-run progress strip** ("Preparing offline data — Staff (4 of 6)") and, if
      anything failed, a persistent **"Offline data incomplete: …"** strip. Only the first run shows
      progress; a routine refresh happens behind a catalog that already works.

**Recent sales are deliberately NOT cached.** Offline reprint of a queued sale is already served by
the outbox, and offline returns are out of scope (E1) — so caching sale history would earn nothing
today while adding a large, constantly-stale table. Revisit only if E1 is taken on.

### G9. Offline behaviour per screen

- [x] **G9** A connection failure used to surface as the screen's generic copy ("Could not load
      sales."), which reads as a server problem the user can do nothing about.
      `networkErrorMessage()` now distinguishes the two, applied at `PaginatedListCubit` (Sales,
      Sales Returns, the return invoice picker), `AdminCubit` (dashboard, overview, reports) and
      `StockCheckCubit`.

### G10. Sync-engine tests — the biggest untested gap

- [x] **G10** `test/offline_sync_cubit_test.dart` (18): drain order (oldest first), the originating
      cashier on the payload and the branch's absence from it, stop-at-first-unreachable, the drain
      mutex, invoice number recorded back, terminal vs retryable across 401/408/429/500/503, backoff
      and `ignoreBackoff`, retry refusing an in-flight row, discard refusing a still-owed row.
- [x] **G11** `test/lookup_snapshot_test.dart` (9): storage and search for all three kinds, order
      preservation, branch isolation, the "empty fetch never wipes a working list" guard, and the
      decorator's fall-back-vs-rethrow rule.
- [x] **G12** `tests/Feature/Api/V1/SaleCashierAttributionTest.php` (7): poster by default, the
      claimed cashier honoured, claims from another tenant / inactive / non-existent all ignored, the
      branch taken from the cashier and never the request, and one cashier draining another's queue.

### Found while writing these tests

| Defect | Fix |
|---|---|
| `OfflineSyncCubit`, `CartCubit.beginCharge` and `OfflineFirstSaleService` resolved app-wide cubits unconditionally. In the decorator a missing registration **lost the sale** — capture aborted and surfaced as "could not save" | The two that only supply display attribution resolve leniently; `BranchCubit` stays required (the sync engine genuinely cannot work without it) and is registered in the test harness instead |

## E. Previously deferred — now resolved

- [~] **E1** Offline **sale returns** — **dropped by decision.** Built in full (own outbox, a
      cached returnable allowance, server-side replay safety, 29 tests) and then rolled back: offline
      is scoped to the POS New Sale screen. A refund now needs the network, as it always did.
      See §H1 for what was removed, so it is not rebuilt by accident.
- [x] **E2** Offline **drafts** — done. See §H2.
- [x] **E3** **Editing a queued sale** — done. See §H3.
- [~] **E4** **Cross-day offline** — **dropped by decision.** Offline lasts minutes or hours, so a
      queued sale lands on the day it was taken anyway. A build that filed sales against the day
      session their own till clock fell in was written and then reverted: it put date logic on the
      server, which is where it does not belong, and it solved a problem that does not occur.
      The date is the app's concern; the server dates a sale exactly as it always did.
- [~] **E5** **Multi-till awareness** — **dropped by decision.** A build that had each device report
      its queue to an `offline_till_states` table, so day close could see another till's held sales,
      was written and then reverted: it is a reporting feature on the server, and none was wanted.
      The day-close guard sees this device's queue, and that is the documented limit.
- [~] **E6** **Line tax** — **dropped by decision.** Tax rates are fixed in this business, so a
      queued sale's committed total cannot drift from the cash collected. The server keeps deriving
      line tax from the product row, on both the create and the update path.

## F. Business decisions — answered 2026-08-09

- [x] **F1** A **provisional receipt number** is acceptable. `OFF-<tag>-<seq>` stands until the
      server assigns the real one. No code change; this unblocked shipping offline capture.
- [x] **F2** **Warn, never block.** Selling from a stale catalog is always allowed — refusing costs
      certain revenue to avoid a possible price error. The warning escalates instead. See §H4.
- [~] **F3** Oversell was answered "commit, allow negative stock", and the build that did it was then
      **reverted** with the rest of the server-side offline handling. A queued sale that oversells is
      refused by the server's existing out-of-stock guard, arrives as a 422, and shows on the
      pending-sales screen as needing attention. **This is the one open money risk** — see §I.

## H. Third round — the deferred items, and the scope correction

### H1. Offline sale returns — built, then rolled back

Implemented in full and reverted the same day once offline was scoped to the New Sale screen.
Recorded here because it worked, and because rebuilding it means re-deciding the same things:

| Removed | What it was |
|---|---|
| `outbox_returns` (sqflite v3) + `PendingReturn` + `ReturnOutboxRepository`/`Service` | A second durable queue with the sale outbox's exact lifecycle |
| `OfflineFirstSaleReturnService` | Decorator: queue on unreachable, surface anything the server answered |
| `snapshot_returnables` + `returnable_cache.dart` | Cached returnable-line data so a refund had something to be raised against, filled from every sale the till committed |
| Restock + allowance spend on capture | Kept the cached shelf and the per-line returnable figure honest before sync |
| `_drainReturns` / `retryReturn` / `discardReturn`, `returnRows` on the state | Sales drained first, then the refunds raised against them |
| `sale_returns.client_uuid` + `client_created_at`, replay lookup, guard bypass, `clientUserId` | Server-side replay safety for refunds |
| `test/offline_returns_test.dart` (29) · `SaleReturnIdempotencyTest.php` (13) | Full coverage of the above |

The sqflite schema went back to **v2** and the `sale_returns` columns were dropped, so nothing of it
remains in either schema. The one design note worth keeping: a refund is money **out** of the drawer,
so if it is ever taken on again it needs the same replay safety a sale has — the returnable-quantity
cap does **not** cover a retry, only a genuine over-return.

### H2. Offline drafts

- [x] **H7** `beginCharge(status: 'draft')` captures a draft through the same key-and-snapshot
      machinery. A draft still must not replay into two parked rows.
- [x] **H8** A draft moves **no stock** and does **not** block the day close — it is not unbanked
      takings, and blocking a close over a quote left on a screen teaches people to force past the
      guard that protects the money. It is still counted as held, so the cashier can see it.

### H3. Editing a queued sale

- [x] **H9** `OfflineSyncCubit.editPending()` replaces the outbox row's payload and receipt snapshot
      while **preserving its identity** — same `clientUuid` (a new one would let the server commit
      both versions), same provisional reference (the customer already holds it), same capture time
      (the queue order is the order customers were served). Attempts and last error reset.
- [x] **H10** Refused for a `syncing` row (a request may be committing the version being replaced)
      and for a `synced` one (that is an ordinary server-side edit).
- [x] **H11** `CartCubit.seedFromPendingSale()` + `CartState.editingPendingUuid`. `emit` **pins** the
      key while a correction is loaded instead of retiring it, which is the exact inverse of the
      normal double-charge guard and the whole reason a correction cannot become a second sale.
- [x] **H12** The old quantities are handed back to the cached stock before the corrected ones are
      taken, or every edit walks the figure further down.

### H4. Held sales live in the Sales list, and are edited there

- [x] **H13** Sales this device is holding appear at the **top of the first page** of the Sales list,
      and are the **only** rows shown when the server is unreachable. A cashier who has just rung
      something up offline looks for it in Sales; a list that omits it reads as a lost sale. Tapping
      through opens the invoice screen, which offers **Edit** and routes it to the queue.
- [x] **H14** Filters are honoured honestly: the status filter and the date range apply to held rows
      (dated by the till clock that captured them), and a **payment-method filter excludes them** —
      a held sale has no payment-method id yet, so it cannot be claimed to match one.
- [x] **H15** A server that *answered* is always surfaced. Only an unreachable server falls back to
      the held-rows-only list, because a short list otherwise reads as "these are all your sales".
- [x] **H18** **Edit straight from the list row.** A held row carries an `edit` button; a committed
      one does not (it is edited from its invoice, where the permission gate and Return/Delete live).
      Also fixed a real bug this exposed: a held row has no server id, so `_open` returned early and
      **tapping a held sale did nothing at all**. Both phone and tablet paths now render a held row
      from its own snapshot instead of trying to fetch it.

### H5b. Auto-sync triggers

- [x] **H19** The sync engine now also listens to **`ConnectivityCubit` flipping to online**, which
      is the only signal that proves the server was actually reached. The OS "interface appeared"
      event never fires for the case that bites hardest — a till that kept its shop wifi the whole
      time while the uplink was down — so recovery there previously waited on the 60s timer, plus up
      to fifteen minutes of earned backoff on top. Subscribed on the *transition* only, since every
      successful request keeps the status online. Five triggers in total, documented in the KB §4.3.

### H5. Staleness warning (F2)

- [x] **H16** `CatalogFreshness` — one definition of the thresholds (aging > 12h, stale > 24h) and
      one wording of the age (`catalogAgeLabel`), shared by the app-wide strip and the grid chip so
      they can never disagree. Past the thresholds the offline strip escalates its copy and takes
      the warning colour; a stale snapshot is called out **even while online**, because a refresh
      that keeps failing looks like nothing at all. **Selling is never blocked.**

### H6. An acknowledged row leaves the device

- [x] **H17** A synced sale or refund is **deleted immediately**, not kept for seven days. The outbox
      exists to hold what the server does not have; keeping rows afterwards left a second,
      permanently-staling copy of a sale the Sales list now serves properly — and the device is
      online by definition at that moment. `state.lastSyncedRefs` reports the real invoice numbers
      the queued rows became, which is the one chance to tell a cashier holding an `OFF-…` receipt
      what it turned into.

### H6b. The printed receipt number survives the sync (2026-08-10)

The gap H17 created. A sale rung up offline is receipted on the spot under a device-minted
`OFF-7K2-0042`, and H17 deletes the local copy of that reference at the exact moment the sale becomes
real — so the number in the customer's hand referred to nothing, and a receipt from an outage could
never be traced to its sale.

- [x] **H18** `_push` sends the reference as `offlineRef` on the syncing POST — read off the row, not
      the payload, so a correction (which rebuilds the payload) cannot drop it. This is the last
      moment it can be saved anywhere permanent.
- [x] **H19** The server stores it in **`sales.reference_no`**. Chosen over a dedicated `offline_ref`
      column on instruction; an `offline_ref` migration was written and removed. It works out cleanly
      because that column is already fillable, already in `scopeFilter`'s search, already on both V1
      resources, already on the web sale view and already an optional web list column — one write
      made the number visible and searchable everywhere. `BuildDaySessionReportAction` is unaffected
      (its `reference_no` comes from `invoice_no`). Residual risk: the web sale form binds this field,
      so editing a synced offline sale on the web can overwrite the receipt number.
- [x] **H20** `Sale.offlineRef` is `reference_no` **gated on `client_uuid`**, so a reference typed in
      the back office is never printed on a receipt as an offline number. Shown on the invoice hero,
      the tablet detail head, both sales-list rows, and as an `Offline Ref` line on a reprint — the
      reprint case is the one that matters, since the customer's copy shows the provisional number and
      the reprint would otherwise show only the invoice number.
- [x] **H21** Searching the printed number finds the sale in both states: locally by
      `provisionalRef` while held, server-side via `reference_no` once synced.
- [x] **H22** `offline_receipt_reference_test.dart` (9) + `SaleOfflineReferenceTest.php` (7). Also
      hardened `offline_auto_sync_test.dart`: three tests waited a fixed 40 ms for a fire-and-forget
      drain and failed under parallel load for want of a few milliseconds. They now poll the outcome
      (`untilSynced`); the two that assert a *negative* keep the fixed wait, since absence cannot be
      polled for.

### H7. The scope correction — what was reverted

Written, tested, and then removed once the constraint above was set. Recorded so nobody rebuilds it:

| Reverted | Why |
|---|---|
| `Sale::creating` / `SaleReturn::creating` accepting a **closed** day session for a backdated capture, and `resolveDaySession()` in both V1 create actions | Date logic on the server. Offline lasts hours, so the sale lands on the right day regardless |
| `Sale.client_created_at` datetime cast | Only existed for the above |
| `App\Support\Sale\OfflineCapture` + the `OutOfStockSales` hook + `offlineCaptured` on both requests | Server-side offline special-casing. See §I — this leaves the oversell risk open |
| `offline_till_states` table, model, action, controller, route; `OfflineTillRepository` / `OfflineTillService`; `branchTills` on the sync state; the multi-till day-close copy | A reporting feature on the server; not wanted |
| `items.*.tax` on `StoreRequest` / `UpdateRequest` and both create actions | Tax is fixed, so nothing can drift |
| The whole offline **sale-return** stack, mobile and server (§H1) | Offline is the POS New Sale screen only |

**What the server still does for offline, and only this:** recognises a replayed `clientUuid` and
returns the committed row (sales and returns), and honours `clientUserId` so a shared till's takings
are filed under the cashier who served the customer rather than whoever synced them.

## I. Known open risk

**An oversold offline sale is refused at sync.** The server's out-of-stock guard answers 422, the
row goes `failed`, and `failed` is the only status offering **Discard** — the one action that can
lose recorded takings. The goods have already left the shop and the money is in the drawer, so a
refusal cannot un-sell it; it only strands the record.

The fix is the reverted `OfflineCapture` scope (§H7), or the equivalent decision taken somewhere
else. Until then the mitigation is procedural: the pending-sales screen shows the reason, and
whoever resolves it must correct the stock rather than discard the sale.
