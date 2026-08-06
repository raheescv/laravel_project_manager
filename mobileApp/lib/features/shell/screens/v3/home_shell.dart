import 'package:flutter/material.dart';
import 'package:flutter/rendering.dart' show ScrollDirection;
import 'package:go_router/go_router.dart';

import 'package:invo/shared/domain/helpers/responsive.dart';
import 'package:invo/shared/utils/router/routes.dart';
import 'package:invo/shared/widgets/astra_bottom_nav.dart';
import 'package:invo/shared/widgets/astra_drawer.dart';
import 'package:invo/shared/widgets/astra_side_rail.dart';
import 'package:invo/features/admin/screens/v3/dashboard_screen.dart';
import 'package:invo/features/admin/screens/v3/reports_screen.dart';
import 'package:invo/features/sales/screens/v3/sales_list_screen.dart';
import 'package:invo/features/settings/screens/v3/settings_screen.dart';
import 'package:invo/features/stock_check/screens/v3/stock_check_list_screen.dart';
import 'package:invo/features/sales_returns/screens/v3/sales_returns_list_screen.dart';
import 'package:invo/features/admin/screens/v3/day_session_screen.dart';
import 'package:invo/features/profile/screens/v3/profile_screen.dart';
import 'package:invo/features/settings/screens/v3/permissions_screen.dart';
import 'package:invo/features/auth/logic/auth_cubit/auth_cubit.dart';
import 'package:invo/shared/domain/constants/mobile_permissions.dart';
import 'package:provider/provider.dart';

/// Adaptive admin shell: a glossy floating bottom nav on phones (Instagram-style:
/// hides on scroll-down, returns on scroll-up), a left side-rail on tablets.
class HomeShell extends StatefulWidget {
  const HomeShell({super.key, this.initialTab = 0});

  /// Which primary tab to open on (0=Home … 3=Settings); clamped to range.
  final int initialTab;

  @override
  State<HomeShell> createState() => _HomeShellState();
}

class _HomeShellState extends State<HomeShell> {
  late int _index = widget.initialTab;
  bool _navVisible = true;

  /// Tablet-only destinations that have actually been opened. They stay in the
  /// stack afterwards (so their state survives switching away), but an unvisited
  /// one renders as an empty box — [IndexedStack] builds every child eagerly,
  /// and three extra screens fetching on launch is a cost nobody asked for.
  /// The four phone tabs are always built, exactly as before.
  late final Set<int> _visited = {widget.initialTab};

  /// The shell's destinations. Returns / Stock Check / Day Session are real
  /// tablet destinations (indices [kReturnsTab], [kStockCheckTab],
  /// [kDaySessionTab]) so the rail switches to them with the same instant swap
  /// as the four tabs — routing to them instead makes those links slide in as
  /// pages while the others don't.
  ///
  /// Indices must stay stable, so an unpermitted destination becomes a
  /// placeholder rather than shrinking the list. The rail never offers it.
  List<Widget> _pagesFor(BuildContext context) {
    final auth = context.read<AuthCubit>();
    Widget extra(int tab, String slug, Widget Function() build) =>
        context.isTablet && auth.hasPermission(slug) && _visited.contains(tab)
            ? build()
            : const SizedBox.shrink();
    return [
      DashboardScreen(onSelectTab: _goToTab),
      SalesListScreen(onSelectTab: _goToTab),
      const ReportsScreen(),
      SettingsScreen(onSelectTab: _goToTab),
      extra(kReturnsTab, PermissionSlug.saleReturnView, () => const SalesReturnListScreen()),
      extra(kStockCheckTab, PermissionSlug.stockCheck, () => const StockCheckListScreen()),
      extra(kDaySessionTab, PermissionSlug.daySession, () => const DaySessionScreen()),
      // No permission gate on either — everyone has a profile and a permission
      // list of their own.
      context.isTablet && _visited.contains(kProfileTab)
          ? ProfileScreen(onSelectTab: _goToTab)
          : const SizedBox.shrink(),
      context.isTablet && _visited.contains(kPermissionsTab)
          ? const PermissionsScreen()
          : const SizedBox.shrink(),
    ];
  }

  void _goToTab(int i) => setState(() {
        _index = i;
        _visited.add(i);
      });

  /// Shared by the phone and tablet scaffolds — the frosted drawer that holds
  /// every module link (tab items switch the shell, the rest push routes).
  Widget get _drawer => AstraDrawer(activeTab: _index, onSelectTab: _goToTab);

  static const _drawerScrim = Color(0x85040C09);

  bool _onScroll(UserScrollNotification n) {
    if (n.metrics.axis != Axis.vertical) return false;
    // Don't hide when the content is too short to scroll meaningfully.
    if (n.direction == ScrollDirection.reverse && _navVisible && n.metrics.maxScrollExtent > 60) {
      setState(() => _navVisible = false);
    } else if (n.direction == ScrollDirection.forward && !_navVisible) {
      setState(() => _navVisible = true);
    }
    return false;
  }

  @override
  Widget build(BuildContext context) {
    final pages = _pagesFor(context);
    // Clamped here, not in initState: whether index 4 exists depends on the size
    // class and the Stock Check permission, neither of which is safe to read there.
    final index = _index.clamp(0, pages.length - 1);

    if (context.isTablet) {
      return Scaffold(
        drawer: _drawer,
        drawerScrimColor: _drawerScrim,
        body: SafeArea(
          child: Row(
            children: [
              AstraSideRail(activeIndex: index, onSelect: _goToTab),
              Expanded(child: IndexedStack(index: index, children: pages)),
            ],
          ),
        ),
      );
    }

    return Scaffold(
      extendBody: true,
      drawer: _drawer,
      drawerScrimColor: _drawerScrim,
      body: NotificationListener<UserScrollNotification>(
        onNotification: _onScroll,
        child: IndexedStack(index: index, children: pages),
      ),
      floatingActionButtonLocation: FloatingActionButtonLocation.centerDocked,
      floatingActionButton: AnimatedSlide(
        offset: _navVisible ? Offset.zero : const Offset(0, 2.4),
        duration: const Duration(milliseconds: 260),
        curve: Curves.easeOutCubic,
        child: AnimatedOpacity(
          opacity: _navVisible ? 1 : 0,
          duration: const Duration(milliseconds: 200),
          // Centred square "+" that docks into the gap in the middle of the bar
          // (matches the preview). Tap = New Sale.
          child: AstraNavFab(onTap: () => context.push(Routes.sale)),
        ),
      ),
      bottomNavigationBar: AnimatedSlide(
        offset: _navVisible ? Offset.zero : const Offset(0, 1.8),
        duration: const Duration(milliseconds: 260),
        curve: Curves.easeOutCubic,
        child: AstraNavBar(activeIndex: index, onTap: _goToTab),
      ),
    );
  }

}
