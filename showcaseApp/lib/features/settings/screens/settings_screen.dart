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
class SettingsScreen extends StatefulWidget {
  const SettingsScreen({super.key});

  @override
  State<SettingsScreen> createState() => _SettingsScreenState();
}

class _SettingsScreenState extends State<SettingsScreen> {
  /// Which of the two palette slots the strip is dressing. Null until the
  /// first tap, and then read as "whichever mode the tablet is showing" — so
  /// the tab you land on paints the screen you are looking at, and the other
  /// one is a deliberate step away.
  Brightness? _slot;

  @override
  Widget build(BuildContext context) {
    final cubit = context.watch<ThemeCubit>();
    final state = cubit.state;
    final p = context.pearl;
    final t = L.of(context);
    final slot = _slot ?? p.brightness;
    final night = slot == Brightness.dark;

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
                    label: [t.textStandard, t.textLarge, t.textLarger, t.textLargest][i],
                    on: on,
                  ),
                ),
              ),
              _Block(
                title: t.sizesPerRow,
                note: t.sizesPerRowHint,
                span: 2,
                child: _Segments(
                  count: ThemeCubit.sizeColumnOptions.length,
                  selected:
                      ThemeCubit.sizeColumnOptions.indexOf(state.sizeColumns),
                  onTap: (i) =>
                      cubit.setSizeColumns(ThemeCubit.sizeColumnOptions[i]),
                  builder: (i, on) => _ColumnsLabel(
                    columns: ThemeCubit.sizeColumnOptions[i],
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
                // One palette block, not two. Both slots are the same choice
                // made twice, and side by side they read as eight schemes
                // rather than four seen in two lights — the mode tab says
                // which light you are looking at.
                title: t.palette,
                note: night ? t.usedInDark : t.usedInLight,
                span: 2,
                child: Column(
                  children: [
                    _Segments(
                      count: 2,
                      selected: night ? 1 : 0,
                      onTap: (i) => setState(() => _slot =
                          i == 0 ? Brightness.light : Brightness.dark),
                      builder: (i, on) => _IconLabel(
                        icon: _modeIcons[i + 1],
                        label: i == 0 ? t.lightMode : t.darkMode,
                        on: on,
                      ),
                    ),
                    const SizedBox(height: 10),
                    _PaletteRow(
                      brightness: slot,
                      selected: night ? state.dark : state.light,
                      onPick: night ? cubit.setDark : cubit.setLight,
                    ),
                  ],
                ),
              ),
            ],
          ),
          if (state.light != state.dark) ...[
            const SizedBox(height: 14),
            // Offers the scheme the tab is showing, not always the day one —
            // it is the one under your eyes when you reach for the button.
            PearlButton(
              label: t.useForBoth(night ? state.dark.label : state.light.label),
              ghost: true,
              onTap: () => cubit.setBoth(night ? state.dark : state.light),
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
        ThemeMode.system => t.systemMode,
        ThemeMode.light => t.lightMode,
        ThemeMode.dark => t.darkMode,
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
                // A floor, not a height. Every cell holds a mark over a
                // caption and both scale with the text-size setting, so a
                // fixed box overflows the moment the type grows — which it
                // already did at the standard size once the captions were
                // there, quietly, because nothing rendered this screen.
                constraints: const BoxConstraints(minHeight: 54),
                alignment: Alignment.center,
                padding: const EdgeInsets.symmetric(horizontal: 4, vertical: 8),
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

/// The count as the thing it makes: that many blocks in a row, at the width
/// they would be. "4" tells you the number; four blocks tell you the screen.
class _ColumnsLabel extends StatelessWidget {
  const _ColumnsLabel({required this.columns, required this.on});

  final int columns;
  final bool on;

  @override
  Widget build(BuildContext context) {
    final p = context.pearl;
    return Column(
      mainAxisSize: MainAxisSize.min,
      children: [
        SizedBox(
          width: 52,
          child: Row(
            children: [
              for (var i = 0; i < columns; i++) ...[
                Expanded(
                  child: Container(
                    height: 14,
                    color: on ? p.accentInk : p.faint,
                  ),
                ),
                if (i < columns - 1) const SizedBox(width: 2),
              ],
            ],
          ),
        ),
        const SizedBox(height: 6),
        _Caption('$columns', on: on),
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
/// A card painted in each scheme showed more, but four of them stacked was
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
    return LayoutBuilder(
      builder: (context, box) {
        // Four abreast needs the width of a tablet column. Narrower than this
        // the names start ellipsing away to nothing, so the strip folds to two
        // rows instead of shrinking further.
        const all = ThemePreset.values;
        final perRow = box.maxWidth < 380 ? 2 : all.length;
        final rows = [
          for (var i = 0; i < all.length; i += perRow)
            all.sublist(i, (i + perRow).clamp(0, all.length)),
        ];
        return Column(
          children: [
            for (final row in rows) ...[
              Row(
                children: [
                  for (final preset in row) ...[
                    Expanded(child: _tile(context, preset)),
                    if (preset != row.last) const SizedBox(width: 6),
                  ],
                  // A short last row keeps its tiles the width of the ones
                  // above rather than stretching them.
                  for (var i = row.length; i < perRow; i++) ...[
                    const SizedBox(width: 6),
                    const Spacer(),
                  ],
                ],
              ),
              if (row != rows.last) const SizedBox(height: 6),
            ],
          ],
        );
      },
    );
  }

  Widget _tile(BuildContext context, ThemePreset preset) {
    final theme = context.pearl;
    return InkWell(
      onTap: () => onPick(preset),
      child: Container(
        padding: const EdgeInsets.all(7),
        decoration: BoxDecoration(
          // The ring is the app's accent, not the preset's, so "chosen" looks
          // the same whichever scheme it lands on.
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
                  Expanded(child: Container(height: 22, color: colour)),
              ],
            ),
            const SizedBox(height: 7),
            _Caption(preset.label, on: false),
          ],
        ),
      ),
    );
  }
}
