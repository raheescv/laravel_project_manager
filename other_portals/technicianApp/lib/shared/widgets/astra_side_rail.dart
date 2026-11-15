import 'package:flutter/material.dart';
import 'package:flutter/services.dart';
import 'package:go_router/go_router.dart';
import 'package:provider/provider.dart';

import 'package:invo/features/auth/logic/auth_cubit/auth_cubit.dart';
import 'package:invo/shared/domain/helpers/responsive.dart';
import 'package:invo/shared/domain/models/index.dart';
import 'package:invo/shared/logic/theme_cubit/theme_cubit.dart';
import 'package:invo/shared/utils/components/theme/index.dart';
import 'package:invo/shared/utils/router/routes.dart';
import 'package:invo/shared/widgets/astra_widgets.dart';
import 'package:invo/shared/widgets/fixmate_logo.dart';

/// The four primary destinations, shared by the phone bottom nav and the
/// tablet rail. Indices are fixed — `/home?tab=N` depends on them.
const technicianTabs = [
  (icon: Icons.grid_view_rounded, label: 'Dashboard'),
  (icon: Icons.assignment_outlined, label: 'My Jobs'),
  (icon: Icons.fact_check_outlined, label: 'Checklists'),
  (icon: Icons.settings_outlined, label: 'Settings'),
];

/// My Profile — a tablet shell destination (it swaps in place instead of
/// sliding a page over the shell) but deliberately NOT a rail link: the rail
/// is for modules, and the avatar at the foot of the rail already reads "me".
const int kProfileTab = 4;

/// Lets anything ask the home shell to show a tab — a rail link on a pushed
/// route, the seal flow returning to the inbox. Re-navigating to `/home`
/// keeps the shell's State (same page key), so a query param alone would not
/// switch it; the shell listens here instead.
class ShellTabRequests extends ChangeNotifier {
  int? _tab;

  /// The last requested tab (consumed by the shell).
  int? get tab => _tab;

  void request(int tab) {
    _tab = tab;
    notifyListeners();
  }
}

final shellTabRequests = ShellTabRequests();

/// Leaves any pushed route and lands on the shell's [tab]. Every rail link
/// uses this (never `push`), so the links behave as peers.
void goToShellTab(BuildContext context, int tab) {
  shellTabRequests.request(tab);
  context.go(Routes.home);
}

Future<bool> confirmLogout(BuildContext context) async {
  final ok = await showDialog<bool>(
    context: context,
    builder: (ctx) => AlertDialog(
      title: const Text('Log out?'),
      content: const Text('You can sign back in with your MPIN, password or biometrics.'),
      actions: [
        TextButton(onPressed: () => Navigator.pop(ctx, false), child: const Text('Cancel')),
        TextButton(onPressed: () => Navigator.pop(ctx, true), child: const Text('Log out')),
      ],
    ),
  );
  return ok == true;
}

/// The tablet side-rail — the app's primary navigation and the only chrome a
/// tablet screen gets (no header band). Phones keep the glass bottom nav.
class AstraSideRail extends StatelessWidget {
  const AstraSideRail({super.key, required this.activeIndex, required this.onSelect, this.flush = false});

  /// Highlighted tab, or null when the current screen isn't one of them.
  final int? activeIndex;
  final ValueChanged<int> onSelect;

  /// Painted to the window's edges (every chrome but `peers`); it then takes
  /// the status-bar inset into its own padding.
  final bool flush;

  @override
  Widget build(BuildContext context) {
    final p = context.astra;
    final inset = MediaQuery.viewPaddingOf(context);
    final user = context.select<AuthCubit, ApiUser?>((c) => c.user);
    final cfg = context.read<AuthCubit>().config;
    return Container(
      width: 92,
      margin: flush ? EdgeInsets.zero : const EdgeInsets.fromLTRB(14, 14, 0, 14),
      padding: EdgeInsets.only(top: 16 + (flush ? inset.top : 0), bottom: 16 + (flush ? inset.bottom : 0)),
      decoration: BoxDecoration(
        color: p.darkSurface,
        borderRadius: flush ? null : BorderRadius.circular(26),
        boxShadow: flush
            ? null
            : [BoxShadow(color: Colors.black.withValues(alpha: 0.3), blurRadius: 36, offset: const Offset(0, 14))],
      ),
      // Scrolls when a short landscape window can't fit every link, while still
      // pushing the account block to the foot when there is room.
      child: LayoutBuilder(
        builder: (context, c) => SingleChildScrollView(
          child: ConstrainedBox(
            constraints: BoxConstraints(minHeight: c.maxHeight),
            child: IntrinsicHeight(
              child: Column(
                children: [
                  const SizedBox(height: 4),
                  const FixMateLogo(size: 40),
                  const SizedBox(height: 24),
                  for (var i = 0; i < technicianTabs.length; i++)
                    _link(context, p,
                        icon: technicianTabs[i].icon,
                        label: technicianTabs[i].label,
                        active: i == activeIndex,
                        onTap: () => onSelect(i)),
                  const Spacer(),
                  GestureDetector(
                    onTap: () => onSelect(kProfileTab),
                    behavior: HitTestBehavior.opaque,
                    child: Column(children: [
                      Container(
                        padding: const EdgeInsets.all(2),
                        decoration: BoxDecoration(
                          shape: BoxShape.circle,
                          border: Border.all(
                            color: activeIndex == kProfileTab ? p.accent : Colors.transparent,
                            width: 2,
                          ),
                        ),
                        child: ProfileAvatar(
                          letter: user?.initial ?? '·',
                          imageUrl: user != null && user.hasPhoto ? cfg.assetUrl(user.photoUrl) : null,
                          headers: cfg.assetHeaders,
                          size: 44,
                        ),
                      ),
                      const SizedBox(height: 4),
                      Text('Profile',
                          style: ui(
                              size: 8.5,
                              weight: activeIndex == kProfileTab ? FontWeight.w700 : FontWeight.w600,
                              color: activeIndex == kProfileTab ? p.accent : Colors.white.withValues(alpha: 0.6))),
                    ]),
                  ),
                  // The rail is the only chrome a tablet screen gets, so it
                  // carries the way out too — held apart by a hairline and tinted
                  // danger so it never reads as one more destination.
                  Container(
                    width: 44,
                    height: 1,
                    margin: const EdgeInsets.fromLTRB(0, 14, 0, 2),
                    color: Colors.white.withValues(alpha: 0.14),
                  ),
                  _link(context, p,
                      icon: Icons.power_settings_new,
                      label: 'Log out',
                      active: false,
                      danger: true,
                      onTap: () => _logout(context)),
                ],
              ),
            ),
          ),
        ),
      ),
    );
  }

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
    required VoidCallback onTap,
    bool danger = false,
  }) {
    // The rail is always dark, so the palette's danger is lifted towards white.
    final red = Color.lerp(AstraPalette.danger, Colors.white, 0.42)!;
    final fg = danger ? red : (active ? p.accent : Colors.white.withValues(alpha: 0.55));
    return GestureDetector(
      onTap: onTap,
      behavior: HitTestBehavior.opaque,
      child: Padding(
        padding: const EdgeInsets.symmetric(vertical: 9),
        child: Column(
          children: [
            AnimatedContainer(
              duration: AstraMotion.fast,
              curve: AstraMotion.curve,
              width: 46,
              height: 40,
              decoration: BoxDecoration(
                color: danger
                    ? AstraPalette.danger.withValues(alpha: 0.16)
                    : (active ? Colors.white.withValues(alpha: 0.12) : Colors.transparent),
                borderRadius: BorderRadius.circular(13),
              ),
              child: Icon(icon, size: 20, color: fg),
            ),
            const SizedBox(height: 4),
            Text(label, style: ui(size: 8.5, weight: active || danger ? FontWeight.w700 : FontWeight.w600, color: fg)),
          ],
        ),
      ),
    );
  }
}

/// Rail + content, assembled the way this device's [AstraChrome] asks for.
/// The home shell and every railed route both delegate here, so the two can
/// never drift apart.
class AstraRailShell extends StatelessWidget {
  const AstraRailShell({super.key, required this.activeIndex, required this.onSelect, required this.child});

  final int? activeIndex;
  final ValueChanged<int> onSelect;
  final Widget child;

  @override
  Widget build(BuildContext context) {
    final p = context.astra;
    final chrome = context.select<ThemeCubit, AstraChrome>((c) => c.state.chrome);
    final rail = AstraSideRail(activeIndex: activeIndex, onSelect: onSelect, flush: chrome.railIsFlush);

    // A flush rail paints under the status bar itself, so the inset is
    // re-applied to the content side only.
    Widget content({bool top = true}) => SafeArea(top: top, bottom: false, child: child);

    final Widget body = switch (chrome) {
      // Rail flush and dark to the edges; the content lifts off it as one
      // rounded card with an EVEN gutter on all four sides (a card that floats
      // has to float on every side, or its corner opens a wedge by the rail).
      AstraChrome.insetCanvas => ColoredBox(
          color: p.darkSurface,
          child: Row(children: [
            rail,
            Expanded(
              child: Padding(
                padding: EdgeInsets.fromLTRB(10, MediaQuery.viewPaddingOf(context).top + 10, 10, 10),
                child: ClipRRect(
                  borderRadius: BorderRadius.circular(22),
                  child: ColoredBox(color: p.canvas, child: content(top: false)),
                ),
              ),
            ),
          ]),
        ),
      AstraChrome.docked => Row(children: [rail, Expanded(child: content())]),
      AstraChrome.peers => SafeArea(
          bottom: false,
          child: Row(children: [
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
          ]),
        ),
      AstraChrome.unified => SafeArea(
          child: Padding(
            padding: const EdgeInsets.all(14),
            child: ClipRRect(
              borderRadius: BorderRadius.circular(26),
              child: ColoredBox(color: p.canvas, child: Row(children: [rail, Expanded(child: child)])),
            ),
          ),
        ),
    };

    return AnnotatedRegion<SystemUiOverlayStyle>(
      value: chrome.darkStatusStrip
          ? SystemUiOverlayStyle.light
          : (p.isDark ? SystemUiOverlayStyle.light : SystemUiOverlayStyle.dark),
      child: body,
    );
  }
}

/// Wraps a *pushed* destination with the side-rail on a tablet so navigation
/// stays reachable; a no-op on phones. Rail taps leave the pushed stack and
/// land on the shell's matching tab via [goToShellTab].
class TabletRailScaffold extends StatefulWidget {
  const TabletRailScaffold({super.key, required this.child, this.activeTab});

  final Widget child;

  /// Which rail tab to highlight, if this destination belongs to one.
  final int? activeTab;

  @override
  State<TabletRailScaffold> createState() => _TabletRailScaffoldState();
}

class _TabletRailScaffoldState extends State<TabletRailScaffold> {
  /// Keeps the destination's own subtree (its cubits, controllers, scroll
  /// position) alive when the layout flips between the phone form (child alone)
  /// and the tablet form (child beside the rail) — an iPad rotating or entering
  /// split view. Without it the child is rebuilt from scratch, closing any cubit
  /// it provides while sheets above it still hold that instance.
  final _childKey = GlobalKey();

  @override
  Widget build(BuildContext context) {
    final child = KeyedSubtree(key: _childKey, child: widget.child);
    if (!context.isTablet) return child;
    // No transparent background: the strip the rail sits on must be the page
    // canvas, or the route underneath shows through.
    return Scaffold(
      body: AstraRailShell(
        activeIndex: widget.activeTab,
        onSelect: (i) => goToShellTab(context, i),
        child: child,
      ),
    );
  }
}
