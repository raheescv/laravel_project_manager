import 'package:flutter/material.dart';
import 'package:flutter_bloc/flutter_bloc.dart';
import 'package:go_router/go_router.dart';

import '../../../shared/domain/constants/data_fetching_status.dart';
import '../../../shared/domain/helpers/responsive.dart';
import '../../../shared/domain/models/index.dart';
import '../../../shared/utils/components/theme/pearl_theme.dart';
import '../../../shared/utils/router/routes.dart';
import '../../../shared/widgets/chrome/app_top_bar.dart';
import '../../../shared/widgets/chrome/showcase_scaffold.dart';
import '../../../shared/widgets/pearl_widgets.dart';
import '../../../shared/widgets/product_card.dart';
import '../logic/funnel_cubit/funnel_cubit.dart';
import '../logic/product_list_cubit/product_list_cubit.dart';

/// Step 1 — the entry screen. Headline, categories, and a strip of what is
/// actually on the shelf today so the tablet is never a blank menu.
class BrowseScreen extends StatelessWidget {
  const BrowseScreen({super.key});

  @override
  Widget build(BuildContext context) {
    return BlocProvider<ProductListCubit>(
      create: (_) => ProductListCubit(
        filters: const ProductFilters(inStockOnly: true, sortBy: 'name'),
      )..load(),
      child: const _BrowseView(),
    );
  }
}

class _BrowseView extends StatelessWidget {
  const _BrowseView();

  @override
  Widget build(BuildContext context) {
    final state = context.watch<FunnelCubit>().state;
    return ShowcaseScaffold(
      topBar: const AppTopBar(),
      body: switch (state.categoriesStatus) {
        DataFetchStatus.failed => MessageState(
            title: 'The catalogue did not load',
            detail: state.errorMessage,
            actionLabel: 'Try again',
            onAction: () => context.read<FunnelCubit>().loadCategories(),
          ),
        _ => _BrowseBody(state: state),
      },
    );
  }
}

class _BrowseBody extends StatelessWidget {
  const _BrowseBody({required this.state});

  final FunnelState state;

  @override
  Widget build(BuildContext context) {
    final tablet = context.isTablet;
    return ListView(
      padding: EdgeInsets.fromLTRB(
        PearlMetrics.pad,
        tablet ? 18 : 14,
        PearlMetrics.pad,
        40,
      ),
      children: [
        const _Headline(),
        const SizedBox(height: 26),
        SectionHeading(
          'Categories',
          meta: state.categories.isEmpty
              ? null
              : '${state.categories.length} groups · ${_totalProducts(state.categories)} styles',
        ),
        if (state.categoriesStatus.isWaiting && state.categories.isEmpty)
          const _CategorySkeleton()
        else
          _CategoryGrid(categories: state.categories),
        const SizedBox(height: 30),
        const _TrendingStrip(),
      ],
    );
  }

  static String _totalProducts(List<CategoryOption> rows) =>
      rows.fold<int>(0, (sum, c) => sum + c.productCount).toString();
}

class _Headline extends StatelessWidget {
  const _Headline();

  @override
  Widget build(BuildContext context) {
    final p = context.pearl;
    final tablet = context.isTablet;
    return Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        Text('The showcase'.toUpperCase(),
            style: PearlText.micro.copyWith(color: p.faint)),
        const SizedBox(height: 12),
        Text(
          'Find your pair',
          style: PearlText.display(tablet ? 40 : 30).copyWith(color: p.ink),
        ),
        const SizedBox(height: 12),
        ConstrainedBox(
          constraints: const BoxConstraints(maxWidth: 460),
          child: Text(
            'Pick a category, then a size. Everything you see is stocked in the store '
            'shown at the top of this screen.',
            style: PearlText.body(tablet ? 13 : 12.5).copyWith(color: p.muted),
          ),
        ),
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
        final columns = switch (constraints.maxWidth) {
          >= 1000 => 6,
          >= 760 => 4,
          >= 460 => 3,
          _ => 2,
        };
        return GridView.builder(
          shrinkWrap: true,
          physics: const NeverScrollableScrollPhysics(),
          // A fixed height rather than an aspect ratio: the tile holds two lines
          // of type and nothing else, so letting it grow with the column width
          // just makes six tall empty boxes on a wide screen.
          gridDelegate: SliverGridDelegateWithFixedCrossAxisCount(
            crossAxisCount: columns,
            crossAxisSpacing: PearlMetrics.gap,
            mainAxisSpacing: PearlMetrics.gap,
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
        if (context.mounted) context.go(Routes.size);
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
        shrinkWrap: true,
        physics: const NeverScrollableScrollPhysics(),
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

/// What is on the shelf right now. Deliberately the only place on the entry
/// screen that shows prices — the categories above are a menu, not a sales pitch.
class _TrendingStrip extends StatelessWidget {
  const _TrendingStrip();

  @override
  Widget build(BuildContext context) {
    final state = context.watch<ProductListCubit>().state;
    if (state.status.isFailed || (state.status.isSuccess && state.items.isEmpty)) {
      return const SizedBox.shrink();
    }
    final rows = state.items.take(8).toList();
    final tablet = context.isTablet;
    final cardWidth = tablet ? 178.0 : 136.0;
    return Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        const SectionHeading('In store now', meta: 'stocked here today'),
        SizedBox(
          height: cardWidth + 86,
          child: state.items.isEmpty
              ? const SkeletonBlock(height: 120)
              : ListView.separated(
                  scrollDirection: Axis.horizontal,
                  itemCount: rows.length,
                  separatorBuilder: (_, __) => const SizedBox(width: PearlMetrics.gap),
                  itemBuilder: (context, i) => SizedBox(
                    width: cardWidth,
                    child: ProductCard(
                      product: rows[i],
                      width: cardWidth,
                      compact: true,
                      onTap: () => context.push(Routes.productById(rows[i].id)),
                    ),
                  ),
                ),
        ),
      ],
    );
  }
}
