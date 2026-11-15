import 'package:flutter/material.dart';
import 'package:flutter_bloc/flutter_bloc.dart';

import 'shared/logic/funnel_cubit/funnel_cubit.dart';
import 'shared/utils/router/funnel_navigation.dart';
import 'shared/domain/constants/global_variables.dart';
import 'l10n/app_localizations.dart';
import 'shared/logic/branch_cubit/branch_cubit.dart';
import 'shared/logic/locale_cubit/locale_cubit.dart';
import 'shared/logic/connectivity_cubit/connectivity_cubit.dart';
import 'shared/logic/theme_cubit/theme_cubit.dart';
import 'shared/utils/components/theme/panel_scale.dart';
import 'shared/utils/components/theme/pearl_theme.dart';
import 'shared/utils/router/app_router.dart';
import 'shared/utils/router/routes.dart';
import 'shared/widgets/chrome/idle_reset.dart';

/// Root widget. Provides the app-wide cubits and builds the Pearl-themed router.
///
/// [FunnelCubit] lives here rather than on a screen so the choices survive
/// navigation: reopening the size step from the results must not lose the
/// category, and the breadcrumbs have to show the same answers on every screen
/// of the flow.
class ShowcaseApp extends StatefulWidget {
  const ShowcaseApp({super.key});

  @override
  State<ShowcaseApp> createState() => _ShowcaseAppState();
}

class _ShowcaseAppState extends State<ShowcaseApp> {
  final _router = createRouter();

  /// The wait set in Settings, and the panel is somebody else's.
  ///
  /// The clearing itself lives beside the funnel's own navigation, because the
  /// Home control on the product page has to do exactly the same thing — see
  /// [clearForNextCustomer]. Navigating to the root replaces the stack, so a
  /// half-typed search and the screen it was on go with it.
  ///
  /// The router is held here rather than reached through the context: this
  /// builder sits above the `Router` it configures, so there is no `GoRouter`
  /// to look up from here.
  Future<void> _returnHome(BuildContext context) async {
    await clearForNextCustomer(context);
    _router.go(Routes.size);
  }

  @override
  Widget build(BuildContext context) {
    return MultiBlocProvider(
      providers: [
        BlocProvider<ThemeCubit>.value(value: serviceLocator<ThemeCubit>()),
        BlocProvider<BranchCubit>.value(value: serviceLocator<BranchCubit>()),
        BlocProvider<ConnectivityCubit>.value(value: serviceLocator<ConnectivityCubit>()),
        BlocProvider<LocaleCubit>(create: (_) => LocaleCubit()),
        BlocProvider<FunnelCubit>(create: (_) => FunnelCubit()),
      ],
      child: BlocBuilder<ThemeCubit, ThemeSettings>(
        builder: (context, settings) => BlocBuilder<LocaleCubit, Locale?>(
          builder: (context, locale) => MaterialApp.router(
            title: 'Sizerun',
            debugShowCheckedModeBanner: false,
            themeMode: settings.mode,
            // Each mode wears the preset chosen for it in Settings → Appearance.
            theme: buildPearlTheme(settings.light.light),
            darkTheme: buildPearlTheme(settings.dark.dark),
            // Null follows the device. Arabic brings RTL with it — Flutter
            // mirrors the whole tree off the locale, so nothing below has to
            // ask which way round it is.
            locale: locale,
            supportedLocales: L.supportedLocales,
            localizationsDelegates: L.localizationsDelegates,
            routerConfig: _router,
            // Applied here rather than per style so both reach everything —
            // including the widgets that build their own TextStyles, and the
            // sheets and dialogs the router puts in its own overlay.
            builder: (context, child) => IdleReset(
              onIdle: () => _returnHome(context),
              after: Duration(minutes: settings.idleMinutes),
              // The panel first, the setting second. How large the app is
              // drawn is a property of the glass it is drawn on; "text size"
              // is a multiplier a customer applies on top of whatever that
              // turned out to be, not a substitute for it.
              child: PanelScale(
                child: MediaQuery.withClampedTextScaling(
                  minScaleFactor: settings.textScale,
                  maxScaleFactor: settings.textScale,
                  child: child ?? const SizedBox.shrink(),
                ),
              ),
            ),
          ),
        ),
      ),
    );
  }
}
