import 'package:flutter/material.dart';
import 'package:flutter_bloc/flutter_bloc.dart';
import 'package:go_router/go_router.dart';

import '../../../shared/domain/helpers/responsive.dart';
import '../../../shared/logic/theme_cubit/theme_cubit.dart';
import '../../../shared/utils/components/theme/pearl_theme.dart';
import '../../../shared/utils/components/theme/theme_presets.dart';
import '../../../shared/widgets/chrome/app_top_bar.dart';
import '../../../shared/widgets/chrome/showcase_scaffold.dart';
import '../../../shared/widgets/pearl_widgets.dart';

/// How the tablet is dressed: when to go dark, and which palette each mode
/// wears.
///
/// Day and night are picked separately because they are different rooms — the
/// same shop is daylit through the window at noon and lit by its own spots at
/// eight, and one compromise palette is wrong in both.
class SettingsScreen extends StatelessWidget {
  const SettingsScreen({super.key});

  @override
  Widget build(BuildContext context) {
    final cubit = context.watch<ThemeCubit>();
    final state = cubit.state;
    final p = context.pearl;
    final tablet = context.isTablet;

    return ShowcaseScaffold(
      topBar: AppTopBar(
        leading: IconSquare(
          Icons.arrow_back,
          size: 38,
          onTap: () => context.canPop() ? context.pop() : context.go('/'),
        ),
      ),
      body: ListView(
        padding: const EdgeInsets.fromLTRB(
            PearlMetrics.pad, 18, PearlMetrics.pad, 40),
        children: [
          Text(
            'Appearance',
            style: PearlText.display(tablet ? 30 : 26).copyWith(color: p.ink),
          ),
          const SizedBox(height: 10),
          Text(
            'Set once per tablet. Nothing here leaves the device.',
            style: PearlText.body(12).copyWith(color: p.muted),
          ),
          const ColumnHeading('Mode'),
          _ModeRow(mode: state.mode, onChanged: cubit.set),
          _PresetSection(
            heading: 'Day palette',
            meta: 'used in light mode',
            brightness: Brightness.light,
            selected: state.light,
            onPick: cubit.setLight,
          ),
          _PresetSection(
            heading: 'Night palette',
            meta: 'used in dark mode',
            brightness: Brightness.dark,
            selected: state.dark,
            onPick: cubit.setDark,
          ),
          const SizedBox(height: 26),
          _MatchRow(state: state, onMatch: cubit.setBoth),
        ],
      ),
    );
  }
}

class _ModeRow extends StatelessWidget {
  const _ModeRow({required this.mode, required this.onChanged});

  final ThemeMode mode;
  final ValueChanged<ThemeMode> onChanged;

  static const List<(ThemeMode, String, IconData)> _options = [
    (ThemeMode.system, 'Follow device', Icons.contrast),
    (ThemeMode.light, 'Day', Icons.light_mode_outlined),
    (ThemeMode.dark, 'Night', Icons.dark_mode_outlined),
  ];

  @override
  Widget build(BuildContext context) {
    final p = context.pearl;
    return LayoutBuilder(
      builder: (context, constraints) {
        // "Follow device" is wide-tracked and does not fit beside its icon in a
        // third of a phone. Below that the pill stacks instead of truncating —
        // the label is the part that has to survive.
        final stacked = (constraints.maxWidth - 16) / 3 < 130;
        return Row(
          children: [
            for (final (value, label, icon) in _options) ...[
              Expanded(
                child: InkWell(
                  onTap: () => onChanged(value),
                  child: Container(
                    height: stacked ? 62 : 54,
                    alignment: Alignment.center,
                    padding: const EdgeInsets.symmetric(horizontal: 4),
                    decoration: BoxDecoration(
                      color: value == mode ? p.accent : null,
                      border: Border.all(color: value == mode ? p.accent : p.line),
                    ),
                    child: Flex(
                      direction: stacked ? Axis.vertical : Axis.horizontal,
                      mainAxisAlignment: MainAxisAlignment.center,
                      mainAxisSize: MainAxisSize.min,
                      children: [
                        Icon(icon, size: 15, color: value == mode ? p.accentInk : p.muted),
                        SizedBox(width: stacked ? 0 : 9, height: stacked ? 7 : 0),
                        Flexible(
                          child: Text(
                            label.toUpperCase(),
                            maxLines: 1,
                            overflow: TextOverflow.ellipsis,
                            textAlign: TextAlign.center,
                            style: PearlText.micro.copyWith(
                              fontSize: 8.5,
                              letterSpacing: stacked ? 1.6 : 3.4,
                              color: value == mode ? p.accentInk : p.ink,
                            ),
                          ),
                        ),
                      ],
                    ),
                  ),
                ),
              ),
              if (value != _options.last.$1) const SizedBox(width: 8),
            ],
          ],
        );
      },
    );
  }
}

class _PresetSection extends StatelessWidget {
  const _PresetSection({
    required this.heading,
    required this.meta,
    required this.brightness,
    required this.selected,
    required this.onPick,
  });

  final String heading;
  final String meta;

  /// Which half of each preset the cards preview — a night palette previewed in
  /// its day colours would be picked for the wrong reasons.
  final Brightness brightness;
  final ThemePreset selected;
  final ValueChanged<ThemePreset> onPick;

  @override
  Widget build(BuildContext context) {
    return Column(
      crossAxisAlignment: CrossAxisAlignment.stretch,
      children: [
        SectionHeading(heading, meta: meta),
        LayoutBuilder(
          builder: (context, constraints) {
            const target = 250.0;
            const gap = PearlMetrics.gap;
            final columns =
                ((constraints.maxWidth + gap) / (target + gap)).round().clamp(1, 4);
            final width = (constraints.maxWidth - gap * (columns - 1)) / columns;
            return Wrap(
              spacing: gap,
              runSpacing: gap,
              children: [
                for (final preset in ThemePreset.values)
                  SizedBox(
                    width: width,
                    child: _PresetCard(
                      preset: preset,
                      brightness: brightness,
                      selected: preset == selected,
                      onTap: () => onPick(preset),
                    ),
                  ),
              ],
            );
          },
        ),
      ],
    );
  }
}

/// One direction, previewed in its own colours rather than the current theme's.
///
/// The card paints itself with the palette it is offering — a swatch row
/// describes a scheme, but a card wearing it shows what the screen will
/// actually feel like, which is the thing being chosen.
class _PresetCard extends StatelessWidget {
  const _PresetCard({
    required this.preset,
    required this.brightness,
    required this.selected,
    required this.onTap,
  });

  final ThemePreset preset;
  final Brightness brightness;
  final bool selected;
  final VoidCallback onTap;

  @override
  Widget build(BuildContext context) {
    final theme = context.pearl;
    final swatch = preset.paletteFor(brightness);
    return InkWell(
      onTap: onTap,
      child: Container(
        padding: const EdgeInsets.all(14),
        decoration: BoxDecoration(
          color: swatch.bg,
          // The selection ring is the app's accent, not the preset's, so the
          // "this one is chosen" signal stays the same across all six cards.
          border: Border.all(
            color: selected ? theme.accent : theme.line,
            width: selected ? 2 : 1,
          ),
        ),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Row(
              children: [
                Expanded(
                  child: Text(
                    preset.label.toUpperCase(),
                    maxLines: 1,
                    overflow: TextOverflow.ellipsis,
                    style: PearlText.section.copyWith(fontSize: 10, color: swatch.ink),
                  ),
                ),
                if (selected)
                  Container(
                    width: 16,
                    height: 16,
                    alignment: Alignment.center,
                    color: theme.accent,
                    child: Icon(Icons.check, size: 11, color: theme.accentInk),
                  ),
              ],
            ),
            const SizedBox(height: 9),
            Text(
              preset.blurb,
              maxLines: 2,
              overflow: TextOverflow.ellipsis,
              style: PearlText.body(11).copyWith(color: swatch.muted),
            ),
            const SizedBox(height: 13),
            Row(
              children: [
                for (final color in preset.swatches(brightness)) ...[
                  Expanded(
                    child: Container(
                      height: 26,
                      decoration: BoxDecoration(
                        color: color,
                        border: Border.all(color: swatch.line),
                      ),
                    ),
                  ),
                  const SizedBox(width: 5),
                ],
                // A chip in the preset's own accent: the one thing that differs
                // most between directions is what "selected" looks like.
                Container(
                  width: 42,
                  height: 26,
                  alignment: Alignment.center,
                  color: swatch.accent,
                  child: Text(
                    '42',
                    style: PearlText.label.copyWith(fontSize: 10, color: swatch.accentInk),
                  ),
                ),
              ],
            ),
          ],
        ),
      ),
    );
  }
}

class _MatchRow extends StatelessWidget {
  const _MatchRow({required this.state, required this.onMatch});

  final ThemeSettings state;
  final ValueChanged<ThemePreset> onMatch;

  @override
  Widget build(BuildContext context) {
    if (state.light == state.dark) return const SizedBox.shrink();
    return PearlButton(
      label: 'Use ${state.light.label} for both',
      ghost: true,
      onTap: () => onMatch(state.light),
    );
  }
}
