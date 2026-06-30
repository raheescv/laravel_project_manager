import 'package:flutter/material.dart';
import 'package:invo/features/sale_return/domain/repository/sale_return_repository.dart';
import 'package:invo/shared/domain/repository/lookup_repository.dart';
import 'package:go_router/go_router.dart';
import 'package:provider/provider.dart';

import 'package:invo/shared/utils/router/http_utils/common_exception.dart';
import 'package:invo/shared/domain/constants/global_variables.dart';
import 'package:invo/shared/domain/helpers/formatters.dart';
import 'package:invo/shared/domain/helpers/responsive.dart';
import 'package:invo/shared/domain/models/index.dart';
import 'package:invo/features/sale/logic/cart_cubit/cart_cubit.dart' show PayMode, PayModeX;
import 'package:invo/features/sale_return/logic/return_draft_cubit/return_draft_cubit.dart';
import 'package:invo/shared/utils/components/theme/index.dart';
import 'package:invo/shared/widgets/astra_widgets.dart';
import 'package:invo/shared/widgets/invo_logo.dart';
import 'package:invo/features/sale/widgets/v3/custom_payment_sheet.dart';

/// Step 3 of a return: pick how the refund is issued and confirm. Mirrors the
/// Review & Pay screen (refund-method selector, live balance, confirm CTA).
class ReturnReviewScreen extends StatefulWidget {
  const ReturnReviewScreen({super.key});
  @override
  State<ReturnReviewScreen> createState() => _ReturnReviewScreenState();
}

class _ReturnReviewScreenState extends State<ReturnReviewScreen> {
  bool _busy = false;
  List<PaymentMethod> _methods = [];

  @override
  void initState() {
    super.initState();
    _loadMethods();
  }

  Future<void> _loadMethods() async {
    try {
      final methods = await serviceLocator<LookupRepository>().paymentMethods();
      if (mounted) setState(() => _methods = methods);
    } catch (_) {
      // Cash/Card/Credit still work without the configured list.
    }
  }

  Future<void> _refund() async {
    final draft = context.read<ReturnDraftCubit>();
    final service = serviceLocator<SaleReturnRepository>();
    final editingId = draft.editingReturnId;
    setState(() => _busy = true);
    try {
      final saleReturn = editingId.isEmpty
          ? await service.createSaleReturn(draft.toPayload())
          : await service.updateSaleReturn(editingId, draft.toPayload());
      draft.clear();
      if (mounted) context.pushReplacement('/return-receipt', extra: saleReturn);
    } on ApiException catch (e) {
      _error(e.message);
    } catch (e) {
      _error(editingId.isEmpty
          ? 'Could not save the return. Please try again.'
          : 'Could not update the return. Please try again.');
    }
    if (mounted) setState(() => _busy = false);
  }

  Future<void> _openCustom() async {
    final draft = context.read<ReturnDraftCubit>();
    if (_methods.isEmpty) {
      await _loadMethods();
      if (_methods.isEmpty) {
        _error('No payment methods are configured for this business.');
        return;
      }
    }
    if (!mounted) return;
    final result = await showCustomPaymentSheet(
      context,
      total: draft.total,
      methods: _methods,
      initial: draft.customPayments,
    );
    if (result != null && result.isNotEmpty) {
      draft.setCustomPayments(result);
    }
  }

  void _error(String m) {
    if (!mounted) return;
    ScaffoldMessenger.of(context)
      ..clearSnackBars()
      ..showSnackBar(SnackBar(content: Text(m)));
  }

  @override
  Widget build(BuildContext context) {
    final draft = context.watch<ReturnDraftCubit>();
    final settled = draft.balance.abs() < 0.001;

    return Scaffold(
      body: AstraBackground(
        child: Column(
          children: [
            EmeraldHeader(
              leading: HeaderIconButton(
                icon: Icons.chevron_left,
                onTap: () => context.canPop() ? context.pop() : context.go('/sales-returns'),
              ),
              title: 'Review & Refund',
            ),
            Expanded(
              child: MaxWidthBox(
                maxWidth: 560,
                child: ListView(
                  padding: const EdgeInsets.fromLTRB(16, 13, 16, 130),
                  children: [
                    _receiptCard(draft),
                    const SizedBox(height: 16),
                    _refundSection(draft),
                    const SizedBox(height: 16),
                    _summaryCard(draft),
                    const SizedBox(height: 12),
                    _statusCard(draft),
                  ],
                ),
              ),
            ),
          ],
        ),
      ),
      bottomNavigationBar: SafeArea(
        child: MaxWidthBox(
          maxWidth: 560,
          child: Padding(
            padding: const EdgeInsets.fromLTRB(14, 0, 14, 14),
            child: AstraButton(
              label: draft.isEditing
                  ? 'Update ${Money.of(draft.total)}'
                  : settled
                      ? 'Refund ${Money.of(draft.total)}'
                      : 'Confirm Anyway',
              gold: true,
              busy: _busy,
              onTap: draft.isEmpty ? null : _refund,
            ),
          ),
        ),
      ),
    );
  }

  Widget _receiptCard(ReturnDraftCubit draft) {
    final p = context.astra;
    return AstraCard(
      child: Column(
        children: [
          Column(
            children: [
              const InvoLogomark(height: 28),
              const SizedBox(height: 6),
              Text('REFUND', style: ui(size: 11, weight: FontWeight.w700, color: p.ink, letterSpacing: 4)),
              const SizedBox(height: 3),
              Text('${draft.customerName} · against ${draft.invoiceNo}',
                  style: ui(size: 10, weight: FontWeight.w600, color: p.textMuted)),
            ],
          ),
          _dashed(p),
          for (final l in draft.returningLines)
            Padding(
              padding: const EdgeInsets.only(bottom: 9),
              child: Row(
                children: [
                  Container(
                    padding: const EdgeInsets.symmetric(horizontal: 6, vertical: 1),
                    decoration: BoxDecoration(color: p.tint, borderRadius: BorderRadius.circular(6)),
                    child: Text('${l.returnQty.toStringAsFixed(l.returnQty % 1 == 0 ? 0 : 1)}×',
                        style: ui(size: 10.5, weight: FontWeight.w800, color: p.primaryDark)),
                  ),
                  const SizedBox(width: 8),
                  Expanded(child: Text(l.name, style: ui(size: 12, weight: FontWeight.w600, color: p.ink))),
                  Text(Money.of(l.total), style: ui(size: 12, weight: FontWeight.w800, color: p.ink)),
                ],
              ),
            ),
          _dashed(p),
          _row('Subtotal', Money.of(draft.subtotal), p.textSecondary),
          if (draft.totalDiscount > 0) _row('Discount', '− ${Money.of(draft.totalDiscount)}', p.goldText),
          if (draft.taxTotal > 0) _row('Tax', Money.of(draft.taxTotal), p.textSecondary),
          const SizedBox(height: 4),
          Row(
            mainAxisAlignment: MainAxisAlignment.spaceBetween,
            children: [
              Text('Refund total', style: ui(size: 12.5, weight: FontWeight.w800, color: p.ink)),
              Text(Money.of(draft.total), style: serif(size: 19, color: p.ink)),
            ],
          ),
        ],
      ),
    );
  }

  // ---- Refund method (Cash / Card / Credit / Custom) ----

  Widget _refundSection(ReturnDraftCubit draft) {
    final p = context.astra;
    return Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        Text('REFUND VIA', style: ui(size: 10, weight: FontWeight.w800, color: p.textMuted, letterSpacing: 0.8)),
        const SizedBox(height: 8),
        Row(
          children: [
            _method(draft, PayMode.cash, Icons.payments_outlined),
            const SizedBox(width: 8),
            _method(draft, PayMode.card, Icons.credit_card),
            const SizedBox(width: 8),
            _method(draft, PayMode.credit, Icons.description_outlined),
            const SizedBox(width: 8),
            _method(draft, PayMode.custom, Icons.tune),
          ],
        ),
      ],
    );
  }

  Widget _method(ReturnDraftCubit draft, PayMode mode, IconData icon) {
    final p = context.astra;
    final active = draft.payMode == mode;
    final isCustom = mode == PayMode.custom;
    final count = draft.customPayments.length;

    return Expanded(
      child: GestureDetector(
        onTap: () => isCustom ? _openCustom() : draft.setPayMode(mode),
        child: Container(
          height: 64,
          alignment: Alignment.center,
          decoration: BoxDecoration(
            color: active ? p.primaryDark : p.card,
            borderRadius: BorderRadius.circular(13),
            boxShadow: active ? null : context.astraTheme.softShadow,
          ),
          child: Stack(
            alignment: Alignment.center,
            children: [
              Column(
                mainAxisSize: MainAxisSize.min,
                children: [
                  Icon(icon, size: 18, color: active ? p.accent : p.textSecondary),
                  const SizedBox(height: 5),
                  Text(
                    isCustom && count > 0 ? '$count method${count == 1 ? '' : 's'}' : mode.label,
                    style: ui(size: 10.5, weight: FontWeight.w800, color: active ? Colors.white : p.textSecondary),
                  ),
                ],
              ),
              if (active) Positioned(top: 4, right: 5, child: Icon(Icons.check_circle, size: 13, color: p.accent)),
              if (isCustom && !active && count > 0)
                Positioned(
                  top: 4,
                  right: 5,
                  child: Container(
                    width: 14,
                    height: 14,
                    alignment: Alignment.center,
                    decoration: const BoxDecoration(color: AstraPalette.success, shape: BoxShape.circle),
                    child: Text('$count', style: ui(size: 8, weight: FontWeight.w800, color: Colors.white)),
                  ),
                ),
            ],
          ),
        ),
      ),
    );
  }

  // ---- Transaction summary ----

  Widget _summaryCard(ReturnDraftCubit draft) {
    final p = context.astra;
    final bal = draft.balance;
    final ({Color color, IconData icon, String label}) status = bal.abs() < 0.001
        ? (color: AstraPalette.success, icon: Icons.check_circle, label: 'Balance')
        : bal > 0
            ? (color: const Color(0xFFE08A2B), icon: Icons.timelapse, label: 'Pending Refund')
            : (color: AstraPalette.danger, icon: Icons.warning_amber_rounded, label: 'Over Refunded');

    return AstraCard(
      padding: const EdgeInsets.symmetric(horizontal: 14, vertical: 10),
      child: Column(
        children: [
          _sumRow('Refund Total', Money.of(draft.total), p.primary, p),
          Divider(height: 14, color: p.hairline),
          _sumRow('Refunding', Money.of(draft.refundAmount), p.ink, p),
          Divider(height: 14, color: p.hairline),
          Row(
            mainAxisAlignment: MainAxisAlignment.spaceBetween,
            children: [
              Row(
                children: [
                  Icon(status.icon, size: 14, color: status.color),
                  const SizedBox(width: 6),
                  Text(status.label, style: ui(size: 12, weight: FontWeight.w800, color: status.color)),
                ],
              ),
              Text(Money.of(bal.abs()), style: ui(size: 14, weight: FontWeight.w800, color: status.color)),
            ],
          ),
        ],
      ),
    );
  }

  Widget _sumRow(String label, String value, Color valueColor, AstraPalette p) => Row(
        mainAxisAlignment: MainAxisAlignment.spaceBetween,
        children: [
          Text(label, style: ui(size: 12, weight: FontWeight.w600, color: p.textSecondary)),
          Text(value, style: ui(size: 14, weight: FontWeight.w800, color: valueColor)),
        ],
      );

  // ---- "Ready to refund" status card ----

  Widget _statusCard(ReturnDraftCubit draft) {
    final p = context.astra;
    final bal = draft.balance;
    final settled = bal.abs() < 0.001;

    final (Color tint, Color color, IconData icon, String title, String desc) = settled
        ? (p.successTint, AstraPalette.success, Icons.check_circle, 'Ready to Refund', 'The full amount will be returned to the customer')
        : bal > 0
            ? (p.warnTint, p.warnText, Icons.timelapse, 'Partial Refund', 'Part of the return amount is still pending')
            : (p.dangerTint, AstraPalette.danger, Icons.warning_amber_rounded, 'Over Refunded', 'The refund exceeds the return total');

    return GestureDetector(
      onTap: settled && !_busy ? _refund : null,
      child: Container(
        width: double.infinity,
        padding: const EdgeInsets.symmetric(vertical: 14, horizontal: 14),
        decoration: BoxDecoration(
          color: tint,
          borderRadius: BorderRadius.circular(16),
          border: Border.all(color: color.withValues(alpha: 0.35)),
        ),
        child: Column(
          children: [
            Container(
              width: 30,
              height: 30,
              decoration: BoxDecoration(color: color, shape: BoxShape.circle),
              child: Icon(icon, size: 16, color: Colors.white),
            ),
            const SizedBox(height: 7),
            Text(title, style: ui(size: 13, weight: FontWeight.w800, color: color)),
            const SizedBox(height: 3),
            Text(desc, textAlign: TextAlign.center, style: ui(size: 11, weight: FontWeight.w600, color: color.withValues(alpha: 0.85))),
            if (settled && !_busy) ...[
              const SizedBox(height: 5),
              Text('Tap to confirm', style: ui(size: 10.5, weight: FontWeight.w700, color: color.withValues(alpha: 0.7))),
            ],
          ],
        ),
      ),
    );
  }

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

  Widget _dashed(AstraPalette p) => Padding(
        padding: const EdgeInsets.symmetric(vertical: 11),
        child: LayoutBuilder(
          builder: (context, c) {
            final count = (c.maxWidth / 7).floor();
            return Row(
              mainAxisAlignment: MainAxisAlignment.spaceBetween,
              children: List.generate(count, (_) => Container(width: 3, height: 1, color: p.hairline)),
            );
          },
        ),
      );
}
