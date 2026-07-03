import 'package:flutter/material.dart';
import 'package:flutter_bloc/flutter_bloc.dart';
import 'package:flutter_screenutil/flutter_screenutil.dart';

import 'features/auth/logic/auth_cubit/auth_cubit.dart';
import 'features/technician/logic/complaints_cubit/complaints_cubit.dart';
import 'features/technician/logic/dashboard_cubit/dashboard_cubit.dart';
import 'shared/domain/constants/global_variables.dart';
import 'shared/logic/theme_cubit/theme_cubit.dart';
import 'shared/utils/components/haptics.dart';
import 'shared/utils/components/theme/theme_manager.dart';
import 'shared/utils/router/app_router.dart';

/// Root widget: provides the app-wide cubits, builds the themed
/// `MaterialApp.router`, and wraps everything in the responsive frame + global
/// haptic / keyboard-dismiss chrome (mirrors the POS app's shell).
class TechnicianApp extends StatefulWidget {
  const TechnicianApp({super.key});

  @override
  State<TechnicianApp> createState() => _TechnicianAppState();
}

class _TechnicianAppState extends State<TechnicianApp> {
  late final _router = createRouter(serviceLocator<AuthCubit>());

  @override
  Widget build(BuildContext context) {
    return ScreenUtilInit(
      designSize: const Size(393, 865),
      minTextAdapt: true,
      builder: (context, _) => MultiBlocProvider(
        providers: [
          BlocProvider.value(value: serviceLocator<AuthCubit>()),
          BlocProvider.value(value: serviceLocator<ThemeCubit>()),
          BlocProvider(create: (_) => TechnicianDashboardCubit()),
          BlocProvider(create: (_) => ComplaintsCubit()),
        ],
        child: Builder(
          builder: (context) {
            final palette = context.watch<ThemeCubit>().palette;
            return MaterialApp.router(
              title: 'FixMate',
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
