import 'package:flutter/material.dart';
import 'package:go_router/go_router.dart';
import 'package:provider/provider.dart';

import 'package:invo/features/auth/logic/auth_cubit/auth_cubit.dart';
import 'package:invo/shared/domain/constants/mobile_permissions.dart';
import 'package:invo/shared/domain/helpers/responsive.dart';
import 'package:invo/shared/utils/components/theme/index.dart';
import 'package:invo/shared/widgets/astra_bottom_nav.dart';
import 'package:invo/shared/widgets/invo_logo.dart';

// Tablet-only shell destinations. Indices continue after the four phone tabs in
// [astraNavTabs] (0–3), which are fixed — the phone bottom nav and `/home?tab=N`
// both depend on them. Display order in the rail is independent of these.
const int kReturnsTab = 4;
const int kStockCheckTab = 5;
const int kDaySessionTab = 6;

/// My Profile. A shell destination like the three above — reached from the
/// dashboard avatar and the drawer, so it swaps in place instead of sliding a
/// page over the shell — but deliberately NOT a rail link: the rail is for
/// modules, and the avatar already reads as "me".
const int kProfileTab = 7;

/// My Permissions — same reasoning as [kProfileTab]: reached from Profile and
/// from Settings, so it swaps in place rather than sliding over the shell.
const int kPermissionsTab = 8;

/// The rail's destinations, in display order.
///
/// On a tablet, Returns / Stock Check / Day Session are real shell destinations
/// rather than routes. That matters: every rail item has to switch the same way
/// — an instant [IndexedStack] swap — or the routed ones slide in as pages while
/// the tab ones don't, and the rail feels inconsistent. Each is gated on the
/// same permission as its route.
List<({IconData icon, String label, int tab})> railDestinations(BuildContext context) {
  final auth = context.read<AuthCubit>();
  ({IconData icon, String label, int tab}) tab(int i) =>
      (icon: astraNavTabs[i].icon, label: astraNavTabs[i].label, tab: i);
  return [
    tab(0),
    tab(1),
    if (auth.hasPermission(PermissionSlug.saleReturnView))
      (icon: Icons.assignment_return_outlined, label: 'Returns', tab: kReturnsTab),
    if (auth.hasPermission(PermissionSlug.stockCheck))
      (icon: Icons.fact_check_outlined, label: 'Stock Check', tab: kStockCheckTab),
    tab(2),
    if (auth.hasPermission(PermissionSlug.daySession))
      (icon: Icons.schedule, label: 'Day Session', tab: kDaySessionTab),
    tab(3),
  ];
}

/// The tablet side-rail — the app's primary navigation, and the only chrome a
/// tablet screen gets (there is no header band; see the preview in
/// `docs/mobile-tablet-screens.html`). Phones use [AstraNavBar] instead.
///
/// Lives outside the home shell so *pushed* destinations (Returns, Day Session,
/// receipts) can show the same rail — without it they render as full-bleed
/// screens and the user loses navigation until they go back.
class AstraSideRail extends StatelessWidget {
  const AstraSideRail({
    super.key,
    required this.activeIndex,
    required this.onSelect,
    this.onNew,
  });

  /// Highlighted tab, or null when the current screen isn't one of them (a
  /// pushed destination like Stock Check).
  final int? activeIndex;
  final ValueChanged<int> onSelect;

  /// Tapped on the gold "+" — defaults to starting a new sale.
  final VoidCallback? onNew;

  @override
  Widget build(BuildContext context) {
    final p = context.astra;
    return Container(
      width: 92,
      margin: const EdgeInsets.fromLTRB(14, 14, 0, 14),
      padding: const EdgeInsets.symmetric(vertical: 16),
      decoration: BoxDecoration(
        color: p.darkSurface,
        borderRadius: BorderRadius.circular(26),
        boxShadow: [
          BoxShadow(color: Colors.black.withValues(alpha: 0.3), blurRadius: 36, offset: const Offset(0, 14)),
        ],
      ),
      // In landscape on a phone the width can still trip `isTablet` while the
      // height collapses — so the rail must scroll when short and still push
      // "New" to the bottom when there's room.
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
                  for (final d in railDestinations(context))
                    _link(context, p,
                        icon: d.icon,
                        label: d.label,
                        active: d.tab == activeIndex,
                        onTap: () => onSelect(d.tab)),
                  const Spacer(),
                  GestureDetector(
                    onTap: onNew ?? () => context.push('/sale'),
                    child: Container(
                      width: 52,
                      height: 52,
                      decoration: BoxDecoration(gradient: p.accentGradient, shape: BoxShape.circle),
                      child: Icon(Icons.add, color: p.primaryDark, size: 24),
                    ),
                  ),
                  const SizedBox(height: 4),
                  Text('New',
                      style: ui(size: 9, weight: FontWeight.w700, color: Colors.white.withValues(alpha: 0.7))),
                ],
              ),
            ),
          ),
        ),
      ),
    );
  }

  Widget _link(
    BuildContext context,
    AstraPalette p, {
    required IconData icon,
    required String label,
    required bool active,
    required VoidCallback? onTap,
  }) {
    return GestureDetector(
      onTap: onTap,
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
              child: Icon(icon, size: 20, color: active ? p.accent : Colors.white.withValues(alpha: 0.55)),
            ),
            const SizedBox(height: 4),
            Text(label,
                style: ui(
                    size: 8.5,
                    weight: active ? FontWeight.w700 : FontWeight.w600,
                    color: active ? p.accent : Colors.white.withValues(alpha: 0.55))),
          ],
        ),
      ),
    );
  }
}

/// Wraps a *pushed* destination with the side-rail on a tablet, so navigation
/// stays reachable instead of the screen taking over the whole window. A no-op
/// on phones, where the pushed screen keeps its own back button and bottom nav.
///
/// Rail taps leave the pushed stack and land on the shell's matching tab.
class TabletRailScaffold extends StatelessWidget {
  const TabletRailScaffold({super.key, required this.child, this.activeTab});

  final Widget child;

  /// Which rail tab to highlight, if this destination maps to one.
  final int? activeTab;

  @override
  Widget build(BuildContext context) {
    if (!context.isTablet) return child;
    // No `backgroundColor` override: the strip the rail floats on should be the
    // page canvas, same as in the home shell. Transparent would show whatever
    // route is underneath.
    return Scaffold(
      body: SafeArea(
        child: Row(
          children: [
            AstraSideRail(
              activeIndex: activeTab,
              onSelect: (i) => context.go('/home?tab=$i'),
            ),
            Expanded(child: child),
          ],
        ),
      ),
    );
  }
}
