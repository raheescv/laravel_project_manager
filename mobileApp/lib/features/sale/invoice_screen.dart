import 'package:flutter/material.dart';
import 'package:go_router/go_router.dart';
import 'package:printing/printing.dart';
import 'package:provider/provider.dart';

import '../../core/formatters.dart';
import '../../core/responsive.dart';
import '../../models/models.dart';
import '../../state/print_settings_controller.dart';
import '../../theme/palette.dart';
import '../../theme/theme.dart';
import '../../widgets/astra_widgets.dart';
import '../../widgets/invo_logo.dart';
import 'receipt_pdf.dart';

/// Single-sale view — "Fintech" premium layout: a gradient total hero, a
/// Created → Paid → Receipt status timeline, an itemized card with per-line
/// icon avatars, a summary card and a payment/customer card. Everything is
/// palette-driven so each [AstraSkin] re-skins it.
class InvoiceScreen extends StatelessWidget {
  const InvoiceScreen({super.key, required this.sale});
  final Sale sale;

  // Net payable on this ticket and how much of it is still outstanding.
  double get _payable => sale.grossAmount - sale.discount + sale.taxAmount;
  double get _balance => _payable - sale.paid;
  bool get _paidUp =>
      sale.status.toUpperCase() == 'COMPLETED' || _balance <= 0.5;

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
                      const SizedBox(height: 6),
                      _timeline(p),
                      const SizedBox(height: 14),
                      if (sale.customerName.trim().isNotEmpty) ...[
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
                        Expanded(child: _action(context, Icons.print_outlined, 'Print', () => _print(context))),
                        const SizedBox(width: 9),
                        Expanded(child: _action(context, Icons.ios_share, 'Share', () => _share(context))),
                        const SizedBox(width: 9),
                        Expanded(
                          flex: 2,
                          child: AstraButton(label: 'New Sale', onTap: () => context.go('/sale')),
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
    final (label, bg, fg, icon) = _statusBadge();
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
                Text(sale.branch.isEmpty ? 'Salon POS' : sale.branch,
                    style: ui(size: 9.5, weight: FontWeight.w600, color: p.textMuted)),
              ],
            ),
          ),
          StatusPill(label: label, bg: bg, fg: fg, icon: icon),
        ],
      ),
    );
  }

  (String, Color, Color, IconData) _statusBadge() {
    if (_paidUp) {
      return ('PAID', AstraPalette.successTint, AstraPalette.success, Icons.check_circle);
    }
    if (sale.paid > 0) {
      return ('PARTIAL', AstraPalette.warnTint, const Color(0xFFB7791F), Icons.timelapse);
    }
    return ('UNPAID', AstraPalette.dangerTint, AstraPalette.danger, Icons.error_outline);
  }

  // ---- Total hero ----------------------------------------------------------

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
                ],
              ),
            ),
          ],
        ),
      ),
    );
  }

  // ---- Status timeline -----------------------------------------------------

  Widget _timeline(AstraPalette p) {
    return Padding(
      padding: const EdgeInsets.symmetric(horizontal: 4, vertical: 2),
      child: Row(
        children: [
          _step(p, 'Created', true),
          _seg(p, _paidUp),
          _step(p, _paidUp ? 'Paid' : 'Pending', _paidUp),
          _seg(p, _paidUp),
          _step(p, 'Receipt', _paidUp),
        ],
      ),
    );
  }

  Widget _step(AstraPalette p, String label, bool done) {
    return Column(
      mainAxisSize: MainAxisSize.min,
      children: [
        Container(
          width: 28,
          height: 28,
          decoration: BoxDecoration(
            shape: BoxShape.circle,
            color: done ? AstraPalette.success : p.card,
            border: done ? null : Border.all(color: p.hairline, width: 2),
            boxShadow: done
                ? [BoxShadow(color: AstraPalette.success.withValues(alpha: 0.4), blurRadius: 12, spreadRadius: -4, offset: const Offset(0, 4))]
                : null,
          ),
          child: done
              ? const Icon(Icons.check, size: 15, color: Colors.white)
              : Center(
                  child: Container(
                    width: 7,
                    height: 7,
                    decoration: BoxDecoration(shape: BoxShape.circle, color: p.textMuted),
                  ),
                ),
        ),
        const SizedBox(height: 6),
        Text(label, style: ui(size: 9.5, weight: FontWeight.w700, color: done ? p.textSecondary : p.textMuted)),
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

  // ---- Items ---------------------------------------------------------------

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

  // ---- Summary -------------------------------------------------------------

  Widget _summaryCard(AstraPalette p) => AstraCard(
        padding: const EdgeInsets.fromLTRB(16, 14, 16, 14),
        child: Column(
          children: [
            _sumRow(p, 'Subtotal', Money.of(sale.grossAmount), p.textSecondary),
            if (sale.discount > 0) _sumRow(p, 'Discount', '− ${Money.of(sale.discount)}', p.goldText),
            if (sale.taxAmount > 0) _sumRow(p, 'Tax', Money.of(sale.taxAmount), p.textSecondary),
            Padding(
              padding: const EdgeInsets.only(top: 8),
              child: DottedDivider(color: p.hairline),
            ),
            const SizedBox(height: 12),
            Row(
              mainAxisAlignment: MainAxisAlignment.spaceBetween,
              crossAxisAlignment: CrossAxisAlignment.end,
              children: [
                Text('Total Paid', style: ui(size: 13, weight: FontWeight.w800, color: p.ink)),
                const SizedBox(width: 12),
                Flexible(
                  child: FittedBox(
                    fit: BoxFit.scaleDown,
                    alignment: Alignment.centerRight,
                    child: Text(Money.of(sale.paid),
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

  IconData _payIcon(String method) {
    final s = method.toLowerCase();
    if (s.contains('cash')) return Icons.payments_outlined;
    if (s.contains('card') || s.contains('visa') || s.contains('master')) return Icons.credit_card;
    if (s.contains('credit')) return Icons.account_balance_outlined;
    if (s.contains('custom') || s.contains('split')) return Icons.call_split;
    return Icons.account_balance_wallet_outlined;
  }

  String _titleCase(String s) =>
      s.isEmpty ? s : s[0].toUpperCase() + s.substring(1);

  // ---- PDF actions (unchanged) --------------------------------------------

  String get _fileName {
    final base = (sale.invoiceNo.isEmpty ? sale.id : sale.invoiceNo)
        .replaceAll(RegExp(r'[^A-Za-z0-9_-]'), '');
    return 'invoice_${base.isEmpty ? 'receipt' : base}.pdf';
  }

  /// Open the system print dialog with the receipt PDF, laid out per the active
  /// thermal-print settings (English / bilingual Arabic, paper width, …).
  Future<void> _print(BuildContext context) async {
    final settings = context.read<PrintSettingsController>().snapshot;
    try {
      await Printing.layoutPdf(
        name: _fileName,
        onLayout: (_) => buildReceiptPdf(sale, settings),
      );
    } catch (_) {
      if (context.mounted) _toast(context, 'Could not open the print dialog.');
    }
  }

  /// Open the share sheet with the receipt PDF attached.
  Future<void> _share(BuildContext context) async {
    final settings = context.read<PrintSettingsController>().snapshot;
    try {
      final bytes = await buildReceiptPdf(sale, settings);
      await Printing.sharePdf(bytes: bytes, filename: _fileName);
    } catch (_) {
      if (context.mounted) _toast(context, 'Could not share the receipt.');
    }
  }

  void _toast(BuildContext context, String message) {
    ScaffoldMessenger.of(context)
      ..clearSnackBars()
      ..showSnackBar(SnackBar(content: Text(message)));
  }

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

/// A thin dashed horizontal rule (used between the summary lines and the total).
class DottedDivider extends StatelessWidget {
  const DottedDivider({super.key, required this.color, this.dash = 4, this.gap = 4});
  final Color color;
  final double dash;
  final double gap;

  @override
  Widget build(BuildContext context) => LayoutBuilder(
        builder: (context, c) {
          final count = (c.maxWidth / (dash + gap)).floor().clamp(0, 400);
          return Row(
            mainAxisAlignment: MainAxisAlignment.spaceBetween,
            children: List.generate(
              count,
              (_) => Container(width: dash, height: 1.5, color: color),
            ),
          );
        },
      );
}
