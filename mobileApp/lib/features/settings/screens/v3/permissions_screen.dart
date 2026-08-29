import 'package:flutter/material.dart';
import 'package:go_router/go_router.dart';
import 'package:provider/provider.dart';

import 'package:invo/shared/domain/constants/mobile_permissions.dart';
import 'package:invo/shared/domain/helpers/responsive.dart';
import 'package:invo/features/auth/logic/auth_cubit/auth_cubit.dart';
import 'package:invo/shared/utils/components/theme/index.dart';
import 'package:invo/shared/widgets/astra_widgets.dart';
import 'package:invo/shared/widgets/tablet_widgets.dart';

/// Read-only view of the mobile features this account can reach. Only the
/// permissions the app actually gates on are shown (see [mobilePermissions]),
/// grouped by module; each row reflects whether the signed-in user holds that
/// permission — the same check the router/dashboard guards use.
class PermissionsScreen extends StatelessWidget {
  const PermissionsScreen({super.key, this.embedded = false});

  /// Hosted inside the Settings detail pane rather than standing as its own
  /// page — the pane already carries the head and the framing, so this hands
  /// back the body alone. Same flag, same reasoning as [PrintSettingsScreen].
  final bool embedded;

  @override
  Widget build(BuildContext context) {
    final auth = context.watch<AuthCubit>();
    final allowed = mobilePermissions.where((m) => auth.hasPermission(m.slug)).length;
    final restricted = mobilePermissions.length - allowed;

    // Preserve declaration order of both the groups and the rows within them.
    final groups = <String, List<MobilePermission>>{};
    for (final perm in mobilePermissions) {
      groups.putIfAbsent(perm.group, () => []).add(perm);
    }

    if (embedded) return _embedded(context, auth, groups, allowed, restricted);

    return Scaffold(
      backgroundColor: Colors.transparent,
      body: AstraBackground(
        child: context.isTablet
            ? SafeArea(bottom: false, child: _tablet(context, auth, groups, allowed, restricted))
            : Column(
                children: [
                  EmeraldHeader(
                    title: 'My Permissions',
                    leading: HeaderIconButton(icon: Icons.arrow_back, onTap: () => context.pop()),
                    trailing: const Icon(Icons.verified_user_outlined, size: 18, color: Colors.white70),
                    bottom: Row(
                      children: [
                        _statChip(allowed.toString(), 'Allowed'),
                        const SizedBox(width: 9),
                        _statChip(restricted.toString(), 'Restricted'),
                        const SizedBox(width: 9),
                        _statChip(mobilePermissions.length.toString(), 'Total', gold: true),
                      ],
                    ),
                  ),
                  Expanded(
                    child: MaxWidthBox(
                      maxWidth: 560,
                      child: ListView(
                        padding: const EdgeInsets.fromLTRB(16, 16, 16, 40),
                        children: [
                          if (auth.user?.isAdmin ?? false) ...[
                            _adminNote(context),
                            const SizedBox(height: 14),
                          ],
                          for (final entry in groups.entries) ...[
                            _groupLabel(context, entry.key),
                            _groupCard(context, auth, entry.value),
                            const SizedBox(height: 14),
                          ],
                        ],
                      ),
                    ),
                  ),
                ],
              ),
      ),
    );
  }

  /// The same list as the tablet page, laid out for the Settings detail pane:
  /// counters across the top, then every group down one column. The page's
  /// two-up split is deliberately not used — the pane is 620pt wide, so a
  /// second column would only make each row narrower.
  Widget _embedded(BuildContext context, AuthCubit auth, Map<String, List<MobilePermission>> groups,
      int allowed, int restricted) {
    final p = context.astra;
    return Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        Row(
          children: [
            Expanded(child: _kpi(context, allowed.toString(), 'Allowed', AstraPalette.success)),
            const SizedBox(width: 10),
            Expanded(
                child: _kpi(context, restricted.toString(), 'Restricted',
                    restricted == 0 ? p.textMuted : AstraPalette.danger)),
            const SizedBox(width: 10),
            Expanded(child: _kpi(context, mobilePermissions.length.toString(), 'Total', p.accent)),
          ],
        ),
        const SizedBox(height: 16),
        if (auth.user?.isAdmin ?? false) ...[
          _adminNote(context),
          const SizedBox(height: 16),
        ],
        _groupColumn(context, auth, groups.entries),
      ],
    );
  }

  // ---------------------------------------------------------------- tablet --

  /// Tablet: no header band (the side-rail is the chrome) — a page head, a KPI
  /// row, then the groups as panels in two columns so a 12-row list doesn't
  /// read as one narrow ribbon down the middle of a 1200pt window.
  Widget _tablet(BuildContext context, AuthCubit auth, Map<String, List<MobilePermission>> groups,
      int allowed, int restricted) {
    final isAdmin = auth.user?.isAdmin ?? false;
    return Column(
      children: [
        TabletPageHead(
          title: 'My Permissions',
          subtitle: 'What this account can reach on mobile',
          // A shell destination has nothing to pop; a pushed route does.
          leading: context.canPop()
              ? TabletIconButton(icon: Icons.arrow_back, onTap: () => context.pop())
              : null,
          actions: [
            _kpi(context, allowed.toString(), 'Allowed', AstraPalette.success),
            _kpi(context, restricted.toString(), 'Restricted',
                restricted == 0 ? context.astra.textMuted : AstraPalette.danger),
            _kpi(context, mobilePermissions.length.toString(), 'Total', context.astra.accent),
          ],
        ),
        Expanded(
          child: LayoutBuilder(
            builder: (ctx, c) {
              final entries = groups.entries.toList();
              // Two columns once there is room for them; the split keeps
              // declaration order down each column rather than across, so a
              // module's rows stay together.
              final twoUp = c.maxWidth >= 900;
              final half = (entries.length / 2).ceil();
              return SingleChildScrollView(
                padding: TabletMetrics.forWidth(c.maxWidth).detailPadding.copyWith(top: 22, bottom: 40),
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    if (isAdmin) ...[
                      _adminNote(context),
                      const SizedBox(height: 18),
                    ],
                    if (!twoUp)
                      for (final e in entries) ...[_groupPanel(context, auth, e), const SizedBox(height: 16)]
                    else
                      Row(
                        crossAxisAlignment: CrossAxisAlignment.start,
                        children: [
                          Expanded(child: _groupColumn(context, auth, entries.take(half))),
                          const SizedBox(width: 18),
                          Expanded(child: _groupColumn(context, auth, entries.skip(half))),
                        ],
                      ),
                  ],
                ),
              );
            },
          ),
        ),
      ],
    );
  }

  Widget _groupColumn(
          BuildContext context, AuthCubit auth, Iterable<MapEntry<String, List<MobilePermission>>> entries) =>
      Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          for (final e in entries) ...[_groupPanel(context, auth, e), const SizedBox(height: 16)],
        ],
      );

  Widget _groupPanel(BuildContext context, AuthCubit auth, MapEntry<String, List<MobilePermission>> entry) {
    final p = context.astra;
    return TabletPanel(
      title: entry.key,
      padding: EdgeInsets.zero,
      child: Column(
        children: [
          for (var i = 0; i < entry.value.length; i++) ...[
            if (i > 0)
              Padding(
                padding: const EdgeInsets.only(left: 44),
                child: Container(height: 1, color: p.hairline),
              ),
            _permissionRow(context, entry.value[i], auth.hasPermission(entry.value[i].slug)),
          ],
        ],
      ),
    );
  }

  /// A page-head counter — the tablet stand-in for the header band's stat chips.
  Widget _kpi(BuildContext context, String value, String label, Color accent) {
    final p = context.astra;
    return Container(
      padding: const EdgeInsets.fromLTRB(13, 8, 15, 8),
      decoration: BoxDecoration(
        color: p.surfaceBlur > 0 ? p.card : p.cardSolid,
        borderRadius: BorderRadius.circular(13),
        border: Border.all(color: p.hairline),
        boxShadow: context.astraTheme.softShadow,
      ),
      child: Row(
        mainAxisSize: MainAxisSize.min,
        children: [
          Container(width: 3, height: 26, decoration: BoxDecoration(color: accent, borderRadius: BorderRadius.circular(3))),
          const SizedBox(width: 10),
          Column(
            crossAxisAlignment: CrossAxisAlignment.start,
            mainAxisSize: MainAxisSize.min,
            children: [
              Text(value, style: serif(size: 18, color: p.ink)),
              Text(label.toUpperCase(),
                  style: ui(size: 9, weight: FontWeight.w800, color: p.textMuted, letterSpacing: 1)),
            ],
          ),
        ],
      ),
    );
  }

  Widget _statChip(String value, String label, {bool gold = false}) {
    final bg = gold ? const Color(0x38D8B463) : Colors.white.withValues(alpha: 0.12);
    final valueColor = gold ? const Color(0xFFF0D9A3) : Colors.white;
    final labelColor = gold ? const Color(0xFFF0D9A3) : Colors.white.withValues(alpha: 0.8);
    return Expanded(
      child: Container(
        padding: const EdgeInsets.symmetric(horizontal: 11, vertical: 9),
        decoration: BoxDecoration(color: bg, borderRadius: BorderRadius.circular(11)),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Text(value, style: serif(size: 20, color: valueColor)),
            const SizedBox(height: 3),
            Text(label, style: ui(size: 10, weight: FontWeight.w600, color: labelColor)),
          ],
        ),
      ),
    );
  }

  Widget _adminNote(BuildContext context) {
    final p = context.astra;
    return AstraCard(
      radius: 14,
      child: Row(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          const Icon(Icons.verified_user_outlined, size: 18, color: AstraPalette.success),
          const SizedBox(width: 10),
          Expanded(
            child: Text(
              'You are an administrator, so every feature below is available to you.',
              style: ui(size: 11.5, weight: FontWeight.w600, color: p.ink),
            ),
          ),
        ],
      ),
    );
  }

  Widget _groupLabel(BuildContext context, String label) {
    final p = context.astra;
    return Padding(
      padding: const EdgeInsets.only(left: 2, bottom: 8),
      child: Text(
        label.toUpperCase(),
        style: ui(size: 10.5, weight: FontWeight.w800, color: p.textMuted, letterSpacing: 0.9),
      ),
    );
  }

  Widget _groupCard(BuildContext context, AuthCubit auth, List<MobilePermission> perms) {
    final p = context.astra;
    return AstraCard(
      radius: 14,
      padding: EdgeInsets.zero,
      child: Column(
        children: [
          for (var i = 0; i < perms.length; i++) ...[
            if (i > 0)
              Padding(
                padding: const EdgeInsets.only(left: 44),
                child: Container(height: 1, color: p.hairline),
              ),
            _permissionRow(context, perms[i], auth.hasPermission(perms[i].slug)),
          ],
        ],
      ),
    );
  }

  Widget _permissionRow(BuildContext context, MobilePermission perm, bool allowed) {
    final p = context.astra;
    final rail = allowed ? AstraPalette.success : p.hairline;
    return Padding(
      padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 11),
      child: Row(
        children: [
          Container(
            width: 3,
            height: 30,
            decoration: BoxDecoration(color: rail, borderRadius: BorderRadius.circular(3)),
          ),
          const SizedBox(width: 11),
          Icon(perm.icon, size: 18, color: p.textMuted),
          const SizedBox(width: 11),
          Expanded(
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Text(perm.label, style: ui(size: 12.5, weight: FontWeight.w700, color: p.ink)),
                const SizedBox(height: 1),
                Text(perm.description, style: ui(size: 10.5, weight: FontWeight.w600, color: p.textMuted)),
                const SizedBox(height: 4),
                // Exact backend permission name (the Spatie slug) so it's clear
                // which database permission this row maps to.
                Container(
                  padding: const EdgeInsets.symmetric(horizontal: 6, vertical: 2),
                  decoration: BoxDecoration(
                    color: p.hairline.withValues(alpha: 0.35),
                    borderRadius: BorderRadius.circular(5),
                  ),
                  child: Text(
                    perm.slug,
                    style: ui(size: 9.5, weight: FontWeight.w600, color: p.textMuted, letterSpacing: 0.2),
                  ),
                ),
              ],
            ),
          ),
          const SizedBox(width: 8),
          Icon(
            allowed ? Icons.check_circle : Icons.lock_outline,
            size: allowed ? 19 : 18,
            color: allowed ? AstraPalette.success : AstraPalette.danger,
          ),
        ],
      ),
    );
  }
}
