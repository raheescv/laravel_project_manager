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
import 'package:invo/shared/utils/router/routes.dart';
import 'package:invo/shared/widgets/astra_widgets.dart';
import 'package:invo/shared/widgets/astra_side_rail.dart';
import 'package:invo/shared/widgets/charts.dart';

class DashboardScreen extends StatefulWidget {
  const DashboardScreen({super.key, this.onSelectTab});

  /// Switches the shell to a primary tab (0=Home … 3=Settings). Injected by
  /// [HomeShell] so the Quick-actions launcher can jump to the Sales / Reports
  /// tabs, exactly the way the drawer does.
  final ValueChanged<int>? onSelectTab;

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

  // On a tablet these three are shell destinations, so every way in — the rail,
  // the drawer, these tiles, the day-status pill — has to switch to them the
  // same instant way. Pushing the route from here instead would slide a second
  // copy over the shell and leave the rail highlighting the wrong thing.
  void _open(int tab, String route) =>
      context.isTablet ? widget.onSelectTab?.call(tab) : context.push(route);

  void _openDaySession() => _open(kDaySessionTab, Routes.daySession);
  void _openReturns() => _open(kReturnsTab, Routes.salesReturns);
  void _openStockCheck() => _open(kStockCheckTab, Routes.stockCheck);
  void _openProfile() => _open(kProfileTab, Routes.profile);

  @override
  Widget build(BuildContext context) {
    final admin = context.watch<AdminCubit>();
    final user = context.select<AuthCubit, ApiUser?>((c) => c.state.user);

    return Scaffold(
      backgroundColor: Colors.transparent,
      body: AstraBackground(
        child: Column(
          children: [
            // Phone: the hero is a full-bleed band under the status bar. Tablet:
            // the preview makes it an inset rounded card at the top of the
            // scroll, so it reads as a panel beside the rail, not a banner.
            if (!context.isTablet) _hero(admin, user),
            Expanded(
              // Editorial "spotlight" layout: the hero owns the headline number,
              // then a Quick-actions launcher (always present — usable even for a
              // cashier who can't load admin insights), then the insight blocks.
              child: RefreshIndicator(
                onRefresh: admin.loadDashboard,
                child: MaxWidthBox(
                  maxWidth: 960,
                  child: ListView(
                    padding: EdgeInsets.fromLTRB(16, context.isTablet ? 8 : 14, 16, context.isTablet ? 40 : 110),
                    children: [
                      if (context.isTablet) ...[
                        _hero(admin, user),
                        const SizedBox(height: 20),
                      ],
                      _quickActions(),
                      SizedBox(height: context.isTablet ? 20 : 16),
                      if (admin.dashboard != null) ...[
                        Padding(
                          padding: const EdgeInsets.only(left: 4, bottom: 8),
                          child: SectionLabel('Insights'),
                        ),
                        if (context.isTablet)
                          _tabletInsights(admin)
                        else ...[
                          _periodCards(admin),
                          if (_showLeaderboard(admin)) ...[
                            const SizedBox(height: 14),
                            _topPerformers(admin),
                          ],
                          const SizedBox(height: 18),
                          _paymentSplit(admin),
                        ],
                      ] else if (admin.loading) ...[
                        const Padding(
                          padding: EdgeInsets.symmetric(vertical: 48),
                          child: Center(child: CircularProgressIndicator()),
                        ),
                      ] else if (admin.error != null) ...[
                        _insightsUnavailable(admin),
                      ],
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

  // ---- HERO ----
  Widget _hero(AdminCubit admin, ApiUser? user) {
    final cfg = context.read<AuthCubit>().config;
    final p = context.astra;
    final today = _byTitle(admin.dashboard?.today);
    final sales = today["Today's Sales"];
    final bills = today["Today's Bills"];
    final salesVal = asNum(sales?.value);
    final billsVal = asNum(bills?.value).toInt();
    final avg = billsVal > 0 ? salesVal / billsVal : 0;

    final tablet = context.isTablet;
    return Container(
      clipBehavior: tablet ? Clip.antiAlias : Clip.none,
      decoration: BoxDecoration(
        gradient: p.heroGradient,
        borderRadius: tablet
            ? BorderRadius.circular(22)
            : const BorderRadius.vertical(bottom: Radius.circular(30)),
        boxShadow: tablet ? context.astraTheme.floatShadow(p.primary) : null,
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
            // Inset card on tablet — it no longer touches the status bar, so it
            // must not reserve the notch inset either.
            top: !tablet,
            bottom: false,
            child: Padding(
              padding: EdgeInsets.fromLTRB(tablet ? 24 : 18, tablet ? 20 : 8, tablet ? 24 : 18, tablet ? 22 : 18),
              child: tablet
                  ? _heroTabletBody(admin, user)
                  : Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  Row(
                    children: [
                      _drawerButton(),
                      const SizedBox(width: 12),
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
                        onTap: _openProfile,
                        child: ProfileAvatar(
                          letter: user?.initial ?? 'A',
                          imageUrl: (user?.hasPhoto ?? false) ? cfg.assetUrl(user!.photoUrl) : null,
                          headers: cfg.assetHeaders,
                          size: 44,
                        ),
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

  /// Tablet hero: a two-column layout so the wide green band reads as designed
  /// rather than a phone hero stretched across empty space — revenue headline on
  /// the left, the day-status pill (and trend, if any) filling the right.
  Widget _heroTabletBody(AdminCubit admin, ApiUser? user) {
    final cfg = context.read<AuthCubit>().config;
    final p = context.astra;
    final today = _byTitle(admin.dashboard?.today);
    final sales = today["Today's Sales"];
    final bills = today["Today's Bills"];
    final salesVal = asNum(sales?.value);
    final billsVal = asNum(bills?.value).toInt();
    final avg = billsVal > 0 ? salesVal / billsVal : 0;
    return Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        Row(
          children: [
            _drawerButton(),
            const SizedBox(width: 12),
            Expanded(
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  Text('${_greeting().toUpperCase()},',
                      style: ui(size: 10, weight: FontWeight.w700, color: p.accent, letterSpacing: 2)),
                  const SizedBox(height: 3),
                  Text(user?.name.split(' ').first ?? 'there', style: serif(size: 24, color: Colors.white)),
                ],
              ),
            ),
            GestureDetector(
              onTap: _openProfile,
              child: ProfileAvatar(
                letter: user?.initial ?? 'A',
                imageUrl: (user?.hasPhoto ?? false) ? cfg.assetUrl(user!.photoUrl) : null,
                headers: cfg.assetHeaders,
                size: 46,
              ),
            ),
          ],
        ),
        const SizedBox(height: 24),
        Row(
          crossAxisAlignment: CrossAxisAlignment.end,
          children: [
            Expanded(
              flex: 3,
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  Text("TODAY'S REVENUE",
                      style: ui(size: 11, weight: FontWeight.w800, color: Colors.white70, letterSpacing: 1.5)),
                  const SizedBox(height: 6),
                  FittedBox(
                    fit: BoxFit.scaleDown,
                    alignment: Alignment.centerLeft,
                    child: Text(sales == null ? '—' : Money.of(salesVal),
                        style: serif(size: 50, color: Colors.white, height: 1)),
                  ),
                  const SizedBox(height: 8),
                  Text('$billsVal ${billsVal == 1 ? 'bill' : 'bills'} today · avg ${Money.of(avg)}',
                      style: ui(size: 12.5, weight: FontWeight.w600, color: Colors.white70)),
                ],
              ),
            ),
            const SizedBox(width: 28),
            Expanded(
              flex: 2,
              child: Column(
                mainAxisSize: MainAxisSize.min,
                children: [
                  _dayStatusPill(user),
                  if (admin.trendPoints.length >= 2) ...[
                    const SizedBox(height: 12),
                    AreaTrendChart(
                      values: admin.trendPoints,
                      height: 50,
                      stroke: Colors.white,
                      fill: Colors.white.withValues(alpha: 0.22),
                      dot: p.accent,
                    ),
                  ],
                ],
              ),
            ),
          ],
        ),
      ],
    );
  }

  /// Hamburger that opens the shell's [AstraDrawer]. The dashboard has its own
  /// (transparent) Scaffold, so the nearest Scaffold has no drawer — walk up to
  /// the root one, which is the HomeShell scaffold that hosts it.
  Widget _drawerButton() {
    return GestureDetector(
      onTap: () => context.findRootAncestorStateOfType<ScaffoldState>()?.openDrawer(),
      child: Container(
        width: 40,
        height: 40,
        decoration: BoxDecoration(
          color: Colors.white.withValues(alpha: 0.14),
          borderRadius: BorderRadius.circular(13),
          border: Border.all(color: Colors.white.withValues(alpha: 0.20)),
        ),
        child: const Icon(Icons.menu_rounded, size: 20, color: Colors.white),
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
      onTap: canManageDaySession ? () => _openDaySession() : null,
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
                  // Both labels flex: in the tablet hero this pill sits in the
                  // narrow right column, where the two fixed Texts overflowed.
                  Row(
                    children: [
                      Flexible(
                        child: Text(open ? 'Day open' : 'Day closed',
                            maxLines: 1, overflow: TextOverflow.ellipsis,
                            style: ui(size: 12, weight: FontWeight.w800, color: Colors.white)),
                      ),
                      const SizedBox(width: 6),
                      Flexible(
                        child: Text(open ? '· tap to close' : '· tap to open',
                            maxLines: 1, overflow: TextOverflow.ellipsis,
                            style: ui(size: 11, weight: FontWeight.w600, color: Colors.white70)),
                      ),
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

  /// Tablet insights: the period KPIs and the session-payment breakdown stack in
  /// a wide left column while the leaderboard sits alongside, so the width is
  /// spent on content instead of on margin. Falls back to the phone stack when
  /// there are no top performers to fill the second column.
  Widget _tabletInsights(AdminCubit admin) {
    final leaderboard = _topPerformers(admin);
    // `stretch` matters: _paymentSplit is a Column, so under the default centre
    // alignment it would shrink-wrap to its content and float in the middle of
    // the column instead of filling it.
    Widget mainColumn() => Column(
          crossAxisAlignment: CrossAxisAlignment.stretch,
          children: [
            _periodCards(admin),
            const SizedBox(height: 18),
            _paymentSplit(admin),
          ],
        );
    if (!_showLeaderboard(admin)) return mainColumn();
    return LayoutBuilder(
      builder: (ctx, c) {
        // Only go side-by-side once the narrower column still has room for the
        // leaderboard's name + bar rows; a small tablet in portrait stacks.
        if (c.maxWidth < 820) {
          return Column(
            crossAxisAlignment: CrossAxisAlignment.stretch,
            children: [mainColumn(), const SizedBox(height: 18), leaderboard],
          );
        }
        return Row(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Expanded(flex: 3, child: mainColumn()),
            const SizedBox(width: 16),
            Expanded(flex: 2, child: leaderboard),
          ],
        );
      },
    );
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

  // ---- QUICK ACTIONS (module launcher) ----
  /// The editorial launcher grid. Mirrors the drawer's module set and the exact
  /// same permission gates and destinations: tab items switch the shell, the
  /// rest push their route. Tiles reflow as gates hide them (a cashier sees the
  /// three ungated ones).
  Widget _quickActions() {
    final auth = context.read<AuthCubit>();
    final tiles = <Widget>[
      _launchTile('New Sale', Icons.add_shopping_cart, () => context.push(Routes.sale), gold: true),
      _launchTile('Sales', Icons.receipt_long, () => widget.onSelectTab?.call(1)),
      if (auth.hasPermission(PermissionSlug.saleReturnView))
        _launchTile('Returns', Icons.assignment_return_outlined, _openReturns),
      if (auth.hasPermission(PermissionSlug.stockCheck))
        _launchTile('Stock Check', Icons.fact_check_outlined, _openStockCheck),
      _launchTile('Reports', Icons.bar_chart, () => widget.onSelectTab?.call(2)),
      if (auth.hasPermission(PermissionSlug.daySession))
        _launchTile('Day Session', Icons.schedule, _openDaySession),
    ];
    return Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        Padding(
          padding: const EdgeInsets.only(left: 4, bottom: 8),
          child: SectionLabel('Quick actions'),
        ),
        if (context.isTablet)
          // Tablet: the launchers read best as one unbroken row (the preview's
          // 5-across strip). Only fall back to a width-derived count when a
          // single row would squeeze tiles below a comfortable touch size.
          LayoutBuilder(
            builder: (ctx, c) {
              const gap = 12.0;
              final n = tiles.length;
              final single = (c.maxWidth - gap * (n - 1)) / n;
              final cols = single >= 118 ? n : ((c.maxWidth + gap) / (150 + gap)).floor().clamp(2, n);
              final tileW = (c.maxWidth - gap * (cols - 1)) / cols;
              // Height is what the tile's content needs (chip + label + padding),
              // not a share of its width: the phone's 0.92 ratio turns a 190px
              // tablet tile into a 200px slab of empty card.
              return GridView.count(
                crossAxisCount: cols,
                shrinkWrap: true,
                physics: const NeverScrollableScrollPhysics(),
                mainAxisSpacing: gap,
                crossAxisSpacing: gap,
                childAspectRatio: tileW / 118,
                children: tiles,
              );
            },
          )
        else
          _launcherRows(tiles),
      ],
    );
  }

  /// Phone launcher layout. A plain 3-up grid leaves a dead cell whenever the
  /// gated tile count isn't a multiple of three (five tiles → a card-sized hole
  /// beside the last one), and a width-derived aspect ratio makes every tile
  /// ~135pt tall for ~90pt of content. Balance the tiles across rows and let each
  /// row stretch to the full width instead, at the height the content needs, so
  /// the block always reads as a filled slab.
  Widget _launcherRows(List<Widget> tiles) {
    if (tiles.isEmpty) return const SizedBox.shrink();
    const gap = 12.0;
    final rows = (tiles.length / 3).ceil();
    final base = tiles.length ~/ rows;
    final extra = tiles.length % rows;
    final children = <Widget>[];
    var i = 0;
    for (var r = 0; r < rows; r++) {
      final count = base + (r < extra ? 1 : 0);
      if (r > 0) children.add(const SizedBox(height: gap));
      children.add(SizedBox(
        height: 100,
        child: Row(
          crossAxisAlignment: CrossAxisAlignment.stretch,
          children: [
            for (var c = 0; c < count; c++) ...[
              if (c > 0) const SizedBox(width: gap),
              Expanded(child: tiles[i + c]),
            ],
          ],
        ),
      ));
      i += count;
    }
    return Column(children: children);
  }

  /// One launcher tile. [gold] marks the primary CTA (New Sale) with the gold
  /// champagne accent instead of the emerald tint.
  Widget _launchTile(String label, IconData icon, VoidCallback onTap, {bool gold = false}) {
    final p = context.astra;
    final chipBg = gold ? p.accent.withValues(alpha: p.isDark ? 0.22 : 0.16) : p.tint;
    final chipFg = gold ? p.goldText : p.primary;
    return AstraCard(
      radius: 20,
      padding: const EdgeInsets.symmetric(vertical: 12, horizontal: 8),
      onTap: onTap,
      child: Column(
        mainAxisAlignment: MainAxisAlignment.center,
        children: [
          Container(
            width: 44,
            height: 44,
            decoration: BoxDecoration(color: chipBg, borderRadius: BorderRadius.circular(15)),
            child: Icon(icon, size: 22, color: chipFg),
          ),
          const SizedBox(height: 10),
          Text(label,
              textAlign: TextAlign.center,
              maxLines: 1,
              overflow: TextOverflow.ellipsis,
              style: ui(size: 11.5, weight: FontWeight.w700, color: p.ink)),
        ],
      ),
    );
  }

  /// Slim inline notice shown in place of the Insights block when the dashboard
  /// metrics fail to load — the hero and Quick actions stay usable above it.
  Widget _insightsUnavailable(AdminCubit admin) {
    final p = context.astra;
    return AstraCard(
      child: Column(
        children: [
          Icon(Icons.wifi_off, size: 26, color: p.textMuted),
          const SizedBox(height: 10),
          Text('Insights unavailable', style: ui(size: 13, weight: FontWeight.w800, color: p.ink)),
          const SizedBox(height: 4),
          Text(admin.error ?? 'Pull down to refresh and try again.',
              textAlign: TextAlign.center,
              style: ui(size: 11.5, weight: FontWeight.w500, color: p.textMuted)),
          const SizedBox(height: 14),
          AstraButton(label: 'Retry', icon: Icons.refresh, expand: false, onTap: admin.loadDashboard),
        ],
      ),
    );
  }

  // ---- TOP PERFORMERS ----
  /// Whether to show the leaderboard at all.
  ///
  /// It ranks the branch's staff against one another by revenue, which is a
  /// manager's read of the floor — not something to put in front of the people
  /// being ranked, who would be seeing colleagues' takings. So the gate is the
  /// data scope, not the admin flag: anyone the API answers with the whole
  /// branch — an admin, or a back-office 'user' account without the flag — has
  /// a board to read. Only a rank-and-file employee is kept out, and for them
  /// it would be a board of one anyway.
  bool _showLeaderboard(AdminCubit admin) =>
      (context.read<AuthCubit>().user?.seesAllRecords ?? false) && admin.topStylists.isNotEmpty;

  /// Ranked for the open day session's date only — the same business day the
  /// KPI cards report — so the board reads as "who is ahead today", not as an
  /// all-time honour roll that never moves.
  Widget _topPerformers(AdminCubit admin) {
    final p = context.astra;
    final top = admin.topStylists;
    // Guarded at both call sites; kept here so the card can never be rendered
    // by a third one that forgets.
    if (top.isEmpty || !_showLeaderboard(admin)) return const SizedBox.shrink();
    final day = Dates.human(admin.topStylistsDate);
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
              Expanded(
                child: Text('Top performers',
                    maxLines: 1,
                    overflow: TextOverflow.ellipsis,
                    style: ui(size: 13, weight: FontWeight.w700, color: p.ink)),
              ),
              if (day.isNotEmpty)
                Text(day, style: ui(size: 10.5, weight: FontWeight.w600, color: p.textMuted)),
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

  // ---- SESSION PAYMENTS ----
  /// Replaces the old static "Business overview" counts with an actionable
  /// breakdown of what was collected during the branch's open day session,
  /// split by payment method — the figure an owner reconciles at day-close.
  /// Empty when no day session is open.
  Widget _paymentSplit(AdminCubit admin) {
    final p = context.astra;
    final methods = admin.dashboard?.payments ?? const <Metric>[];
    final total = methods.fold<double>(0, (sum, m) => sum + asNum(m.value));

    return Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        Padding(
          padding: const EdgeInsets.only(left: 4, bottom: 10),
          child: SectionLabel('Session payments'),
        ),
        AstraCard(
          child: methods.isEmpty
              ? Padding(
                  padding: const EdgeInsets.symmetric(vertical: 12),
                  child: Row(
                    children: [
                      Icon(Icons.account_balance_wallet_outlined, size: 18, color: p.textMuted),
                      const SizedBox(width: 10),
                      Expanded(
                        child: Text('No payments collected this session yet.',
                            style: ui(size: 12, weight: FontWeight.w600, color: p.textMuted)),
                      ),
                    ],
                  ),
                )
              : Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    Row(
                      children: [
                        Icon(Icons.account_balance_wallet_outlined, size: 16, color: p.primary),
                        const SizedBox(width: 7),
                        Text('Collected this session', style: ui(size: 13, weight: FontWeight.w700, color: p.ink)),
                        const Spacer(),
                        Text(Money.of(total), style: serif(size: 16, color: p.primaryDark)),
                      ],
                    ),
                    const SizedBox(height: 16),
                    for (var i = 0; i < methods.length; i++) ...[
                      if (i != 0) const SizedBox(height: 13),
                      _paymentRow(methods[i], total, i),
                    ],
                  ],
                ),
        ),
      ],
    );
  }

  /// One payment-method row: colour dot, name, amount, share %, and a bar
  /// sized to its slice of today's total.
  Widget _paymentRow(Metric m, double total, int i) {
    final p = context.astra;
    final amount = asNum(m.value);
    final frac = total <= 0 ? 0.0 : amount / total;
    final pct = (frac * 100).round();
    final bars = [p.primary, p.accent, AstraPalette.success, p.primaryDark];
    final color = bars[i % bars.length];
    return Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        Row(
          children: [
            Container(width: 8, height: 8, decoration: BoxDecoration(color: color, shape: BoxShape.circle)),
            const SizedBox(width: 8),
            Expanded(
              child: Text(m.title, maxLines: 1, overflow: TextOverflow.ellipsis,
                  style: ui(size: 12.5, weight: FontWeight.w700, color: p.ink)),
            ),
            Text(Money.of(amount), style: serif(size: 13.5, color: p.ink)),
            const SizedBox(width: 8),
            SizedBox(
              width: 36,
              child: Text('$pct%',
                  textAlign: TextAlign.right,
                  style: ui(size: 11, weight: FontWeight.w700, color: p.textMuted)),
            ),
          ],
        ),
        const SizedBox(height: 7),
        ProgressBar(fraction: frac, color: color),
      ],
    );
  }
}
