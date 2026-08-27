import 'package:flutter/material.dart';
import 'package:flutter_bloc/flutter_bloc.dart';
import 'package:go_router/go_router.dart';

import '../../logic/theme_cubit/theme_cubit.dart';
import '../../utils/components/theme/pearl_theme.dart';
import '../../utils/router/routes.dart';
import '../brand_mark.dart';

/// The tablet's persistent left rail. Replaces the phone's bottom bar and keeps
/// the top-level destinations reachable from every screen in the funnel.
class AppRail extends StatelessWidget {
  const AppRail({super.key, this.active = 0});

  final int active;

  @override
  Widget build(BuildContext context) {
    final p = context.pearl;
    return Container(
      width: PearlMetrics.rail,
      decoration: BoxDecoration(
        color: p.surface,
        border: Border(right: BorderSide(color: p.line)),
      ),
      child: Column(
        children: [
          const SizedBox(height: 16),
          const BrandMark(height: 46, padding: 4),
          const SizedBox(height: 14),
          _RailIcon(
            icon: Icons.grid_view_outlined,
            selected: active == 0,
            onTap: () => context.go(Routes.size),
          ),
          _RailIcon(
            icon: Icons.search,
            selected: active == 1,
            onTap: () => context.push(Routes.search),
          ),
          _RailIcon(
            icon: Icons.qr_code_scanner_outlined,
            selected: active == 2,
            onTap: () => context.push(Routes.scan),
          ),
          const Spacer(),
          _RailIcon(
            icon: switch (context.watch<ThemeCubit>().state.mode) {
              ThemeMode.light => Icons.light_mode_outlined,
              ThemeMode.dark => Icons.dark_mode_outlined,
              ThemeMode.system => Icons.contrast,
            },
            selected: false,
            onTap: () => context.read<ThemeCubit>().cycle(),
          ),
          _RailIcon(
            icon: Icons.tune,
            selected: active == 3,
            onTap: () => context.push(Routes.settings),
          ),
          const SizedBox(height: 14),
        ],
      ),
    );
  }
}

class _RailIcon extends StatelessWidget {
  const _RailIcon({required this.icon, required this.selected, required this.onTap});

  final IconData icon;
  final bool selected;
  final VoidCallback onTap;

  @override
  Widget build(BuildContext context) {
    final p = context.pearl;
    return Padding(
      padding: const EdgeInsets.symmetric(vertical: 3),
      child: InkWell(
        onTap: onTap,
        child: Container(
          width: 42,
          height: 42,
          alignment: Alignment.center,
          decoration: BoxDecoration(color: selected ? p.accent : null),
          child: Icon(icon, size: 19, color: selected ? p.accentInk : p.faint),
        ),
      ),
    );
  }
}
