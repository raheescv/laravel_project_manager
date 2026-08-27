import 'package:flutter/material.dart';
import 'package:flutter_bloc/flutter_bloc.dart';

import '../../../shared/domain/constants/data_fetching_status.dart';
import '../../../shared/domain/models/index.dart';
import '../../../shared/logic/funnel_cubit/funnel_cubit.dart';
import '../../../shared/logic/theme_cubit/theme_cubit.dart';
import '../../../shared/utils/components/theme/pearl_theme.dart';
import '../../../shared/utils/router/funnel_navigation.dart';
import '../../../shared/widgets/chrome/app_top_bar.dart';
import '../../../shared/widgets/chrome/funnel_breadcrumbs.dart';
import '../../../shared/widgets/chrome/showcase_scaffold.dart';
import '../../../shared/widgets/pearl_widgets.dart';
import '../../../l10n/app_localizations.dart';
import '../../../l10n/app_localizations_ar.dart';
import '../../../l10n/app_localizations_en.dart';

/// Both languages, read straight off the generated localizations.
///
/// `L.of(context)` can only ever hand back the language the app is set to, and
/// the question on this screen is asked in both at once — see
/// [_BilingualQuestion].
final _en = LEn();
final _ar = LAr();

/// Step 1 — the size run, and where the app opens.
///
/// Tapping a size is the whole interaction: it commits the choice and moves to
/// the brand step. No preview column, no Continue — a chip that only highlights
/// and waits for a second tap on a button somewhere else is a step people
/// stall on, and the size run is long enough that the button is often off
/// screen by the time they have found their size.
///
/// The grid therefore gets the full width of the screen, and the chip's own
/// "19 left" / struck-through "none" carries the stock story the aside used to.
class SizeScreen extends StatelessWidget {
  const SizeScreen({super.key});

  Future<void> _choose(BuildContext context, String size) async {
    await context.read<FunnelCubit>().chooseSize(size);
    if (context.mounted) context.goToFunnelStep(FunnelStep.brand);
  }

  /// The other answer to the question: not a size, all of them.
  Future<void> _any(BuildContext context) async {
    await context.read<FunnelCubit>().skipSize();
    if (context.mounted) context.goToFunnelStep(FunnelStep.brand);
  }

  @override
  Widget build(BuildContext context) {
    final funnel = context.watch<FunnelCubit>();
    final state = funnel.state;

    return ShowcaseScaffold(
      topBar: AppTopBar(
        // Step 1 is the root of the funnel — there is nothing behind it, so the
        // bar keeps the wordmark rather than offering a back control.
        title: FunnelBreadcrumbs(
          state: state,
          current: FunnelStep.size,
          onReopen: (step) => reopenFunnelStep(context, step),
        ),
      ),
      body: switch (state.sizesStatus) {
        DataFetchStatus.failed => MessageState(
            title: L.of(context).sizesDidNotLoad,
            detail: state.errorMessage,
            actionLabel: L.of(context).tryAgain,
            onAction: funnel.loadSizes,
          ),
        DataFetchStatus.waiting when state.sizes.isEmpty => const _SizeSkeleton(),
        _ => _SizeBody(
            state: state,
            onTapSize: (size) => _choose(context, size),
            onTapAny: () => _any(context),
          ),
      },
      // No bottom bar. "All sizes" used to be a filled button pinned under the
      // run, which made the screen ask its question in two places: a grid of
      // plates, and a button somewhere else that answered the same question in
      // a different shape. It is the first plate now — the same target as every
      // other answer, in the place a customer is already looking.
    );
  }
}

class _SizeBody extends StatelessWidget {
  const _SizeBody({
    required this.state,
    required this.onTapSize,
    required this.onTapAny,
  });

  final FunnelState state;
  final void Function(String) onTapSize;
  final VoidCallback onTapAny;

  @override
  Widget build(BuildContext context) {
    final p = context.pearl;
    final t = L.of(context);
    final run = state.visibleSizes;

    return ListView(
      padding: const EdgeInsets.fromLTRB(
          PearlMetrics.pad, 18, PearlMetrics.pad, 30),
      children: [
        const _BilingualQuestion(size: 26),
        // Only when the run is showing sizes that cannot be sold, because then
        // the strike-through needs explaining. With the stock filter on there
        // is nothing to explain — every chip on screen is one you can have.
        if (!state.inStockOnly) ...[
          const SizedBox(height: 10),
          Text(
            t.sizeHintAll,
            style: PearlText.body(12).copyWith(color: p.muted),
          ),
        ],
        if (run.isNotEmpty) ...[
          const SizedBox(height: 22),
          // `state.size` rather than a local selection: the only thing worth
          // marking is the answer already given, for someone who reopened the
          // step to change it.
          _SizeWrap(
            sizes: run,
            selected: state.size,
            onTap: onTapSize,
            onTapAny: onTapAny,
          ),
        ],
        if (run.isEmpty)
          Padding(
            padding: const EdgeInsets.only(top: 40),
            child: MessageState(
              title: t.noSizes,
              detail: t.noSizesDetail,
            ),
          ),
      ],
    );
  }
}

/// The question, in English and Arabic at the same time.
///
/// Everything else on this screen follows Settings → Language, but this is the
/// line a customer reads before a member of staff has touched a setting — and a
/// kiosk left in the wrong language is a kiosk people walk past. Both
/// wordings are set at the same size in the same ink: neither is a caption for
/// the other.
///
/// Each half keeps its own direction, its own face and its own end of the
/// screen: English against the left margin, Arabic against the right, in both
/// languages. This is not one sentence in the page's language — it is the same
/// question asked twice, so neither half moves when Settings → Language does.
/// The bar above holds its controls still for the same reason.
class _BilingualQuestion extends StatelessWidget {
  const _BilingualQuestion({required this.size});

  final double size;

  /// Least the two halves can sit apart before they read as one line.
  static const double _gap = 18;

  /// The English half as it is set: capitals, because this is the one line on
  /// the screen a customer is meant to read from across the shop.
  ///
  /// Held here rather than in the string table so the wording stays ordinary
  /// sentence case for whoever edits the translations — the shouting is this
  /// heading's, not the copy's. The Arabic half is drawn as written: the
  /// script has no case, so there is nothing to raise.
  static String get _english => _en.whichSize.toUpperCase();

  static String get _arabic => _ar.whichSize;

  /// How both halves are set: the way this app asks a question, named once in
  /// [PearlText.displayCaps] and shared with the brand run's heading. Only the
  /// script differs here — each half names its own, since the page's language
  /// is the wrong answer for one of them.
  TextStyle _style({required bool arabic}) =>
      PearlText.displayCaps(size, arabic: arabic);

  /// What the half will actually measure once drawn, scale included.
  ///
  /// The two are pinned to opposite margins, so nothing about the layout tells
  /// us whether they still fit — this asks before choosing a row or a stack.
  double _widthOf(String text, {required bool arabic, required TextScaler scaler}) {
    final painter = TextPainter(
      text: TextSpan(text: text, style: _style(arabic: arabic)),
      textDirection: arabic ? TextDirection.rtl : TextDirection.ltr,
      textScaler: scaler,
    )..layout();
    return painter.width;
  }

  @override
  Widget build(BuildContext context) {
    final p = context.pearl;

    Widget wording(String text, {required bool arabic}) => Directionality(
          // Set on the wording itself rather than inherited from the page.
          // Arabic in a paragraph running the other way hands its trailing "؟"
          // to the wrong end of the line, and the Latin faces carry no Arabic
          // glyphs at all — so each half names both its direction and, through
          // [PearlText.displayIn], its script.
          textDirection: arabic ? TextDirection.rtl : TextDirection.ltr,
          child: Text(
            text,
            // The half keeps its end of the screen even when it takes two
            // lines: English stacked against the left, Arabic against the right.
            textAlign: arabic ? TextAlign.right : TextAlign.left,
            style: _style(arabic: arabic).copyWith(color: p.ink),
          ),
        );

    return Directionality(
      // Fixed, whichever way the page runs: English on the left of the screen,
      // Arabic on the right. Left to the page they would swap ends with the
      // language, which reads as the heading having moved rather than as the
      // same question asked twice.
      textDirection: TextDirection.ltr,
      child: LayoutBuilder(
        builder: (context, constraints) {
          final scaler = MediaQuery.textScalerOf(context);
          final fits = _widthOf(_english, arabic: false, scaler: scaler) +
                  _gap +
                  _widthOf(_arabic, arabic: true, scaler: scaler) <=
              constraints.maxWidth;

          // A pair that cannot share a line — a phone, or the largest text
          // size — stacks instead of breaking mid-question, and each half
          // still hangs off its own margin.
          if (!fits) {
            return Column(
              crossAxisAlignment: CrossAxisAlignment.stretch,
              children: [
                wording(_english, arabic: false),
                const SizedBox(height: 10),
                wording(_arabic, arabic: true),
              ],
            );
          }

          return Row(
            mainAxisAlignment: MainAxisAlignment.spaceBetween,
            crossAxisAlignment: CrossAxisAlignment.center,
            children: [
              wording(_english, arabic: false),
              wording(_arabic, arabic: true),
            ],
          );
        },
      ),
    );
  }
}

class _SizeWrap extends StatelessWidget {
  const _SizeWrap({
    required this.sizes,
    required this.selected,
    required this.onTap,
    required this.onTapAny,
  });

  final List<SizeOption> sizes;
  final String? selected;
  final void Function(String) onTap;

  /// "Any of them" — the first plate in the run.
  final VoidCallback onTapAny;

  /// The gap between plates. Everything else about the run is the column
  /// count and the width there is to divide.
  static const double gap = 10;

  /// The label a plate of [side] carries. Proportional, because the plate is
  /// now whatever the column share works out to and a fixed size sat in the
  /// middle of it like a caption; capped because four characters set at a
  /// third of a kiosk plate stop being a number and start being a poster.
  static double labelFor(double side) => (side * .26).clamp(15.0, 48.0);

  /// The side of one plate at [columns] across in [width] of page.
  static double sideFor(double width, int columns) =>
      (width - gap * (columns - 1)) / columns;

  @override
  Widget build(BuildContext context) {
    return LayoutBuilder(
      builder: (context, constraints) {
        // The count is a setting, not a rule read off the width.
        //
        // It was a rule, and the rule was wrong on the screens that matter: a
        // chip's width is worth more than a fourth column, and a kiosk mounted
        // at arm's length has the room for six where one on a counter does not.
        // Nothing about the column tells you which, so Appearance asks and this
        // obeys.
        //
        // Selected rather than watched, the same as the product grid: this is
        // one field of a settings object that also carries the palette, the
        // typeface and the text size, and the run has no business redrawing
        // when one of those moves.
        final columns =
            context.select<ThemeCubit, int>((cubit) => cubit.state.sizeColumns);
        // The plate takes the column, all of it. It used to be capped at 120pt
        // and centred in whatever the share came to, which meant fewer columns
        // bought air rather than a bigger target — and on a kiosk it left a
        // wall of small squares floating in the middle of the glass. The
        // setting is the only thing that decides how big a size is now: three
        // across fills the width in three, six across in six.
        final side = sideFor(constraints.maxWidth, columns);
        return Wrap(
          spacing: gap,
          runSpacing: gap,
          children: [
            // "All" leads the run rather than sitting under it in a button.
            //
            // It is marked whenever no size has been chosen, which is what the
            // screen actually means when it is first opened: nobody has
            // narrowed anything, so every size is still on the table. That also
            // means the run always shows the answer currently in force, the
            // same as it does for a size a customer came back to change.
            SizedBox(
              width: side,
              child: PearlChip(
                label: L.of(context).anySize,
                height: side,
                labelSize: labelFor(side),
                labelWeight: FontWeight.w700,
                selected: selected == null,
                onTap: onTapAny,
              ),
            ),
            for (final size in sizes)
              SizedBox(
                width: side,
                child: PearlChip(
                  label: size.size,
                  // No count. The run is long and a number on every chip made
                  // it a table to read rather than a row to scan — the size is
                  // the only thing being chosen here.
                  height: side,
                  labelSize: labelFor(side),
                  labelWeight: FontWeight.w700,
                  selected: size.size == selected,
                  available: size.inStock,
                  onTap: () => onTap(size.size),
                ),
              ),
          ],
        );
      },
    );
  }
}

class _SizeSkeleton extends StatelessWidget {
  const _SizeSkeleton();

  @override
  Widget build(BuildContext context) {
    // Rehearses the real run — same column count, same square plates — so the
    // page does not reflow the moment the sizes land.
    final columns =
        context.select<ThemeCubit, int>((cubit) => cubit.state.sizeColumns);
    return Padding(
      padding: const EdgeInsets.all(PearlMetrics.pad),
      child: LayoutBuilder(
        builder: (context, constraints) {
          const gap = _SizeWrap.gap;
          final side = _SizeWrap.sideFor(constraints.maxWidth, columns);
          // Only the rows that fit. The skeleton is not scrollable, and a run
          // drawn past the bottom of the box is an overflow, not a hint.
          final rows =
              ((constraints.maxHeight - 58) / (side + gap)).floor().clamp(1, 3);
          return Column(
            crossAxisAlignment: CrossAxisAlignment.stretch,
            children: [
              const SkeletonBlock(height: 34, width: 200),
              const SizedBox(height: 24),
              Wrap(
                spacing: gap,
                runSpacing: gap,
                children: [
                  for (var i = 0; i < columns * rows; i++)
                    SkeletonBlock(
                      height: side,
                      width: side,
                      radius: (side * .095).clamp(6.0, 24.0),
                    ),
                ],
              ),
            ],
          );
        },
      ),
    );
  }
}
