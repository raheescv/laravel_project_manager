# Project Architecture Skeleton

## Table of Contents

1. [Project Overview](#1-project-overview)
2. [Top-Level Directory Structure](#2-top-level-directory-structure)
3. [Feature Organization](#3-feature-organization)
4. [Three-Layer Feature Architecture](#4-three-layer-feature-architecture)
5. [State Management — BLoC / Cubit](#5-state-management--bloc--cubit)
6. [Dependency Injection — get_it](#6-dependency-injection--get_it)
7. [Network / API Layer](#7-network--api-layer)
8. [Data Models — hand-written, defensively](#8-data-models--hand-written-defensively)
9. [Local Storage Layer](#9-local-storage-layer)
10. [Data Flow — End to End](#10-data-flow--end-to-end)
11. [Routing and Navigation](#11-routing-and-navigation)
12. [Flavor / Environment Setup](#12-flavor--environment-setup)
13. [Theme and Styling System](#13-theme-and-styling-system)
14. [Asset Organization](#14-asset-organization)
15. [Error Handling Patterns](#15-error-handling-patterns)
16. [Shared Components](#16-shared-components)
17. [Export / Index File Convention](#17-export--index-file-convention)
18. [Key Integrations](#18-key-integrations)
19. [Naming Conventions](#19-naming-conventions)
20. [Rules and Conventions](#20-rules-and-conventions)
21. [How to Add a New Feature](#21-how-to-add-a-new-feature)
22. [Reusable Skeleton Templates](#22-reusable-skeleton-templates)

---

> **Status note (2026-08-07).** This document began as a generic template and
> parts of it still describe an app this is not. The sections below have been
> corrected against the real codebase; where a rule is *aspirational* rather
> than current practice it now says so explicitly. For the enforceable
> day-to-day patterns, the `flutter-code-standards` skill is authoritative, and
> outstanding gaps are tracked in `docs/mobile-app-architecture-audit.html`.

## 1. Project Overview

| Property | Value |
|----------|-------|
| Dart package | `invo` |
| State management | `flutter_bloc` — see §5 for the two cubit shapes actually in use |
| DI framework | `get_it` |
| HTTP client | `dio`, wrapped by `HttpService` |
| Routing | `go_router`, declarative, with permission-gated redirects (§11) |
| Typography | `AstraTypefaces` — 5 user-selectable pairings, via `ui()` / `serif()` (§13) |
| Colour | `context.astra` palette — 5 presets × light/dark (§13) |
| Serialization | hand-written `fromJson` / `toJson` with coercion helpers (§8) |
| Flavors | `dev`, `prod` |
| Verification | `flutter analyze lib` (zero issues, 17 strict lints) + `flutter test` |

**Not used, despite what a generic Flutter skeleton would assume:** Firebase in
any form (no Analytics, Crashlytics, Messaging or Remote Config),
`json_serializable` / `build_runner`, and named-route or imperative
`GlobalKey<NavigatorState>` navigation. `flutter_screenutil` is initialised in
`app.dart` but currently read by no widget — see the audit for that decision.

---

## 2. Top-Level Directory Structure

```
mobileApp/
├── android/  ios/  macos/  web/   # Native projects
├── assets/
│   ├── fonts/                     # IBM Plex Sans Arabic (receipt printing)
│   └── icon/                      # Launcher icon source
├── docs/                          # Design previews + the architecture audit
├── test/                          # Widget + unit tests, with fakes in support/
├── lib/
│   ├── app.dart              # Root widget, MultiBlocProvider, MaterialApp.router
│   ├── main.dart             # Boot sequence, DI setup, runApp
│   ├── main_dev.dart         # Flavor entry point (sets F.appFlavor = dev)
│   ├── main_prod.dart        # Flavor entry point (sets F.appFlavor = prod)
│   ├── flavors.dart          # Flavor enum + F helper class
│   ├── features/             # One directory per feature
│   └── shared/               # Cross-feature code
├── env.json / gen_env.sh          # Build-time environment
└── project_architecture_skeleton.md
```

There is no `config/` or `doc/` directory, and no `firebase_options_*.dart` —
this app has no Firebase dependency. Assets are limited to fonts and the
launcher icon; there is no `assets/images|icons|gifs/v3/` tree, so §14's asset
manager classes describe a convention that does not yet have any content.

---

## 3. Feature Organization

Every product domain has its own directory under `lib/features/`. Each feature is fully self-contained — its domain layer, state management, screens, and widgets all live inside the same folder.

```
lib/features/
├── auth/          ← Login (credential / PIN / biometric), lock, connection
├── shell/         ← App shell: bottom nav on phone, side rail on tablet
├── admin/         ← Dashboard, reports, day session
├── sale/          ← New Sale, cart, review & pay, invoice
├── sales/         ← Sales list (screens only — no logic layer yet)
├── sale_return/   ← Return authoring: pick invoice → compose → review → receipt
├── sales_returns/ ← Returns list (screens only — no logic layer yet)
├── stock_check/   ← Physical inventory count
├── profile/       ← Profile, edit, change PIN / password
└── settings/      ← Appearance, typography, branch, currency, print, permissions
```

Features should not cross-import each other's internals; shared logic and types
live in `lib/shared/`. **Currently 45 imports break this rule** — `shell`
legitimately reaches every feature it hosts, but the rest (e.g. `sale`'s cart
cubit importing `settings`' print cubit) are real coupling. See the audit.

---

## 4. Three-Layer Feature Architecture

Every feature follows the same internal structure:

```
features/{name}/
├── domain/
│   ├── models/         → Data classes
│   ├── repository/     → Abstract interfaces (contracts)
│   └── services/       → Concrete implementations of the repository
├── logic/              → BLoC / Cubit state management
├── screens/            → Full-page UI widgets
└── widgets/            → Feature-specific reusable widgets
```

Each layer has an `index.dart` re-export file (see [§17](#17-export--index-file-convention)).

The top-level `features/{name}/index.dart` re-exports all four layers:

```dart
export 'domain/index.dart';
export 'logic/index.dart';
export 'screens/index.dart';
export 'widgets/index.dart';
```

### Domain Layer

**`models/`** — Pure Dart data classes. No Flutter imports, no business logic.

- Extend `Equatable` or `with EquatableMixin`
- Provide `fromJson(Map<String, dynamic>)` constructor
- Provide `toJson()` returning `Map<String, dynamic>`
- Override `get props` for value equality

**`repository/`** — Abstract classes that define what a service must do. Never import HTTP or platform code here.

```dart
abstract class ExampleRepository {
  Future<ExampleModel> getItem({required String id});
  Future<void> createItem({required ExampleModel body});
}
```

**`services/`** — Concrete classes that `implement` the repository. These import `HttpService` (via `serviceLocator`), `EndPoints`, etc.

```dart
class ExampleService implements ExampleRepository {
  @override
  Future<ExampleModel> getItem({required String id}) async { ... }
}
```

### Logic Layer

Contains Cubits (simple) and Blocs (event-driven complex). Each Cubit/Bloc lives in its own subdirectory with its state (and event if a Bloc) as a `part` file.

```
logic/
└── {name}_cubit/
    ├── {name}_cubit.dart   (class + part directive)
    └── {name}_state.dart   (part of)

logic/
└── {name}_bloc/
    ├── {name}_bloc.dart
    ├── {name}_event.dart
    └── {name}_state.dart
```

### Screens Layer

Full-page widgets. New screens go under `v3/` to distinguish the current design generation:

```
screens/
└── v3/
    └── {name}_screen.dart
```

### Widgets Layer

Feature-specific reusable widget components. Also uses `v3/`:

```
widgets/
└── v3/
    └── {name}_widget.dart
```

---

## 5. State Management — BLoC / Cubit

> **Status (2026-08-07): adopted.** Every cubit in the app is now a real
> `Cubit<State>` with an `Equatable` state, `copyWith` and `DataFetchStatus` —
> 16 of them. The old `HolderCubit` migration bridge (a `Cubit<int>` that owned
> mutable fields and emitted a tick) has been deleted along with its last
> subclass. `PaginatedListCubit` and `CartCubit` are the reference
> implementations.
>
> Two things are still outstanding: `buildWhen` / `BlocSelector` are unused, so
> rebuilds are not yet filtered per field (now *possible* — the tick made it
> impossible); and 125 `setState` calls remain for genuinely screen-local view
> state, which is fine.

### Migrating a cubit (the method that worked)

1. Move the fields into an `Equatable` state class with `copyWith`, as a
   `part` file.
2. Replace each mutator's assignments with `emit(state.copyWith(…))`.
3. Leave a forwarding getter per field on the cubit
   (`double get total => state.total`) so existing readers compile unchanged —
   `CartCubit` had 109 read sites across 6 files and none needed touching.
4. Adopt `BlocBuilder` / `BlocSelector` in the hot screens afterwards, at
   leisure.

Watch for state that depends on something outside the cubit: `ThemeCubit` had
to take `platformIsDark` as a *field*, because with an `Equatable` state an
emit that changes nothing rebuilds nothing — and an OS dark-mode flip has to
re-skin the app.

### Cubit vs Bloc — When to Use Each

| Cubit | Bloc |
|-------|------|
| Simple operations, no complex event routing | Complex flows needing discrete named events |
| Form field changes, toggles, single fetches | Paginated lists, lifecycle management, multi-step flows |
| Example: `LoginCubit`, `SplashCubit`, `SignupCubit` | Example: `CallLogsBloc`, `NotificationsBloc` |

### State Pattern (Cubit)

All states:
1. Extend `Equatable`
2. Have only `const` or immutable fields
3. Provide a `copyWith()` method
4. Override `get props` listing all fields
5. Are declared as a `part` of the cubit file

```dart
part of 'example_cubit.dart';

class ExampleState extends Equatable {
  const ExampleState({
    this.status = DataFetchStatus.idle,
    this.data,
    this.errorMessage,
  });

  final DataFetchStatus status;
  final ExampleModel? data;
  final String? errorMessage;

  ExampleState copyWith({
    DataFetchStatus? status,
    ExampleModel? data,
    String? errorMessage,
  }) {
    return ExampleState(
      status: status ?? this.status,
      data: data ?? this.data,
      errorMessage: errorMessage ?? this.errorMessage,
    );
  }

  @override
  List<Object?> get props => [status, data, errorMessage];
}
```

### Cubit Pattern

```dart
part 'example_state.dart';

class ExampleCubit extends Cubit<ExampleState> {
  ExampleCubit() : super(const ExampleState());

  final _repo = serviceLocator<ExampleRepository>();

  Future<void> fetchData() async {
    emit(state.copyWith(status: DataFetchStatus.waiting));
    try {
      final result = await _repo.getData();
      emit(state.copyWith(status: DataFetchStatus.success, data: result));
    } catch (e) {
      emit(state.copyWith(
        status: DataFetchStatus.failed,
        errorMessage: e is ApiException ? e.message : 'Something went wrong',
      ));
    }
  }
}
```

### DataFetchStatus Enum

Located at `lib/shared/domain/constants/data_fetching_status.dart`:

```dart
enum DataFetchStatus {
  waiting,
  success,
  failed,
  idle,
  refreshCompleted,
}
```

Use `idle` as the initial value. Transition: `idle → waiting → success | failed`.

### Bloc Pattern (Event-Driven)

Events and states live as separate files:

```dart
// {name}_event.dart
abstract class ExampleEvent extends Equatable {
  const ExampleEvent();
}

class LoadExample extends ExampleEvent {
  const LoadExample(this.id);
  final String id;

  @override
  List<Object?> get props => [id];
}

// {name}_bloc.dart
class ExampleBloc extends Bloc<ExampleEvent, ExampleState> {
  ExampleBloc() : super(const ExampleState()) {
    on<LoadExample>(_onLoadExample);
  }

  Future<void> _onLoadExample(LoadExample event, Emitter<ExampleState> emit) async {
    emit(state.copyWith(status: DataFetchStatus.waiting));
    try {
      final data = await serviceLocator<ExampleRepository>().getItem(id: event.id);
      emit(state.copyWith(status: DataFetchStatus.success, data: data));
    } catch (e) {
      emit(state.copyWith(
        status: DataFetchStatus.failed,
        errorMessage: e is ApiException ? e.message : 'Something went wrong',
      ));
    }
  }
}
```

### BlocProvider Registration

Cubits/Blocs that must survive the entire app lifetime are registered in `app.dart` inside `MultiBlocProvider`:

```dart
MultiBlocProvider(
  providers: [
    BlocProvider(create: (context) => LoginCubit()),
    BlocProvider(create: (context) => SplashCubit()),
    // ...
  ],
  child: MaterialApp(...),
)
```

Feature-specific Cubits/Blocs are provided at the screen level or in the app shell's `MultiBlocProvider`.

### State Consumption in UI

```dart
// Rebuild on state change
BlocBuilder<ExampleCubit, ExampleState>(
  builder: (context, state) {
    if (state.status == DataFetchStatus.waiting) {
      return const CircularProgressIndicator();
    }
    // ...
  },
)

// Side-effects (navigation, snackbars)
BlocListener<ExampleCubit, ExampleState>(
  listener: (context, state) {
    if (state.status == DataFetchStatus.success) {
      Navigator.of(context).pop();
    }
  },
)

// Both together
BlocConsumer<ExampleCubit, ExampleState>(
  listener: (context, state) { ... },
  builder: (context, state) { ... },
)
```

---

## 6. Dependency Injection — get_it

### Global Accessor

```dart
// lib/shared/domain/constants/global_variables.dart
final serviceLocator = GetIt.instance;
```

Import and use anywhere:
```dart
import 'package:{app_name}/shared/domain/constants/global_variables.dart';

final repo = serviceLocator<MyRepository>();
```

### Registration in `setup.dart`

All registrations happen in `lib/shared/utils/service_locator_setup/setup.dart` inside `setUpServiceLocator()`, called once at app boot.

Two registration patterns:

| Pattern | When to use |
|---------|-------------|
| `registerSingleton<T>(impl)` | Always-alive services needed immediately at boot (local storage, `HttpService`) |
| `registerLazySingleton<T>(impl.new)` | Services needed only when first accessed |

**Register an abstract type against its implementation:**
```dart
serviceLocator.registerLazySingleton<MyRepository>(MyService.new);
```

**Named instances** are supported by `get_it` but are not currently used —
routing is declarative (§11), so there are no registered navigator keys. If you
need one, register it with an `instanceName` and retrieve it the same way.

### Adding a New Service Registration

1. Create `{Name}Repository` abstract class in `features/{name}/domain/repository/`
2. Create `{Name}Service implements {Name}Repository` in `features/{name}/domain/services/`
3. In `setup.dart`, chain:
```dart
..registerLazySingleton<{Name}Repository>({Name}Service.new)
```

---

## 7. Network / API Layer

### Stack

```
Cubit / Screen
   ↓
Repository (abstract) → Service (concrete)
   ↓
HttpService            ← the single API entry point: injects the auth token,
   ↓                     tenant/host headers and active branch_id, and unwraps
Dio HTTP client          the {success,data,message} envelope
   ↓
Laravel /api/v1
```

> **Changed 2026-08-07.** An earlier `HttpHelper.getDataFromServer()` facade and
> its `ResponseData` wrapper were removed: no service ever used them, and they
> flattened `ApiException` (losing `fieldErrors` on a 422). `HttpService` is the
> entry point. Services hold
> `HttpService get _http => serviceLocator<HttpService>();`.

### EndPoints

`lib/shared/api/end_points.dart` defines every endpoint as a `static final String` on the `EndPoints` class. Base URLs are resolved per flavor:

```dart
final String _baseUrl = F.isDev
  ? 'https://your-dev-api.com'
  : 'https://your-prod-api.com';

String endpointV1 = '$_baseUrl/api/v1';
String endpointV2 = '$_baseUrl/api/v2';
String endpointV3 = '$_baseUrl/api/v3';
```

Endpoints:
```dart
class EndPoints {
  static final String login  = '$endpointV2/login';
  static final String getUser = '$endpointV3/get-user';
  // ...
}
```

### Calling an endpoint

`lib/shared/utils/router/http_utils/http_service.dart` exposes
`get` / `post` / `put` / `delete`, plus `getBytes` (PDFs) and `postFileBytes`
(multipart upload). Each returns the already-unwrapped `data` payload, or throws
`ApiException`:

```dart
class ExampleService implements ExampleRepository {
  HttpService get _http => serviceLocator<HttpService>();

  @override
  Future<List<ExampleModel>> list({int page = 1}) async {
    final data = await _http.get(EndPoints.examples, query: {'page': page});
    return (data as List<dynamic>? ?? [])
        .map((e) => ExampleModel.fromJson(Map<String, dynamic>.from(e)))
        .toList();
  }
}
```

Never inline a path string — every path lives on `EndPoints`.

### HttpService

`lib/shared/utils/router/http_utils/http_service.dart`

Wraps Dio. Handles:
- Injecting Bearer token from the stored auth token
- Silent token refresh when the access token is near expiry
- Broadcasting `UserTokenState` (active / expired / refreshFailed) on a stream

### Unauthorized handling

`HttpService.onUnauthorized` is a callback, not a stream. `AuthCubit` sets it at
boot (`_http.onUnauthorized = _forceSignOut`), so a 401 from any endpoint drops
the session and the router's redirect sends the user to `/login`.

---

## 8. Data Models — hand-written, defensively

Models write `fromJson` / `toJson` **by hand**, and should continue to.

> **Reversed 2026-08-07.** This section previously recommended
> `json_serializable`. It is the wrong tool here: the generator emits strict
> `as`-cast parsers, and this API returns numbers as strings in several places —
> exactly what the `asStr` / `asNum` coercion helpers absorb. Migrating 29
> working parsers would trade a maintained convention for new crash surfaces and
> add `build_runner` to every checkout. There is no `json_serializable` or
> `build_runner` dependency in `pubspec.yaml`.

### The pattern

```dart
class ExampleModel extends Equatable {
  const ExampleModel({required this.id, required this.name, this.total = 0});

  factory ExampleModel.fromJson(Map<String, dynamic> j) => ExampleModel(
        id: asStr(j['id']),                 // never `j['id'] as String`
        name: asStr(j['name']),
        total: asNum(j['total']).toDouble(),
      );

  final String id;
  final String name;
  final double total;

  Map<String, dynamic> toJson() => {'id': id, 'name': name, 'total': total};

  ExampleModel copyWith({String? name, double? total}) =>
      ExampleModel(id: id, name: name ?? this.name, total: total ?? this.total);

  @override
  List<Object?> get props => [id, name, total];
}
```

Rules:

- **Coerce, never cast.** `asStr` / `asNum` from
  `shared/domain/helpers/formatters.dart` handle nulls and string-encoded
  numbers. A bare `as String` on API data is a crash waiting for a backend tweak.
- **Nested lists:**
  `(j['items'] as List<dynamic>? ?? []).map((e) => X.fromJson(Map<String, dynamic>.from(e))).toList()`
- **Fields `final`, plus `Equatable` + `props`** so models compare by value.
  `CartLine` is the reference implementation; most models in
  `shared/domain/models/models.dart` still lack `props` and compare by identity
  — tracked in the audit.
- **Money:** round at the same points the server's `decimal(16,2)` columns do,
  using `round2()`. See §8.1.

### 8.1 Money

Every money column in the Laravel schema is `decimal(16,2)`, and the sale totals
are *generated* columns — so MySQL rounds each intermediate before deriving the
next:

```
sale_items.gross_amount = unit_price * quantity
sale_items.net_amount   = gross_amount - discount
sale_items.tax_amount   = (net_amount * tax) / 100
sale_items.total        = net_amount + tax_amount
sales.total             = SUM(gross_amount) - item_discount + SUM(tax_amount)
sales.grand_total       = (total - other_discount + freight) + round_off
```

Dart `double` is binary, so summing unrounded values drifts from that by a cent
on percentage discounts and tax — enough for a settled ticket to show a phantom
balance. Every money getter therefore wraps its result in `round2()`, at the
same points, including the aggregates (adding two 2dp doubles is not 2dp).
`CartCubit` and `ReturnDraftCubit` are the worked examples, and
`test/cart_totals_test.dart` locks the behaviour down.

### 8.2 Currency formatting

`Money.of(v)` / `Money.compact(v)` / `Money.plain(v)` from
`shared/domain/helpers/formatters.dart`. Never hardcode a currency symbol — the
active one is user-configurable and applies app-wide.

`Money.symbol` and `Money.decimals` are deliberately **mutable statics**, set
once by `CurrencyCubit` from the cached web settings. This is an accepted
trade-off, not an oversight: threading a currency object through 315+ call
sites would be a large change for no user-visible gain. The cost is that
`app.dart` watches `CurrencyCubit` at the root so a currency change re-renders
the app, and that currency-dependent tests must set the value they expect.

---

## 9. Local Storage Layer

Two storage backends are used together:

| Backend | Class | Use case |
|---------|-------|----------|
| `flutter_secure_storage` | `LocalStorageService` | Sensitive: auth tokens, user data, profile |
| `shared_preferences` | Accessed directly via DI | Non-sensitive flags: first-run, tour shown, feature intro flags |

### LocalStorageService

`lib/shared/utils/local_storage/local_storage_service.dart`

```dart
// Read
final token = await serviceLocator<LocalStorageService>()
    .getFromLocal(LocalStorageKeys.token);

// Write
await serviceLocator<LocalStorageService>()
    .saveToLocal(value, LocalStorageKeys.token);

// Delete
await serviceLocator<LocalStorageService>()
    .removeFromLocal(LocalStorageKeys.token);
```

### Key Constants

All storage keys are string constants on the `LocalStorageKeys` class:
`lib/shared/utils/local_storage/keys.dart`

```dart
class LocalStorageKeys {
  static const String token         = 'token';
  static const String loginResponse = 'login_response';
  static const String userData      = 'user_data';
  // ...
}
```

---

## 10. Data Flow — End to End

### User Action → State → UI

```
User taps button in Widget
      ↓
Widget calls context.read<ExampleCubit>().doSomething()
      ↓
Cubit emits state.copyWith(status: DataFetchStatus.waiting)
      ↓
BlocBuilder rebuilds → shows loading indicator
      ↓
Cubit calls serviceLocator<ExampleRepository>().apiMethod()
      ↓
Service calls _http.get(EndPoints.example, query: {...})
      ↓
HttpService sends Dio request with Bearer token
      ↓
Response arrives → envelope unwrapped by HttpService
      ↓
Service returns typed Model
      ↓
Cubit emits state.copyWith(status: DataFetchStatus.success, data: model)
      ↓
BlocBuilder/BlocListener rebuilds with new state
```

### Error Path

```
Service throws ApiException(message, statusCode)
      ↓
Cubit catches in try/catch
      ↓
Cubit emits state.copyWith(status: DataFetchStatus.failed, errorMessage: e.message)
      ↓
UI shows error snackbar or inline message
```

---

## 11. Routing and Navigation

The app uses **`go_router`**, declaratively, configured in
`lib/shared/utils/router/app_router.dart`.

> **Corrected 2026-08-07.** This section previously said the project does *not*
> use `go_router` and navigates imperatively with `Navigator` +
> `GlobalKey<NavigatorState>`. That was never true here. The unused
> `NavigationKeys` constants have been deleted.

### Route constants

Every path is a constant on `Routes`
(`lib/shared/utils/router/routes.dart`). The router declares its `GoRoute`s from
them and every call site navigates by constant, so renaming a path is one edit
the compiler checks:

```dart
context.push(Routes.saleReturnPick);
context.go(Routes.homeTab(kReturnsTab));
```

Never write a raw path string in a `context.go` / `context.push` call.

### Auth and permission gating

The top-level `redirect` is the single gate — screens do not check permissions
for routing. It sends signed-out users to `/login`, routes users without
`salesOverview` away from the admin home, and gates the sale-return, day-session
and stock-check routes on their `PermissionSlug`. `GoRouterRefreshStream`
re-evaluates it whenever `AuthCubit` emits.

### Passing objects between routes

`state.extra` is `Object?`, is **not** preserved across a platform restore, and
is absent on a deep link. Every route that carries a model therefore guards the
cast in its own `redirect` and falls back to its list:

```dart
GoRoute(
  path: Routes.invoice,
  redirect: (_, state) => state.extra is Sale ? null : Routes.sales,
  builder: (_, state) => InvoiceScreen(sale: state.extra as Sale),
),
```

A bare `state.extra as Sale` is a crash with no way back — do not add one.

### App flow

```
/login  ──(AuthCubit.status == signedIn)──▶  /home  (admin)  or  /sale  (cashier)

HomeShell
  └── phone: AstraBottomNav · tablet: AstraSideRail (TabletRailScaffold)
       └── one destination per feature
```

---

## 12. Flavor / Environment Setup

### Flavor Enum

`lib/flavors.dart`:

```dart
enum Flavor { dev, prod }

class F {
  static Flavor? appFlavor;
  static bool get isDev => appFlavor == Flavor.dev;
  static String get name => appFlavor?.name ?? '';
  static String get title => appFlavor == Flavor.dev ? 'App Dev' : 'App';
}
```

### Entry Points

| File | Purpose |
|------|---------|
| `lib/main_dev.dart` | Sets `F.appFlavor = Flavor.dev` |
| `lib/main_prod.dart` | Sets `F.appFlavor = Flavor.prod` |

Both call `await runner.main()` (`lib/main.dart`). A plain `flutter run` lands in `main.dart` directly and defaults to the dev flavor.

### Using Flavor in Code

```dart
// Resolve URLs per environment
final String _baseUrl = F.isDev
  ? 'https://dev-api.example.com'
  : 'https://api.example.com';

// Gate dev-only logic
if (F.isDev) {
  // dev-only path
}
```

### Dev Banner

`App.build()` renders a `Banner` widget over the UI when `F.isDev` is true. No manual code required — it's automatic.

### Build Commands

```bash
# Run dev
flutter run -t lib/main_dev.dart --flavor dev

# Run prod
flutter run -t lib/main_prod.dart --flavor prod

# Build APK (dev)
flutter build apk -t lib/main_dev.dart --flavor dev --release
```

---

## 13. Theme and Styling System

> **Corrected 2026-08-07.** This section previously described a single light
> `ThemeData`, a `ColorManager.primary` palette and `getRegularStyle()`-style
> helpers over Poppins. None of that matches the app.

### Palette — `context.astra`

`lib/shared/utils/components/theme/palette.dart` +
`theme_manager.dart`. The app ships **5 presets × light/dark**, chosen by the
user in Settings → Appearance and applied by `buildAstraTheme(palette, typeface)`
in `app.dart`. Widgets read the active palette off the context:

```dart
final p = context.astra;
Container(color: p.card, child: Text('…', style: ui(size: 13, color: p.ink)));
```

Common tokens: `canvas`, `card`, `cardSolid`, `sheet`, `ink`, `textSecondary`,
`textMuted`, `hairline`, `primary`, `accent`, `primaryGradient`, `heroGradient`.

`ColorManager` holds only the few preset-independent constants (`success`,
`danger`, black/white/transparent). Never write `Color(0xFF…)` or
`Colors.grey[600]` in a feature file — a hardcoded grey breaks dark mode.

### Typography — `ui()` and `serif()`

`styles_manager.dart`, backed by `AstraTypefaces` (`typeface.dart`) — 5
user-selectable pairings, device-local, kept in sync by `ThemeCubit`:

```dart
ui(size: 13, weight: FontWeight.w700, color: p.ink)   // rows, labels, buttons
serif(size: 22, color: p.ink)                          // titles, prices, KPIs
```

Never construct a `TextStyle` directly — it will not follow the Typography
setting.

### Responsive layout

`lib/shared/domain/helpers/responsive.dart`:

```dart
context.isTablet   // shortestSide >= 600, so rotation never reclassifies a phone
context.isWide     // tablet AND width >= 1200
MaxWidthBox(...)   // caps phone-shaped content at 560 on large screens
```

Tablet layouts are gated on `context.isTablet`; the phone UI must not change.
`app.dart` also clamps the OS text scale to 0.85–1.3 so an accessibility setting
cannot overflow fixed-height rows.

**Sizing is raw literals** — `ui(size: 13)`, `SizedBox(height: 14)`,
`EdgeInsets.fromLTRB(20, 14, 20, 24)` — combined with the breakpoint helpers
above and the text-scale clamp.

> **Removed 2026-08-07.** `flutter_screenutil`, its `ScreenUtilInit` wrapper and
> `size_manager.dart`'s `KFontSize` / `KRadius` / `KPadding` / `KHeight` /
> `KWidth` tokens are gone. The framework was initialised app-wide but read by
> no widget, and the breakpoint approach is the one the app actually uses.
> `ColorManager` went the same way — `context.astra` and `AstraPalette` cover
> everything it held.

---

## 14. Asset Organization

Assets are declared in `pubspec.yaml` and referenced only through static constant classes — never by raw path strings.

### Asset Manager Classes

`lib/shared/utils/components/assets_manager.dart`:

```dart
class ImageAssets {
  static const String logo     = 'assets/images/v3/logo.svg';
  static const String noData   = 'assets/icons/v3/no_data.svg';
  // ...
}

class GifAssets {
  static const String intro = 'assets/gifs/v3/intro.gif';
}

class LottieAssets { ... }
class AudioAssets  { ... }
```

### Asset Directory Convention

```
assets/
├── fonts/
├── gifs/
│   └── v3/         ← current design generation
├── icons/
│   └── v3/
└── images/
    └── v3/
```

The `v3/` subdirectory marks assets belonging to the current design generation. Legacy assets remain directly under the parent folder.

---

## 15. Error Handling Patterns

### ApiException

`lib/shared/utils/router/http_utils/common_exception.dart`

```dart
class ApiException implements Exception {
  ApiException(this.message, this.statusCode);
  final String message;
  final int statusCode;
}
```

Services throw `ApiException` when `response.success` is false. Cubits/Blocs catch it and emit a `failed` state.

### Feature-Specific Exceptions

Features that need richer error data (e.g. field-level validation) extend `ApiException`:

```dart
class FeatureException extends ApiException {
  FeatureException(super.message, super.statusCode, {this.fieldErrors});
  final Map<String, String>? fieldErrors;
}
```

Catch the specific exception first, then the base:

```dart
} on FeatureException catch (e) {
  emit(state.copyWith(
    status: DataFetchStatus.failed,
    errorMessage: e.message,
    fieldErrors: e.fieldErrors ?? {},
  ));
} on ApiException catch (e) {
  emit(state.copyWith(
    status: DataFetchStatus.failed,
    errorMessage: e.message,
  ));
} catch (_) {
  emit(state.copyWith(
    status: DataFetchStatus.failed,
    errorMessage: AppStrings.somethingWentWrongPleaseTryAgainLater,
  ));
}
```

### Crash reporting

**There is none.** `lib/main.dart` installs neither `FlutterError.onError` nor
`PlatformDispatcher.instance.onError`, and there is no Firebase dependency, so
an uncaught error on a shop-floor device leaves no trace. Known gap — see the
audit; it needs a destination (a Laravel endpoint alongside the existing API
logging is the lighter option) before handlers are worth adding.

### Silent catches

`catch (_) {}` with an empty body swallows `TypeError` and `NoSuchMethodError`
too, so programming bugs disappear. Narrow to the expected type, or leave a
one-line comment naming what is being ignored and why.

### Token Expiry

`HttpService` silently refreshes tokens. If refresh fails, it broadcasts `UserTokenState.expired` on the `tokenState` stream and the app shell redirects to login.

---

## 16. Shared Components

### `lib/shared/`

```
shared/
├── api/
│   └── end_points.dart             → Every /api/v1 path, one place
├── domain/
│   ├── constants/
│   │   ├── app_config.dart         → Base URL + tenant resolution
│   │   ├── data_fetching_status.dart  → (defined; unused until §5 migration)
│   │   ├── global_variables.dart   → serviceLocator instance
│   │   └── mobile_permissions.dart → PermissionSlug constants
│   ├── helpers/
│   │   ├── formatters.dart         → Money, Dates, qtyLabel, asStr/asNum, round2
│   │   ├── icons.dart
│   │   └── responsive.dart         → Breakpoints, context.isTablet, MaxWidthBox
│   ├── models/                     → models.dart (29 classes), currency, print_settings
│   ├── repository/lookup_repository.dart
│   └── services/lookup_service.dart
├── logic/
│   ├── base/holder_cubit.dart
│   ├── branch_cubit/  currency_cubit/  haptics_cubit/  theme_cubit/
├── utils/
│   ├── camera_permission.dart
│   ├── components/
│   │   ├── app_strings.dart        → User-facing copy
│   │   ├── haptics.dart            → HapticTapDetector
│   │   ├── size_manager.dart       → K* tokens (defined; currently unused)
│   │   └── theme/
│   │       ├── color_manager.dart  → Preset-independent constants only
│   │       ├── palette.dart        → AstraPalette, the 5 presets
│   │       ├── styles_manager.dart → ui() / serif()
│   │       ├── theme_manager.dart  → buildAstraTheme, context.astra
│   │       └── typeface.dart       → AstraTypefaces
│   ├── local_storage/{keys,local_storage_service}.dart
│   ├── router/
│   │   ├── app_router.dart         → createRouter + permission redirects
│   │   ├── routes.dart             → Route path constants
│   │   ├── go_router_refresh_stream.dart
│   │   └── http_utils/
│   │       ├── common_exception.dart  → ApiException
│   │       ├── http_service.dart      → The API entry point
│   │       └── dev_http_{io,stub}.dart
│   └── service_locator_setup/setup.dart
└── widgets/                        → astra_widgets, astra_drawer, astra_bottom_nav,
                                       astra_side_rail, tablet_widgets, charts,
                                       receipt_pdf, receipt_printer, qty_input_sheet,
                                       continuous_scanner_screen, invo_logo
```

> `shared/widgets/` has no `index.dart` and no `v3/` subfolder, unlike the
> feature layers — an inconsistency, not a rule.

### Shared Widgets (Commonly Used)

| Widget | Location | Purpose |
|--------|----------|---------|
| `CustomButton` | `shared/widgets/custom_button.dart` | Primary action button |
| `CommonLoadingWidget` | `shared/widgets/common_loading_widget.dart` | Loading indicator |
| `NoDataFoundWidget` | `shared/widgets/no_data_found_widget.dart` | Empty state |
| `CommonShimmer` | `shared/widgets/common_shimmer.dart` | Skeleton loading |
| `CommonPinput` | `shared/widgets/common_pinput.dart` | OTP / PIN input |
| `CommonAppbarV3` | `shared/widgets/v3/common_appbar_v3.dart` | App bar |

### App Strings

`lib/shared/utils/components/app_strings.dart` — all user-facing copy as `static const String`. Never hardcode English strings in widget or cubit files.

---

## 17. Export / Index File Convention

Every layer subdirectory has an `index.dart` that re-exports all public files in that directory.

```
features/{name}/
├── domain/
│   ├── models/
│   │   ├── index.dart          → export '{name}_model.dart';
│   │   └── {name}_model.dart
│   ├── repository/
│   │   ├── index.dart          → export '{name}_repository.dart';
│   │   └── {name}_repository.dart
│   ├── services/
│   │   ├── index.dart          → export '{name}_service.dart';
│   │   └── {name}_service.dart
│   └── index.dart              → export 'models/index.dart';
│                                  export 'repository/index.dart';
│                                  export 'services/index.dart';
├── logic/
│   ├── {name}_cubit/
│   │   ├── {name}_cubit.dart
│   │   └── {name}_state.dart   (part of — NOT separately exported)
│   └── index.dart              → export '{name}_cubit/{name}_cubit.dart';
├── screens/
│   ├── v3/
│   │   └── {name}_screen.dart
│   └── index.dart              → export 'v3/{name}_screen.dart';
├── widgets/
│   └── index.dart
└── index.dart                  → export 'domain/index.dart';
                                   export 'logic/index.dart';
                                   export 'screens/index.dart';
                                   export 'widgets/index.dart';
```

**Decided 2026-08-07 — barrels are a convenience, not a rule.** All 132
`package:invo/features/...` imports are direct paths to the file they need, and
that is fine: a deep import states its dependency precisely, and importing a
feature's top-level `index.dart` would pull that feature's whole surface
(including its screens) into the importer for one model. Barrels exist and are
kept complete so a folder *can* be re-exported, but there is no requirement to
import through them, and no cleanup task to convert existing imports.

```dart
// Preferred — says exactly what is used.
import 'package:invo/features/sale/logic/cart_cubit/cart_cubit.dart';
```

What *does* still apply is §3: a cross-feature import should be rare and
deliberate. If two features need the same type, it belongs in `lib/shared/`.

Cubit state files declared as `part` of the cubit are **not** exported — they are only accessible through the cubit file.

---

## 18. Key Integrations

> **Rewritten 2026-08-07.** This section previously documented Firebase
> Analytics, Crashlytics, Messaging and Remote Config. **There is no Firebase
> dependency in this app** and none of those integrations exist.

| Concern | How it works |
|---|---|
| Barcode scanning | `mobile_scanner` behind the shared `ContinuousScannerScreen` — permission-first via `permission_handler`, with a serialized camera op-queue. Never let `mobile_scanner` request permission itself. |
| Receipt printing | On-device via `printing` + `pdf` (`buildReceiptPdf`) — instant and offline. Arabic shapes correctly **only** when IBM Plex Sans Arabic is the *base* font. Options come from the web Sale Configuration (`/settings/sale`); only paper width is device-local. |
| Biometrics | `local_auth` for Touch ID / Face ID / fingerprint sign-in. |
| Secure storage | `flutter_secure_storage` for the token and user; `shared_preferences` for device-local flags. |
| Haptics | App-wide via `HapticTapDetector` in `app.dart`'s builder — do not add per-tap `selectionClick()`. |
| Photos | `image_picker` + `crop_your_image` for the profile avatar. |

**Crash reporting: none.** `main()` installs neither `FlutterError.onError` nor
`PlatformDispatcher.instance.onError`, so shop-floor crashes are invisible. This
is a known gap, not a decision — see the audit.

---

## 19. Naming Conventions

### Files

| Type | Convention | Example |
|------|-----------|---------|
| Repository (abstract) | `{name}_repository.dart` | `login_repository.dart` |
| Service (concrete) | `{name}_service.dart` | `login_service.dart` |
| Model | `{name}_model.dart` | `login_model.dart` |
| Cubit | `{name}_cubit.dart` | `login_cubit.dart` |
| Cubit state | `{name}_state.dart` | `login_state.dart` |
| Bloc | `{name}_bloc.dart` | `notifications_bloc.dart` |
| Bloc event | `{name}_event.dart` | `notifications_event.dart` |
| Bloc state | `{name}_state.dart` | `notifications_state.dart` |
| Screen | `{name}_screen.dart` | `login_screen.dart` |
| Widget | `{name}_widget.dart` or descriptive | `social_login_button.dart` |
| Generated serialization | `{name}_model.g.dart` | `login_model.g.dart` |

All files use `snake_case`.

### Classes

| Type | Convention | Example |
|------|-----------|---------|
| Repository | `{Name}Repository` | `LoginRepository` |
| Service | `{Name}Service` | `LoginService` |
| Response model | `{Name}ResponseModel` | `LoginResponseModel` |
| Request / data model | `{Name}Model` | `SignupModel` |
| Cubit | `{Name}Cubit` | `LoginCubit` |
| Cubit state | `{Name}State` | `LoginState` |
| Bloc | `{Name}Bloc` | `NotificationsBloc` |
| Bloc event base | `{Name}Event` | `NotificationsEvent` |
| Bloc state | `{Name}State` | `NotificationsState` |
| Screen | `{Name}Screen` | `LoginScreen` |

### Variables and Methods

All `camelCase`. Async data-fetch methods use the `fetch` prefix: `fetchUserList()`, `fetchAppVersion()`.

### Enums

`PascalCase` for type, `camelCase` for values:
```dart
enum DataFetchStatus { waiting, success, failed, idle }
```

---

## 20. Rules and Conventions

### State Mutation — NEVER mutate state directly

```dart
// ❌ Wrong
state.someValue = newValue;

// ✅ Correct
emit(state.copyWith(someValue: newValue));
```

### DI Access — Always through the service locator

```dart
// ❌ Wrong
final service = LoginService();

// ✅ Correct
final service = serviceLocator<LoginRepository>();
```

### Repository vs Service in Cubits/Blocs

Cubits/Blocs hold a reference to the **repository** (abstract type), never the concrete service class. The DI container resolves the concrete implementation.

### Flavor-Gated Code

```dart
if (F.isDev) {
  // dev-only
}
```

Never use `kDebugMode` for environment-specific logic. Always use `F.isDev`.

### HTTP Calls

- All HTTP calls go through `HttpService` (resolved from `serviceLocator`)
- Never use `Dio` directly inside feature services
- Always check `response.success` before parsing
- Throw `ApiException` on non-success responses

### Error Messages

- **Service:** `throw ApiException(response.message, response.responseCode)`
- **Cubit:** catch and `emit` a `failed` state with `errorMessage`
- **UI:** read `state.errorMessage`, show a snackbar or inline widget

### Strings

- All user-facing strings in `AppStrings`
- No hardcoded English strings in widget or cubit files

### Assets

- All paths in `ImageAssets`, `GifAssets`, `LottieAssets`, `AudioAssets`
- No raw path strings in widget files

### Dimensions

- All sizes via `KFontSize`, `KRadius`, `KPadding`, `KHeight`, `KWidth`
- Never use raw literals like `16.0` — always `KFontSize.f16`, `KRadius.r16`, etc.

### Colors

- All colors via `ColorManager`
- Never use `Color(0xFF...)` inline in widget files

### Index Files

- Every `domain/`, `logic/`, `screens/`, `widgets/` folder must have an `index.dart`
- The feature root `{name}/index.dart` re-exports all four layers
- Import features via their top-level `index.dart`

### Design Generation Convention

New screens and widgets go under `v3/`. Older design-revision files at earlier paths are kept but not extended.

### Serialization

Hand-written `fromJson` / `toJson` with `asStr` / `asNum` coercion — never a bare
`as` on API data, and never `json_serializable` (§8). Give new models `final`
fields plus `Equatable` `props`.

### Money

Round with `round2()` at the same points the server's `decimal(16,2)` columns
do, aggregates included (§8.1).

---

## 21. How to Add a New Feature

Use an existing feature (`login`, `signup`, `splash`) as a reference.

### Step 1 — Create the directory structure

```
lib/features/{name}/
├── domain/
│   ├── models/
│   │   └── index.dart
│   ├── repository/
│   │   └── index.dart
│   ├── services/
│   │   └── index.dart
│   └── index.dart
├── logic/
│   └── index.dart
├── screens/
│   ├── v3/
│   └── index.dart
├── widgets/
│   └── index.dart
└── index.dart
```

### Step 2 — Define the model

`features/{name}/domain/models/{name}_model.dart`:

```dart
import 'package:equatable/equatable.dart';
import 'package:invo/shared/domain/helpers/formatters.dart';

class ExampleModel extends Equatable {
  const ExampleModel({required this.id, this.title = ''});

  factory ExampleModel.fromJson(Map<String, dynamic> j) => ExampleModel(
        id: asStr(j['id']),
        title: asStr(j['title']),
      );

  final String id;
  final String title;

  Map<String, dynamic> toJson() => {'id': id, 'title': title};

  @override
  List<Object?> get props => [id, title];
}
```

### Step 3 — Define the repository

`features/{name}/domain/repository/{name}_repository.dart`:

```dart
abstract class ExampleRepository {
  Future<ExampleModel> getItem({required String id});
}
```

### Step 4 — Implement the service

`features/{name}/domain/services/{name}_service.dart`:

```dart
import 'package:invo/features/{name}/domain/index.dart';
import 'package:invo/shared/api/end_points.dart';
import 'package:invo/shared/domain/constants/global_variables.dart';
import 'package:invo/shared/utils/router/http_utils/http_service.dart';

class ExampleService implements ExampleRepository {
  HttpService get _http => serviceLocator<HttpService>();

  @override
  Future<ExampleModel> getItem({required String id}) async {
    final data = await _http.get(EndPoints.exampleById(id));
    return ExampleModel.fromJson(Map<String, dynamic>.from(data as Map));
  }
}
```

`HttpService` unwraps the envelope and throws `ApiException` on failure — the
cubit catches it (§5).

### Step 5 — Register in DI

`lib/shared/utils/service_locator_setup/setup.dart`:

```dart
..registerLazySingleton<ExampleRepository>(ExampleService.new)
```

### Step 6 — Create Cubit and State

`features/{name}/logic/{name}_cubit/{name}_state.dart`:

```dart
part of '{name}_cubit.dart';

class ExampleState extends Equatable {
  const ExampleState({
    this.status = DataFetchStatus.idle,
    this.data,
    this.errorMessage,
  });

  final DataFetchStatus status;
  final ExampleModel? data;
  final String? errorMessage;

  ExampleState copyWith({
    DataFetchStatus? status,
    ExampleModel? data,
    String? errorMessage,
  }) {
    return ExampleState(
      status: status ?? this.status,
      data: data ?? this.data,
      errorMessage: errorMessage ?? this.errorMessage,
    );
  }

  @override
  List<Object?> get props => [status, data, errorMessage];
}
```

`features/{name}/logic/{name}_cubit/{name}_cubit.dart`:

```dart
import 'package:bloc/bloc.dart';
import 'package:{app_name}/features/{name}/domain/index.dart';
import 'package:{app_name}/shared/domain/constants/data_fetching_status.dart';
import 'package:{app_name}/shared/domain/constants/global_variables.dart';
import 'package:{app_name}/shared/utils/router/http%20utils/common_exception.dart';
import 'package:equatable/equatable.dart';

part '{name}_state.dart';

class ExampleCubit extends Cubit<ExampleState> {
  ExampleCubit() : super(const ExampleState());

  final _repo = serviceLocator<ExampleRepository>();

  Future<void> fetchItem({required String id}) async {
    emit(state.copyWith(status: DataFetchStatus.waiting));
    try {
      final result = await _repo.getItem(id: id);
      emit(state.copyWith(status: DataFetchStatus.success, data: result));
    } catch (e) {
      emit(state.copyWith(
        status: DataFetchStatus.failed,
        errorMessage: e is ApiException ? e.message : 'Something went wrong',
      ));
    }
  }
}
```

### Step 7 — Create the screen

`features/{name}/screens/v3/{name}_screen.dart`:

```dart
import 'package:{app_name}/features/{name}/index.dart';
import 'package:{app_name}/shared/domain/constants/data_fetching_status.dart';
import 'package:{app_name}/shared/utils/components/theme/color_manager.dart';
import 'package:flutter/material.dart';
import 'package:flutter_bloc/flutter_bloc.dart';

class ExampleScreen extends StatelessWidget {
  const ExampleScreen({super.key});

  @override
  Widget build(BuildContext context) {
    return BlocProvider(
      create: (_) => ExampleCubit()..fetchItem(id: 'someId'),
      child: const _ExampleView(),
    );
  }
}

class _ExampleView extends StatelessWidget {
  const _ExampleView();

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      backgroundColor: ColorManager.background,
      body: BlocConsumer<ExampleCubit, ExampleState>(
        listener: (context, state) {
          if (state.status == DataFetchStatus.failed) {
            ScaffoldMessenger.of(context).showSnackBar(
              SnackBar(content: Text(state.errorMessage ?? '')),
            );
          }
        },
        builder: (context, state) {
          if (state.status == DataFetchStatus.waiting) {
            return const Center(child: CircularProgressIndicator());
          }
          if (state.data == null) return const SizedBox.shrink();
          return Text(state.data!.title ?? '');
        },
      ),
    );
  }
}
```

### Step 8 — Add the endpoint

`lib/shared/api/end_points.dart`:

```dart
static final String exampleGet = '$endpointV3/example/get';
```

### Step 9 — Update all index files

Update each `index.dart` in the chain to export the new file.

---

## 22. Reusable Skeleton Templates

### Minimal Cubit (no API call)

```dart
part '{name}_state.dart';

class {Name}Cubit extends Cubit<{Name}State> {
  {Name}Cubit() : super(const {Name}State());

  void updateField(String value) {
    emit(state.copyWith(field: value));
  }
}
```

### Cubit — DI vs Constructor Injection

```dart
// Option A: serviceLocator (common pattern in this project)
class {Name}Cubit extends Cubit<{Name}State> {
  {Name}Cubit() : super(const {Name}State());
  final _repo = serviceLocator<{Name}Repository>();
}

// Option B: constructor injection (better for unit tests)
class {Name}Cubit extends Cubit<{Name}State> {
  {Name}Cubit(this._repo) : super(const {Name}State());
  final {Name}Repository _repo;
}
```

Both exist in the codebase. Prefer Option B when the cubit will be unit-tested in isolation.

### Bloc Skeleton

```dart
// {name}_event.dart
abstract class {Name}Event extends Equatable {
  const {Name}Event();
}

class Load{Name} extends {Name}Event {
  const Load{Name}();
  @override List<Object?> get props => [];
}

// {name}_bloc.dart
class {Name}Bloc extends Bloc<{Name}Event, {Name}State> {
  {Name}Bloc() : super(const {Name}State()) {
    on<Load{Name}>(_onLoad);
  }

  final _repo = serviceLocator<{Name}Repository>();

  Future<void> _onLoad(Load{Name} event, Emitter<{Name}State> emit) async {
    emit(state.copyWith(status: DataFetchStatus.waiting));
    try {
      final items = await _repo.getList();
      emit(state.copyWith(status: DataFetchStatus.success, items: items));
    } catch (e) {
      emit(state.copyWith(
        status: DataFetchStatus.failed,
        errorMessage: e is ApiException ? e.message : 'Something went wrong',
      ));
    }
  }
}
```

### State with List Data

```dart
class {Name}State extends Equatable {
  const {Name}State({
    this.status = DataFetchStatus.idle,
    this.items = const [],
    this.errorMessage,
  });

  final DataFetchStatus status;
  final List<{Name}Model> items;
  final String? errorMessage;

  {Name}State copyWith({
    DataFetchStatus? status,
    List<{Name}Model>? items,
    String? errorMessage,
  }) {
    return {Name}State(
      status: status ?? this.status,
      items: items ?? this.items,
      errorMessage: errorMessage ?? this.errorMessage,
    );
  }

  @override
  List<Object?> get props => [status, items, errorMessage];
}
```

### Model

```dart
import 'package:equatable/equatable.dart';
import 'package:invo/shared/domain/helpers/formatters.dart';

class {Name}Model extends Equatable {
  const {Name}Model({required this.id, this.name = '', this.child});

  factory {Name}Model.fromJson(Map<String, dynamic> j) => {Name}Model(
        id: asStr(j['id']),
        name: asStr(j['name']),
        child: j['child'] is Map
            ? {Child}Model.fromJson(Map<String, dynamic>.from(j['child'] as Map))
            : null,
      );

  final String id;
  final String name;
  final {Child}Model? child;

  Map<String, dynamic> toJson() =>
      {'id': id, 'name': name, 'child': child?.toJson()};

  @override
  List<Object?> get props => [id, name, child];
}
```

### Repository + Service Pair

```dart
// {name}_repository.dart
abstract class {Name}Repository {
  Future<{Name}ResponseModel> getItem({required String id});
  Future<void> createItem({required {Name}Model body});
}

// {name}_service.dart
class {Name}Service implements {Name}Repository {
  HttpService get _http => serviceLocator<HttpService>();

  @override
  Future<{Name}ResponseModel> getItem({required String id}) async {
    final data = await _http.get(EndPoints.{name}ById(id));
    return {Name}ResponseModel.fromJson(Map<String, dynamic>.from(data as Map));
  }

  @override
  Future<void> createItem({required {Name}Model body}) async {
    await _http.post(EndPoints.{name}, body: body.toJson());
  }
}
```

---

*End of Architecture Skeleton*
