import 'package:flutter/material.dart';
import 'package:go_router/go_router.dart';
import 'package:provider/provider.dart';

import 'package:invo/shared/domain/helpers/responsive.dart';
import 'package:invo/features/auth/logic/auth_cubit/auth_cubit.dart';
import 'package:invo/shared/logic/branch_cubit/branch_cubit.dart';
import 'package:invo/shared/logic/currency_cubit/currency_cubit.dart';
import 'package:invo/features/settings/logic/print_settings_cubit/print_settings_cubit.dart';
import 'package:invo/shared/logic/haptics_cubit/haptics_cubit.dart';
import 'package:invo/shared/logic/theme_cubit/theme_cubit.dart';
import 'package:invo/shared/utils/components/theme/index.dart';
import 'package:invo/shared/widgets/astra_widgets.dart';
import 'package:invo/features/auth/widgets/v3/connection_sheet.dart';
import 'package:invo/features/settings/widgets/v3/appearance_sheet.dart';
import 'package:invo/features/settings/widgets/v3/branch_sheet.dart';
import 'package:invo/features/settings/widgets/v3/currency_sheet.dart';
import 'package:invo/features/settings/widgets/v3/theme_sheet.dart';

class SettingsScreen extends StatelessWidget {
  const SettingsScreen({super.key});

  @override
  Widget build(BuildContext context) {
    final p = context.astra;
    final theme = context.watch<ThemeCubit>();
    final currencyCtl = context.watch<CurrencyCubit>();
    final branch = context.watch<BranchCubit>();

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
                  _hapticsCard(context),
                  const SizedBox(height: 11),
                  _currencyCard(context, currencyCtl),
                  const SizedBox(height: 11),
                  _branchCard(context, branch),
                  const SizedBox(height: 11),
                  _printerCard(context),
                  const SizedBox(height: 11),
                  _permissionsCard(context),
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
                    child: Text('Invo · Astra POS · v1.0.0',
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

  Widget _presetCard(BuildContext context, ThemeCubit theme) {
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

  Widget _appearanceCard(BuildContext context, ThemeCubit theme) {
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

  Widget _hapticsCard(BuildContext context) {
    final p = context.astra;
    final haptics = context.watch<HapticsCubit>();
    final on = haptics.enabled;
    return AstraCard(
      radius: 14,
      onTap: () => context.read<HapticsCubit>().toggle(),
      child: Row(
        children: [
          IconChip(
            icon: on ? Icons.vibration : Icons.smartphone_outlined,
            size: 34,
            radius: 9,
            bg: p.tint,
          ),
          const SizedBox(width: 11),
          Expanded(
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Text('Haptics', style: ui(size: 12.5, weight: FontWeight.w700, color: p.ink)),
                Text(on ? 'Vibration feedback on tap' : 'Vibration feedback off',
                    style: ui(size: 10, weight: FontWeight.w600, color: p.textMuted)),
              ],
            ),
          ),
          _switch(context, on),
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

  Widget _currencyCard(BuildContext context, CurrencyCubit controller) {
    final p = context.astra;
    final currency = controller.currency;
    final count = controller.available.length;
    final subtitle = count > 1
        ? '${currency.code} · $count available${controller.isCached ? ' · cached' : ''}'
        : '${currency.name} · ${currency.code}';
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
                Text(subtitle, style: ui(size: 10, weight: FontWeight.w600, color: p.textMuted)),
              ],
            ),
          ),
          Icon(Icons.chevron_right, color: p.textMuted, size: 18),
        ],
      ),
    );
  }

  Widget _branchCard(BuildContext context, BranchCubit branch) {
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
    final print = context.watch<PrintSettingsCubit>();
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

  Widget _permissionsCard(BuildContext context) {
    final p = context.astra;
    final user = context.watch<AuthCubit>().user;
    final count = user?.permissions.length ?? 0;
    final subtitle = (user?.isAdmin ?? false)
        ? 'Administrator · full access'
        : '$count ${count == 1 ? 'permission' : 'permissions'} granted';
    return AstraCard(
      radius: 14,
      onTap: () => context.push('/permissions'),
      child: Row(
        children: [
          IconChip(icon: Icons.verified_user_outlined, size: 34, radius: 9, bg: p.tint),
          const SizedBox(width: 11),
          Expanded(
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Text('My permissions', style: ui(size: 12.5, weight: FontWeight.w700, color: p.ink)),
                Text(subtitle, style: ui(size: 10, weight: FontWeight.w600, color: p.textMuted)),
              ],
            ),
          ),
          Icon(Icons.chevron_right, color: p.textMuted, size: 18),
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
      await context.read<AuthCubit>().logout();
    }
  }
}
