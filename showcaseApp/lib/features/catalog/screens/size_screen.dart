import 'package:flutter/material.dart';
import 'package:flutter_bloc/flutter_bloc.dart';

import '../../../shared/domain/constants/data_fetching_status.dart';
import '../../../shared/domain/helpers/responsive.dart';
import '../../../shared/domain/models/index.dart';
import '../../../shared/logic/theme_cubit/theme_cubit.dart';
import '../../../shared/utils/components/theme/pearl_theme.dart';
import '../../../shared/widgets/chrome/app_top_bar.dart';
import '../../../shared/widgets/chrome/funnel_column.dart';
import '../../../shared/widgets/chrome/showcase_scaffold.dart';
import '../../../shared/widgets/pearl_widgets.dart';
import '../logic/funnel_cubit/funnel_cubit.dart';
import 'funnel_navigation.dart';
import '../../../l10n/app_localizations.dart';

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

  @override
  Widget build(BuildContext context) {
    final funnel = context.watch<FunnelCubit>();
    final state = funnel.state;

    return ShowcaseScaffold(
      topBar: AppTopBar(
        // Step 1 is the root of the funnel — there is nothing behind it, so the
        // bar keeps the wordmark rather than offering a back control.
        title: context.isTablet
            ? null
            : FunnelBreadcrumbs(
                state: state,
                current: FunnelStep.size,
                onReopen: (step) => reopenFunnelStep(context, step),
              ),
      ),
      leftColumn: _LeftColumn(state: state),
      body: switch (state.sizesStatus) {
        DataFetchStatus.failed => MessageState(
            title: L.of(context).sizesDidNotLoad,
            detail: state.errorMessage,
            actionLabel: L.of(context).tryAgain,
            onAction: funnel.loadSizes,
          ),
        DataFetchStatus.waiting when state.sizes.isEmpty => const _SizeSkeleton(),
        _ => _SizeBody(state: state, onTapSize: (size) => _choose(context, size)),
      },
      // "Any size" is the only thing left to press, so it takes the bar.
      bottomBar: PinnedBar(
        child: PearlButton(
          label: L.of(context).anySize,
          ghost: true,
          onTap: () async {
            await funnel.skipSize();
            if (context.mounted) context.goToFunnelStep(FunnelStep.brand);
          },
        ),
      ),
    );
  }
}

class _SizeBody extends StatelessWidget {
  const _SizeBody({required this.state, required this.onTapSize});

  final FunnelState state;
  final void Function(String) onTapSize;

  @override
  Widget build(BuildContext context) {
    final p = context.pearl;
    final t = L.of(context);
    final tablet = context.isTablet;
    final run = state.visibleSizes;

    return ListView(
      padding: const EdgeInsets.fromLTRB(
          PearlMetrics.pad, 18, PearlMetrics.pad, 30),
      children: [
        Text(
          t.whichSize,
          style: PearlText.display(tablet ? 30 : 26).copyWith(color: p.ink),
        ),
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

class _SizeWrap extends StatelessWidget {
  const _SizeWrap({
    required this.sizes,
    required this.selected,
    required this.onTap,
  });

  final List<SizeOption> sizes;
  final String? selected;
  final void Function(String) onTap;

  @override
  Widget build(BuildContext context) {
    return LayoutBuilder(
      builder: (context, constraints) {
        // The count is a setting, not a rule read off the width.
        //
        // It was a rule, and the rule was wrong on the screens that matter: a
        // chip's width is worth more than a fourth column on a phone, and a
        // counter-top tablet has the room for six. Neither is derivable from
        // the width alone — the same 11" tablet is a display in one branch and
        // a handheld in another — so Appearance asks, and this obeys.
        const gap = 10.0;
        final columns = context.watch<ThemeCubit>().state.sizeColumns;
        final width = (constraints.maxWidth - gap * (columns - 1)) / columns;
        return Wrap(
          spacing: gap,
          runSpacing: gap,
          children: [
            for (final size in sizes)
              SizedBox(
                width: width,
                child: PearlChip(
                  label: size.size,
                  // No count. The run is long and a number on every chip made
                  // it a table to read rather than a row to scan — the size is
                  // the only thing being chosen here.
                  height: 58,
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
  Widget build(BuildContext context) => const Padding(
        padding: EdgeInsets.all(PearlMetrics.pad),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.stretch,
          children: [
            SkeletonBlock(height: 34, width: 200),
            SizedBox(height: 24),
            SkeletonBlock(height: 54),
            SizedBox(height: 10),
            SkeletonBlock(height: 54),
            SizedBox(height: 10),
            SkeletonBlock(height: 54),
          ],
        ),
      );
}

class _LeftColumn extends StatelessWidget {
  const _LeftColumn({required this.state});

  final FunnelState state;

  @override
  Widget build(BuildContext context) => SingleChildScrollView(
        padding: const EdgeInsets.fromLTRB(14, 16, 14, 16),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.stretch,
          children: [
            FunnelColumn(
              state: state,
              current: FunnelStep.size,
              onReopen: (step) => reopenFunnelStep(context, step),
            ),
          ],
        ),
      );
}
