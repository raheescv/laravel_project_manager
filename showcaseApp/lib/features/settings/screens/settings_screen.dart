import 'package:flutter/material.dart';
import 'package:flutter_bloc/flutter_bloc.dart';
import 'package:go_router/go_router.dart';
import 'package:package_info_plus/package_info_plus.dart';

import '../../../l10n/app_localizations.dart';
import '../../../shared/logic/theme_cubit/theme_cubit.dart';
import '../../../shared/utils/components/theme/pearl_theme.dart';
import '../../../shared/utils/components/theme/theme_presets.dart';
import '../../../shared/utils/components/theme/type_presets.dart';
import '../../../shared/widgets/chrome/app_top_bar.dart';
import '../../../shared/widgets/chrome/idle_reset.dart';
import '../../../shared/widgets/chrome/showcase_scaffold.dart';
import '../../../shared/widgets/pearl_widgets.dart';

/// How the tablet is dressed: when to go dark, which palette each mode wears,
/// what it is set in, and how large.
///
/// Laid out as blocks rather than a run of full-width sections. There are five
/// settings here and the old layout put each one under the last, which made a
/// screen you had to scroll to see the effect of the control you were touching
/// — a lost connection between cause and effect.
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

  /// The installed build, read off the bundle rather than off the pubspec at
  /// compile time — the number that matters is the one on this tablet, not the
  /// one in the checkout. Null until the read lands, and the footer stays away
  /// until then rather than flashing a placeholder.
  String? _build;

  @override
  void initState() {
    super.initState();
    _readBuild();
  }

  /// Swallows a failed read rather than letting it surface: the line is a
  /// courtesy, and a platform that will not answer should cost the screen
  /// nothing but the line itself.
  Future<void> _readBuild() async {
    try {
      final info = await PackageInfo.fromPlatform();
      if (!mounted) return;
      setState(() => _build = '${info.version} (${info.buildNumber})');
    } catch (_) {
      // Left null; the footer never appears.
    }
  }

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
        // You are already here: the bar's Settings square goes quiet rather
        // than stacking another copy of this screen behind the back arrow.
        atSettings: true,
        leading: IconSquare(
          Icons.arrow_back,
          size: 38,
          prominent: true,
          onTap: () => context.canPop() ? context.pop() : context.go('/'),
        ),
      ),
      body: ListView(
        padding: const EdgeInsets.fromLTRB(
            PearlMetrics.pad, 16, PearlMetrics.pad, 32),
        children: [
          Text(
            t.appearance,
            style: PearlText.display(23)
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
                title: t.productsPerRow,
                note: t.productsPerRowHint,
                span: 2,
                child: _Segments(
                  count: ThemeCubit.productColumnOptions.length,
                  selected: ThemeCubit.productColumnOptions
                      .indexOf(state.productColumns),
                  onTap: (i) => cubit
                      .setProductColumns(ThemeCubit.productColumnOptions[i]),
                  builder: (i, on) => _ColumnsLabel(
                    columns: ThemeCubit.productColumnOptions[i],
                    on: on,
                  ),
                ),
              ),
              _Block(
                title: t.resetTimer,
                note: t.resetTimerHint,
                child: _MinutesField(
                  minutes: state.idleMinutes,
                  onCommit: cubit.setIdleMinutes,
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
          if (_build != null) ...[
            const SizedBox(height: 22),
            // Set at the foot in the faintest ink on the screen: nobody comes
            // to Settings for it, but whoever is asked "which version is that
            // one on?" needs somewhere to look.
            Center(
              child: Text(
                t.appVersion(_build!),
                style: PearlText.micro.copyWith(fontSize: 8.5, color: p.faint),
              ),
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
        // Four abreast needs most of the content column. Narrower than this
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
            // Hairlined: the light presets all stand on pure white now, and
            // so does this tile, so an unbordered strip would lose whichever
            // of its four swatches happens to be the ground.
            Container(
              decoration: BoxDecoration(border: Border.all(color: theme.line)),
              child: Row(
                children: [
                  for (final colour in preset.swatches(brightness))
                    Expanded(child: Container(height: 22, color: colour)),
                ],
              ),
            ),
            const SizedBox(height: 7),
            _Caption(preset.label, on: false),
          ],
        ),
      ),
    );
  }
}


/// A number somebody types, not one they pick.
///
/// Every other control on this screen is a segmented row, because every other
/// setting has a handful of right answers. This one does not: the wait that
/// suits a shop depends on its queue and where the tablet stands, and a list
/// of four guesses would be four wrong ones for somebody.
///
/// It commits on submit and on losing focus rather than on every keystroke —
/// typing "45" passes through "4", and a panel that starts resetting every
/// four minutes halfway through a number is worse than one that waits for you
/// to finish. Whatever is committed is pulled into range and written back into
/// the field, so what is on screen is always what is stored.
class _MinutesField extends StatefulWidget {
  const _MinutesField({required this.minutes, required this.onCommit});

  final int minutes;
  final ValueChanged<int> onCommit;

  @override
  State<_MinutesField> createState() => _MinutesFieldState();
}

class _MinutesFieldState extends State<_MinutesField> {
  late final TextEditingController _controller =
      TextEditingController(text: '${widget.minutes}');
  final FocusNode _focus = FocusNode();

  @override
  void initState() {
    super.initState();
    _focus.addListener(() {
      if (!_focus.hasFocus) _commit();
      // The border tracks focus, and nothing else rebuilds this on its own.
      if (mounted) setState(() {});
    });
  }

  @override
  void didUpdateWidget(_MinutesField old) {
    super.didUpdateWidget(old);
    // Only while nobody is typing — otherwise the clamp that follows a commit
    // would fight the cursor.
    if (widget.minutes != old.minutes && !_focus.hasFocus) {
      _controller.text = '${widget.minutes}';
    }
  }

  @override
  void dispose() {
    _controller.dispose();
    _focus.dispose();
    super.dispose();
  }

  void _commit() {
    final typed = int.tryParse(_controller.text.trim());
    // An empty field or a stray character is not an instruction to change
    // anything; it puts back what is already set.
    final value = (typed ?? widget.minutes)
        .clamp(ThemeCubit.minIdleMinutes, ThemeCubit.maxIdleMinutes);
    _controller.text = '$value';
    widget.onCommit(value);
  }

  @override
  Widget build(BuildContext context) {
    final p = context.pearl;
    return Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        Container(
          constraints: const BoxConstraints(minHeight: 54),
          padding: const EdgeInsets.symmetric(horizontal: 14),
          decoration: BoxDecoration(
            color: p.surface,
            border: Border.all(color: _focus.hasFocus ? p.accent : p.line),
          ),
          child: Row(
            children: [
              Icon(Icons.timer_outlined, size: 17, color: p.faint),
              const SizedBox(width: 11),
              Expanded(
                child: TextField(
                  controller: _controller,
                  focusNode: _focus,
                  keyboardType: TextInputType.number,
                  textInputAction: TextInputAction.done,
                  onSubmitted: (_) => _commit(),
                  // Not a commit — the field still waits for submit or blur.
                  // This only tells the idle timer somebody is here, because
                  // the soft keyboard's taps never reach it, and being reset
                  // out of Settings while typing the reset time is a joke the
                  // shop does not need.
                  onChanged: (_) => IdleReset.keepAlive(context),
                  style: PearlText.display(19).copyWith(color: p.ink),
                  cursorColor: p.ink,
                  decoration: const InputDecoration(
                    isDense: true,
                    border: InputBorder.none,
                  ),
                ),
              ),
              const SizedBox(width: 8),
              // The unit lives in the field rather than the label, so the
              // number reads as a quantity even before you reach the note.
              Text(
                L.of(context).minutesUnit,
                style: PearlText.body(11.5).copyWith(color: p.muted),
              ),
            ],
          ),
        ),
        const SizedBox(height: 7),
        Text(
          L.of(context).resetTimerRange(
              ThemeCubit.minIdleMinutes, ThemeCubit.maxIdleMinutes),
          style: PearlText.micro.copyWith(color: p.faint),
        ),
      ],
    );
  }
}
