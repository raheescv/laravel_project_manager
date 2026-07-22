import 'package:flutter/material.dart';
import 'package:invo/features/sale_return/domain/repository/sale_return_repository.dart';
import 'package:flutter/services.dart';
import 'package:go_router/go_router.dart';
import 'package:printing/printing.dart';
import 'package:provider/provider.dart';

import 'package:invo/shared/domain/constants/global_variables.dart';
import 'package:invo/shared/domain/constants/mobile_permissions.dart';
import 'package:invo/shared/domain/helpers/formatters.dart';
import 'package:invo/shared/domain/helpers/responsive.dart';
import 'package:invo/shared/domain/models/index.dart';
import 'package:invo/features/auth/logic/auth_cubit/auth_cubit.dart';
import 'package:invo/features/sale/domain/repository/sale_repository.dart';
import 'package:invo/features/sale/logic/cart_cubit/cart_cubit.dart';
import 'package:invo/features/settings/logic/print_settings_cubit/print_settings_cubit.dart';
import 'package:invo/features/sale_return/logic/return_draft_cubit/return_draft_cubit.dart';
import 'package:invo/shared/utils/router/http_utils/common_exception.dart';
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
    // Edit and Delete are permission-gated (match the web `sale.edit` /
    // `sale.delete` guards). The server still enforces the business rules —
    // e.g. a completed sale is refused for delete.
    final auth = context.read<AuthCubit>();
    final canEdit = auth.hasPermission(PermissionSlug.saleEdit);
    final canDelete = auth.hasPermission(PermissionSlug.saleDelete);
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
                    padding: const EdgeInsets.fromLTRB(14, 2, 14, 8),
                    // Compact action bar: Print stays visible next to the dominant
                    // "New Sale" CTA; every other action (Edit / Return / Share /
                    // Delete) lives one tap deep behind the "•••" overflow sheet.
                    child: IntrinsicHeight(
                      child: Row(
                        crossAxisAlignment: CrossAxisAlignment.stretch,
                        children: [
                          _utilityButton(context, p, Icons.print_outlined, 'Print', () => _preview(context)),
                          const SizedBox(width: 9),
                          Expanded(
                            child: AstraButton(
                              label: 'New Sale',
                              icon: Icons.add,
                              onTap: () => _tap(() => context.go('/sale')),
                            ),
                          ),
                          const SizedBox(width: 9),
                          _moreButton(context, p, canEdit, canDelete),
                        ],
                      ),
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
                Text(sale.branch.isEmpty ? 'Astra POS' : sale.branch,
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
    // The preview navbar picks up the selected theme preset's brand colour so it
    // matches the rest of the app rather than the platform default.
    final p = context.astra;
    Navigator.of(context).push(MaterialPageRoute<void>(
      fullscreenDialog: true,
      builder: (_) => Scaffold(
        backgroundColor: Colors.white,
        appBar: AppBar(
          title: Text(title),
          backgroundColor: p.primary,
          foregroundColor: Colors.white,
        ),
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
          // The PdfPreview action bar (print/share/format icons) otherwise falls
          // back to the platform Theme.primaryColor — pin it to the selected
          // preset so the whole preview matches the chosen theme.
          actionBarTheme: PdfActionBarTheme(
            backgroundColor: p.primary,
            iconColor: Colors.white,
          ),
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

  /// Re-open this sale in the New Sale flow for editing: load it into the ticket
  /// (each line keeps its sale_item id) and push the edit screen.
  void _edit(BuildContext context) {
    context.read<CartCubit>().seedFromSale(sale);
    context.push('/sale');
  }

  // ---- Delete --------------------------------------------------------------

  /// Confirm, then permanently delete this sale. On success we leave the view
  /// page (the sale no longer exists) — popping back to the sales list with a
  /// `true` result so it refreshes, or falling back to the list route. The
  /// server enforces the rule that a completed sale can't be deleted; its exact
  /// message is surfaced if it refuses.
  Future<void> _delete(BuildContext context) async {
    final ref = sale.invoiceNo.isEmpty ? '#${sale.id}' : sale.invoiceNo;
    final confirmed = await showDialog<bool>(
      context: context,
      builder: (ctx) => AlertDialog(
        title: const Text('Delete this sale?'),
        content: Text('Invoice $ref, its items and payments will be permanently '
            'removed. This cannot be undone.'),
        actions: [
          TextButton(onPressed: () => Navigator.pop(ctx, false), child: const Text('Cancel')),
          TextButton(
            onPressed: () => Navigator.pop(ctx, true),
            child: const Text('Delete',
                style: TextStyle(color: AstraPalette.danger, fontWeight: FontWeight.w700)),
          ),
        ],
      ),
    );
    if (confirmed != true || !context.mounted) return;

    showDialog(
      context: context,
      barrierDismissible: false,
      builder: (_) => const Center(child: CircularProgressIndicator()),
    );
    try {
      await serviceLocator<SaleRepository>().deleteSale(sale.id);
      if (!context.mounted) return;
      Navigator.pop(context); // dismiss the loader
      _toast(context, 'Sale deleted.');
      if (context.canPop()) {
        context.pop(true); // signal the sales list to reload
      } else {
        context.go('/sales');
      }
    } catch (e) {
      if (!context.mounted) return;
      Navigator.pop(context); // dismiss the loader
      _toast(context, e is ApiException ? e.message : 'Could not delete the sale.');
    }
  }

  /// Fire a short tap vibration, then run [action]. Every button on this view
  /// page is routed through here so taps give haptic feedback.
  void _tap(VoidCallback action) {
    HapticFeedback.lightImpact();
    action();
  }

  // ---- Compact action bar --------------------------------------------------

  /// A slim card button (icon + label) that stretches to the CTA's height via
  /// the enclosing IntrinsicHeight/stretch row. Used for the always-visible
  /// Print action beside "New Sale".
  Widget _utilityButton(BuildContext context, AstraPalette p, IconData icon, String label, VoidCallback onTap) {
    final t = context.astraTheme;
    return GestureDetector(
      onTap: () => _tap(onTap),
      child: Container(
        padding: const EdgeInsets.symmetric(horizontal: 16),
        alignment: Alignment.center,
        decoration: BoxDecoration(
          color: p.card,
          borderRadius: BorderRadius.circular(t.rButton),
          boxShadow: t.softShadow,
        ),
        child: Row(
          mainAxisSize: MainAxisSize.min,
          children: [
            Icon(icon, size: 16, color: p.ink),
            const SizedBox(width: 7),
            Text(label, style: ui(size: 13, weight: FontWeight.w700, color: p.ink)),
          ],
        ),
      ),
    );
  }

  /// The "•••" overflow trigger — same card treatment, square-ish footprint.
  Widget _moreButton(BuildContext context, AstraPalette p, bool canEdit, bool canDelete) {
    final t = context.astraTheme;
    return GestureDetector(
      onTap: () => _tap(() => _moreSheet(context, canEdit, canDelete)),
      child: Container(
        width: 52,
        alignment: Alignment.center,
        decoration: BoxDecoration(
          color: p.card,
          borderRadius: BorderRadius.circular(t.rButton),
          boxShadow: t.softShadow,
        ),
        child: Icon(Icons.more_horiz, size: 22, color: p.ink),
      ),
    );
  }

  /// Overflow sheet holding the secondary invoice actions. Each row respects
  /// the same guards as the old inline buttons (edit/return/delete visibility).
  Future<void> _moreSheet(BuildContext context, bool canEdit, bool canDelete) async {
    final p = context.astra;
    await showModalBottomSheet(
      context: context,
      backgroundColor: Colors.transparent,
      builder: (ctx) => Container(
        decoration: BoxDecoration(
          color: p.sheet,
          borderRadius: const BorderRadius.vertical(top: Radius.circular(30)),
        ),
        padding: const EdgeInsets.fromLTRB(16, 12, 16, 16),
        child: SafeArea(
          top: false,
          child: Column(
            mainAxisSize: MainAxisSize.min,
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              Center(
                child: Container(
                  width: 40,
                  height: 4,
                  decoration: BoxDecoration(
                    color: p.textMuted.withValues(alpha: 0.30),
                    borderRadius: BorderRadius.circular(2),
                  ),
                ),
              ),
              const SizedBox(height: 16),
              const SectionLabel('Invoice'),
              const SizedBox(height: 4),
              Text('Actions', style: serif(size: 22, color: p.ink)),
              const SizedBox(height: 16),
              // Scrollable so all four actions never overflow the sheet in
              // landscape / on a short viewport.
              Flexible(
                child: SingleChildScrollView(
                  child: Column(
                    mainAxisSize: MainAxisSize.min,
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: [
                      if (_editable && canEdit)
                        _sheetAction(ctx, p, Icons.edit_outlined, p.primary, 'Edit',
                            'Re-open this sale to change items', () => _edit(context)),
                      if (_returnable)
                        _sheetAction(ctx, p, Icons.assignment_return_outlined, p.goldText, 'Return',
                            'Start a return against this invoice', () => _return(context)),
                      _sheetAction(ctx, p, Icons.ios_share, p.ink, 'Share',
                          'Send the receipt as a PDF', () => _share(context)),
                      if (canDelete)
                        _sheetAction(ctx, p, Icons.delete_outline, AstraPalette.danger, 'Delete',
                            'Permanently remove this sale', () => _delete(context)),
                    ],
                  ),
                ),
              ),
            ],
          ),
        ),
      ),
    );
  }

  /// A single row inside the overflow sheet: tinted icon tile, title + hint,
  /// chevron. Taps close the sheet first, then run [action] on the page.
  Widget _sheetAction(BuildContext ctx, AstraPalette p, IconData icon, Color tint,
      String label, String subtitle, VoidCallback action) {
    final t = ctx.astraTheme;
    return Padding(
      padding: const EdgeInsets.only(bottom: 10),
      child: GestureDetector(
        onTap: () {
          HapticFeedback.lightImpact();
          Navigator.pop(ctx);
          action();
        },
        child: Container(
          padding: const EdgeInsets.symmetric(horizontal: 14, vertical: 12),
          decoration: BoxDecoration(
            color: p.card,
            borderRadius: BorderRadius.circular(16),
            boxShadow: t.softShadow,
          ),
          child: Row(
            children: [
              Container(
                width: 38,
                height: 38,
                alignment: Alignment.center,
                decoration: BoxDecoration(
                  color: tint.withValues(alpha: 0.12),
                  borderRadius: BorderRadius.circular(11),
                ),
                child: Icon(icon, size: 18, color: tint),
              ),
              const SizedBox(width: 12),
              Expanded(
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    Text(label, style: ui(size: 14, weight: FontWeight.w800, color: p.ink)),
                    const SizedBox(height: 1),
                    Text(subtitle,
                        maxLines: 1,
                        overflow: TextOverflow.ellipsis,
                        style: ui(size: 11, weight: FontWeight.w500, color: p.textMuted)),
                  ],
                ),
              ),
              Icon(Icons.chevron_right, size: 18, color: p.textMuted),
            ],
          ),
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
