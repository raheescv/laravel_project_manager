---
name: flutter-apps
description: "Use when working in mobileApp/ (the Astra POS/admin app) or technicianApp/ — adding a screen, cubit, repository, model, or endpoint call, wiring dependencies, or debugging state, storage, offline behaviour, printing, scanning, or auth/lock flows. Covers the flutter_bloc + get_it three-layer architecture, feature folder anatomy, HolderCubit, EndPoints, the service_locator setup, and the hard project rules (never run dart format; never blind-tap the live POS simulator). Read before editing any .dart file in this repo."
---

# Flutter Apps

Two Flutter apps live in this repo and talk to `/api/v1` (see the mobile-api-v1 skill for the server side):

- `mobileApp/` — Astra POS/admin (Dart package name **`invo`**)
- `technicianApp/` — standalone maintenance-technician app, scoped to `technician_id = auth id`

Both follow the same architecture, adopted in the 2026-06-30 restructure. Paths from before that (`lib/state`, `lib/core`, `lib/models`, `lib/theme`, `ApiService`, `Storage`, `ApiClient`) no longer exist — do not follow older references.

## Two hard rules

1. **Never run `dart format` (or format-on-save) on these packages.** The codebase uses a compact hand style; formatting produces ~6000 lines of churn. Edit by hand and verify with `flutter analyze lib`. A background auto-committer may commit mid-session, so unintended churn lands in history.
2. **Never blind-tap the running simulator.** It writes to the real backend — a mis-tap has charged a real sale. Screenshot before every tap, and do not drive the New Sale flow to "test" something.

## Layout

```
lib/
  main.dart  main_dev.dart  main_prod.dart  flavors.dart  app.dart
  features/<feature>/
    domain/repository/<f>_repository.dart   # abstract contract
    domain/services/<f>_service.dart        # concrete impl, talks HTTP
    logic/<name>_cubit/<name>_cubit.dart    # state
    screens/                                # full pages
    widgets/                                # feature-local widgets
  shared/
    api/end_points.dart                     # every /api/v1 path, one place
    domain/{models,repository,services,constants}
    logic/{branch,currency,haptics,theme}_cubit, logic/base/holder_cubit.dart
    utils/local_storage/local_storage_service.dart
    utils/router/http_utils/http_service.dart
    utils/service_locator_setup/setup.dart
    widgets/
```

Features present in `mobileApp`: `auth`, `sale`, `sale_return`, `sales`, `sales_returns`, `stock_check`, `admin`, `profile`, `settings`, `shell`.

## The three layers

**Repository (abstract) → Service (concrete) → Cubit → Screen.** Cubits and screens depend on the *abstract* repository, never on the service:

```dart
abstract class SaleRepository {
  Future<Sale> createSale(Map<String, dynamic> payload);
  Future<SalesPage> sales({String? status, String? search, /* … */ int page, int perPage});
  Future<Sale> saleById(String id);
  Future<void> deleteSale(String id);   // throws ApiException the caller surfaces
}
```

Services use `HttpService` and pull their paths from `EndPoints` — no string literals scattered through feature code:

```dart
static const String login = '/login';
static const String profilePhoto = '/profile/photo';
```

Everything is registered once at boot in `shared/utils/service_locator_setup/setup.dart`:

```dart
serviceLocator
  ..registerSingleton<LocalStorageService>(storage)
  ..registerSingleton<HttpService>(http)
  ..registerLazySingleton<SaleRepository>(SaleService.new)   // abstract → concrete
  ..registerLazySingleton(() => SaleCubit(serviceLocator()));
```

Adding a feature means adding all four pieces plus its registration — a screen that news up a service directly is a bug.

## State: two cubit shapes

- **Ordinary `Cubit<State>`** with an immutable state class for new, well-bounded state.
- **`HolderCubit`** (`shared/logic/base/holder_cubit.dart`) for the screens migrated from `ChangeNotifier`: it owns mutable fields directly and emits a monotonically increasing tick. Call `refresh()` where the old code called `notifyListeners()`, and watch with `context.watch<XCubit>()`. `CartCubit` is the reference. Don't convert one to the other opportunistically — match the file you're in.

## Cross-cutting behaviour already handled

Don't reimplement these per screen:

- **Haptics** — every tap ticks app-wide via `HapticTapDetector` (`shared/utils/components/haptics.dart`), installed in `app.dart`'s builder. Do not add per-tap `selectionClick()`; reserve `impact()` for emphasis or async completion.
- **Scanning** — the shared `ContinuousScannerScreen` is permission-first (`permission_handler` primer → settings flow) with a serialized camera op-queue. Never let `mobile_scanner` self-request permission or allow overlapping starts.
- **Printing** — receipts render on-device via `buildReceiptPdf` (instant, offline). Arabic shapes correctly **only** when IBM Plex Sans Arabic is the *base* font; as a fallback it reverses and disconnects. Print options come from the web Sale Configuration via `/settings/sale`; only paper width is device-local. Charging auto-prints to a paired printer (Android cannot print silently).
- **Auth and lock** — shared-till handover puts the app in `AuthStatus.locked` rather than signing out, so unlock is a local PIN check with no API call and no catalog refetch.
- **Offline** — currency and other settings are cached locally from the web settings, which remain the source of truth (`rate_to_base` = base units per 1 unit).
- **Employee self-scope** — non-admin employees see only their own sales; the server enforces it, so don't add a client-side filter that contradicts it.

## Server contract

Every response is `{success, data, message}`. Dart models are hand-written, so any change to an `app/Http/Resources/V1/` resource must land in the same change as its Dart model. Endpoint added? Add it to `EndPoints`, the repository contract, the service, and the cubit — in that order.

## Verifying

`flutter analyze lib` is the check. Run the app only when you must, and re-read the two hard rules above before touching a running simulator.
