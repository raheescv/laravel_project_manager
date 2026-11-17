import 'package:flutter/material.dart';
import 'package:go_router/go_router.dart';

import 'package:invo/features/shell/screens/v3/home_shell.dart';
import 'package:invo/shared/domain/helpers/responsive.dart';
import 'package:invo/shared/utils/router/routes.dart';
import 'package:invo/shared/widgets/astra_bottom_nav.dart';
import 'package:invo/shared/widgets/astra_side_rail.dart';
import 'package:invo/shared/widgets/nav_hide.dart';

/// A page pushed over the home shell that keeps the app's primary navigation:
/// the side-rail on a tablet, the bottom nav plus the New Sale "+" on a phone.
///
/// A nav tap closes the pushed pages and has the shell swap to the tab in
/// place — so whatever the shell was showing (a Products list with its
/// filters, say) is still there, not rebuilt. Opened by deep link with no shell
/// underneath, it routes to the tab instead.
///
/// The phone body is extended under the bar: read `MediaQuery.paddingOf(context).bottom`
/// for how much of it the bar covers.
class NavScaffold extends StatelessWidget {
  const NavScaffold({super.key, required this.child, this.activeTab = 3});

  final Widget child;

  /// The highlighted primary tab (see [astraNavTabs]); Settings by default.
  final int activeTab;

  static void _goTo(BuildContext context, int tab) {
    final router = GoRouter.of(context);
    if (!HomeShell.selectTab(tab)) return router.go(Routes.homeTab(tab));
    while (router.canPop()) {
      router.pop();
    }
  }

  @override
  Widget build(BuildContext context) {
    if (context.isTablet) {
      return Scaffold(body: AstraRailShell(activeIndex: activeTab, onSelect: (i) => _goTo(context, i), child: child));
    }
    return NavHide(
      child: Scaffold(
        extendBody: true,
        body: child,
        floatingActionButtonLocation: FloatingActionButtonLocation.centerDocked,
        floatingActionButton: AstraNavFab(onTap: () => context.push(Routes.sale)),
        bottomNavigationBar: AstraNavBar(activeIndex: activeTab, onTap: (i) => _goTo(context, i)),
      ),
    );
  }
}
