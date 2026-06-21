import 'dart:ui' show ImageFilter;

import 'package:flutter/material.dart';
import 'package:flutter/rendering.dart' show ScrollDirection;
import 'package:go_router/go_router.dart';

import '../../core/responsive.dart';
import '../../theme/palette.dart';
import '../../theme/theme.dart';
import '../../widgets/invo_logo.dart';
import '../admin/dashboard_screen.dart';
import '../admin/reports_screen.dart';
import '../sales/sales_list_screen.dart';
import '../settings/settings_screen.dart';

/// Adaptive admin shell: a glossy floating bottom nav on phones (Instagram-style:
/// hides on scroll-down, returns on scroll-up), a left side-rail on tablets.
class HomeShell extends StatefulWidget {
  const HomeShell({super.key});
  @override
  State<HomeShell> createState() => _HomeShellState();
}

class _HomeShellState extends State<HomeShell> {
  int _index = 0;
  bool _navVisible = true;

  static const _tabs = [
    (icon: Icons.grid_view, label: 'Home'),
    (icon: Icons.receipt_long, label: 'Sales'),
    (icon: Icons.bar_chart, label: 'Reports'),
    (icon: Icons.settings, label: 'Settings'),
  ];

  static const _pages = [
    DashboardScreen(),
    SalesListScreen(),
    ReportsScreen(),
    SettingsScreen(),
  ];

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
          child: GestureDetector(
            onTap: () => context.push('/sale'),
            child: Container(
              width: 58,
              height: 58,
              decoration: BoxDecoration(
                gradient: p.accentGradient,
                borderRadius: BorderRadius.circular(19),
                boxShadow: [
                  BoxShadow(color: p.accent.withValues(alpha: 0.45), blurRadius: 20, spreadRadius: -2, offset: const Offset(0, 10)),
                ],
              ),
              child: const Icon(Icons.add, color: Colors.white, size: 28),
            ),
          ),
        ),
      ),
      bottomNavigationBar: AnimatedSlide(
        offset: _navVisible ? Offset.zero : const Offset(0, 1.8),
        duration: const Duration(milliseconds: 260),
        curve: Curves.easeOutCubic,
        child: _bottomNav(p),
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
                  for (var i = 0; i < _tabs.length; i++) _railItem(p, i),
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
              child: Icon(_tabs[i].icon, size: 20, color: active ? p.accent : Colors.white.withValues(alpha: 0.55)),
            ),
            const SizedBox(height: 4),
            Text(_tabs[i].label,
                style: ui(size: 8.5, weight: active ? FontWeight.w700 : FontWeight.w600, color: active ? p.accent : Colors.white.withValues(alpha: 0.55))),
          ],
        ),
      ),
    );
  }

  /// Premium frosted-glass bottom nav ("Aurora underline") — now skin-aware: a
  /// light translucent glass bar for the light skins (Aurora / Luminous /
  /// Emerald) and the deep gold-on-black bar for Editorial Noir. A glowing
  /// gradient underline glides beneath the active tab while its icon lifts.
  Widget _bottomNav(AstraPalette p) {
    const navHeight = 68.0;
    const hPad = 12.0;
    const indWidth = 30.0;
    final light = !p.isDark;
    final accent = light ? p.primary : p.accent; // underline + active colour
    final sheen = Colors.white.withValues(alpha: light ? 0.65 : 0.30);
    final navColors = light
        ? [Colors.white.withValues(alpha: 0.82), Colors.white.withValues(alpha: 0.70)]
        : [
            Color.lerp(p.darkSurface, p.primary, 0.28)!.withValues(alpha: 0.80),
            p.darkSurface.withValues(alpha: 0.92),
          ];
    final navBorder = light ? Colors.white.withValues(alpha: 0.75) : Colors.white.withValues(alpha: 0.14);
    final navShadow = light
        ? [
            BoxShadow(color: const Color(0xFF26306B).withValues(alpha: 0.16), blurRadius: 30, spreadRadius: -10, offset: const Offset(0, 14)),
            BoxShadow(color: accent.withValues(alpha: 0.12), blurRadius: 22, spreadRadius: -12, offset: const Offset(0, 6)),
          ]
        : [
            BoxShadow(color: Colors.black.withValues(alpha: 0.42), blurRadius: 36, spreadRadius: -8, offset: const Offset(0, 16)),
            BoxShadow(color: accent.withValues(alpha: 0.10), blurRadius: 24, spreadRadius: -12, offset: const Offset(0, 6)),
          ];

    return SafeArea(
      child: Padding(
        padding: const EdgeInsets.fromLTRB(16, 0, 16, 14),
        child: ClipRRect(
          borderRadius: BorderRadius.circular(28),
          child: BackdropFilter(
            filter: ImageFilter.blur(sigmaX: 22, sigmaY: 22),
            child: Container(
              height: navHeight,
              decoration: BoxDecoration(
                gradient: LinearGradient(
                  begin: Alignment.topCenter,
                  end: Alignment.bottomCenter,
                  colors: navColors,
                ),
                borderRadius: BorderRadius.circular(28),
                border: Border.all(color: navBorder),
                boxShadow: navShadow,
              ),
              child: LayoutBuilder(
                builder: (context, c) {
                  const gap = 66.0; // centre slot the docked "+" button sits in
                  final tabW = (c.maxWidth - hPad * 2 - gap) / _tabs.length;
                  // Tabs 2 & 3 sit after the centre gap.
                  double tabLeft(int i) => hPad + i * tabW + (i >= 2 ? gap : 0);
                  final indLeft = tabLeft(_index) + (tabW - indWidth) / 2;
                  return Stack(
                    children: [
                      // Glass sheen along the top edge.
                      Positioned(
                        left: 28,
                        right: 28,
                        top: 0,
                        child: Container(
                          height: 1,
                          decoration: BoxDecoration(
                            gradient: LinearGradient(colors: [Colors.transparent, sheen, Colors.transparent]),
                          ),
                        ),
                      ),
                      Positioned.fill(
                        child: Padding(
                          padding: const EdgeInsets.symmetric(horizontal: hPad),
                          child: Row(children: [
                            _navItem(p, 0),
                            _navItem(p, 1),
                            const SizedBox(width: gap),
                            _navItem(p, 2),
                            _navItem(p, 3),
                          ]),
                        ),
                      ),
                      // Aurora underline — glides under the active tab.
                      AnimatedPositioned(
                        duration: const Duration(milliseconds: 380),
                        curve: Curves.easeOutCubic,
                        left: indLeft,
                        bottom: 8,
                        width: indWidth,
                        height: 4,
                        child: DecoratedBox(
                          decoration: BoxDecoration(
                            borderRadius: BorderRadius.circular(4),
                            gradient: LinearGradient(
                              colors: [
                                accent.withValues(alpha: 0.0),
                                accent,
                                Color.lerp(accent, Colors.white, 0.4)!,
                                accent,
                                accent.withValues(alpha: 0.0),
                              ],
                              stops: const [0, 0.2, 0.5, 0.8, 1],
                            ),
                            boxShadow: [BoxShadow(color: accent.withValues(alpha: 0.6), blurRadius: 14, spreadRadius: 0.5)],
                          ),
                        ),
                      ),
                    ],
                  );
                },
              ),
            ),
          ),
        ),
      ),
    );
  }

  /// A single bottom-nav tab — the active one lifts slightly and its icon
  /// glows gold (the underline supplies the rest of the "aurora" accent).
  Widget _navItem(AstraPalette p, int i) {
    final active = i == _index;
    final light = !p.isDark;
    final activeColor = light ? p.primary : p.accent;
    final color = active ? activeColor : (light ? p.textMuted : Colors.white.withValues(alpha: 0.55));
    return Expanded(
      child: GestureDetector(
        onTap: () => setState(() => _index = i),
        behavior: HitTestBehavior.opaque,
        child: AnimatedSlide(
          offset: active ? const Offset(0, -0.12) : Offset.zero,
          duration: const Duration(milliseconds: 320),
          curve: Curves.easeOutCubic,
          child: Column(
            mainAxisSize: MainAxisSize.min,
            mainAxisAlignment: MainAxisAlignment.center,
            children: [
              Icon(
                _tabs[i].icon,
                size: 20,
                color: color,
                shadows: active ? [Shadow(color: activeColor.withValues(alpha: 0.55), blurRadius: 14)] : null,
              ),
              const SizedBox(height: 4),
              Text(
                _tabs[i].label,
                style: ui(size: 9, weight: active ? FontWeight.w800 : FontWeight.w600, color: color),
              ),
            ],
          ),
        ),
      ),
    );
  }
}
