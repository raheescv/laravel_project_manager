import 'package:flutter/material.dart';
import 'package:invo/features/sale_return/domain/repository/sale_return_repository.dart';
import 'package:flutter/services.dart';
import 'package:go_router/go_router.dart';
import 'package:printing/printing.dart';
import 'package:provider/provider.dart';

import 'package:invo/shared/domain/constants/global_variables.dart';
import 'package:invo/shared/domain/helpers/formatters.dart';
import 'package:invo/shared/domain/helpers/responsive.dart';
import 'package:invo/shared/domain/models/index.dart';
import 'package:invo/features/sale/logic/cart_cubit/cart_cubit.dart';
import 'package:invo/features/settings/logic/print_settings_cubit/print_settings_cubit.dart';
import 'package:invo/features/sale_return/logic/return_draft_cubit/return_draft_cubit.dart';
import 'package:invo/shared/utils/components/theme/index.dart';
import 'package:invo/shared/widgets/astra_widgets.dart';
import 'package:invo/shared/widgets/invo_logo.dart';
import 'package:invo/shared/widgets/receipt_pdf.dart';

/// Single-sale view — "Fintech" premium layout: a gradient total hero, a
/// Created → Paid → Receipt status timeline, an itemized card with per-line
/// icon avatars, a summary card and a payment/customer card. Everything is
/// palette-driven so each [AstraSkin] re-skins it.
class InvoiceScreen extends StatelessWidget {
  const InvoiceScreen({super.key, required this.sale});
  final Sale sale;

  // Net payable on this ticket and how much of it is still outstanding. Both come
  // straight from the sale's own columns (grand_total / balance); we fall back to
  // a local recompute only for older payloads that didn't send them.
  double get _payable =>
      sale.grandTotal > 0 ? sale.grandTotal : sale.grossAmount - sale.discount + sale.taxAmount;
  double get _balance => sale.grandTotal > 0 ? sale.balance : _payable - sale.paid;
  // Settled only when nothing is outstanding — a "completed" sale can still owe a
  // balance (e.g. a credit sale), so status alone must not mark it paid.
  bool get _paidUp => _balance <= 0.5;

  // Only completed invoices can be returned (matches the returns flow).
  bool get _returnable => sale.status.toLowerCase() == 'completed';

  // A cancelled sale is view-only; completed or draft sales can be edited.
  bool get _editable => sale.status.toLowerCase() != 'cancelled';

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
                    child: Column(
                      mainAxisSize: MainAxisSize.min,
                      children: [
                        if (_editable || _returnable) ...[
                          Row(
                            children: [
                              if (_editable)
                                Expanded(child: _editInvoiceButton(context, p)),
                              if (_editable && _returnable) const SizedBox(width: 9),
                              if (_returnable)
                                Expanded(child: _returnInvoiceButton(context, p)),
                            ],
                          ),
                          const SizedBox(height: 9),
                        ],
                        Row(
                          children: [
                            Expanded(child: _action(context, Icons.print_outlined, 'Print', () => _preview(context))),
                            const SizedBox(width: 9),
                            Expanded(child: _action(context, Icons.ios_share, 'Share', () => _share(context))),
                            const SizedBox(width: 9),
                            Expanded(
                              flex: 2,
                              child: AstraButton(label: 'New Sale', onTap: () => _tap(() => context.go('/sale'))),
                            ),
                          ],
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
              onTap: () => _tap(() => context.pop()),
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

  (String, Color, Color, IconData) _statusBadge(AstraPalette p) {
    if (_paidUp) {
      return ('PAID', p.successTint, AstraPalette.success, Icons.check_circle);
    }
    if (sale.paid > 0) {
      return ('PARTIAL', p.warnTint, p.warnText, Icons.timelapse);
    }
    return ('UNPAID', p.dangerTint, AstraPalette.danger, Icons.error_outline);
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

  /// Open the share sheet with the receipt PDF attached.
  Future<void> _share(BuildContext context) async {
    final settings = context.read<PrintSettingsCubit>().snapshot;
    try {
      final bytes = await buildReceiptPdf(sale, settings);
      await Printing.sharePdf(bytes: bytes, filename: _fileName);
    } catch (_) {
      if (context.mounted) _toast(context, 'Could not share the receipt.');
    }
  }

  /// Preview-before-print: a full-screen preview of the actual thermal receipt,
  /// filling the screen width in its native thermal style (header included,
  /// nothing cropped). The user reviews it, then prints/shares from the preview's
  /// own action bar — that's the actual print step.
  void _preview(BuildContext context) {
    final settings = context.read<PrintSettingsCubit>().snapshot;
    final title = 'Invoice ${sale.invoiceNo.isEmpty ? sale.id : sale.invoiceNo}';
    Navigator.of(context).push(MaterialPageRoute<void>(
      fullscreenDialog: true,
      builder: (_) => Scaffold(
        backgroundColor: Colors.white,
        appBar: AppBar(title: Text(title)),
        body: PdfPreview(
          // Always render the native thermal roll; the preview scales it to fill
          // the screen width so the receipt occupies the page edge-to-edge.
          build: (_) => buildReceiptPdf(sale, settings),
          useActions: true,
          canChangePageFormat: false,
          canChangeOrientation: false,
          canDebug: false,
          pdfFileName: _fileName,
          padding: EdgeInsets.zero,
          previewPageMargin: EdgeInsets.zero,
          scrollViewDecoration: const BoxDecoration(color: Colors.white),
        ),
      ),
    ));
  }

  void _toast(BuildContext context, String message) {
    ScaffoldMessenger.of(context)
      ..clearSnackBars()
      ..showSnackBar(SnackBar(content: Text(message)));
  }

  // ---- Return shortcut -----------------------------------------------------

  /// Jump straight into a return for this invoice: load its returnable lines,
  /// seed the return draft, and open the New Return screen.
  Future<void> _return(BuildContext context) async {
    showDialog(context: context, barrierDismissible: false, builder: (_) => const Center(child: CircularProgressIndicator()));
    try {
      final returnable = await serviceLocator<SaleReturnRepository>().returnableSale(sale.id);
      if (!context.mounted) return;
      Navigator.pop(context); // close the loader
      final hasReturnable = returnable.lines.any((l) => l.returnableQuantity > 0);
      if (!hasReturnable) {
        _toast(context, 'Every item on this invoice has already been returned.');
        return;
      }
      context.read<ReturnDraftCubit>().seed(returnable);
      context.push('/sale-return');
    } catch (_) {
      if (context.mounted) Navigator.pop(context);
      if (context.mounted) _toast(context, 'Could not start a return for this invoice.');
    }
  }

  Widget _returnInvoiceButton(BuildContext context, AstraPalette p) {
    final t = context.astraTheme;
    return GestureDetector(
      onTap: () => _tap(() => _return(context)),
      child: Container(
        width: double.infinity,
        padding: const EdgeInsets.symmetric(vertical: 13, horizontal: 14),
        decoration: BoxDecoration(
          color: p.card,
          borderRadius: BorderRadius.circular(13),
          border: Border.all(color: p.goldText.withValues(alpha: 0.5), width: 1.5),
          boxShadow: t.softShadow,
        ),
        child: Row(
          mainAxisAlignment: MainAxisAlignment.center,
          children: [
            Icon(Icons.assignment_return_outlined, size: 16, color: p.goldText),
            const SizedBox(width: 9),
            Flexible(child: Text('Return', maxLines: 1, overflow: TextOverflow.ellipsis, style: ui(size: 13, weight: FontWeight.w800, color: p.ink))),
          ],
        ),
      ),
    );
  }

  /// Re-open this sale in the New Sale flow for editing: load it into the ticket
  /// (each line keeps its sale_item id) and push the edit screen.
  void _edit(BuildContext context) {
    context.read<CartCubit>().seedFromSale(sale);
    context.push('/sale');
  }

  Widget _editInvoiceButton(BuildContext context, AstraPalette p) {
    final t = context.astraTheme;
    return GestureDetector(
      onTap: () => _tap(() => _edit(context)),
      child: Container(
        width: double.infinity,
        padding: const EdgeInsets.symmetric(vertical: 13, horizontal: 14),
        decoration: BoxDecoration(
          color: p.card,
          borderRadius: BorderRadius.circular(13),
          border: Border.all(color: p.primary.withValues(alpha: 0.5), width: 1.5),
          boxShadow: t.softShadow,
        ),
        child: Row(
          mainAxisAlignment: MainAxisAlignment.center,
          children: [
            Icon(Icons.edit_outlined, size: 16, color: p.primary),
            const SizedBox(width: 9),
            Flexible(child: Text('Edit', maxLines: 1, overflow: TextOverflow.ellipsis, style: ui(size: 13, weight: FontWeight.w800, color: p.ink))),
          ],
        ),
      ),
    );
  }

  /// Fire a short tap vibration, then run [action]. Every button on this view
  /// page is routed through here so taps give haptic feedback.
  void _tap(VoidCallback action) {
    HapticFeedback.lightImpact();
    action();
  }

  Widget _action(BuildContext context, IconData icon, String label, VoidCallback onTap) {
    final p = context.astra;
    final t = context.astraTheme;
    return GestureDetector(
      onTap: () => _tap(onTap),
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
