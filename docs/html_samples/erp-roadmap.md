# Project Manager ERP — Product Roadmap

_Last updated: 2026-06-14_

A prioritized, phased plan for closing gaps and adding features, sequenced by
**dependency** and **effort‑vs‑impact**. This is a planning document, not a
commitment — reorder phases to match current business pressure (e.g. pull VAT
return generation forward if a filing deadline is looming).

## Current state (one paragraph)

The system is a mature, multi‑tenant, multi‑branch ERP whose **transaction layer
is largely complete**: double‑entry accounting, full procure‑to‑pay, POS + sales
returns, property/rental lifecycle, tailoring, attendance, and an isolated
algo‑trading platform. The gaps are concentrated in (a) **HR beyond attendance**
(no payroll), (b) **finance depth** (no cash flow, bank rec, VAT returns,
multi‑currency), (c) **customer‑facing automation** (no portals, online payment,
or due‑date reminders despite a working WhatsApp/Telegram gateway), and
(d) **platform trust** (very low test coverage, no 2FA, stubbed notifications).

## Prioritization principles

- **Build shared foundations once.** A generic notification/reminder service is
  reused by HR document‑expiry, rent reminders, low‑stock alerts, and tailoring
  delivery — build it before the features that depend on it.
- **Compliance and money‑safety beat polish.** Gulf VAT returns, gratuity/EOS,
  and test coverage on financial paths rank above net‑new conveniences.
- **Effort sizing:** `S` ≈ 1–5 dev‑days · `M` ≈ 1–3 weeks · `L` ≈ 1–2 months
  (single developer). **Impact** is rated 1–5 for the target (Gulf SMB) market.
- **Testing is not a phase.** Per the repo's own rules, every new module ships
  with Pest tests; a backfill of money‑path tests is scheduled in Phase 0.

---

## Phase 0 — Foundations & quick wins

_Goal: small, high‑leverage work that de‑risks the platform and unblocks every
later reminder/alert feature._

| Initiative | Effort | Impact | Notes |
|---|---|---|---|
| **Generic notification/reminder service** (WhatsApp + Telegram + email + SMS, one API) | M | 5 | Gateway drivers already exist; this is the spine for all later alerts. |
| **Notification center** (in‑app bell/inbox + preferences) | M | 4 | `NotificationController` is currently a stub; broadcasting is already wired. |
| **Low‑stock reorder alerts** (+ optional auto‑Purchase Request) | S | 4 | `Product.reorder_level` / `min_stock` exist but nothing reads them. |
| **Auth hardening** — 2FA (TOTP), login lockout, scrub PII from `ApiLog` | M | 4 | No 2FA today; `ApiLog` appears to store raw request/response. |
| **Scheduled report emailing** | S | 3 | Horizon + 59 Excel exports already exist; add a schedule + mail job. |
| **Money‑path test backfill** (sales, journals, payments, returns) | M | 4 | ~31 test files today; protect the financial core first. |

---

## Phase 1 — HR & Payroll suite

_Goal: close the single biggest functional hole. Self‑contained, ties into the
existing GL and attendance, and has clear legal value in the Gulf._

| Initiative | Effort | Impact | Notes |
|---|---|---|---|
| **Payroll engine** — salary structures → payroll run → payslip → GL posting | L | 5 | `User` already holds salary/allowance/HRA; auto‑post to accounting. |
| **Document‑expiry tracking** (visa, passport, labour card, Emirates ID) + alerts | S–M | 5 | Legal must‑have; reuses Phase 0 notifications. |
| **Gratuity / end‑of‑service calculation** | M | 4 | UAE/GCC labour‑law settlement on exit. |
| **Leave management** (types, balances, approval, calendar) | M | 4 | Integrates with attendance + payroll deductions. |
| **Shift scheduling + overtime** | M | 3 | Extends `WorkingDay`; feeds overtime into payroll. |
| **Loans & advances** (repayment via payroll deduction) | M | 3 | Common Gulf SMB need. |

---

## Phase 2 — Finance depth

_Goal: move accounting from "books" to "compliance + control"._

| Initiative | Effort | Impact | Notes |
|---|---|---|---|
| **VAT/GST return generation** (GCC 5%, filing report) | M | 5 | Tax captured per line today but cannot be filed. Pull forward if deadline‑driven. |
| **Cash‑flow statement** | S–M | 4 | Only accrual reports (P&L, balance sheet) exist today. |
| **Bank reconciliation** (statement import + auto‑matching) | M | 4 | Current "rec" only stamps a delivered date. |
| **Multi‑currency** (rates, revaluation, FX gain/loss) | L | 3 | Single currency today; needed for import‑heavy clients. |
| **Recurring journals + budgeting + cost centers** | M | 3 | Department/project P&L and budget‑vs‑actual. |
| **Year‑end close wizard** (retained‑earnings carry‑forward) | M | 3 | Opening balances are manual today. |

---

## Phase 3 — Customer‑facing & automation

_Goal: turn the back‑office ERP into something customers and tenants touch — the
biggest retention/differentiation lever._

| Initiative | Effort | Impact | Notes |
|---|---|---|---|
| **Self‑service portals** (customer / tenant / vendor) | L | 4 | Invoices, statements, ticket submission, rent payment. |
| **Online payment gateway** (Telr / PayTabs / Network Intl) | M | 4 | For rent + sales invoices; regional gateways. |
| **Rent due reminders + arrears/dunning + late fees** | S–M | 4 | Reminder job fires only on booking today; reuses Phase 0. |
| **Tailoring fitting scheduling + delivery notifications** | M | 3 | Appointment module + WhatsApp channel both already exist. |
| **Loyalty / rewards / gift cards / store credit** | M | 3 | Retail repeat‑purchase driver. |

---

## Phase 4 — Inventory maturity & manufacturing

_Goal: accuracy and verticals — most valuable if clients sell perishables,
pharma/cosmetics, serialized goods, or actually manufacture._

| Initiative | Effort | Impact | Notes |
|---|---|---|---|
| **Batch + expiry tracking** (+ FIFO rotation) | M | 4 | `batch` column exists but no expiry/FIFO. |
| **Stock valuation** (weighted‑avg / FIFO) + landed cost | M–L | 4 | Improves COGS accuracy; freight/customs into cost. |
| **Serial‑number tracking** | M | 3 | Electronics/appliances; validate on sale + return. |
| **Production / manufacturing orders** (BOM → output → wastage) | L | 3 | `ProductRawMaterial` exists; `Production` folder is empty. |
| **Bidirectional e‑commerce sync** (Shopify push/pull + webhooks) | M | 3 | `ShopifyService` is read‑only today. |
| **ABC analysis + AI demand forecasting** | M | 3 | Uses existing `laravel/ai`. |

---

## Phase 5 — Platform & AI differentiation

_Goal: ongoing polish and AI leverage; fold in alongside earlier phases rather
than waiting._

| Initiative | Effort | Impact | Notes |
|---|---|---|---|
| **i18n + RTL completion** (Arabic) | M | 4 | ~150 Arabic keys, no RTL layout — direct market fit. |
| **AI assists** — NL report queries, accounting anomaly detection, receipt/invoice OCR, support chatbot | M each | 3–4 | `laravel/ai` + OpenAI already shipped; only used for trade postmortems + product images today. |
| **Global search** (Scout + Meilisearch) | M | 3 | No cross‑module search today. |
| **Full REST API parity + published Scramble docs** | L | 3 | API covers ~12 mobile/POS endpoints vs 110+ controllers. |
| **Unified executive dashboard** | M | 3 | 15 dashboards exist but are fragmented. |
| **CSV import wizard** (field mapping + dry‑run preview) | M | 3 | 10 importers exist but no mapping UI. |
| **PWA / offline POS** | L | 3 | Resilience for retail floor. |

---

## Cross‑cutting (apply in every phase)

- **Tests** with each module (Pest), plus the Phase 0 money‑path backfill.
- **Audit logging** — already standard via `owen-it/laravel-auditing`; keep it on new models.
- **Arabic strings** added as features ship, not retrofitted later.
- **Security review** on anything touching auth, payments, or PII.

## Strategic note — the trading module

The algo‑trading platform (FlatTrade live + paper, backtesting, risk engine, AI
postmortems) is **architecturally bolted on**: separate routes, no foreign keys
to any ERP entity. It is effectively a second product in the same repo. Decide
deliberately whether to (a) keep it as an internal tool, (b) extract it into its
own service, or (c) invest to productize it (portfolio analytics, options,
real‑time streaming). It should not sit on the ERP roadmap by default.

## Suggested sequence

`Phase 0` (always first — it unblocks the rest) → `Phase 1 HR/Payroll`
(largest hole) → `Phase 2 Finance` (compliance) → `Phase 3 Portals` (growth) →
`Phase 4 Inventory` and `Phase 5 Platform/AI` interleaved as client demand dictates.
Pull **VAT returns** and **document‑expiry alerts** forward if either is causing
active pain.
