import 'dart:async';

import 'package:flutter/material.dart';
import 'package:invo/features/sale/logic/sale_ops_cubit/sale_ops_cubit.dart';
import 'package:flutter/services.dart';
import 'package:go_router/go_router.dart';
import 'package:printing/printing.dart';
import 'package:provider/provider.dart';

import 'package:invo/shared/domain/constants/mobile_permissions.dart';
import 'package:invo/shared/domain/helpers/formatters.dart';
import 'package:invo/shared/domain/helpers/responsive.dart';
import 'package:invo/shared/domain/models/index.dart';
import 'package:invo/features/auth/logic/auth_cubit/auth_cubit.dart';
import 'package:invo/features/sale/logic/cart_cubit/cart_cubit.dart';
import 'package:invo/features/settings/logic/print_settings_cubit/print_settings_cubit.dart';
import 'package:invo/features/sale_return/logic/return_draft_cubit/return_draft_cubit.dart';
import 'package:invo/shared/utils/components/app_strings.dart';
import 'package:invo/shared/utils/router/http_utils/common_exception.dart';
import 'package:invo/shared/utils/components/theme/index.dart';
import 'package:invo/shared/utils/router/routes.dart';
import 'package:invo/shared/widgets/astra_widgets.dart';
import 'package:invo/shared/widgets/invo_logo.dart';
import 'package:invo/shared/widgets/receipt_pdf.dart';
import 'package:invo/shared/widgets/receipt_printer.dart';
import 'package:invo/shared/widgets/tablet_widgets.dart';

part 'invoice_receipt_sections.dart';

/// Single-sale view — "Fintech" premium layout: a gradient total hero, a
/// Created → Paid → Receipt status timeline, an itemized card with per-line
/// icon avatars, a summary card and a payment/customer card. Everything is
/// palette-driven so each [AstraSkin] re-skins it.
class InvoiceScreen extends StatelessWidget {
  const InvoiceScreen({super.key, required this.sale, this.onClose});
  final Sale sale;

  /// When set, this screen is embedded in a tablet master–detail pane rather
  /// than pushed as its own route: the header shows a "clear selection" button
  /// instead of a back arrow, and deleting the sale calls this (to drop the row
  /// and reload the list) rather than popping a route. Null on phones.
  final VoidCallback? onClose;

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
    // Tablet renders the flat detail layout from the approved preview instead of
    // the phone receipt (gradient hero, timeline, docked action bar) — both when
    // embedded in a master–detail pane and when pushed as the last step of a
    // sale, so an invoice looks the same however you arrived at it.
    if (context.isTablet || onClose != null) return _tabletDetail(context, p, canEdit, canDelete);
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
                          _utilityButton(context, p, Icons.print_outlined, 'Print', () => _print(context)),
                          const SizedBox(width: 9),
                          Expanded(
                            child: AstraButton(
                              label: 'New Sale',
                              icon: Icons.add,
                              onTap: () => _tap(() => context.go(Routes.sale)),
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

  // ---- Tablet detail pane --------------------------------------------------

  /// The preview's `.detail`: caption + amount + context line with the record's
  /// actions on the right, then plain Items / Summary / Payment panels. Same
  /// data and same actions as the phone receipt — only the presentation differs.
  Widget _tabletDetail(BuildContext context, AstraPalette p, bool canEdit, bool canDelete) {
    final embedded = onClose != null;
    final (label, bg, fg, icon) = _statusBadge(p);
    final methods = sale.payments.map((e) => _titleCase(e.method.trim())).where((e) => e.isNotEmpty).toSet();
    final subtitle = [
      sale.customerName.trim().isEmpty ? AppStrings.walkInCustomer : sale.customerName.trim(),
      if (sale.date.isNotEmpty) Dates.human(sale.date),
      if (methods.isNotEmpty) methods.join(', '),
    ].join('  ·  ');

    return Scaffold(
      backgroundColor: Colors.transparent,
      body: AstraBackground(
        child: SafeArea(
          child: LayoutBuilder(
            builder: (ctx, c) {
              final m = TabletMetrics.forWidth(c.maxWidth);
              return ListView(
                padding: m.detailPadding,
                children: [
                  TabletDetailHead(
                    label: 'Invoice · ${sale.invoiceNo.isEmpty ? '#${sale.id}' : sale.invoiceNo}',
                    amount: Money.of(_payable),
                    subtitle: subtitle,
                    badge: StatusPill(label: label, bg: bg, fg: fg, icon: icon),
                    // Pushed route (e.g. straight after charging) gets a back
                    // arrow; a master–detail pane gets a close that clears the
                    // selection instead.
                    leading: embedded || !context.canPop()
                        ? null
                        : TabletIconButton(
                            icon: Icons.chevron_left, tooltip: 'Back', onTap: () => _tap(() => context.pop())),
                    actions: [
                      TabletActionButton(
                          label: 'Print', icon: Icons.print_outlined, onTap: () => _tap(() => _print(context))),
                      if (_returnable)
                        TabletActionButton(
                            label: 'Return',
                            icon: Icons.assignment_return_outlined,
                            onTap: () => _tap(() => _return(context))),
                      if (canEdit && _editable)
                        TabletActionButton(
                            label: 'Edit', icon: Icons.edit_outlined, onTap: () => _tap(() => _edit(context))),
                      // Pushed: the dominant next action is starting the next
                      // ticket. Embedded: the list is right there, so it isn't.
                      if (!embedded)
                        TabletActionButton(
                            label: 'New Sale', icon: Icons.add, primary: true, onTap: () => _tap(() => context.go(Routes.sale))),
                      TabletIconButton(
                          icon: Icons.more_horiz,
                          tooltip: 'More',
                          onTap: () => _tap(() => _moreSheet(context, canEdit, canDelete))),
                      if (embedded)
                        TabletIconButton(
                            icon: Icons.close_rounded, tooltip: 'Close', onTap: () => _tap(onClose!)),
                    ],
                  ),
                  const SizedBox(height: 22),
                  if (sale.customerMobile.trim().isNotEmpty) ...[
                    TabletPanel(title: 'Billed to', padding: const EdgeInsets.fromLTRB(16, 12, 16, 12), child: _customerLine(p)),
                    const SizedBox(height: 18),
                  ],
                  TabletPanel(title: 'Items', child: _itemLines(p)),
                  const SizedBox(height: 18),
                  TabletPanel(
                    title: 'Summary',
                    padding: const EdgeInsets.fromLTRB(16, 14, 16, 14),
                    child: _summaryLines(p),
                  ),
                  const SizedBox(height: 18),
                  TabletPanel(title: 'Payment', child: _paymentLines(p)),
                ],
              );
            },
          ),
        ),
      ),
    );
  }

  Widget _customerLine(AstraPalette p) => Row(
        children: [
          const IconChip(icon: Icons.person_outline, size: 34, radius: 10),
          const SizedBox(width: 11),
          Expanded(child: Text(sale.customerName, style: ui(size: 13, weight: FontWeight.w700, color: p.ink))),
          Text(sale.customerMobile, style: ui(size: 12, weight: FontWeight.w600, color: p.textSecondary)),
        ],
      );

  Widget _itemLines(AstraPalette p) {
    final lines = sale.lines;
    return Column(
      children: [
        for (int i = 0; i < lines.length; i++) ...[
          _lineRow(p, lines[i]),
          if (i != lines.length - 1) Container(height: 1, color: p.hairline),
        ],
      ],
    );
  }

  Widget _summaryLines(AstraPalette p) => Column(
        children: [
          _sumRow(p, 'Subtotal', Money.of(sale.grossAmount), p.textSecondary),
          if (sale.discount > 0) _sumRow(p, 'Discount', '− ${Money.of(sale.discount)}', p.goldText),
          if (sale.taxAmount > 0) _sumRow(p, 'Tax', Money.of(sale.taxAmount), p.textSecondary),
          if (sale.tip > 0) _sumRow(p, 'Tip', Money.of(sale.tip), p.goldText),
          Padding(padding: const EdgeInsets.only(top: 8), child: DottedDivider(color: p.hairline)),
          const SizedBox(height: 12),
          if (_balance > 0.5) ...[
            _sumRow(p, 'Paid', Money.of(sale.paid), p.textSecondary),
            const SizedBox(height: 4),
            _totalRow(p, 'Balance Due', _balance, p.warnText),
          ] else
            _totalRow(p, 'Total Paid', sale.paid, p.primaryDark),
        ],
      );

  Widget _paymentLines(AstraPalette p) {
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
    return Column(children: rows);
  }

  // ---- Header --------------------------------------------------------------

  Widget _header(BuildContext context, AstraPalette p) {
    final (label, bg, fg, icon) = _statusBadge(p);
    return Padding(
      padding: const EdgeInsets.fromLTRB(16, 10, 18, 4),
      child: Row(
        children: [
          if (onClose != null) ...[
            GestureDetector(
              onTap: () => _tap(onClose!),
              child: Container(
                width: 36,
                height: 36,
                decoration: BoxDecoration(
                  color: p.card,
                  borderRadius: BorderRadius.circular(11),
                  boxShadow: context.astraTheme.softShadow,
                ),
                child: Icon(Icons.close_rounded, size: 17, color: p.ink),
              ),
            ),
            const SizedBox(width: 11),
          ] else if (context.canPop()) ...[
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

  // ---- Status timeline -----------------------------------------------------

  // ---- Customer ------------------------------------------------------------

  // ---- Items ---------------------------------------------------------------

  // ---- Summary -------------------------------------------------------------

  // ---- Payment -------------------------------------------------------------

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

  /// The Print button. Once this till is paired with a printer, printing is the
  /// point — send the receipt straight there (one tap, no preview). Without a
  /// pairing we keep the preview-then-print path, which is the only way to
  /// choose a printer on that device. The preview stays reachable either way
  /// from the "•••" sheet.
  Future<void> _print(BuildContext context) async {
    final print = context.read<PrintSettingsCubit>();
    if (!print.hasPrinter) {
      _preview(context);
      return;
    }
    final messenger = ScaffoldMessenger.of(context);
    final result = await printReceipt(
      sale,
      print.snapshot,
      printerUrl: print.printerUrl,
      printerName: print.printerName,
    );
    if (result == ReceiptPrintResult.cancelled) return;
    messenger
      ..clearSnackBars()
      ..showSnackBar(SnackBar(
        content: Text(result.ok
            ? 'Sent to ${print.printerName}'
            : 'Couldn\'t reach the printer — check it\'s on and paired.'),
        duration: const Duration(milliseconds: 1400),
      ));
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
        body: LayoutBuilder(
          builder: (ctx, c) {
            // The preview scales the page to the width it is given. A phone is
            // about a receipt wide, so edge-to-edge is right there — but a
            // tablet would blow an 80mm roll up ~5×, soft and absurd. Cap it to
            // a paper-like column and centre it on the sheet.
            const rollWidth = 420.0;
            final side = c.maxWidth <= rollWidth + 40 ? 0.0 : (c.maxWidth - rollWidth) / 2;
            return PdfPreview(
              // Always render the native thermal roll; only the on-screen
              // presentation width changes.
              build: (_) => buildReceiptPdf(sale, settings),
              useActions: true,
              canChangePageFormat: false,
              canChangeOrientation: false,
              canDebug: false,
              pdfFileName: _fileName,
              padding: EdgeInsets.symmetric(horizontal: side),
              previewPageMargin: EdgeInsets.zero,
              scrollViewDecoration: const BoxDecoration(color: Colors.white),
              // The PdfPreview action bar (print/share/format icons) otherwise
              // falls back to the platform Theme.primaryColor — pin it to the
              // selected preset so the whole preview matches the chosen theme.
              actionBarTheme: PdfActionBarTheme(
                backgroundColor: p.primary,
                iconColor: Colors.white,
              ),
            );
          },
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
    final ops = SaleOpsCubit(); // repository access for this flow (§10)
    unawaited(showDialog(context: context, barrierDismissible: false, builder: (_) => const Center(child: CircularProgressIndicator())));
    try {
      final returnable = await ops.returnableSale(sale.id);
      if (!context.mounted) return;
      Navigator.pop(context); // close the loader
      final hasReturnable = returnable.lines.any((l) => l.returnableQuantity > 0);
      if (!hasReturnable) {
        _toast(context, 'Every item on this invoice has already been returned.');
        return;
      }
      context.read<ReturnDraftCubit>().seed(returnable);
      unawaited(context.push(Routes.saleReturn));
    } catch (_) {
      if (context.mounted) Navigator.pop(context);
      if (context.mounted) _toast(context, 'Could not start a return for this invoice.');
    }
  }

  /// Re-open this sale in the New Sale flow for editing: load it into the ticket
  /// (each line keeps its sale_item id) and push the edit screen.
  void _edit(BuildContext context) {
    context.read<CartCubit>().seedFromSale(sale);
    context.push(Routes.sale);
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

    final ops = SaleOpsCubit(); // repository access for this flow (§10)
    unawaited(showDialog(
      context: context,
      barrierDismissible: false,
      builder: (_) => const Center(child: CircularProgressIndicator()),
    ));
    try {
      await ops.deleteSale(sale.id);
      if (!context.mounted) return;
      Navigator.pop(context); // dismiss the loader
      _toast(context, 'Sale deleted.');
      if (onClose != null) {
        onClose!(); // embedded: drop the row + reload the list, keep the pane
      } else if (context.canPop()) {
        context.pop(true); // signal the sales list to reload
      } else {
        context.go(Routes.sales);
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
                      _sheetAction(ctx, p, Icons.visibility_outlined, p.ink, 'Preview receipt',
                          'See the thermal receipt before printing', () => _preview(context)),
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
