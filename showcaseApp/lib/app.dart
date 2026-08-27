import 'package:flutter/material.dart';
import 'package:flutter_bloc/flutter_bloc.dart';

import 'features/catalog/logic/funnel_cubit/funnel_cubit.dart';
import 'shared/domain/constants/global_variables.dart';
import 'l10n/app_localizations.dart';
import 'shared/logic/branch_cubit/branch_cubit.dart';
import 'shared/logic/locale_cubit/locale_cubit.dart';
import 'shared/logic/connectivity_cubit/connectivity_cubit.dart';
import 'shared/logic/theme_cubit/theme_cubit.dart';
import 'shared/utils/components/theme/pearl_theme.dart';
import 'shared/utils/router/app_router.dart';

/// Root widget. Provides the app-wide cubits and builds the Pearl-themed router.
///
/// [FunnelCubit] lives here rather than on a screen so the choices survive
/// navigation: reopening the size step from the results must not lose the
/// category, and the funnel column has to render the same answers on every
/// screen of the flow.
class ShowcaseApp extends StatefulWidget {
  const ShowcaseApp({super.key});

  @override
  State<ShowcaseApp> createState() => _ShowcaseAppState();
}

class _ShowcaseAppState extends State<ShowcaseApp> {
  final _router = createRouter();

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
            // Applied here rather than per style so it reaches everything —
            // including the widgets that build their own TextStyles.
            builder: (context, child) => MediaQuery.withClampedTextScaling(
              minScaleFactor: settings.textScale,
              maxScaleFactor: settings.textScale,
              child: child ?? const SizedBox.shrink(),
            ),
          ),
        ),
      ),
    );
  }
}
