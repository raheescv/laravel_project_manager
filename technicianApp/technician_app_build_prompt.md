# Build Prompt — Technician Maintenance App

> **How to use this file:** This is a complete, self-contained build spec. Hand it to an
> implementer (human or agent) working inside this repo. It has two halves:
> **Part 1 (Backend)** adds the missing Laravel `/api/v1` endpoints; **Part 2 (Flutter)**
> builds the technician app inside `mobileApp/`.
>
> **Two authoritative references — respect the precedence:**
> 1. **Architecture / structure / state / DI / network / models / naming** → follow
>    [`mobileApp/project_architecture_skeleton.md`](project_architecture_skeleton.md).
>    Section numbers below (e.g. "skeleton §4") point into it.
> 2. **Visual design, theme, widgets, settings UX** → copy the **live v3 code** already in
>    `mobileApp/lib/` (the "Astra" design system).
>
> **Where the two conflict, the live v3 code wins for anything visual/navigational**
> (the app has evolved past the skeleton: it uses `go_router` and the `AstraPalette`
> theme via `context.astra`, whereas the skeleton documents the classic `Navigator` +
> `ColorManager` baseline). Use the skeleton for *how code is layered and wired*; use the
> live `features/admin`, `features/sales`, and `features/settings` for *how screens look
> and behave*.

---

## 0. Goal

A field **technician** logs in, lands on a **dashboard** summarizing their workload, browses
their **assigned complaints**, opens one, and completes the full on-site workflow:
technician remarks → spare-part/supply items → notes → photo/video/PDF attachments →
**Complete**. It must reproduce the web page at `property/maintenance/complaint/{id}`
feature-for-feature, with premium UI consistent with the existing app.

**Source of truth for behavior:** `app/Livewire/Maintenance/Complaint.php` and
`resources/views/livewire/maintenance/complaint.blade.php`. Do **not** invent new
business rules — mirror these exactly.

---

# PART 1 — Backend (Laravel `/api/v1`)

No maintenance/complaint/technician API exists yet. Add it following the existing V1
patterns: `routes/api_v1.php`, `IdentifyTenant`, `auth:sanctum`, `EnsureMobilePermission`,
`app/Actions/V1/*` action classes, `app/Http/Resources/V1/*` resources, and the
`sendSuccess`/`sendServerError` envelope helpers used by the current controllers.

All routes are **tenant-scoped**, **Sanctum-authenticated**, and **filtered to
`technician_id = auth()->id()`**. Reuse `POST /api/v1/login` (PIN or password) — the
`AuthUserResource` already returns `is_admin` and `permissions[]`.

### 1.1 Endpoints

| Method | Path | Mirrors (Livewire) | Notes |
|--------|------|--------------------|-------|
| `GET`  | `/technician/dashboard` | derived from `MaintenanceComplaint` | Summary counts for the logged-in technician |
| `GET`  | `/technician/complaints` | `loadData()` list mapping | Paginated; filters `?status=`, `?search=`, date range; infinite scroll |
| `GET`  | `/technician/complaints/{id}` | full `loadData()` payload | Detail (see 1.3) |
| `PUT`  | `/technician/complaints/{id}` | `save('pending')` | Save `technician_remark` only |
| `POST` | `/technician/complaints/{id}/complete` | `save('completed')` | Requires non-empty remark; auto-completes parent maintenance |
| `POST` | `/technician/complaints/{id}/supply-items` | `addCart()` | Lazily creates the `SupplyRequest` (`getOrCreateSupplyRequest`) |
| `PUT`  | `/technician/supply-items/{itemId}` | `editCartItem()` | Recompute totals |
| `DELETE` | `/technician/supply-items/{itemId}` | `deleteItem()` | Gate `supply request.delete item` |
| `POST` | `/technician/complaints/{id}/notes` | `addNote()` | |
| `DELETE` | `/technician/notes/{noteId}` | `deleteNote()` | |
| `POST` | `/technician/complaints/{id}/attachments` | `updatedImages()` | multipart, multiple files (image/video/pdf/doc), uses `SupplyRequestImage::storeFile` |
| `DELETE` | `/technician/attachments/{imageId}` | `deleteImage()` | |
| `GET`  | `/products` | *(exists — reuse)* | Product picker + barcode lookup |
| `GET`  | `/branches` | *(exists — reuse)* | Store dropdown |

### 1.2 Key rules to replicate exactly

- **Complete** (`POST .../complete`): reject if `technician_remark` is empty; set
  `status=Completed`, `completed_by=auth id`, `completed_at=now()`. Then **auto-complete
  the parent `Maintenance`** when it has no complaints left in a non-`completed`/non-`cancelled`
  state (see `Complaint.php` lines ~525–538).
- **Supply items**: `mode ∈ {New, Damaged}`; on product select, default `unit_price` from
  `product.cost`; `total = quantity × unit_price`. After every item add/edit/delete,
  recompute `SupplyRequest.total` and `grand_total` (`grand_total = total + other_charges`).
- **Barcode**: looking up a product by barcode and auto-adding it to the cart mirrors the
  web `updated('item.barcode')` behavior.
- Editing is blocked when complaint status is `completed` or `cancelled`.

### 1.3 Detail payload shape (`GET /technician/complaints/{id}`)

Return everything `loadData()` assembles, as a resource:

```jsonc
{
  "id": 1,
  "status": "pending",            // pending|assigned|completed|outstanding|cancelled
  "is_completed": false,
  "is_cancelled": false,
  "technician_remark": "",
  "property_info": {              // registration_id, group, building, type, property_number,
                                  // priority (label), priority_color, date, time
  },
  "customer_info": {              // complaint_status(+color), rentout_id, rentout_status,
                                  // agreement_start_date, customer_name, customer_mobile, work_order_no
  },
  "activity_log": {               // created_by/at, assigned_by/at, completed_by/at
  },
  "all_complaints": [             // sibling complaints on the same maintenance
    { "id", "category_name", "complaint_name", "technician_name",
      "technician_remark", "status", "status_label", "status_color", "is_current" }
  ],
  "supply_request": {
    "id", "total", "other_charges", "grand_total",
    "items": [ { "id", "branch_id", "branch_name", "product_id", "product_name",
                 "mode", "quantity", "unit_price", "total", "remarks" } ],
    "notes": [ { "id", "note", "creator", "created_at" } ],
    "images": [ { "id", "name", "type", "path"/*absolute URL*/,
                  "is_image", "is_video", "is_pdf" } ]
  }
}
```

Use the enum `label()` / `color()` from `MaintenanceComplaintStatus`, `MaintenancePriority`,
`MaintenanceStatus` so the app renders identical status/priority chips.

### 1.4 Permissions

Gate the routes with `EnsureMobilePermission` on the relevant maintenance permissions
(`maintenance.view`, `maintenance.complete`, `supply request.*`). **Before wiring**, confirm
which Spatie role/permission identifies a "technician" so list/detail scope correctly.

---

# PART 2 — Flutter app (inside `mobileApp/`, package `invo`)

Build the technician experience as **new features inside the existing `mobileApp/`**, sharing
its `shared/` layer (auth, tenant header, networking, theme) — **not** a from-scratch project.

## 2.1 Data model reference (ground truth)

- **MaintenanceComplaint**: `maintenance_id`, `complaint_id`, `status`
  (pending/assigned/completed/outstanding/cancelled), `technician_id`, `technician_remark`,
  `assigned_by/at`, `completed_by/at`, `supply_request_id`.
- **Maintenance** (parent): property → building → group, type, `rentOut` → customer,
  `date`, `time`, `priority` (low/medium/high/critical), `segment`, `contact_no`, `remark`,
  `status` (pending/in_progress/completed/cancelled).
- **Complaint** → `category`.
- **SupplyRequest**: `order_no`, `total`, `other_charges`, `grand_total`, `status`;
  **items** (product, branch/store, `mode` New|Damaged, qty, unit_price, total, remarks);
  **notes** (note, creator, created_at); **images** (name, type, path, is_image/is_video/is_pdf).

## 2.2 Feature structure (skeleton §3, §4, §17, §21)

Add three self-contained features. Each follows the three-layer structure with `index.dart`
barrels at every level, and `screens/v3/` + `widgets/v3/` for the current design generation:

```
lib/features/technician_dashboard/
lib/features/complaints/          # assigned-complaints list
lib/features/complaint_detail/    # full workflow screen
    ├── domain/
    │   ├── models/      → *_model.dart (json_serializable, skeleton §8)
    │   ├── repository/  → *_repository.dart (abstract, skeleton §4)
    │   └── services/    → *_service.dart (implements repo, calls the network layer)
    ├── logic/
    │   └── *_cubit/     → *_cubit.dart + *_state.dart (part of)
    ├── screens/v3/      → *_screen.dart
    ├── widgets/v3/      → *_widget.dart
    └── index.dart       → re-exports all four layers
```

Follow the "How to Add a New Feature" 9-step recipe in **skeleton §21** verbatim.

## 2.3 State management (skeleton §5)

- **Cubit per screen**, state extends `Equatable`, immutable fields, `copyWith()`, `get props`,
  declared as `part of` the cubit.
- Use the project's status enum for every async op. The skeleton documents `DataFetchStatus`
  (`idle → waiting → success | failed`); **match whatever the live `features/admin` cubits
  use** (`admin_cubit.dart`, `day_session_cubit.dart`) so it stays consistent — if the live
  code uses `DataFetchingStatus`, use that. Do not introduce a third variant.
- The complaint-detail Cubit should, after every write (save remark, add/edit/delete item,
  add/delete note, upload/delete attachment), **re-fetch the detail** and re-emit — the web
  `loadData()` runs after each mutation; replicate that reconciliation.
- Never mutate state directly — always `emit(state.copyWith(...))` (skeleton §20).

## 2.4 Dependency injection (skeleton §6)

- Register each `{Name}Repository` against its `{Name}Service` in the DI setup
  (`registerLazySingleton<Repo>(Service.new)`). Cubits hold the **abstract repository** type
  resolved via the service locator — never the concrete service (skeleton §20).
- Register in the existing service-locator setup file used by the app.

## 2.5 Network layer (skeleton §7)

- All calls go through the project's single HTTP entry point. The skeleton documents
  `HttpHelper.getDataFromServer(...)`; the live app wraps Dio in `HttpService` with a token
  interceptor + tenant identification. **Use whatever the live services already call** (open
  an existing service such as `features/admin/domain/services/admin_service.dart` and copy its
  call style) so auth/tenant headers are applied automatically.
- Add every new endpoint as a constant in `lib/shared/api/end_points.dart` (skeleton §7).
- Check `success` before parsing; throw `ApiException` on failure; Cubit catches and emits a
  `failed` state with `errorMessage` (skeleton §15).

## 2.6 Models (skeleton §8)

Prefer `json_serializable` for new models (`@JsonSerializable`, `@JsonKey(name: 'snake_case')`,
`explicitToJson: true` for nested). Run
`dart run build_runner build --delete-conflicting-outputs` and commit the `.g.dart` files.
Models to create: `TechnicianDashboardModel`, `ComplaintListItemModel`, `ComplaintDetailModel`
(+ nested `PropertyInfo`, `CustomerInfo`, `ActivityLog`, `SiblingComplaint`, `SupplyRequest`,
`SupplyItem`, `SupplyNote`, `Attachment`).

## 2.7 Routing / navigation

Follow the **live v3 code**, not skeleton §11 (the app uses `go_router`, the skeleton predates
it). Register technician routes in the existing router and add a technician bottom-nav via
`astraNavTabs` in `shared/widgets/astra_bottom_nav.dart`, wired through `home_shell.dart`. For
a technician role show: **Dashboard · My Jobs · Settings** (hide POS/sales tabs based on
`is_admin` / permissions).

## 2.8 Screens & UX

Mirror the information architecture of `complaint.blade.php`:

1. **Login** — reuse the existing PIN + credential + biometric flow (`features/auth`). Route
   technicians to the technician dashboard on success.
2. **Dashboard** (`technician_dashboard`) — hero greeting + KPI cards (Assigned, Pending,
   Completed Today, Completed This Week, Outstanding), a priority breakdown
   (Critical/High/Medium/Low), and a "Recent complaints" list. Pull-to-refresh.
3. **My Complaints** (`complaints`) — status segmented control (Pending / Assigned /
   Completed / All), search, date presets + custom range, **paginated infinite scroll**
   (reuse the Sales "Bento control card" pattern from `features/sales`). Card shows complaint +
   category, property number/building, priority chip, status chip, appointment date, customer.
4. **Complaint Detail** (`complaint_detail`) — sectioned exactly like the web page:
   - **Property info bar** — REG id, group, building, type, unit number, priority + status chips.
   - **Details** — appointment date/time, customer, mobile (tap-to-call), rentout id,
     agreement start, work order no.
   - **Technician Remarks** — multiline editor; disabled when completed/cancelled.
   - **Activity Log** — Created → Assigned → Completed timeline.
   - **Maintenance Requests (siblings)** — all complaints on the same maintenance; current one
     highlighted; tap to switch.
   - **Supply Items** — add row: Store picker, product picker + **barcode scan**
     (`mobile_scanner`), mode New/Damaged, qty, price, auto total, remarks; editable/deletable
     rows; live subtotal + grand total.
   - **Notes** — add / list / delete.
   - **Attachments** — capture from camera or pick files (image/video/pdf); thumbnail grid with
     lightbox; delete.
   - **Actions** — **Save** (pending) and **Complete** (confirm dialog; blocks if remark empty,
     matching server validation). Hide actions when completed/cancelled.

Every write is optimistic, then reconciled against the re-fetched detail payload (2.3).

## 2.9 Visual design — use the Astra system (live v3 code, not skeleton §13)

Copy the existing design language so the technician screens are visually identical to POS:

- **Palette/skins** — `shared/utils/components/theme/palette.dart`: `AstraPalette` + presets
  (`AstraPresets.all`: Aurora Glass, Luminous Indigo, Emerald & Gold, Editorial Noir). Never
  hardcode colors; read from **`context.astra`** (the `AstraThemeX` extension in
  `theme_getters.dart`). Use its semantic getters: `primary`, `primaryDark`, `accent`/
  `goldText`, `canvas`, `card`/`cardSolid`, `cardBorder`, `ink`, `textSecondary`, `textMuted`,
  `hairline`, `tint`, `heroGradient`, `primaryGradient`, `accentGradient`, and status tints
  `successTint`/`dangerTint`/`warnTint`/`warnText` (+ `AstraPalette.success`/`danger`).
- **Status/priority chips → Astra tints** (not Bootstrap names): completed→`successTint`;
  cancelled/outstanding→muted/`danger`; pending & high→`warnTint`+`warnText`; assigned &
  medium→`tint`+`primary`; critical→`dangerTint`. Render via `StatusPill` / `AstraChip`.
- **Reusable widgets** — build every screen from `shared/widgets/astra_widgets.dart`:
  `AstraBackground`, `EmeraldHeader`, `HeaderIconButton`, `AstraCard`, `IconChip`,
  `ProductThumb`, `SectionLabel`, `AstraChip`, `AstraButton`, `StatusPill`, `QtyStepper`
  (for supply-item quantity), `Monogram`, `EmptyState`, `KeyboardDoneField`. Dashboard KPIs:
  `shared/widgets/charts.dart`. Quantity sheet: `shared/widgets/qty_input_sheet.dart`.
- **Typography** — use the `ui()` / `serif()` text-style helpers already used across v3
  screens. (The skeleton's `getRegularStyle`/`ColorManager` baseline still exists, but v3
  screens use the Astra helpers — match the v3 screens.)
- **Screen shell convention** — `Scaffold(backgroundColor: transparent) → AstraBackground →
  Column[ EmeraldHeader(title:…), Expanded(MaxWidthBox → ListView of AstraCards) ]`, matching
  `settings_screen.dart` and `dashboard_screen.dart`.
- **Theme mode** — respect `ThemeCubit` (`AstraMode.light/dark/system`); every screen must
  render correctly in light and dark.
- **Sheets are click-and-go** — pickers (Store, status filter, product) apply on tap, no Save
  button; copy `features/settings/widgets/v3/branch_sheet.dart` / `currency_sheet.dart`.
- Global haptics already fire app-wide (`HapticTapDetector`) — do not re-add per-tap; reserve
  `impact()` for Complete/error events.
- Icons: match the existing app's icon usage; on the web side stay with Font Awesome 4.3.0.

## 2.10 Settings (model on `features/settings`)

Reuse `features/settings/screens/v3/settings_screen.dart` — a `ListView` of tappable
`AstraCard`s — and trim/extend for technicians:

- **Keep**: Colour preset (`showThemeSheet`), Appearance (`appearance_sheet`), Branch
  (`branch_sheet`), My permissions (`permissions_screen.dart`, driven by
  `AuthCubit.user.permissions`/`isAdmin`), Server connection (`ConnectionSheet`), Log out
  (confirm dialog + `AuthCubit.logout()`).
- **Drop** POS-only cards (Currency, Printer/receipt) unless a printable **job sheet / work
  order PDF** is wanted — if so, repurpose the printer card using the existing `printing`+`pdf`
  approach (`shared/widgets/receipt_pdf.dart`).
- **Add** a technician profile header (name, designation, code from `AuthUserResource`) and
  optional **change PIN / change password** (endpoints exist: `/api/v1/change-pin`,
  `/api/v1/change-password`).

---

## 3. Conventions checklist (skeleton §20)

- [ ] Every `domain/`, `logic/`, `screens/`, `widgets/` folder has an `index.dart`; feature
      root re-exports all four layers; import features via their top-level `index.dart`.
- [ ] New screens/widgets under `v3/`.
- [ ] State immutable; only `emit(copyWith(...))`.
- [ ] Cubits depend on abstract repositories resolved via the service locator.
- [ ] All HTTP through the shared helper; check success; throw `ApiException`; emit `failed`.
- [ ] New endpoints in `end_points.dart`; new DI registrations in the setup file.
- [ ] `json_serializable` models; `.g.dart` generated and committed.
- [ ] No hardcoded colors (`context.astra`), strings (`AppStrings`), or raw dimensions in v3
      widget files where the live code uses the managers.
- [ ] Naming per skeleton §19 (`*_repository.dart`, `*_service.dart`, `*_cubit.dart`,
      `*_state.dart`, `*_screen.dart`; `PascalCase` classes; `fetch*` async methods).

---

## 4. Acceptance criteria

1. Backend V1 endpoints (Part 1) exist with resources, actions, permission middleware, and
   tenant scoping — behavior identical to `App\Livewire\Maintenance\Complaint`.
2. Technician app: login → dashboard → complaints list → complaint detail with the complete
   edit/complete workflow.
3. A technician can end-to-end: see their summary; open an assigned complaint; add/edit/remove
   supply items (incl. barcode scan); add notes; upload attachments (camera + file); write a
   remark; and **Complete** (which auto-completes the parent maintenance when all siblings are
   done).
4. UI matches the existing app's Astra design (theme presets, light/dark, shared widgets);
   settings reuse the `features/settings` scaffolding; haptics respected.
5. Code follows `project_architecture_skeleton.md` for structure, DI, network, state, models,
   and naming; visuals/navigation follow the live v3 code where they diverge.

## 5. Open questions to confirm before starting

- Which Spatie role/permission identifies a "technician" (for scoping + `EnsureMobilePermission`)?
- Ship as a **separate flavor/build** or a **technician mode inside the existing app**
  (recommended: same app, role-gated nav sharing `shared/`)?
- Is a printable **job-sheet / work-order PDF** in scope for v1?
```

