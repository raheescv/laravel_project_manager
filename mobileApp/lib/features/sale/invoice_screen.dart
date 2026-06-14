import 'package:flutter/material.dart';
import 'package:go_router/go_router.dart';
import 'package:printing/printing.dart';

import '../../core/formatters.dart';
import '../../core/responsive.dart';
import '../../models/models.dart';
import '../../theme/palette.dart';
import '../../theme/theme.dart';
import '../../widgets/astra_widgets.dart';
import '../../widgets/invo_logo.dart';
import 'receipt_pdf.dart';

class InvoiceScreen extends StatelessWidget {
  const InvoiceScreen({super.key, required this.sale});
  final Sale sale;

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
              Padding(
                padding: const EdgeInsets.fromLTRB(20, 10, 20, 0),
                child: Row(
                  children: [
                    const SizedBox(width: 4),
                    const InvoLogomark(height: 34),
                    const SizedBox(width: 12),
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
                    StatusPill(
                      label: sale.status.toUpperCase() == 'COMPLETED' ? 'PAID' : sale.status.toUpperCase(),
                      bg: AstraPalette.successTint,
                      fg: AstraPalette.success,
                      icon: Icons.check_circle,
                    ),
                  ],
                ),
              ),
              Expanded(
                child: ListView(
                  padding: const EdgeInsets.fromLTRB(16, 14, 16, 24),
                  children: [
                    AstraCard(
                      radius: 14,
                      child: Row(
                        mainAxisAlignment: MainAxisAlignment.spaceBetween,
                        children: [
                          _meta('INVOICE', sale.invoiceNo.isEmpty ? '#${sale.id}' : sale.invoiceNo, p),
                          _meta('DATE', Dates.human(sale.date), p),
                          _meta('STYLIST', sale.createdBy.isEmpty ? '—' : sale.createdBy, p, end: true),
                        ],
                      ),
                    ),
                    const SizedBox(height: 14),
                    for (final l in sale.lines)
                      Padding(
                        padding: const EdgeInsets.only(bottom: 10),
                        child: Row(
                          children: [
                            Expanded(
                              child: Column(
                                crossAxisAlignment: CrossAxisAlignment.start,
                                children: [
                                  Text(l.name, style: ui(size: 12.5, weight: FontWeight.w700, color: p.ink)),
                                  Text('${l.quantity.toStringAsFixed(l.quantity % 1 == 0 ? 0 : 1)} × ${Money.of(l.unitPrice)}${l.employee.isEmpty ? '' : ' · ${l.employee}'}',
                                      style: ui(size: 10, weight: FontWeight.w600, color: p.textMuted)),
                                ],
                              ),
                            ),
                            Text(Money.of(l.total), style: ui(size: 12.5, weight: FontWeight.w800, color: p.ink)),
                          ],
                        ),
                      ),
                    Padding(
                      padding: const EdgeInsets.symmetric(vertical: 6),
                      child: Container(height: 1, color: p.hairline),
                    ),
                    _row('Subtotal', Money.of(sale.grossAmount), p.textSecondary),
                    if (sale.discount > 0) _row('Discount', '− ${Money.of(sale.discount)}', p.goldText),
                    _row('Tax', Money.of(sale.taxAmount), p.textSecondary),
                    const SizedBox(height: 6),
                    Container(
                      padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 10),
                      decoration: BoxDecoration(color: p.tint, borderRadius: BorderRadius.circular(11)),
                      child: Row(
                        mainAxisAlignment: MainAxisAlignment.spaceBetween,
                        children: [
                          Text('Total Paid', style: ui(size: 12.5, weight: FontWeight.w800, color: p.ink)),
                          Text(Money.of(sale.paid), style: serif(size: 18, color: p.primaryDark)),
                        ],
                      ),
                    ),
                    if (sale.payments.isNotEmpty) ...[
                      const SizedBox(height: 8),
                      Row(
                        children: [
                          Icon(Icons.credit_card, size: 13, color: p.textMuted),
                          const SizedBox(width: 7),
                          Text('Paid by ${sale.payments.first.method}',
                              style: ui(size: 10.5, weight: FontWeight.w600, color: p.textMuted)),
                        ],
                      ),
                    ],
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

  String get _fileName {
    final base = (sale.invoiceNo.isEmpty ? sale.id : sale.invoiceNo)
        .replaceAll(RegExp(r'[^A-Za-z0-9_-]'), '');
    return 'invoice_${base.isEmpty ? 'receipt' : base}.pdf';
  }

  /// Open the system print dialog with the receipt PDF.
  Future<void> _print(BuildContext context) async {
    try {
      await Printing.layoutPdf(
        name: _fileName,
        onLayout: (_) => buildReceiptPdf(sale),
      );
    } catch (_) {
      if (context.mounted) _toast(context, 'Could not open the print dialog.');
    }
  }

  /// Open the share sheet with the receipt PDF attached.
  Future<void> _share(BuildContext context) async {
    try {
      final bytes = await buildReceiptPdf(sale);
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

  Widget _meta(String label, String value, p, {bool end = false}) => Column(
        crossAxisAlignment: end ? CrossAxisAlignment.end : CrossAxisAlignment.start,
        children: [
          Text(label, style: ui(size: 9.5, weight: FontWeight.w700, color: p.textMuted, letterSpacing: 0.4)),
          const SizedBox(height: 3),
          Text(value, style: ui(size: 12.5, weight: FontWeight.w800, color: p.ink)),
        ],
      );

  Widget _row(String label, String value, Color color) => Padding(
        padding: const EdgeInsets.only(bottom: 5),
        child: Row(
          mainAxisAlignment: MainAxisAlignment.spaceBetween,
          children: [
            Text(label, style: ui(size: 11, weight: FontWeight.w600, color: color)),
            Text(value, style: ui(size: 11, weight: FontWeight.w700, color: color)),
          ],
        ),
      );
}
