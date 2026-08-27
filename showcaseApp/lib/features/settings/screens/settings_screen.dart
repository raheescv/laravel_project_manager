import 'package:flutter/material.dart';
import 'package:flutter_bloc/flutter_bloc.dart';
import 'package:go_router/go_router.dart';

import '../../../l10n/app_localizations.dart';
import '../../../shared/domain/helpers/responsive.dart';
import '../../../shared/logic/theme_cubit/theme_cubit.dart';
import '../../../shared/utils/components/theme/pearl_theme.dart';
import '../../../shared/utils/components/theme/theme_presets.dart';
import '../../../shared/utils/components/theme/type_presets.dart';
import '../../../shared/widgets/chrome/app_top_bar.dart';
import '../../../shared/widgets/chrome/showcase_scaffold.dart';
import '../../../shared/widgets/pearl_widgets.dart';

/// How the tablet is dressed: when to go dark, which palette each mode wears,
/// what it is set in, and how large.
///
/// Laid out as blocks on a grid rather than a single column of full-width
/// sections. There are five settings here and the old layout put each one
/// under the last, which made a screen you had to scroll to see the effect of
/// the control you were touching — on a tablet with room for two columns that
/// was a page of whitespace and a lost connection between cause and effect.
class SettingsScreen extends StatelessWidget {
  const SettingsScreen({super.key});

  @override
  Widget build(BuildContext context) {
    final cubit = context.watch<ThemeCubit>();
    final state = cubit.state;
    final p = context.pearl;
    final t = L.of(context);

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
            PearlMetrics.pad, 16, PearlMetrics.pad, 32),
        children: [
          Text(
            t.appearance,
            style: PearlText.display(context.isTablet ? 26 : 23)
                .copyWith(color: p.ink),
          ),
          const SizedBox(height: 6),
          Text(
            t.appearanceIntro,
            style: PearlText.body(11.5).copyWith(color: p.muted),
          ),
          const SizedBox(height: 18),
          _Blocks(
            children: [
              _Block(
                title: t.mode,
                child: _Segments(
                  count: _modes.length,
                  selected: _modes.indexOf(state.mode),
                  onTap: (i) => cubit.set(_modes[i]),
                  builder: (i, on) => _IconLabel(
                    icon: _modeIcons[i],
                    label: _modeLabel(t, _modes[i]),
                    on: on,
                  ),
                ),
              ),
              _Block(
                title: t.textSize,
                note: t.textSizeHint,
                child: _Segments(
                  count: ThemeCubit.textScales.length,
                  selected: ThemeCubit.textScales.indexOf(state.textScale),
                  onTap: (i) => cubit.setTextScale(ThemeCubit.textScales[i]),
                  // The sample is the preview: "Aa" drawn at the multiplier it
                  // sets, so the control shows its answer.
                  builder: (i, on) => _AaLabel(
                    scale: ThemeCubit.textScales[i],
                    label: [t.textStandard, t.textLarge, t.textLarger][i],
                    on: on,
                  ),
                ),
              ),
              _Block(
                title: t.typeface,
                note: t.typefaceHint,
                span: 2,
                child: _Segments(
                  count: TypePreset.values.length,
                  selected: TypePreset.values.indexOf(state.typeface),
                  onTap: (i) => cubit.setTypeface(TypePreset.values[i]),
                  // Each option set in its own face — naming a typeface tells
                  // you nothing you can act on; seeing it does.
                  builder: (i, on) => _FaceLabel(
                    preset: TypePreset.values[i],
                    on: on,
                  ),
                ),
              ),
              _Block(
                title: t.dayPalette,
                note: t.usedInLight,
                child: _PaletteRow(
                  brightness: Brightness.light,
                  selected: state.light,
                  onPick: cubit.setLight,
                ),
              ),
              _Block(
                title: t.nightPalette,
                note: t.usedInDark,
                child: _PaletteRow(
                  brightness: Brightness.dark,
                  selected: state.dark,
                  onPick: cubit.setDark,
                ),
              ),
            ],
          ),
          if (state.light != state.dark) ...[
            const SizedBox(height: 14),
            PearlButton(
              label: t.useForBoth(state.light.label),
              ghost: true,
              onTap: () => cubit.setBoth(state.light),
            ),
          ],
        ],
      ),
    );
  }

  static const List<ThemeMode> _modes = [
    ThemeMode.system,
    ThemeMode.light,
    ThemeMode.dark,
  ];

  static const List<IconData> _modeIcons = [
    Icons.contrast,
    Icons.light_mode_outlined,
    Icons.dark_mode_outlined,
  ];

  static String _modeLabel(L t, ThemeMode mode) => switch (mode) {
        ThemeMode.system => t.followDevice,
        ThemeMode.light => t.day,
        ThemeMode.dark => t.night,
      };
}

/// Two columns where there is room for two, one where there is not.
class _Blocks extends StatelessWidget {
  const _Blocks({required this.children});

  final List<_Block> children;

  @override
  Widget build(BuildContext context) {
    return LayoutBuilder(
      builder: (context, constraints) {
        const gap = 12.0;
        final columns = constraints.maxWidth >= 620 ? 2 : 1;
        final unit = (constraints.maxWidth - gap * (columns - 1)) / columns;
        return Wrap(
          spacing: gap,
          runSpacing: gap,
          children: [
            for (final block in children)
              SizedBox(
                // A block can ask for the full width when its control needs it.
                width: block.span == 2 || columns == 1
                    ? constraints.maxWidth
                    : unit,
                child: block,
              ),
          ],
        );
      },
    );
  }
}

class _Block extends StatelessWidget {
  const _Block({
    required this.title,
    required this.child,
    this.note,
    this.span = 1,
  });

  final String title;
  final Widget child;
  final String? note;

  /// 2 to take the whole row even on a two-column layout.
  final int span;

  @override
  Widget build(BuildContext context) {
    final p = context.pearl;
    return Container(
      padding: const EdgeInsets.fromLTRB(14, 12, 14, 14),
      decoration: BoxDecoration(border: Border.all(color: p.line)),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.stretch,
        children: [
          Row(
            children: [
              Expanded(
                child: Text(
                  title.toUpperCase(),
                  style: PearlText.micro.copyWith(fontSize: 8.5, color: p.faint),
                ),
              ),
              if (note != null)
                Flexible(
                  child: Text(
                    note!.toUpperCase(),
                    maxLines: 1,
                    overflow: TextOverflow.ellipsis,
                    textAlign: TextAlign.end,
                    style: PearlText.micro.copyWith(fontSize: 7.5, color: p.faint),
                  ),
                ),
            ],
          ),
          const SizedBox(height: 11),
          child,
        ],
      ),
    );
  }
}

/// A row of equal cells, one selected. Every control on this screen is one of
/// these, so they all behave and measure the same.
class _Segments extends StatelessWidget {
  const _Segments({
    required this.count,
    required this.selected,
    required this.onTap,
    required this.builder,
  });

  final int count;
  final int selected;
  final ValueChanged<int> onTap;
  final Widget Function(int index, bool selected) builder;

  @override
  Widget build(BuildContext context) {
    final p = context.pearl;
    return Row(
      children: [
        for (var i = 0; i < count; i++) ...[
          Expanded(
            child: InkWell(
              onTap: () => onTap(i),
              child: Container(
                height: 54,
                alignment: Alignment.center,
                padding: const EdgeInsets.symmetric(horizontal: 4),
                decoration: BoxDecoration(
                  color: i == selected ? p.accent : null,
                  border: Border.all(color: i == selected ? p.accent : p.line),
                ),
                child: builder(i, i == selected),
              ),
            ),
          ),
          if (i < count - 1) const SizedBox(width: 6),
        ],
      ],
    );
  }
}

class _IconLabel extends StatelessWidget {
  const _IconLabel({required this.icon, required this.label, required this.on});

  final IconData icon;
  final String label;
  final bool on;

  @override
  Widget build(BuildContext context) {
    final p = context.pearl;
    return Column(
      mainAxisSize: MainAxisSize.min,
      children: [
        Icon(icon, size: 15, color: on ? p.accentInk : p.muted),
        const SizedBox(height: 6),
        _Caption(label, on: on),
      ],
    );
  }
}

class _AaLabel extends StatelessWidget {
  const _AaLabel({required this.scale, required this.label, required this.on});

  final double scale;
  final String label;
  final bool on;

  @override
  Widget build(BuildContext context) {
    final p = context.pearl;
    return Column(
      mainAxisSize: MainAxisSize.min,
      children: [
        Text(
          'Aa',
          style: PearlText.label
              .copyWith(fontSize: 13 * scale, color: on ? p.accentInk : p.ink),
        ),
        const SizedBox(height: 5),
        _Caption(label, on: on),
      ],
    );
  }
}

class _FaceLabel extends StatelessWidget {
  const _FaceLabel({required this.preset, required this.on});

  final TypePreset preset;
  final bool on;

  @override
  Widget build(BuildContext context) {
    final p = context.pearl;
    return Column(
      mainAxisSize: MainAxisSize.min,
      children: [
        Text(
          'Ag',
          style: preset.sample(19).copyWith(color: on ? p.accentInk : p.ink),
        ),
        const SizedBox(height: 4),
        _Caption(preset.label, on: on),
      ],
    );
  }
}

class _Caption extends StatelessWidget {
  const _Caption(this.text, {required this.on});

  final String text;
  final bool on;

  @override
  Widget build(BuildContext context) {
    final p = context.pearl;
    return Text(
      text.toUpperCase(),
      maxLines: 1,
      overflow: TextOverflow.ellipsis,
      textAlign: TextAlign.center,
      style: PearlText.micro
          .copyWith(fontSize: 7.5, color: on ? p.accentInk : p.faint),
    );
  }
}

/// The palettes, as swatch strips rather than the full preview cards.
///
/// A card painted in each scheme showed more, but three of them stacked was
/// most of the screen — and the thing being chosen is a colour scheme, which a
/// four-colour strip conveys at a fraction of the height.
class _PaletteRow extends StatelessWidget {
  const _PaletteRow({
    required this.brightness,
    required this.selected,
    required this.onPick,
  });

  final Brightness brightness;
  final ThemePreset selected;
  final ValueChanged<ThemePreset> onPick;

  @override
  Widget build(BuildContext context) {
    final theme = context.pearl;
    return Row(
      children: [
        for (final preset in ThemePreset.values) ...[
          Expanded(
            child: InkWell(
              onTap: () => onPick(preset),
              child: Container(
                padding: const EdgeInsets.all(7),
                decoration: BoxDecoration(
                  // The ring is the app's accent, not the preset's, so
                  // "chosen" looks the same across all three.
                  border: Border.all(
                    color: preset == selected ? theme.accent : theme.line,
                    width: preset == selected ? 2 : 1,
                  ),
                ),
                child: Column(
                  children: [
                    Row(
                      children: [
                        for (final colour in preset.swatches(brightness))
                          Expanded(
                            child: Container(height: 22, color: colour),
                          ),
                      ],
                    ),
                    const SizedBox(height: 7),
                    _Caption(preset.label, on: false),
                  ],
                ),
              ),
            ),
          ),
          if (preset != ThemePreset.values.last) const SizedBox(width: 6),
        ],
      ],
    );
  }
}
