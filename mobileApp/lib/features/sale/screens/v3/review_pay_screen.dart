import 'package:flutter/material.dart';
import 'package:invo/features/sale/domain/repository/sale_repository.dart';
import 'package:invo/shared/domain/repository/lookup_repository.dart';
import 'package:go_router/go_router.dart';
import 'package:provider/provider.dart';

import 'package:invo/shared/utils/router/http_utils/common_exception.dart';
import 'package:invo/shared/domain/constants/global_variables.dart';
import 'package:invo/shared/domain/helpers/formatters.dart';
import 'package:invo/shared/domain/helpers/responsive.dart';
import 'package:invo/shared/domain/models/index.dart';
import 'package:invo/features/sale/logic/cart_cubit/cart_cubit.dart';
import 'package:invo/shared/utils/components/theme/index.dart';
import 'package:invo/shared/widgets/astra_widgets.dart';
import 'package:invo/shared/widgets/invo_logo.dart';
import 'package:invo/features/sale/widgets/v3/custom_payment_sheet.dart';

class ReviewPayScreen extends StatefulWidget {
  const ReviewPayScreen({super.key});
  @override
  State<ReviewPayScreen> createState() => _ReviewPayScreenState();
}

class _ReviewPayScreenState extends State<ReviewPayScreen> {
  bool _busy = false;
  List<PaymentMethod> _methods = [];

  @override
  void initState() {
    super.initState();
    _loadMethods();
  }

  /// Best-effort fetch of the configured payment methods for the custom split.
  /// A failure here is non-fatal — Cash/Card/Credit still work without it.
  Future<void> _loadMethods() async {
    try {
      final methods = await serviceLocator<LookupRepository>().paymentMethods();
      if (mounted) setState(() => _methods = methods);
    } catch (_) {
      // Leave _methods empty; the custom sheet surfaces the "none configured" state.
    }
  }

  Future<void> _charge() async {
    final cart = context.read<CartCubit>();
    final service = serviceLocator<SaleRepository>();
    final editingId = cart.editingSaleId;
    setState(() => _busy = true);
    try {
      final sale = editingId == null
          ? await service.createSale(cart.toPayload())
          : await service.updateSale(editingId, cart.toPayload());
      cart.clear();
      if (mounted) context.pushReplacement('/invoice', extra: sale);
    } on ApiException catch (e) {
      _error(e.message);
    } catch (e) {
      _error(editingId == null
          ? 'Could not save the sale. Please try again.'
          : 'Could not update the sale. Please try again.');
    }
    if (mounted) setState(() => _busy = false);
  }

  Future<void> _openCustom() async {
    final cart = context.read<CartCubit>();
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
      total: cart.total,
      methods: _methods,
      initial: cart.customPayments,
    );
    if (result != null && result.isNotEmpty) {
      cart.setCustomPayments(result);
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
    final cart = context.watch<CartCubit>();
    final settled = cart.balance.abs() < 0.001;

    return Scaffold(
      body: AstraBackground(
        child: Column(
          children: [
            EmeraldHeader(
              leading: HeaderIconButton(icon: Icons.chevron_left, onTap: () => context.pop()),
              title: 'Review & Pay',
            ),
            Expanded(
              child: MaxWidthBox(
                maxWidth: 560,
                child: ListView(
                  padding: const EdgeInsets.fromLTRB(16, 13, 16, 130),
                  children: [
                    _receiptCard(cart),
                    const SizedBox(height: 16),
                    _tipSelector(cart),
                    const SizedBox(height: 16),
                    _paymentSection(cart),
                    const SizedBox(height: 16),
                    _summaryCard(cart),
                    const SizedBox(height: 12),
                    _statusCard(cart),
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
              label: cart.isEditing
                  ? 'Update ${Money.of(cart.total)}'
                  : settled
                      ? 'Charge ${Money.of(cart.total)}'
                      : 'Submit Anyway',
              gold: true,
              busy: _busy,
              onTap: cart.isEmpty ? null : _charge,
            ),
          ),
        ),
      ),
    );
  }

  Widget _receiptCard(CartCubit cart) {
    final p = context.astra;
    return AstraCard(
      child: Column(
        children: [
          Column(
            children: [
              const InvoLogomark(height: 28),
              const SizedBox(height: 6),
              Text('INVO', style: ui(size: 11, weight: FontWeight.w700, color: p.ink, letterSpacing: 4)),
              const SizedBox(height: 3),
              Text('${cart.customerName} · ${cart.stylistName.isEmpty ? 'Me' : cart.stylistName}',
                  style: ui(size: 10, weight: FontWeight.w600, color: p.textMuted)),
            ],
          ),
          _dashed(p),
          for (final l in cart.lines)
            Padding(
              padding: const EdgeInsets.only(bottom: 9),
              child: Row(
                children: [
                  Container(
                    padding: const EdgeInsets.symmetric(horizontal: 6, vertical: 1),
                    decoration: BoxDecoration(color: p.tint, borderRadius: BorderRadius.circular(6)),
                    child: Text('${l.qty.toStringAsFixed(l.qty % 1 == 0 ? 0 : 1)}×',
                        style: ui(size: 10.5, weight: FontWeight.w800, color: p.primaryDark)),
                  ),
                  const SizedBox(width: 8),
                  Expanded(child: Text(l.name, style: ui(size: 12, weight: FontWeight.w600, color: p.ink))),
                  Text(Money.of(l.total), style: ui(size: 12, weight: FontWeight.w800, color: p.ink)),
                ],
              ),
            ),
          _dashed(p),
          _row('Subtotal', Money.of(cart.subtotal), p.textSecondary),
          if (cart.totalDiscount > 0) _row('Discount', '− ${Money.of(cart.totalDiscount)}', p.goldText),
          _row('Tax', Money.of(cart.taxTotal), p.textSecondary),
          if (cart.tipAmount > 0) _row('Tip (${cart.tipPercent.toStringAsFixed(0)}%)', Money.of(cart.tipAmount), p.textSecondary),
          const SizedBox(height: 4),
          Row(
            mainAxisAlignment: MainAxisAlignment.spaceBetween,
            children: [
              Text('Total', style: ui(size: 12.5, weight: FontWeight.w800, color: p.ink)),
              Text(Money.of(cart.total), style: serif(size: 19, color: p.ink)),
            ],
          ),
        ],
      ),
    );
  }

  Widget _tipSelector(CartCubit cart) {
    final p = context.astra;
    final tips = [0.0, 10.0, 15.0, 20.0];
    return Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        Text('ADD A TIP', style: ui(size: 10, weight: FontWeight.w800, color: p.textMuted, letterSpacing: 0.8)),
        const SizedBox(height: 7),
        Row(
          children: [
            for (final tip in tips) ...[
              Expanded(
                child: GestureDetector(
                  onTap: () => cart.setTip(tip),
                  child: Container(
                    padding: const EdgeInsets.symmetric(vertical: 11),
                    alignment: Alignment.center,
                    decoration: BoxDecoration(
                      gradient: cart.tipPercent == tip ? p.primaryGradient : null,
                      color: cart.tipPercent == tip ? null : p.card,
                      borderRadius: BorderRadius.circular(11),
                      boxShadow: context.astraTheme.softShadow,
                    ),
                    child: Text(tip == 0 ? 'None' : '${tip.toStringAsFixed(0)}%',
                        style: ui(size: 11.5, weight: FontWeight.w800, color: cart.tipPercent == tip ? Colors.white : p.textSecondary)),
                  ),
                ),
              ),
              if (tip != tips.last) const SizedBox(width: 7),
            ],
          ],
        ),
      ],
    );
  }

  // ---- Payment method (Cash / Card / Credit / Custom) + WhatsApp toggle ----

  Widget _paymentSection(CartCubit cart) {
    final p = context.astra;
    return Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        Row(
          children: [
            Expanded(
              child: Text('PAYMENT METHOD',
                  style: ui(size: 10, weight: FontWeight.w800, color: p.textMuted, letterSpacing: 0.8)),
            ),
            // _whatsappToggle(cart),
          ],
        ),
        const SizedBox(height: 8),
        Row(
          children: [
            _method(cart, PayMode.cash, Icons.payments_outlined),
            const SizedBox(width: 8),
            _method(cart, PayMode.card, Icons.credit_card),
            const SizedBox(width: 8),
            _method(cart, PayMode.credit, Icons.description_outlined),
            const SizedBox(width: 8),
            _method(cart, PayMode.custom, Icons.tune),
          ],
        ),
      ],
    );
  }

  Widget _method(CartCubit cart, PayMode mode, IconData icon) {
    final p = context.astra;
    final active = cart.payMode == mode;
    final isCustom = mode == PayMode.custom;
    final count = cart.customPayments.length;

    return Expanded(
      child: GestureDetector(
        onTap: () => isCustom ? _openCustom() : cart.setPayMode(mode),
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
              if (active)
                Positioned(
                  top: 4,
                  right: 5,
                  child: Icon(Icons.check_circle, size: 13, color: p.accent),
                ),
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

  // ---- Transaction summary (Grand Total / Paid / Balance) ----

  Widget _summaryCard(CartCubit cart) {
    final p = context.astra;
    final bal = cart.balance;
    final ({Color color, IconData icon, String label}) status = bal.abs() < 0.001
        ? (color: AstraPalette.success, icon: Icons.check_circle, label: 'Balance')
        : bal > 0
            ? (color: AstraPalette.danger, icon: Icons.warning_amber_rounded, label: 'Remaining Balance')
            : (color: const Color(0xFFE08A2B), icon: Icons.south, label: 'Overpaid Amount');

    return AstraCard(
      padding: const EdgeInsets.symmetric(horizontal: 14, vertical: 10),
      child: Column(
        children: [
          _sumRow('Grand Total', Money.of(cart.total), p.primary, p),
          Divider(height: 14, color: p.hairline),
          _sumRow('Paid Amount', Money.of(cart.paidAmount), p.ink, p),
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

  // ---- "Ready to submit" status card ----

  Widget _statusCard(CartCubit cart) {
    final p = context.astra;
    final bal = cart.balance;
    final settled = bal.abs() < 0.001;

    final (Color tint, Color color, IconData icon, String title, String desc) = settled
        ? (p.successTint, AstraPalette.success, Icons.check_circle, 'Ready to Submit', 'Transaction is fully paid and ready to submit')
        : bal > 0
            ? (p.warnTint, p.warnText, Icons.warning_amber_rounded, 'Partial Payment', 'Transaction has a remaining balance')
            : (p.tint, p.primaryDark, Icons.south, 'Overpaid Transaction', 'Transaction amount exceeds payment');

    return GestureDetector(
      onTap: settled && !_busy ? _charge : null,
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
              Text('Tap to submit', style: ui(size: 10.5, weight: FontWeight.w700, color: color.withValues(alpha: 0.7))),
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
