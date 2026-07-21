import 'package:flutter/material.dart';
import 'package:flutter/rendering.dart' show ScrollDirection;
import 'package:go_router/go_router.dart';

import 'package:invo/shared/domain/helpers/responsive.dart';
import 'package:invo/shared/utils/components/theme/index.dart';
import 'package:invo/shared/widgets/astra_bottom_nav.dart';
import 'package:invo/shared/widgets/astra_drawer.dart';
import 'package:invo/shared/widgets/invo_logo.dart';
import 'package:invo/features/admin/screens/v3/dashboard_screen.dart';
import 'package:invo/features/admin/screens/v3/reports_screen.dart';
import 'package:invo/features/sales/screens/v3/sales_list_screen.dart';
import 'package:invo/features/settings/screens/v3/settings_screen.dart';

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
  late int _index = widget.initialTab.clamp(0, _pages.length - 1);
  bool _navVisible = true;

  late final List<Widget> _pages = [
    DashboardScreen(onSelectTab: _goToTab),
    const SalesListScreen(),
    const ReportsScreen(),
    const SettingsScreen(),
  ];

  void _goToTab(int i) => setState(() => _index = i);

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
    final p = context.astra;

    if (context.isTablet) {
      return Scaffold(
        drawer: _drawer,
        drawerScrimColor: _drawerScrim,
        body: SafeArea(
          child: Row(
            children: [
              _sideRail(p),
              Expanded(child: IndexedStack(index: _index, children: _pages)),
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
        child: IndexedStack(index: _index, children: _pages),
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
          child: AstraNavFab(onTap: () => context.push('/sale')),
        ),
      ),
      bottomNavigationBar: AnimatedSlide(
        offset: _navVisible ? Offset.zero : const Offset(0, 1.8),
        duration: const Duration(milliseconds: 260),
        curve: Curves.easeOutCubic,
        child: AstraNavBar(activeIndex: _index, onTap: (i) => setState(() => _index = i)),
      ),
    );
  }

  Widget _sideRail(AstraPalette p) {
    return Container(
      width: 92,
      margin: const EdgeInsets.fromLTRB(14, 14, 0, 14),
      padding: const EdgeInsets.symmetric(vertical: 16),
      decoration: BoxDecoration(
        color: p.darkSurface,
        borderRadius: BorderRadius.circular(26),
        boxShadow: [BoxShadow(color: Colors.black.withValues(alpha: 0.3), blurRadius: 36, offset: const Offset(0, 14))],
      ),
      // In landscape on a phone the width still trips `isTablet`, but the height
      // collapses — so the rail must scroll when short and still push "New" to
      // the bottom when there's room. minHeight + IntrinsicHeight keeps the
      // Spacer working inside the scroll view.
      child: LayoutBuilder(
        builder: (context, c) => SingleChildScrollView(
          child: ConstrainedBox(
            constraints: BoxConstraints(minHeight: c.maxHeight),
            child: IntrinsicHeight(
              child: Column(
                children: [
                  const SizedBox(height: 4),
                  const InvoLogomark(height: 38),
                  const SizedBox(height: 24),
                  for (var i = 0; i < astraNavTabs.length; i++) _railItem(p, i),
                  const Spacer(),
                  GestureDetector(
                    onTap: () => context.push('/sale'),
                    child: Container(
                      width: 52,
                      height: 52,
                      decoration: BoxDecoration(gradient: p.accentGradient, shape: BoxShape.circle),
                      child: Icon(Icons.add, color: p.primaryDark, size: 24),
                    ),
                  ),
                  const SizedBox(height: 4),
                  Text('New', style: ui(size: 9, weight: FontWeight.w700, color: Colors.white.withValues(alpha: 0.7))),
                ],
              ),
            ),
          ),
        ),
      ),
    );
  }

  Widget _railItem(AstraPalette p, int i) {
    final active = i == _index;
    return GestureDetector(
      onTap: () => setState(() => _index = i),
      behavior: HitTestBehavior.opaque,
      child: Padding(
        padding: const EdgeInsets.symmetric(vertical: 9),
        child: Column(
          children: [
            Container(
              width: 46,
              height: 40,
              decoration: BoxDecoration(
                color: active ? Colors.white.withValues(alpha: 0.12) : Colors.transparent,
                borderRadius: BorderRadius.circular(13),
              ),
              child: Icon(astraNavTabs[i].icon, size: 20, color: active ? p.accent : Colors.white.withValues(alpha: 0.55)),
            ),
            const SizedBox(height: 4),
            Text(astraNavTabs[i].label,
                style: ui(size: 8.5, weight: active ? FontWeight.w700 : FontWeight.w600, color: active ? p.accent : Colors.white.withValues(alpha: 0.55))),
          ],
        ),
      ),
    );
  }
}
