import 'package:flutter/material.dart';
import 'package:go_router/go_router.dart';
import 'package:provider/provider.dart';

import 'package:invo/features/auth/logic/auth_cubit/auth_cubit.dart';
import 'package:invo/shared/domain/constants/mobile_permissions.dart';
import 'package:invo/shared/domain/helpers/responsive.dart';
import 'package:invo/features/settings/logic/print_settings_cubit/print_settings_cubit.dart';
import 'package:invo/features/settings/screens/v3/printer_picker_sheet.dart';
import 'package:invo/shared/utils/components/theme/index.dart';
import 'package:invo/shared/widgets/astra_widgets.dart';
import 'package:invo/shared/widgets/receipt_printer.dart';
import 'package:invo/shared/widgets/astra_snack.dart';

/// Thermal-printer configuration. The receipt options (language, footers,
/// the show-on-receipt toggles, Qty vs Weight label) live in the shared web
/// Settings → Sale Configuration: they sync down on open, and users holding
/// `configuration.settings` (the web Settings page gate) can edit them here —
/// click-and-go, written straight back to the web config. Everyone else sees
/// them read-only. The "This device" block (paper width, auto-print and the
/// paired printer) is local to the till.
class PrintSettingsScreen extends StatefulWidget {
  const PrintSettingsScreen({super.key, this.embedded = false});

  /// Rendered inside the tablet Settings detail pane rather than pushed as its
  /// own page: body only, no scaffold, no header, no back arrow. The host owns
  /// the frame, so opening this from Settings never leaves Settings.
  final bool embedded;

  @override
  State<PrintSettingsScreen> createState() => _PrintSettingsScreenState();
}

class _PrintSettingsScreenState extends State<PrintSettingsScreen> {
  bool _testing = false;

  @override
  void initState() {
    super.initState();
    // Pull the latest web configuration so changes made in Settings → Sale
    // Configuration show up immediately; offline keeps the cached values.
    context.read<PrintSettingsCubit>().syncFromServer();
  }

  /// Awaits a cubit save and toasts when it was rolled back (offline / server
  /// rejected).
  Future<void> _apply(Future<bool> save) async {
    final ok = await save;
    if (!ok && mounted) {
      AstraSnack.error(context, 'Couldn\'t save — check your connection.');
    }
  }

  @override
  Widget build(BuildContext context) {
    final p = context.astra;
    final c = context.watch<PrintSettingsCubit>();
    final canEdit = context.read<AuthCubit>().hasPermission(PermissionSlug.settings);

    // Built either way: embedded it IS the screen, pushed it is the scroll
    // inside the page frame.
    final body = ListView(
      shrinkWrap: widget.embedded,
      physics: widget.embedded ? const NeverScrollableScrollPhysics() : null,
      padding: widget.embedded
          ? EdgeInsets.zero
          : const EdgeInsets.fromLTRB(16, 16, 16, 40),
      children: [
            SectionLabel('This device'),
            const SizedBox(height: 8),
            _label(context, 'Paper width'),
            const SizedBox(height: 8),
            _segment<PaperWidth>(
              context,
              options: PaperWidth.values,
              selected: c.width,
              labelOf: (w) => w.label,
              onSelect: (w) => context.read<PrintSettingsCubit>().setWidth(w),
            ),
            const SizedBox(height: 14),
            _flagRow(context,
                icon: Icons.bolt_outlined,
                title: 'Auto-print after charge',
                subtitle: 'Print the receipt the moment a sale is charged',
                value: c.autoPrint,
                onChanged: (v) => context.read<PrintSettingsCubit>().setAutoPrint(v)),
            if (c.autoPrint) ...[
              const SizedBox(height: 9),
              _printerRow(context, c),
              const SizedBox(height: 9),
              _testPrintRow(context, c),
              const SizedBox(height: 9),
              _flagRow(context,
                  icon: Icons.replay_outlined,
                  title: 'Back to New Sale',
                  subtitle: c.printsSilently
                      ? 'Skip the invoice screen once the receipt prints'
                      : 'Needs a direct printer link',
                  value: c.skipInvoice,
                  // Only meaningful when the receipt actually prints on
                  // its own — with the system dialog in the way there is
                  // no silent print to skip ahead from.
                  onChanged: c.printsSilently
                      ? (v) => context.read<PrintSettingsCubit>().setSkipInvoice(v)
                      : null),
            ],
            const SizedBox(height: 20),
            SectionLabel('Receipt options'),
            Padding(
              padding: const EdgeInsets.only(top: 6, left: 4, bottom: 8),
              child: Row(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  Icon(canEdit ? Icons.cloud_sync_outlined : Icons.cloud_done_outlined,
                      size: 13, color: p.textMuted),
                  const SizedBox(width: 5),
                  Expanded(
                    child: Text(
                      canEdit
                          ? 'Shared with the web Sale Configuration — changes here update the web invoice print too.'
                          : 'Managed in web Settings → Sale Configuration and synced automatically.',
                      style: ui(size: 10.5, weight: FontWeight.w600, color: p.textMuted),
                    ),
                  ),
                ],
              ),
            ),
            if (canEdit) ...[
              _label(context, 'Print language'),
              const SizedBox(height: 8),
              _segment<PrintStyle>(
                context,
                options: PrintStyle.values,
                selected: c.style,
                labelOf: (s) => s.label,
                onSelect: (s) => _apply(context.read<PrintSettingsCubit>().setStyle(s)),
              ),
              const SizedBox(height: 14),
              _label(context, 'Quantity label'),
              const SizedBox(height: 8),
              _segment<QuantityLabel>(
                context,
                options: QuantityLabel.values,
                selected: c.quantityLabel,
                labelOf: (q) => q.column,
                onSelect: (q) => _apply(context.read<PrintSettingsCubit>().setQuantityLabel(q)),
              ),
              const SizedBox(height: 14),
            ] else ...[
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
            ],
            _flagRow(context,
                icon: Icons.image_outlined,
                title: 'Logo',
                subtitle: 'Print the company logo',
                value: c.showLogo,
                onChanged: canEdit
                    ? (v) => _apply(context.read<PrintSettingsCubit>().setShowLogo(v))
                    : null),
            const SizedBox(height: 9),
            _flagRow(context,
                icon: Icons.storefront_outlined,
                title: 'Company name',
                subtitle: c.showCompanyName && c.companyName.trim().isNotEmpty
                    ? c.companyName.trim()
                    : 'Print the company name',
                value: c.showCompanyName,
                onChanged: canEdit
                    ? (v) => _apply(context.read<PrintSettingsCubit>().setShowCompanyName(v))
                    : null),
            const SizedBox(height: 9),
            _flagRow(context,
                icon: Icons.percent,
                title: 'Discount',
                subtitle: 'Print the discount line',
                value: c.showDiscount,
                onChanged: canEdit
                    ? (v) => _apply(context.read<PrintSettingsCubit>().setShowDiscount(v))
                    : null),
            const SizedBox(height: 9),
            _flagRow(context,
                icon: Icons.numbers,
                title: 'Total quantity',
                subtitle: 'Print the total item count',
                value: c.showTotalQty,
                onChanged: canEdit
                    ? (v) => _apply(context.read<PrintSettingsCubit>().setShowTotalQty(v))
                    : null),
            const SizedBox(height: 9),
            _flagRow(context,
                icon: Icons.qr_code_2,
                title: 'Barcode & QR',
                subtitle: 'Print the barcode and QR code',
                value: c.showBarcode,
                onChanged: canEdit
                    ? (v) => _apply(context.read<PrintSettingsCubit>().setShowBarcode(v))
                    : null),
            const SizedBox(height: 20),
            SectionLabel('Footer'),
            const SizedBox(height: 8),
        _footerCard(context, c, canEdit),
      ],
    );

    // Hosted in the settings detail pane, which already carries the head and
    // the framing — so this hands back the body alone rather than a page. It is
    // the same screen either way, never a second copy of these controls.
    if (widget.embedded) return body;

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
            Expanded(child: MaxWidthBox(maxWidth: 560, child: body)),
          ],
        ),
      ),
    );
  }

  Widget _label(BuildContext context, String text) => SectionLabel(text);

  /// The printer this till is paired with. Tapping opens the picker, which
  /// lists every printer this device can reach over any transport; the
  /// trailing ✕ un-pairs back to the system print dialog.
  Widget _printerRow(BuildContext context, PrintSettingsCubit c) {
    final p = context.astra;
    final printer = c.printer;
    final paired = printer.isPaired;
    return _row(
      context,
      icon: Icons.print_outlined,
      title: 'Printer',
      subtitle: !paired
          ? 'Tap to choose the printer at this till'
          : printer.isDirect
              ? '${printer.displayName} · ${printer.transport.label}'
              : '${printer.displayName} · via the system print dialog',
      onTap: () => _pickPrinter(context, c),
      trailing: paired
          ? GestureDetector(
              onTap: () => c.setPrinter(null),
              behavior: HitTestBehavior.opaque,
              child: Padding(
                padding: const EdgeInsets.symmetric(horizontal: 6, vertical: 4),
                child: Icon(Icons.close_rounded, size: 17, color: p.textMuted),
              ),
            )
          : _pill(context, 'Choose', on: false),
    );
  }

  Widget _testPrintRow(BuildContext context, PrintSettingsCubit c) => _row(
        context,
        icon: Icons.receipt_long_outlined,
        title: 'Test print',
        subtitle: 'Print a sample slip to check the pairing',
        onTap: _testing ? null : () => _testPrint(c),
        trailing: _pill(context, _testing ? 'Printing…' : 'Print', on: !_testing),
      );

  Future<void> _pickPrinter(BuildContext context, PrintSettingsCubit c) async {
    final chosen = await showPrinterPickerSheet(context, current: c.printer);
    if (chosen == null) return; // backed out — keep the current pairing
    await c.setPrinter(chosen.isPaired ? chosen : null);
  }

  /// Prints the sample slip down the same path a real receipt takes, so a
  /// success here means charging a sale will print too.
  Future<void> _testPrint(PrintSettingsCubit c) async {
    setState(() => _testing = true);
    final result = await printTestReceipt(c.snapshot, target: c.printer);
    if (!mounted) return;
    setState(() => _testing = false);
    if (result == ReceiptPrintResult.cancelled) return;
    if (result.ok) {
      AstraSnack.success(context, 'Test slip sent to the printer.');
    } else {
      _toast('Couldn\'t reach the printer.');
    }
  }

  void _toast(String message) {
    AstraSnack.error(context, message);
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

  /// On/off flag row — a live switch when [onChanged] is given (editors),
  /// otherwise a read-only status pill.
  Widget _flagRow(
    BuildContext context, {
    required IconData icon,
    required String title,
    required String subtitle,
    required bool value,
    void Function(bool)? onChanged,
  }) =>
      _row(context,
          icon: icon,
          title: title,
          subtitle: subtitle,
          onTap: onChanged == null ? null : () => onChanged(!value),
          trailing: onChanged == null
              ? _pill(context, value ? 'On' : 'Off', on: value)
              : _switch(context, value));

  Widget _row(
    BuildContext context, {
    required IconData icon,
    required String title,
    required String subtitle,
    required Widget trailing,
    VoidCallback? onTap,
  }) {
    final p = context.astra;
    return AstraCard(
      radius: 14,
      onTap: onTap,
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

  /// The configured footer message(s) — tappable for editors, preview-only
  /// otherwise.
  Widget _footerCard(BuildContext context, PrintSettingsCubit c, bool canEdit) {
    final p = context.astra;
    final en = c.footerEnglish.trim();
    final ar = c.footerArabic.trim();
    return AstraCard(
      radius: 14,
      onTap: canEdit ? () => _editFooters(context, c) : null,
      child: Row(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Expanded(
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                if (en.isEmpty && (!c.style.isArabic || ar.isEmpty))
                  Text(canEdit ? 'No footer message — tap to add one.' : 'No footer message configured.',
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
          ),
          if (canEdit) ...[
            const SizedBox(width: 8),
            Icon(Icons.edit_outlined, size: 15, color: p.textMuted),
          ],
        ],
      ),
    );
  }

  /// Bottom sheet with the two footer messages; saves both in one go.
  void _editFooters(BuildContext context, PrintSettingsCubit c) {
    final p = context.astra;
    showModalBottomSheet<void>(
      context: context,
      isScrollControlled: true,
      backgroundColor: p.card,
      shape: const RoundedRectangleBorder(
        borderRadius: BorderRadius.vertical(top: Radius.circular(22)),
      ),
      builder: (sheetContext) => _FooterSheet(
        english: c.footerEnglish,
        arabic: c.footerArabic,
        onSave: (en, ar) => _apply(c.setFooters(english: en, arabic: ar)),
      ),
    );
  }
}

/// Stateful so the controllers live exactly as long as the sheet's own element.
/// Disposing them from `showModalBottomSheet(...).whenComplete` instead fires
/// while the dismissal animation is still rebuilding the fields, which throws
/// "A TextEditingController was used after being disposed".
class _FooterSheet extends StatefulWidget {
  const _FooterSheet({required this.english, required this.arabic, required this.onSave});

  final String english;
  final String arabic;
  final void Function(String english, String arabic) onSave;

  @override
  State<_FooterSheet> createState() => _FooterSheetState();
}

class _FooterSheetState extends State<_FooterSheet> {
  late final _enCtrl = TextEditingController(text: widget.english);
  late final _arCtrl = TextEditingController(text: widget.arabic);

  @override
  void dispose() {
    _enCtrl.dispose();
    _arCtrl.dispose();
    super.dispose();
  }

  void _save() {
    final en = _enCtrl.text.trim();
    final ar = _arCtrl.text.trim();
    Navigator.of(context).pop();
    widget.onSave(en, ar);
  }

  @override
  Widget build(BuildContext context) {
    final p = context.astra;
    return Padding(
      padding: EdgeInsets.fromLTRB(16, 16, 16, 16 + MediaQuery.of(context).viewInsets.bottom),
      child: Column(
        mainAxisSize: MainAxisSize.min,
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Text('Receipt footer', style: ui(size: 14, weight: FontWeight.w800, color: p.ink)),
          const SizedBox(height: 2),
          Text('Printed at the bottom of every receipt — web invoices too.',
              style: ui(size: 10.5, weight: FontWeight.w600, color: p.textMuted)),
          const SizedBox(height: 14),
          _footerField(controller: _enCtrl, label: 'English'),
          const SizedBox(height: 10),
          _footerField(controller: _arCtrl, label: 'Arabic', rtl: true),
          const SizedBox(height: 14),
          SizedBox(
            width: double.infinity,
            child: GestureDetector(
              onTap: _save,
              child: Container(
                padding: const EdgeInsets.symmetric(vertical: 13),
                alignment: Alignment.center,
                decoration: BoxDecoration(
                  gradient: p.primaryGradient,
                  borderRadius: BorderRadius.circular(13),
                ),
                child: Text('Save footer',
                    style: ui(size: 13, weight: FontWeight.w800, color: Colors.white)),
              ),
            ),
          ),
        ],
      ),
    );
  }

  Widget _footerField({
    required TextEditingController controller,
    required String label,
    bool rtl = false,
  }) {
    final p = context.astra;
    return Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        Text(label, style: ui(size: 10.5, weight: FontWeight.w700, color: p.textMuted)),
        const SizedBox(height: 5),
        Container(
          decoration: BoxDecoration(
            color: p.tint,
            borderRadius: BorderRadius.circular(12),
          ),
          padding: const EdgeInsets.symmetric(horizontal: 12),
          child: TextField(
            controller: controller,
            maxLines: 2,
            minLines: 1,
            textDirection: rtl ? TextDirection.rtl : TextDirection.ltr,
            style: ui(size: 12, weight: FontWeight.w600, color: p.ink),
            decoration: const InputDecoration(border: InputBorder.none),
          ),
        ),
      ],
    );
  }
}
