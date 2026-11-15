import 'package:flutter/material.dart';
import 'package:flutter/services.dart';

import 'package:invo/shared/domain/helpers/formatters.dart';
import 'package:invo/shared/utils/components/theme/index.dart';
import 'package:invo/shared/widgets/astra_widgets.dart';

/// Bottom sheet to type an exact quantity (supports decimals, matching the web
/// POS 0.001 step). Returns the entered value clamped to [min]..[max], or null
/// if cancelled.
Future<double?> showQtyInputSheet(
  BuildContext context, {
  required double current,
  double min = 0.001,
  double max = 999999,
  String title = 'Quantity',
  String? subtitle,
}) {
  return showModalBottomSheet<double>(
    context: context,
    isScrollControlled: true,
    backgroundColor: Colors.transparent,
    builder: (_) => _QtyInputSheet(
      current: current,
      min: min,
      max: max,
      title: title,
      subtitle: subtitle,
    ),
  );
}

class _QtyInputSheet extends StatefulWidget {
  const _QtyInputSheet({
    required this.current,
    required this.min,
    required this.max,
    required this.title,
    this.subtitle,
  });
  final double current;
  final double min;
  final double max;
  final String title;
  final String? subtitle;

  @override
  State<_QtyInputSheet> createState() => _QtyInputSheetState();
}

class _QtyInputSheetState extends State<_QtyInputSheet> {
  late final TextEditingController _ctl =
      TextEditingController(text: qtyLabel(widget.current));
  final FocusNode _focus = FocusNode();

  @override
  void initState() {
    super.initState();
    // Select the whole value so the first keystroke replaces it.
    WidgetsBinding.instance.addPostFrameCallback((_) {
      _focus.requestFocus();
      _ctl.selection = TextSelection(baseOffset: 0, extentOffset: _ctl.text.length);
    });
  }

  @override
  void dispose() {
    _ctl.dispose();
    _focus.dispose();
    super.dispose();
  }

  void _submit() {
    final parsed = double.tryParse(_ctl.text.trim());
    if (parsed == null) {
      Navigator.pop(context);
      return;
    }
    Navigator.pop(context, parsed.clamp(widget.min, widget.max).toDouble());
  }

  @override
  Widget build(BuildContext context) {
    final p = context.astra;
    return Padding(
      padding: EdgeInsets.only(bottom: MediaQuery.of(context).viewInsets.bottom),
      child: Container(
        decoration: BoxDecoration(
            color: p.sheet,
            borderRadius: const BorderRadius.vertical(top: Radius.circular(30))),
        padding: const EdgeInsets.fromLTRB(20, 14, 20, 22),
        child: Column(
          mainAxisSize: MainAxisSize.min,
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Center(
              child: Container(
                  width: 42,
                  height: 5,
                  decoration: BoxDecoration(
                      color: p.hairline, borderRadius: BorderRadius.circular(3))),
            ),
            const SizedBox(height: 16),
            Text(widget.title,
                style: serif(size: 22, color: p.ink)),
            if (widget.subtitle != null) ...[
              const SizedBox(height: 2),
              Text(widget.subtitle!,
                  style: ui(size: 12, weight: FontWeight.w600, color: p.textMuted)),
            ],
            const SizedBox(height: 16),
            KeyboardDoneField(
              focusNode: _focus,
              child: Container(
                padding: const EdgeInsets.symmetric(horizontal: 16, vertical: 4),
                decoration: BoxDecoration(
                  color: p.card,
                  borderRadius: BorderRadius.circular(15),
                  border: Border.all(color: p.primary.withValues(alpha: 0.25)),
                ),
                child: TextField(
                  controller: _ctl,
                  focusNode: _focus,
                  autofocus: true,
                  keyboardType: const TextInputType.numberWithOptions(decimal: true),
                  inputFormatters: [
                    FilteringTextInputFormatter.allow(RegExp(r'[0-9.]')),
                  ],
                  textAlign: TextAlign.center,
                  style: serif(size: 30, color: p.ink),
                  cursorColor: p.primary,
                  decoration: const InputDecoration(
                    border: InputBorder.none,
                    hintText: '0',
                  ),
                  onSubmitted: (_) => _submit(),
                ),
              ),
            ),
            const SizedBox(height: 16),
            Row(
              children: [
                AstraButton(
                    label: 'Cancel',
                    gold: false,
                    expand: false,
                    onTap: () => Navigator.pop(context)),
                const SizedBox(width: 11),
                Expanded(child: AstraButton(label: 'Set quantity', onTap: _submit)),
              ],
            ),
          ],
        ),
      ),
    );
  }
}
