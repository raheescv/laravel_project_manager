import 'package:flutter/material.dart';
import 'package:go_router/go_router.dart';

import '../../core/formatters.dart';
import '../../core/responsive.dart';
import '../../models/models.dart';
import '../../theme/palette.dart';
import '../../theme/theme.dart';
import '../../widgets/astra_widgets.dart';
import '../../widgets/invo_logo.dart';
import '../sale/invoice_screen.dart' show DottedDivider;

/// Sale-return confirmation / view — mirrors the invoice screen: a refund hero,
/// the returned items, a summary and a refund-payment card. Palette-driven so
/// every [AstraSkin] re-skins it.
class ReturnReceiptScreen extends StatelessWidget {
  const ReturnReceiptScreen({super.key, required this.saleReturn});
  final SaleReturn saleReturn;

  bool get _done => saleReturn.status.toLowerCase() == 'completed';
  double get _refunded => saleReturn.paid;

  @override
  Widget build(BuildContext context) {
    final p = context.astra;
    return Scaffold(
      body: AstraBackground(
        child: SafeArea(
          child: MaxWidthBox(
            maxWidth: 560,
            child: Column(
              children: [
                _header(context, p),
                Expanded(
                  child: ListView(
                    padding: const EdgeInsets.fromLTRB(16, 8, 16, 18),
                    children: [
                      _heroCard(p),
                      const SizedBox(height: 14),
                      if (saleReturn.customerName.trim().isNotEmpty) ...[
                        _customerCard(p),
                        const SizedBox(height: 12),
                      ],
                      _itemsCard(p),
                      const SizedBox(height: 12),
                      _summaryCard(p),
                      const SizedBox(height: 12),
                      _paymentCard(p),
                    ],
                  ),
                ),
                SafeArea(
                  top: false,
                  child: Padding(
                    padding: const EdgeInsets.fromLTRB(14, 0, 14, 6),
                    child: Row(
                      children: [
                        Expanded(child: _action(context, Icons.assignment_return_outlined, 'New Return', () => context.go('/sale-return/pick'))),
                        const SizedBox(width: 9),
                        Expanded(
                          flex: 2,
                          child: AstraButton(label: 'Done', onTap: () => context.go('/sales-returns')),
                        ),
                      ],
                    ),
                  ),
                ),
              ],
            ),
          ),
        ),
      ),
    );
  }

  // ---- Header --------------------------------------------------------------

  Widget _header(BuildContext context, AstraPalette p) {
    final (label, bg, fg, icon) = _statusBadge(p);
    return Padding(
      padding: const EdgeInsets.fromLTRB(16, 10, 18, 4),
      child: Row(
        children: [
          if (context.canPop()) ...[
            GestureDetector(
              onTap: () => context.pop(),
              child: Container(
                width: 36,
                height: 36,
                decoration: BoxDecoration(
                  color: p.card,
                  borderRadius: BorderRadius.circular(11),
                  boxShadow: context.astraTheme.softShadow,
                ),
                child: Icon(Icons.arrow_back_ios_new, size: 15, color: p.ink),
              ),
            ),
            const SizedBox(width: 11),
          ] else
            const SizedBox(width: 4),
          const InvoLogomark(height: 32),
          const SizedBox(width: 11),
          Expanded(
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Text('Invo', style: serif(size: 16, color: p.ink)),
                Text(saleReturn.branch.isEmpty ? 'Sale Return' : saleReturn.branch,
                    style: ui(size: 9.5, weight: FontWeight.w600, color: p.textMuted)),
              ],
            ),
          ),
          StatusPill(label: label, bg: bg, fg: fg, icon: icon),
        ],
      ),
    );
  }

  (String, Color, Color, IconData) _statusBadge(AstraPalette p) {
    if (_done) {
      return ('REFUNDED', p.successTint, AstraPalette.success, Icons.check_circle);
    }
    return ('DRAFT', p.warnTint, p.warnText, Icons.timelapse);
  }

  // ---- Total hero ----------------------------------------------------------

  Widget _heroCard(AstraPalette p) {
    final amountColor = p.isEditorial ? p.accent : Colors.white;
    final faint = Colors.white.withValues(alpha: 0.82);
    final ref = saleReturn.referenceNo.isEmpty ? '#${saleReturn.id}' : saleReturn.referenceNo;
    final refLine = [
      ref,
      if (saleReturn.date.isNotEmpty) Dates.human(saleReturn.date),
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
                  Text(_done ? 'TOTAL REFUNDED' : 'REFUND TOTAL',
                      style: ui(size: 10.5, weight: FontWeight.w700, color: faint, letterSpacing: 1.4)),
                  const SizedBox(height: 7),
                  FittedBox(
                    fit: BoxFit.scaleDown,
                    alignment: Alignment.centerLeft,
                    child: Text(Money.of(_done ? _refunded : saleReturn.grandTotal),
                        maxLines: 1, style: serif(size: 40, color: amountColor)),
                  ),
                  const SizedBox(height: 6),
                  Text('Return  $refLine',
                      style: ui(size: 11.5, weight: FontWeight.w600, color: faint)),
                ],
              ),
            ),
          ],
        ),
      ),
    );
  }

  // ---- Customer ------------------------------------------------------------

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
                  Text('Refunded to', style: ui(size: 9.5, weight: FontWeight.w700, color: p.textMuted, letterSpacing: 0.4)),
                  const SizedBox(height: 2),
                  Text(saleReturn.customerName, style: ui(size: 13.5, weight: FontWeight.w700, color: p.ink)),
                ],
              ),
            ),
            if (saleReturn.customerMobile.trim().isNotEmpty)
              Text(saleReturn.customerMobile, style: ui(size: 11.5, weight: FontWeight.w600, color: p.textSecondary)),
          ],
        ),
      );

  // ---- Items ---------------------------------------------------------------

  Widget _itemsCard(AstraPalette p) {
    final lines = saleReturn.lines;
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

  Widget _lineRow(AstraPalette p, SaleReturnLine l) {
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

  // ---- Summary -------------------------------------------------------------

  Widget _summaryCard(AstraPalette p) => AstraCard(
        padding: const EdgeInsets.fromLTRB(16, 14, 16, 14),
        child: Column(
          children: [
            _sumRow(p, 'Subtotal', Money.of(saleReturn.grossAmount), p.textSecondary),
            if (saleReturn.discount > 0) _sumRow(p, 'Discount', '− ${Money.of(saleReturn.discount)}', p.goldText),
            if (saleReturn.taxAmount > 0) _sumRow(p, 'Tax', Money.of(saleReturn.taxAmount), p.textSecondary),
            Padding(
              padding: const EdgeInsets.only(top: 8),
              child: DottedDivider(color: p.hairline),
            ),
            const SizedBox(height: 12),
            Row(
              mainAxisAlignment: MainAxisAlignment.spaceBetween,
              crossAxisAlignment: CrossAxisAlignment.end,
              children: [
                Text('Total Refunded', style: ui(size: 13, weight: FontWeight.w800, color: p.ink)),
                const SizedBox(width: 12),
                Flexible(
                  child: FittedBox(
                    fit: BoxFit.scaleDown,
                    alignment: Alignment.centerRight,
                    child: Text(Money.of(_refunded),
                        maxLines: 1, style: serif(size: 22, color: p.primaryDark)),
                  ),
                ),
              ],
            ),
          ],
        ),
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

  // ---- Payment -------------------------------------------------------------

  Widget _paymentCard(AstraPalette p) {
    final rows = <Widget>[];
    if (saleReturn.payments.isNotEmpty) {
      for (int i = 0; i < saleReturn.payments.length; i++) {
        final pay = saleReturn.payments[i];
        rows.add(_payRow(p, pay.method, Money.of(pay.amount)));
        if (i != saleReturn.payments.length - 1) rows.add(Container(height: 1, color: p.hairline));
      }
    } else {
      rows.add(_payRow(p, _done ? 'Refunded' : 'Pending', Money.of(saleReturn.paid)));
    }
    return AstraCard(
      padding: const EdgeInsets.symmetric(horizontal: 14, vertical: 4),
      child: Column(children: rows),
    );
  }

  Widget _payRow(AstraPalette p, String method, String amount) {
    final label = method.trim().isEmpty ? 'Refund' : method;
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
                Text('Refund issued', style: ui(size: 10.5, weight: FontWeight.w600, color: p.textMuted)),
              ],
            ),
          ),
          Text(amount, style: ui(size: 13.5, weight: FontWeight.w800, color: p.ink)),
        ],
      ),
    );
  }

  IconData _payIcon(String method) {
    final s = method.toLowerCase();
    if (s.contains('cash')) return Icons.payments_outlined;
    if (s.contains('card') || s.contains('visa') || s.contains('master')) return Icons.credit_card;
    if (s.contains('credit')) return Icons.account_balance_outlined;
    if (s.contains('custom') || s.contains('split')) return Icons.call_split;
    return Icons.account_balance_wallet_outlined;
  }

  String _titleCase(String s) => s.isEmpty ? s : s[0].toUpperCase() + s.substring(1);

  Widget _action(BuildContext context, IconData icon, String label, VoidCallback onTap) {
    final p = context.astra;
    final t = context.astraTheme;
    return GestureDetector(
      onTap: onTap,
      child: Container(
        padding: const EdgeInsets.symmetric(vertical: 12),
        alignment: Alignment.center,
        decoration: BoxDecoration(color: p.card, borderRadius: BorderRadius.circular(13), boxShadow: t.softShadow),
        child: Row(
          mainAxisAlignment: MainAxisAlignment.center,
          mainAxisSize: MainAxisSize.min,
          children: [
            Icon(icon, size: 15, color: p.ink),
            const SizedBox(width: 7),
            Flexible(child: Text(label, maxLines: 1, overflow: TextOverflow.ellipsis, style: ui(size: 12, weight: FontWeight.w700, color: p.ink))),
          ],
        ),
      ),
    );
  }
}
