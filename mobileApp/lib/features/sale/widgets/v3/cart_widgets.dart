import 'dart:async';

import 'package:flutter/material.dart';
import 'package:flutter/services.dart';
import 'package:provider/provider.dart';

import 'package:invo/shared/domain/helpers/formatters.dart';
import 'package:invo/shared/domain/helpers/icons.dart';
import 'package:invo/features/sale/logic/cart_cubit/cart_cubit.dart';
import 'package:invo/shared/utils/components/theme/index.dart';
import 'package:invo/shared/widgets/astra_widgets.dart';
import 'package:invo/features/sale/widgets/v3/edit_line_sheet.dart';
import 'package:invo/shared/widgets/qty_input_sheet.dart';

/// Shared cart building blocks used by both the full-screen Cart (phone) and the
/// persistent cart panel (tablet split view).

Widget cartLineCard(BuildContext context, CartLine line) {
  final p = context.astra;
  final cart = context.read<CartCubit>();
  return Padding(
    padding: const EdgeInsets.only(bottom: 11),
    child: AstraCard(
      radius: 18,
      padding: const EdgeInsets.fromLTRB(13, 13, 13, 11),
      child: Column(
        children: [
          Row(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              ProductThumb(url: line.thumbnail, fallbackIcon: iconForName('${line.type} ${line.name}')),
              const SizedBox(width: 11),
              Expanded(
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    Text(line.name, style: serif(size: 15, color: p.ink)),
                    const SizedBox(height: 5),
                    Row(
                      children: [
                        Container(
                          width: 16,
                          height: 16,
                          decoration: BoxDecoration(gradient: p.primaryGradient, shape: BoxShape.circle),
                          alignment: Alignment.center,
                          child: Text(
                            line.employeeName.isEmpty ? '?' : line.employeeName[0].toUpperCase(),
                            style: ui(size: 8, weight: FontWeight.w700, color: Colors.white),
                          ),
                        ),
                        const SizedBox(width: 6),
                        Flexible(
                          child: Text(line.employeeName.isEmpty ? 'Unassigned' : line.employeeName,
                              maxLines: 1,
                              overflow: TextOverflow.ellipsis,
                              style: ui(size: 10.5, weight: FontWeight.w600, color: p.textSecondary)),
                        ),
                        if (line.discountLabel.isNotEmpty) ...[
                          const SizedBox(width: 6),
                          Container(
                            padding: const EdgeInsets.symmetric(horizontal: 7, vertical: 2),
                            decoration: BoxDecoration(color: p.warnTint, borderRadius: BorderRadius.circular(10)),
                            child: Text(line.discountLabel, style: ui(size: 9, weight: FontWeight.w800, color: p.goldText)),
                          ),
                        ],
                      ],
                    ),
                  ],
                ),
              ),
              const SizedBox(width: 8),
              Flexible(
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.end,
                  children: [
                    FittedBox(
                      fit: BoxFit.scaleDown,
                      alignment: Alignment.centerRight,
                      child: Text(Money.of(line.total), style: serif(size: 16, color: p.goldText)),
                    ),
                    Text('${Money.of(line.unitPrice)} / unit',
                        maxLines: 1,
                        overflow: TextOverflow.ellipsis,
                        style: ui(size: 9.5, weight: FontWeight.w600, color: p.textMuted)),
                  ],
                ),
              ),
            ],
          ),
          const SizedBox(height: 11),
          Container(height: 1, color: p.hairline),
          const SizedBox(height: 11),
          Row(
            mainAxisAlignment: MainAxisAlignment.spaceBetween,
            children: [
              QtyStepper(
                qty: qtyLabel(line.qty),
                onMinus: () {
                  HapticFeedback.selectionClick();
                  cart.changeQty(line, -cart.defaultQty);
                },
                onPlus: () {
                  HapticFeedback.selectionClick();
                  cart.changeQty(line, cart.defaultQty);
                },
                onTapValue: () async {
                  unawaited(HapticFeedback.selectionClick());
                  final v = await showQtyInputSheet(
                    context,
                    current: line.qty,
                    title: line.name,
                    subtitle: 'Enter quantity',
                  );
                  if (v != null) cart.setQty(line, v);
                },
              ),
              GestureDetector(
                onTap: () {
                  HapticFeedback.selectionClick();
                  showEditLineSheet(context, line);
                },
                child: Container(
                  padding: const EdgeInsets.symmetric(horizontal: 14, vertical: 7),
                  decoration: BoxDecoration(
                    borderRadius: BorderRadius.circular(11),
                    border: Border.all(color: p.goldText.withValues(alpha: 0.5), width: 1.5),
                  ),
                  child: Row(
                    children: [
                      Icon(Icons.edit_outlined, size: 13, color: p.goldText),
                      const SizedBox(width: 7),
                      Text('Edit details', style: ui(size: 12, weight: FontWeight.w700, color: p.goldText)),
                    ],
                  ),
                ),
              ),
            ],
          ),
        ],
      ),
    ),
  );
}

/// Order discount card: a big %/amount switch beside a roomy input (unit shown
/// inside it), and the live amount saved in the header — edits right in the card, no popup. [embedded] drops the card chrome
/// and the header amount so it can sit inside [cartSummaryCard], whose own
/// Discount line already shows what's saved.
class OrderDiscountRow extends StatefulWidget {
  const OrderDiscountRow({super.key, required this.cart, this.embedded = false});
  final CartCubit cart;
  final bool embedded;
  @override
  State<OrderDiscountRow> createState() => _OrderDiscountRowState();
}

class _OrderDiscountRowState extends State<OrderDiscountRow> {
  late final TextEditingController _ctl = TextEditingController(text: _fmt(widget.cart.orderDiscount));
  final FocusNode _focus = FocusNode();

  static String _fmt(double v) => v == 0 ? '' : (v % 1 == 0 ? v.toStringAsFixed(0) : v.toString());

  @override
  void dispose() {
    _ctl.dispose();
    _focus.dispose();
    super.dispose();
  }

  @override
  Widget build(BuildContext context) {
    final p = context.astra;
    final cart = context.watch<CartCubit>();
    final isPercent = cart.orderDiscountIsPercent;
    final saved = cart.orderDiscountAmount;
    final unit = isPercent ? '%' : Money.symbol.trim();
    final body = Column(
        crossAxisAlignment: CrossAxisAlignment.stretch,
        children: [
          Row(
            children: [
              IconChip(icon: Icons.sell_outlined, size: 32, radius: 10, iconSize: 18, bg: p.warnTint, fg: p.goldText),
              const SizedBox(width: 10),
              Expanded(child: Text('Order discount', style: ui(size: 13, weight: FontWeight.w700, color: p.ink))),
              if (!widget.embedded)
                saved > 0
                    ? Text('− ${Money.of(saved)}', style: ui(size: 13, weight: FontWeight.w800, color: p.goldText))
                    : Text('None', style: ui(size: 11.5, weight: FontWeight.w600, color: p.textMuted)),
            ],
          ),
          const SizedBox(height: 11),
          Row(
            children: [
              Container(
                height: 40,
                padding: const EdgeInsets.all(3),
                decoration: BoxDecoration(
                  color: p.isDark ? Colors.white12 : const Color(0xFFF3EFE6),
                  borderRadius: BorderRadius.circular(11),
                ),
                child: Row(
                  mainAxisSize: MainAxisSize.min,
                  children: [_toggle('%', true, isPercent), _toggle(Money.symbol.trim(), false, !isPercent)],
                ),
              ),
              const SizedBox(width: 10),
              Expanded(child: _input(p, isPercent, unit)),
            ],
          ),
        ],
    );
    if (widget.embedded) return body;
    return AstraCard(radius: 15, padding: const EdgeInsets.fromLTRB(13, 11, 13, 12), child: body);
  }

  Widget _input(AstraPalette p, bool isPercent, String unit) {
    final focused = _focus.hasFocus;
    final unitStyle = ui(size: 13, weight: FontWeight.w800, color: p.goldText);
    return GestureDetector(
      onTap: _focus.requestFocus,
      child: AnimatedContainer(
        duration: const Duration(milliseconds: 150),
        height: 40,
        padding: const EdgeInsets.symmetric(horizontal: 12),
        decoration: BoxDecoration(
          color: p.isDark ? Colors.white.withValues(alpha: 0.04) : Colors.white,
          borderRadius: BorderRadius.circular(11),
          border: Border.all(color: focused ? p.goldText : p.hairline, width: focused ? 1.5 : 1),
        ),
        child: Row(
          children: [
            if (!isPercent) ...[Text(unit, style: unitStyle), const SizedBox(width: 6)],
            Expanded(
              child: KeyboardDoneField(
                focusNode: _focus,
                child: TextField(
                  controller: _ctl,
                  focusNode: _focus,
                  textAlign: TextAlign.right,
                  keyboardType: const TextInputType.numberWithOptions(decimal: true),
                  inputFormatters: [FilteringTextInputFormatter.allow(RegExp(r'[0-9.]'))],
                  style: ui(size: 15, weight: FontWeight.w800, color: p.ink),
                  cursorColor: p.primary,
                  decoration: InputDecoration(
                    isCollapsed: true,
                    border: InputBorder.none,
                    hintText: isPercent ? '0' : '0.00',
                    hintStyle: ui(size: 15, weight: FontWeight.w700, color: p.textMuted),
                  ),
                  onTap: () => setState(() {}),
                  onTapOutside: (_) => setState(() {}),
                  onChanged: _onTyped,
                ),
              ),
            ),
            if (isPercent) ...[const SizedBox(width: 4), Text(unit, style: unitStyle)],
          ],
        ),
      ),
    );
  }

  /// A percentage can't pass 100 — cap it in the field rather than charging
  /// a negative total.
  void _onTyped(String v) {
    var value = double.tryParse(v) ?? 0;
    if (widget.cart.orderDiscountIsPercent && value > 100) {
      value = 100;
      _ctl.value = TextEditingValue(text: '100', selection: const TextSelection.collapsed(offset: 3));
    }
    widget.cart.setOrderDiscount(value);
  }

  /// Switch the discount type. Changing %/amount resets the value to 0 (and
  /// clears the field) so a flat amount isn't silently reinterpreted as a
  /// percentage.
  void _setType(bool percent) {
    if (percent == widget.cart.orderDiscountIsPercent) return;
    widget.cart.setOrderDiscountIsPercent(percent);
    widget.cart.setOrderDiscount(0);
    _ctl.clear();
  }

  Widget _toggle(String label, bool percent, bool active) {
    final p = context.astra;
    return GestureDetector(
      onTap: () => _setType(percent),
      child: AnimatedContainer(
        duration: const Duration(milliseconds: 150),
        constraints: const BoxConstraints(minWidth: 42),
        alignment: Alignment.center,
        padding: const EdgeInsets.symmetric(horizontal: 10),
        decoration: BoxDecoration(
          color: active ? p.cardSolid : Colors.transparent,
          borderRadius: BorderRadius.circular(8),
          boxShadow: active ? [BoxShadow(color: Colors.black.withValues(alpha: 0.08), blurRadius: 4, offset: const Offset(0, 1))] : null,
        ),
        child: Text(label, style: ui(size: 13, weight: FontWeight.w800, color: active ? p.ink : p.textMuted)),
      ),
    );
  }
}

/// Totals + Charge. Pass [withDiscount] to fold the order discount into the
/// top of the same card, set apart on its own tinted panel.
Widget cartSummaryCard(BuildContext context, CartCubit cart, {required VoidCallback onCharge, bool withDiscount = false}) {
  final p = context.astra;
  final t = context.astraTheme;
  Widget sumRow(String label, String value, Color color) => Padding(
        padding: const EdgeInsets.only(bottom: 6),
        child: Row(
          mainAxisAlignment: MainAxisAlignment.spaceBetween,
          children: [
            Text(label, style: ui(size: 11.5, weight: FontWeight.w600, color: color)),
            Text(value, style: ui(size: 11.5, weight: FontWeight.w700, color: color)),
          ],
        ),
      );
  return Container(
    padding: const EdgeInsets.fromLTRB(15, 14, 15, 13),
    decoration: BoxDecoration(color: p.card, borderRadius: BorderRadius.circular(22), boxShadow: t.cardShadow),
    child: Column(
      mainAxisSize: MainAxisSize.min,
      children: [
        if (withDiscount) ...[
          Container(
            padding: const EdgeInsets.fromLTRB(11, 10, 11, 11),
            decoration: BoxDecoration(
              color: p.goldText.withValues(alpha: p.isDark ? 0.10 : 0.06),
              borderRadius: BorderRadius.circular(14),
              border: Border.all(color: p.goldText.withValues(alpha: 0.18)),
            ),
            child: OrderDiscountRow(cart: cart, embedded: true),
          ),
          Padding(padding: const EdgeInsets.only(top: 12, bottom: 10), child: Container(height: 1, color: p.hairline)),
        ],
        sumRow('Subtotal', Money.of(cart.subtotal), p.textSecondary),
        if (cart.totalDiscount > 0) sumRow('Discount', '− ${Money.of(cart.totalDiscount)}', p.goldText),
        // A zero tax line is a row that says nothing; the ones that do carry
        // tax still show it. Same rule the discount row already follows.
        if (cart.taxTotal > 0) sumRow('Tax', Money.of(cart.taxTotal), p.textSecondary),
        if (cart.roundOff != 0) sumRow('Round Off', Money.of(cart.roundOff), p.textSecondary),
        Padding(padding: const EdgeInsets.symmetric(vertical: 9), child: Container(height: 1, color: p.hairline)),
        Row(
          children: [
            Expanded(
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  Text('Total', style: ui(size: 10.5, weight: FontWeight.w600, color: p.textMuted)),
                  // Scales down rather than running under the Charge button —
                  // the tablet rail gives this card ~260pt, and a five-figure
                  // total in a three-letter currency needs most of it.
                  FittedBox(
                    fit: BoxFit.scaleDown,
                    alignment: Alignment.centerLeft,
                    child: Text(Money.of(cart.total), style: serif(size: 23, color: p.ink)),
                  ),
                ],
              ),
            ),
            AstraButton(
              label: 'Charge',
              icon: Icons.arrow_forward,
              expand: false,
              onTap: () {
                HapticFeedback.lightImpact();
                onCharge();
              },
            ),
          ],
        ),
      ],
    ),
  );
}

