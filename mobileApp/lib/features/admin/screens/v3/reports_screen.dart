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

class ReportsScreen extends StatefulWidget {
  const ReportsScreen({super.key});
  @override
  State<ReportsScreen> createState() => _ReportsScreenState();
}

class _ReportsScreenState extends State<ReportsScreen> {
  final _scrollCtl = ScrollController();
  StreamSubscription<int>? _branchSub;

  @override
  void initState() {
    super.initState();
    _scrollCtl.addListener(_onScroll);
    // Skip the report fetches entirely when the user can't view reports —
    // the endpoint would 403 (see EnsureMobilePermission on /admin/reports).
    if (!context.read<AuthCubit>().hasPermission(PermissionSlug.report)) return;
    WidgetsBinding.instance.addPostFrameCallback((_) {
      context.read<AdminCubit>()
        ..loadReports()
        ..loadOverview();
    });
    // The shell keeps this screen alive, so reload the current range for the
    // new branch when the active branch changes.
    _branchSub = context.read<BranchCubit>().onBranchChanged.listen((_) {
      if (!mounted) return;
      context.read<AdminCubit>()
        ..loadReports()
        ..loadOverview();
    });
  }

  @override
  void dispose() {
    _scrollCtl.dispose();
    _branchSub?.cancel();
    super.dispose();
  }

  /// Infinite scroll: pull the next page of the breakdown once near the bottom.
  void _onScroll() {
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
                child: ListView(
                  controller: _scrollCtl,
                  padding: const EdgeInsets.fromLTRB(16, 14, 16, 110),
                  children: [
                    // One gap between every section keeps spacing uniform & DRY;
                    // null sections (e.g. the trend on a single-day range) drop out
                    // entirely so they don't leave an empty gap.
                    for (final section in <Widget?>[
                      _dateFilter(admin),
                      _salesPerformance(admin),
                      _paymentOverview(admin),
                      _byDay(admin),
                      _breakdownCard(admin),
                    ])
                      if (section != null) Padding(padding: const EdgeInsets.only(bottom: 8), child: section),
                  ],
                ),
              )
                  : const Center(
                      child: Padding(
                        padding: EdgeInsets.all(24),
                        child: EmptyState(
                          icon: Icons.lock_outline,
                          title: 'Reports restricted',
                          message: "You don't have permission to view sales reports. "
                              'Ask an administrator to grant access.',
                        ),
                      ),
                    ),
            ),
          ],
        ),
      ),
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

  /// Sales Performance card — success rate, per-method Sales/Returns/Net,
  /// and the six gradient financial tiles.
  Widget _salesPerformance(AdminCubit admin) {
    final p = context.astra;
    final ov = admin.overview;
    if (ov == null) return _overviewPlaceholder(admin, 'Sales Performance', Icons.insights_rounded);
    final s = ov.summary;

    return AstraCard(
      padding: const EdgeInsets.all(12),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          _cardHeader(Icons.insights_rounded, 'Sales Performance',
              trailing: _pill('Disc ${Money.compact(s.discount)}', p.tint, p.textSecondary)),
          const SizedBox(height: 12),
          _miniStatsRow(s),
          const SizedBox(height: 12),
          Container(height: 1, color: p.hairline),
          const SizedBox(height: 12),
          _rateBar('Sales Success Rate', s.successRate, [p.primary, p.accent]),
          const SizedBox(height: 10),
          for (final m in ov.payments.methods) ...[
            _methodCard(m),
            const SizedBox(height: 8),
          ],
          const SizedBox(height: 2),
          _finTiles(s),
        ],
      ),
    );
  }

  Widget _methodCard(PaymentMethodStat m) {
    final p = context.astra;
    final color = _methodColor(m.method, p);
    return Container(
      padding: const EdgeInsets.all(10),
      decoration: BoxDecoration(
        color: p.isDark ? Colors.white.withValues(alpha: 0.03) : Colors.black.withValues(alpha: 0.02),
        borderRadius: BorderRadius.circular(13),
        border: Border.all(color: p.hairline),
      ),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Row(
            children: [
              Container(
                width: 32,
                height: 32,
                alignment: Alignment.center,
                decoration: BoxDecoration(color: color.withValues(alpha: 0.15), borderRadius: BorderRadius.circular(10)),
                child: Icon(_methodIcon(m.method), size: 17, color: color),
              ),
              const SizedBox(width: 9),
              Expanded(
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    Text(m.method, style: ui(size: 12.5, weight: FontWeight.w800, color: p.ink)),
                    Text('${m.transactions} transactions',
                        style: ui(size: 10, weight: FontWeight.w600, color: p.textMuted)),
                  ],
                ),
              ),
            ],
          ),
          const SizedBox(height: 8),
          Row(
            children: [
              _methodCell('Sales', m.sales, _good),
              const SizedBox(width: 7),
              _methodCell('Returns', m.returns, _warn),
              const SizedBox(width: 7),
              _methodCell('Net', m.net, m.net < 0 ? _bad : p.primary),
            ],
          ),
        ],
      ),
    );
  }

  Widget _methodCell(String label, double value, Color color) {
    final p = context.astra;
    return Expanded(
      child: Container(
        padding: const EdgeInsets.symmetric(vertical: 8, horizontal: 4),
        decoration: BoxDecoration(color: color.withValues(alpha: 0.12), borderRadius: BorderRadius.circular(9)),
        child: Column(
          children: [
            FittedBox(
              fit: BoxFit.scaleDown,
              child: Text(Money.plain(value), style: ui(size: 12.5, weight: FontWeight.w800, color: color)),
            ),
            const SizedBox(height: 2),
            Text(label.toUpperCase(),
                style: ui(size: 8.5, weight: FontWeight.w800, color: p.textMuted, letterSpacing: 0.4)),
          ],
        ),
      ),
    );
  }

  Widget _finTiles(OverviewSummary s) {
    final p = context.astra;
    final tiles = <Widget>[
      _finTile(Icons.payments_rounded, 'Gross Sales', s.grossSales, [p.primary, p.primaryDark]),
      _finTile(Icons.sell_rounded, 'Discounts', s.discount, const [Color(0xFFF59E0B), Color(0xFFD97706)]),
      _finTile(Icons.account_balance_wallet_rounded, 'Net Sales', s.netSales, const [Color(0xFF22C55E), Color(0xFF15803D)]),
      _finTile(Icons.inventory_2_rounded, 'Total Item', s.totalItem, const [Color(0xFF64748B), Color(0xFF334155)]),
      _finTile(Icons.shopping_cart_rounded, 'Products', s.productSale, const [Color(0xFF06B6D4), Color(0xFF0E7490)]),
      _finTile(Icons.star_rounded, 'Services', s.serviceSale, const [Color(0xFF8B5CF6), Color(0xFF6D28D9)]),
    ];
    return GridView.count(
      crossAxisCount: context.isTablet ? 3 : 2,
      shrinkWrap: true,
      physics: const NeverScrollableScrollPhysics(),
      mainAxisSpacing: 9,
      crossAxisSpacing: 9,
      childAspectRatio: context.isTablet ? 3.3 : 2.7,
      children: tiles,
    );
  }

  /// Compact horizontal money tile — icon beside label/value, no stranded
  /// whitespace (the old vertical spaceBetween left a big empty middle).
  Widget _finTile(IconData icon, String label, double value, List<Color> gradient) {
    return Container(
      padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 10),
      decoration: BoxDecoration(
        gradient: LinearGradient(begin: Alignment.topLeft, end: Alignment.bottomRight, colors: gradient),
        borderRadius: BorderRadius.circular(14),
        boxShadow: [BoxShadow(color: gradient.last.withValues(alpha: 0.28), blurRadius: 12, offset: const Offset(0, 6))],
      ),
      child: Row(
        children: [
          Container(
            width: 30,
            height: 30,
            alignment: Alignment.center,
            decoration: BoxDecoration(color: Colors.white.withValues(alpha: 0.22), borderRadius: BorderRadius.circular(9)),
            child: Icon(icon, size: 16, color: Colors.white),
          ),
          const SizedBox(width: 10),
          Expanded(
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              mainAxisSize: MainAxisSize.min,
              children: [
                Text(label.toUpperCase(),
                    maxLines: 1,
                    overflow: TextOverflow.ellipsis,
                    style: ui(size: 9, weight: FontWeight.w700, color: Colors.white.withValues(alpha: 0.92), letterSpacing: 0.4)),
                const SizedBox(height: 2),
                FittedBox(
                  fit: BoxFit.scaleDown,
                  alignment: Alignment.centerLeft,
                  child: Text(Money.compact(value), style: serif(size: 17, color: Colors.white)),
                ),
              ],
            ),
          ),
        ],
      ),
    );
  }

  /// Payment Overview card — sales/returns payments, net, collection rate,
  /// the methods list and a payment-method donut.
  Widget _paymentOverview(AdminCubit admin) {
    final p = context.astra;
    final ov = admin.overview;
    if (ov == null) return _overviewPlaceholder(admin, 'Payment Overview', Icons.account_balance_wallet_rounded);
    final pay = ov.payments;
    final net = pay.netPayment;

    final listMethods = pay.methods.where((m) => m.method.toLowerCase() != 'credit' && m.sales > 0).take(4).toList();
    final slices = <DonutSlice>[];
    for (var i = 0; i < pay.chart.length; i++) {
      if (pay.chart[i].value <= 0) continue;
      slices.add(DonutSlice(pay.chart[i].value, _sliceColor(i, p), pay.chart[i].label));
    }
    final chartTotal = slices.fold<double>(0, (a, s) => a + s.value);

    return AstraCard(
      padding: const EdgeInsets.all(12),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          _cardHeader(Icons.account_balance_wallet_rounded, 'Payment Overview'),
          const SizedBox(height: 12),
          Row(
            children: [
              _payTile('Sales Payments', pay.salesTotal, '${pay.salesTransactions} transactions', _good),
              const SizedBox(width: 10),
              _payTile('Returns Payments', pay.returnsTotal, '${pay.returnsTransactions} returns', _warn),
            ],
          ),
          const SizedBox(height: 12),
          Center(
            child: Column(
              children: [
                FittedBox(
                  fit: BoxFit.scaleDown,
                  child: Text(Money.of(net), style: serif(size: 30, color: net < 0 ? _bad : _good)),
                ),
                const SizedBox(height: 3),
                Text('Net Payments', style: ui(size: 11.5, weight: FontWeight.w700, color: p.textSecondary)),
                Text('${pay.totalTransactions} total transactions',
                    style: ui(size: 10, weight: FontWeight.w600, color: p.textMuted)),
              ],
            ),
          ),
          const SizedBox(height: 13),
          _rateBar('Collection Rate', ov.summary.collectionRate, const [_good, Color(0xFF3FC07E)]),
          if (listMethods.isNotEmpty) ...[
            const SizedBox(height: 16),
            SectionLabel('Payment Methods'),
            const SizedBox(height: 8),
            for (final m in listMethods) ...[
              _payMethodRow(m),
              const SizedBox(height: 7),
            ],
          ],
          if (slices.isNotEmpty) ...[
            Container(height: 1, color: p.hairline, margin: const EdgeInsets.symmetric(vertical: 14)),
            Row(
              children: [
                DonutChart(
                  slices: slices,
                  centerTop: Money.compact(chartTotal),
                  centerBottom: 'PAID',
                  size: 116,
                ),
                const SizedBox(width: 14),
                Expanded(
                  child: Column(
                    children: [
                      for (final sl in slices) _legendRow(sl),
                    ],
                  ),
                ),
              ],
            ),
          ],
        ],
      ),
    );
  }

  Widget _payTile(String label, double value, String caption, Color color) {
    final p = context.astra;
    return Expanded(
      child: Container(
        padding: const EdgeInsets.all(13),
        decoration: BoxDecoration(color: color.withValues(alpha: 0.12), borderRadius: BorderRadius.circular(14)),
        child: Column(
          children: [
            FittedBox(
              fit: BoxFit.scaleDown,
              child: Text(Money.of(value), style: serif(size: 18, color: color)),
            ),
            const SizedBox(height: 3),
            Text(label, style: ui(size: 10, weight: FontWeight.w600, color: p.textSecondary)),
            Text(caption, style: ui(size: 9.5, weight: FontWeight.w700, color: color)),
          ],
        ),
      ),
    );
  }

  Widget _payMethodRow(PaymentMethodStat m) {
    final p = context.astra;
    final color = _methodColor(m.method, p);
    return Container(
      padding: const EdgeInsets.all(9),
      decoration: BoxDecoration(
        color: p.isDark ? Colors.white.withValues(alpha: 0.03) : Colors.black.withValues(alpha: 0.02),
        borderRadius: BorderRadius.circular(12),
        border: Border.all(color: p.hairline),
      ),
      child: Row(
        children: [
          Container(
            width: 28,
            height: 28,
            alignment: Alignment.center,
            decoration: BoxDecoration(color: color.withValues(alpha: 0.15), borderRadius: BorderRadius.circular(8)),
            child: Icon(_methodIcon(m.method), size: 15, color: color),
          ),
          const SizedBox(width: 9),
          Expanded(child: Text(m.method, style: ui(size: 12, weight: FontWeight.w700, color: p.ink))),
          Column(
            crossAxisAlignment: CrossAxisAlignment.end,
            children: [
              Text(Money.plain(m.sales), style: ui(size: 12.5, weight: FontWeight.w800, color: p.ink)),
              Text('${m.salesTransactions} txns', style: ui(size: 9, weight: FontWeight.w600, color: p.textMuted)),
            ],
          ),
        ],
      ),
    );
  }

  Widget _legendRow(DonutSlice sl) {
    final p = context.astra;
    return Padding(
      padding: const EdgeInsets.symmetric(vertical: 4),
      child: Row(
        children: [
          Container(
            width: 11,
            height: 11,
            decoration: BoxDecoration(color: sl.color, borderRadius: BorderRadius.circular(4)),
          ),
          const SizedBox(width: 8),
          Expanded(
            child: Text(sl.label,
                maxLines: 1,
                overflow: TextOverflow.ellipsis,
                style: ui(size: 11.5, weight: FontWeight.w700, color: p.textSecondary)),
          ),
          Text(Money.compact(sl.value), style: ui(size: 11.5, weight: FontWeight.w800, color: p.ink)),
        ],
      ),
    );
  }

  // ---- Shared overview helpers ----

  Widget _overviewPlaceholder(AdminCubit admin, String title, IconData icon) {
    final p = context.astra;
    Widget body;
    if (admin.overviewError != null) {
      body = Row(
        children: [
          Icon(Icons.wifi_off_rounded, size: 18, color: p.textMuted),
          const SizedBox(width: 8),
          Expanded(child: Text(admin.overviewError!, style: ui(size: 12, weight: FontWeight.w600, color: p.textSecondary))),
        ],
      );
    } else {
      body = const SizedBox(
        height: 90,
        child: Center(child: SizedBox(width: 24, height: 24, child: CircularProgressIndicator(strokeWidth: 2.4))),
      );
    }
    return AstraCard(
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          _cardHeader(icon, title),
          const SizedBox(height: 14),
          body,
        ],
      ),
    );
  }

  /// Shared card header: tinted icon chip + title + optional trailing widget.
  Widget _cardHeader(IconData icon, String title, {Widget? trailing}) {
    final p = context.astra;
    return Row(
      children: [
        IconChip(icon: icon, size: 30, radius: 9),
        const SizedBox(width: 10),
        Expanded(child: Text(title, style: ui(size: 13, weight: FontWeight.w800, color: p.ink))),
        if (trailing != null) trailing,
      ],
    );
  }

  /// Compact 3-up stat strip (Invoices · Avg Ticket · Returns) — lives inside the
  /// Sales Performance card header area, not a separate card, to save space.
  Widget _miniStatsRow(OverviewSummary s) {
    final p = context.astra;
    final inv = s.noOfSales;
    final avg = inv > 0 ? s.netSales / inv : 0.0;
    Widget div() => Container(width: 1, height: 26, color: p.hairline);
    return Row(
      children: [
        _miniStat(Icons.receipt_long_rounded, 'Invoices', '$inv'),
        div(),
        _miniStat(Icons.sell_rounded, 'Avg Ticket', inv > 0 ? Money.compact(avg) : '—'),
        div(),
        _miniStat(Icons.assignment_return_rounded, 'Returns', '${s.noOfSalesReturns}'),
      ],
    );
  }

  Widget _miniStat(IconData icon, String label, String value) {
    final p = context.astra;
    return Expanded(
      child: Column(
        children: [
          Icon(icon, size: 16, color: p.primary),
          const SizedBox(height: 5),
          FittedBox(fit: BoxFit.scaleDown, child: Text(value, style: serif(size: 16, color: p.ink))),
          const SizedBox(height: 2),
          Text(label.toUpperCase(),
              style: ui(size: 8.5, weight: FontWeight.w800, color: p.textMuted, letterSpacing: 0.6)),
        ],
      ),
    );
  }

  Widget _rateBar(String label, double pct, List<Color> colors) {
    final p = context.astra;
    return Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        Row(
          mainAxisAlignment: MainAxisAlignment.spaceBetween,
          children: [
            Text(label, style: ui(size: 12, weight: FontWeight.w700, color: p.textSecondary)),
            Text('${pct.toStringAsFixed(1)}%', style: ui(size: 12, weight: FontWeight.w800, color: colors.first)),
          ],
        ),
        const SizedBox(height: 7),
        Container(
          height: 9,
          decoration: BoxDecoration(color: p.tint, borderRadius: BorderRadius.circular(999)),
          child: Align(
            alignment: Alignment.centerLeft,
            child: FractionallySizedBox(
              widthFactor: (pct / 100).clamp(0.0, 1.0),
              child: Container(
                decoration: BoxDecoration(
                  gradient: LinearGradient(colors: colors),
                  borderRadius: BorderRadius.circular(999),
                ),
              ),
            ),
          ),
        ),
      ],
    );
  }

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
    final data = [
      for (var i = 0; i < pts.length; i++)
        BarDatum(_dayLabel(labels.length > i ? labels[i] : '', pts.length), pts[i], peak: pts[i] == maxV),
    ];
    return AstraCard(
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          _cardHeader(Icons.bar_chart_rounded, 'Gross sales by day',
              trailing: _pill('Peak ${Money.compact(maxV)}', p.accent.withValues(alpha: 0.16), p.goldText)),
          const SizedBox(height: 14),
          BarChart(data: data),
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
          _breakdownBody(admin),
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
          onTap: () => admin.loadReports(type: type),
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
      return const SizedBox(height: 220, child: Center(child: CircularProgressIndicator()));
    }
    // Bounded height lets EmptyState centre itself (under the ListView's
    // unbounded constraints it would otherwise top-align).
    if (admin.reportError != null) {
      return SizedBox(
        height: 260,
        child: EmptyState(icon: Icons.wifi_off, title: 'Report unavailable', message: admin.reportError),
      );
    }
    if (admin.reportRows.isEmpty) {
      return const SizedBox(
        height: 260,
        child: EmptyState(icon: Icons.bar_chart, title: 'No data for this period'),
      );
    }

    final isItem = admin.reportType == 'itemwise';
    final rowIcon = isItem ? Icons.inventory_2_rounded : Icons.person_rounded;
    final maxAmount = admin.reportRows.fold<double>(0, (a, r) => r.amount > a ? r.amount : a);

    return Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        SectionLabel(
          isItem ? 'Item breakdown' : 'Stylist breakdown',
          trailing: _pill('${admin.reportRowCount} ${isItem ? 'items' : 'stylists'}', p.tint, p.textSecondary),
        ),
        const SizedBox(height: 10),
        for (final r in admin.reportRows) _reportRow(admin, r, rowIcon, maxAmount),
        // Infinite-scroll footer: a spinner while the next page streams in, plus
        // a tap-to-load fallback (in case the scroll trigger is missed). Shown
        // only while more pages remain; new pages arrive via [_onScroll].
        if (admin.reportHasMore) _loadMoreFooter(admin),
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
