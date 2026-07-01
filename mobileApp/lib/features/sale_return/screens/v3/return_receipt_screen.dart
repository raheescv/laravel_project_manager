import 'package:flutter/material.dart';
import 'package:invo/features/sale_return/domain/repository/sale_return_repository.dart';
import 'package:go_router/go_router.dart';
import 'package:provider/provider.dart';

import 'package:invo/shared/utils/router/http_utils/common_exception.dart';
import 'package:invo/features/auth/logic/auth_cubit/auth_cubit.dart';
import 'package:invo/shared/domain/constants/global_variables.dart';
import 'package:invo/shared/domain/constants/mobile_permissions.dart';
import 'package:invo/shared/domain/helpers/formatters.dart';
import 'package:invo/shared/domain/helpers/responsive.dart';
import 'package:invo/shared/domain/models/index.dart';
import 'package:invo/features/sale_return/logic/return_draft_cubit/return_draft_cubit.dart';
import 'package:invo/shared/utils/components/theme/index.dart';
import 'package:invo/shared/widgets/astra_widgets.dart';
import 'package:invo/shared/widgets/invo_logo.dart';
import 'package:invo/features/sale/screens/v3/invoice_screen.dart'
    show DottedDivider;

/// Sale-return confirmation / view — mirrors the invoice screen: a refund hero,
/// the returned items, a summary and a refund-payment card. Palette-driven so
/// every [AstraSkin] re-skins it.
class ReturnReceiptScreen extends StatelessWidget {
  const ReturnReceiptScreen({super.key, required this.saleReturn});
  final SaleReturn saleReturn;

  double get _refunded => saleReturn.paid;
  // Outstanding refund still owed to the customer. Uses the return's own balance
  // column (grand_total − paid); recomputes locally for older payloads.
  double get _balance {
    final col = saleReturn.balance;
    return col != 0 ? col : saleReturn.grandTotal - saleReturn.paid;
  }

  // Fully settled only when nothing is left to refund — a "completed" return can
  // still owe a balance, so status alone must not mark it refunded.
  bool get _fullyRefunded => saleReturn.grandTotal > 0 && _balance <= 0.5;

  // Editable when not cancelled and the source sale is known (so its returnable
  // lines can be re-fetched to seed the edit).
  bool get _editable => saleReturn.status.toLowerCase() != 'cancelled' && saleReturn.saleId.isNotEmpty;

  @override
  Widget build(BuildContext context) {
    final p = context.astra;
    final auth = context.read<AuthCubit>();
    final canEdit = auth.hasPermission(PermissionSlug.saleReturnEdit);
    final canCreate = auth.hasPermission(PermissionSlug.saleReturnCreate);
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
                    child: Column(
                      mainAxisSize: MainAxisSize.min,
                      children: [
                        if (_editable && canEdit) ...[
                          _editReturnButton(context, p),
                          const SizedBox(height: 9),
                        ],
                        Row(
                          children: [
                            if (canCreate) ...[
                              Expanded(child: _action(context, Icons.assignment_return_outlined, 'New Return', () => context.go('/sale-return/pick'))),
                              const SizedBox(width: 9),
                            ],
                            Expanded(
                              flex: 2,
                              child: AstraButton(label: 'Done', onTap: () => context.go('/sales-returns')),
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
    if (_fullyRefunded) {
      return ('REFUNDED', p.successTint, AstraPalette.success, Icons.check_circle);
    }
    if (saleReturn.paid > 0) {
      return ('PARTIAL', p.warnTint, p.warnText, Icons.timelapse);
    }
    return ('PENDING', p.dangerTint, AstraPalette.danger, Icons.error_outline);
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
                  Text(_fullyRefunded ? 'TOTAL REFUNDED' : 'REFUND DUE',
                      style: ui(size: 10.5, weight: FontWeight.w700, color: faint, letterSpacing: 1.4)),
                  const SizedBox(height: 7),
                  FittedBox(
                    fit: BoxFit.scaleDown,
                    alignment: Alignment.centerLeft,
                    child: Text(Money.of(_fullyRefunded ? _refunded : _balance),
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
            // Refunded vs balance, driven by the return's own paid/balance columns.
            if (_balance > 0.5) ...[
              _sumRow(p, 'Refunded', Money.of(_refunded), p.textSecondary),
              const SizedBox(height: 4),
              _totalRow(p, 'Refund Due', _balance, p.warnText),
            ] else
              _totalRow(p, 'Total Refunded', _refunded, p.primaryDark),
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
    if (saleReturn.payments.isNotEmpty) {
      for (int i = 0; i < saleReturn.payments.length; i++) {
        final pay = saleReturn.payments[i];
        rows.add(_payRow(p, pay.method, Money.of(pay.amount)));
        if (i != saleReturn.payments.length - 1) rows.add(Container(height: 1, color: p.hairline));
      }
    } else {
      rows.add(_payRow(p, _fullyRefunded ? 'Refunded' : 'Pending', Money.of(saleReturn.paid)));
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

  // ---- Edit shortcut -------------------------------------------------------

  /// Re-open this return for editing: re-fetch the source sale's returnable lines,
  /// overlay this return's quantities, and open the return flow in edit mode.
  Future<void> _edit(BuildContext context) async {
    showDialog(context: context, barrierDismissible: false, builder: (_) => const Center(child: CircularProgressIndicator()));
    try {
      final returnable = await serviceLocator<SaleReturnRepository>().returnableSale(saleReturn.saleId);
      if (!context.mounted) return;
      Navigator.pop(context); // close the loader
      context.read<ReturnDraftCubit>().seedForEdit(returnable, saleReturn);
      context.push('/sale-return');
    } on ApiException catch (e) {
      if (context.mounted) Navigator.pop(context);
      if (context.mounted) _toast(context, e.message);
    } catch (_) {
      if (context.mounted) Navigator.pop(context);
      if (context.mounted) _toast(context, 'Could not open this return for editing.');
    }
  }

  void _toast(BuildContext context, String message) {
    ScaffoldMessenger.of(context)
      ..clearSnackBars()
      ..showSnackBar(SnackBar(content: Text(message)));
  }

  Widget _editReturnButton(BuildContext context, AstraPalette p) {
    final t = context.astraTheme;
    return GestureDetector(
      onTap: () => _edit(context),
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
            Flexible(child: Text('Edit this return', maxLines: 1, overflow: TextOverflow.ellipsis, style: ui(size: 13, weight: FontWeight.w800, color: p.ink))),
          ],
        ),
      ),
    );
  }
}
