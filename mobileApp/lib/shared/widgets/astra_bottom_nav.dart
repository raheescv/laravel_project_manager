import 'dart:ui' show ImageFilter;

import 'package:flutter/material.dart';

import 'package:invo/shared/utils/components/theme/index.dart';

/// The four primary destinations, shared by the bottom nav and the tablet rail.
const astraNavTabs = [
  (icon: Icons.grid_view, label: 'Home'),
  (icon: Icons.receipt_long, label: 'Sales'),
  (icon: Icons.bar_chart, label: 'Reports'),
  (icon: Icons.settings, label: 'Settings'),
];

/// Premium frosted-glass bottom nav ("Aurora underline"), shared by the home
/// shell and any standalone page that wants the app's primary navigation. A
/// glowing gradient underline glides beneath the active tab while its icon
/// lifts. [activeIndex] highlights a tab (pass -1 for none); [onTap] reports
/// the tapped index. Leaves a centre gap for [AstraNavFab].
class AstraNavBar extends StatelessWidget {
  const AstraNavBar({super.key, required this.activeIndex, required this.onTap});

  final int activeIndex;
  final ValueChanged<int> onTap;

  @override
  Widget build(BuildContext context) {
    final p = context.astra;
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
                  final tabW = (c.maxWidth - hPad * 2 - gap) / astraNavTabs.length;
                  // Tabs 2 & 3 sit after the centre gap.
                  double tabLeft(int i) => hPad + i * tabW + (i >= 2 ? gap : 0);
                  final hasActive = activeIndex >= 0 && activeIndex < astraNavTabs.length;
                  final indLeft = tabLeft(hasActive ? activeIndex : 0) + (tabW - indWidth) / 2;
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
                      if (hasActive)
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
  /// glows (the underline supplies the rest of the "aurora" accent).
  Widget _navItem(AstraPalette p, int i) {
    final active = i == activeIndex;
    final light = !p.isDark;
    final activeColor = light ? p.primary : p.accent;
    final color = active ? activeColor : (light ? p.textMuted : Colors.white.withValues(alpha: 0.55));
    return Expanded(
      child: GestureDetector(
        onTap: () => onTap(i),
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
                astraNavTabs[i].icon,
                size: 20,
                color: color,
                shadows: active ? [Shadow(color: activeColor.withValues(alpha: 0.55), blurRadius: 14)] : null,
              ),
              const SizedBox(height: 4),
              Text(
                astraNavTabs[i].label,
                maxLines: 1,
                overflow: TextOverflow.ellipsis,
                textAlign: TextAlign.center,
                style: ui(size: 9, weight: active ? FontWeight.w800 : FontWeight.w600, color: color),
              ),
            ],
          ),
        ),
      ),
    );
  }
}

/// The docked accent "+" button that nests in the centre gap of [AstraNavBar].
/// Pair it with `FloatingActionButtonLocation.centerDocked`.
class AstraNavFab extends StatelessWidget {
  const AstraNavFab({super.key, required this.onTap});

  final VoidCallback onTap;

  @override
  Widget build(BuildContext context) {
    final p = context.astra;
    return GestureDetector(
      onTap: onTap,
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
    );
  }
}
