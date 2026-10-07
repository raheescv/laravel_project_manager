import 'dart:async';

import 'package:flutter/material.dart';
import 'package:flutter_bloc/flutter_bloc.dart';
import 'package:flutter_screenutil/flutter_screenutil.dart';

import 'features/auth/logic/auth_cubit/auth_cubit.dart';
import 'features/checklist/logic/checklist_inbox_cubit/checklist_inbox_cubit.dart';
import 'features/technician/logic/complaints_cubit/complaints_cubit.dart';
import 'features/technician/logic/dashboard_cubit/dashboard_cubit.dart';
import 'shared/domain/constants/global_variables.dart';
import 'shared/logic/haptics_cubit/haptics_cubit.dart';
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
  final _auth = serviceLocator<AuthCubit>();
  late final _router = createRouter(_auth);

  // Above the router so pushed routes (complaint detail) can read them too —
  // which is why they live as long as the app and must be wiped by hand when
  // the session ends, or the next technician sees the last one's jobs.
  final _dashboard = TechnicianDashboardCubit();
  final _complaints = ComplaintsCubit();
  AuthStatus _lastStatus = AuthStatus.unknown;
  StreamSubscription<int>? _authSub;

  @override
  void initState() {
    super.initState();
    // Read now, not lazily: a `late` initialiser would first run inside the
    // listener — after the status had already flipped — and never see the change.
    _lastStatus = _auth.status;
    _authSub = _auth.stream.listen((_) {
      final status = _auth.status;
      if (status == AuthStatus.signedOut && _lastStatus != AuthStatus.signedOut) {
        _dashboard.reset();
        _complaints.reset();
        serviceLocator<ChecklistInboxCubit>().reset();
      }
      _lastStatus = status;
    });
  }

  @override
  void dispose() {
    _authSub?.cancel();
    _dashboard.close();
    _complaints.close();
    super.dispose();
  }

  @override
  Widget build(BuildContext context) {
    return ScreenUtilInit(
      designSize: const Size(393, 865),
      minTextAdapt: true,
      builder: (context, _) => MultiBlocProvider(
        providers: [
          BlocProvider.value(value: serviceLocator<AuthCubit>()),
          BlocProvider.value(value: serviceLocator<ThemeCubit>()),
          BlocProvider.value(value: serviceLocator<HapticsCubit>()),
          BlocProvider.value(value: _dashboard),
          BlocProvider.value(value: _complaints),
          BlocProvider.value(value: serviceLocator<ChecklistInboxCubit>()),
        ],
        child: BlocBuilder<ThemeCubit, ThemeState>(
          builder: (context, theme) {
            return MaterialApp.router(
              title: 'FixMate',
              debugShowCheckedModeBanner: false,
              theme: buildAstraTheme(theme.palette, theme.typeface),
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
