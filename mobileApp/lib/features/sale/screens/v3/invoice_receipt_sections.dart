part of 'invoice_screen.dart';

// The on-screen receipt: hero, timeline and the customer / items / summary /
// payment cards. Split out of invoice_screen.dart; a `part`, so these stay
// library-private and no call site changed.

extension _ReceiptSections on InvoiceScreen {
  Widget _heroCard(AstraPalette p) {
    final amountColor = p.isEditorial ? p.accent : Colors.white;
    final faint = Colors.white.withValues(alpha: 0.82);
    final invLine = [
      sale.invoiceNo.isEmpty ? '#${sale.id}' : sale.invoiceNo,
      if (sale.date.isNotEmpty) Dates.human(sale.date),
    ].join('  ·  ');

    return Container(
      decoration: BoxDecoration(
        gradient: p.heroGradient,
        borderRadius: BorderRadius.circular(24),
        boxShadow: [
          BoxShadow(
            color: p.primary.withValues(alpha: p.isEditorial ? 0.30 : 0.42),
            blurRadius: 34,
            spreadRadius: -18,
            offset: const Offset(0, 18),
          ),
        ],
      ),
      child: ClipRRect(
        borderRadius: BorderRadius.circular(24),
        child: Stack(
          children: [
            Positioned(
              right: -36,
              top: -42,
              child: Container(
                width: 170,
                height: 170,
                decoration: BoxDecoration(
                  shape: BoxShape.circle,
                  color: Colors.white.withValues(alpha: 0.10),
                ),
              ),
            ),
            Padding(
              padding: const EdgeInsets.fromLTRB(22, 20, 22, 22),
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  Text(_paidUp ? 'TOTAL PAID' : 'AMOUNT DUE',
                      style: ui(size: 10.5, weight: FontWeight.w700, color: faint, letterSpacing: 1.4)),
                  const SizedBox(height: 7),
                  FittedBox(
                    fit: BoxFit.scaleDown,
                    alignment: Alignment.centerLeft,
                    child: Text(Money.of(_paidUp ? sale.paid : _balance),
                        maxLines: 1, style: serif(size: 40, color: amountColor)),
                  ),
                  const SizedBox(height: 6),
                  Text('Invoice  $invLine',
                      style: ui(size: 11.5, weight: FontWeight.w600, color: faint)),
                  // Shown once this sale has synced and taken a real invoice
                  // number: the customer is holding a receipt printed under the
                  // provisional one, and this is what lets whoever is looking at
                  // the screen confirm the two are the same sale.
                  if (sale.offlineRef.isNotEmpty && sale.offlineRef != sale.invoiceNo) ...[
                    const SizedBox(height: 3),
                    Text('Offline receipt  ${sale.offlineRef}',
                        style: ui(size: 10.5, weight: FontWeight.w600, color: faint)),
                  ],
                ],
              ),
            ),
          ],
        ),
      ),
    );
  }

  Widget _timeline(AstraPalette p) {
    // Three genuine stages: the sale is always Created; payment is either still
    // the active stage (Pending) or Paid; the receipt is the final stage, reached
    // once the ticket is fully settled. The first segment is always filled since
    // "Created" is complete, so the line never looks dead while a balance is due.
    final paid = _paidUp;
    return Padding(
      padding: const EdgeInsets.symmetric(horizontal: 4, vertical: 2),
      child: Row(
        children: [
          _step(p, 'Created', done: true),
          _seg(p, true),
          _step(p, paid ? 'Paid' : 'Pending', done: paid, active: !paid),
          _seg(p, paid),
          _step(p, 'Receipt', done: paid),
        ],
      ),
    );
  }

  Widget _step(AstraPalette p, String label, {required bool done, bool active = false}) {
    final lit = done || active; // the current or a completed stage
    return Column(
      mainAxisSize: MainAxisSize.min,
      children: [
        Container(
          width: 28,
          height: 28,
          decoration: BoxDecoration(
            shape: BoxShape.circle,
            color: done ? AstraPalette.success : p.card,
            border: done ? null : Border.all(color: active ? AstraPalette.success : p.hairline, width: active ? 2.5 : 2),
            boxShadow: lit
                ? [BoxShadow(color: AstraPalette.success.withValues(alpha: done ? 0.4 : 0.22), blurRadius: 12, spreadRadius: -4, offset: const Offset(0, 4))]
                : null,
          ),
          child: done
              ? const Icon(Icons.check, size: 15, color: Colors.white)
              : Center(
                  child: Container(
                    width: active ? 9 : 7,
                    height: active ? 9 : 7,
                    decoration: BoxDecoration(shape: BoxShape.circle, color: active ? AstraPalette.success : p.textMuted),
                  ),
                ),
        ),
        const SizedBox(height: 6),
        Text(label, style: ui(size: 9.5, weight: FontWeight.w700, color: done ? p.textSecondary : (active ? AstraPalette.success : p.textMuted))),
      ],
    );
  }

  Widget _seg(AstraPalette p, bool done) => Expanded(
        child: Padding(
          padding: const EdgeInsets.only(bottom: 22),
          child: Container(
            height: 2.5,
            decoration: BoxDecoration(
              color: done ? AstraPalette.success : p.hairline,
              borderRadius: BorderRadius.circular(2),
            ),
          ),
        ),
      );

  Widget _customerCard(AstraPalette p) => AstraCard(
        padding: const EdgeInsets.symmetric(horizontal: 14, vertical: 12),
        child: Row(
          children: [
            const IconChip(icon: Icons.person_outline, size: 38, radius: 12),
            const SizedBox(width: 12),
            Expanded(
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  Text('Billed to', style: ui(size: 9.5, weight: FontWeight.w700, color: p.textMuted, letterSpacing: 0.4)),
                  const SizedBox(height: 2),
                  Text(sale.customerName, style: ui(size: 13.5, weight: FontWeight.w700, color: p.ink)),
                ],
              ),
            ),
            if (sale.customerMobile.trim().isNotEmpty)
              Text(sale.customerMobile, style: ui(size: 11.5, weight: FontWeight.w600, color: p.textSecondary)),
          ],
        ),
      );

  Widget _itemsCard(AstraPalette p) {
    final lines = sale.lines;
    return AstraCard(
      padding: const EdgeInsets.symmetric(horizontal: 14, vertical: 4),
      child: Column(
        children: [
          for (int i = 0; i < lines.length; i++) ...[
            _lineRow(p, lines[i]),
            if (i != lines.length - 1) Container(height: 1, color: p.hairline),
          ],
        ],
      ),
    );
  }

  Widget _lineRow(AstraPalette p, SaleLine l) {
    final isService = l.type.toLowerCase().startsWith('serv');
    final qty = l.quantity.toStringAsFixed(l.quantity % 1 == 0 ? 0 : 1);
    final sub = '$qty × ${Money.of(l.unitPrice)}${l.employee.isEmpty ? '' : ' · ${l.employee}'}';
    return Padding(
      padding: const EdgeInsets.symmetric(vertical: 11),
      child: Row(
        children: [
          IconChip(icon: isService ? Icons.content_cut : Icons.shopping_bag_outlined, size: 38, radius: 12),
          const SizedBox(width: 12),
          Expanded(
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Text(l.name, style: ui(size: 13, weight: FontWeight.w700, color: p.ink)),
                const SizedBox(height: 2),
                Text(sub, style: ui(size: 10.5, weight: FontWeight.w600, color: p.textMuted)),
              ],
            ),
          ),
          const SizedBox(width: 10),
          Text(Money.of(l.total), style: ui(size: 13.5, weight: FontWeight.w800, color: p.ink)),
        ],
      ),
    );
  }

  Widget _summaryCard(AstraPalette p) => AstraCard(
        padding: const EdgeInsets.fromLTRB(16, 14, 16, 14),
        child: Column(
          children: [
            _sumRow(p, 'Subtotal', Money.of(sale.grossAmount), p.textSecondary),
            if (sale.discount > 0) _sumRow(p, 'Discount', '− ${Money.of(sale.discount)}', p.goldText),
            if (sale.taxAmount > 0) _sumRow(p, 'Tax', Money.of(sale.taxAmount), p.textSecondary),
            // Gratuity — a standalone extra that is excluded from grand_total but
            // included in the amount paid, so it reads as Subtotal − Discount + Tax + Tip = Total.
            if (sale.tip > 0) _sumRow(p, 'Tip', Money.of(sale.tip), p.goldText),
            Padding(
              padding: const EdgeInsets.only(top: 8),
              child: DottedDivider(color: p.hairline),
            ),
            const SizedBox(height: 12),
            // Paid vs balance, driven by the sale's own paid/balance columns.
            if (_balance > 0.5) ...[
              _sumRow(p, 'Paid', Money.of(sale.paid), p.textSecondary),
              const SizedBox(height: 4),
              _totalRow(p, 'Balance Due', _balance, p.warnText),
            ] else
              _totalRow(p, 'Total Paid', sale.paid, p.primaryDark),
          ],
        ),
      );

  Widget _totalRow(AstraPalette p, String label, double amount, Color color) => Row(
        mainAxisAlignment: MainAxisAlignment.spaceBetween,
        crossAxisAlignment: CrossAxisAlignment.end,
        children: [
          Text(label, style: ui(size: 13, weight: FontWeight.w800, color: p.ink)),
          const SizedBox(width: 12),
          Flexible(
            child: FittedBox(
              fit: BoxFit.scaleDown,
              alignment: Alignment.centerRight,
              child: Text(Money.of(amount), maxLines: 1, style: serif(size: 22, color: color)),
            ),
          ),
        ],
      );

  Widget _sumRow(AstraPalette p, String label, String value, Color color) => Padding(
        padding: const EdgeInsets.symmetric(vertical: 4),
        child: Row(
          mainAxisAlignment: MainAxisAlignment.spaceBetween,
          children: [
            Text(label, style: ui(size: 12.5, weight: FontWeight.w600, color: color)),
            Text(value, style: ui(size: 12.5, weight: FontWeight.w700, color: color)),
          ],
        ),
      );

  Widget _paymentCard(AstraPalette p) {
    final rows = <Widget>[];
    if (sale.payments.isNotEmpty) {
      for (int i = 0; i < sale.payments.length; i++) {
        final pay = sale.payments[i];
        rows.add(_payRow(p, pay.method, Money.of(pay.amount)));
        if (i != sale.payments.length - 1) rows.add(Container(height: 1, color: p.hairline));
      }
    } else {
      rows.add(_payRow(p, _paidUp ? 'Paid' : 'Pending', Money.of(sale.paid)));
    }
    return AstraCard(
      padding: const EdgeInsets.symmetric(horizontal: 14, vertical: 4),
      child: Column(children: rows),
    );
  }

  Widget _payRow(AstraPalette p, String method, String amount) {
    final label = method.trim().isEmpty ? 'Payment' : method;
    return Padding(
      padding: const EdgeInsets.symmetric(vertical: 11),
      child: Row(
        children: [
          IconChip(icon: _payIcon(method), size: 38, radius: 12),
          const SizedBox(width: 12),
          Expanded(
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Text(_titleCase(label), style: ui(size: 13, weight: FontWeight.w700, color: p.ink)),
                const SizedBox(height: 2),
                Text('Payment received', style: ui(size: 10.5, weight: FontWeight.w600, color: p.textMuted)),
              ],
            ),
          ),
          Text(amount, style: ui(size: 13.5, weight: FontWeight.w800, color: p.ink)),
        ],
      ),
    );
  }
}
