part of 'reports_screen.dart';

// The Overview tab's section builders. Split out of reports_screen.dart to
// keep that file readable — as a `part` these stay library-private and every
// call site is unchanged.

extension _OverviewSections on _ReportsScreenState {
  /// Sales Performance card — direction B "Refined Tiles", picked from
  /// docs/mobile-reports-overview-premium-preview.html: Net sales as one wide
  /// brand tile, the other money figures as soft tiles with a colour edge and
  /// the full amount, an Invoices / Returns strip, then the payment methods as
  /// a share bar over a compact table.
  Widget _salesPerformance(AdminCubit admin) {
    final p = context.astra;
    final ov = admin.overview;
    if (ov == null) return _overviewPlaceholder(admin, 'Sales Performance', Icons.insights_rounded);
    final s = ov.summary;
    final busy = admin.overviewLoading;
    final rate = s.successRate;

    return AstraCard(
      padding: const EdgeInsets.all(14),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          _cardHeader(Icons.insights_rounded, 'Sales Performance',
              busy: busy,
              trailing: _pill('${rate.toStringAsFixed(rate % 1 == 0 ? 0 : 1)}% success', p.tint, p.primary)),
          const SizedBox(height: 13),
          _refreshing(busy, Column(
            crossAxisAlignment: CrossAxisAlignment.stretch,
            children: [
              _netSalesTile(s),
              const SizedBox(height: 8),
              _figureTiles(s),
              const SizedBox(height: 12),
              _countStrip(s),
              _methodsSection(ov.payments.methods),
            ],
          )),
        ],
      ),
    );
  }

  /// The wide brand tile: Net sales, with Gross and Discount beside it.
  Widget _netSalesTile(OverviewSummary s) {
    final p = context.astra;
    Widget aside(String label, double value) => Text.rich(
          TextSpan(children: [
            TextSpan(text: '$label  '),
            TextSpan(text: Money.plain(value), style: ui(size: 11, weight: FontWeight.w800, color: Colors.white)),
          ]),
          style: ui(size: 10.5, weight: FontWeight.w600, color: Colors.white.withValues(alpha: 0.78)),
        );

    return Container(
      padding: const EdgeInsets.fromLTRB(13, 12, 13, 13),
      decoration: BoxDecoration(
        gradient: LinearGradient(
            begin: Alignment.topLeft, end: Alignment.bottomRight, colors: [p.primary, p.primaryDark]),
        borderRadius: BorderRadius.circular(15),
        boxShadow: context.astraTheme.floatShadow(p.primary),
      ),
      child: Row(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Expanded(
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                _tileLabel('Net sales', Icons.account_balance_wallet_rounded, Colors.white, onBrand: true),
                const SizedBox(height: 8),
                _amount(s.netSales, size: 26, color: Colors.white),
              ],
            ),
          ),
          const SizedBox(width: 10),
          Column(
            crossAxisAlignment: CrossAxisAlignment.end,
            children: [
              aside('Gross', s.grossSales),
              const SizedBox(height: 3),
              aside('Discount', s.discount),
            ],
          ),
        ],
      ),
    );
  }

  /// The other money figures as soft tiles — two to a row on a phone, three
  /// once the card is wide enough to keep each one readable. Plain rows rather
  /// than a GridView: a grid picks up the screen's safe-area padding and opened
  /// big gaps above and below the tiles.
  Widget _figureTiles(OverviewSummary s) {
    final p = context.astra;
    final avg = s.noOfSales > 0 ? s.netSales / s.noOfSales : 0.0;
    final tiles = [
      _figureTile('Gross sales', s.grossSales, Icons.payments_rounded, p.primary),
      _figureTile('Discounts', s.discount, Icons.sell_rounded, _ReportsScreenState._warn),
      _figureTile('Item total', s.totalItem, Icons.inventory_2_rounded, const Color(0xFF64748B)),
      _figureTile('Products', s.productSale, Icons.shopping_cart_rounded, const Color(0xFF0891B2)),
      _figureTile('Services', s.serviceSale, Icons.star_rounded, const Color(0xFF7C3AED)),
      _figureTile('Avg ticket', avg, Icons.confirmation_number_rounded, _ReportsScreenState._good),
    ];

    return LayoutBuilder(
      builder: (context, c) {
        final cols = c.maxWidth >= 520 ? 3 : 2;
        return Column(
          children: [
            for (var i = 0; i < tiles.length; i += cols)
              Padding(
                padding: EdgeInsets.only(top: i == 0 ? 0 : 8),
                child: Row(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    for (var j = 0; j < cols; j++) ...[
                      if (j > 0) const SizedBox(width: 8),
                      Expanded(child: i + j < tiles.length ? tiles[i + j] : const SizedBox()),
                    ],
                  ],
                ),
              ),
          ],
        );
      },
    );
  }

  Widget _figureTile(String label, double value, IconData icon, Color color) {
    final p = context.astra;
    return Container(
      clipBehavior: Clip.antiAlias,
      decoration: BoxDecoration(
        color: p.isDark ? Colors.white.withValues(alpha: 0.04) : Colors.black.withValues(alpha: 0.025),
        borderRadius: BorderRadius.circular(14),
        border: Border.all(color: p.hairline),
      ),
      child: Stack(
        children: [
          Padding(
            padding: const EdgeInsets.fromLTRB(13, 11, 11, 11),
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                _tileLabel(label, icon, color),
                const SizedBox(height: 8),
                _amount(value, size: 18, color: p.ink),
              ],
            ),
          ),
          // The colour edge that tells the tiles apart at a glance.
          Positioned(left: 0, top: 0, bottom: 0, child: Container(width: 3, color: color)),
        ],
      ),
    );
  }

  /// Icon square + upper-case label, as every figure tile opens.
  Widget _tileLabel(String label, IconData icon, Color color, {bool onBrand = false}) {
    final p = context.astra;
    return Row(
      children: [
        Container(
          width: 22,
          height: 22,
          alignment: Alignment.center,
          decoration: BoxDecoration(
            color: onBrand ? Colors.white.withValues(alpha: 0.16) : color.withValues(alpha: 0.14),
            borderRadius: BorderRadius.circular(7),
          ),
          child: Icon(icon, size: 13, color: color),
        ),
        const SizedBox(width: 7),
        Flexible(
          child: Text(label.toUpperCase(),
              maxLines: 1,
              overflow: TextOverflow.ellipsis,
              style: ui(
                  size: 9.5,
                  weight: FontWeight.w800,
                  letterSpacing: 0.6,
                  color: onBrand ? Colors.white.withValues(alpha: 0.82) : p.textSecondary)),
        ),
      ],
    );
  }

  /// A full amount with the currency as a small prefix — `QAR 1,322.00`, never
  /// the compact `QAR1.32K` — shrinking to fit rather than wrapping.
  Widget _amount(double value, {required double size, required Color color}) {
    final symbol = Money.symbol.trim();
    return FittedBox(
      fit: BoxFit.scaleDown,
      alignment: Alignment.centerLeft,
      child: Text.rich(
        TextSpan(children: [
          if (symbol.isNotEmpty)
            TextSpan(
                text: '$symbol ',
                style: ui(size: size * 0.5, weight: FontWeight.w700, color: color.withValues(alpha: 0.7))),
          TextSpan(text: Money.plain(value)),
        ]),
        maxLines: 1,
        style: ui(size: size, weight: FontWeight.w700, color: color),
      ),
    );
  }

  /// Invoices and Returns between hairlines.
  Widget _countStrip(OverviewSummary s) {
    final p = context.astra;
    Widget cell(String value, String label, IconData icon) => Expanded(
          child: Row(
            mainAxisAlignment: MainAxisAlignment.center,
            children: [
              Icon(icon, size: 16, color: p.primary),
              const SizedBox(width: 8),
              Text(value, style: ui(size: 16, weight: FontWeight.w700, color: p.ink)),
              const SizedBox(width: 6),
              Text(label.toUpperCase(),
                  style: ui(size: 9, weight: FontWeight.w800, color: p.textMuted, letterSpacing: 0.6)),
            ],
          ),
        );

    return Container(
      padding: const EdgeInsets.symmetric(vertical: 10),
      decoration: BoxDecoration(border: Border.symmetric(horizontal: BorderSide(color: p.hairline))),
      child: Row(
        children: [
          cell('${s.noOfSales}', 'Invoices', Icons.receipt_long_rounded),
          Container(width: 1, height: 22, color: p.hairline),
          cell('${s.noOfSalesReturns}', 'Returns', Icons.assignment_return_rounded),
        ],
      ),
    );
  }

  /// Payment methods: a stacked share bar over a Method · Txns · Sales · Net
  /// table. A method with nothing against it stays listed, muted, instead of
  /// taking a card of zeros.
  Widget _methodsSection(List<PaymentMethodStat> methods) {
    if (methods.isEmpty) return const SizedBox.shrink();
    final p = context.astra;
    final txns = methods.fold<int>(0, (a, m) => a + m.transactions);
    final salesTotal = methods.fold<double>(0, (a, m) => a + (m.sales > 0 ? m.sales : 0));
    final shares = [
      for (final m in methods)
        if (m.sales > 0 && salesTotal > 0) (method: m, flex: (m.sales / salesTotal * 1000).round().clamp(1, 1000)),
    ];
    final inset = p.isDark ? Colors.white.withValues(alpha: 0.04) : Colors.black.withValues(alpha: 0.025);
    final headStyle = ui(size: 9, weight: FontWeight.w800, color: p.textMuted, letterSpacing: 0.6);

    Widget tableRow(List<Widget> cells, {Color? background}) => Container(
          color: background,
          padding: const EdgeInsets.symmetric(horizontal: 11, vertical: 9),
          child: Row(
            children: [
              Expanded(flex: 15, child: cells[0]),
              Expanded(flex: 7, child: Align(alignment: Alignment.centerRight, child: cells[1])),
              Expanded(flex: 11, child: Align(alignment: Alignment.centerRight, child: cells[2])),
              Expanded(flex: 11, child: Align(alignment: Alignment.centerRight, child: cells[3])),
            ],
          ),
        );

    Widget methodRow(PaymentMethodStat m) {
      final muted = m.transactions == 0 && m.sales == 0 && m.returns == 0;
      TextStyle style({FontWeight weight = FontWeight.w600, Color? color}) =>
          ui(size: 12, weight: weight, color: muted ? p.textMuted : (color ?? p.ink));
      return tableRow([
        Row(
          children: [
            Container(
              width: 8,
              height: 8,
              decoration: BoxDecoration(color: _methodColor(m.method, p), borderRadius: BorderRadius.circular(3)),
            ),
            const SizedBox(width: 7),
            Flexible(
              child: Text(m.method,
                  maxLines: 1, overflow: TextOverflow.ellipsis, style: style(weight: FontWeight.w700)),
            ),
          ],
        ),
        Text('${m.transactions}', style: style()),
        FittedBox(fit: BoxFit.scaleDown, child: Text(Money.plain(m.sales), style: style())),
        FittedBox(
          fit: BoxFit.scaleDown,
          child: Text(Money.plain(m.net),
              style: style(weight: FontWeight.w700, color: m.net < 0 ? _ReportsScreenState._bad : null)),
        ),
      ]);
    }

    return Column(
      crossAxisAlignment: CrossAxisAlignment.stretch,
      children: [
        const SizedBox(height: 14),
        Row(
          children: [
            Expanded(child: Text('Payment methods', style: ui(size: 12.5, weight: FontWeight.w800, color: p.ink))),
            Text('$txns transactions', style: ui(size: 11, weight: FontWeight.w600, color: p.textMuted)),
          ],
        ),
        if (shares.isNotEmpty) ...[
          const SizedBox(height: 9),
          ClipRRect(
            borderRadius: BorderRadius.circular(99),
            child: SizedBox(
              height: 8,
              child: Row(
                children: [
                  for (var i = 0; i < shares.length; i++) ...[
                    if (i > 0) const SizedBox(width: 2),
                    Expanded(
                      flex: shares[i].flex,
                      child: ColoredBox(color: _methodColor(shares[i].method.method, p)),
                    ),
                  ],
                ],
              ),
            ),
          ),
        ],
        const SizedBox(height: 10),
        Container(
          clipBehavior: Clip.antiAlias,
          decoration: BoxDecoration(
            borderRadius: BorderRadius.circular(13),
            border: Border.all(color: p.hairline),
          ),
          child: Column(
            children: [
              tableRow([
                Text('METHOD', style: headStyle),
                Text('TXNS', style: headStyle),
                Text('SALES', style: headStyle),
                Text('NET', style: headStyle),
              ], background: inset),
              for (final m in methods) ...[
                Container(height: 1, color: p.hairline),
                methodRow(m),
              ],
            ],
          ),
        ),
      ],
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
