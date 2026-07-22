import 'package:flutter/material.dart';
import 'package:go_router/go_router.dart';
import 'package:provider/provider.dart';

import 'package:invo/shared/domain/helpers/responsive.dart';
import 'package:invo/features/settings/logic/print_settings_cubit/print_settings_cubit.dart';
import 'package:invo/shared/utils/components/theme/index.dart';
import 'package:invo/shared/widgets/astra_widgets.dart';

/// Thermal-printer configuration. The receipt options (language, footers,
/// discount / total-quantity / barcode, Qty vs Weight label) follow the web
/// Settings → Sale Configuration — the server is the source of truth, so they
/// are shown read-only here and refresh on open. Only the paper width is a
/// device-local choice (each till can hold a different roll).
class PrintSettingsScreen extends StatefulWidget {
  const PrintSettingsScreen({super.key});

  @override
  State<PrintSettingsScreen> createState() => _PrintSettingsScreenState();
}

class _PrintSettingsScreenState extends State<PrintSettingsScreen> {
  @override
  void initState() {
    super.initState();
    // Pull the latest web configuration so changes made in Settings → Sale
    // Configuration show up immediately; offline keeps the cached values.
    context.read<PrintSettingsCubit>().syncFromServer();
  }

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
                    SectionLabel('Paper width'),
                    const SizedBox(height: 8),
                    _segment<PaperWidth>(
                      context,
                      options: PaperWidth.values,
                      selected: c.width,
                      labelOf: (w) => w.label,
                      onSelect: (w) => context.read<PrintSettingsCubit>().setWidth(w),
                    ),
                    const SizedBox(height: 20),
                    SectionLabel('Receipt options'),
                    Padding(
                      padding: const EdgeInsets.only(top: 6, left: 4, bottom: 8),
                      child: Row(
                        crossAxisAlignment: CrossAxisAlignment.start,
                        children: [
                          Icon(Icons.cloud_done_outlined, size: 13, color: p.textMuted),
                          const SizedBox(width: 5),
                          Expanded(
                            child: Text(
                              'Managed in web Settings → Sale Configuration and synced automatically.',
                              style: ui(size: 10.5, weight: FontWeight.w600, color: p.textMuted),
                            ),
                          ),
                        ],
                      ),
                    ),
                    _valueRow(
                      context,
                      icon: Icons.translate,
                      title: 'Print language',
                      subtitle: c.style.isArabic
                          ? 'English with Arabic labels & item names alongside'
                          : 'English only',
                      value: c.style.label,
                    ),
                    const SizedBox(height: 9),
                    _valueRow(
                      context,
                      icon: Icons.straighten,
                      title: 'Quantity label',
                      subtitle: 'Column and total row wording',
                      value: c.quantityLabel.column,
                    ),
                    const SizedBox(height: 9),
                    _flagRow(context,
                        icon: Icons.image_outlined,
                        title: 'Logo',
                        subtitle: 'Print the company logo',
                        value: c.showLogo),
                    const SizedBox(height: 9),
                    _flagRow(context,
                        icon: Icons.percent,
                        title: 'Discount',
                        subtitle: 'Print the discount line',
                        value: c.showDiscount),
                    const SizedBox(height: 9),
                    _flagRow(context,
                        icon: Icons.numbers,
                        title: 'Total quantity',
                        subtitle: 'Print the total item count',
                        value: c.showTotalQty),
                    const SizedBox(height: 9),
                    _flagRow(context,
                        icon: Icons.qr_code_2,
                        title: 'Barcode & QR',
                        subtitle: 'Print the barcode and QR code',
                        value: c.showBarcode),
                    const SizedBox(height: 20),
                    SectionLabel('Footer'),
                    const SizedBox(height: 8),
                    _footerCard(context, c),
                  ],
                ),
              ),
            ),
          ],
        ),
      ),
    );
  }

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

  /// Read-only row showing a server-managed choice as text.
  Widget _valueRow(
    BuildContext context, {
    required IconData icon,
    required String title,
    required String subtitle,
    required String value,
  }) =>
      _row(context, icon: icon, title: title, subtitle: subtitle, trailing: _pill(context, value, on: true));

  /// Read-only row showing a server-managed on/off flag.
  Widget _flagRow(
    BuildContext context, {
    required IconData icon,
    required String title,
    required String subtitle,
    required bool value,
  }) =>
      _row(context,
          icon: icon, title: title, subtitle: subtitle, trailing: _pill(context, value ? 'On' : 'Off', on: value));

  Widget _row(
    BuildContext context, {
    required IconData icon,
    required String title,
    required String subtitle,
    required Widget trailing,
  }) {
    final p = context.astra;
    return AstraCard(
      radius: 14,
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
          trailing,
        ],
      ),
    );
  }

  Widget _pill(BuildContext context, String text, {required bool on}) {
    final p = context.astra;
    return Container(
      padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 5),
      decoration: BoxDecoration(
        color: on ? p.tint : p.hairline,
        borderRadius: BorderRadius.circular(20),
      ),
      child: Text(text,
          style: ui(size: 10.5, weight: FontWeight.w700, color: on ? p.ink : p.textMuted)),
    );
  }

  /// Read-only preview of the configured footer message(s).
  Widget _footerCard(BuildContext context, PrintSettingsCubit c) {
    final p = context.astra;
    final en = c.footerEnglish.trim();
    final ar = c.footerArabic.trim();
    return AstraCard(
      radius: 14,
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          if (en.isEmpty && (!c.style.isArabic || ar.isEmpty))
            Text('No footer message configured.',
                style: ui(size: 10.5, weight: FontWeight.w600, color: p.textMuted))
          else ...[
            if (en.isNotEmpty)
              Text(en, style: ui(size: 11, weight: FontWeight.w600, color: p.ink)),
            if (c.style.isArabic && ar.isNotEmpty) ...[
              if (en.isNotEmpty) const SizedBox(height: 6),
              Align(
                alignment: Alignment.centerRight,
                child: Text(ar,
                    textDirection: TextDirection.rtl,
                    style: ui(size: 11, weight: FontWeight.w600, color: p.ink)),
              ),
            ],
          ],
        ],
      ),
    );
  }
}
