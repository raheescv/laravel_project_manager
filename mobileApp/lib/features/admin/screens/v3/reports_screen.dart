import 'dart:async';

import 'package:flutter/material.dart';
import 'package:provider/provider.dart';

import 'package:invo/features/auth/logic/auth_cubit/auth_cubit.dart';
import 'package:invo/shared/logic/branch_cubit/branch_cubit.dart';
import 'package:invo/shared/domain/constants/mobile_permissions.dart';
import 'package:invo/shared/domain/helpers/formatters.dart';
import 'package:invo/shared/domain/helpers/responsive.dart';
import 'package:invo/shared/domain/models/index.dart';
import 'package:invo/features/admin/logic/admin_cubit/admin_cubit.dart';
import 'package:invo/shared/utils/components/theme/index.dart';
import 'package:invo/shared/widgets/astra_widgets.dart';
import 'package:invo/shared/widgets/charts.dart';
import 'package:invo/shared/widgets/tablet_widgets.dart';

part 'reports_overview_sections.dart';

class ReportsScreen extends StatefulWidget {
  const ReportsScreen({super.key});
  @override
  State<ReportsScreen> createState() => _ReportsScreenState();
}

class _ReportsScreenState extends State<ReportsScreen> {
  final _scrollCtl = ScrollController();
  StreamSubscription<int>? _branchSub;

  /// The two reports this page holds: 0 = Sales Overview (performance,
  /// payments, per-day trend), 1 = Breakdown (By Item / By Stylist ranking).
  /// The date range is shared, so it stays on top of both.
  int _tab = 0;

  @override
  void initState() {
    super.initState();
    _scrollCtl.addListener(_onScroll);
    // Skip the report fetches entirely when the user can't view reports —
    // the endpoint would 403 (see EnsureMobilePermission on /admin/reports).
    if (!context.read<AuthCubit>().hasPermission(PermissionSlug.report)) return;
    WidgetsBinding.instance.addPostFrameCallback((_) {
      context.read<AdminCubit>()
        ..loadReports(force: true)
        ..loadOverview();
    });
    // The shell keeps this screen alive, so reload the current range for the
    // new branch when the active branch changes. `force` because the range is
    // the same but nothing cached for the old branch survives it.
    _branchSub = context.read<BranchCubit>().onBranchChanged.listen((_) {
      if (!mounted) return;
      context.read<AdminCubit>()
        ..loadReports(force: true)
        ..loadOverview();
    });
  }

  @override
  void dispose() {
    _scrollCtl.dispose();
    _branchSub?.cancel();
    super.dispose();
  }

  /// Switch report. The two reports have unrelated lengths, so the shared
  /// scroll position is reset instead of carrying over.
  void _setTab(int i) {
    if (_tab == i) return;
    setState(() => _tab = i);
    if (_scrollCtl.hasClients) _scrollCtl.jumpTo(0);
  }

  /// Infinite scroll: pull the next page of the breakdown once near the bottom.
  /// Only the breakdown report paginates.
  void _onScroll() {
    if (_tab != 1) return;
    if (!context.read<AuthCubit>().hasPermission(PermissionSlug.report)) return;
    if (!_scrollCtl.hasClients) return;
    final pos = _scrollCtl.position;
    if (pos.pixels >= pos.maxScrollExtent - 500) {
      context.read<AdminCubit>().loadMoreReport();
    }
  }

  @override
  Widget build(BuildContext context) {
    final admin = context.watch<AdminCubit>();
    final canView = context.read<AuthCubit>().hasPermission(PermissionSlug.report);

    // Tablet has no header band — the page head carries the title and the range
    // controls in one toolbar row (the preview's `.pagehead`).
    if (context.isTablet) {
      return Scaffold(
        backgroundColor: Colors.transparent,
        body: AstraBackground(
          child: SafeArea(
            bottom: false,
            child: canView
                ? Column(
                    children: [
                      _reportsPageHead(admin),
                      Expanded(child: MaxWidthBox(maxWidth: 1120, child: _tabletReports(admin))),
                    ],
                  )
                : _restricted(),
          ),
        ),
      );
    }
    return Scaffold(
      backgroundColor: Colors.transparent,
      body: AstraBackground(
        child: Column(
          children: [
            EmeraldHeader(
              title: 'Reports',
              subtitle: 'Every angle on your sales',
              trailing: canView ? HeaderIconButton(icon: Icons.download, gold: true) : null,
            ),
            Expanded(
              child: canView
                  ? MaxWidthBox(
                maxWidth: 820,
                child: _tab == 1
                    // Breakdown: the card fills what's left and scrolls its own
                    // rows, so they are built lazily as they come into view.
                    // Controls and the grand total stay put, which is also the
                    // better read on a long list.
                    ? Padding(
                        padding: const EdgeInsets.fromLTRB(16, 14, 16, 16),
                        child: Column(
                          children: [
                            _reportTabs(),
                            const SizedBox(height: 8),
                            _dateFilter(admin),
                            const SizedBox(height: 8),
                            Expanded(child: _breakdownCard(admin)),
                          ],
                        ),
                      )
                    : ListView(
                  controller: _scrollCtl,
                  padding: const EdgeInsets.fromLTRB(16, 14, 16, 110),
                  children: [
                    // One gap between every section keeps spacing uniform & DRY;
                    // null sections (e.g. the trend on a single-day range) drop out
                    // entirely so they don't leave an empty gap.
                    for (final section in <Widget?>[
                      _reportTabs(),
                      _dateFilter(admin),
                      ..._tabSections(admin),
                    ])
                      if (section != null) Padding(padding: const EdgeInsets.only(bottom: 8), child: section),
                  ],
                ),
              )
                  : _restricted(),
            ),
          ],
        ),
      ),
    );
  }

  /// Sections of the currently selected report (the date range is rendered
  /// separately, above the switcher's output, since both reports share it).
  List<Widget?> _tabSections(AdminCubit admin) => _tab == 0
      ? [_salesPerformance(admin), _paymentOverview(admin), _byDay(admin)]
      : [_breakdownCard(admin)];

  /// Report switcher — picks which of the two reports the page shows.
  Widget _reportTabs() {
    final p = context.astra;
    Widget tab(String label, int index, IconData icon) {
      final active = _tab == index;
      return Expanded(
        child: GestureDetector(
          onTap: () => _setTab(index),
          child: Container(
            padding: const EdgeInsets.symmetric(vertical: 10),
            alignment: Alignment.center,
            decoration: BoxDecoration(
              gradient: active ? p.primaryGradient : null,
              borderRadius: BorderRadius.circular(12),
              boxShadow: active ? context.astraTheme.floatShadow(p.primary) : null,
            ),
            child: Row(
              mainAxisSize: MainAxisSize.min,
              children: [
                Icon(icon, size: 15, color: active ? Colors.white : p.textSecondary),
                const SizedBox(width: 7),
                Flexible(
                  child: Text(label,
                      maxLines: 1,
                      overflow: TextOverflow.ellipsis,
                      style: ui(size: 12.5, weight: FontWeight.w800, color: active ? Colors.white : p.textSecondary)),
                ),
              ],
            ),
          ),
        ),
      );
    }

    return Container(
      padding: const EdgeInsets.all(4),
      decoration: BoxDecoration(color: p.tint, borderRadius: BorderRadius.circular(15)),
      child: Row(children: [
        tab('Overview', 0, Icons.insights_rounded),
        tab('Breakdown', 1, Icons.leaderboard_rounded),
      ]),
    );
  }

  Widget _restricted() => const Center(
        child: Padding(
          padding: EdgeInsets.all(24),
          child: EmptyState(
            icon: Icons.lock_outline,
            title: 'Reports restricted',
            message: "You don't have permission to view sales reports. "
                'Ask an administrator to grant access.',
          ),
        ),
      );

  /// Tablet page head — the title plus the whole range control in one toolbar
  /// row, replacing both the header band and the phone's stacked filter card.
  Widget _reportsPageHead(AdminCubit admin) {
    final p = context.astra;
    final custom = admin.rangePreset == 'custom';
    const presets = [('Today', 'today'), ('7 Days', '7d'), ('30 Days', '30d'), ('Month', 'month')];
    return TabletPageHead(
      title: 'Reports',
      subtitle: Dates.range(admin.startDate, admin.endDate),
      actions: [
        for (final (label, id) in presets)
          TabletFilterChip(label: label, active: admin.rangePreset == id, onTap: () => admin.setPreset(id)),
        GestureDetector(
          onTap: () => _pickCustom(admin),
          child: Container(
            width: 34,
            height: 34,
            alignment: Alignment.center,
            decoration: BoxDecoration(
              gradient: custom ? p.accentGradient : null,
              color: custom ? null : p.tint,
              borderRadius: BorderRadius.circular(11),
            ),
            child: Icon(Icons.edit_calendar, size: 17, color: custom ? p.primaryDark : p.primary),
          ),
        ),
      ],
    );
  }

  /// Tablet: the date filter spans the top as a toolbar (the page head); the
  /// switcher picks the report and the Overview's sections split into two
  /// columns to use the width. The split only kicks in once each column would
  /// still be at least phone-width — on a small tablet in portrait two columns
  /// would squeeze every chart and donut, so those stack instead. The Breakdown
  /// report is a single ranked table, so it stays one column.
  Widget _tabletReports(AdminCubit admin) {
    Widget col(List<Widget?> items) => Column(
          crossAxisAlignment: CrossAxisAlignment.stretch,
          children: [
            for (final s in items)
              if (s != null) Padding(padding: const EdgeInsets.only(bottom: 12), child: s),
          ],
        );
    final left = <Widget?>[_salesPerformance(admin)];
    final right = <Widget?>[_paymentOverview(admin), _byDay(admin)];

    // Breakdown fills the pane and scrolls its own rows — see the phone body.
    if (_tab == 1) {
      return Padding(
        padding: const EdgeInsets.fromLTRB(20, 16, 20, 20),
        child: Column(
          children: [
            _reportTabs(),
            const SizedBox(height: 14),
            Expanded(child: _breakdownCard(admin)),
          ],
        ),
      );
    }

    return ListView(
      controller: _scrollCtl,
      padding: const EdgeInsets.fromLTRB(20, 16, 20, 40),
      children: [
        Padding(padding: const EdgeInsets.only(bottom: 14), child: _reportTabs()),
        LayoutBuilder(
          builder: (ctx, c) {
            if (c.maxWidth < 820) return col([...left, ...right]);
            return Row(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Expanded(child: col(left)),
                const SizedBox(width: 16),
                Expanded(child: col(right)),
              ],
            );
          },
        ),
      ],
    );
  }

  // ---- Premium date-range filter ----

  Widget _dateFilter(AdminCubit admin) {
    final p = context.astra;
    final custom = admin.rangePreset == 'custom';
    return AstraCard(
      padding: const EdgeInsets.all(12),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Row(
            children: [
              IconChip(icon: Icons.event_note, size: 40, radius: 12, fg: p.goldText),
              const SizedBox(width: 12),
              Expanded(
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    Text('DATE RANGE',
                        style: ui(size: 9.5, weight: FontWeight.w800, color: p.textMuted, letterSpacing: 0.9)),
                    const SizedBox(height: 2),
                    Text(Dates.range(admin.startDate, admin.endDate), style: serif(size: 16.5, color: p.ink)),
                  ],
                ),
              ),
              GestureDetector(
                onTap: () => _pickCustom(admin),
                child: Container(
                  width: 40,
                  height: 40,
                  decoration: BoxDecoration(
                    gradient: custom ? p.accentGradient : null,
                    color: custom ? null : p.tint,
                    borderRadius: BorderRadius.circular(12),
                  ),
                  child: Icon(Icons.edit_calendar,
                      size: 18, color: custom ? p.primaryDark : p.primary),
                ),
              ),
            ],
          ),
          const SizedBox(height: 11),
          Row(
            children: [
              _presetChip(admin, 'Today', 'today'),
              const SizedBox(width: 7),
              _presetChip(admin, '7 Days', '7d'),
              const SizedBox(width: 7),
              _presetChip(admin, '30 Days', '30d'),
              const SizedBox(width: 7),
              _presetChip(admin, 'Month', 'month'),
            ],
          ),
        ],
      ),
    );
  }

  Widget _presetChip(AdminCubit admin, String label, String id) {
    final p = context.astra;
    final active = admin.rangePreset == id;
    return Expanded(
      child: GestureDetector(
        onTap: () => admin.setPreset(id),
        child: Container(
          padding: const EdgeInsets.symmetric(vertical: 9),
          alignment: Alignment.center,
          decoration: BoxDecoration(
            gradient: active ? p.primaryGradient : null,
            color: active ? null : p.tint,
            borderRadius: BorderRadius.circular(10),
            boxShadow: active ? context.astraTheme.floatShadow(p.primary) : null,
          ),
          child: Text(label,
              maxLines: 1,
              overflow: TextOverflow.ellipsis,
              style: ui(size: 11, weight: FontWeight.w800, color: active ? Colors.white : p.textSecondary)),
        ),
      ),
    );
  }

  Future<void> _pickCustom(AdminCubit admin) async {
    final p = context.astra;
    final now = DateTime.now();
    final picked = await showDateRangePicker(
      context: context,
      firstDate: DateTime(now.year - 3),
      lastDate: DateTime(now.year, now.month, now.day),
      initialDateRange: DateTimeRange(start: admin.startDate, end: admin.endDate),
      helpText: 'Select report range',
      builder: (ctx, child) => Theme(
        data: Theme.of(ctx).copyWith(
          colorScheme: (p.isDark ? const ColorScheme.dark() : const ColorScheme.light()).copyWith(
            primary: p.primary,
            onPrimary: Colors.white,
            surface: p.card,
            onSurface: p.ink,
            secondary: p.accent,
          ),
        ),
        child: child!,
      ),
    );
    if (picked != null) admin.setCustomRange(picked.start, picked.end);
  }

  // ---- Sales Overview dashboard (type=overview) ------------------------------
  // Status colours kept local so the card reads the same across all skins.
  static const _good = Color(0xFF1F9D63);
  static const _warn = Color(0xFFD9890C);
  static const _bad = Color(0xFFD4546A);

  // ---- Shared overview helpers ----

  Widget _pill(String text, Color bg, Color fg) => Container(
        padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 3),
        decoration: BoxDecoration(color: bg, borderRadius: BorderRadius.circular(999)),
        child: Text(text, style: ui(size: 9.5, weight: FontWeight.w800, color: fg, letterSpacing: 0.3)),
      );

  Color _sliceColor(int i, AstraPalette p) {
    final palette = [p.primary, _good, _warn, p.accent, p.primaryDark];
    return palette[i % palette.length];
  }

  IconData _methodIcon(String name) {
    switch (name.toLowerCase()) {
      case 'cash':
        return Icons.payments_rounded;
      case 'card':
        return Icons.credit_card_rounded;
      case 'bank':
        return Icons.account_balance_rounded;
      case 'credit':
        return Icons.account_balance_wallet_rounded;
      case 'mobile money':
      case 'mobile':
        return Icons.smartphone_rounded;
      default:
        return Icons.credit_card_rounded;
    }
  }

  Color _methodColor(String name, AstraPalette p) {
    switch (name.toLowerCase()) {
      case 'cash':
        return _good;
      case 'card':
        return p.primary;
      case 'bank':
        return _warn;
      case 'credit':
        return p.accent;
      default:
        return p.primary;
    }
  }

  // ---- Per-day trend ----

  Widget? _byDay(AdminCubit admin) {
    final p = context.astra;
    final pts = admin.reportTrendPoints;
    final labels = admin.reportTrendLabels;
    if (pts.length < 2) return null;
    final maxV = pts.reduce((a, b) => a > b ? a : b);
    final total = pts.fold<double>(0, (a, b) => a + b);
    var peakTaken = false; // one peak only, even when two days tie
    final data = <BarDatum>[];
    for (var i = 0; i < pts.length; i++) {
      final iso = labels.length > i ? labels[i] : '';
      final isPeak = maxV > 0 && pts[i] == maxV && !peakTaken;
      if (isPeak) peakTaken = true;
      data.add(BarDatum(_dayLabel(iso, pts.length), pts[i], peak: isPeak, detail: _dayDetail(iso)));
    }
    // The trend rides on loadReports (via _applyRangeSummary), not the overview
    // call, so it reports that request's progress.
    final busy = admin.reportLoading;
    return AstraCard(
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          _cardHeader(Icons.bar_chart_rounded, 'Gross sales by day',
              busy: busy, trailing: _pill('Total ${Money.compact(total)}', p.tint, p.primary)),
          const SizedBox(height: 12),
          _refreshing(
            busy,
            BarChart(
              data: data,
              // Tall enough for the value labels to clear the bars; long ranges
              // scroll sideways rather than shrink (see BarChart.minSlot).
              height: 150,
              showReadout: true,
            ),
          ),
        ],
      ),
    );
  }

  String _dayLabel(String iso, int count) {
    final d = DateTime.tryParse(iso);
    if (d == null) return '';
    if (count <= 8) {
      const days = ['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'];
      return days[d.weekday - 1];
    }
    return '${d.day}/${d.month}';
  }

  /// Full date for the chart readout, e.g. `Sat, 21 Jun 2026`.
  String _dayDetail(String iso) {
    final d = DateTime.tryParse(iso);
    return d == null ? '' : Dates.weekday(d);
  }

  // ---- By Item / By Stylist breakdown (toggle + metric + ranked table) -------

  /// One card: the By Item / By Stylist segmented control, the Amount/Qty metric
  /// row (items only), then the ranked, paginated table — was three stacked
  /// blocks, now a single cohesive card.
  Widget _breakdownCard(AdminCubit admin) {
    return AstraCard(
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          _segmentToggle(admin),
          if (admin.reportType == 'itemwise') ...[
            const SizedBox(height: 10),
            _itemMetricRow(admin),
            const SizedBox(height: 10),
            _itemTypeRow(admin),
          ],
          const SizedBox(height: 14),
          // The rows are the only unbounded part of this card, so they get the
          // remaining height and scroll inside it — built lazily, unlike the
          // old `for (final r in rows)` which laid out every loaded page on
          // every rebuild. Keeping the card a plain box (rather than slivers)
          // means it still renders identically under all five skins, including
          // Glass, whose BackdropFilter has no sliver equivalent.
          Expanded(child: _breakdownBody(admin)),
        ],
      ),
    );
  }

  Widget _segmentToggle(AdminCubit admin) {
    final p = context.astra;
    Widget seg(String label, String type, IconData icon) {
      final active = admin.reportType == type;
      return Expanded(
        child: GestureDetector(
          onTap: () => admin.setReportType(type),
          child: Container(
            padding: const EdgeInsets.symmetric(vertical: 9),
            alignment: Alignment.center,
            decoration: BoxDecoration(
              gradient: active ? p.primaryGradient : null,
              borderRadius: BorderRadius.circular(10),
              boxShadow: active ? context.astraTheme.floatShadow(p.primary) : null,
            ),
            child: Row(
              mainAxisSize: MainAxisSize.min,
              children: [
                Icon(icon, size: 14, color: active ? Colors.white : p.textSecondary),
                const SizedBox(width: 7),
                Text(label,
                    style: ui(size: 12, weight: FontWeight.w800, color: active ? Colors.white : p.textSecondary)),
              ],
            ),
          ),
        ),
      );
    }

    return Container(
      padding: const EdgeInsets.all(4),
      decoration: BoxDecoration(color: p.tint, borderRadius: BorderRadius.circular(13)),
      child: Row(children: [
        seg('By Item', 'itemwise', Icons.inventory_2_rounded),
        seg('By Stylist', 'employeewise', Icons.people_alt_rounded),
      ]),
    );
  }

  Widget _itemMetricRow(AdminCubit admin) {
    final p = context.astra;
    Widget chip(String label, String id, IconData icon) {
      final active = admin.itemMetric == id;
      return Expanded(
        child: GestureDetector(
          onTap: () => admin.setItemMetric(id),
          child: Container(
            padding: const EdgeInsets.symmetric(vertical: 8),
            alignment: Alignment.center,
            decoration: BoxDecoration(
              gradient: active ? p.accentGradient : null,
              color: active ? null : p.tint,
              borderRadius: BorderRadius.circular(10),
            ),
            child: Row(
              mainAxisSize: MainAxisSize.min,
              children: [
                Icon(icon, size: 13, color: active ? p.primaryDark : p.textSecondary),
                const SizedBox(width: 6),
                Text(label,
                    style: ui(size: 11, weight: FontWeight.w800, color: active ? p.primaryDark : p.textSecondary)),
              ],
            ),
          ),
        ),
      );
    }

    return Row(
      children: [
        Text('RANK BY',
            style: ui(size: 9.5, weight: FontWeight.w800, color: p.textMuted, letterSpacing: 0.8)),
        const SizedBox(width: 10),
        chip('Amount', 'amount', Icons.payments_rounded),
        const SizedBox(width: 7),
        chip('Qty', 'qty', Icons.numbers_rounded),
      ],
    );
  }

  /// Item-report type filter: All / Product / Service / Asset. Passing null to
  /// the controller clears the filter (the server returns every type). Mirrors
  /// the web report's `product_type` filter.
  Widget _itemTypeRow(AdminCubit admin) {
    final p = context.astra;
    Widget chip(String label, String? id, IconData icon) {
      final active = admin.itemProductType == id;
      return Expanded(
        child: GestureDetector(
          onTap: () => admin.setItemProductType(id),
          child: Container(
            padding: const EdgeInsets.symmetric(vertical: 8),
            alignment: Alignment.center,
            decoration: BoxDecoration(
              gradient: active ? p.primaryGradient : null,
              color: active ? null : p.tint,
              borderRadius: BorderRadius.circular(10),
              boxShadow: active ? context.astraTheme.floatShadow(p.primary) : null,
            ),
            child: Row(
              mainAxisSize: MainAxisSize.min,
              children: [
                Icon(icon, size: 13, color: active ? Colors.white : p.textSecondary),
                const SizedBox(width: 6),
                Text(label,
                    maxLines: 1,
                    overflow: TextOverflow.ellipsis,
                    style: ui(size: 11, weight: FontWeight.w800, color: active ? Colors.white : p.textSecondary)),
              ],
            ),
          ),
        ),
      );
    }

    return Row(
      children: [
        Text('TYPE',
            style: ui(size: 9.5, weight: FontWeight.w800, color: p.textMuted, letterSpacing: 0.8)),
        const SizedBox(width: 10),
        chip('All', null, Icons.apps_rounded),
        const SizedBox(width: 7),
        chip('Product', 'product', Icons.inventory_2_rounded),
        const SizedBox(width: 7),
        chip('Service', 'service', Icons.design_services_rounded),
      ],
    );
  }

  /// Ranked breakdown body (share-of-total bars + top highlight). Lives inside
  /// the breakdown card so the toggle stays visible across loading/empty states.
  Widget _breakdownBody(AdminCubit admin) {
    final p = context.astra;
    if (admin.reportLoading) {
      return const Center(child: CircularProgressIndicator());
    }
    if (admin.reportError != null) {
      return Center(
        child: EmptyState(
            icon: Icons.wifi_off, title: 'Report unavailable', message: admin.reportError),
      );
    }
    if (admin.reportRows.isEmpty) {
      return const Center(
        child: EmptyState(icon: Icons.bar_chart, title: 'No data for this period'),
      );
    }

    final isItem = admin.reportType == 'itemwise';
    final rowIcon = isItem ? Icons.inventory_2_rounded : Icons.person_rounded;
    final maxAmount = admin.reportRows.fold<double>(0, (a, r) => r.amount > a ? r.amount : a);
    // One trailing slot for the load-more footer while further pages remain.
    final extra = admin.reportHasMore ? 1 : 0;

    return Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        SectionLabel(
          isItem ? 'Item breakdown' : 'Stylist breakdown',
          trailing: _pill('${admin.reportRowCount} ${isItem ? 'items' : 'stylists'}', p.tint, p.textSecondary),
        ),
        const SizedBox(height: 10),
        Expanded(
          child: ListView.builder(
            controller: _scrollCtl,
            padding: EdgeInsets.zero,
            itemCount: admin.reportRows.length + extra,
            itemBuilder: (_, i) {
              // Infinite-scroll footer: a spinner while the next page streams
              // in, plus a tap-to-load fallback in case the scroll trigger is
              // missed. New pages arrive via [_onScroll].
              if (i == admin.reportRows.length) return _loadMoreFooter(admin);
              return _reportRow(admin, admin.reportRows[i], rowIcon, maxAmount);
            },
          ),
        ),
        Container(height: 1.5, color: p.hairline, margin: const EdgeInsets.fromLTRB(2, 8, 2, 10)),
        Padding(
          padding: const EdgeInsets.symmetric(horizontal: 2),
          child: Row(
            mainAxisAlignment: MainAxisAlignment.spaceBetween,
            children: [
              Text(isItem && admin.itemMetric == 'qty' ? 'Total qty' : 'Grand total',
                  style: ui(size: 12.5, weight: FontWeight.w800, color: p.ink)),
              Text(admin.reportTotalText, style: serif(size: 17, color: p.primaryDark)),
            ],
          ),
        ),
      ],
    );
  }

  /// Footer under the loaded rows while more pages remain: a spinner during a
  /// fetch, otherwise a tap-to-load cue showing how many of the total are on
  /// screen. Scrolling near the bottom auto-loads the next page via [_onScroll].
  Widget _loadMoreFooter(AdminCubit admin) {
    final p = context.astra;
    return Padding(
      padding: const EdgeInsets.symmetric(vertical: 12),
      child: Center(
        child: admin.reportLoadingMore
            ? Row(
                mainAxisSize: MainAxisSize.min,
                children: [
                  SizedBox(
                      width: 15,
                      height: 15,
                      child: CircularProgressIndicator(strokeWidth: 2.2, color: p.primary)),
                  const SizedBox(width: 9),
                  Text('Loading more…', style: ui(size: 11.5, weight: FontWeight.w700, color: p.textMuted)),
                ],
              )
            : GestureDetector(
                onTap: admin.loadMoreReport,
                behavior: HitTestBehavior.opaque,
                child: Text('Showing ${admin.reportRows.length} of ${admin.reportRowCount} · tap to load more',
                    style: ui(size: 11, weight: FontWeight.w700, color: p.primary)),
              ),
      ),
    );
  }

  Widget _reportRow(AdminCubit admin, ReportRow r, IconData icon, double maxAmount) {
    final p = context.astra;
    final frac = admin.reportTotal > 0 ? r.amount / admin.reportTotal : 0.0;
    final pct = frac * 100;
    final isTop = maxAmount > 0 && r.amount >= maxAmount;
    return Container(
      margin: const EdgeInsets.only(bottom: 4),
      padding: const EdgeInsets.all(9),
      decoration: BoxDecoration(
        color: isTop ? p.tint : Colors.transparent,
        borderRadius: BorderRadius.circular(12),
      ),
      child: Row(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Container(
            width: 38,
            height: 38,
            alignment: Alignment.center,
            decoration: BoxDecoration(
              color: isTop ? p.accent.withValues(alpha: 0.18) : p.tint,
              borderRadius: BorderRadius.circular(11),
            ),
            child: Icon(icon, size: 17, color: isTop ? p.goldText : p.primary),
          ),
          const SizedBox(width: 11),
          Expanded(
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Row(
                  children: [
                    Flexible(
                      child: Text(r.title,
                          maxLines: 1,
                          overflow: TextOverflow.ellipsis,
                          style: ui(size: 12.5, weight: FontWeight.w800, color: p.ink)),
                    ),
                    if (isTop) ...[
                      const SizedBox(width: 7),
                      Container(
                        padding: const EdgeInsets.symmetric(horizontal: 6, vertical: 2),
                        decoration: BoxDecoration(
                          color: p.accent.withValues(alpha: 0.18),
                          borderRadius: BorderRadius.circular(20),
                        ),
                        child: Row(
                          mainAxisSize: MainAxisSize.min,
                          children: [
                            Icon(Icons.star_rounded, size: 10, color: p.goldText),
                            const SizedBox(width: 3),
                            Text('TOP',
                                style: ui(size: 8.5, weight: FontWeight.w900, color: p.goldText, letterSpacing: 0.6)),
                          ],
                        ),
                      ),
                    ],
                  ],
                ),
                const SizedBox(height: 2),
                Text(r.subtitle,
                    maxLines: 1,
                    overflow: TextOverflow.ellipsis,
                    style: ui(size: 10.5, weight: FontWeight.w600, color: p.textMuted)),
                const SizedBox(height: 8),
                ProgressBar(fraction: frac),
              ],
            ),
          ),
          const SizedBox(width: 11),
          Column(
            crossAxisAlignment: CrossAxisAlignment.end,
            children: [
              Text(r.value, style: ui(size: 13, weight: FontWeight.w800, color: p.ink)),
              const SizedBox(height: 3),
              Text('${pct.toStringAsFixed(pct >= 10 ? 0 : 1)}%',
                  style: ui(size: 10, weight: FontWeight.w700, color: p.textMuted)),
            ],
          ),
        ],
      ),
    );
  }
}
