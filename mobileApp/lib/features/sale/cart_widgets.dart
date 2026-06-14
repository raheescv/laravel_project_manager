import 'package:flutter/material.dart';
import 'package:flutter/services.dart';
import 'package:provider/provider.dart';

import '../../core/formatters.dart';
import '../../core/icons.dart';
import '../../state/cart_controller.dart';
import '../../theme/palette.dart';
import '../../theme/theme.dart';
import '../../widgets/astra_widgets.dart';
import 'edit_line_sheet.dart';

/// Shared cart building blocks used by both the full-screen Cart (phone) and the
/// persistent cart panel (tablet split view).

Widget cartLineCard(BuildContext context, CartLine line) {
  final p = context.astra;
  final cart = context.read<CartController>();
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
                            decoration: BoxDecoration(color: AstraPalette.warnTint, borderRadius: BorderRadius.circular(10)),
                            child: Text(line.discountLabel, style: ui(size: 9, weight: FontWeight.w800, color: p.goldText)),
                          ),
                        ],
                      ],
                    ),
                  ],
                ),
              ),
              Column(
                crossAxisAlignment: CrossAxisAlignment.end,
                children: [
                  Text(Money.of(line.total), style: serif(size: 16, color: p.goldText)),
                  Text('${Money.of(line.unitPrice)} / unit',
                      style: ui(size: 9.5, weight: FontWeight.w600, color: p.textMuted)),
                ],
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
                qty: line.qty.toStringAsFixed(line.qty % 1 == 0 ? 0 : 2),
                onMinus: () => cart.changeQty(line, -1),
                onPlus: () => cart.changeQty(line, 1),
              ),
              GestureDetector(
                onTap: () => showEditLineSheet(context, line),
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

/// Inline-editable order discount (flat $ amount; edits right in the row, no popup).
class OrderDiscountRow extends StatefulWidget {
  const OrderDiscountRow({super.key, required this.cart});
  final CartController cart;
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
    return AstraCard(
      radius: 15,
      padding: const EdgeInsets.symmetric(horizontal: 13, vertical: 10),
      child: Row(
        children: [
          IconChip(icon: Icons.sell_outlined, size: 30, radius: 9, bg: AstraPalette.warnTint, fg: p.goldText),
          const SizedBox(width: 10),
          Expanded(child: Text('Order discount', style: ui(size: 12.5, weight: FontWeight.w700, color: p.ink))),
          Text(r'$', style: ui(size: 13, weight: FontWeight.w800, color: p.goldText)),
          const SizedBox(width: 2),
          SizedBox(
            width: 78,
            child: KeyboardDoneField(
              focusNode: _focus,
              child: TextField(
                controller: _ctl,
                focusNode: _focus,
                textAlign: TextAlign.right,
                keyboardType: const TextInputType.numberWithOptions(decimal: true),
                inputFormatters: [FilteringTextInputFormatter.allow(RegExp(r'[0-9.]'))],
                style: ui(size: 13, weight: FontWeight.w800, color: p.goldText),
                cursorColor: p.primary,
                decoration: InputDecoration(
                  isCollapsed: true,
                  border: InputBorder.none,
                  hintText: '0.00',
                  hintStyle: ui(size: 13, weight: FontWeight.w700, color: p.textMuted),
                ),
                onChanged: (v) => widget.cart.setOrderDiscount(double.tryParse(v) ?? 0),
              ),
            ),
          ),
        ],
      ),
    );
  }
}

Widget cartSummaryCard(BuildContext context, CartController cart, {required VoidCallback onCharge}) {
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
        sumRow('Subtotal', Money.of(cart.subtotal), p.textSecondary),
        if (cart.totalDiscount > 0) sumRow('Discount', '− ${Money.of(cart.totalDiscount)}', p.goldText),
        sumRow('Tax', Money.of(cart.taxTotal), p.textSecondary),
        Padding(padding: const EdgeInsets.symmetric(vertical: 9), child: Container(height: 1, color: p.hairline)),
        Row(
          children: [
            Expanded(
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  Text('Total', style: ui(size: 10.5, weight: FontWeight.w600, color: p.textMuted)),
                  Text(Money.of(cart.total), style: serif(size: 23, color: p.ink)),
                ],
              ),
            ),
            AstraButton(label: 'Charge', icon: Icons.arrow_forward, expand: false, onTap: onCharge),
          ],
        ),
      ],
    ),
  );
}

