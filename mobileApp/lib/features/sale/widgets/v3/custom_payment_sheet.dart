import 'package:flutter/material.dart';
import 'package:flutter/services.dart';

import 'package:invo/shared/domain/helpers/formatters.dart';
import 'package:invo/shared/domain/models/index.dart';
import 'package:invo/features/sale/logic/cart_cubit/cart_cubit.dart';
import 'package:invo/shared/utils/components/theme/index.dart';
import 'package:invo/shared/widgets/astra_widgets.dart';

/// Bottom-sheet equivalent of the web "Custom Payment" modal: split the grand
/// total across one or more configured payment methods. Returns the finished
/// list of [CustomPayment] rows when the user taps Save, or `null` if dismissed.
Future<List<CustomPayment>?> showCustomPaymentSheet(
  BuildContext context, {
  required double total,
  required List<PaymentMethod> methods,
  List<CustomPayment> initial = const [],
}) {
  return showModalBottomSheet<List<CustomPayment>>(
    context: context,
    isScrollControlled: true,
    backgroundColor: Colors.transparent,
    barrierColor: Colors.black.withValues(alpha: 0.45),
    builder: (_) => _CustomPaymentSheet(total: total, methods: methods, initial: initial),
  );
}

class _CustomPaymentSheet extends StatefulWidget {
  const _CustomPaymentSheet({required this.total, required this.methods, required this.initial});
  final double total;
  final List<PaymentMethod> methods;
  final List<CustomPayment> initial;

  @override
  State<_CustomPaymentSheet> createState() => _CustomPaymentSheetState();
}

class _CustomPaymentSheetState extends State<_CustomPaymentSheet> {
  late final TextEditingController _amount;
  final List<CustomPayment> _payments = [];
  PaymentMethod? _selected;
  String? _error;

  @override
  void initState() {
    super.initState();
    _payments.addAll(widget.initial);
    _amount = TextEditingController();
    _resetAmountToBalance();
  }

  @override
  void dispose() {
    _amount.dispose();
    super.dispose();
  }

  double get _totalPaid => _payments.fold(0.0, (a, p) => a + p.amount);
  double get _balanceDue => widget.total - _totalPaid;

  void _resetAmountToBalance() {
    final b = _balanceDue;
    _amount.text = (b > 0 ? b : 0).toStringAsFixed(2);
  }

  void _addPayment() {
    setState(() => _error = null);
    final method = _selected;
    final amount = double.tryParse(_amount.text.trim()) ?? 0;

    if (method == null) {
      setState(() => _error = 'Select a payment method.');
      return;
    }
    if (amount <= 0) {
      setState(() => _error = 'Enter an amount greater than 0.');
      return;
    }
    if (_totalPaid + amount > widget.total + 0.001) {
      setState(() => _error = 'Total payments cannot exceed the payable amount.');
      return;
    }

    setState(() {
      _payments.add(CustomPayment(methodId: method.id, methodName: method.name, amount: amount));
      _selected = null;
      _resetAmountToBalance();
    });
  }

  void _removeAt(int index) {
    setState(() {
      _payments.removeAt(index);
      _resetAmountToBalance();
    });
  }

  void _save() {
    if (_payments.isEmpty) {
      setState(() => _error = 'Add at least one payment.');
      return;
    }
    Navigator.of(context).pop(_payments);
  }

  Future<void> _pickMethod() async {
    FocusScope.of(context).unfocus();
    final p = context.astra;
    final chosen = await showModalBottomSheet<PaymentMethod>(
      context: context,
      backgroundColor: Colors.transparent,
      builder: (_) => Container(
        decoration: BoxDecoration(
          color: p.card,
          borderRadius: const BorderRadius.vertical(top: Radius.circular(26)),
        ),
        child: SafeArea(
          top: false,
          child: Column(
            mainAxisSize: MainAxisSize.min,
            children: [
              const SizedBox(height: 10),
              _grabber(p),
              const SizedBox(height: 8),
              Padding(
                padding: const EdgeInsets.fromLTRB(20, 4, 20, 10),
                child: Align(
                  alignment: Alignment.centerLeft,
                  child: SectionLabel('Select payment method'),
                ),
              ),
              if (widget.methods.isEmpty)
                Padding(
                  padding: const EdgeInsets.fromLTRB(20, 4, 20, 24),
                  child: Text('No payment methods are configured.',
                      style: ui(size: 12.5, weight: FontWeight.w600, color: p.textSecondary)),
                ),
              ...widget.methods.map((m) {
                final active = _selected?.id == m.id;
                // Own Material so the tap ink paints in front of the card-colored
                // sheet Container (otherwise the splash is hidden behind it).
                return Material(
                  type: MaterialType.transparency,
                  child: ListTile(
                    onTap: () => Navigator.of(context).pop(m),
                    leading: Icon(active ? Icons.radio_button_checked : Icons.radio_button_off,
                        size: 20, color: active ? p.primary : p.textMuted),
                    title: Text(m.name, style: ui(size: 13.5, weight: FontWeight.w700, color: p.ink)),
                  ),
                );
              }),
              const SizedBox(height: 10),
            ],
          ),
        ),
      ),
    );
    if (chosen != null) setState(() => _selected = chosen);
  }

  @override
  Widget build(BuildContext context) {
    final p = context.astra;
    final insets = MediaQuery.of(context).viewInsets.bottom;

    return Padding(
      padding: EdgeInsets.only(bottom: insets),
      child: Container(
        constraints: BoxConstraints(maxHeight: MediaQuery.of(context).size.height * 0.9),
        decoration: BoxDecoration(
          color: p.canvas,
          borderRadius: const BorderRadius.vertical(top: Radius.circular(30)),
        ),
        child: Column(
          mainAxisSize: MainAxisSize.min,
          children: [
            const SizedBox(height: 10),
            _grabber(p),
            Padding(
              padding: const EdgeInsets.fromLTRB(20, 12, 16, 4),
              child: Row(
                children: [
                  Icon(Icons.tune, size: 18, color: p.primary),
                  const SizedBox(width: 9),
                  Expanded(child: Text('Custom Payment', style: serif(size: 20, color: p.ink))),
                  GestureDetector(
                    onTap: () => Navigator.of(context).pop(),
                    child: Icon(Icons.close, size: 20, color: p.textMuted),
                  ),
                ],
              ),
            ),
            Flexible(
              child: ListView(
                shrinkWrap: true,
                padding: const EdgeInsets.fromLTRB(16, 8, 16, 16),
                children: [
                  _payableHeader(p),
                  const SizedBox(height: 16),
                  SectionLabel('Add payment'),
                  const SizedBox(height: 9),
                  _addRow(p),
                  if (_error != null) ...[
                    const SizedBox(height: 9),
                    _errorBox(p, _error!),
                  ],
                  if (_payments.isNotEmpty) ...[
                    const SizedBox(height: 18),
                    SectionLabel('Payment summary'),
                    const SizedBox(height: 9),
                    _summary(p),
                  ],
                  const SizedBox(height: 16),
                  _totalsCard(p),
                ],
              ),
            ),
            SafeArea(
              top: false,
              child: Padding(
                padding: const EdgeInsets.fromLTRB(16, 4, 16, 14),
                child: AstraButton(
                  label: 'Save Payment',
                  icon: Icons.check_rounded,
                  gold: true,
                  onTap: _payments.isEmpty ? null : _save,
                ),
              ),
            ),
          ],
        ),
      ),
    );
  }

  Widget _payableHeader(AstraPalette p) => AstraCard(
        padding: const EdgeInsets.symmetric(vertical: 16),
        child: Column(
          children: [
            Text('TOTAL PAYABLE AMOUNT',
                style: ui(size: 10, weight: FontWeight.w800, color: p.textMuted, letterSpacing: 0.9)),
            const SizedBox(height: 6),
            Text(Money.of(widget.total), style: serif(size: 28, color: p.primary)),
          ],
        ),
      );

  Widget _addRow(AstraPalette p) {
    return Row(
      crossAxisAlignment: CrossAxisAlignment.end,
      children: [
        // Method picker (tap to choose).
        Expanded(
          child: GestureDetector(
            onTap: _pickMethod,
            child: _field(
              p,
              child: Row(
                children: [
                  Expanded(
                    child: Text(
                      _selected?.name ?? 'Select method',
                      maxLines: 1,
                      overflow: TextOverflow.ellipsis,
                      style: ui(
                        size: 13,
                        weight: FontWeight.w700,
                        color: _selected == null ? p.textMuted : p.ink,
                      ),
                    ),
                  ),
                  Icon(Icons.expand_more, size: 18, color: p.textMuted),
                ],
              ),
            ),
          ),
        ),
        const SizedBox(width: 8),
        // Amount.
        SizedBox(
          width: 104,
          child: _field(
            p,
            child: TextField(
              controller: _amount,
              keyboardType: const TextInputType.numberWithOptions(decimal: true),
              inputFormatters: [FilteringTextInputFormatter.allow(RegExp(r'[0-9.]'))],
              textAlign: TextAlign.right,
              onTap: () => _amount.selection =
                  TextSelection(baseOffset: 0, extentOffset: _amount.text.length),
              style: ui(size: 13.5, weight: FontWeight.w800, color: p.ink),
              decoration: InputDecoration(
                isDense: true,
                border: InputBorder.none,
                contentPadding: EdgeInsets.zero,
                hintText: '0.00',
                hintStyle: ui(size: 13, weight: FontWeight.w700, color: p.textMuted),
              ),
            ),
          ),
        ),
        const SizedBox(width: 8),
        GestureDetector(
          onTap: _addPayment,
          child: Container(
            height: 44,
            width: 46,
            decoration: BoxDecoration(
              gradient: p.primaryGradient,
              borderRadius: BorderRadius.circular(context.astraTheme.rField),
              boxShadow: context.astraTheme.floatShadow(p.primary),
            ),
            child: const Icon(Icons.add, size: 20, color: Colors.white),
          ),
        ),
      ],
    );
  }

  Widget _summary(AstraPalette p) => AstraCard(
        padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 6),
        child: Column(
          children: [
            for (var i = 0; i < _payments.length; i++) ...[
              if (i > 0) Divider(height: 1, color: p.hairline),
              Padding(
                padding: const EdgeInsets.symmetric(vertical: 9),
                child: Row(
                  children: [
                    Expanded(
                      child: Text(_payments[i].methodName,
                          style: ui(size: 12.5, weight: FontWeight.w700, color: p.ink)),
                    ),
                    Text(Money.of(_payments[i].amount),
                        style: ui(size: 12.5, weight: FontWeight.w800, color: p.ink)),
                    const SizedBox(width: 10),
                    GestureDetector(
                      onTap: () => _removeAt(i),
                      child: Container(
                        padding: const EdgeInsets.all(5),
                        decoration: BoxDecoration(
                          color: p.dangerTint,
                          borderRadius: BorderRadius.circular(8),
                        ),
                        child: const Icon(Icons.delete_outline, size: 15, color: AstraPalette.danger),
                      ),
                    ),
                  ],
                ),
              ),
            ],
          ],
        ),
      );

  Widget _totalsCard(AstraPalette p) {
    final settled = _balanceDue.abs() < 0.001;
    return Container(
      padding: const EdgeInsets.all(12),
      decoration: BoxDecoration(
        color: p.isDark ? Colors.white.withValues(alpha: 0.04) : const Color(0xFFF4F1E9),
        borderRadius: BorderRadius.circular(14),
      ),
      child: Column(
        children: [
          _totRow('Total Paid', Money.of(_totalPaid), AstraPalette.success, p),
          const SizedBox(height: 6),
          _totRow(
            settled ? 'Balance' : 'Balance Due',
            Money.of(_balanceDue),
            settled ? p.textSecondary : AstraPalette.danger,
            p,
          ),
        ],
      ),
    );
  }

  Widget _totRow(String label, String value, Color color, AstraPalette p) => Row(
        mainAxisAlignment: MainAxisAlignment.spaceBetween,
        children: [
          Text(label, style: ui(size: 12, weight: FontWeight.w700, color: color)),
          Text(value, style: ui(size: 13, weight: FontWeight.w800, color: color)),
        ],
      );

  Widget _field(AstraPalette p, {required Widget child}) => Container(
        height: 44,
        padding: const EdgeInsets.symmetric(horizontal: 12),
        alignment: Alignment.centerLeft,
        decoration: BoxDecoration(
          color: p.card,
          borderRadius: BorderRadius.circular(context.astraTheme.rField),
          border: Border.all(color: p.hairline),
        ),
        child: child,
      );

  Widget _errorBox(AstraPalette p, String message) => Container(
        padding: const EdgeInsets.symmetric(horizontal: 11, vertical: 9),
        decoration: BoxDecoration(
          color: p.dangerTint,
          borderRadius: BorderRadius.circular(11),
        ),
        child: Row(
          children: [
            const Icon(Icons.error_outline, size: 15, color: AstraPalette.danger),
            const SizedBox(width: 8),
            Expanded(
              child: Text(message, style: ui(size: 11.5, weight: FontWeight.w700, color: AstraPalette.danger)),
            ),
          ],
        ),
      );

  Widget _grabber(AstraPalette p) => Container(
        width: 40,
        height: 4,
        decoration: BoxDecoration(color: p.hairline, borderRadius: BorderRadius.circular(3)),
      );
}
