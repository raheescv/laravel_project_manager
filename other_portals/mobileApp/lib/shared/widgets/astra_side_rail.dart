import 'package:flutter/material.dart';
import 'package:flutter/services.dart';
import 'package:go_router/go_router.dart';
import 'package:provider/provider.dart';

import 'package:invo/features/auth/logic/auth_cubit/auth_cubit.dart';
import 'package:invo/shared/domain/constants/global_variables.dart';
import 'package:invo/shared/domain/constants/mobile_permissions.dart';
import 'package:invo/shared/domain/helpers/responsive.dart';
import 'package:invo/shared/utils/components/theme/index.dart';
import 'package:invo/shared/utils/local_storage/local_storage_service.dart';
import 'package:invo/shared/utils/router/routes.dart';
import 'package:invo/shared/widgets/astra_bottom_nav.dart';
import 'package:invo/shared/widgets/astra_new_sale_fab.dart';
import 'package:invo/shared/logic/theme_cubit/theme_cubit.dart';
import 'package:invo/shared/widgets/qloud_logo.dart';
import 'package:invo/shared/widgets/astra_widgets.dart';

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

/// Link Student Card — a real shell destination too, for the same reason as
/// Returns / Stock Check / Day Session: a rail item that pushed a route instead
/// slid in as a full page while every other item swapped instantly in place,
/// which read as leaving the app rather than switching a section of it.
const int kLinkCardTab = 9;

/// Queue tokens (`sale.queue token`): a shell destination on tablet so the
/// kiosk reaches it from the rail like any other module.
const int kTokenTab = 10;

/// Settings → Catalog: Products and Services. Shell destinations on both size classes, so they keep their
/// search, filters and scroll while the user visits other modules — the same
/// instant swap as Returns or Stock Check, never a page that is rebuilt on the
/// way back. Not rail links: they live under Settings, which the rail and the
/// bottom nav highlight while one is open.
const int kProductsTab = 11;
const int kServicesTab = 12;

/// The Add / Edit form of each type — a shell page too, so a half-filled form
/// is still there after a trip to Sales and back.
const int kProductFormTab = 13;
const int kServiceFormTab = 14;
const kSettingsChildTabs = {kProductsTab, kServicesTab, kProductFormTab, kServiceFormTab};

/// The rail's scrolling destinations, in display order. Dashboard and Settings
/// are not in it: the pinned logo is the way home, and Settings is pinned in
/// the footer beside Log out.
///
/// Every item is a real shell destination ([tab]) rather than a route, an
/// instant [IndexedStack] swap. A routed item would slide in as a page while
/// the others don't, and the rail would feel inconsistent. Each is gated on
/// the same permission as its route.
List<({IconData icon, String label, int tab})> railDestinations(BuildContext context) {
  final auth = context.read<AuthCubit>();
  ({IconData icon, String label, int tab}) tab(int i) =>
      (icon: astraNavTabs[i].icon, label: astraNavTabs[i].label, tab: i);
  return [
    tab(1),
    if (auth.hasPermission(PermissionSlug.saleReturnView))
      (icon: Icons.assignment_return_outlined, label: 'Returns', tab: kReturnsTab),
    if (auth.hasPermission(PermissionSlug.stockCheck))
      (icon: Icons.fact_check_outlined, label: 'Stock Check', tab: kStockCheckTab),
    tab(2),
    if (auth.hasPermission(PermissionSlug.daySession))
      (icon: Icons.schedule, label: 'Day Session', tab: kDaySessionTab),
    if (auth.hasPermission(PermissionSlug.tokenIssue))
      (icon: Icons.confirmation_number_outlined, label: 'Token', tab: kTokenTab),
    if (serviceLocator<LocalStorageService>().schoolEnabled &&
        auth.hasPermission(PermissionSlug.studentCardAssign))
      (icon: Icons.nfc, label: 'Link Card', tab: kLinkCardTab),
  ];
}

/// The tablet side-rail — the app's primary navigation, and the only chrome a
/// tablet screen gets (there is no header band; see the preview in
/// `docs/mobile-tablet-screens.html`). Phones use [AstraNavBar] instead.
///
/// Lives outside the home shell so *pushed* destinations (Returns, Day Session,
/// receipts) can show the same rail — without it they render as full-bleed
/// screens and the user loses navigation until they go back.
///
/// "Ink Notch" (direction C of `docs/mobile-tablet-rail-fab-preview.html`):
/// both ends are pinned — the logomark (tap → Dashboard) on top, Settings and a
/// small flat Log out at the foot — and only the modules between them scroll.
/// No tile behind the active item; an accent notch on the rail edge marks it.
/// New Sale is no longer here: it floats over the content ([AstraNewSaleFab]).
class AstraSideRail extends StatelessWidget {
  const AstraSideRail({
    super.key,
    required this.activeIndex,
    required this.onSelect,
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
      child: Column(
        children: [
          _brand(p),
          // Soft fades at both edges so a list that runs under the pinned ends
          // reads as scrolling rather than cut off.
          Expanded(
            child: ShaderMask(
              blendMode: BlendMode.dstIn,
              shaderCallback: (r) => const LinearGradient(
                begin: Alignment.topCenter,
                end: Alignment.bottomCenter,
                colors: [Colors.transparent, Colors.black, Colors.black, Colors.transparent],
                stops: [0, 0.04, 0.96, 1],
              ).createShader(r),
              child: SingleChildScrollView(
                padding: const EdgeInsets.symmetric(vertical: 6),
                child: Column(
                  children: [
                    for (final d in railDestinations(context))
                      _link(context, p,
                          icon: d.icon,
                          label: d.label,
                          active: d.tab == activeIndex,
                          onTap: () => onSelect(d.tab)),
                  ],
                ),
              ),
            ),
          ),
          Container(
            width: 44,
            height: 1,
            margin: const EdgeInsets.fromLTRB(0, 4, 0, 4),
            color: Colors.white.withValues(alpha: 0.14),
          ),
          _link(context, p,
              icon: astraNavTabs[3].icon,
              label: astraNavTabs[3].label,
              active: activeIndex == 3,
              onTap: () => onSelect(3)),
          const SizedBox(height: 4),
          _logoutButton(context),
        ],
      ),
    );
  }

  /// The logomark is the way home: a tap lands on the Dashboard. Lit with a
  /// soft tile while the Dashboard is showing, since it has no rail item.
  Widget _brand(AstraPalette p) {
    final home = activeIndex == 0;
    return Semantics(
      button: true,
      selected: home,
      label: 'Dashboard',
      child: GestureDetector(
        key: const ValueKey('rail-brand'),
        onTap: () => onSelect(0),
        behavior: HitTestBehavior.opaque,
        child: Padding(
          padding: const EdgeInsets.only(top: 4, bottom: 14),
          child: Column(
            children: [
              AnimatedContainer(
                duration: const Duration(milliseconds: 180),
                padding: const EdgeInsets.symmetric(horizontal: 6, vertical: 5),
                decoration: BoxDecoration(
                  color: home ? Colors.white.withValues(alpha: 0.12) : Colors.transparent,
                  borderRadius: BorderRadius.circular(14),
                ),
                // The rail is painted `p.darkSurface`; see [QloudLogomark.onDark].
                child: const QloudLogomark(height: 34, onDark: true),
              ),
              const SizedBox(height: 9),
              Container(
                width: 22,
                height: 2,
                decoration: BoxDecoration(
                  color: p.accent.withValues(alpha: 0.85),
                  borderRadius: BorderRadius.circular(2),
                ),
              ),
            ],
          ),
        ),
      ),
    );
  }

  /// The rail is the only chrome a tablet screen gets, so it has to carry the
  /// way out too. Small and flat — a hairline capsule, no tile — but tinted
  /// danger so it never reads as one more place to navigate to.
  Widget _logoutButton(BuildContext context) {
    // The rail is always painted dark, so the palette's danger reads muddy on
    // it — lift it towards white.
    final red = Color.lerp(AstraPalette.danger, Colors.white, 0.42)!;
    return Semantics(
      button: true,
      label: 'Log out',
      child: GestureDetector(
        onTap: () => _logout(context),
        behavior: HitTestBehavior.opaque,
        child: Container(
          constraints: const BoxConstraints(maxWidth: 72),
          padding: const EdgeInsets.symmetric(horizontal: 9, vertical: 6),
          decoration: BoxDecoration(
            borderRadius: BorderRadius.circular(999),
            border: Border.all(color: red.withValues(alpha: 0.28)),
          ),
          // Scales down rather than overflowing under a large text size.
          child: FittedBox(
            fit: BoxFit.scaleDown,
            child: Row(
              mainAxisSize: MainAxisSize.min,
              children: [
                Icon(Icons.power_settings_new, size: 13, color: red),
                const SizedBox(width: 4),
                Text('LOG OUT',
                    style: ui(size: 7.5, weight: FontWeight.w700, color: red, letterSpacing: 1.1)),
              ],
            ),
          ),
        ),
      ),
    );
  }

  /// Signs out, once confirmed. Nothing to close first: unlike the drawer the
  /// rail is part of the shell the auth redirect replaces.
  Future<void> _logout(BuildContext context) async {
    if (await confirmLogout(context) && context.mounted) {
      await context.read<AuthCubit>().logout();
    }
  }

  Widget _link(
    BuildContext context,
    AstraPalette p, {
    required IconData icon,
    required String label,
    required bool active,
    required VoidCallback? onTap,
  }) {
    final ink = active ? p.accent : Colors.white.withValues(alpha: 0.55);
    return GestureDetector(
      onTap: onTap,
      behavior: HitTestBehavior.opaque,
      // Full rail width, so the notch below lands on the rail's edge.
      child: SizedBox(
        width: double.infinity,
        child: Stack(
          alignment: Alignment.topCenter,
          children: [
            Padding(
              padding: const EdgeInsets.symmetric(vertical: 8),
              child: Column(
                children: [
                  SizedBox(width: 46, height: 36, child: Icon(icon, size: 21, color: ink)),
                  const SizedBox(height: 3),
                  Text(label,
                      style: ui(size: 8.5, weight: active ? FontWeight.w700 : FontWeight.w600, color: ink)),
                ],
              ),
            ),
            // The notch on the rail's edge is the whole active state — no tile.
            if (active)
              Positioned(
                left: 0,
                top: 0,
                bottom: 0,
                child: Center(
                  child: Container(
                    width: 3.5,
                    height: 30,
                    decoration: BoxDecoration(
                      color: p.accent,
                      borderRadius: const BorderRadius.horizontal(right: Radius.circular(4)),
                    ),
                  ),
                ),
              ),
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
    // New Sale floats over whatever the rail is showing, on every railed
    // screen alike — see [AstraNewSaleFab].
    final page = Stack(children: [child, const AstraNewSaleFab()]);
    Widget content({bool top = true}) => SafeArea(top: top, bottom: false, child: page);

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
                    child: ColoredBox(color: p.canvas, child: page),
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
                child: Row(children: [rail, Expanded(child: page)]),
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
