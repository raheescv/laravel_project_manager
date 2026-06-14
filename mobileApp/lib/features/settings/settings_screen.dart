import 'package:flutter/material.dart';
import 'package:provider/provider.dart';

import '../../core/currency.dart';
import '../../core/responsive.dart';
import '../../state/auth_controller.dart';
import '../../state/currency_controller.dart';
import '../../state/theme_controller.dart';
import '../../theme/palette.dart';
import '../../theme/theme.dart';
import '../../widgets/astra_widgets.dart';
import '../auth/connection_sheet.dart';
import 'currency_sheet.dart';

class SettingsScreen extends StatelessWidget {
  const SettingsScreen({super.key});

  @override
  Widget build(BuildContext context) {
    final p = context.astra;
    final theme = context.watch<ThemeController>();
    final currency = context.watch<CurrencyController>().currency;

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
                  Row(
                    children: [
                      IconChip(icon: Icons.palette_outlined, size: 26, radius: 8),
                      const SizedBox(width: 9),
                      Column(
                        crossAxisAlignment: CrossAxisAlignment.start,
                        children: [
                          Text('Colour preset', style: ui(size: 12.5, weight: FontWeight.w800, color: p.ink)),
                          Text('Re-skins the whole app instantly',
                              style: ui(size: 10, weight: FontWeight.w600, color: p.textMuted)),
                        ],
                      ),
                    ],
                  ),
                  const SizedBox(height: 11),
                  for (final preset in AstraPresets.all) ...[
                    _presetRow(context, preset, preset.id == theme.palette.id),
                    const SizedBox(height: 9),
                  ],
                  const SizedBox(height: 8),
                  _currencyCard(context, currency),
                  const SizedBox(height: 11),
                  _group(context, [
                    (Icons.business, 'Branch & tax settings'),
                    (Icons.description_outlined, 'Receipt & invoice'),
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

  Widget _presetRow(BuildContext context, AstraPalette preset, bool active) {
    final p = context.astra;
    final t = context.astraTheme;
    return GestureDetector(
      onTap: () => context.read<ThemeController>().setPreset(preset),
      child: Container(
        padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 11),
        decoration: BoxDecoration(
          color: p.card,
          borderRadius: BorderRadius.circular(15),
          boxShadow: t.softShadow,
          border: Border.all(color: active ? p.primary : Colors.transparent, width: 1.5),
        ),
        child: Row(
          children: [
            SizedBox(
              width: 24.0 + 15 * 3,
              height: 24,
              child: Stack(
                children: [
                  for (var i = 0; i < preset.swatch.length; i++)
                    Positioned(
                      left: i * 15.0,
                      child: Container(
                        width: 24,
                        height: 24,
                        decoration: BoxDecoration(
                          color: preset.swatch[i],
                          shape: BoxShape.circle,
                          border: Border.all(color: Colors.white, width: 2),
                        ),
                      ),
                    ),
                ],
              ),
            ),
            const SizedBox(width: 12),
            Expanded(
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  Text(preset.name, style: ui(size: 13, weight: FontWeight.w700, color: p.ink)),
                  const SizedBox(height: 1),
                  Text(preset.tagline, style: ui(size: 10, weight: FontWeight.w600, color: p.textMuted)),
                ],
              ),
            ),
            Container(
              width: 24,
              height: 24,
              decoration: BoxDecoration(
                shape: BoxShape.circle,
                gradient: active ? p.primaryGradient : null,
                border: active ? null : Border.all(color: p.hairline, width: 1.5),
              ),
              child: active ? const Icon(Icons.check, size: 13, color: Colors.white) : null,
            ),
          ],
        ),
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
