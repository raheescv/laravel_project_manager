import 'dart:ui' show ImageFilter;

import 'package:flutter/material.dart';
import 'package:flutter/rendering.dart' show ScrollDirection;

import 'package:invo/shared/utils/components/theme/index.dart';
import 'package:invo/features/technician/screens/v3/technician_dashboard_screen.dart';
import 'package:invo/features/technician/screens/v3/complaints_list_screen.dart';
import 'package:invo/features/settings/screens/v3/technician_settings_screen.dart';

/// Primary technician shell: Dashboard · My Jobs · Settings, behind a frosted
/// glass bottom nav (the app's "Aurora" style, trimmed to three tabs — no POS
/// centre FAB).
class TechnicianShell extends StatefulWidget {
  const TechnicianShell({super.key, this.initialTab = 0});

  final int initialTab;

  @override
  State<TechnicianShell> createState() => _TechnicianShellState();
}

class _TechnicianShellState extends State<TechnicianShell> {
  late int _index = widget.initialTab.clamp(0, _pages.length - 1);
  bool _navHidden = false;

  static const _pages = [
    TechnicianDashboardScreen(),
    ComplaintsListScreen(),
    TechnicianSettingsScreen(),
  ];

  static const _tabs = [
    (icon: Icons.grid_view_rounded, label: 'Dashboard'),
    (icon: Icons.assignment_outlined, label: 'My Jobs'),
    (icon: Icons.settings_outlined, label: 'Settings'),
  ];

  /// Nav slides away while the technician scrolls down a page and glides back
  /// the moment they scroll up or settle near the top. Scroll notifications
  /// bubble up here from whichever tab's list is scrolling.
  bool _onUserScroll(UserScrollNotification n) {
    if (n.metrics.axis != Axis.vertical) return false;
    final hide = switch (n.direction) {
      ScrollDirection.reverse => n.metrics.pixels > 48,
      ScrollDirection.forward => false,
      ScrollDirection.idle => _navHidden && n.metrics.pixels > 48,
    };
    if (hide != _navHidden) setState(() => _navHidden = hide);
    return false;
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      extendBody: true,
      backgroundColor: Colors.transparent,
      body: NotificationListener<UserScrollNotification>(
        onNotification: _onUserScroll,
        child: IndexedStack(index: _index, children: _pages),
      ),
      bottomNavigationBar: AnimatedSlide(
        offset: _navHidden ? const Offset(0, 1.3) : Offset.zero,
        duration: const Duration(milliseconds: 450),
        curve: Curves.easeOutQuint,
        child: AnimatedOpacity(
          opacity: _navHidden ? 0 : 1,
          duration: const Duration(milliseconds: 300),
          curve: Curves.easeOut,
          child: _navBar(context),
        ),
      ),
    );
  }

  Widget _navBar(BuildContext context) {
    final p = context.astra;
    final light = !p.isDark;
    final accent = light ? p.primary : p.accent;
    final sheen = Colors.white.withValues(alpha: light ? 0.65 : 0.30);
    final navColors = light
        ? [Colors.white.withValues(alpha: 0.84), Colors.white.withValues(alpha: 0.70)]
        : [
            Color.lerp(p.darkSurface, p.primary, 0.28)!.withValues(alpha: 0.80),
            p.darkSurface.withValues(alpha: 0.92),
          ];
    return SafeArea(
      child: Padding(
        padding: const EdgeInsets.fromLTRB(16, 0, 16, 14),
        child: ClipRRect(
          borderRadius: BorderRadius.circular(28),
          child: BackdropFilter(
            filter: ImageFilter.blur(sigmaX: 22, sigmaY: 22),
            child: Container(
              height: 68,
              decoration: BoxDecoration(
                gradient: LinearGradient(
                  begin: Alignment.topCenter,
                  end: Alignment.bottomCenter,
                  colors: navColors,
                ),
                borderRadius: BorderRadius.circular(28),
                border: Border.all(color: light ? Colors.white.withValues(alpha: 0.75) : Colors.white.withValues(alpha: 0.14)),
                boxShadow: [
                  BoxShadow(
                    color: light ? const Color(0xFF26306B).withValues(alpha: 0.16) : Colors.black.withValues(alpha: 0.42),
                    blurRadius: 30,
                    spreadRadius: -10,
                    offset: const Offset(0, 14),
                  ),
                  BoxShadow(
                    color: accent.withValues(alpha: light ? 0.12 : 0.10),
                    blurRadius: 22,
                    spreadRadius: -12,
                    offset: const Offset(0, 6),
                  ),
                ],
              ),
              child: LayoutBuilder(
                builder: (context, c) {
                  const hPad = 10.0;
                  const indWidth = 32.0;
                  final tabW = (c.maxWidth - hPad * 2) / _tabs.length;
                  final indLeft = hPad + _index * tabW + (tabW - indWidth) / 2;
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
                      // Soft accent halo that glides with the active tab.
                      AnimatedPositioned(
                        duration: const Duration(milliseconds: 380),
                        curve: Curves.easeOutCubic,
                        left: hPad + _index * tabW,
                        top: 0,
                        bottom: 0,
                        width: tabW,
                        child: IgnorePointer(
                          child: DecoratedBox(
                            decoration: BoxDecoration(
                              gradient: RadialGradient(
                                radius: 0.95,
                                colors: [
                                  accent.withValues(alpha: light ? 0.13 : 0.20),
                                  accent.withValues(alpha: 0),
                                ],
                              ),
                            ),
                          ),
                        ),
                      ),
                      Positioned.fill(
                        child: Padding(
                          padding: const EdgeInsets.symmetric(horizontal: hPad),
                          child: Row(
                            children: [
                              for (var i = 0; i < _tabs.length; i++) _item(context, i, accent),
                            ],
                          ),
                        ),
                      ),
                      // Aurora underline — glides beneath the active tab.
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

  Widget _item(BuildContext context, int i, Color accent) {
    final p = context.astra;
    final light = !p.isDark;
    final active = i == _index;
    final color = active ? accent : (light ? p.textMuted : Colors.white.withValues(alpha: 0.55));
    return Expanded(
      child: GestureDetector(
        onTap: () => setState(() => _index = i),
        behavior: HitTestBehavior.opaque,
        child: AnimatedSlide(
          offset: active ? const Offset(0, -0.1) : Offset.zero,
          duration: const Duration(milliseconds: 320),
          curve: Curves.easeOutCubic,
          child: Column(
            mainAxisAlignment: MainAxisAlignment.center,
            children: [
              AnimatedScale(
                scale: active ? 1.12 : 1,
                duration: const Duration(milliseconds: 320),
                curve: Curves.easeOutBack,
                child: Icon(
                  _tabs[i].icon,
                  size: 21,
                  color: color,
                  shadows: active ? [Shadow(color: accent.withValues(alpha: 0.55), blurRadius: 14)] : null,
                ),
              ),
              const SizedBox(height: 4),
              Text(_tabs[i].label, style: ui(size: 9.5, weight: active ? FontWeight.w800 : FontWeight.w600, color: color)),
            ],
          ),
        ),
      ),
    );
  }
}
