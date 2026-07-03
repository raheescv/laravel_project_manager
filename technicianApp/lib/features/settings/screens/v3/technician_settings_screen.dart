import 'package:flutter/material.dart';
import 'package:go_router/go_router.dart';
import 'package:provider/provider.dart';

import 'package:invo/features/auth/logic/auth_cubit/auth_cubit.dart';
import 'package:invo/features/auth/widgets/v3/connection_sheet.dart';
import 'package:invo/features/settings/widgets/v3/appearance_sheet.dart';
import 'package:invo/features/settings/widgets/v3/theme_sheet.dart';
import 'package:invo/shared/domain/helpers/responsive.dart';
import 'package:invo/shared/logic/theme_cubit/theme_cubit.dart';
import 'package:invo/shared/utils/components/theme/index.dart';
import 'package:invo/shared/widgets/astra_widgets.dart';

/// Technician settings — the POS settings scaffold trimmed to the technician
/// surface: profile header, colour preset, appearance, security
/// (change PIN / password), server connection and log out.
class TechnicianSettingsScreen extends StatelessWidget {
  const TechnicianSettingsScreen({super.key});

  @override
  Widget build(BuildContext context) {
    final p = context.astra;
    final theme = context.watch<ThemeCubit>();
    final user = context.watch<AuthCubit>().user;

    final mode = theme.mode;
    final appearanceIcon = switch (mode) {
      AstraMode.light => Icons.light_mode_outlined,
      AstraMode.dark => Icons.dark_mode_outlined,
      AstraMode.system => Icons.brightness_auto_outlined,
    };
    final appearanceSub =
        mode == AstraMode.system ? 'System · ${theme.isDark ? 'Dark' : 'Light'}' : mode.label;

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
                  padding: const EdgeInsets.fromLTRB(16, 16, 16, 120),
                  children: [
                    _profileHeader(context, user?.name ?? 'Technician',
                        user?.designation ?? '', user?.code ?? ''),
                    const SizedBox(height: 14),
                    _card(context,
                        leading: _presetSwatch(context, theme),
                        title: 'Colour preset',
                        subtitle: '${theme.preset.name} · ${theme.preset.tagline}',
                        onTap: () => showThemeSheet(context)),
                    const SizedBox(height: 11),
                    _iconCard(context, appearanceIcon, 'Appearance', appearanceSub,
                        () => showAppearanceSheet(context)),
                    const SizedBox(height: 11),
                    _iconCard(context, Icons.pin_outlined, 'Change PIN',
                        'Update your login MPIN', () => context.push('/change-pin')),
                    const SizedBox(height: 11),
                    _iconCard(context, Icons.lock_outline, 'Change password',
                        'Update your account password', () => context.push('/change-password')),
                    const SizedBox(height: 11),
                    _iconCard(context, Icons.cloud_outlined, 'Server connection',
                        'Base URL & tenant', () => ConnectionSheet.show(context)),
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
                      child: Text('FixMate · v1.0.0',
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

  Widget _profileHeader(BuildContext context, String name, String designation, String code) {
    final p = context.astra;
    return AstraCard(
      radius: 18,
      child: Row(
        children: [
          Monogram(letter: name.isNotEmpty ? name[0].toUpperCase() : 'T', size: 52),
          const SizedBox(width: 14),
          Expanded(
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Text(name, maxLines: 1, overflow: TextOverflow.ellipsis, style: serif(size: 19, color: p.ink)),
                const SizedBox(height: 3),
                Text(
                  [
                    if (designation.isNotEmpty) designation else 'Technician',
                    if (code.isNotEmpty) 'Code $code',
                  ].join(' · '),
                  style: ui(size: 11.5, weight: FontWeight.w600, color: p.textMuted),
                ),
              ],
            ),
          ),
        ],
      ),
    );
  }

  Widget _presetSwatch(BuildContext context, ThemeCubit theme) {
    final p = context.astra;
    final preset = theme.preset;
    return SizedBox(
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
    );
  }

  Widget _card(BuildContext context,
      {required Widget leading, required String title, required String subtitle, required VoidCallback onTap}) {
    final p = context.astra;
    return AstraCard(
      radius: 14,
      onTap: onTap,
      child: Row(
        children: [
          leading,
          const SizedBox(width: 11),
          Expanded(
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Text(title, style: ui(size: 12.5, weight: FontWeight.w700, color: p.ink)),
                Text(subtitle, maxLines: 1, overflow: TextOverflow.ellipsis, style: ui(size: 10, weight: FontWeight.w600, color: p.textMuted)),
              ],
            ),
          ),
          Icon(Icons.chevron_right, color: p.textMuted, size: 18),
        ],
      ),
    );
  }

  Widget _iconCard(BuildContext context, IconData icon, String title, String subtitle, VoidCallback onTap) {
    final p = context.astra;
    return _card(context,
        leading: IconChip(icon: icon, size: 34, radius: 9, bg: p.tint),
        title: title,
        subtitle: subtitle,
        onTap: onTap);
  }

  Future<void> _confirmLogout(BuildContext context) async {
    final ok = await showDialog<bool>(
      context: context,
      builder: (ctx) => AlertDialog(
        title: const Text('Log out?'),
        content: const Text('You’ll need your MPIN or password to sign back in.'),
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
