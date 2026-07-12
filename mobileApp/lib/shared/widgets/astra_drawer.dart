import 'dart:ui' show ImageFilter;

import 'package:flutter/material.dart';
import 'package:go_router/go_router.dart';
import 'package:provider/provider.dart';

import 'package:invo/features/auth/logic/auth_cubit/auth_cubit.dart';
import 'package:invo/shared/domain/constants/mobile_permissions.dart';
import 'package:invo/shared/logic/branch_cubit/branch_cubit.dart';
import 'package:invo/shared/utils/components/theme/index.dart';
import 'package:invo/shared/widgets/astra_widgets.dart';

/// "Frosted Glass" navigation drawer — the single place that lists every
/// module in the app (the bottom nav keeps only the four primary tabs).
/// A translucent blurred sheet over the dimmed page, matching the frosted
/// bottom-nav language: gold hairline under the identity header, gold-tint
/// pill on the active destination. Items are permission-gated with the same
/// rules as the router, so a user never sees a link they can't open.
class AstraDrawer extends StatelessWidget {
  const AstraDrawer({super.key, required this.activeTab, required this.onSelectTab});

  /// The shell tab currently shown (0=Home … 3=Settings); highlights its item.
  final int activeTab;

  /// Switches the shell to a primary tab (drawer closes itself first).
  final ValueChanged<int> onSelectTab;

  void _toTab(BuildContext context, int i) {
    Navigator.pop(context);
    onSelectTab(i);
  }

  void _toRoute(BuildContext context, String route) {
    Navigator.pop(context);
    context.push(route);
  }

  @override
  Widget build(BuildContext context) {
    final p = context.astra;
    final auth = context.watch<AuthCubit>();
    final width = (MediaQuery.sizeOf(context).width * 0.81).clamp(260.0, 340.0);

    return Drawer(
      width: width,
      backgroundColor: Colors.transparent,
      elevation: 0,
      shape: const RoundedRectangleBorder(
        borderRadius: BorderRadius.horizontal(right: Radius.circular(30)),
      ),
      child: ClipRRect(
        borderRadius: const BorderRadius.horizontal(right: Radius.circular(30)),
        child: BackdropFilter(
          filter: ImageFilter.blur(sigmaX: 26, sigmaY: 26),
          child: Container(
            decoration: BoxDecoration(
              color: p.isDark
                  ? p.darkSurface.withValues(alpha: 0.62)
                  : p.canvas.withValues(alpha: 0.66),
              border: Border(
                right: BorderSide(
                  color: Colors.white.withValues(alpha: p.isDark ? 0.12 : 0.45),
                ),
              ),
            ),
            child: Column(
              children: [
                _header(context, p, auth),
                Expanded(
                  child: ListView(
                    padding: const EdgeInsets.fromLTRB(14, 2, 14, 12),
                    children: [
                      _section(p, 'OVERVIEW'),
                      _item(context, p,
                          icon: Icons.grid_view,
                          label: 'Dashboard',
                          active: activeTab == 0,
                          onTap: () => _toTab(context, 0)),
                      _item(context, p,
                          icon: Icons.bar_chart,
                          label: 'Reports',
                          subtitle: 'Item wise · employee wise',
                          active: activeTab == 2,
                          onTap: () => _toTab(context, 2)),
                      if (auth.hasPermission(PermissionSlug.daySession))
                        _item(context, p,
                            icon: Icons.schedule,
                            label: 'Day Session',
                            subtitle: 'Open / close the branch day',
                            onTap: () => _toRoute(context, '/day-session')),
                      _section(p, 'SALES'),
                      _item(context, p,
                          icon: Icons.add,
                          label: 'New Sale',
                          onTap: () => _toRoute(context, '/sale')),
                      _item(context, p,
                          icon: Icons.receipt_long,
                          label: 'Sales',
                          subtitle: 'Invoices & history',
                          active: activeTab == 1,
                          onTap: () => _toTab(context, 1)),
                      if (auth.hasPermission(PermissionSlug.saleReturnView))
                        _item(context, p,
                            icon: Icons.assignment_return_outlined,
                            label: 'Sales Returns',
                            subtitle: 'Returns list · new return',
                            onTap: () => _toRoute(context, '/sales-returns')),
                      if (auth.hasPermission(PermissionSlug.stockCheck)) ...[
                        _section(p, 'INVENTORY'),
                        _item(context, p,
                            icon: Icons.fact_check_outlined,
                            label: 'Stock Check',
                            subtitle: 'Count physical stock & reconcile',
                            onTap: () => _toRoute(context, '/stock-check')),
                      ],
                      _section(p, 'ACCOUNT'),
                      _item(context, p,
                          icon: Icons.person_outline,
                          label: 'Profile',
                          onTap: () => _toRoute(context, '/profile')),
                      _item(context, p,
                          icon: Icons.print_outlined,
                          label: 'Print Settings',
                          onTap: () => _toRoute(context, '/print-settings')),
                      _item(context, p,
                          icon: Icons.settings_outlined,
                          label: 'Settings',
                          subtitle: 'Theme · permissions · about',
                          active: activeTab == 3,
                          onTap: () => _toTab(context, 3)),
                    ],
                  ),
                ),
                _footer(context, p),
              ],
            ),
          ),
        ),
      ),
    );
  }

  // ---- identity header, closed by a gold hairline ----
  Widget _header(BuildContext context, AstraPalette p, AuthCubit auth) {
    final user = auth.user;
    final branch = context.watch<BranchCubit>().selected;
    final dayOpen = user?.dayOpen ?? false;
    final dayColor = dayOpen ? AstraPalette.success : AstraPalette.danger;

    return Container(
      padding: EdgeInsets.fromLTRB(18, MediaQuery.paddingOf(context).top + 14, 18, 14),
      decoration: BoxDecoration(
        border: Border(
          bottom: BorderSide(color: p.accent.withValues(alpha: 0.55), width: 1),
        ),
      ),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Text('A S T R A', style: serif(size: 11, color: p.goldText).copyWith(letterSpacing: 4.5)),
          const SizedBox(height: 12),
          Row(
            children: [
              Monogram(letter: user?.initial ?? 'A', size: 42),
              const SizedBox(width: 11),
              Expanded(
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    Text(user?.name ?? '—', maxLines: 1, overflow: TextOverflow.ellipsis,
                        style: serif(size: 17, color: p.ink)),
                    if (branch != null) ...[
                      const SizedBox(height: 2),
                      Text(branch.name.toUpperCase(), maxLines: 1, overflow: TextOverflow.ellipsis,
                          style: ui(size: 9.5, weight: FontWeight.w700, color: p.textMuted, letterSpacing: 0.8)),
                    ],
                  ],
                ),
              ),
            ],
          ),
          const SizedBox(height: 11),
          Container(
            padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 5),
            decoration: BoxDecoration(
              color: dayColor.withValues(alpha: 0.14),
              borderRadius: BorderRadius.circular(20),
              border: Border.all(color: dayColor.withValues(alpha: 0.45)),
            ),
            child: Row(
              mainAxisSize: MainAxisSize.min,
              children: [
                Container(width: 6, height: 6, decoration: BoxDecoration(color: dayColor, shape: BoxShape.circle)),
                const SizedBox(width: 6),
                Text(dayOpen ? 'DAY OPEN' : 'DAY CLOSED',
                    style: ui(size: 9.5, weight: FontWeight.w800, color: dayColor, letterSpacing: 0.6)),
              ],
            ),
          ),
        ],
      ),
    );
  }

  Widget _section(AstraPalette p, String label) => Padding(
        padding: const EdgeInsets.fromLTRB(6, 13, 6, 6),
        child: Text(label,
            style: ui(size: 8.5, weight: FontWeight.w800, color: p.textMuted, letterSpacing: 2.6)),
      );

  Widget _item(
    BuildContext context,
    AstraPalette p, {
    required IconData icon,
    required String label,
    String? subtitle,
    bool active = false,
    required VoidCallback onTap,
  }) {
    final inkTint = p.isDark ? Colors.white.withValues(alpha: 0.08) : p.ink.withValues(alpha: 0.07);
    final activeInk = Color.lerp(p.ink, p.goldText, 0.7)!;
    return GestureDetector(
      onTap: onTap,
      behavior: HitTestBehavior.opaque,
      child: Container(
        margin: const EdgeInsets.only(bottom: 2),
        padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 9),
        decoration: BoxDecoration(
          color: active ? p.accent.withValues(alpha: 0.16) : Colors.transparent,
          borderRadius: BorderRadius.circular(13),
        ),
        child: Row(
          children: [
            Container(
              width: 32,
              height: 32,
              decoration: BoxDecoration(
                gradient: active ? p.accentGradient : null,
                color: active ? null : inkTint,
                borderRadius: BorderRadius.circular(10),
              ),
              child: Icon(icon, size: 16, color: active ? const Color(0xFF2F2508) : p.ink),
            ),
            const SizedBox(width: 11),
            Expanded(
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  Text(label,
                      style: ui(size: 12.5, weight: FontWeight.w700, color: active ? activeInk : p.ink)),
                  if (subtitle != null) ...[
                    const SizedBox(height: 1),
                    Text(subtitle, maxLines: 1, overflow: TextOverflow.ellipsis,
                        style: ui(size: 9.5, weight: FontWeight.w600, color: p.textMuted)),
                  ],
                ],
              ),
            ),
            Icon(Icons.chevron_right, size: 15, color: p.textMuted.withValues(alpha: 0.6)),
          ],
        ),
      ),
    );
  }

  // ---- log out ----
  Widget _footer(BuildContext context, AstraPalette p) {
    return Container(
      padding: EdgeInsets.fromLTRB(18, 12, 18, MediaQuery.paddingOf(context).bottom + 14),
      decoration: BoxDecoration(
        border: Border(top: BorderSide(color: p.hairline)),
      ),
      child: GestureDetector(
        onTap: () => _confirmLogout(context),
        behavior: HitTestBehavior.opaque,
        child: Row(
          children: [
            const Icon(Icons.power_settings_new, size: 16, color: AstraPalette.danger),
            const SizedBox(width: 9),
            Text('Log out', style: ui(size: 12.5, weight: FontWeight.w800, color: AstraPalette.danger)),
            const Spacer(),
            Text('invo · v3', style: ui(size: 9, weight: FontWeight.w600, color: p.textMuted)),
          ],
        ),
      ),
    );
  }

  Future<void> _confirmLogout(BuildContext context) async {
    final ok = await showDialog<bool>(
      context: context,
      builder: (ctx) => AlertDialog(
        title: const Text('Log out?'),
        content: const Text('You’ll need your MPIN to sign back in.'),
        actions: [
          TextButton(onPressed: () => Navigator.pop(ctx, false), child: const Text('Cancel')),
          TextButton(onPressed: () => Navigator.pop(ctx, true), child: const Text('Log out')),
        ],
      ),
    );
    if (ok == true && context.mounted) {
      Navigator.pop(context); // close the drawer before auth redirects
      await context.read<AuthCubit>().logout();
    }
  }
}
