import 'dart:async';

import 'package:flutter/material.dart';
import 'package:flutter_bloc/flutter_bloc.dart';

import 'features/admin/logic/admin_cubit/admin_cubit.dart';
import 'features/admin/logic/day_session_cubit/day_session_cubit.dart';
import 'features/auth/logic/auth_cubit/auth_cubit.dart';
import 'features/sale/logic/cart_cubit/cart_cubit.dart';
import 'features/sale/logic/catalog_cubit/catalog_cubit.dart';
import 'features/sale/logic/offline_sync_cubit/offline_sync_cubit.dart';
import 'features/sale/logic/stylist_cubit/stylist_cubit.dart';
import 'features/sale_return/logic/return_draft_cubit/return_draft_cubit.dart';
import 'features/settings/logic/pos_settings_cubit/pos_settings_cubit.dart';
import 'features/settings/logic/print_settings_cubit/print_settings_cubit.dart';
import 'shared/domain/constants/global_variables.dart';
import 'shared/logic/branch_cubit/branch_cubit.dart';
import 'shared/logic/currency_cubit/currency_cubit.dart';
import 'shared/logic/haptics_cubit/haptics_cubit.dart';
import 'shared/logic/theme_cubit/theme_cubit.dart';
import 'shared/utils/components/haptics.dart';
import 'shared/utils/components/theme/theme_manager.dart';
import 'shared/utils/router/app_router.dart';
import 'shared/utils/router/routes.dart';
import 'shared/widgets/offline_banner.dart';

/// Root widget: provides every app-wide cubit, builds the themed
/// `MaterialApp.router`, and wraps the app in the global haptic /
/// keyboard-dismiss chrome. Responsive layout is handled per screen through
/// `context.isTablet` / `MaxWidthBox`, not a global scaling frame.
class InvoApp extends StatefulWidget {
  const InvoApp({super.key});

  @override
  State<InvoApp> createState() => _InvoAppState();
}

class _InvoAppState extends State<InvoApp> with WidgetsBindingObserver {
  late final _router = createRouter(serviceLocator<AuthCubit>());
  StreamSubscription<AuthState>? _authSub;

  @override
  void initState() {
    super.initState();
    WidgetsBinding.instance.addObserver(this);
    final auth = serviceLocator<AuthCubit>();
    // Sales queued offline outlive the session that took them, so the sync
    // engine starts the moment there is a token to post with — not when some
    // screen happens to be opened.
    if (auth.status == AuthStatus.signedIn) _startSync();
    _authSub = auth.stream.listen((state) {
      if (state.status == AuthStatus.signedIn) {
        _startSync();
      } else if (state.status == AuthStatus.signedOut) {
        // Sign-out wipes the catalog snapshot, and only bootstrap() re-takes it.
        // Without releasing the latch the next cashier would sign in to a till
        // with no offline catalog and no way to get one until a restart.
        _syncStarted = false;
      }
    });
  }

  @override
  void dispose() {
    WidgetsBinding.instance.removeObserver(this);
    _authSub?.cancel();
    super.dispose();
  }

  @override
  void didChangeAppLifecycleState(AppLifecycleState state) {
    // Coming back to the foreground is the moment a till that was carried out
    // of range is most likely to have a connection again.
    if (state == AppLifecycleState.resumed &&
        serviceLocator<AuthCubit>().status == AuthStatus.signedIn) {
      final sync = serviceLocator<OfflineSyncCubit>();
      // Ignore the backoff: a till carried back into range has usually earned
      // one purely from being out of range.
      unawaited(sync.drain(ignoreBackoff: true));
      unawaited(sync.refreshCatalog());
    }
  }

  bool _syncStarted = false;

  void _startSync() {
    if (_syncStarted) return;
    _syncStarted = true;
    unawaited(serviceLocator<OfflineSyncCubit>().bootstrap());
  }

  @override
  Widget build(BuildContext context) {
    return MultiBlocProvider(
        providers: [
          // App-wide singletons (resolved from get_it).
          BlocProvider.value(value: serviceLocator<AuthCubit>()),
          BlocProvider.value(value: serviceLocator<ThemeCubit>()),
          BlocProvider.value(value: serviceLocator<HapticsCubit>()),
          BlocProvider.value(value: serviceLocator<CurrencyCubit>()),
          BlocProvider.value(value: serviceLocator<BranchCubit>()),
          BlocProvider.value(value: serviceLocator<PrintSettingsCubit>()),
          BlocProvider.value(value: serviceLocator<PosSettingsCubit>()),
          // Per-session feature cubits.
          BlocProvider(create: (_) => CartCubit()),
          BlocProvider(create: (_) => ReturnDraftCubit()),
          BlocProvider(create: (_) => CatalogCubit()),
          BlocProvider(create: (_) => StylistCubit()),
          BlocProvider(create: (_) => AdminCubit()),
          BlocProvider(create: (_) => DaySessionCubit()),
        ],
        // Re-skin on a theme change, and re-render on a currency change so the
        // Money formatter's new symbol reaches every already-built label.
        //
        // Both are selectors, not watches: `refreshCurrencies()` runs at every
        // boot and sign-in and emits a new currency *list*, which would rebuild
        // the whole app under a plain watch even though the active currency —
        // the only part the formatter reads — is unchanged.
        child: BlocSelector<CurrencyCubit, CurrencyState, String>(
          selector: (s) => s.currency.code,
          builder: (context, _) => BlocSelector<ThemeCubit, ThemeState, ThemeState>(
            selector: (s) => s,
            builder: (context, theme) {
            return MaterialApp.router(
              title: 'Invo',
              debugShowCheckedModeBanner: false,
              theme: buildAstraTheme(theme.palette, theme.typeface),
              routerConfig: _router,
              builder: (context, child) {
                // Clamp the OS text-scale so a large accessibility setting can't
                // blow past the app's fixed-height rows/tiles and trip overflow.
                // We still honour moderate scale-ups (up to 1.3×) and any
                // scale-down; only the runaway upper end is capped.
                final mq = MediaQuery.of(context);
                final scaled = MediaQuery(
                  data: mq.copyWith(
                    textScaler: mq.textScaler.clamp(
                      minScaleFactor: 0.85,
                      maxScaleFactor: 1.3,
                    ),
                  ),
                  child: child ?? const SizedBox.shrink(),
                );
                // The status strip (offline / first-run provisioning) sits above
                // every screen. It owns the layout because only it knows whether
                // a strip is currently visible — and when one is, it has already
                // taken the status-bar inset.
                return HapticTapDetector(
                  child: GestureDetector(
                    behavior: HitTestBehavior.translucent,
                    onTap: () => FocusManager.instance.primaryFocus?.unfocus(),
                    child: OfflineBanner(
                      onTapPending: () => _router.push(Routes.pendingSales),
                      child: scaled,
                    ),
                  ),
                );
              },
            );
            },
          ),
        ),
    );
  }
}
