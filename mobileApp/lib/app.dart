import 'package:flutter/material.dart';
import 'package:flutter_bloc/flutter_bloc.dart';
import 'package:flutter_screenutil/flutter_screenutil.dart';

import 'features/admin/logic/admin_cubit/admin_cubit.dart';
import 'features/admin/logic/day_session_cubit/day_session_cubit.dart';
import 'features/auth/logic/auth_cubit/auth_cubit.dart';
import 'features/sale/logic/cart_cubit/cart_cubit.dart';
import 'features/sale/logic/catalog_cubit/catalog_cubit.dart';
import 'features/sale/logic/stylist_cubit/stylist_cubit.dart';
import 'features/sale_return/logic/return_draft_cubit/return_draft_cubit.dart';
import 'features/settings/logic/print_settings_cubit/print_settings_cubit.dart';
import 'shared/domain/constants/global_variables.dart';
import 'shared/logic/branch_cubit/branch_cubit.dart';
import 'shared/logic/currency_cubit/currency_cubit.dart';
import 'shared/logic/haptics_cubit/haptics_cubit.dart';
import 'shared/logic/theme_cubit/theme_cubit.dart';
import 'shared/utils/components/haptics.dart';
import 'shared/utils/components/theme/theme_manager.dart';
import 'shared/utils/router/app_router.dart';

/// Root widget: provides every app-wide cubit, builds the themed
/// `MaterialApp.router`, and wraps the app in the screen-util responsive frame
/// plus the global haptic / keyboard-dismiss chrome.
class InvoApp extends StatefulWidget {
  const InvoApp({super.key});

  @override
  State<InvoApp> createState() => _InvoAppState();
}

class _InvoAppState extends State<InvoApp> {
  late final _router = createRouter(serviceLocator<AuthCubit>());

  @override
  Widget build(BuildContext context) {
    return ScreenUtilInit(
      designSize: const Size(393, 865),
      minTextAdapt: true,
      builder: (context, _) => MultiBlocProvider(
        providers: [
          // App-wide singletons (resolved from get_it).
          BlocProvider.value(value: serviceLocator<AuthCubit>()),
          BlocProvider.value(value: serviceLocator<ThemeCubit>()),
          BlocProvider.value(value: serviceLocator<HapticsCubit>()),
          BlocProvider.value(value: serviceLocator<CurrencyCubit>()),
          BlocProvider.value(value: serviceLocator<BranchCubit>()),
          BlocProvider.value(value: serviceLocator<PrintSettingsCubit>()),
          // Per-session feature cubits.
          BlocProvider(create: (_) => CartCubit()),
          BlocProvider(create: (_) => ReturnDraftCubit()),
          BlocProvider(create: (_) => CatalogCubit()),
          BlocProvider(create: (_) => StylistCubit()),
          BlocProvider(create: (_) => AdminCubit()),
          BlocProvider(create: (_) => DaySessionCubit()),
        ],
        // Depend on theme + currency so a change re-skins / reformats everything.
        child: Builder(
          builder: (context) {
            context.watch<CurrencyCubit>();
            final palette = context.watch<ThemeCubit>().palette;
            return MaterialApp.router(
              title: 'Invo',
              debugShowCheckedModeBanner: false,
              theme: buildAstraTheme(palette),
              routerConfig: _router,
              builder: (context, child) => HapticTapDetector(
                child: GestureDetector(
                  behavior: HitTestBehavior.translucent,
                  onTap: () => FocusManager.instance.primaryFocus?.unfocus(),
                  child: child,
                ),
              ),
            );
          },
        ),
      ),
    );
  }
}
