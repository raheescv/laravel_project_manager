import 'package:flutter/material.dart';
import 'package:flutter_bloc/flutter_bloc.dart';
import 'package:go_router/go_router.dart';

import '../../../shared/domain/constants/data_fetching_status.dart';
import '../../../shared/domain/helpers/responsive.dart';
import '../../../shared/domain/models/index.dart';
import '../../../shared/utils/components/theme/pearl_theme.dart';
import '../../../shared/utils/router/routes.dart';
import '../../../shared/widgets/chrome/app_top_bar.dart';
import '../../../shared/widgets/chrome/funnel_column.dart';
import '../../../shared/widgets/chrome/showcase_scaffold.dart';
import '../../../shared/widgets/pearl_widgets.dart';
import '../logic/funnel_cubit/funnel_cubit.dart';
import 'funnel_navigation.dart';

/// Step 2 — what kind of thing, now that the size is known.
///
/// Every count on this screen is already scoped to the chosen size (and to
/// stock, when that is on), so a category shown here always has something
/// behind it. Tapping one moves straight to the brand step.
class CategoryScreen extends StatelessWidget {
  const CategoryScreen({super.key});

  @override
  Widget build(BuildContext context) {
    final funnel = context.watch<FunnelCubit>();
    final state = funnel.state;

    return ShowcaseScaffold(
      topBar: AppTopBar(
        leading: IconSquare(Icons.arrow_back, size: 38, onTap: () => context.go(Routes.size)),
        title: context.isTablet
            ? null
            : FunnelBreadcrumbs(
                state: state,
                current: FunnelStep.category,
                onReopen: (step) => reopenFunnelStep(context, step),
              ),
      ),
      leftColumn: _LeftColumn(state: state),
      body: switch (state.categoriesStatus) {
        DataFetchStatus.failed => MessageState(
            title: 'The catalogue did not load',
            detail: state.errorMessage,
            actionLabel: 'Try again',
            onAction: funnel.loadCategories,
          ),
        DataFetchStatus.waiting when state.categories.isEmpty => const _CategorySkeleton(),
        _ => _CategoryBody(state: state),
      },
      bottomBar: PinnedBar(
        child: PearlButton(
          label: 'Everything in my size',
          ghost: true,
          onTap: () async {
            await funnel.skipCategory();
            if (context.mounted) context.go(Routes.brand);
          },
        ),
      ),
    );
  }
}

class _CategoryBody extends StatelessWidget {
  const _CategoryBody({required this.state});

  final FunnelState state;

  @override
  Widget build(BuildContext context) {
    final p = context.pearl;
    final tablet = context.isTablet;

    if (state.categories.isEmpty) {
      return MessageState(
        title: 'Nothing in this size',
        detail: state.inStockOnly
            ? 'No category has size ${state.size ?? '—'} on the shelf here. Turn off '
                '"In stock" at the top, or pick another size.'
            : 'Nothing in the catalogue carries size ${state.size ?? '—'}.',
        actionLabel: 'Choose another size',
        onAction: () => reopenFunnelStep(context, FunnelStep.size),
      );
    }

    return ListView(
      padding: const EdgeInsets.fromLTRB(
          PearlMetrics.pad, 18, PearlMetrics.pad, 30),
      children: [
        Text(
          'Which department?',
          style: PearlText.display(tablet ? 30 : 26).copyWith(color: p.ink),
        ),
        const SizedBox(height: 10),
        Text(
          state.size == null
              ? 'Everything below is stocked in the store shown at the top.'
              : 'Counts are for size ${state.size} in the store shown at the top.',
          style: PearlText.body(12).copyWith(color: p.muted),
        ),
        const ColumnHeading('Departments'),
        _CategoryGrid(categories: state.categories),
      ],
    );
  }
}

class _CategoryGrid extends StatelessWidget {
  const _CategoryGrid({required this.categories});

  final List<CategoryOption> categories;

  @override
  Widget build(BuildContext context) {
    return LayoutBuilder(
      builder: (context, constraints) {
        // Same target-width sizing as the size run, one notch wider: these
        // tiles hold a word rather than a number.
        const target = 168.0;
        const gap = PearlMetrics.gap;
        final columns =
            ((constraints.maxWidth + gap) / (target + gap)).round().clamp(2, 8);
        return GridView.builder(
          shrinkWrap: true,
          physics: const NeverScrollableScrollPhysics(),
          // A fixed height rather than an aspect ratio: the tile holds two lines
          // of type and nothing else, so letting it grow with the column width
          // just makes tall empty boxes on a wide screen.
          gridDelegate: SliverGridDelegateWithFixedCrossAxisCount(
            crossAxisCount: columns,
            crossAxisSpacing: gap,
            mainAxisSpacing: gap,
            mainAxisExtent: 118,
          ),
          itemCount: categories.length,
          itemBuilder: (context, i) => _CategoryTile(category: categories[i]),
        );
      },
    );
  }
}

class _CategoryTile extends StatelessWidget {
  const _CategoryTile({required this.category});

  final CategoryOption category;

  @override
  Widget build(BuildContext context) {
    final p = context.pearl;
    return Hairline(
      onTap: () async {
        await context.read<FunnelCubit>().chooseCategory(category);
        if (context.mounted) context.go(Routes.brand);
      },
      padding: const EdgeInsets.all(13),
      // Typographic rather than illustrated: categories have no artwork in the
      // catalogue, and a stock icon standing in for one reads as a placeholder.
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Text(
            '${category.productCount}',
            style: PearlText.display(22).copyWith(color: p.ink),
          ),
          const SizedBox(height: 3),
          Text(
            'styles'.toUpperCase(),
            style: PearlText.micro.copyWith(fontSize: 8.5, color: p.faint),
          ),
          const Spacer(),
          Text(
            category.name.toUpperCase(),
            maxLines: 2,
            overflow: TextOverflow.ellipsis,
            style: PearlText.productName(11).copyWith(color: p.ink),
          ),
        ],
      ),
    );
  }
}

class _CategorySkeleton extends StatelessWidget {
  const _CategorySkeleton();

  @override
  Widget build(BuildContext context) => GridView.builder(
        padding: const EdgeInsets.all(PearlMetrics.pad),
        shrinkWrap: true,
        gridDelegate: const SliverGridDelegateWithFixedCrossAxisCount(
          crossAxisCount: 3,
          crossAxisSpacing: PearlMetrics.gap,
          mainAxisSpacing: PearlMetrics.gap,
          mainAxisExtent: 118,
        ),
        itemCount: 6,
        itemBuilder: (context, _) => const SkeletonBlock(height: 118),
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
              current: FunnelStep.category,
              onReopen: (step) => reopenFunnelStep(context, step),
            ),
          ],
        ),
      );
}
