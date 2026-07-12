import 'dart:async';

import 'package:flutter/material.dart';
import 'package:go_router/go_router.dart';
import 'package:provider/provider.dart';

import 'package:invo/shared/domain/constants/mobile_permissions.dart';
import 'package:invo/shared/domain/helpers/formatters.dart';
import 'package:invo/shared/domain/helpers/responsive.dart';
import 'package:invo/shared/domain/models/index.dart';
import 'package:invo/features/admin/logic/admin_cubit/admin_cubit.dart';
import 'package:invo/features/auth/logic/auth_cubit/auth_cubit.dart';
import 'package:invo/shared/logic/branch_cubit/branch_cubit.dart';
import 'package:invo/shared/utils/components/theme/index.dart';
import 'package:invo/shared/widgets/astra_widgets.dart';
import 'package:invo/shared/widgets/charts.dart';

class DashboardScreen extends StatefulWidget {
  const DashboardScreen({super.key});
  @override
  State<DashboardScreen> createState() => _DashboardScreenState();
}

class _DashboardScreenState extends State<DashboardScreen> {
  StreamSubscription<int>? _branchSub;

  @override
  void initState() {
    super.initState();
    WidgetsBinding.instance.addPostFrameCallback((_) => context.read<AdminCubit>().loadDashboard());
    // The shell keeps this screen alive, so initState won't re-run on a branch
    // switch — reload the dashboard explicitly when the active branch changes.
    _branchSub = context.read<BranchCubit>().onBranchChanged.listen((_) {
      if (mounted) context.read<AdminCubit>().loadDashboard();
    });
  }

  @override
  void dispose() {
    _branchSub?.cancel();
    super.dispose();
  }

  // ---- metric helpers ----
  Map<String, Metric> _byTitle(List<Metric>? list) => {for (final m in list ?? const <Metric>[]) m.title: m};

  @override
  Widget build(BuildContext context) {
    final admin = context.watch<AdminCubit>();
    final user = context.watch<AuthCubit>().user;

    return Scaffold(
      backgroundColor: Colors.transparent,
      body: AstraBackground(
        child: Column(
          children: [
            _hero(admin, user),
            Expanded(
              child: admin.loading && admin.dashboard == null
                  ? const Center(child: CircularProgressIndicator())
                  : admin.error != null && admin.dashboard == null
                      ? EmptyState(
                          icon: Icons.wifi_off,
                          title: 'Dashboard unavailable',
                          message: admin.error,
                          action: AstraButton(label: 'Retry', icon: Icons.refresh, expand: false, onTap: admin.loadDashboard),
                        )
                      : RefreshIndicator(
                          onRefresh: admin.loadDashboard,
                          child: MaxWidthBox(
                            maxWidth: 960,
                            child: ListView(
                              padding: const EdgeInsets.fromLTRB(16, 16, 16, 110),
                              children: [
                                ..._quickActions(),
                                _periodCards(admin),
                                const SizedBox(height: 14),
                                _trendCard(admin),
                                const SizedBox(height: 14),
                                _topPerformers(admin),
                                const SizedBox(height: 14),
                                _overview(admin),
                              ],
                            ),
                          ),
                        ),
            ),
          ],
        ),
      ),
    );
  }

  // ---- QUICK ACTIONS (permission-gated module shortcuts) ----
  List<Widget> _quickActions() {
    final tiles = <Widget>[];
    if (context.read<AuthCubit>().hasPermission(PermissionSlug.stockCheck)) {
      tiles.add(_actionTile(
        icon: Icons.fact_check_outlined,
        title: 'Stock Check',
        subtitle: 'Count physical stock & reconcile',
        onTap: () => context.push('/stock-check'),
      ));
    }
    if (tiles.isEmpty) return const [];
    return [
      ...tiles,
      const SizedBox(height: 14),
    ];
  }

  Widget _actionTile({required IconData icon, required String title, required String subtitle, required VoidCallback onTap}) {
    final p = context.astra;
    return Padding(
      padding: const EdgeInsets.only(bottom: 10),
      child: AstraCard(
        radius: 18,
        padding: const EdgeInsets.all(14),
        onTap: onTap,
        child: Row(
          children: [
            IconChip(icon: icon, size: 42, radius: 12),
            const SizedBox(width: 12),
            Expanded(
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  Text(title, style: serif(size: 15.5, color: p.ink)),
                  const SizedBox(height: 3),
                  Text(subtitle, style: ui(size: 11, weight: FontWeight.w600, color: p.textMuted)),
                ],
              ),
            ),
            Icon(Icons.chevron_right, size: 18, color: p.textMuted),
          ],
        ),
      ),
    );
  }

  // ---- HERO ----
  Widget _hero(AdminCubit admin, ApiUser? user) {
    final p = context.astra;
    final today = _byTitle(admin.dashboard?.today);
    final sales = today["Today's Sales"];
    final bills = today["Today's Bills"];
    final salesVal = asNum(sales?.value);
    final billsVal = asNum(bills?.value).toInt();
    final avg = billsVal > 0 ? salesVal / billsVal : 0;

    return Container(
      decoration: BoxDecoration(
        gradient: p.heroGradient,
        borderRadius: const BorderRadius.vertical(bottom: Radius.circular(30)),
      ),
      child: Stack(
        children: [
          Positioned(
            right: -40,
            top: -50,
            child: Container(
              width: 200,
              height: 200,
              decoration: BoxDecoration(
                shape: BoxShape.circle,
                gradient: RadialGradient(colors: [p.accent.withValues(alpha: 0.22), Colors.transparent]),
              ),
            ),
          ),
          // Soft secondary glow — adds depth and reads well across every skin.
          Positioned(
            left: -55,
            bottom: -70,
            child: Container(
              width: 180,
              height: 180,
              decoration: BoxDecoration(
                shape: BoxShape.circle,
                gradient: RadialGradient(colors: [Colors.white.withValues(alpha: 0.10), Colors.transparent]),
              ),
            ),
          ),
          SafeArea(
            bottom: false,
            child: Padding(
              padding: const EdgeInsets.fromLTRB(18, 8, 18, 18),
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  Row(
                    children: [
                      Expanded(
                        child: Column(
                          crossAxisAlignment: CrossAxisAlignment.start,
                          children: [
                            Text('${_greeting().toUpperCase()},',
                                style: ui(size: 10, weight: FontWeight.w700, color: p.accent, letterSpacing: 2)),
                            const SizedBox(height: 3),
                            Text(user?.name.split(' ').first ?? 'there',
                                style: serif(size: 24, color: Colors.white)),
                          ],
                        ),
                      ),
                      GestureDetector(
                        onTap: () => context.push('/profile'),
                        child: Monogram(letter: user?.initial ?? 'A', size: 44),
                      ),
                    ],
                  ),
                  const SizedBox(height: 18),
                  Text("TODAY'S REVENUE",
                      style: ui(size: 10, weight: FontWeight.w800, color: Colors.white70, letterSpacing: 1.4)),
                  const SizedBox(height: 4),
                  FittedBox(
                    fit: BoxFit.scaleDown,
                    alignment: Alignment.centerLeft,
                    child: Text(sales == null ? '—' : Money.of(salesVal),
                        style: serif(size: 38, color: Colors.white, height: 1)),
                  ),
                  const SizedBox(height: 4),
                  Text('$billsVal ${billsVal == 1 ? 'bill' : 'bills'} today · avg ${Money.of(avg)}',
                      style: ui(size: 11.5, weight: FontWeight.w600, color: Colors.white70)),
                  const SizedBox(height: 12),
                  _dayStatusPill(user),
                  if (admin.trendPoints.length >= 2) ...[
                    const SizedBox(height: 12),
                    AreaTrendChart(
                      values: admin.trendPoints,
                      height: 44,
                      stroke: Colors.white,
                      fill: Colors.white.withValues(alpha: 0.22),
                      dot: p.accent,
                    ),
                  ],
                ],
              ),
            ),
          ),
        ],
      ),
    );
  }

  /// Tappable day open/closed chip on the hero → the Day Session screen.
  /// When the day is closed it is highlighted (danger-tinted) to nudge the
  /// user to open it; a second line shows the open time (when open) or the
  /// last close time (when closed).
  Widget _dayStatusPill(ApiUser? user) {
    final open = user?.dayOpen ?? false;
    final accent = open ? AstraPalette.success : AstraPalette.danger;

    // Sub-label: opened-at while open, last-closed-at while closed.
    final whenIso = open ? (user?.daySessionOpenedAt ?? '') : (user?.lastClosedSessionAt ?? '');
    final when = Dates.humanDateTime(whenIso);
    final whenLabel = open
        ? (when.isEmpty ? 'Opened today' : 'Opened $when')
        : (when.isEmpty ? 'Not opened yet today' : 'Last closed $when');

    final canManageDaySession = context.read<AuthCubit>().hasPermission(PermissionSlug.daySession);

    return GestureDetector(
      onTap: canManageDaySession ? () => context.push('/day-session') : null,
      child: Container(
        padding: const EdgeInsets.symmetric(horizontal: 13, vertical: 10),
        decoration: BoxDecoration(
          // Closed → bold danger tint that stands out; open → subtle glass.
          color: open
              ? Colors.white.withValues(alpha: 0.14)
              : accent.withValues(alpha: 0.22),
          borderRadius: BorderRadius.circular(13),
          border: Border.all(
            color: open ? Colors.white.withValues(alpha: 0.18) : accent.withValues(alpha: 0.85),
            width: open ? 1 : 1.5,
          ),
        ),
        child: Row(
          children: [
            Container(width: 8, height: 8, decoration: BoxDecoration(color: accent, shape: BoxShape.circle)),
            const SizedBox(width: 8),
            Expanded(
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  Row(
                    children: [
                      Text(open ? 'Day open' : 'Day closed',
                          style: ui(size: 12, weight: FontWeight.w800, color: Colors.white)),
                      const SizedBox(width: 6),
                      Text(open ? '· tap to close' : '· tap to open',
                          style: ui(size: 11, weight: FontWeight.w600, color: Colors.white70)),
                    ],
                  ),
                  const SizedBox(height: 2),
                  Text(whenLabel,
                      style: ui(size: 10.5, weight: FontWeight.w600, color: Colors.white70)),
                ],
              ),
            ),
            const SizedBox(width: 6),
            const Icon(Icons.chevron_right, size: 16, color: Colors.white70),
          ],
        ),
      ),
    );
  }

  String _greeting() {
    final h = DateTime.now().hour;
    if (h < 12) return 'Good morning';
    if (h < 17) return 'Good afternoon';
    return 'Good evening';
  }

  // ---- WEEK / MONTH comparison ----
  Widget _periodCards(AdminCubit admin) {
    final business = _byTitle(admin.dashboard?.business);
    final weekly = business['weekly sales'];
    final monthly = business['Monthly sales'];
    return Row(
      children: [
        Expanded(child: _periodCard('This week', weekly, Icons.calendar_view_week)),
        const SizedBox(width: 12),
        Expanded(child: _periodCard('This month', monthly, Icons.calendar_month)),
      ],
    );
  }

  Widget _periodCard(String label, Metric? m, IconData icon) {
    final p = context.astra;
    final delta = m?.percentage;
    final down = delta != null && delta.startsWith('-');
    return AstraCard(
      radius: 18,
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Row(
            children: [
              IconChip(icon: icon, size: 30, radius: 9),
              const Spacer(),
              if (delta != null)
                Container(
                  padding: const EdgeInsets.symmetric(horizontal: 7, vertical: 3),
                  decoration: BoxDecoration(
                    color: (down ? AstraPalette.danger : AstraPalette.success).withValues(alpha: 0.12),
                    borderRadius: BorderRadius.circular(20),
                  ),
                  child: Row(
                    mainAxisSize: MainAxisSize.min,
                    children: [
                      Icon(down ? Icons.south_east : Icons.north_east,
                          size: 10, color: down ? AstraPalette.danger : AstraPalette.success),
                      const SizedBox(width: 3),
                      Text(delta, style: ui(size: 10.5, weight: FontWeight.w800, color: down ? AstraPalette.danger : AstraPalette.success)),
                    ],
                  ),
                ),
            ],
          ),
          const SizedBox(height: 14),
          FittedBox(
            fit: BoxFit.scaleDown,
            alignment: Alignment.centerLeft,
            child: Text(m == null ? '—' : Money.of(asNum(m.value)), style: serif(size: 24, color: p.ink)),
          ),
          Text(label, style: ui(size: 11, weight: FontWeight.w600, color: p.textMuted)),
        ],
      ),
    );
  }

  // ---- TREND ----
  Widget _trendCard(AdminCubit admin) {
    final p = context.astra;
    final pts = admin.trendPoints;
    final total = pts.fold<double>(0, (a, b) => a + b);
    return AstraCard(
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Row(
            children: [
              Expanded(child: Text('Revenue trend', maxLines: 1, overflow: TextOverflow.ellipsis,
                  style: ui(size: 13, weight: FontWeight.w700, color: p.ink))),
              if (pts.length >= 2)
                Text('${pts.length}-day · ${Money.of(total)}',
                    style: ui(size: 10.5, weight: FontWeight.w700, color: p.textMuted)),
            ],
          ),
          const SizedBox(height: 12),
          if (pts.length < 2)
            SizedBox(
              height: 96,
              child: Center(child: Text('Not enough sales data yet',
                  style: ui(size: 12, weight: FontWeight.w500, color: p.textMuted))),
            )
          else ...[
            AreaTrendChart(values: pts, height: 110),
            const SizedBox(height: 8),
            Row(
              mainAxisAlignment: MainAxisAlignment.spaceBetween,
              children: [
                for (final l in admin.trendLabels)
                  Text(_dayLabel(l), style: ui(size: 9, weight: FontWeight.w700, color: p.textMuted)),
              ],
            ),
          ],
        ],
      ),
    );
  }

  String _dayLabel(String iso) {
    final d = DateTime.tryParse(iso);
    if (d == null) return '';
    const days = ['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'];
    return days[d.weekday - 1];
  }

  // ---- TOP PERFORMERS ----
  Widget _topPerformers(AdminCubit admin) {
    final p = context.astra;
    final top = admin.topStylists;
    if (top.isEmpty) return const SizedBox.shrink();
    final maxRev = top.first.amount <= 0 ? 1.0 : top.first.amount;
    const medals = [Color(0xFFD9A93B), Color(0xFFB6B6C2), Color(0xFFC58B5B)];
    return AstraCard(
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Row(
            children: [
              Icon(Icons.emoji_events_outlined, size: 16, color: p.goldText),
              const SizedBox(width: 7),
              Text('Top performers', style: ui(size: 13, weight: FontWeight.w700, color: p.ink)),
            ],
          ),
          const SizedBox(height: 14),
          for (var i = 0; i < top.length; i++)
            Padding(
              padding: const EdgeInsets.only(bottom: 13),
              child: Row(
                children: [
                  SizedBox(
                    width: 22,
                    child: Text('${i + 1}',
                        textAlign: TextAlign.center,
                        style: serif(size: 15, color: i < 3 ? medals[i] : p.textMuted)),
                  ),
                  const SizedBox(width: 8),
                  Container(
                    width: 34,
                    height: 34,
                    decoration: BoxDecoration(gradient: p.primaryGradient, shape: BoxShape.circle),
                    alignment: Alignment.center,
                    child: Text(top[i].title.isEmpty ? '?' : top[i].title[0].toUpperCase(),
                        style: ui(size: 13, weight: FontWeight.w700, color: Colors.white)),
                  ),
                  const SizedBox(width: 11),
                  Expanded(
                    child: Column(
                      crossAxisAlignment: CrossAxisAlignment.start,
                      children: [
                        Row(
                          children: [
                            Expanded(child: Text(top[i].title, maxLines: 1, overflow: TextOverflow.ellipsis,
                                style: ui(size: 12.5, weight: FontWeight.w700, color: p.ink))),
                            Text(top[i].value, style: serif(size: 14, color: p.primaryDark)),
                          ],
                        ),
                        const SizedBox(height: 6),
                        ProgressBar(fraction: top[i].amount / maxRev),
                      ],
                    ),
                  ),
                ],
              ),
            ),
        ],
      ),
    );
  }

  // ---- BUSINESS OVERVIEW ----
  Widget _overview(AdminCubit admin) {
    final p = context.astra;
    final inv = _byTitle(admin.dashboard?.inventory);
    final tiles = <(String, IconData, Metric?)>[
      ('Employees', Icons.badge_outlined, inv['Active Employees']),
      ('Customers', Icons.people_outline, inv['Customers']),
      ('Products', Icons.inventory_2_outlined, inv['Products']),
      ('Services', Icons.content_cut, inv['Services']),
    ];
    return Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        Padding(
          padding: const EdgeInsets.only(left: 4, bottom: 10),
          child: SectionLabel('Business overview'),
        ),
        GridView.count(
          crossAxisCount: context.isTablet ? 4 : 2,
          shrinkWrap: true,
          physics: const NeverScrollableScrollPhysics(),
          mainAxisSpacing: 12,
          crossAxisSpacing: 12,
          childAspectRatio: 1.7,
          children: [
            for (final t in tiles)
              AstraCard(
                radius: 16,
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  mainAxisAlignment: MainAxisAlignment.center,
                  children: [
                    IconChip(icon: t.$2, size: 30, radius: 9),
                    const Spacer(),
                    Text(t.$3 == null ? '—' : asNum(t.$3!.value).toInt().toString(),
                        style: serif(size: 21, color: p.ink)),
                    Text(t.$1, style: ui(size: 10.5, weight: FontWeight.w600, color: p.textMuted)),
                  ],
                ),
              ),
          ],
        ),
      ],
    );
  }
}
