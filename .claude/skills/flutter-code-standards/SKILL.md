---
name: flutter-code-standards
description: "Use when writing or reviewing Dart code in mobileApp/ or technicianApp/ — a new cubit, state class, repository, service, model, paginated list screen, bottom sheet, or route. Covers the enforceable code patterns: immutable Cubit state with copyWith + DataFetchStatus, repository/service pairs over HttpService + EndPoints, defensive JSON parsing with asStr/asNum, controller disposal in sheets, lazy list building, route and string constants, and the mistakes this codebase actually makes. Read alongside flutter-apps (which covers layout, cross-cutting behaviour, and the two hard rules) before writing new .dart code or reviewing a Flutter diff."
---

# Flutter Code Standards

Companion to the **flutter-apps** skill. That one tells you where things live and what not to
touch; this one tells you what the code should look like when you write it.

`project_architecture_skeleton.md` in `mobileApp/` is the long-form reference. It is partly a
generic template — where it disagrees with this skill, this skill wins, because this skill was
written from an audit of the actual codebase (`mobileApp/docs/mobile-app-architecture-audit.html`).

**The check is `flutter analyze lib`. Never `dart format`, `flutter format`, or `dart fix --apply`
— this codebase uses a compact hand style and formatting produces ~6000 lines of churn.**

---

## 1. Cubit state — new cubits use an immutable state class

All 16 cubits use the shape below — the old `HolderCubit` tick base class was deleted once its last
subclass was converted. `PaginatedListCubit` and `CartCubit` are the references to copy.

```dart
// logic/{name}_cubit/{name}_state.dart
part of '{name}_cubit.dart';

class ExampleState extends Equatable {
  const ExampleState({
    this.status = DataFetchStatus.idle,
    this.items = const [],
    this.errorMessage,
  });

  final DataFetchStatus status;
  final List<ExampleModel> items;
  final String? errorMessage;

  ExampleState copyWith({
    DataFetchStatus? status,
    List<ExampleModel>? items,
    String? errorMessage,
  }) =>
      ExampleState(
        status: status ?? this.status,
        items: items ?? this.items,
        errorMessage: errorMessage ?? this.errorMessage,
      );

  @override
  List<Object?> get props => [status, items, errorMessage];
}
```

```dart
// logic/{name}_cubit/{name}_cubit.dart
part '{name}_state.dart';

class ExampleCubit extends Cubit<ExampleState> {
  ExampleCubit(this._repo) : super(const ExampleState());

  final ExampleRepository _repo;   // abstract type, injected — never the concrete Service

  Future<void> fetchItems() async {
    emit(state.copyWith(status: DataFetchStatus.waiting));
    try {
      final items = await _repo.list();
      emit(state.copyWith(status: DataFetchStatus.success, items: items));
    } on ApiException catch (e) {
      emit(state.copyWith(status: DataFetchStatus.failed, errorMessage: e.message));
    } catch (_) {
      emit(state.copyWith(
        status: DataFetchStatus.failed,
        errorMessage: AppStrings.somethingWentWrong,
      ));
    }
  }
}
```

Rules:

- Never mutate state. `emit(state.copyWith(…))`, never `state.field = x`.
- `DataFetchStatus` (`shared/domain/constants/data_fetching_status.dart`) is the status enum:
  `idle → waiting → success | failed`. Don't invent per-screen `_loading`/`_error` booleans.
- Constructor-inject the repository (testable) rather than reaching for `serviceLocator` inside the
  cubit body. Register the wiring in `setup.dart`.
- Consume with `BlocBuilder` + `buildWhen`, or `BlocSelector` when you only need one field.
  `BlocListener` for side effects (navigation, snackbars). `context.watch` rebuilds everything below it.

---

## 2. Repository / Service pair

Contract first, implementation second, both registered abstract→concrete.

```dart
// domain/repository/{name}_repository.dart — no HTTP, no Flutter imports
abstract class ExampleRepository {
  Future<List<ExampleModel>> list({int page});
  Future<ExampleModel> byId(String id);
}
```

```dart
// domain/services/{name}_service.dart
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

```dart
// shared/utils/service_locator_setup/setup.dart
..registerLazySingleton<ExampleRepository>(ExampleService.new)
```

- **Every path goes in `EndPoints`** (`shared/api/end_points.dart`) — no inline `'/products'` in a
  service. Static const for fixed paths, a function for parameterised ones
  (`static String byId(String id) => '/example/$id';`).
- `HttpService` unwraps the `{success, data, message}` envelope and throws `ApiException`
  (with `fieldErrors` on a 422). Let it throw; catch in the cubit.
- Screens and widgets must not call `serviceLocator<XRepository>()`. That call belongs in a cubit.
  If the feature has no cubit, that is the bug — add one.
- `HttpHelper` / `ResponseData` / `RequestType` exist but have zero call sites. Do not start using
  them; they flatten `ApiException` and lose `fieldErrors`.

---

## 3. Models — hand-written and defensive

This project does **not** use `json_serializable`, and should not start. The API returns numbers as
strings in places; generated `as`-cast parsers would throw where the coercion helpers absorb it.

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

- `asStr` / `asNum` from `shared/domain/helpers/formatters.dart`. Never raw-cast API data.
- Nested lists: `(j['items'] as List<dynamic>? ?? []).map((e) => X.fromJson(Map<String, dynamic>.from(e))).toList()`.
- **Fields `final`. Add `Equatable` + `props`** so models compare by value — several existing models
  don't, which makes `list.remove(model)` identity-dependent and silently no-op on a copy.
- New feature models go in `features/{name}/domain/models/{name}_model.dart`, not into
  `shared/domain/models/models.dart` (already 29 classes / 1,034 lines).
- Money: hold minor units (integer fils/cents) in new code. `double` prices compound rounding error
  through percentage discounts and tax and can disagree with the server's total by a cent.

---

## 4. Paginated list screens — the block that keeps breaking

Five screens hand-roll `_load` / `_loadMore` / `_reqId` and three of them shipped the same bug.
Prefer a cubit holding this state. If you must write it in a `State`, the flag reset goes in a
`finally`:

```dart
Future<void> _loadMore() async {
  if (_loadingMore || _loading || !_hasMore) return;
  final req = _reqId;                       // tie to current filters
  setState(() => _loadingMore = true);
  try {
    final res = await _fetch(_page + 1);
    if (!mounted || req != _reqId) return;  // filters changed — void this page
    setState(() {
      _rows = [..._rows, ...res.rows];
      _page = res.currentPage;
      _lastPage = res.lastPage;
    });
  } catch (_) {
    // Keep what we have; the next scroll retries the same page.
  } finally {
    if (mounted) setState(() => _loadingMore = false);   // ← must not be skippable
  }
}
```

Without the `finally`, the early `return` leaves `_loadingMore == true` forever and infinite scroll
is dead for the life of the screen.

**Render accumulated rows lazily.** The app has 1 `ListView.builder` against 42
`ListView(children: […])`; the eager form over a growing `_rows` builds every row on every
`setState`.

```dart
CustomScrollView(
  controller: _scrollCtl,
  slivers: [
    SliverToBoxAdapter(child: _header()),
    SliverList.separated(
      itemCount: _rows.length,
      separatorBuilder: (_, __) => const SizedBox(height: 9),
      itemBuilder: (_, i) => _row(_rows[i]),
    ),
  ],
)
```

`ListView(children:)` is fine for a short fixed list (settings rows, a sheet's options). It is not
fine for anything paginated or API-driven.

---

## 5. Controllers in bottom sheets and dialogs

The leak pattern: a controller built inside a `show*` method is never disposed, and a new pair is
allocated every time the sheet opens.

```dart
// ✗ leaks two controllers per open
void _pickClient() {
  final nameCtl = TextEditingController(text: cart.customerName);
  showModalBottomSheet(context: context, builder: (ctx) => …);
}

// ✓ the sheet body owns and disposes them
class _ClientSheet extends StatefulWidget { … }
class _ClientSheetState extends State<_ClientSheet> {
  late final _name = TextEditingController(text: widget.initialName);
  @override
  void dispose() { _name.dispose(); super.dispose(); }
}
```

Same for `ScrollController`, `FocusNode`, `AnimationController`, `StreamSubscription`
(`.cancel()`), and `Timer` (`.cancel()`).

---

## 6. Constants, not literals

| Kind | Home | Never |
|---|---|---|
| API path | `EndPoints` | `_http.get('/products')` |
| Route path | `Routes` (`shared/utils/router/`) | `context.push('/sale-return/pick')` |
| User-facing copy | `AppStrings` | `'Could not load sales.'` inline |
| Colour | `context.astra` palette | `Color(0xFF…)`, `Colors.teal` in a feature file |
| Text style | `ui()` / `serif()` | `TextStyle(…)` built inline |
| Domain sentinel | a named `const` | `'Walk-in'`, a hardcoded phone number |

Colours: `context.astra` is the themeable palette (5 presets × light/dark).
`ColorManager` holds only preset-independent constants (`success`, `danger`, black/white).
`Colors.white` on a coloured surface is fine; `Colors.grey[600]` as body text is not — it breaks
dark mode.

Typography: `ui(size:, weight:, color:)` for UI text, `serif(size:, color:)` for display/prices.
Both follow the user's Typography setting. Never construct `TextStyle` directly.

---

## 7. Navigation

`go_router`, declarative, configured in `shared/utils/router/app_router.dart`. Permission gating
belongs in the top-level `redirect`, keyed off `PermissionSlug` — not in the screen.

**Guard every `state.extra` cast.** `extra` is `Object?` and is not preserved across a process
restore or supplied by a deep link, so a bare `as` crashes with no way back:

```dart
GoRoute(
  path: Routes.invoice,
  redirect: (_, state) => state.extra is Sale ? null : Routes.sales,
  builder: (_, state) => InvoiceScreen(sale: state.extra as Sale),
),
```

---

## 8. Widgets

- Extract repeated UI to a `StatelessWidget`, not a `Widget _foo()` method. Methods can't be
  `const`, get no element of their own, and always rebuild with the parent. Several screens carry
  20+ private widget-methods; don't add to them.
- Sizes are raw literals in this codebase (`ui(size: 13)`, `SizedBox(height: 14)`). `KFontSize`,
  `KRadius`, `KPadding`, `KHeight`, `KWidth` exist but have zero call sites — don't reach for them
  without a decision to adopt them everywhere.
- Responsive: `context.isTablet` / `context.isWide` / `MaxWidthBox` from
  `shared/domain/helpers/responsive.dart`. Tablet layouts are gated on `context.isTablet`; the phone
  UI must not change.
- After an `await`, check `mounted` before touching `context`. The analyzer enforces this
  (`use_build_context_synchronously`) and the codebase is currently clean — keep it that way.

---

## 9. Error handling

```dart
try {
  …
} on ApiException catch (e) {
  // e.message is server copy, already user-appropriate; e.fieldErrors is set on a 422
} catch (_) {
  // AppStrings.somethingWentWrong
}
```

Never `catch (_) {}` with an empty body — it swallows `TypeError` and `NoSuchMethodError` too, so
programming bugs disappear. If you genuinely must ignore something, narrow the type
(`on PlatformException catch (_)`) and leave a one-line comment saying what is being ignored.

No `print` / `debugPrint` in committed code — the codebase currently has zero.

---

## 10. Feature anatomy

```
features/{name}/
  domain/models/{name}_model.dart
  domain/repository/{name}_repository.dart      # abstract
  domain/services/{name}_service.dart           # implements the above
  logic/{name}_cubit/{name}_cubit.dart          # + {name}_state.dart as a part
  screens/v3/{name}_screen.dart
  widgets/v3/{name}_widget.dart
```

A feature with screens but no `logic/` is the shape that produces every recurring bug in this
codebase (`sales`, `sales_returns`, `profile`, `stock_check` all have none). Add the cubit.

Every layer folder gets an `index.dart` re-export, and the feature root re-exports all four —
that is the documented convention, though note no import currently uses it. Follow it for new
features; don't rewrite existing imports as a side quest.

---

## Mistakes this codebase actually makes

From the audit (`mobileApp/docs/mobile-app-architecture-audit.html`). Check a diff against these:

1. **Re-implementing list pagination in a screen** — use the shared `PaginatedListCubit`. The
   hand-rolled version shipped the same stuck-`_loadingMore` bug in three screens at once. §4.
2. **Eager `ListView(children:)` over paginated rows** — no recycling, degrading frames. §4.
3. **`state.extra as T` with no guard** — crash on restore or deep link. §7.
4. **Controllers built inside a `show*` method** — leaked on every open. §5.
5. **Hardcoded currency symbols and sentinels** (`'$'`, `'Walk-in'`, a literal phone number) in a
   multi-currency app. §6.
6. **Mutable model handed out live** — a writer bypasses the cubit, no tick emitted, UI goes stale
   while the payload changes. §3.
7. **Repository called straight from a screen** — fetch state ends up duplicated in `setState`
   across five screens, which is how bug 1 shipped three times. §2.
8. **Writing against a dead subsystem.** `HttpHelper`, `ResponseData`, `RequestType`,
   `NavigationKeys`, `theme_getters.dart`, `KFontSize`/`KRadius`/`KPadding`/`KHeight`/`KWidth` and
   the `equatable` package all have zero call sites. The skeleton document describes several of
   them as mandatory. They are not in use — don't add the first call site without deciding to
   adopt them project-wide.

---

## Verifying

```bash
flutter analyze lib
```

Zero issues is the current baseline; keep it there. `flutter test` for the suites in `test/`
(a shared harness and hand-written repository fakes already exist in `test/support/`).
Never run a formatter. Never blind-tap the running simulator — it writes to the real backend.
