# Project Architecture Skeleton

## Table of Contents

1. [Project Overview](#1-project-overview)
2. [Top-Level Directory Structure](#2-top-level-directory-structure)
3. [Feature Organization](#3-feature-organization)
4. [Three-Layer Feature Architecture](#4-three-layer-feature-architecture)
5. [State Management — BLoC / Cubit](#5-state-management--bloc--cubit)
6. [Dependency Injection — get_it](#6-dependency-injection--get_it)
7. [Network / API Layer](#7-network--api-layer)
8. [Data Models — Manual vs json_serializable](#8-data-models--manual-vs-json_serializable)
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

## 1. Project Overview

| Property | Value |
|----------|-------|
| State management | `flutter_bloc` (Cubits + Blocs) |
| DI framework | `get_it` |
| HTTP client | `dio` (wrapped in custom helpers) |
| Primary font | Poppins via `google_fonts` |
| Design base size | 393×865 (`flutter_screenutil`) |
| Flavors | `dev`, `prod` |

---

## 2. Top-Level Directory Structure

```
project_root/
├── android/                  # Android native project
├── ios/                      # iOS native project
├── assets/
│   ├── fonts/
│   ├── gifs/
│   │   └── v3/
│   ├── icons/
│   │   └── v3/
│   └── images/
│       └── v3/
├── config/
│   ├── dev/                  # Dev signing keys + Firebase config
│   └── prod/                 # Prod signing keys + Firebase config
├── doc/                      # Architecture docs
├── lib/
│   ├── app.dart              # Root widget, MultiBlocProvider, MaterialApp
│   ├── main.dart             # Boot sequence, DI setup, runApp
│   ├── main_dev.dart         # Flavor entry point (sets F.appFlavor = dev)
│   ├── main_prod.dart        # Flavor entry point (sets F.appFlavor = prod)
│   ├── flavors.dart          # Flavor enum + F helper class
│   ├── firebase_options_dev.dart
│   ├── firebase_options_prod.dart
│   ├── features/             # One directory per feature
│   └── shared/               # Cross-feature code
```

---

## 3. Feature Organization

Every product domain has its own directory under `lib/features/`. Each feature is fully self-contained — its domain layer, state management, screens, and widgets all live inside the same folder.

```
lib/features/
├── splash/
├── login/
├── signup/
├── main/         ← App shell (bottom nav + global providers)
├── dash_board/
├── contacts/
├── calls/
├── sms/
├── email/
├── settings/
├── profile/
└── ...           ← One folder per product domain
```

Features do not cross-import each other's internals. Shared logic and types live in `lib/shared/`.

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

**`services/`** — Concrete classes that `implement` the repository. These import `HttpHelper`, `EndPoints`, `serviceLocator`, etc.

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
| `registerSingleton<T>(impl)` | Always-alive services needed immediately at boot (auth, Firebase, local storage) |
| `registerLazySingleton<T>(impl.new)` | Services needed only when first accessed |

**Register an abstract type against its implementation:**
```dart
serviceLocator.registerLazySingleton<MyRepository>(MyService.new);
```

**Named instances** (for `GlobalKey`, `PageController` instances):
```dart
serviceLocator.registerSingleton<GlobalKey<NavigatorState>>(
  GlobalKey<NavigatorState>(),
  instanceName: NavigationKeys.mainScreen,
);
```

Retrieve a named instance:
```dart
serviceLocator<GlobalKey<NavigatorState>>(instanceName: NavigationKeys.mainScreen);
```

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
Cubit / Bloc
   ↓
Service (calls HttpHelper)
   ↓
HttpHelper.getDataFromServer()   ← unified API call entry point
   ↓
HttpService (Dio wrapper)         ← injects auth token, handles refresh
   ↓
Dio HTTP client
   ↓
REST API
```

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

### HttpHelper

`lib/shared/utils/router/http utils/http_helper.dart` — the single call site for all API requests:

```dart
final response = await HttpHelper.getDataFromServer(
  EndPoints.someEndpoint,
  requestType: RequestType.post,   // default is post
  data: {'key': value},
  authenticationRequired: true,    // default is true
);

if (!response.success) {
  throw ApiException(response.message, response.responseCode);
}

final model = MyModel.fromJson(response.responseBody);
```

`RequestType` values: `post`, `get`, `delete`, `put`, `patch`.

### HttpService

`lib/shared/utils/router/http utils/http_service.dart`

Wraps Dio. Handles:
- Injecting Bearer token from the stored auth token
- Silent token refresh when the access token is near expiry
- Broadcasting `UserTokenState` (active / expired / refreshFailed) on a stream

### ResponseData

```dart
class ResponseData {
  final bool success;
  final String message;
  final int responseCode;
  final Map<String, dynamic> responseBody;
}
```

### Token State Stream

When a token expires and refresh fails, the `tokenState` stream emits `UserTokenState.expired`. The app shell listens and navigates to login.

---

## 8. Data Models — Manual vs json_serializable

The project currently writes `fromJson` / `toJson` by hand. For new models, **`json_serializable`** is the recommended approach — it eliminates boilerplate and keeps serialization in sync with the class definition automatically.

### Current Manual Pattern

```dart
class ExampleModel with EquatableMixin {
  ExampleModel({this.id, this.name});

  ExampleModel.fromJson(Map<String, dynamic> json) {
    id   = json['id'];
    name = json['name'];
  }

  String? id;
  String? name;

  Map<String, dynamic> toJson() => {'id': id, 'name': name};

  @override
  List<Object?> get props => [id, name];
}
```

### Recommended: json_serializable

Add to `pubspec.yaml`:
```yaml
dependencies:
  json_annotation: ^4.9.0

dev_dependencies:
  build_runner: ^2.4.0
  json_serializable: ^6.8.0
```

Then write the model once and let the generator produce `fromJson` / `toJson`:

```dart
import 'package:equatable/equatable.dart';
import 'package:json_annotation/json_annotation.dart';

part 'example_model.g.dart';

@JsonSerializable()
class ExampleModel with EquatableMixin {
  const ExampleModel({this.id, this.name});

  factory ExampleModel.fromJson(Map<String, dynamic> json) =>
      _$ExampleModelFromJson(json);

  @JsonKey(name: 'id')
  final String? id;

  @JsonKey(name: 'name')
  final String? name;

  Map<String, dynamic> toJson() => _$ExampleModelToJson(this);

  @override
  List<Object?> get props => [id, name];
}
```

Generate the `.g.dart` file:
```bash
dart run build_runner build --delete-conflicting-outputs
```

Or watch during development:
```bash
dart run build_runner watch --delete-conflicting-outputs
```

### Useful json_annotation Options

| Annotation | Purpose |
|-----------|---------|
| `@JsonKey(name: 'snake_case_key')` | Map a JSON key to a differently-named Dart field |
| `@JsonKey(defaultValue: [])` | Provide a fallback when the key is absent |
| `@JsonKey(includeIfNull: false)` | Omit null fields from `toJson` output |
| `@JsonSerializable(explicitToJson: true)` | Serialize nested objects (not just top-level) |

### Nested Model Example

```dart
@JsonSerializable(explicitToJson: true)
class ParentModel with EquatableMixin {
  const ParentModel({this.id, this.child});

  factory ParentModel.fromJson(Map<String, dynamic> json) =>
      _$ParentModelFromJson(json);

  final String? id;
  final ChildModel? child;

  Map<String, dynamic> toJson() => _$ParentModelToJson(this);

  @override
  List<Object?> get props => [id, child];
}
```

> **Note:** Commit the generated `.g.dart` files so CI doesn't require a build step.

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
Service calls HttpHelper.getDataFromServer(EndPoints.example, data: {...})
      ↓
HttpService sends Dio request with Bearer token
      ↓
Response arrives → ResponseData parsed
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

The project does **not** use `go_router`, `auto_route`, or named routes. Navigation is imperative using `Navigator` + `GlobalKey<NavigatorState>`.

### Navigator Keys

Named keys are registered in DI so services can navigate without a `BuildContext`:

```dart
serviceLocator.registerSingleton<GlobalKey<NavigatorState>>(
  GlobalKey<NavigatorState>(),
  instanceName: NavigationKeys.global,
);
```

Key name constants live in `lib/shared/domain/constants/navigation_keys.dart`.

### App Flow

```
SplashScreen (home)
      ↓
SplashCubit.checkLoginStatus()
      ├─ not logged in → LoginScreen
      └─ logged in → (optional gate screen) → MainScreen

MainScreen
  └── Custom bottom nav bar
       └── LazyIndexedStack (one tab per feature)
```

### Navigating Between Screens

```dart
// Push from within widget tree
Navigator.of(context).push(
  MaterialPageRoute(builder: (_) => const SomeScreen()),
);

// Push from outside widget tree (e.g. service layer)
serviceLocator<GlobalKey<NavigatorState>>(instanceName: NavigationKeys.global)
  .currentState
  ?.push(MaterialPageRoute(builder: (_) => const SomeScreen()));
```

### Tab Navigation

The main screen uses a custom bottom nav bar widget. Active tab index is tracked by a dedicated Cubit (`ActiveIndexObserverCubit`) registered at app startup so any component can switch tabs programmatically.

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
| `lib/main_dev.dart` | Sets `F.appFlavor = Flavor.dev`, uses dev Firebase options |
| `lib/main_prod.dart` | Sets `F.appFlavor = Flavor.prod`, uses prod Firebase options |

Both call `await runner.main()` (`lib/main.dart`) after Firebase initialization.

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

### Theme Entry Point

`lib/shared/utils/components/theme/theme_manager.dart` → `getApplicationThemeLight(BuildContext context)` returns the single `ThemeData`. Only a light theme exists.

Applied in `app.dart`:
```dart
theme: getApplicationThemeLight(context),
```

### Color System

`lib/shared/utils/components/theme/color_manager.dart`

All colors are `static const Color` on `ColorManager`:

```dart
ColorManager.primary
ColorManager.secondary
ColorManager.background
ColorManager.whiteColor
ColorManager.greyTextColor
// ...
```

Never use raw `Color(0xFF...)` in widget files — always reference `ColorManager`.

### Typography

`lib/shared/utils/components/theme/styles_manager.dart`

All text styles built from helper functions backed by `GoogleFonts.poppins()`:

```dart
getRegularStyle(color: ColorManager.secondary, fontSize: KFontSize.f14)
getMediumStyle(color: ColorManager.secondary, fontSize: KFontSize.f16)
getSemiBoldStyle(color: ColorManager.secondary, fontSize: KFontSize.f18)
getBoldStyle(color: ColorManager.secondary, fontSize: KFontSize.f20)
getLightStyle(color: ColorManager.secondary, fontSize: KFontSize.f12)
```

Never create `TextStyle(...)` directly in a widget. Use these helpers.

### Responsive Dimensions

`lib/shared/utils/components/size_manager.dart` — backed by `flutter_screenutil`. Design size is **393×865**.

```dart
KFontSize.f14    // font sizes  → .sp
KRadius.r10      // radii       → .r
KPadding.v15     // padding     → .h / .w
KHeight.h20      // heights     → .h
KWidth.w10       // widths      → .w
```

Initialized in `app.dart` via `ScreenUtilInit(designSize: const Size(393, 865), ...)`. All references scale automatically.

### Theme Getters

`lib/shared/utils/components/theme/theme_getters.dart` — convenience wrappers for accessing `ThemeData` properties without a `BuildContext`.

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

`lib/shared/utils/router/http utils/common_exception.dart`

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

### Firebase Crashlytics Integration

`lib/main.dart` installs crash handlers at boot:

```dart
FlutterError.onError = FirebaseCrashlytics.instance.recordFlutterFatalError;
PlatformDispatcher.instance.onError = (error, stack) {
  FirebaseCrashlytics.instance.recordError(error, stack, fatal: true);
  return true;
};
```

Network errors (`SocketException`, `ClientException`, etc.) are recorded as non-fatal.

### Token Expiry

`HttpService` silently refreshes tokens. If refresh fails, it broadcasts `UserTokenState.expired` on the `tokenState` stream and the app shell redirects to login.

---

## 16. Shared Components

### `lib/shared/`

```
shared/
├── api/
│   ├── end_points.dart         → All API endpoint constants
│   └── urls.dart               → External web URL constants
├── domain/
│   ├── constants/
│   │   ├── data_fetching_status.dart
│   │   ├── global_variables.dart   → serviceLocator instance + app-wide constants
│   │   ├── navigation_keys.dart    → Named navigator key string constants
│   │   └── page_controllers.dart   → Named PageController string constants
│   ├── helpers/
│   │   ├── extensions/             → on_string, on_json, on_list, etc.
│   │   ├── validators.dart
│   │   ├── format_phone.dart
│   │   ├── snack_bar.dart
│   │   └── global_setters.dart     → Post-login data persistence helpers
│   ├── models/                     → Shared models (e.g. token response)
│   ├── repository/                 → Shared abstract repos
│   └── services/                   → Shared service implementations
├── logic/
│   ├── active_index_observer_cubit/
│   ├── app_update_checker_bloc/
│   └── global_functional_cubit/
├── screens/                        → Shared full-page screens
├── utils/
│   ├── components/
│   │   ├── app_strings.dart        → All user-facing strings
│   │   ├── assets_manager.dart     → Asset path constants
│   │   ├── size_manager.dart       → KFontSize, KRadius, KPadding, etc.
│   │   └── theme/
│   │       ├── color_manager.dart
│   │       ├── styles_manager.dart
│   │       ├── theme_getters.dart
│   │       └── theme_manager.dart
│   ├── local_storage/
│   │   ├── keys.dart
│   │   └── local_storage_service.dart
│   ├── router/
│   │   └── http utils/
│   │       ├── common_exception.dart
│   │       ├── http_helper.dart
│   │       ├── http_service.dart
│   │       └── model/
│   └── service_locator_setup/
│       └── setup.dart
└── widgets/
    ├── v3/                          → Current-generation shared widgets
    └── *.dart                       → Common widgets
```

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

**Rule:** Import a feature via its top-level `index.dart` to get its full public API:
```dart
import 'package:{app_name}/features/{name}/index.dart';
```

Cubit state files declared as `part` of the cubit are **not** exported — they are only accessible through the cubit file.

---

## 18. Key Integrations

### Firebase

- `FirebaseAnalytics` — registered as singleton, accessed via `serviceLocator<FirebaseAnalytics>()`
- Analytics are wrapped behind `FirebaseAnalyticsRepository` → `FirebaseAnalyticsService` (log events through this interface, not directly)
- `FirebaseCrashlytics` — accessed directly (not via DI); error handlers set at boot in `main.dart`
- `FirebaseMessaging` — background handler registered in `main.dart`; FCM token fetched during login

### Firebase Remote Config

`RemoteConfigService.instance.init()` called at boot. Used to gate features by version or roll out flags without an app update.

### Local Notifications

`LocalNotificationRepository` → `LocalNotificationService`, registered as a **singleton** (must be ready before the first push arrives).

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

- All HTTP calls go through `HttpHelper.getDataFromServer()`
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

### json_serializable

For any new model added to the project, prefer `json_serializable` over manual `fromJson`/`toJson`. Run `dart run build_runner build --delete-conflicting-outputs` after model changes and commit the `.g.dart` file.

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

`features/{name}/domain/models/{name}_model.dart` (using `json_serializable`):

```dart
import 'package:equatable/equatable.dart';
import 'package:json_annotation/json_annotation.dart';

part '{name}_model.g.dart';

@JsonSerializable()
class ExampleModel with EquatableMixin {
  const ExampleModel({this.id, this.title});

  factory ExampleModel.fromJson(Map<String, dynamic> json) =>
      _$ExampleModelFromJson(json);

  final String? id;
  final String? title;

  Map<String, dynamic> toJson() => _$ExampleModelToJson(this);

  @override
  List<Object?> get props => [id, title];
}
```

Run `dart run build_runner build --delete-conflicting-outputs` to generate `{name}_model.g.dart`.

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
import 'package:{app_name}/features/{name}/domain/index.dart';
import 'package:{app_name}/shared/api/end_points.dart';
import 'package:{app_name}/shared/utils/router/http%20utils/common_exception.dart';
import 'package:{app_name}/shared/utils/router/http%20utils/http_helper.dart';

class ExampleService implements ExampleRepository {
  @override
  Future<ExampleModel> getItem({required String id}) async {
    final response = await HttpHelper.getDataFromServer(
      EndPoints.exampleGet,
      requestType: RequestType.get,
      data: {'id': id},
    );
    if (!response.success) {
      throw ApiException(response.message, response.responseCode);
    }
    return ExampleModel.fromJson(response.responseBody);
  }
}
```

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

### Model (json_serializable)

```dart
import 'package:equatable/equatable.dart';
import 'package:json_annotation/json_annotation.dart';

part '{name}_model.g.dart';

@JsonSerializable(explicitToJson: true)
class {Name}Model with EquatableMixin {
  const {Name}Model({this.id, this.name, this.child});

  factory {Name}Model.fromJson(Map<String, dynamic> json) =>
      _${Name}ModelFromJson(json);

  final String? id;
  final String? name;
  final {Child}Model? child;

  Map<String, dynamic> toJson() => _${Name}ModelToJson(this);

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
  @override
  Future<{Name}ResponseModel> getItem({required String id}) async {
    final response = await HttpHelper.getDataFromServer(
      EndPoints.{name}Get,
      requestType: RequestType.get,
      data: {'id': id},
    );
    if (!response.success) {
      throw ApiException(response.message, response.responseCode);
    }
    return {Name}ResponseModel.fromJson(response.responseBody);
  }

  @override
  Future<void> createItem({required {Name}Model body}) async {
    final response = await HttpHelper.getDataFromServer(
      EndPoints.{name}Create,
      data: body.toJson(),
    );
    if (!response.success) {
      throw ApiException(response.message, response.responseCode);
    }
  }
}
```

---

*End of Architecture Skeleton*
