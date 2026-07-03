import 'package:flutter_bloc/flutter_bloc.dart';
import 'package:go_router/go_router.dart';

import 'package:invo/features/auth/logic/auth_cubit/auth_cubit.dart';
import 'package:invo/features/auth/screens/v3/login_screen.dart';
import 'package:invo/features/profile/screens/v3/change_password_screen.dart';
import 'package:invo/features/profile/screens/v3/change_pin_screen.dart';
import 'package:invo/features/shell/screens/v3/technician_shell.dart';
import 'package:invo/features/technician/logic/complaint_detail_cubit/complaint_detail_cubit.dart';
import 'package:invo/features/technician/screens/v3/complaint_detail_screen.dart';

import 'go_router_refresh_stream.dart';

/// The technician app router. After sign-in every technician lands on the
/// dashboard; there is no permission-gated admin/POS surface.
GoRouter createRouter(AuthCubit auth) {
  return GoRouter(
    initialLocation: '/login',
    refreshListenable: GoRouterRefreshStream(auth.stream),
    redirect: (context, state) {
      if (auth.status == AuthStatus.unknown) return null;
      final loggedIn = auth.status == AuthStatus.signedIn;
      final atLogin = state.matchedLocation == '/login';
      if (!loggedIn) return atLogin ? null : '/login';
      if (atLogin) return '/home';
      return null;
    },
    routes: [
      GoRoute(path: '/login', builder: (_, __) => const LoginScreen()),
      GoRoute(
        path: '/home',
        builder: (_, state) => TechnicianShell(
          initialTab: int.tryParse(state.uri.queryParameters['tab'] ?? '') ?? 0,
        ),
      ),
      // "My Jobs" tab as a deep link (dashboard "View all").
      GoRoute(path: '/complaints', builder: (_, __) => const TechnicianShell(initialTab: 1)),
      GoRoute(
        path: '/complaints/:id',
        builder: (_, state) {
          final id = int.parse(state.pathParameters['id']!);
          return BlocProvider(
            create: (_) => ComplaintDetailCubit(id)..load(),
            child: const ComplaintDetailScreen(),
          );
        },
      ),
      GoRoute(path: '/change-pin', builder: (_, __) => const ChangePinScreen()),
      GoRoute(path: '/change-password', builder: (_, __) => const ChangePasswordScreen()),
    ],
  );
}
