import 'package:flutter/material.dart';
import 'package:go_router/go_router.dart';
import 'package:provider/provider.dart';

import '../../core/currency.dart';
import '../../core/responsive.dart';
import '../../state/auth_controller.dart';
import '../../state/branch_controller.dart';
import '../../state/currency_controller.dart';
import '../../state/print_settings_controller.dart';
import '../../state/theme_controller.dart';
import '../../theme/palette.dart';
import '../../theme/theme.dart';
import '../../widgets/astra_widgets.dart';
import '../auth/connection_sheet.dart';
import 'appearance_sheet.dart';
import 'branch_sheet.dart';
import 'currency_sheet.dart';
import 'theme_sheet.dart';

class SettingsScreen extends StatelessWidget {
  const SettingsScreen({super.key});

  @override
  Widget build(BuildContext context) {
    final p = context.astra;
    final theme = context.watch<ThemeController>();
    final currency = context.watch<CurrencyController>().currency;
    final branch = context.watch<BranchController>();

    return Scaffold(
      backgroundColor: Colors.transparent,
      body: AstraBackground(
        child: Column(
          children: [
            const EmeraldHeader(title: 'Settings'),
            Expanded(
              child: MaxWidthBox(
                maxWidth: 560,
                child: ListView(
                padding: const EdgeInsets.fromLTRB(16, 16, 16, 110),
                children: [
                  _presetCard(context, theme),
                  const SizedBox(height: 11),
                  _appearanceCard(context, theme),
                  const SizedBox(height: 11),
                  _currencyCard(context, currency),
                  const SizedBox(height: 11),
                  _branchCard(context, branch),
                  const SizedBox(height: 11),
                  _printerCard(context),
                  const SizedBox(height: 11),
                  _group(context, [
                    (Icons.group_outlined, 'Staff & permissions'),
                  ]),
                  const SizedBox(height: 11),
                  AstraCard(
                    radius: 14,
                    onTap: () => ConnectionSheet.show(context),
                    child: Row(
                      children: [
                        IconChip(icon: Icons.cloud_outlined, size: 28, radius: 8, bg: p.tint),
                        const SizedBox(width: 11),
                        Expanded(child: Text('Server connection', style: ui(size: 12.5, weight: FontWeight.w700, color: p.ink))),
                        Icon(Icons.chevron_right, color: p.textMuted, size: 18),
                      ],
                    ),
                  ),
                  const SizedBox(height: 11),
                  GestureDetector(
                    onTap: () => _confirmLogout(context),
                    child: AstraCard(
                      radius: 14,
                      child: Center(
                        child: Row(
                          mainAxisSize: MainAxisSize.min,
                          children: [
                            const Icon(Icons.logout, size: 16, color: AstraPalette.danger),
                            const SizedBox(width: 8),
                            Text('Log out', style: ui(size: 12.5, weight: FontWeight.w700, color: AstraPalette.danger)),
                          ],
                        ),
                      ),
                    ),
                  ),
                  const SizedBox(height: 14),
                  Center(
                    child: Text('Invo · Salon POS · v1.0.0',
                        style: ui(size: 10.5, weight: FontWeight.w600, color: p.textMuted)),
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

  Widget _presetCard(BuildContext context, ThemeController theme) {
    final p = context.astra;
    final preset = theme.preset;
    return AstraCard(
      radius: 14,
      onTap: () => showThemeSheet(context),
      child: Row(
        children: [
          SizedBox(
            width: 18.0 + 12 * 3,
            height: 34,
            child: Stack(
              alignment: Alignment.centerLeft,
              children: [
                for (var i = 0; i < preset.swatch.length; i++)
                  Positioned(
                    left: i * 12.0,
                    child: Container(
                      width: 20,
                      height: 20,
                      decoration: BoxDecoration(
                        color: preset.swatch[i],
                        shape: BoxShape.circle,
                        border: Border.all(color: p.cardSolid, width: 2),
                      ),
                    ),
                  ),
              ],
            ),
          ),
          const SizedBox(width: 11),
          Expanded(
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Text('Colour preset', style: ui(size: 12.5, weight: FontWeight.w700, color: p.ink)),
                Text('${preset.name} · ${preset.tagline}',
                    maxLines: 1,
                    overflow: TextOverflow.ellipsis,
                    style: ui(size: 10, weight: FontWeight.w600, color: p.textMuted)),
              ],
            ),
          ),
          Icon(Icons.chevron_right, color: p.textMuted, size: 18),
        ],
      ),
    );
  }

  Widget _appearanceCard(BuildContext context, ThemeController theme) {
    final p = context.astra;
    final mode = theme.mode;
    final icon = switch (mode) {
      AstraMode.light => Icons.light_mode_outlined,
      AstraMode.dark => Icons.dark_mode_outlined,
      AstraMode.system => Icons.brightness_auto_outlined,
    };
    final subtitle = mode == AstraMode.system
        ? 'System · ${theme.isDark ? 'Dark' : 'Light'}'
        : mode.label;
    return AstraCard(
      radius: 14,
      onTap: () => showAppearanceSheet(context),
      child: Row(
        children: [
          IconChip(icon: icon, size: 34, radius: 9, bg: p.tint),
          const SizedBox(width: 11),
          Expanded(
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Text('Appearance', style: ui(size: 12.5, weight: FontWeight.w700, color: p.ink)),
                Text(subtitle, style: ui(size: 10, weight: FontWeight.w600, color: p.textMuted)),
              ],
            ),
          ),
          Icon(Icons.chevron_right, color: p.textMuted, size: 18),
        ],
      ),
    );
  }

  Widget _currencyCard(BuildContext context, Currency currency) {
    final p = context.astra;
    return AstraCard(
      radius: 14,
      onTap: () => showCurrencySheet(context),
      child: Row(
        children: [
          Container(
            width: 34,
            height: 34,
            alignment: Alignment.center,
            decoration: BoxDecoration(color: p.tint, borderRadius: BorderRadius.circular(9)),
            child: Text(currency.symbol.trim(), style: serif(size: 16, color: p.primaryDark)),
          ),
          const SizedBox(width: 11),
          Expanded(
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Text('Currency', style: ui(size: 12.5, weight: FontWeight.w700, color: p.ink)),
                Text('${currency.name} · ${currency.code}',
                    style: ui(size: 10, weight: FontWeight.w600, color: p.textMuted)),
              ],
            ),
          ),
          Icon(Icons.chevron_right, color: p.textMuted, size: 18),
        ],
      ),
    );
  }

  Widget _branchCard(BuildContext context, BranchController branch) {
    final p = context.astra;
    final selected = branch.selected;
    final subtitle = branch.loading && selected == null
        ? 'Loading branches…'
        : selected == null
            ? (branch.error ?? 'Tap to choose a branch')
            : (selected.location.isEmpty ? selected.code : selected.location);
    return AstraCard(
      radius: 14,
      onTap: () => showBranchSheet(context),
      child: Row(
        children: [
          IconChip(icon: Icons.business, size: 34, radius: 9, bg: p.tint),
          const SizedBox(width: 11),
          Expanded(
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Text(selected?.name ?? 'Branch',
                    style: ui(size: 12.5, weight: FontWeight.w700, color: p.ink)),
                Text(subtitle, style: ui(size: 10, weight: FontWeight.w600, color: p.textMuted)),
              ],
            ),
          ),
          Icon(Icons.chevron_right, color: p.textMuted, size: 18),
        ],
      ),
    );
  }

  Widget _printerCard(BuildContext context) {
    final p = context.astra;
    final print = context.watch<PrintSettingsController>();
    return AstraCard(
      radius: 14,
      onTap: () => context.push('/print-settings'),
      child: Row(
        children: [
          IconChip(icon: Icons.receipt_long_outlined, size: 34, radius: 9, bg: p.tint),
          const SizedBox(width: 11),
          Expanded(
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Text('Printer & receipt', style: ui(size: 12.5, weight: FontWeight.w700, color: p.ink)),
                Text('${print.style.label} · ${print.width.label}',
                    style: ui(size: 10, weight: FontWeight.w600, color: p.textMuted)),
              ],
            ),
          ),
          Icon(Icons.chevron_right, color: p.textMuted, size: 18),
        ],
      ),
    );
  }

  Widget _group(BuildContext context, List<(IconData, String)> items) {
    final p = context.astra;
    final t = context.astraTheme;
    return Container(
      decoration: BoxDecoration(borderRadius: BorderRadius.circular(15), boxShadow: t.softShadow),
      clipBehavior: Clip.antiAlias,
      child: Column(
        children: [
          for (var i = 0; i < items.length; i++) ...[
            if (i > 0) Container(height: 1, color: p.hairline),
            Container(
              color: p.card,
              padding: const EdgeInsets.symmetric(horizontal: 14, vertical: 13),
              child: Row(
                children: [
                  IconChip(icon: items[i].$1, size: 28, radius: 8, bg: p.tint),
                  const SizedBox(width: 11),
                  Expanded(child: Text(items[i].$2, style: ui(size: 12.5, weight: FontWeight.w700, color: p.ink))),
                  Icon(Icons.chevron_right, color: p.textMuted, size: 18),
                ],
              ),
            ),
          ],
        ],
      ),
    );
  }

  Future<void> _confirmLogout(BuildContext context) async {
    final ok = await showDialog<bool>(
      context: context,
      builder: (ctx) => AlertDialog(
        title: const Text('Log out?'),
        content: const Text('You’ll need your MPIN to sign back in.'),
        actions: [
          TextButton(onPressed: () => Navigator.pop(ctx, false), child: const Text('Cancel')),
          TextButton(onPressed: () => Navigator.pop(ctx, true), child: const Text('Log out')),
        ],
      ),
    );
    if (ok == true && context.mounted) {
      await context.read<AuthController>().logout();
    }
  }
}
