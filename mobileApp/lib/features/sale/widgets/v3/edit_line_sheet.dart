import 'package:flutter/material.dart';
import 'package:flutter/services.dart';
import 'package:provider/provider.dart';

import 'package:invo/shared/domain/helpers/formatters.dart';
import 'package:invo/features/auth/logic/auth_cubit/auth_cubit.dart';
import 'package:invo/features/sale/logic/cart_cubit/cart_cubit.dart';
import 'package:invo/features/sale/logic/stylist_cubit/stylist_cubit.dart';
import 'package:invo/shared/utils/components/theme/index.dart';
import 'package:invo/shared/widgets/astra_widgets.dart';
import 'package:invo/shared/widgets/qty_input_sheet.dart';
import 'package:invo/features/sale/widgets/v3/stylist_sheet.dart';

/// Bottom sheet to edit a whole line: stylist, unit price, tax, discount, qty.
Future<void> showEditLineSheet(BuildContext context, CartLine line) {
  return showModalBottomSheet(
    context: context,
    isScrollControlled: true,
    backgroundColor: Colors.transparent,
    builder: (_) => _EditLineSheet(line: line),
  );
}

class _EditLineSheet extends StatefulWidget {
  const _EditLineSheet({required this.line});
  final CartLine line;
  @override
  State<_EditLineSheet> createState() => _EditLineSheetState();
}

class _EditLineSheetState extends State<_EditLineSheet> {
  late double _unitPrice = widget.line.unitPrice;
  late double _tax = widget.line.taxPercent;
  late double _discount = widget.line.discountValue;
  late bool _isPercent = widget.line.discountIsPercent;
  late double _qty = widget.line.qty;
  late int? _employeeId = widget.line.employeeId;
  late String _employeeName = widget.line.employeeName;
  String _employeePhotoUrl = '';

  @override
  void initState() {
    super.initState();
    _employeePhotoUrl = _resolvePhoto(widget.line.employeeId);
  }

  /// Look up the assigned stylist's avatar from the (already-loaded) stylist
  /// list — the cart line only carries the id/name, not the photo.
  String _resolvePhoto(int? id) {
    if (id == null) return '';
    for (final e in context.read<StylistCubit>().all) {
      if (e.id == id) return e.photoUrl;
    }
    return '';
  }

  late final TextEditingController _unitCtl = TextEditingController(text: _fmt(_unitPrice));
  late final TextEditingController _taxCtl = TextEditingController(text: _fmt(_tax));
  late final TextEditingController _discCtl = TextEditingController(text: _fmt(_discount));

  @override
  void dispose() {
    _unitCtl.dispose();
    _taxCtl.dispose();
    _discCtl.dispose();
    super.dispose();
  }

  static String _fmt(double v) => v == 0 ? '' : (v % 1 == 0 ? v.toStringAsFixed(0) : v.toString());

  Future<void> _pickLineStylist() async {
    final chosen = await pickStylist(context, selectedId: _employeeId);
    if (chosen == null || !mounted) return;
    setState(() {
      _employeeId = chosen.id;
      _employeeName = chosen.name;
      _employeePhotoUrl = chosen.photoUrl;
    });
  }

  double get _base => _unitPrice * _qty;
  double get _discountAmount => _isPercent ? _base * _discount / 100 : _discount;
  double get _taxable => (_base - _discountAmount).clamp(0, double.infinity);
  double get _total => _taxable + _taxable * _tax / 100;

  @override
  Widget build(BuildContext context) {
    final p = context.astra;
    final cfg = context.read<AuthCubit>().config;
    return Padding(
      padding: EdgeInsets.only(bottom: MediaQuery.of(context).viewInsets.bottom),
      child: Container(
        decoration: BoxDecoration(color: p.sheet, borderRadius: const BorderRadius.vertical(top: Radius.circular(30))),
        padding: const EdgeInsets.fromLTRB(20, 14, 20, 22),
        // Scrollable so the full edit form never overflows vertically when the
        // keyboard is up on a small phone or in landscape.
        child: SingleChildScrollView(
          child: Column(
          mainAxisSize: MainAxisSize.min,
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Center(
              child: Container(width: 42, height: 5,
                  decoration: BoxDecoration(color: p.hairline, borderRadius: BorderRadius.circular(3))),
            ),
            const SizedBox(height: 14),
            Row(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Expanded(
                  child: Column(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: [
                      const SectionLabel('Edit service'),
                      const SizedBox(height: 3),
                      Text(widget.line.name, style: serif(size: 23, color: p.ink)),
                      Text('${widget.line.code} · ${widget.line.type}',
                          style: ui(size: 11, weight: FontWeight.w600, color: p.textMuted)),
                    ],
                  ),
                ),
                HeaderIconButtonLight(icon: Icons.close, onTap: () => Navigator.pop(context)),
              ],
            ),
            const SizedBox(height: 15),
            // stylist (tap to reassign this line)
            AstraCard(
              radius: 15,
              padding: const EdgeInsets.all(11),
              onTap: _pickLineStylist,
              child: Row(
                children: [
                  ProfileAvatar(
                    letter: _employeeName.isEmpty ? '?' : _employeeName[0],
                    imageUrl: _employeePhotoUrl.isNotEmpty ? cfg.assetUrl(_employeePhotoUrl) : null,
                    headers: cfg.assetHeaders,
                    size: 34,
                  ),
                  const SizedBox(width: 11),
                  Expanded(
                    child: Column(
                      crossAxisAlignment: CrossAxisAlignment.start,
                      children: [
                        Text('STYLIST', style: ui(size: 9.5, weight: FontWeight.w800, color: p.textMuted, letterSpacing: 0.6)),
                        Text(_employeeName.isEmpty ? 'Unassigned' : _employeeName,
                            style: ui(size: 13.5, weight: FontWeight.w700, color: p.ink)),
                      ],
                    ),
                  ),
                  Icon(Icons.swap_horiz, size: 18, color: p.textMuted),
                ],
              ),
            ),
            const SizedBox(height: 11),
            Row(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Expanded(child: _editField(label: 'Unit price', controller: _unitCtl, prefix: r'$', onChanged: (v) => setState(() => _unitPrice = v))),
                const SizedBox(width: 11),
                Expanded(child: _editField(label: 'Tax', controller: _taxCtl, suffix: '%', onChanged: (v) => setState(() => _tax = v))),
              ],
            ),
            const SizedBox(height: 11),
            IntrinsicHeight(
              child: Row(
                crossAxisAlignment: CrossAxisAlignment.stretch,
                children: [
                  Expanded(child: _discountField()),
                  const SizedBox(width: 11),
                  Expanded(child: _qtyCard()),
                ],
              ),
            ),
            const SizedBox(height: 13),
            _lineTotal(),
            const SizedBox(height: 13),
            Row(
              children: [
                AstraButton(label: 'Cancel', gold: false, expand: false, onTap: () => Navigator.pop(context)),
                const SizedBox(width: 11),
                Expanded(
                  child: AstraButton(
                    label: 'Save changes',
                    onTap: () {
                      context.read<CartCubit>().updateLine(
                            widget.line,
                            unitPrice: _unitPrice,
                            qty: _qty,
                            discountValue: _discount,
                            discountIsPercent: _isPercent,
                            taxPercent: _tax,
                            employeeId: _employeeId,
                            employeeName: _employeeName,
                          );
                      Navigator.pop(context);
                    },
                  ),
                ),
              ],
            ),
          ],
          ),
        ),
      ),
    );
  }

  /// Inline numeric field — edits directly in the sheet (no popup).
  Widget _editField({
    required String label,
    required TextEditingController controller,
    required ValueChanged<double> onChanged,
    String? prefix,
    String? suffix,
    Color? valueColor,
    Widget? trailing,
  }) {
    final p = context.astra;
    final style = serif(size: 19, color: valueColor ?? p.ink);
    return AstraCard(
      radius: 15,
      padding: const EdgeInsets.all(11),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          SizedBox(
            height: 22,
            child: Row(
              mainAxisAlignment: MainAxisAlignment.spaceBetween,
              crossAxisAlignment: CrossAxisAlignment.center,
              children: [
                Text(label.toUpperCase(), style: ui(size: 9.5, weight: FontWeight.w800, color: p.textMuted, letterSpacing: 0.6)),
                if (trailing != null) trailing,
              ],
            ),
          ),
          const SizedBox(height: 4),
          Row(
            children: [
              if (prefix != null) Text(prefix, style: style),
              Expanded(
                child: TextField(
                  controller: controller,
                  keyboardType: const TextInputType.numberWithOptions(decimal: true),
                  inputFormatters: [FilteringTextInputFormatter.allow(RegExp(r'[0-9.]'))],
                  style: style,
                  cursorColor: p.primary,
                  decoration: const InputDecoration(
                    isCollapsed: true,
                    border: InputBorder.none,
                    hintText: '0',
                  ),
                  onChanged: (v) => onChanged(double.tryParse(v) ?? 0),
                ),
              ),
              if (suffix != null) Text(suffix, style: style),
            ],
          ),
        ],
      ),
    );
  }

  Widget _discountField() {
    final p = context.astra;
    return _editField(
      label: 'Discount',
      controller: _discCtl,
      prefix: _isPercent ? null : r'$',
      suffix: _isPercent ? '%' : null,
      valueColor: p.goldText,
      onChanged: (v) => setState(() => _discount = v),
      trailing: Container(
        padding: const EdgeInsets.all(2),
        decoration: BoxDecoration(color: p.isDark ? Colors.white12 : const Color(0xFFF3EFE6), borderRadius: BorderRadius.circular(7)),
        child: Row(
          mainAxisSize: MainAxisSize.min,
          children: [_toggle('%', _isPercent), _toggle(r'$', !_isPercent)],
        ),
      ),
    );
  }

  Widget _toggle(String label, bool active) {
    final p = context.astra;
    return GestureDetector(
      onTap: () => setState(() => _isPercent = label == '%'),
      child: Container(
        padding: const EdgeInsets.symmetric(horizontal: 6, vertical: 2),
        decoration: BoxDecoration(color: active ? p.card : Colors.transparent, borderRadius: BorderRadius.circular(5)),
        child: Text(label, style: ui(size: 9, weight: FontWeight.w800, color: active ? p.ink : p.textMuted)),
      ),
    );
  }

  Widget _qtyCard() {
    final p = context.astra;
    final step = context.read<CartCubit>().defaultQty;
    return AstraCard(
      radius: 15,
      padding: const EdgeInsets.all(11),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Text('QUANTITY', style: ui(size: 9.5, weight: FontWeight.w800, color: p.textMuted, letterSpacing: 0.6)),
          const SizedBox(height: 5),
          QtyStepper(
            qty: qtyLabel(_qty),
            onMinus: () => setState(() => _qty = (_qty - step).clamp(0.001, 999999)),
            onPlus: () => setState(() => _qty = (_qty + step).clamp(0.001, 999999)),
            onTapValue: () async {
              final v = await showQtyInputSheet(
                context,
                current: _qty,
                title: widget.line.name,
                subtitle: 'Enter quantity',
              );
              if (v != null) setState(() => _qty = v);
            },
          ),
        ],
      ),
    );
  }

  Widget _lineTotal() {
    final p = context.astra;
    return Container(
      padding: const EdgeInsets.symmetric(horizontal: 15, vertical: 13),
      decoration: BoxDecoration(
        color: p.tint,
        borderRadius: BorderRadius.circular(15),
        border: Border.all(color: p.primary.withValues(alpha: 0.18)),
      ),
      child: Row(
        children: [
          Expanded(
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Text('LINE TOTAL', style: ui(size: 10, weight: FontWeight.w700, color: p.textSecondary, letterSpacing: 0.4)),
                Text(
                  '${Money.of(_unitPrice)} ${_discount > 0 ? '− ${_isPercent ? '${_discount.toStringAsFixed(0)}%' : Money.of(_discount)} ' : ''}${_tax > 0 ? '+ ${_tax.toStringAsFixed(0)}% tax' : ''}',
                  maxLines: 1,
                  overflow: TextOverflow.ellipsis,
                  style: ui(size: 9.5, weight: FontWeight.w600, color: p.textMuted),
                ),
              ],
            ),
          ),
          const SizedBox(width: 8),
          Flexible(
            child: FittedBox(
              fit: BoxFit.scaleDown,
              alignment: Alignment.centerRight,
              child: Text(Money.of(_total), style: serif(size: 24, color: p.primaryDark)),
            ),
          ),
        ],
      ),
    );
  }

}

/// Light variant of the header icon button (for sheets on cream background).
class HeaderIconButtonLight extends StatelessWidget {
  const HeaderIconButtonLight({super.key, required this.icon, this.onTap});
  final IconData icon;
  final VoidCallback? onTap;
  @override
  Widget build(BuildContext context) {
    final p = context.astra;
    final t = context.astraTheme;
    return GestureDetector(
      onTap: onTap,
      child: Container(
        width: 34,
        height: 34,
        decoration: BoxDecoration(color: p.card, borderRadius: BorderRadius.circular(11), boxShadow: t.softShadow),
        child: Icon(icon, size: 15, color: p.ink),
      ),
    );
  }
}
