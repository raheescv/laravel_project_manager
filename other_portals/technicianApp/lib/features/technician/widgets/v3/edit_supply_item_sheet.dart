import 'package:flutter/material.dart';

import 'package:invo/shared/domain/helpers/formatters.dart';
import 'package:invo/shared/utils/components/theme/index.dart';
import 'package:invo/shared/widgets/astra_widgets.dart';

import '../../domain/models/technician_models.dart';

/// The values the edit sheet hands back; the screen saves them via the cubit.
typedef SupplyItemEdit = ({String mode, double? quantity, double? unitPrice, String remarks});

/// Edit a supply item's mode, quantity, unit price and remarks. Owns (and
/// disposes) its own controllers — they used to be created by the opener and
/// never disposed.
class EditSupplyItemSheet extends StatefulWidget {
  const EditSupplyItemSheet({super.key, required this.item});
  final SupplyItem item;

  @override
  State<EditSupplyItemSheet> createState() => _EditSupplyItemSheetState();
}

class _EditSupplyItemSheetState extends State<EditSupplyItemSheet> {
  late final _qtyCtl = TextEditingController(text: qtyLabel(widget.item.quantity));
  late final _priceCtl = TextEditingController(text: widget.item.unitPrice.toStringAsFixed(2));
  late final _remarksCtl = TextEditingController(text: widget.item.remarks);
  late String _mode = widget.item.mode;

  @override
  void dispose() {
    _qtyCtl.dispose();
    _priceCtl.dispose();
    _remarksCtl.dispose();
    super.dispose();
  }

  void _save() => Navigator.of(context).pop<SupplyItemEdit>((
        mode: _mode,
        quantity: double.tryParse(_qtyCtl.text.trim()),
        unitPrice: double.tryParse(_priceCtl.text.trim()),
        remarks: _remarksCtl.text.trim(),
      ));

  @override
  Widget build(BuildContext context) {
    final p = context.astra;
    return Padding(
      padding: EdgeInsets.only(bottom: MediaQuery.viewInsetsOf(context).bottom),
      child: Container(
        decoration: BoxDecoration(color: p.canvas, borderRadius: const BorderRadius.vertical(top: Radius.circular(26))),
        child: SafeArea(
          top: false,
          child: Padding(
            padding: const EdgeInsets.fromLTRB(18, 14, 18, 18),
            child: Column(mainAxisSize: MainAxisSize.min, crossAxisAlignment: CrossAxisAlignment.start, children: [
              Text('Edit item', style: serif(size: 20, color: p.ink)),
              const SizedBox(height: 4),
              Text(widget.item.productName, style: ui(size: 12, weight: FontWeight.w600, color: p.textMuted)),
              const SizedBox(height: 16),
              Row(children: [
                for (final m in const ['New', 'Damaged'])
                  Padding(
                    padding: const EdgeInsets.only(right: 8),
                    child: AstraChip(label: m, active: _mode == m, onTap: () => setState(() => _mode = m)),
                  ),
              ]),
              const SizedBox(height: 14),
              Row(children: [
                Expanded(
                  child: _SheetField(
                      controller: _qtyCtl, label: 'Quantity', keyboardType: const TextInputType.numberWithOptions(decimal: true)),
                ),
                const SizedBox(width: 10),
                Expanded(
                  child: _SheetField(
                      controller: _priceCtl, label: 'Unit price', keyboardType: const TextInputType.numberWithOptions(decimal: true)),
                ),
              ]),
              const SizedBox(height: 12),
              _SheetField(controller: _remarksCtl, label: 'Remarks'),
              const SizedBox(height: 18),
              AstraButton(label: 'Save changes', onTap: _save),
            ]),
          ),
        ),
      ),
    );
  }
}

class _SheetField extends StatelessWidget {
  const _SheetField({required this.controller, required this.label, this.keyboardType});
  final TextEditingController controller;
  final String label;
  final TextInputType? keyboardType;

  @override
  Widget build(BuildContext context) {
    final p = context.astra;
    return Column(crossAxisAlignment: CrossAxisAlignment.start, children: [
      Text(label.toUpperCase(), style: ui(size: 10, weight: FontWeight.w800, color: p.textMuted, letterSpacing: 1)),
      const SizedBox(height: 6),
      Container(
        padding: const EdgeInsets.symmetric(horizontal: 12),
        decoration: BoxDecoration(color: p.card, borderRadius: BorderRadius.circular(12), border: Border.all(color: p.cardBorder)),
        child: TextField(
          controller: controller,
          keyboardType: keyboardType,
          style: ui(size: 13.5, weight: FontWeight.w600, color: p.ink),
          decoration: const InputDecoration(isDense: true, contentPadding: EdgeInsets.symmetric(vertical: 13), border: InputBorder.none),
        ),
      ),
    ]);
  }
}
