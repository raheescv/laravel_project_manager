import 'package:flutter/material.dart';
import 'package:flutter_bloc/flutter_bloc.dart';

import 'package:invo/features/auth/logic/auth_cubit/auth_cubit.dart';
import 'package:invo/shared/domain/constants/mobile_permissions.dart';
import 'package:invo/shared/utils/components/theme/index.dart';
import 'package:invo/shared/widgets/astra_snack.dart';

import '../../domain/models/catalog_models.dart';
import '../../logic/product_config_cubit/product_config_cubit.dart';
import '../../widgets/v3/catalog_controls.dart';
import '../../widgets/v3/catalog_form_parts.dart';

/// Settings → Product configuration — the web settings tab's barcode and POS
/// options. Click-and-go: every change saves as it is made.
///
/// Only ever hosted inside Settings (the tablet detail pane, the phone category
/// page), which owns the heading, the scrolling and the chrome.
class ProductConfigScreen extends StatelessWidget {
  const ProductConfigScreen({super.key});

  @override
  Widget build(BuildContext context) =>
      BlocProvider(create: (_) => ProductConfigCubit()..load(), child: const _ConfigCards());
}

class _ConfigCards extends StatelessWidget {
  const _ConfigCards();

  Future<void> _apply(BuildContext context, ProductConfig next) async {
    final cubit = context.read<ProductConfigCubit>();
    final snack = AstraSnack.capture(context);
    if (!await cubit.update(next)) snack.error(cubit.state.errorMessage ?? 'Could not save the change.');
  }

  @override
  Widget build(BuildContext context) {
    final p = context.astra;
    final canHideStock = context.read<AuthCubit>().hasPermission(PermissionSlug.saleCreate);
    return BlocBuilder<ProductConfigCubit, ProductConfigState>(
      builder: (context, s) {
        if (s.isLoading) return const Padding(padding: EdgeInsets.all(40), child: Center(child: CircularProgressIndicator()));
        final c = s.config;
        return Column(
          crossAxisAlignment: CrossAxisAlignment.stretch,
          children: [
            FormSectionCard(icon: Icons.qr_code_2, title: 'Barcodes', children: [
              LabeledField(
                label: 'Barcode type',
                child: SegmentedChoice<bool>(
                  height: 44,
                  options: const [(true, 'Product wise', null), (false, 'System generated', null)],
                  selected: c.barcodeTypedPerProduct,
                  onChanged: (v) => _apply(context, c.copyWith(barcodeTypedPerProduct: v)),
                ),
              ),
              Text(
                c.barcodeTypedPerProduct
                    ? 'Each product\'s barcode is typed or scanned on its form.'
                    : 'New products are numbered automatically — the form hides the barcode field.',
                style: ui(size: 11.5, weight: FontWeight.w500, color: p.textMuted, height: 1.4),
              ),
              _PrefixRow(prefix: c.barcodePrefix, onChanged: (v) => _apply(context, c.copyWith(barcodePrefix: v))),
              ToggleRow(
                icon: Icons.link,
                title: 'Barcode = SKU',
                subtitle: 'One value fills both the Barcode and the UPC/EAN/ISBN/SKU code',
                value: c.barcodeIsSku,
                onChanged: (v) => _apply(context, c.copyWith(barcodeIsSku: v)),
              ),
            ]),
            const SizedBox(height: 14),
            FormSectionCard(icon: Icons.point_of_sale_outlined, title: 'Point of sale', children: [
              ToggleRow(
                icon: Icons.visibility_off_outlined,
                title: 'Hide out-of-stock items',
                subtitle: canHideStock
                    ? 'Zero-stock products leave the New Sale catalog and can\'t be scanned in'
                    : 'Needs the Create Sale permission to change',
                value: c.hideOutOfStock,
                enabled: canHideStock,
                onChanged: (v) => _apply(context, c.copyWith(hideOutOfStock: v)),
              ),
            ]),
          ],
        );
      },
    );
  }
}

class _PrefixRow extends StatelessWidget {
  const _PrefixRow({required this.prefix, required this.onChanged});

  final String prefix;
  final ValueChanged<String> onChanged;

  Future<void> _edit(BuildContext context) async {
    final p = context.astra;
    final next = await showModalBottomSheet<String>(
      context: context,
      isScrollControlled: true,
      useSafeArea: true,
      backgroundColor: p.cardSolid,
      shape: const RoundedRectangleBorder(borderRadius: BorderRadius.vertical(top: Radius.circular(22))),
      builder: (_) => _PrefixSheet(initial: prefix),
    );
    if (next != null && next != prefix) onChanged(next);
  }

  @override
  Widget build(BuildContext context) {
    final p = context.astra;
    return GestureDetector(
      behavior: HitTestBehavior.opaque,
      onTap: () => _edit(context),
      child: Row(
        children: [
          Expanded(
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Text('Barcode prefix', style: ui(size: 13.5, weight: FontWeight.w700, color: p.ink)),
                Text('Put in front of every generated barcode · up to 10 characters',
                    style: ui(size: 11, weight: FontWeight.w500, color: p.textMuted)),
              ],
            ),
          ),
          Text(prefix.isEmpty ? 'None' : prefix,
              style: ui(size: 14, weight: FontWeight.w800, color: prefix.isEmpty ? p.textMuted : p.primary, letterSpacing: 1)),
          Icon(Icons.chevron_right, color: p.textMuted, size: 20),
        ],
      ),
    );
  }
}

class _PrefixSheet extends StatefulWidget {
  const _PrefixSheet({required this.initial});

  final String initial;

  @override
  State<_PrefixSheet> createState() => _PrefixSheetState();
}

class _PrefixSheetState extends State<_PrefixSheet> {
  late final _prefix = TextEditingController(text: widget.initial);

  @override
  void dispose() {
    _prefix.dispose();
    super.dispose();
  }

  @override
  Widget build(BuildContext context) {
    final p = context.astra;
    return Padding(
      padding: EdgeInsets.fromLTRB(20, 18, 20, MediaQuery.viewInsetsOf(context).bottom + 20),
      child: Column(
        mainAxisSize: MainAxisSize.min,
        crossAxisAlignment: CrossAxisAlignment.stretch,
        children: [
          Text('Barcode prefix', style: ui(size: 16, weight: FontWeight.w800, color: p.ink)),
          const SizedBox(height: 12),
          TextField(
            controller: _prefix,
            autofocus: true,
            maxLength: 10,
            textCapitalization: TextCapitalization.characters,
            style: ui(size: 16, weight: FontWeight.w800, color: p.ink, letterSpacing: 1.5),
            decoration: InputDecoration(
              hintText: 'e.g. LS',
              filled: true,
              fillColor: p.tint,
              border: OutlineInputBorder(borderRadius: BorderRadius.circular(12), borderSide: BorderSide.none),
            ),
            onSubmitted: (v) => Navigator.pop(context, v.trim()),
          ),
          const SizedBox(height: 8),
          SizedBox(
            height: 46,
            child: FilledButton(
              style: FilledButton.styleFrom(backgroundColor: p.primary, shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(13))),
              onPressed: () => Navigator.pop(context, _prefix.text.trim()),
              child: Text('Save', style: ui(size: 14, weight: FontWeight.w800, color: Colors.white)),
            ),
          ),
        ],
      ),
    );
  }
}
