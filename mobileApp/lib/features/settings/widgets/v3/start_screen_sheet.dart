import 'package:flutter/material.dart';
import 'package:provider/provider.dart';

import 'package:invo/features/auth/logic/auth_cubit/auth_cubit.dart';
import 'package:invo/features/settings/logic/pos_settings_cubit/pos_settings_cubit.dart';
import 'package:invo/shared/domain/constants/mobile_permissions.dart';
import 'package:invo/shared/utils/components/theme/index.dart';
import 'package:invo/shared/utils/router/routes.dart';
import 'package:invo/shared/widgets/astra_widgets.dart';

/// The icon each landing option wears, everywhere it's offered. Deliberately
/// the icons those destinations already carry in the nav — the dashboard tile
/// grid, the New Sale launcher, the Sales tab — so the choice is recognisable
/// as a place in the app rather than a word in a list.
IconData startScreenIcon(StartScreen s) => switch (s) {
      StartScreen.home => Icons.grid_view,
      StartScreen.sale => Icons.add_shopping_cart,
      StartScreen.sales => Icons.receipt_long,
    };

/// Click-and-go start-screen picker: tapping a row saves it and closes the
/// sheet — it applies at the next sign-in or unlock, so there is nothing to
/// confirm and nothing to preview.
Future<void> showStartScreenSheet(BuildContext context) {
  final p = context.astra;
  return showModalBottomSheet<void>(
    context: context,
    backgroundColor: Colors.transparent,
    barrierColor: Colors.black.withValues(alpha: 0.45),
    builder: (sheetContext) {
      final pos = sheetContext.watch<PosSettingsCubit>();
      // Offer only what this account could actually land on — the dashboard and
      // the Sales tab live inside the home shell, which the router keeps shut
      // for anyone without the sales overview permission.
      final options = StartScreen.optionsFor(
          canViewDashboard: sheetContext
              .read<AuthCubit>()
              .hasPermission(PermissionSlug.salesOverview));
      return Container(
        decoration: BoxDecoration(
          color: p.canvas,
          borderRadius: const BorderRadius.vertical(top: Radius.circular(30)),
        ),
        child: SafeArea(
          top: false,
          child: Column(
            mainAxisSize: MainAxisSize.min,
            children: [
              const SizedBox(height: 10),
              Container(
                width: 40,
                height: 4,
                decoration: BoxDecoration(color: p.hairline, borderRadius: BorderRadius.circular(3)),
              ),
              Padding(
                padding: const EdgeInsets.fromLTRB(20, 14, 20, 2),
                child: Row(
                  children: [
                    Icon(Icons.login_outlined, size: 18, color: p.primary),
                    const SizedBox(width: 9),
                    Expanded(child: Text('Start screen', style: serif(size: 20, color: p.ink))),
                    GestureDetector(
                      onTap: () => Navigator.of(sheetContext).pop(),
                      child: Icon(Icons.close, size: 20, color: p.textMuted),
                    ),
                  ],
                ),
              ),
              Padding(
                padding: const EdgeInsets.fromLTRB(20, 0, 20, 8),
                child: Align(
                  alignment: AlignmentDirectional.centerStart,
                  child: Text('Where this device opens once you sign in.',
                      style: ui(size: 11.5, weight: FontWeight.w600, color: p.textMuted)),
                ),
              ),
              Flexible(
                child: ListView(
                  shrinkWrap: true,
                  padding: const EdgeInsets.fromLTRB(12, 4, 12, 12),
                  children: [
                    for (final s in options) _row(sheetContext, s, s == pos.startScreen),
                  ],
                ),
              ),
            ],
          ),
        ),
      );
    },
  );
}

Widget _row(BuildContext context, StartScreen screen, bool active) {
  final p = context.astra;
  return GestureDetector(
    onTap: () {
      context.read<PosSettingsCubit>().setStartScreen(screen);
      Navigator.of(context).pop();
    },
    behavior: HitTestBehavior.opaque,
    child: Container(
      margin: const EdgeInsets.symmetric(vertical: 4, horizontal: 4),
      padding: const EdgeInsets.symmetric(horizontal: 14, vertical: 12),
      decoration: BoxDecoration(
        color: p.card,
        borderRadius: BorderRadius.circular(14),
        boxShadow: context.astraTheme.softShadow,
        border: Border.all(color: active ? p.primary : Colors.transparent, width: 1.5),
      ),
      child: Row(
        children: [
          IconChip(icon: startScreenIcon(screen), size: 38, radius: 11, bg: p.tint),
          const SizedBox(width: 12),
          Expanded(
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Text(screen.label, style: ui(size: 14, weight: FontWeight.w800, color: p.ink)),
                const SizedBox(height: 2),
                Text(screen.blurb,
                    style: ui(size: 10.5, weight: FontWeight.w600, color: p.textMuted)),
              ],
            ),
          ),
          const SizedBox(width: 10),
          Container(
            width: 24,
            height: 24,
            decoration: BoxDecoration(
              shape: BoxShape.circle,
              color: active ? p.primary : Colors.transparent,
              border: Border.all(color: active ? p.primary : p.hairline, width: 1.5),
            ),
            child: active ? const Icon(Icons.check, size: 15, color: Colors.white) : null,
          ),
        ],
      ),
    ),
  );
}
