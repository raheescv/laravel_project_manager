import 'package:flutter/material.dart';
import 'package:flutter_bloc/flutter_bloc.dart';

import 'package:invo/shared/domain/helpers/formatters.dart';
import 'package:invo/shared/utils/components/theme/index.dart';
import 'package:invo/shared/widgets/astra_widgets.dart';

import '../../domain/models/technician_models.dart';
import '../../logic/complaint_detail_cubit/complaint_detail_cubit.dart';
import 'scanner_screen.dart';

/// Opens the "Add supply item" sheet. On success it adds via the [cubit]
/// (which re-fetches the detail) and returns true. Mirrors the web addCart row:
/// store, product (or barcode scan), mode New/Damaged, qty, price, remarks.
Future<void> showAddSupplyItemSheet(BuildContext context, ComplaintDetailCubit cubit) async {
  cubit.ensureBranches();
  await showModalBottomSheet<void>(
    context: context,
    isScrollControlled: true,
    backgroundColor: Colors.transparent,
    builder: (_) => _SupplyItemSheet(cubit: cubit),
  );
}

class _SupplyItemSheet extends StatefulWidget {
  const _SupplyItemSheet({required this.cubit});
  final ComplaintDetailCubit cubit;

  @override
  State<_SupplyItemSheet> createState() => _SupplyItemSheetState();
}

class _SupplyItemSheetState extends State<_SupplyItemSheet> {
  BranchOption? _branch;
  ProductOption? _product;
  String _mode = 'New';
  double _qty = 1;
  final _priceCtl = TextEditingController();
  final _remarksCtl = TextEditingController();
  bool _submitting = false;

  @override
  void initState() {
    super.initState();
    final branches = widget.cubit.branches;
    if (branches.isNotEmpty) _branch = branches.first;
  }

  @override
  void dispose() {
    _priceCtl.dispose();
    _remarksCtl.dispose();
    super.dispose();
  }

  double? get _price {
    final t = _priceCtl.text.trim();
    if (t.isEmpty) return null; // let the server default to product cost
    return double.tryParse(t);
  }

  Future<void> _add() async {
    if (_branch == null) {
      _toast('Please select a store');
      return;
    }
    if (_product == null) {
      _toast('Please select a product');
      return;
    }
    setState(() => _submitting = true);
    final ok = await widget.cubit.addSupplyItem(
      branchId: _branch!.id,
      productId: _product!.id,
      mode: _mode,
      quantity: _qty,
      unitPrice: _price,
      remarks: _remarksCtl.text.trim(),
    );
    if (!mounted) return;
    if (ok) {
      Navigator.of(context).pop();
    } else {
      setState(() => _submitting = false);
      _toast(widget.cubit.actionError ?? 'Could not add item');
    }
  }

  Future<void> _scan() async {
    final code = await ScannerScreen.open(context);
    if (code == null || code.isEmpty || !mounted) return;
    if (_branch == null) {
      _toast('Please select a store first');
      return;
    }
    setState(() => _submitting = true);
    final ok = await widget.cubit.addSupplyItem(
      branchId: _branch!.id,
      barcode: code,
      mode: _mode,
      quantity: _qty,
    );
    if (!mounted) return;
    if (ok) {
      Navigator.of(context).pop();
    } else {
      setState(() => _submitting = false);
      _toast(widget.cubit.actionError ?? 'No product matched that barcode');
    }
  }

  void _toast(String msg) {
    ScaffoldMessenger.of(context)
      ..hideCurrentSnackBar()
      ..showSnackBar(SnackBar(content: Text(msg)));
  }

  @override
  Widget build(BuildContext context) {
    final p = context.astra;
    return Padding(
      padding: EdgeInsets.only(bottom: MediaQuery.of(context).viewInsets.bottom),
      child: Container(
        decoration: BoxDecoration(
          color: p.canvas,
          borderRadius: const BorderRadius.vertical(top: Radius.circular(26)),
        ),
        child: SafeArea(
          top: false,
          child: SingleChildScrollView(
            padding: const EdgeInsets.fromLTRB(18, 12, 18, 18),
            child: Column(
              mainAxisSize: MainAxisSize.min,
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Center(
                  child: Container(
                    width: 40,
                    height: 4,
                    decoration: BoxDecoration(color: p.hairline, borderRadius: BorderRadius.circular(2)),
                  ),
                ),
                const SizedBox(height: 14),
                Row(
                  children: [
                    Expanded(child: Text('Add supply item', style: serif(size: 20, color: p.ink))),
                    GestureDetector(
                      onTap: _submitting ? null : _scan,
                      child: Container(
                        padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 9),
                        decoration: BoxDecoration(color: p.tint, borderRadius: BorderRadius.circular(12)),
                        child: Row(mainAxisSize: MainAxisSize.min, children: [
                          Icon(Icons.qr_code_scanner, size: 16, color: p.primary),
                          const SizedBox(width: 6),
                          Text('Scan', style: ui(size: 12.5, weight: FontWeight.w700, color: p.primary)),
                        ]),
                      ),
                    ),
                  ],
                ),
                const SizedBox(height: 16),
                SectionLabel('Store'),
                const SizedBox(height: 8),
                _pickerRow(
                  icon: Icons.store_outlined,
                  label: _branch?.name ?? 'Select a store',
                  placeholder: _branch == null,
                  onTap: _pickStore,
                ),
                const SizedBox(height: 14),
                SectionLabel('Product'),
                const SizedBox(height: 8),
                _pickerRow(
                  icon: Icons.inventory_2_outlined,
                  label: _product?.name ?? 'Select a product',
                  placeholder: _product == null,
                  onTap: _pickProduct,
                ),
                const SizedBox(height: 14),
                Row(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    Expanded(
                      child: Column(
                        crossAxisAlignment: CrossAxisAlignment.start,
                        children: [
                          SectionLabel('Mode'),
                          const SizedBox(height: 8),
                          Row(
                            children: [
                              for (final m in const ['New', 'Damaged'])
                                Padding(
                                  padding: const EdgeInsets.only(right: 8),
                                  child: AstraChip(
                                    label: m,
                                    active: _mode == m,
                                    onTap: () => setState(() => _mode = m),
                                  ),
                                ),
                            ],
                          ),
                        ],
                      ),
                    ),
                    Column(
                      crossAxisAlignment: CrossAxisAlignment.start,
                      children: [
                        SectionLabel('Quantity'),
                        const SizedBox(height: 8),
                        QtyStepper(
                          qty: qtyLabel(_qty),
                          onMinus: () => setState(() => _qty = (_qty - 1).clamp(1, 9999)),
                          onPlus: () => setState(() => _qty = _qty + 1),
                        ),
                      ],
                    ),
                  ],
                ),
                const SizedBox(height: 14),
                SectionLabel('Unit price (optional — defaults to product cost)'),
                const SizedBox(height: 8),
                _field(_priceCtl, hint: '0.00', keyboardType: const TextInputType.numberWithOptions(decimal: true)),
                const SizedBox(height: 14),
                SectionLabel('Remarks'),
                const SizedBox(height: 8),
                _field(_remarksCtl, hint: 'Optional note for this item'),
                const SizedBox(height: 20),
                AstraButton(
                  label: 'Add to supply request',
                  icon: Icons.add,
                  busy: _submitting,
                  onTap: _add,
                ),
              ],
            ),
          ),
        ),
      ),
    );
  }

  Widget _pickerRow({required IconData icon, required String label, required bool placeholder, required VoidCallback onTap}) {
    final p = context.astra;
    return GestureDetector(
      onTap: onTap,
      child: AstraCard(
        radius: 13,
        padding: const EdgeInsets.all(12),
        child: Row(
          children: [
            IconChip(icon: icon, size: 32, radius: 9, bg: p.tint),
            const SizedBox(width: 11),
            Expanded(
              child: Text(label,
                  maxLines: 1,
                  overflow: TextOverflow.ellipsis,
                  style: ui(size: 13, weight: FontWeight.w700, color: placeholder ? p.textMuted : p.ink)),
            ),
            Icon(Icons.chevron_right, color: p.textMuted, size: 18),
          ],
        ),
      ),
    );
  }

  Widget _field(TextEditingController ctl, {required String hint, TextInputType? keyboardType}) {
    final p = context.astra;
    return Container(
      padding: const EdgeInsets.symmetric(horizontal: 12),
      decoration: BoxDecoration(color: p.card, borderRadius: BorderRadius.circular(12), border: Border.all(color: p.cardBorder)),
      child: TextField(
        controller: ctl,
        keyboardType: keyboardType,
        style: ui(size: 13.5, weight: FontWeight.w600, color: p.ink),
        decoration: InputDecoration(
          isDense: true,
          contentPadding: const EdgeInsets.symmetric(vertical: 13),
          border: InputBorder.none,
          hintText: hint,
          hintStyle: ui(size: 13, weight: FontWeight.w500, color: p.textMuted),
        ),
      ),
    );
  }

  Future<void> _pickStore() async {
    final branches = widget.cubit.branches;
    final chosen = await showModalBottomSheet<BranchOption>(
      context: context,
      backgroundColor: Colors.transparent,
      builder: (_) => _SimplePickerSheet<BranchOption>(
        title: 'Select store',
        items: branches,
        selected: _branch,
        labelOf: (b) => b.name,
        icon: Icons.store_outlined,
      ),
    );
    if (chosen != null) setState(() => _branch = chosen);
  }

  Future<void> _pickProduct() async {
    final chosen = await showModalBottomSheet<ProductOption>(
      context: context,
      isScrollControlled: true,
      backgroundColor: Colors.transparent,
      builder: (_) => _ProductPickerSheet(cubit: widget.cubit),
    );
    if (chosen != null) {
      setState(() {
        _product = chosen;
        // Prefill the unit price with the product's cost on selection.
        if (chosen.cost > 0) {
          _priceCtl.text = qtyLabel(chosen.cost);
        }
      });
    }
  }
}

/// A generic click-and-go picker (applies on tap, no Save button).
class _SimplePickerSheet<T> extends StatelessWidget {
  const _SimplePickerSheet({
    required this.title,
    required this.items,
    required this.selected,
    required this.labelOf,
    required this.icon,
  });

  final String title;
  final List<T> items;
  final T? selected;
  final String Function(T) labelOf;
  final IconData icon;

  @override
  Widget build(BuildContext context) {
    final p = context.astra;
    return Container(
      decoration: BoxDecoration(color: p.canvas, borderRadius: const BorderRadius.vertical(top: Radius.circular(26))),
      child: SafeArea(
        top: false,
        child: Column(
          mainAxisSize: MainAxisSize.min,
          children: [
            const SizedBox(height: 12),
            Container(width: 40, height: 4, decoration: BoxDecoration(color: p.hairline, borderRadius: BorderRadius.circular(2))),
            const SizedBox(height: 12),
            Padding(
              padding: const EdgeInsets.fromLTRB(18, 0, 18, 8),
              child: Align(alignment: Alignment.centerLeft, child: Text(title, style: serif(size: 19, color: p.ink))),
            ),
            if (items.isEmpty)
              Padding(
                padding: const EdgeInsets.all(28),
                child: Text('Nothing available', style: ui(size: 13, weight: FontWeight.w600, color: p.textMuted)),
              )
            else
              Flexible(
                child: ListView.builder(
                  shrinkWrap: true,
                  padding: const EdgeInsets.fromLTRB(14, 0, 14, 14),
                  itemCount: items.length,
                  itemBuilder: (context, i) {
                    final item = items[i];
                    final isSel = item == selected;
                    return Padding(
                      padding: const EdgeInsets.only(bottom: 8),
                      child: AstraCard(
                        radius: 12,
                        onTap: () => Navigator.of(context).pop(item),
                        padding: const EdgeInsets.all(12),
                        child: Row(
                          children: [
                            IconChip(icon: icon, size: 30, radius: 8, bg: p.tint),
                            const SizedBox(width: 11),
                            Expanded(child: Text(labelOf(item), style: ui(size: 13, weight: FontWeight.w700, color: p.ink))),
                            if (isSel) Icon(Icons.check_circle, size: 18, color: p.primary),
                          ],
                        ),
                      ),
                    );
                  },
                ),
              ),
          ],
        ),
      ),
    );
  }
}

/// Product picker with search-as-you-type (click-and-go).
class _ProductPickerSheet extends StatefulWidget {
  const _ProductPickerSheet({required this.cubit});
  final ComplaintDetailCubit cubit;

  @override
  State<_ProductPickerSheet> createState() => _ProductPickerSheetState();
}

class _ProductPickerSheetState extends State<_ProductPickerSheet> {
  final _searchCtl = TextEditingController();

  @override
  void initState() {
    super.initState();
    WidgetsBinding.instance.addPostFrameCallback((_) => widget.cubit.loadProducts(''));
  }

  @override
  void dispose() {
    _searchCtl.dispose();
    super.dispose();
  }

  @override
  Widget build(BuildContext context) {
    final p = context.astra;
    final cubit = widget.cubit;
    return Padding(
      padding: EdgeInsets.only(bottom: MediaQuery.of(context).viewInsets.bottom),
      child: DraggableScrollableSheet(
        expand: false,
        initialChildSize: 0.75,
        maxChildSize: 0.92,
        builder: (context, scroll) => Container(
          decoration: BoxDecoration(color: p.canvas, borderRadius: const BorderRadius.vertical(top: Radius.circular(26))),
          child: Column(
            children: [
              const SizedBox(height: 12),
              Container(width: 40, height: 4, decoration: BoxDecoration(color: p.hairline, borderRadius: BorderRadius.circular(2))),
              Padding(
                padding: const EdgeInsets.fromLTRB(16, 12, 16, 10),
                child: Container(
                  padding: const EdgeInsets.symmetric(horizontal: 12),
                  decoration: BoxDecoration(color: p.card, borderRadius: BorderRadius.circular(12), border: Border.all(color: p.cardBorder)),
                  child: Row(
                    children: [
                      Icon(Icons.search, size: 18, color: p.textMuted),
                      const SizedBox(width: 8),
                      Expanded(
                        child: TextField(
                          controller: _searchCtl,
                          autofocus: true,
                          onChanged: (v) => cubit.loadProducts(v),
                          style: ui(size: 13.5, weight: FontWeight.w600, color: p.ink),
                          decoration: InputDecoration(
                            isDense: true,
                            contentPadding: const EdgeInsets.symmetric(vertical: 13),
                            border: InputBorder.none,
                            hintText: 'Search products…',
                            hintStyle: ui(size: 13, weight: FontWeight.w500, color: p.textMuted),
                          ),
                        ),
                      ),
                    ],
                  ),
                ),
              ),
              Expanded(
                child: BlocBuilder<ComplaintDetailCubit, int>(
                  bloc: cubit,
                  builder: (context, _) {
                    if (cubit.productsLoading && cubit.products.isEmpty) {
                      return Center(child: CircularProgressIndicator(color: p.primary));
                    }
                    if (cubit.products.isEmpty) {
                      return EmptyState(icon: Icons.inventory_2_outlined, title: 'No products', message: 'Try another search.');
                    }
                    return ListView.builder(
                      controller: scroll,
                      padding: const EdgeInsets.fromLTRB(14, 0, 14, 16),
                      itemCount: cubit.products.length,
                      itemBuilder: (context, i) {
                        final prod = cubit.products[i];
                        return Padding(
                          padding: const EdgeInsets.only(bottom: 8),
                          child: AstraCard(
                            radius: 12,
                            onTap: () => Navigator.of(context).pop(prod),
                            padding: const EdgeInsets.all(12),
                            child: Row(
                              children: [
                                IconChip(icon: Icons.inventory_2_outlined, size: 32, radius: 9, bg: p.tint),
                                const SizedBox(width: 11),
                                Expanded(
                                  child: Column(
                                    crossAxisAlignment: CrossAxisAlignment.start,
                                    children: [
                                      Text(prod.name, maxLines: 1, overflow: TextOverflow.ellipsis, style: ui(size: 13, weight: FontWeight.w700, color: p.ink)),
                                      if (prod.barcode.isNotEmpty)
                                        Text(prod.barcode, style: ui(size: 10.5, weight: FontWeight.w600, color: p.textMuted)),
                                    ],
                                  ),
                                ),
                                Icon(Icons.add_circle_outline, size: 18, color: p.primary),
                              ],
                            ),
                          ),
                        );
                      },
                    );
                  },
                ),
              ),
            ],
          ),
        ),
      ),
    );
  }
}
