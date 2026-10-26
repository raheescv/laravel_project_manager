import 'package:flutter/material.dart';
import 'package:flutter_bloc/flutter_bloc.dart';
import 'package:go_router/go_router.dart';

import 'package:invo/features/auth/logic/auth_cubit/auth_cubit.dart';
import 'package:invo/features/settings/logic/pos_settings_cubit/pos_settings_cubit.dart';
import 'package:invo/features/settings/widgets/v3/start_screen_sheet.dart';
import 'package:invo/shared/domain/constants/mobile_permissions.dart';
import 'package:invo/shared/utils/components/theme/index.dart';
import 'package:invo/shared/utils/router/routes.dart';
import 'package:invo/shared/widgets/astra_widgets.dart';

/// Asked once per user per device: the first time someone signs in here, the
/// router holds them on this screen until they say where the app should open
/// for them. Click-and-go — the tap saves the choice and lands on it. Changing
/// it later is Settings → Start screen.
class ChooseStartScreen extends StatefulWidget {
  const ChooseStartScreen({super.key});

  @override
  State<ChooseStartScreen> createState() => _ChooseStartScreenState();
}

class _ChooseStartScreenState extends State<ChooseStartScreen> {
  /// The option being saved, so a double tap can't land twice.
  StartScreen? _choosing;

  Future<void> _choose(StartScreen screen) async {
    if (_choosing != null) return;
    final auth = context.read<AuthCubit>();
    setState(() => _choosing = screen);
    await context.read<PosSettingsCubit>().chooseStartScreenFor(auth.user?.id, screen);
    if (!mounted) return;
    context.go(screen.resolve(canViewReports: auth.hasPermission(PermissionSlug.report)));
  }

  @override
  Widget build(BuildContext context) {
    final p = context.astra;
    final name = (context.watch<AuthCubit>().user?.name ?? '').trim();
    final firstName = name.isEmpty ? '' : name.split(RegExp(r'\s+')).first;
    return Scaffold(
      backgroundColor: p.canvas,
      body: SafeArea(
        child: Center(
          child: ConstrainedBox(
            constraints: const BoxConstraints(maxWidth: 620),
            child: ListView(
              shrinkWrap: true,
              padding: const EdgeInsets.fromLTRB(20, 28, 20, 28),
              children: [
                Row(
                  children: [
                    IconChip(icon: Icons.login_outlined, size: 44, radius: 14, bg: p.tint),
                    const SizedBox(width: 13),
                    Expanded(
                      child: Column(
                        crossAxisAlignment: CrossAxisAlignment.start,
                        children: [
                          Text(firstName.isEmpty ? 'Welcome' : 'Welcome, $firstName',
                              style: ui(size: 12, weight: FontWeight.w700, color: p.primary)),
                          const SizedBox(height: 2),
                          Text('Where should we open for you?', style: serif(size: 22, color: p.ink)),
                        ],
                      ),
                    ),
                  ],
                ),
                const SizedBox(height: 8),
                Text('Pick the screen this device opens on once you sign in — and after an unlock. '
                    'It\'s saved for you alone.',
                    style: ui(size: 12, weight: FontWeight.w600, color: p.textMuted)),
                const SizedBox(height: 20),
                for (final s in startScreenOptions(context)) _row(context, s),
                const SizedBox(height: 8),
                Text('You can change this any time in Settings → Start screen.',
                    textAlign: TextAlign.center,
                    style: ui(size: 11, weight: FontWeight.w600, color: p.textMuted)),
              ],
            ),
          ),
        ),
      ),
    );
  }

  Widget _row(BuildContext context, StartScreen screen) {
    final p = context.astra;
    final active = screen == _choosing;
    return GestureDetector(
      behavior: HitTestBehavior.opaque,
      onTap: () => _choose(screen),
      child: Container(
        margin: const EdgeInsets.only(bottom: 10),
        padding: const EdgeInsets.symmetric(horizontal: 14, vertical: 15),
        decoration: BoxDecoration(
          color: active ? p.tint : p.card,
          borderRadius: BorderRadius.circular(14),
          border: Border.all(color: active ? p.primary : p.cardBorder, width: active ? 1.5 : 1),
        ),
        child: Row(
          children: [
            IconChip(icon: startScreenIcon(screen), size: 38, radius: 11, bg: p.tint),
            const SizedBox(width: 12),
            Expanded(
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  Text(screen.label, style: ui(size: 14, weight: FontWeight.w700, color: p.ink)),
                  Text(screen.blurb, style: ui(size: 10.5, weight: FontWeight.w600, color: p.textMuted)),
                ],
              ),
            ),
            active
                ? SizedBox(
                    width: 20,
                    height: 20,
                    child: CircularProgressIndicator(strokeWidth: 2, color: p.primary))
                : Icon(Icons.chevron_right, size: 20, color: p.textMuted),
          ],
        ),
      ),
    );
  }
}
