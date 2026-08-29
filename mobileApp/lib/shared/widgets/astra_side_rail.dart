import 'package:flutter/material.dart';
import 'package:flutter/services.dart';
import 'package:go_router/go_router.dart';
import 'package:provider/provider.dart';

import 'package:invo/features/auth/logic/auth_cubit/auth_cubit.dart';
import 'package:invo/shared/domain/constants/mobile_permissions.dart';
import 'package:invo/shared/domain/helpers/responsive.dart';
import 'package:invo/shared/utils/components/theme/index.dart';
import 'package:invo/shared/utils/router/routes.dart';
import 'package:invo/shared/widgets/astra_bottom_nav.dart';
import 'package:invo/shared/logic/theme_cubit/theme_cubit.dart';
import 'package:invo/shared/widgets/qloud_logo.dart';

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
    this.flush = false,
  });

  /// Painted to the window's edges — no margin, no radius, no shadow — for
  /// every chrome except [AstraChrome.peers]. A flush rail runs under the
  /// status bar, so it takes that inset into its own padding: the shell can no
  /// longer hold it off, and without this the logomark sits under the clock.
  final bool flush;

  /// Highlighted tab, or null when the current screen isn't one of them (a
  /// pushed destination like Stock Check).
  final int? activeIndex;
  final ValueChanged<int> onSelect;

  /// Tapped on the gold "+" — defaults to starting a new sale.
  final VoidCallback? onNew;

  @override
  Widget build(BuildContext context) {
    final p = context.astra;
    final inset = MediaQuery.viewPaddingOf(context);
    return Container(
      width: 92,
      margin: flush ? EdgeInsets.zero : const EdgeInsets.fromLTRB(14, 14, 0, 14),
      padding: EdgeInsets.only(
        top: 16 + (flush ? inset.top : 0),
        bottom: 16 + (flush ? inset.bottom : 0),
      ),
      decoration: BoxDecoration(
        color: p.darkSurface,
        borderRadius: flush ? null : BorderRadius.circular(26),
        boxShadow: flush
            ? null
            : [
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
                  // The rail is painted `p.darkSurface`; see [QloudLogomark.onDark].
                  const QloudLogomark(height: 38, onDark: true),
                  const SizedBox(height: 24),
                  for (final d in railDestinations(context))
                    _link(context, p,
                        icon: d.icon,
                        label: d.label,
                        active: d.tab == activeIndex,
                        onTap: () => onSelect(d.tab)),
                  const Spacer(),
                  GestureDetector(
                    onTap: onNew ?? () => context.push(Routes.sale),
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
/// Rail + content, assembled the way this device's [AstraChrome] asks for.
///
/// The home shell and every railed route both used to inline the same
/// `SafeArea(Row([rail, Expanded(child)]))`, which is why the two idioms could
/// drift apart in the first place. There is now one place that decides how the
/// window is put together, and four answers it can give.
class AstraRailShell extends StatelessWidget {
  const AstraRailShell({
    super.key,
    required this.activeIndex,
    required this.onSelect,
    required this.child,
  });

  final int? activeIndex;
  final ValueChanged<int> onSelect;
  final Widget child;

  @override
  Widget build(BuildContext context) {
    final p = context.astra;
    final chrome = context.select<ThemeCubit, AstraChrome>((c) => c.state.chrome);
    final rail = AstraSideRail(
      activeIndex: activeIndex,
      onSelect: onSelect,
      flush: chrome.railIsFlush,
    );

    // The screens beyond this point were all written against a shell that had
    // already taken the top inset off. A flush rail paints under the status bar
    // itself, so the inset is re-applied to the content side only — otherwise
    // every tablet screen would have to learn about insets at once.
    Widget content({bool top = true}) => SafeArea(top: top, bottom: false, child: child);

    final Widget body = switch (chrome) {
      // Rail flush and dark to the edges; the content lifts off it as one
      // rounded surface on an even gutter. The card is held below the status
      // bar rather than sliding under it, so the clock never sits on content.
      AstraChrome.insetCanvas => ColoredBox(
          color: p.darkSurface,
          child: Row(
            children: [
              rail,
              Expanded(
                // An EVEN gutter on all four sides. With no gap on the left the
                // card butted against the rail while its rounded corner curved
                // away from it, opening a dark wedge in the crook — the same
                // corner this chrome exists to fix, just moved 92pt right. A
                // card that floats has to float on every side.
                child: Padding(
                  padding: EdgeInsets.fromLTRB(10, MediaQuery.viewPaddingOf(context).top + 10, 10, 10),
                  child: ClipRRect(
                    borderRadius: BorderRadius.circular(22),
                    child: ColoredBox(color: p.canvas, child: content(top: false)),
                  ),
                ),
              ),
            ],
          ),
        ),

      // Nothing rounded against anything: both sides run corner to corner.
      AstraChrome.docked => Row(children: [rail, Expanded(child: content())]),

      // The original look, made consistent — the content now floats on the same
      // inset, the same radius and the same shadow as the rail.
      AstraChrome.peers => SafeArea(
          bottom: false,
          child: Row(
            children: [
              rail,
              Expanded(
                child: Padding(
                  padding: const EdgeInsets.fromLTRB(12, 14, 14, 14),
                  child: ClipRRect(
                    borderRadius: BorderRadius.circular(26),
                    child: ColoredBox(color: p.canvas, child: child),
                  ),
                ),
              ),
            ],
          ),
        ),

      // One card holding both, split by the rail's own edge. Only the outer
      // corners are rounded, so there is no interior corner left to resolve.
      AstraChrome.unified => SafeArea(
          child: Padding(
            padding: const EdgeInsets.all(14),
            child: ClipRRect(
              borderRadius: BorderRadius.circular(26),
              child: ColoredBox(
                color: p.canvas,
                child: Row(children: [rail, Expanded(child: child)]),
              ),
            ),
          ),
        ),
    };

    // With the rail behind the clock the default dark-on-light status icons
    // vanish into it, so the strip's own brightness decides them — not the
    // page's.
    return AnnotatedRegion<SystemUiOverlayStyle>(
      value: chrome.darkStatusStrip
          ? SystemUiOverlayStyle.light
          : (p.isDark ? SystemUiOverlayStyle.light : SystemUiOverlayStyle.dark),
      child: body,
    );
  }
}

class TabletRailScaffold extends StatelessWidget {
  const TabletRailScaffold({super.key, required this.child, this.activeTab});

  final Widget child;

  /// Which rail tab to highlight, if this destination maps to one.
  final int? activeTab;

  @override
  Widget build(BuildContext context) {
    if (!context.isTablet) return child;
    // No `backgroundColor` override: the strip the rail sits on should be the
    // page canvas, same as in the home shell. Transparent would show whatever
    // route is underneath.
    return Scaffold(
      body: AstraRailShell(
        activeIndex: activeTab,
        onSelect: (i) => context.go(Routes.homeTab(i)),
        child: child,
      ),
    );
  }
}
