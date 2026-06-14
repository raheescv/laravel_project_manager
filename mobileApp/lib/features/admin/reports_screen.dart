import 'package:flutter/material.dart';
import 'package:provider/provider.dart';

import '../../core/formatters.dart';
import '../../core/responsive.dart';
import '../../state/admin_controller.dart';
import '../../theme/theme.dart';
import '../../widgets/astra_widgets.dart';
import '../../widgets/charts.dart';

class ReportsScreen extends StatefulWidget {
  const ReportsScreen({super.key});
  @override
  State<ReportsScreen> createState() => _ReportsScreenState();
}

class _ReportsScreenState extends State<ReportsScreen> {
  @override
  void initState() {
    super.initState();
    WidgetsBinding.instance.addPostFrameCallback((_) {
      context.read<AdminController>().loadReports();
    });
  }

  @override
  Widget build(BuildContext context) {
    final admin = context.watch<AdminController>();

    return Scaffold(
      backgroundColor: Colors.transparent,
      body: AstraBackground(
        child: Column(
          children: [
            EmeraldHeader(
              title: 'Reports',
              subtitle: 'Every angle on your sales',
              trailing: HeaderIconButton(icon: Icons.download, gold: true),
            ),
            Expanded(
              child: MaxWidthBox(
                maxWidth: 820,
                child: ListView(
                  padding: const EdgeInsets.fromLTRB(16, 14, 16, 110),
                  children: [
                    _dateFilter(admin),
                    const SizedBox(height: 13),
                    _overview(admin),
                    const SizedBox(height: 13),
                    _byDay(admin),
                    const SizedBox(height: 14),
                    _segments(admin),
                    const SizedBox(height: 13),
                    _breakdown(admin),
                  ],
                ),
              ),
            ),
          ],
        ),
      ),
    );
  }

  // ---- Premium date-range filter ----

  Widget _dateFilter(AdminController admin) {
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

  Widget _presetChip(AdminController admin, String label, String id) {
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

  Future<void> _pickCustom(AdminController admin) async {
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

  /// Short badge for the active range, shown on each KPI card.
  String _rangeBadge(AdminController admin) {
    switch (admin.rangePreset) {
      case 'today':
        return 'TODAY';
      case '7d':
        return '7D';
      case '30d':
        return '30D';
      case 'month':
        return 'MONTH';
      default:
        return 'CUSTOM';
    }
  }

  // ---- Overview KPIs (range-aware) ----

  Widget _overview(AdminController admin) {
    final gross = admin.rangeGross;
    final inv = admin.rangeInvoices;
    final hasData = !admin.reportLoading || inv > 0;
    String money(double v) => hasData ? Money.of(v) : '—';

    // Best day, derived from the per-day trend (real data — no fabricated deltas).
    var peak = 0.0;
    var peakLabel = '';
    for (var i = 0; i < admin.reportTrendPoints.length; i++) {
      if (admin.reportTrendPoints[i] > peak) {
        peak = admin.reportTrendPoints[i];
        peakLabel = admin.reportTrendLabels.length > i ? admin.reportTrendLabels[i] : '';
      }
    }

    final cards = <Widget>[
      _kpiCard(admin,
          icon: Icons.payments_rounded,
          label: 'GROSS SALES',
          value: money(gross),
          caption: hasData ? '$inv invoice${inv == 1 ? '' : 's'}' : '—'),
      _kpiCard(admin,
          icon: Icons.receipt_long_rounded,
          label: 'INVOICES',
          value: hasData ? '$inv' : '—',
          caption: 'in range'),
      _kpiCard(admin,
          icon: Icons.sell_rounded,
          label: 'AVG TICKET',
          value: inv > 0 ? Money.of(gross / inv) : '—',
          caption: 'per invoice'),
      _kpiCard(admin,
          icon: Icons.trending_up_rounded,
          label: 'BEST DAY',
          value: peak > 0 ? Money.compact(peak) : '—',
          caption: peak > 0 ? _dayLabel(peakLabel, admin.reportTrendPoints.length) : '—',
          gold: true),
    ];
    return GridView.count(
      crossAxisCount: context.isTablet ? 4 : 2,
      shrinkWrap: true,
      physics: const NeverScrollableScrollPhysics(),
      mainAxisSpacing: 10,
      crossAxisSpacing: 10,
      childAspectRatio: context.isTablet ? 1.6 : 1.45,
      children: cards,
    );
  }

  Widget _kpiCard(
    AdminController admin, {
    required IconData icon,
    required String label,
    required String value,
    required String caption,
    bool gold = false,
  }) {
    final p = context.astra;
    return AstraCard(
      radius: 16,
      padding: const EdgeInsets.all(12),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Row(
            children: [
              Container(
                width: 30,
                height: 30,
                alignment: Alignment.center,
                decoration: BoxDecoration(
                  color: gold ? p.accent.withValues(alpha: 0.16) : p.tint,
                  borderRadius: BorderRadius.circular(9),
                ),
                child: Icon(icon, size: 15, color: gold ? p.goldText : p.primary),
              ),
              const Spacer(),
              Container(
                padding: const EdgeInsets.symmetric(horizontal: 7, vertical: 3),
                decoration: BoxDecoration(color: p.tint, borderRadius: BorderRadius.circular(20)),
                child: Text(_rangeBadge(admin),
                    style: ui(size: 8.5, weight: FontWeight.w800, color: p.textSecondary, letterSpacing: 0.6)),
              ),
            ],
          ),
          const Spacer(),
          Text(label,
              maxLines: 1,
              overflow: TextOverflow.ellipsis,
              style: ui(size: 9.5, weight: FontWeight.w800, color: p.textMuted, letterSpacing: 0.8)),
          const SizedBox(height: 3),
          FittedBox(
            fit: BoxFit.scaleDown,
            alignment: Alignment.centerLeft,
            child: Text(value, style: serif(size: 20, color: p.ink)),
          ),
          const SizedBox(height: 2),
          Text(caption,
              maxLines: 1,
              overflow: TextOverflow.ellipsis,
              style: ui(size: 9.5, weight: FontWeight.w600, color: p.textMuted)),
        ],
      ),
    );
  }

  // ---- Per-day trend ----

  Widget _byDay(AdminController admin) {
    final p = context.astra;
    final pts = admin.reportTrendPoints;
    final labels = admin.reportTrendLabels;
    if (pts.length < 2) return const SizedBox.shrink();
    final maxV = pts.reduce((a, b) => a > b ? a : b);
    final data = [
      for (var i = 0; i < pts.length; i++)
        BarDatum(_dayLabel(labels.length > i ? labels[i] : '', pts.length), pts[i], peak: pts[i] == maxV),
    ];
    return AstraCard(
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Row(
            children: [
              Container(
                width: 30,
                height: 30,
                alignment: Alignment.center,
                decoration: BoxDecoration(color: p.tint, borderRadius: BorderRadius.circular(9)),
                child: Icon(Icons.bar_chart_rounded, size: 16, color: p.primary),
              ),
              const SizedBox(width: 10),
              Expanded(
                child: Text('Gross sales by day', style: ui(size: 12.5, weight: FontWeight.w800, color: p.ink)),
              ),
              Container(
                padding: const EdgeInsets.symmetric(horizontal: 9, vertical: 4),
                decoration: BoxDecoration(color: p.accent.withValues(alpha: 0.16), borderRadius: BorderRadius.circular(20)),
                child: Text('Peak ${Money.compact(maxV)}',
                    style: ui(size: 10, weight: FontWeight.w800, color: p.goldText)),
              ),
            ],
          ),
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

  // ---- Breakdown mode toggle ----

  Widget _segments(AdminController admin) {
    final p = context.astra;
    Widget seg(String label, String type, IconData icon) {
      final active = admin.reportType == type;
      return Expanded(
        child: GestureDetector(
          onTap: () => admin.loadReports(type: type),
          child: Container(
            padding: const EdgeInsets.symmetric(vertical: 10),
            alignment: Alignment.center,
            decoration: BoxDecoration(
              gradient: active ? p.primaryGradient : null,
              borderRadius: BorderRadius.circular(11),
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
      decoration: BoxDecoration(color: p.card, borderRadius: BorderRadius.circular(14), boxShadow: context.astraTheme.softShadow),
      child: Row(children: [
        seg('By Invoice', 'billwise', Icons.receipt_long_rounded),
        seg('By Stylist', 'employeewise', Icons.people_alt_rounded),
      ]),
    );
  }

  // ---- Ranked breakdown table (share-of-total bars + top highlight) ----

  Widget _breakdown(AdminController admin) {
    final p = context.astra;
    if (admin.reportLoading) {
      return const AstraCard(
        child: SizedBox(height: 200, child: Center(child: CircularProgressIndicator())),
      );
    }
    if (admin.reportError != null) {
      return EmptyState(icon: Icons.wifi_off, title: 'Report unavailable', message: admin.reportError);
    }
    if (admin.reportRows.isEmpty) {
      return EmptyState(icon: Icons.bar_chart, title: 'No data for this period');
    }

    final isBill = admin.reportType == 'billwise';
    final rowIcon = isBill ? Icons.receipt_long_rounded : Icons.person_rounded;
    final maxAmount = admin.reportRows.fold<double>(0, (a, r) => r.amount > a ? r.amount : a);

    return AstraCard(
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          SectionLabel(
            isBill ? 'Invoice breakdown' : 'Stylist breakdown',
            trailing: Container(
              padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 3),
              decoration: BoxDecoration(color: p.tint, borderRadius: BorderRadius.circular(20)),
              child: Text('${admin.reportRows.length} rows',
                  style: ui(size: 9.5, weight: FontWeight.w800, color: p.textSecondary, letterSpacing: 0.4)),
            ),
          ),
          const SizedBox(height: 10),
          for (final r in admin.reportRows) _reportRow(admin, r, rowIcon, maxAmount),
          Container(height: 1.5, color: p.hairline, margin: const EdgeInsets.fromLTRB(2, 8, 2, 10)),
          Padding(
            padding: const EdgeInsets.symmetric(horizontal: 2),
            child: Row(
              mainAxisAlignment: MainAxisAlignment.spaceBetween,
              children: [
                Text('Grand total', style: ui(size: 12.5, weight: FontWeight.w800, color: p.ink)),
                Text(Money.of(admin.reportTotal), style: serif(size: 17, color: p.primaryDark)),
              ],
            ),
          ),
        ],
      ),
    );
  }

  Widget _reportRow(AdminController admin, ReportRow r, IconData icon, double maxAmount) {
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
