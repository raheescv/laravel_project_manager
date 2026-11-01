import 'package:flutter/material.dart';
import 'package:flutter/services.dart';

import 'package:invo/shared/utils/components/theme/index.dart';
import 'package:invo/shared/widgets/astra_widgets.dart';

import '../../domain/models/catalog_models.dart';
import '../../logic/catalog_form_cubit/catalog_form_cubit.dart';

/// One titled card of the Add / Edit form.
class FormSectionCard extends StatelessWidget {
  const FormSectionCard({super.key, required this.icon, required this.title, required this.children, this.trailing});

  final IconData icon;
  final String title;
  final Widget? trailing;
  final List<Widget> children;

  @override
  Widget build(BuildContext context) {
    final p = context.astra;
    return Container(
      padding: const EdgeInsets.fromLTRB(14, 12, 14, 14),
      decoration: BoxDecoration(color: p.cardSolid, borderRadius: BorderRadius.circular(18), boxShadow: context.astraTheme.cardShadow),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.stretch,
        children: [
          Row(
            children: [
              IconChip(icon: icon, size: 30, radius: 10, iconSize: 17, bg: p.tint),
              const SizedBox(width: 10),
              Expanded(child: Text(title, style: ui(size: 14, weight: FontWeight.w800, color: p.ink))),
              if (trailing != null) trailing!,
            ],
          ),
          const SizedBox(height: 12),
          for (var i = 0; i < children.length; i++) ...[if (i > 0) const SizedBox(height: 11), children[i]],
        ],
      ),
    );
  }
}

/// A field label with an optional required mark and error line under [child].
class LabeledField extends StatelessWidget {
  const LabeledField({super.key, required this.label, required this.child, this.required = false, this.error});

  final String label;
  final Widget child;
  final bool required;
  final String? error;

  @override
  Widget build(BuildContext context) {
    final p = context.astra;
    return Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        Text.rich(
          TextSpan(text: label, children: [
            if (required) const TextSpan(text: ' *', style: TextStyle(color: AstraPalette.danger)),
          ]),
          style: ui(size: 11.5, weight: FontWeight.w700, color: p.textSecondary),
        ),
        const SizedBox(height: 5),
        child,
        if (error != null)
          Padding(
            padding: const EdgeInsets.only(top: 4),
            child: Text(error!, style: ui(size: 11, weight: FontWeight.w600, color: AstraPalette.danger)),
          ),
      ],
    );
  }
}

/// The form's text input: filled, borderless, primary ring on focus.
class InputBox extends StatelessWidget {
  const InputBox({
    super.key,
    required this.controller,
    this.hint,
    this.numeric = false,
    this.decimal = true,
    this.rtl = false,
    this.lines = 1,
    this.suffix,
    this.hasError = false,
  });

  final TextEditingController controller;
  final String? hint;
  final bool numeric;

  /// With [numeric]: allow a decimal point.
  final bool decimal;
  final bool rtl;
  final int lines;
  final Widget? suffix;
  final bool hasError;

  @override
  Widget build(BuildContext context) {
    final p = context.astra;
    OutlineInputBorder border(Color c) => OutlineInputBorder(borderRadius: BorderRadius.circular(12), borderSide: BorderSide(color: c, width: 1.5));
    return TextField(
      controller: controller,
      maxLines: lines,
      minLines: lines,
      textDirection: rtl ? TextDirection.rtl : null,
      textAlign: rtl ? TextAlign.right : TextAlign.start,
      keyboardType: numeric ? TextInputType.numberWithOptions(decimal: decimal) : (lines > 1 ? TextInputType.multiline : null),
      inputFormatters: numeric ? [FilteringTextInputFormatter.allow(RegExp(decimal ? r'[0-9.]' : r'[0-9]'))] : null,
      style: ui(size: 14, weight: FontWeight.w600, color: p.ink),
      decoration: InputDecoration(
        hintText: hint,
        hintStyle: ui(size: 13.5, weight: FontWeight.w500, color: p.textMuted),
        isDense: true,
        filled: true,
        fillColor: p.tint,
        contentPadding: const EdgeInsets.symmetric(horizontal: 12, vertical: 12),
        suffixIcon: suffix,
        enabledBorder: border(hasError ? AstraPalette.danger : Colors.transparent),
        focusedBorder: border(hasError ? AstraPalette.danger : p.primary),
      ),
    );
  }
}

/// Looks like an input; opens a picker sheet.
class PickerBox extends StatelessWidget {
  const PickerBox({super.key, required this.value, required this.placeholder, required this.onTap, this.hasError = false});

  final String? value;
  final String placeholder;
  final VoidCallback onTap;
  final bool hasError;

  @override
  Widget build(BuildContext context) {
    final p = context.astra;
    final empty = value == null || value!.isEmpty;
    return GestureDetector(
      onTap: onTap,
      child: Container(
        height: 44,
        padding: const EdgeInsets.only(left: 12, right: 8),
        decoration: BoxDecoration(
          color: p.tint,
          borderRadius: BorderRadius.circular(12),
          border: Border.all(color: hasError ? AstraPalette.danger : Colors.transparent, width: 1.5),
        ),
        child: Row(
          children: [
            Expanded(
              child: Text(empty ? placeholder : value!,
                  maxLines: 1,
                  overflow: TextOverflow.ellipsis,
                  style: ui(size: 14, weight: empty ? FontWeight.w500 : FontWeight.w600, color: empty ? p.textMuted : p.ink)),
            ),
            Icon(Icons.expand_more, size: 20, color: p.textMuted),
          ],
        ),
      ),
    );
  }
}

/// Two fields side by side, or stacked when there's no room.
class FieldPair extends StatelessWidget {
  const FieldPair(this.first, this.second, {super.key});

  final Widget first;
  final Widget second;

  @override
  Widget build(BuildContext context) => LayoutBuilder(
        builder: (context, box) => box.maxWidth < 300
            ? Column(children: [first, const SizedBox(height: 11), second])
            : Row(crossAxisAlignment: CrossAxisAlignment.start, children: [
                Expanded(child: first),
                const SizedBox(width: 10),
                Expanded(child: second),
              ]),
      );
}

/// Worked-out figures under a price field.
class CalcStrip extends StatelessWidget {
  const CalcStrip({super.key, required this.items});

  final List<({String label, String value, bool good})> items;

  @override
  Widget build(BuildContext context) {
    final p = context.astra;
    return Container(
      decoration: BoxDecoration(color: p.tint, borderRadius: BorderRadius.circular(12)),
      child: IntrinsicHeight(
        child: Row(
          children: [
            for (var i = 0; i < items.length; i++) ...[
              if (i > 0) VerticalDivider(width: 1, thickness: 1, color: p.hairline),
              Expanded(
                child: Padding(
                  padding: const EdgeInsets.symmetric(horizontal: 11, vertical: 9),
                  child: Column(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: [
                      Text(items[i].label.toUpperCase(),
                          maxLines: 1,
                          overflow: TextOverflow.ellipsis,
                          style: ui(size: 9, weight: FontWeight.w800, color: p.textMuted, letterSpacing: 0.5)),
                      const SizedBox(height: 3),
                      FittedBox(
                        fit: BoxFit.scaleDown,
                        alignment: Alignment.centerLeft,
                        child: Text(items[i].value,
                            style: ui(size: 14, weight: FontWeight.w800, color: items[i].good ? AstraPalette.success : p.ink)),
                      ),
                    ],
                  ),
                ),
              ),
            ],
          ],
        ),
      ),
    );
  }
}

/// How the item will look on the POS, updated as the form is typed.
class LivePreviewCard extends StatelessWidget {
  const LivePreviewCard({super.key, required this.type, required this.name, required this.subtitle, required this.price, required this.badge, this.thumbnail = ''});

  final CatalogType type;
  final String name;
  final String subtitle;
  final String price;
  final String badge;
  final String thumbnail;

  @override
  Widget build(BuildContext context) {
    final p = context.astra;
    final white70 = Colors.white.withValues(alpha: 0.72);
    return Container(
      padding: const EdgeInsets.all(14),
      decoration: BoxDecoration(gradient: p.heroGradient, borderRadius: BorderRadius.circular(20), boxShadow: context.astraTheme.cardShadow),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.stretch,
        children: [
          Row(
            children: [
              Icon(Icons.visibility_outlined, size: 15, color: white70),
              const SizedBox(width: 6),
              Expanded(child: Text('LIVE POS PREVIEW', style: ui(size: 10, weight: FontWeight.w800, color: white70, letterSpacing: 1.2))),
              Container(
                padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 3),
                decoration: BoxDecoration(color: Colors.white.withValues(alpha: 0.18), borderRadius: BorderRadius.circular(20)),
                child: Text(badge.toUpperCase(), style: ui(size: 9.5, weight: FontWeight.w800, color: Colors.white, letterSpacing: 0.6)),
              ),
            ],
          ),
          const SizedBox(height: 10),
          Container(
            padding: const EdgeInsets.all(10),
            decoration: BoxDecoration(
              color: Colors.white.withValues(alpha: 0.12),
              borderRadius: BorderRadius.circular(15),
              border: Border.all(color: Colors.white.withValues(alpha: 0.18)),
            ),
            child: Row(
              children: [
                ProductThumb(url: thumbnail, fallbackIcon: type.icon, size: 44, radius: 12),
                const SizedBox(width: 11),
                Expanded(
                  child: Column(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: [
                      Text(name.isEmpty ? '${type.label} name' : name,
                          maxLines: 2, overflow: TextOverflow.ellipsis, style: ui(size: 14, weight: FontWeight.w800, color: Colors.white)),
                      const SizedBox(height: 2),
                      Text(subtitle, maxLines: 1, overflow: TextOverflow.ellipsis, style: ui(size: 11.5, weight: FontWeight.w600, color: white70)),
                    ],
                  ),
                ),
                const SizedBox(width: 8),
                Text(price, style: serif(size: 18, color: Colors.white)),
              ],
            ),
          ),
        ],
      ),
    );
  }
}

/// The item's photos: saved ones, ones waiting for the first save, and an add tile.
class PhotoGrid extends StatelessWidget {
  const PhotoGrid({
    super.key,
    required this.type,
    required this.images,
    required this.pending,
    required this.canEdit,
    required this.onAdd,
    required this.onRemove,
    required this.onRemovePending,
  });

  final CatalogType type;
  final List<CatalogImage> images;
  final List<PendingPhoto> pending;
  final bool canEdit;
  final VoidCallback onAdd;
  final ValueChanged<CatalogImage> onRemove;
  final ValueChanged<int> onRemovePending;

  @override
  Widget build(BuildContext context) {
    final p = context.astra;
    return LayoutBuilder(builder: (context, box) {
      final size = (box.maxWidth - 3 * 8) / 4;
      Widget removable(Widget child, VoidCallback onRemove, {bool cover = false}) => SizedBox(
            width: size,
            height: size,
            child: Stack(children: [
              Positioned.fill(child: child),
              if (cover)
                Positioned(
                  left: 5,
                  bottom: 5,
                  child: Container(
                    padding: const EdgeInsets.symmetric(horizontal: 6, vertical: 2),
                    decoration: BoxDecoration(color: Colors.black.withValues(alpha: 0.5), borderRadius: BorderRadius.circular(10)),
                    child: Text('Cover', style: ui(size: 9, weight: FontWeight.w800, color: Colors.white)),
                  ),
                ),
              if (canEdit)
                Positioned(
                  right: 4,
                  top: 4,
                  child: GestureDetector(
                    onTap: onRemove,
                    child: Container(
                      width: 24,
                      height: 24,
                      decoration: BoxDecoration(color: Colors.black.withValues(alpha: 0.55), shape: BoxShape.circle),
                      child: const Icon(Icons.close, size: 15, color: Colors.white),
                    ),
                  ),
                ),
            ]),
          );
      return Wrap(
        spacing: 8,
        runSpacing: 8,
        children: [
          for (var i = 0; i < images.length; i++)
            removable(ProductThumb(url: images[i].path, fallbackIcon: type.icon, size: size, radius: 13), () => onRemove(images[i]), cover: i == 0),
          for (var i = 0; i < pending.length; i++)
            removable(
              ClipRRect(borderRadius: BorderRadius.circular(13), child: Image.memory(pending[i].bytes, fit: BoxFit.cover, cacheWidth: (size * 3).round())),
              () => onRemovePending(i),
              cover: images.isEmpty && i == 0,
            ),
          if (canEdit)
            GestureDetector(
              onTap: onAdd,
              child: Container(
                width: size,
                height: size,
                decoration: BoxDecoration(
                  borderRadius: BorderRadius.circular(13),
                  border: Border.all(color: p.primary.withValues(alpha: 0.4), width: 1.5),
                ),
                child: Column(
                  mainAxisAlignment: MainAxisAlignment.center,
                  children: [
                    Icon(Icons.add_a_photo_outlined, size: 22, color: p.primary),
                    const SizedBox(height: 3),
                    Text('Add', style: ui(size: 11, weight: FontWeight.w700, color: p.primary)),
                  ],
                ),
              ),
            ),
        ],
      );
    });
  }
}
