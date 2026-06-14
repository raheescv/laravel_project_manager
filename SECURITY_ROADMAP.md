# 🔒 Security Remediation Roadmap

**Project:** project_manager (Laravel 12 + Livewire 3)
**Audit date:** 2026-06-14
**Scope:** Full codebase — 1,261 PHP files, 799 Blade templates, Node WhatsApp sidecar, routes, config, committed secrets.
**Method:** Read-only static review across all major vulnerability classes. No files were modified during the audit.

---

## How to use this document

- Each finding has a stable ID (`C#`, `H#`, `M#`, `L#`), a location (`file:line`), the issue, and the fix.
- Track progress by checking the boxes. Update **Status** as you go.
- Work top-to-bottom through the **Phased Plan** — it is ordered by blast radius and ease of exploitation, not just severity.

**Status legend:** ☐ Not started · ◐ In progress · ☑ Done · ⊘ Won't fix / accepted risk

> ⚠️ **Deployment-dependent:** Several criticals (C4 Node server, C5/C8 webhooks, C6 debug mode) are catastrophic if the host is internet-facing and internal-only otherwise. Confirm your network exposure before triaging.

---

## Progress summary

| Severity | Total | Done | Remaining |
|---|---|---|---|
| 🔴 Critical | 8 | 4 | 4 |
| 🟠 High | 14 | 0 | 14 |
| 🟡 Medium | 13 | 0 | 13 |
| ⚪ Low / Info | 8 | 0 | 8 |
| **Total** | **43** | **4** | **39** |

> **C2, C3, C5 fixed; C7 substantially complete** — 179 Livewire components now guarded (264 guards); 64 documented `// TODO(C7)` deferrals remain (nested quick-add saves, RentOut documents, Trading). See C7 detail.

---

## 📅 Phased plan

### Phase 0 — Stop the bleeding (today)
- [ ] **C6** Set `APP_DEBUG=false`, fix duplicate `APP_ENV`, **rotate all leaked keys**
- [ ] **C1** Fix `exec()` RCE in `SaleHelper`
- [ ] **C4** Authenticate the Node WhatsApp server + bind to localhost
- [x] **C5** ~~Authenticate/remove the `fyers` → `.env` webhook~~ — Done: Fyers integration removed entirely (webhook, controller, route, service, Livewire page, commands deleted)
- [x] **C3** ~~Lock down public API v1 routes~~ — Done: catalog routes moved inside `IdentifyTenant` + `auth:sanctum`
- [ ] **H8 / H14** Rotate committed secrets, untrack `dump.rdb`, `chmod 600 .env*`

### Phase 1 — Authorization pass (this week)
- [x] **C2** ~~Fix User mass-assignment privilege escalation~~ — Done
- [x] **C7** ~~Add per-method authorization to Livewire components~~ — Substantially done: 179 components guarded (264 guards); 64 documented deferrals (nested quick-add saves, RentOut documents, Trading)
- [ ] **H2 / H3 / M2** Close the IDORs (print, customer, stock-check)
- [ ] **H4 / H5** Fix PIN login scoping + default password

### Phase 2 — Injection & XSS
- [ ] **H1 / M1** Whitelist `sort_direction` / `sort_field` (SQLi)
- [ ] **H6 / H7** Escape stored-XSS sinks (footer, category, employee names)

### Phase 3 — Webhooks & credentials
- [ ] **C8 / H13 / M5** Sign FlatTrade + Telegram webhooks, validate OAuth `state`
- [ ] **H9** Remove `asdasd` super-admin seeds
- [ ] **H10 / H11 / H12** Close Scramble docs, fix CORS, fix backup path traversal

### Phase 4 — Hardening
- [ ] All remaining **Medium** items (M3, M4, M6–M13)
- [ ] All **Low/Info** items (L1–L8)

---

## 🔴 CRITICAL

### C1 — Remote Code Execution via shell command injection
- **Status:** ☐
- **Location:** `app/Helpers/SaleHelper.php:78`
- **Issue:** `exec("wkhtmltoimage ... $htmlFilePath $outputImagePath")` where the output path is `public_path("invoices/$title.png")` and `$title = $sale->invoice_no`. The invoice number is client-supplied (`POSController::store` → `$request->all()` → `CreateAction:21` uses the client value; `Sale::rules()` has no charset restriction). Second vector via Excel import (`SaleImport.php:75`). A payload like `a$(touch /tmp/pwn).png` runs arbitrary OS commands as the web/queue user.
- **Impact:** Any user with `sale.create` → full host compromise (RCE).
- **Fix:** Use `Symfony\Component\Process\Process` with an argument array (no shell string), or `escapeshellarg()` each path. Derive the filename from `$sale->id`, not free-text. Validate `invoice_no` to `[A-Za-z0-9/_-]`.

### C2 — Mass-assignment privilege escalation to Super Admin
- **Status:** ☑ Done (2026-06-14)
- **Location:** `app/Livewire/User/Employee/Page.php` & `app/Livewire/User/Page.php` (`save()`) → `app/Actions/User/CreateAction.php` / `UpdateAction.php`
- **Issue:** A public Livewire property is passed verbatim to `User::create($data)` with no permission check; `$fillable` includes `is_admin`, `is_super_admin`, `tenant_id`, `salary`, `max_discount_per_sale`. A crafted request sets `users[is_super_admin]=1` + `selectedRoles[]=Super Admin` then calls `save()`.
- **Impact:** Any authenticated user → full tenant takeover.
- **Fix:** Remove privileged columns from `$fillable` (or use `$guarded`); `Arr::only()` whitelist in the actions; `$this->authorize('employee.create'|'edit')` in `save()`; validate `selectedRoles` against assignable roles.
- **Resolution:** Three layers added:
  1. `CreateAction` & `UpdateAction` now `Arr::except()` the protected fields (`is_admin`, `is_super_admin`, `tenant_id`, `id`, timestamps) before `create()/update()` — one chokepoint covering **both** the Employee and User management forms.
  2. `Employee\Page::save()` and `User\Page::save()` now `abort_unless(auth()->user()?->can('employee.edit'|'employee.create' / 'user.edit'|'user.create'), 403)`.
  3. Role assignment now flows through a new `assignableRoles()` guard that strips the **Super Admin** role unless the actor is a super admin.
  Verified: `is_admin`/`is_super_admin` are not bound in any Blade form, so the chokepoint strip breaks no legitimate flow; permission names verified against `config/permissions.php`; `php -l` + Pint clean.
- **Follow-up (optional, defense-in-depth):** also drop the privilege columns from `User::$fillable` (requires updating `UserSeeder` + the property-migration command to set them via `forceFill`/direct assignment).

### C3 — Unauthenticated, un-tenant-scoped API leaks every tenant's data
- **Status:** ☑ Done (2026-06-14)
- **Location:** `routes/api_v1.php`
- **Issue:** `products`, `products/{id}`, `categories`, `brands`, `sizes`, `colors`, `branches` are registered **outside** both `IdentifyTenant` and `auth:sanctum`. With no tenant resolved, `TenantScope` adds no filter → returns all tenants' catalog, inventory, and branch names/phones/locations to anonymous callers.
- **Impact:** Anonymous cross-tenant data disclosure + enumeration.
- **Fix:** Move these route groups inside the `IdentifyTenant` + `auth:sanctum` group.
- **Resolution:** `routes/api_v1.php` restructured — the entire `v1` group is now wrapped in `IdentifyTenant`, and every catalog endpoint (`products`, `categories`, `brands`, `sizes`, `colors`, `branches`) plus the sale/customer routes sit inside `auth:sanctum`. Only `login` stays public (tenant-scoped + `throttle:10,1`). Verified with `php artisan route:list -v`: `api/v1/products` now lists `IdentifyTenant` + `Authenticate:sanctum`.

### C4 — Unauthenticated WhatsApp Node sidecar → file exfiltration + message sending
- **Status:** ☐
- **Location:** `public/node/server.js:233` (`/send-message`), also `/get-qr`, `/disconnect`
- **Issue:** No authentication. Accepts `{number, message, filePath}` and `fs.access(filePath)` / sends the file. `app.listen(PORT)` binds all interfaces (0.0.0.0). Anyone who can reach the port can send WhatsApp messages from the business account and exfiltrate any readable file (e.g. `../../.env`) via `filePath`. CORS does not stop `curl`/server-to-server.
- **Impact:** Arbitrary file read + account abuse from anyone who can reach the port.
- **Fix:** Require a shared-secret header (compare to env), bind listener to `127.0.0.1`, `realpath`-confine `filePath` to an allowed media directory.

### C5 — Unauthenticated webhook injects arbitrary content into `.env`
- **Status:** ☑ Done — resolved by removal (2026-06-14)
- **Location:** ~~`routes/trading.php:14` (GET `fyers`) → `app/Http/Controllers/TradingController.php:17`~~ (deleted); `writeToEnv` still at `app/Helpers/helper.php:20`
- **Issue:** `writeToEnv('FYERS_AUTH_CODE', $request->get('auth_code'))` wrote `$key.'='.$value` with **no escaping**. A newline in `auth_code` could inject/overwrite other env keys (flip `APP_DEBUG=true`, swap DB/mail/broker creds). No auth, `state`, or signature on the endpoint.
- **Impact:** Unauthenticated `.env` tampering.
- **Resolution:** The entire Fyers integration was removed — webhook route, `TradingController`, `FyersService`, `FyersBrokerAdapter`, the Livewire trading page, and the Fyers console commands were all deleted. The vulnerable endpoint no longer exists.
- **Note:** `writeToEnv()` itself remains unescaped in `app/Helpers/helper.php:20`; audit any remaining callers before relying on it.

### C6 — Debug mode enabled with a live-secret `.env`
- **Status:** ☐
- **Location:** `.env:6` (`APP_DEBUG=true`); `APP_ENV` declared three times at lines 2-4 (staging/local/production)
- **Issue:** Any unhandled exception renders Laravel's error page, which dumps the full environment: DB password, AWS keys, broker secrets (FlatTrade), OpenAI/Anthropic/Gemini keys, WhatsApp/Telegram tokens, Pusher secret.
- **Impact:** Complete secret disclosure on any deployed/staging host.
- **Fix:** `APP_DEBUG=false`; single authoritative `APP_ENV`; **rotate every key in `.env`** (assume compromised).

### C7 — Destructive Livewire methods lack authorization (systemic)
- **Status:** ☑ Substantially complete (2026-06-14) — destructive / state-changing / main-save methods gated app-wide; documented deferrals remain
- **Location:** All of `app/Livewire/**` (was: only ~9 of ~337 components had any authz check).
- **Issue:** Any public Livewire method is invokable via `callMethod` with client-supplied IDs. Route-level `.can()` only guards the initial page GET, not subsequent method calls; `mount()` checks don't re-run. A user with only `*.view` could call `delete()` on sales, roles, branches, customers, etc.
- **Impact:** Mass data destruction / privilege-structure tampering by low-privilege users.
- **Resolution:** `abort_unless(auth()->user()?->can('<resource>.<action>'), 403)` added to privileged public methods across **every module** — delete/bulk-delete, state changes (approve/cancel/decide/complete/dispose/assign), payments, and main create/edit `save()`; `is_super_admin` for the Tenant module. **179 components now carry guards (264 total).** Mapping rules: create-vs-edit via the component's `$table_id`/`$id`; agreement-type-aware perms (`rent out` vs `rent out lease`, cheque variants) resolved at runtime; RentOut financial sub-modals (service/utility payments) gated with `<type>.payment`. **Independently verified:** every active guard permission exists verbatim in `config/permissions.php` (no deny-all); all changed files pass `php -l` + Pint. Delivered partly via committed work (`auth permission added livewire wise too` ×3) and the RentOut nested-tab/modal pass.
- **Deferred — 64 documented `// TODO(C7)` markers, intentionally NOT gated to avoid breaking real flows (need a decision):**
  1. **Nested quick-add `save()`** reachable cross-feature (inline create customer/vendor/account from POS/vouchers; product price/unit modals) — gating with `<resource>.create` would block users who legitimately quick-create inline.
  2. **Sub-record deletes with no dedicated permission** (line items / images / notes) — most gated with the parent `<resource>.edit`; a few left TODO where even that was ambiguous.
  3. **RentOut documents** (`Tabs/DocumentModal::save`, `Tabs/DocumentsTab::deleteDocument/deleteSelected`) — no `rent out document` group in the catalog. Add the group or gate with `rent out.edit`.
  4. **Trading** (`app/Livewire/Trading/*`, `Dashboard/MarketInfo`) — references `flat_trade.*` permissions that **don't exist** in the catalog; add a `flat_trade`/`trading` group (or remove the module) then gate.
- **Pre-existing catalog gaps surfaced (separate from C7, already in committed HEAD):** `local purchase request.*` (likely a typo for `local purchase order` / `lpo-purchase`) and several `*.view own` strings are referenced but absent from the catalog → those row-level "view own" scoping checks silently never fire (fail-open). Small follow-up.

### C8 — FlatTrade trading webhook: unsigned + CSRF-exempt
- **Status:** ☐
- **Location:** `bootstrap/app.php:66` (CSRF exemption); `app/Http/Controllers/FlatTradeController.php:87` (signature check commented out)
- **Issue:** `flat_trade/webhook/post_back` is CSRF-exempt and the HMAC check is a `// TODO`. Anyone can POST forged `trade_executed`/`balance_changed`/`account_updated` events into trading-state handlers.
- **Impact:** Financial-integrity compromise on a live trading app.
- **Fix:** Enforce HMAC verification (`hash_equals` over the raw body against the shared secret) before processing; reject on mismatch.

---

## 🟠 HIGH

### H1 — SQL injection via `sort_direction`
- **Status:** ☐
- **Location:** `app/Traits/BuildsCustomerReminderQuery.php:143,145`
- **Issue:** `orderByRaw("$orderByExpression $sortDirection")`. `$sortDirection`/`$sortField` are unvalidated public Livewire properties (`app/Livewire/Report/Customer/CustomerCallbackReminder.php:43`) hydrated from the client; `orderByRaw` does no asc/desc validation. Also reachable via `CustomerReminderCallbackExport`.
- **Impact:** Authenticated SQL injection (data exfiltration).
- **Fix:** Whitelist `$sortDirection` to `asc|desc` and `$sortField` to known columns.

### H2 — IDOR: print routes leak leases, vouchers, statements
- **Status:** ☐
- **Location:** `routes/print.php:14-29`; handlers `app/Http/Controllers/PrintController.php`
- **Issue:** rentout statement/lease/receipt/voucher and vendor statement carry `auth` but no `.can()`; handlers `findOrFail($id)` with tenant scope only. Any low-priv user can iterate `{id}` to render any lease/payment/PII document.
- **Impact:** Cross-role/branch disclosure of tenant PII and financials.
- **Fix:** Add `.can()` per resource + branch-ownership checks (or `AssignedBranchScope` on those models).

### H3 — IDOR + mass-assignment on customers
- **Status:** ☐
- **Location:** `routes/sale.php:50-51`; `app/Http/Controllers/Settings/CustomerController.php` → `app/Actions/Account/UpdateAction.php`
- **Issue:** `POST/PUT /customers/{id}` have only `auth`; `$request->all()` flows into `Account::find($id)->update()` where `$fillable` includes `opening_debit/credit`, `account_category_id`.
- **Impact:** Any user can overwrite any customer's balances/PII/category by ID.
- **Fix:** Permission gate + input key whitelist + branch-ownership check.

### H4 — PIN login not tenant-scoped + brute-forceable
- **Status:** ☐
- **Location:** `app/Actions/V1/Auth/LoginAction.php:23`; `app/Http/Requests/V1/Auth/LoginRequest.php`; throttle in `routes/api_v1.php`
- **Issue:** Loads all users with a PIN across all tenants and matches a ≤6-char PIN (no min/numeric rule). Throttle is per-IP (10/min), not per-account. Sanctum tokens never expire.
- **Impact:** PIN guessing → token; cross-tenant auth ambiguity.
- **Fix:** Scope candidates to the resolved tenant/branch; require identifier+PIN; per-account lockout; set token expiry.

### H5 — New users get default password `"password"`
- **Status:** ☐
- **Location:** `app/Actions/User/CreateAction.php:12`; `app/Livewire/User/Employee/Page.php:76`
- **Issue:** `$data['password'] ?? 'password'`; the employee form's password rule is commented out. New active employees can log in with `email` + `password`.
- **Impact:** Predictable credentials → account takeover.
- **Fix:** Remove the fallback; require a strong password or random + forced reset.

### H6 — Stored XSS via thermal-printer footer settings
- **Status:** ☐
- **Location:** `resources/views/sale/print.blade.php:681,684`; `sale/day-session-print.blade.php:208,211`; `print/tailoring/*-receipt-thermal.blade.php`; source `app/Livewire/Settings/SaleConfiguration.php:82`
- **Issue:** `{!! $thermal_printer_footer_* !!}` saved unsanitized and served to the browser as HTML (`SaleHelper.php:59` → `PrintController::saleInvoice`). Settings editor stores `<script>`; it fires in every cashier's session.
- **Impact:** Persistent XSS / session theft on a common workflow.
- **Fix:** Use `{{ }}` (with `nl2br(e(...))` if needed) or sanitize with an allow-list on save.

### H7 — Stored XSS via product category & employee names
- **Status:** ☐
- **Location:** `resources/views/livewire/product/table.blade.php:330` (`{!! $item->mainCategory?->name !!}`); `resources/views/sale/print.blade.php:668` (`{!! $sale->employeeNames() !!}`)
- **Issue:** Category names validated only `required|unique`; rendered raw. Employee names rendered raw on the browser-served invoice.
- **Impact:** Stored XSS in admin/inventory views.
- **Fix:** Escape both with `{{ }}`.

### H8 — Committed secrets in tracked env files
- **Status:** ☐
- **Location:** `.env.testing`, `.env.example` (both git-tracked)
- **Issue:** Real-looking `PUSHER_APP_KEY`/`PUSHER_APP_SECRET`, fixed `APP_KEY`, `MAIL_PASSWORD`, `DEFAULT_PASSWORD`. A committed `APP_KEY` enables cookie forgery / `Crypt::` decryption if reused. (`.env`/`.env.backup` are not tracked — good.)
- **Impact:** Credential exposure to anyone with repo access.
- **Fix:** Blank the values in tracked files; rotate the real ones.

### H9 — Seeded super-admins with password `asdasd`
- **Status:** ☐
- **Location:** `database/seeders/UserSeeder.php:14`; `app/Providers/HorizonServiceProvider.php`
- **Issue:** Seeds `admin@astra.com` and `rahees@astra.com` as super-admin with `Hash::make('asdasd')`; the Horizon gate trusts `rahees@astra.com`.
- **Impact:** If seeded in prod / unchanged → super-admin + Horizon takeover.
- **Fix:** No static admin passwords; force reset on first login; pull Horizon gate identity from config.

### H10 — Public Scramble API docs
- **Status:** ☐
- **Location:** `config/scramble.php`
- **Issue:** `RestrictedDocsAccess` is commented out "to allow public access". `/docs/api` + `/docs/api.json` expose the full OpenAPI spec to anyone.
- **Impact:** Endpoint/parameter enumeration roadmap for attackers.
- **Fix:** Re-enable `RestrictedDocsAccess` (or wrap docs in auth+permission); never expose in prod.

### H11 — CORS wildcard origin with credentials
- **Status:** ☐
- **Location:** `config/cors.php`
- **Issue:** `allowed_origins=['*']`, `paths` includes `'*'`, `supports_credentials=true` — invalid + dangerous combination, app-wide.
- **Impact:** Any origin invited to make credentialed cross-site requests.
- **Fix:** Explicit origin allowlist; scope `paths` to `api/*` + `sanctum/csrf-cookie`.

### H12 — Path traversal in backup download
- **Status:** ☐
- **Location:** `app/Http/Controllers/BackupController.php` (`get()`); route `routes/web.php:58`
- **Issue:** `{file}` interpolated into `storage_path('app/private/'.config('app.name').'/'.$file)` with no `basename()`. A user with `backup.download` can fetch `../../.env`.
- **Impact:** Arbitrary file download (privilege escalation for a limited user).
- **Fix:** `basename($file)` + verify the resolved realpath stays inside the backups dir.

### H13 — Telegram webhook has no secret-token verification
- **Status:** ☐
- **Location:** `routes/api.php:11`; `app/Http/Controllers/TelegramWebhookController.php`; `app/Helpers/TelegramHelper.php:112`
- **Issue:** Public `POST /telegram/webhook` → `handleUpdate($request->all())`; webhook registered without `secret_token`.
- **Impact:** Anyone can forge Telegram updates / trigger bot flows.
- **Fix:** Register with a `secret_token` and verify `X-Telegram-Bot-Api-Secret-Token` (constant-time) on every request.

### H14 — Sensitive files committed / world-writable
- **Status:** ☐
- **Location:** `dump.rdb` (git-tracked); `.env`, `.env.backup` are `chmod 777`
- **Issue:** Redis snapshot tracked in git; env files world-readable/writable on disk.
- **Impact:** Data leakage; tampering risk.
- **Fix:** `git rm --cached dump.rdb` (and purge history); `chmod 600 .env*`.

---

## 🟡 MEDIUM

### M1 — SQLi (column name) via `sort_field`
- **Status:** ☐ · **Location:** `app/Actions/Product/Inventory/StockCheck/Item/GetStockCheckItemsAction.php:95`
- **Issue:** `orderBy($sortField, ...)` from raw request input. MySQL backtick-escaping prevents breakout but allows column/table enumeration + error-based info disclosure.
- **Fix:** Whitelist `sort_field` against an allow-list (pattern already used in `DaySessionSalesList::validatedSortField`).

### M2 — IDOR on StockCheck CRUD
- **Status:** ☐ · **Location:** `routes/inventory.php:49-61`; `app/Http/Controllers/StockCheckController.php`
- **Issue:** show/update/delete/import by ID, no `.can()`, tenant-scope only.
- **Fix:** Add inventory permission middleware + branch-ownership verification.

### M3 — Cross-tenant confusion via shared cache
- **Status:** ☐ · **Location:** `app/Services/TenantService.php:71`
- **Issue:** `current_tenant_id` stored under a global cache key; concurrent requests can scope to the wrong tenant.
- **Fix:** Resolve current tenant per-request from host/token only; never persist in shared cache.

### M4 — `AssignedBranchScope` no-ops on empty branch list
- **Status:** ☐ · **Location:** `app/Models/Scopes/AssignedBranchScope.php:16`
- **Issue:** Applies no filter when a user has zero assigned branches → sees all branches.
- **Fix:** Deny-by-default (`whereRaw('1=0')`) when the list is empty.

### M5 — FlatTrade OAuth callback ignores `state`
- **Status:** ☐ · **Location:** `app/Http/Controllers/FlatTradeController.php:21-72`
- **Issue:** Forgets the session state without comparing the returned `state` → OAuth login-CSRF / code injection.
- **Fix:** Compare returned `state` to session (constant-time) before exchanging the code; abort on mismatch.

### M6 — `dd()` left in runtime paths
- **Status:** ☐ · **Location:** `app/Http/Controllers/PhysicalVisitorController.php:70`; `app/Actions/SaleReturn/Payment/DeleteAction.php:22`; `app/Actions/PurchaseReturn/Payment/DeleteAction.php:22`
- **Issue:** `dd($scanResult)` dumps full ID-scan PII to the response and aborts the flow; others break deletion.
- **Fix:** Remove all `dd()` from runtime paths.

### M7 — Sensitive data logged
- **Status:** ☐ · **Location:** `app/Http/Controllers/FlatTradeController.php:44`; `app/Notifications/TelegramChannel.php:29`; `app/Notifications/WhatsappChannel.php:21`; `app/Services/IDCardScannerService.php:28`
- **Issue:** Logs broker `authorization_code`, full provider API responses, and ID-scan PII.
- **Fix:** Redact tokens/PII before logging; drop raw-response logs or gate behind a debug flag.

### M8 — Password hash recorded in audit trail
- **Status:** ☐ · **Location:** `app/Models/User.php:61`
- **Issue:** `$auditExclude` omits `password`; every change stores the bcrypt hash in the broadly-readable `audits` table.
- **Fix:** Add `password` (and token/secret fields) to `$auditExclude` and the global `audit.exclude`.

### M9 — Unrestricted file upload to public disk
- **Status:** ☐ · **Location:** `app/Livewire/RentOut/Tabs/DocumentModal.php:39`; `app/Models/SupplyRequestImage.php:34` (caller `app/Livewire/Maintenance/Complaint.php`)
- **Issue:** `file|max:10240` with no MIME allow-list; stored on the `public` disk with a client-derived extension → SVG/HTML XSS, possible PHP execution.
- **Fix:** Add `mimes:`/`extensions:` allow-list; store on a private disk served through an authenticated controller; derive extension server-side.

### M10 — dompdf `enable_remote=true`, no host allowlist
- **Status:** ☐ · **Location:** `config/dompdf.php:268`
- **Issue:** Latent SSRF / local-file read the moment any user HTML reaches a PDF template (`enable_php` is correctly false).
- **Fix:** Set `enable_remote=false` or set `allowed_remote_hosts`; keep all PDF templates strictly escaped.

### M11 — Sanctum tokens never expire
- **Status:** ☐ · **Location:** `config/sanctum.php:50`
- **Issue:** `expiration => null` — mobile/POS bearer tokens live forever.
- **Fix:** Set an expiration + rotation, especially for the PIN-auth flow.

### M12 — Open public registration
- **Status:** ☐ · **Location:** `routes/auth.php:14`; `app/Http/Controllers/Auth/RegisteredUserController.php`
- **Issue:** `/register` creates a user with null tenant/role and logs them in.
- **Fix:** Disable self-registration or assign tenant/role server-side.

### M13 — Routes with `.can()` commented out
- **Status:** ☐ · **Location:** `routes/purchase.php:20-24` (5 purchase_return routes); `audit/{model}/{id}` (`routes/web.php:53`); several `print/{id}` routes
- **Issue:** Sensitive actions reachable by any authenticated user; `audit/{model}/{id}` takes an arbitrary model string.
- **Fix:** Restore permission middleware; whitelist auditable models.

---

## ⚪ LOW / Informational

- [ ] **L1** — ~80 Livewire tables use unvalidated `orderBy($this->sortField)` (column-enum only; backtick-escaped). Centralize a `validatedSortField()` whitelist.
- [ ] **L2** — `/api/login` (web) lacks route-level `throttle` (Breeze RateLimiter partially mitigates). Add `throttle:10,1`.
- [ ] **L3** — `$guarded = []` on all `Trading*` models + `Notification` — safe only while populated server-side; audit any request-driven mass assignment.
- [ ] **L4** — `unserialize()` in `app/Livewire/Sale/Page.php:122` & `app/Http/Controllers/Sale/PageController.php:277` reads app-written Redis values — theoretical only; prefer JSON. (`Log/Jobs.php:260` already uses `allowed_classes=>false` — safe.)
- [ ] **L5** — `phpoffice/phpspreadsheet 1.30.2` is the oldest major in the tree (historical XXE/formula advisories). Run `composer audit` / `npm audit` in CI; consider upgrading. Other deps are current.
- [ ] **L6** — `resources/views/print/booking/reservation-form.blade.php:310,317` raw `{!! $point !!}` is neutralized today by Browsershot's `disableJavascript()` + `blockDomains(['*'])`; escape it anyway.
- [ ] **L7** — Outbound TLS verification is intact (no `verify=>false`/`withoutVerifying()`); forced HTTPS + trusted proxies configured correctly. *(No action — recorded as verified-good.)*
- [ ] **L8** — Verified safe: ~263 `DB::raw`/`selectRaw` aggregations are static; `UsesBrowsershot` shell calls are hardcoded; `helper.php:755` stored-proc call quotes inputs; most `{!! !!}` wrap framework/boolean markup. *(No action.)*

---

## Appendix — notes & assumptions

- **Verified firsthand:** C1 (exec path), C4 (no-auth + 0.0.0.0 bind), C5 (`writeToEnv` unescaped), C6 (`.env` debug/env), C8 (commented signature).
- **Dependency posture:** Laravel 12.52, Livewire 3.7.10, dompdf 3.1.4, guzzle 7.10, predis 3.4, sanctum 4.3, axios 1.13.2 — all current. Watch `phpoffice/phpspreadsheet` (L5). Run `composer audit` + `npm audit` in CI for authoritative CVE results.
- **Not modified:** This audit was read-only. No remediation has been applied yet.
- **Recommended verification after fixes:** re-run the relevant checks and add regression tests (Pest) for the authz and injection findings.
