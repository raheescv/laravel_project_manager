import 'package:flutter/material.dart';
import 'package:go_router/go_router.dart';
import 'package:provider/provider.dart';

import 'package:invo/shared/domain/helpers/responsive.dart';
import 'package:invo/features/settings/logic/print_settings_cubit/print_settings_cubit.dart';
import 'package:invo/shared/utils/components/theme/index.dart';
import 'package:invo/shared/widgets/astra_widgets.dart';

/// In-app thermal-printer configuration. Mirrors the web sale-print options
/// (resources/views/sale/print.blade.php): English-only vs bilingual Arabic,
/// roll width and the discount / total-quantity toggles.
/// Click-and-go — every change applies and persists instantly.
class PrintSettingsScreen extends StatelessWidget {
  const PrintSettingsScreen({super.key});

  @override
  Widget build(BuildContext context) {
    final p = context.astra;
    final c = context.watch<PrintSettingsCubit>();

    return Scaffold(
      backgroundColor: Colors.transparent,
      body: AstraBackground(
        child: Column(
          children: [
            EmeraldHeader(
              title: 'Printer & Receipt',
              subtitle: 'Thermal print · 58mm / 80mm',
              leading: HeaderIconButton(icon: Icons.arrow_back, onTap: () => context.pop()),
            ),
            Expanded(
              child: MaxWidthBox(
                maxWidth: 560,
                child: ListView(
                  padding: const EdgeInsets.fromLTRB(16, 16, 16, 40),
                  children: [
                    _label(context, 'Print language'),
                    const SizedBox(height: 8),
                    _segment<PrintStyle>(
                      context,
                      options: PrintStyle.values,
                      selected: c.style,
                      labelOf: (s) => s.label,
                      onSelect: (s) => context.read<PrintSettingsCubit>().setStyle(s),
                    ),
                    Padding(
                      padding: const EdgeInsets.only(top: 6, left: 4),
                      child: Text(
                        c.style.isArabic
                            ? 'Receipt prints English with Arabic labels & item names alongside.'
                            : 'Receipt prints in English only.',
                        style: ui(size: 10.5, weight: FontWeight.w600, color: p.textMuted),
                      ),
                    ),
                    const SizedBox(height: 20),
                    _label(context, 'Paper width'),
                    const SizedBox(height: 8),
                    _segment<PaperWidth>(
                      context,
                      options: PaperWidth.values,
                      selected: c.width,
                      labelOf: (w) => w.label,
                      onSelect: (w) => context.read<PrintSettingsCubit>().setWidth(w),
                    ),
                    const SizedBox(height: 20),
                    _label(context, 'Show on receipt'),
                    const SizedBox(height: 8),
                    _toggle(
                      context,
                      icon: Icons.percent,
                      title: 'Discount',
                      subtitle: 'Print the discount line',
                      value: c.showDiscount,
                      onChanged: (v) => context.read<PrintSettingsCubit>().setShowDiscount(v),
                    ),
                    const SizedBox(height: 9),
                    _toggle(
                      context,
                      icon: Icons.numbers,
                      title: 'Total quantity',
                      subtitle: 'Print the total item count',
                      value: c.showTotalQty,
                      onChanged: (v) => context.read<PrintSettingsCubit>().setShowTotalQty(v),
                    ),
                    const SizedBox(height: 9),
                    _toggle(
                      context,
                      icon: Icons.qr_code_2,
                      title: 'Barcode & QR',
                      subtitle: 'Print the barcode and QR code',
                      value: c.showBarcode,
                      onChanged: (v) => context.read<PrintSettingsCubit>().setShowBarcode(v),
                    ),
                  ],
                ),
              ),
            ),
          ],
        ),
      ),
    );
  }

  Widget _label(BuildContext context, String text) => SectionLabel(text);

  /// A two/three-way pill selector. Tapping applies instantly.
  Widget _segment<T>(
    BuildContext context, {
    required List<T> options,
    required T selected,
    required String Function(T) labelOf,
    required void Function(T) onSelect,
  }) {
    final p = context.astra;
    final t = context.astraTheme;
    return Container(
      padding: const EdgeInsets.all(4),
      decoration: BoxDecoration(
        color: p.card,
        borderRadius: BorderRadius.circular(14),
        boxShadow: t.softShadow,
      ),
      child: Row(
        children: [
          for (final o in options)
            Expanded(
              child: GestureDetector(
                onTap: () => onSelect(o),
                behavior: HitTestBehavior.opaque,
                child: AnimatedContainer(
                  duration: const Duration(milliseconds: 160),
                  padding: const EdgeInsets.symmetric(vertical: 11),
                  alignment: Alignment.center,
                  decoration: BoxDecoration(
                    gradient: o == selected ? p.primaryGradient : null,
                    borderRadius: BorderRadius.circular(11),
                  ),
                  child: Text(
                    labelOf(o),
                    style: ui(
                      size: 12.5,
                      weight: FontWeight.w700,
                      color: o == selected ? Colors.white : p.textSecondary,
                    ),
                  ),
                ),
              ),
            ),
        ],
      ),
    );
  }

  Widget _toggle(
    BuildContext context, {
    required IconData icon,
    required String title,
    required String subtitle,
    required bool value,
    required void Function(bool) onChanged,
  }) {
    final p = context.astra;
    return AstraCard(
      radius: 14,
      onTap: () => onChanged(!value),
      child: Row(
        children: [
          IconChip(icon: icon, size: 32, radius: 9, bg: p.tint),
          const SizedBox(width: 11),
          Expanded(
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Text(title, style: ui(size: 12.5, weight: FontWeight.w700, color: p.ink)),
                Text(subtitle, style: ui(size: 10, weight: FontWeight.w600, color: p.textMuted)),
              ],
            ),
          ),
          _switch(context, value),
        ],
      ),
    );
  }

  Widget _switch(BuildContext context, bool value) {
    final p = context.astra;
    return AnimatedContainer(
      duration: const Duration(milliseconds: 160),
      width: 44,
      height: 26,
      padding: const EdgeInsets.all(3),
      alignment: value ? Alignment.centerRight : Alignment.centerLeft,
      decoration: BoxDecoration(
        gradient: value ? p.primaryGradient : null,
        color: value ? null : p.hairline,
        borderRadius: BorderRadius.circular(20),
      ),
      child: Container(
        width: 20,
        height: 20,
        decoration: const BoxDecoration(color: Colors.white, shape: BoxShape.circle),
      ),
    );
  }
}
