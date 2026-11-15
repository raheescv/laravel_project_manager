import 'package:flutter_bloc/flutter_bloc.dart';
import 'package:go_router/go_router.dart';

import 'package:invo/features/auth/logic/auth_cubit/auth_cubit.dart';
import 'package:invo/features/auth/screens/v3/login_screen.dart';
import 'package:invo/features/checklist/logic/checklist_detail_cubit/checklist_detail_cubit.dart';
import 'package:invo/features/checklist/screens/v3/checklist_rooms_screen.dart';
import 'package:invo/features/profile/screens/v3/change_password_screen.dart';
import 'package:invo/features/profile/screens/v3/change_pin_screen.dart';
import 'package:invo/features/profile/screens/v3/edit_profile_screen.dart';
import 'package:invo/features/profile/screens/v3/profile_screen.dart';
import 'package:invo/features/shell/screens/v3/technician_shell.dart';
import 'package:invo/features/technician/logic/complaint_detail_cubit/complaint_detail_cubit.dart';
import 'package:invo/features/technician/screens/v3/complaint_detail_screen.dart';
import 'package:invo/shared/domain/constants/global_variables.dart';
import 'package:invo/shared/utils/components/theme/transitions.dart';
import 'package:invo/shared/widgets/astra_side_rail.dart';

import 'go_router_refresh_stream.dart';
import 'routes.dart';

/// The technician app router. After sign-in every technician lands on the
/// dashboard; there is no permission-gated admin/POS surface.
///
/// Transitions: every route uses the theme's page transitions (Cupertino slide
/// on iOS, the Astra fade-slide elsewhere) except sign-in ↔ home, which fade
/// through — that swap is not a step deeper. Pushed destinations are wrapped
/// in [TabletRailScaffold] so a tablet keeps its rail (a no-op on phones).
GoRouter createRouter(AuthCubit auth) {
  return GoRouter(
    initialLocation: Routes.login,
    refreshListenable: GoRouterRefreshStream(auth.stream),
    redirect: (context, state) {
      if (auth.status == AuthStatus.unknown) return null;
      final loggedIn = auth.status == AuthStatus.signedIn;
      final atLogin = state.matchedLocation == Routes.login;
      if (!loggedIn) return atLogin ? null : Routes.login;
      if (atLogin) return Routes.home;
      return null;
    },
    routes: [
      GoRoute(
        path: Routes.login,
        pageBuilder: (_, state) => astraFadeThroughPage(state, const LoginScreen()),
      ),
      GoRoute(
        path: Routes.home,
        pageBuilder: (_, state) => astraFadeThroughPage(
          state,
          TechnicianShell(initialTab: int.tryParse(state.uri.queryParameters['tab'] ?? '') ?? 0),
        ),
      ),
      // "My Jobs" as a deep link — lands on the one shell rather than building
      // a second one.
      GoRoute(
        path: Routes.complaints,
        redirect: (_, __) {
          shellTabRequests.request(Routes.jobsTabIndex);
          return Routes.homeTab(Routes.jobsTabIndex);
        },
      ),
      GoRoute(
        path: Routes.complaintDetailPattern,
        builder: (_, state) {
          final id = int.tryParse(state.pathParameters['id'] ?? '') ?? 0;
          return TabletRailScaffold(
            activeTab: Routes.jobsTabIndex,
            child: BlocProvider(
              create: (_) => ComplaintDetailCubit(id)..load(),
              child: const ComplaintDetailScreen(),
            ),
          );
        },
      ),
      // A hand-over (rent-out × phase). The detail cubit is shared with the
      // Room / Hand-over screens pushed on top via BlocProvider.value.
      GoRoute(
        path: Routes.checklistDetailPattern,
        builder: (_, state) {
          final id = int.tryParse(state.pathParameters['id'] ?? '') ?? 0;
          final phase = state.uri.queryParameters['phase'];
          // The provider sits OUTSIDE the rail wrapper: the wrapper's layout changes
          // with the device class, and the cubit must outlive that — sheets and the
          // Room / Hand-over screens hold this exact instance.
          return BlocProvider(
            create: (_) => serviceLocator<ChecklistDetailCubit>()..load(id, phase),
            child: const TabletRailScaffold(
              activeTab: Routes.checklistsTabIndex,
              child: ChecklistRoomsScreen(),
            ),
          );
        },
      ),
      // Account. On a tablet these are normally reached inside the profile /
      // settings panes; the routes serve phones and deep links.
      GoRoute(
        path: Routes.profile,
        builder: (_, __) => const TabletRailScaffold(activeTab: kProfileTab, child: ProfileScreen()),
      ),
      GoRoute(
        path: Routes.editProfile,
        builder: (_, __) => const TabletRailScaffold(activeTab: kProfileTab, child: EditProfileScreen()),
      ),
      GoRoute(
        path: Routes.changePin,
        builder: (_, __) => const TabletRailScaffold(activeTab: kProfileTab, child: ChangePinScreen()),
      ),
      GoRoute(
        path: Routes.changePassword,
        builder: (_, __) => const TabletRailScaffold(activeTab: kProfileTab, child: ChangePasswordScreen()),
      ),
    ],
  );
}
