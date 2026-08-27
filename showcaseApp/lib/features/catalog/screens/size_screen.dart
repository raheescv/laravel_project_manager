import 'package:flutter/material.dart';
import 'package:flutter_bloc/flutter_bloc.dart';

import '../../../shared/domain/constants/data_fetching_status.dart';
import '../../../shared/domain/helpers/responsive.dart';
import '../../../shared/domain/models/index.dart';
import '../../../shared/utils/components/theme/pearl_theme.dart';
import '../../../shared/widgets/chrome/app_top_bar.dart';
import '../../../shared/widgets/chrome/funnel_column.dart';
import '../../../shared/widgets/chrome/showcase_scaffold.dart';
import '../../../shared/widgets/pearl_widgets.dart';
import '../logic/funnel_cubit/funnel_cubit.dart';
import 'funnel_navigation.dart';

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
            title: 'Sizes did not load',
            detail: state.errorMessage,
            actionLabel: 'Try again',
            onAction: funnel.loadSizes,
          ),
        DataFetchStatus.waiting when state.sizes.isEmpty => const _SizeSkeleton(),
        _ => _SizeBody(state: state, onTapSize: (size) => _choose(context, size)),
      },
      // "Any size" is the only thing left to press, so it takes the bar.
      bottomBar: PinnedBar(
        child: PearlButton(
          label: 'Any size',
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
    final tablet = context.isTablet;
    final young = state.youngSizes;
    final adult = state.adultSizes;

    return ListView(
      padding: const EdgeInsets.fromLTRB(
          PearlMetrics.pad, 18, PearlMetrics.pad, 30),
      children: [
        Text(
          'Which size?',
          style: PearlText.display(tablet ? 30 : 26).copyWith(color: p.ink),
        ),
        const SizedBox(height: 10),
        Text(
          state.inStockOnly
              ? 'Only sizes on the shelf here are shown. Turn off "In stock" at the '
                  'top to see the whole size run.'
              : 'Sizes with nothing on the shelf are struck through — ask a colleague '
                  'and we can check the other stores.',
          style: PearlText.body(12).copyWith(color: p.muted),
        ),
        if (young.isNotEmpty) ...[
          const ColumnHeading('Young'),
          // `state.size` rather than a local selection: the only thing worth
          // marking is the answer already given, for someone who reopened the
          // step to change it.
          _SizeWrap(sizes: young, selected: state.size, onTap: onTapSize),
        ],
        if (adult.isNotEmpty) ...[
          const ColumnHeading('Adult'),
          _SizeWrap(sizes: adult, selected: state.size, onTap: onTapSize),
        ],
        if (young.isEmpty && adult.isEmpty)
          const Padding(
            padding: EdgeInsets.only(top: 40),
            child: MessageState(
              title: 'No sizes recorded',
              detail: 'Nothing in this category carries a size yet. Continue to see '
                  'everything in it.',
            ),
          ),
      ],
    );
  }
}

class _SizeWrap extends StatelessWidget {
  const _SizeWrap({required this.sizes, required this.selected, required this.onTap});

  final List<SizeOption> sizes;
  final String? selected;
  final void Function(String) onTap;

  @override
  Widget build(BuildContext context) {
    return LayoutBuilder(
      builder: (context, constraints) {
        // Fit as many chips as the row will take at roughly [target] wide,
        // rather than picking a column count per breakpoint. A size chip holds
        // four characters however wide the screen is, so the width is what
        // should stay put — the number of columns is the thing that gives.
        const target = 100.0;
        const gap = 10.0;
        final columns =
            ((constraints.maxWidth + gap) / (target + gap)).round().clamp(4, 12);
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
                  sub: size.inStock ? '${size.stockTotal} left' : 'none',
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
