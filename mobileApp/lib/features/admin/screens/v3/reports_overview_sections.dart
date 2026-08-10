part of 'reports_screen.dart';

// The Overview tab's section builders. Split out of reports_screen.dart to
// keep that file readable — as a `part` these stay library-private and every
// call site is unchanged.

extension _OverviewSections on _ReportsScreenState {
  /// Sales Performance card — success rate, per-method Sales/Returns/Net,
  /// and the six gradient financial tiles.
  Widget _salesPerformance(AdminCubit admin) {
    final p = context.astra;
    final ov = admin.overview;
    if (ov == null) return _overviewPlaceholder(admin, 'Sales Performance', Icons.insights_rounded);
    final s = ov.summary;
    final busy = admin.overviewLoading;

    return AstraCard(
      padding: const EdgeInsets.all(12),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          _cardHeader(Icons.insights_rounded, 'Sales Performance',
              busy: busy,
              trailing: _pill('Disc ${Money.compact(s.discount)}', p.tint, p.textSecondary)),
          const SizedBox(height: 12),
          _refreshing(busy, Column(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
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
          )),
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
              _methodCell('Sales', m.sales, _ReportsScreenState._good),
              const SizedBox(width: 7),
              _methodCell('Returns', m.returns, _ReportsScreenState._warn),
              const SizedBox(width: 7),
              _methodCell('Net', m.net, m.net < 0 ? _ReportsScreenState._bad : p.primary),
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
    const spacing = 9.0;
    if (!context.isTablet) {
      // Phone tiles were borderline-short for the label+value at 1× and could
      // clip once text-scale grows; a little more height gives headroom.
      return GridView.count(
        crossAxisCount: 2,
        shrinkWrap: true,
        physics: const NeverScrollableScrollPhysics(),
        mainAxisSpacing: spacing,
        crossAxisSpacing: spacing,
        childAspectRatio: 2.3,
        children: tiles,
      );
    }
    // Tablet: derive the ratio from the ACTUAL tile width rather than a fixed
    // value — in the two-column report layout this card is only ~half width, so
    // a blanket 3.3 made tiles too short and they overflowed. Sizing to a target
    // pixel height keeps them fitted whether the card is full or half width.
    return LayoutBuilder(
      builder: (ctx, c) {
        const cols = 3;
        final tileW = (c.maxWidth - spacing * (cols - 1)) / cols;
        // Enough height for the icon + label + value at 1× with text-scale headroom.
        return GridView.count(
          crossAxisCount: cols,
          shrinkWrap: true,
          physics: const NeverScrollableScrollPhysics(),
          mainAxisSpacing: spacing,
          crossAxisSpacing: spacing,
          childAspectRatio: tileW / 58,
          children: tiles,
        );
      },
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
    final busy = admin.overviewLoading;

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
          _cardHeader(Icons.account_balance_wallet_rounded, 'Payment Overview', busy: busy),
          const SizedBox(height: 12),
          _refreshing(busy, Column(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              Row(
                children: [
                  _payTile('Sales Payments', pay.salesTotal, '${pay.salesTransactions} transactions', _ReportsScreenState._good),
                  const SizedBox(width: 10),
                  _payTile('Returns Payments', pay.returnsTotal, '${pay.returnsTransactions} returns', _ReportsScreenState._warn),
                ],
              ),
              const SizedBox(height: 12),
              Center(
                child: Column(
                  children: [
                    FittedBox(
                      fit: BoxFit.scaleDown,
                      child: Text(Money.of(net), style: serif(size: 30, color: net < 0 ? _ReportsScreenState._bad : _ReportsScreenState._good)),
                    ),
                    const SizedBox(height: 3),
                    Text('Net Payments', style: ui(size: 11.5, weight: FontWeight.w700, color: p.textSecondary)),
                    Text('${pay.totalTransactions} total transactions',
                        style: ui(size: 10, weight: FontWeight.w600, color: p.textMuted)),
                  ],
                ),
              ),
              const SizedBox(height: 13),
              _rateBar('Collection Rate', ov.summary.collectionRate, const [_ReportsScreenState._good, Color(0xFF3FC07E)]),
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
          )),
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
  /// While [busy] the icon chip becomes a spinner, so a refetch reads as
  /// loading from the top of the card — see [_refreshing].
  Widget _cardHeader(IconData icon, String title, {Widget? trailing, bool busy = false}) {
    final p = context.astra;
    return Row(
      children: [
        if (busy)
          SizedBox(
            width: 30,
            height: 30,
            child: Center(
              child: SizedBox(
                width: 17,
                height: 17,
                child: CircularProgressIndicator(strokeWidth: 2.4, color: p.primary),
              ),
            ),
          )
        else
          IconChip(icon: icon, size: 30, radius: 9),
        const SizedBox(width: 10),
        Expanded(child: Text(title, style: ui(size: 13, weight: FontWeight.w800, color: p.ink))),
        if (trailing != null) trailing,
      ],
    );
  }

  /// A range change refetches while the previous figures are still on screen,
  /// so without this the Overview looked frozen and only the Breakdown tab
  /// (which swaps its whole body for a spinner) read as loading. Fade the stale
  /// body back — the header spinner above it carries the progress.
  Widget _refreshing(bool busy, Widget child) =>
      busy ? IgnorePointer(child: Opacity(opacity: 0.4, child: child)) : child;

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
            Flexible(
              child: Text(label, maxLines: 1, overflow: TextOverflow.ellipsis,
                  style: ui(size: 12, weight: FontWeight.w700, color: p.textSecondary)),
            ),
            const SizedBox(width: 8),
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
}
