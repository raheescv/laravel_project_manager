import 'package:flutter/material.dart';
import 'package:flutter_bloc/flutter_bloc.dart';
import 'package:go_router/go_router.dart';

import '../../../shared/domain/constants/data_fetching_status.dart';
import '../../../shared/domain/helpers/responsive.dart';
import '../../../shared/utils/components/theme/pearl_theme.dart';
import '../../../shared/utils/router/routes.dart';
import '../../../shared/widgets/chrome/app_top_bar.dart';
import '../../../shared/widgets/chrome/funnel_column.dart';
import '../../../shared/widgets/chrome/showcase_scaffold.dart';
import '../../../shared/widgets/pearl_widgets.dart';
import '../../../shared/widgets/product_card.dart';
import '../logic/funnel_cubit/funnel_cubit.dart';
import '../logic/product_list_cubit/product_list_cubit.dart';
import 'filter_panel.dart';
import 'funnel_navigation.dart';

/// Step 4 — the results.
///
/// On tablet the filters are permanent beside the grid rather than behind a
/// sheet, so changing one is a tap and the effect is visible without anything
/// opening or closing.
class ResultsScreen extends StatelessWidget {
  const ResultsScreen({super.key});

  @override
  Widget build(BuildContext context) {
    final funnel = context.read<FunnelCubit>().state;
    return BlocProvider<ProductListCubit>(
      create: (_) => ProductListCubit(
        filters: ProductFilters(
          mainCategoryId: funnel.category?.id,
          brandId: funnel.brand?.id,
          size: funnel.size,
          inStockOnly: funnel.inStockOnly,
        ),
      )
        ..load()
        ..loadColors(),
      child: const _ResultsView(),
    );
  }
}

class _ResultsView extends StatefulWidget {
  const _ResultsView();

  @override
  State<_ResultsView> createState() => _ResultsViewState();
}

class _ResultsViewState extends State<_ResultsView> {
  final ScrollController _scroll = ScrollController();

  @override
  void initState() {
    super.initState();
    _scroll.addListener(_onScroll);
  }

  @override
  void dispose() {
    _scroll
      ..removeListener(_onScroll)
      ..dispose();
    super.dispose();
  }

  /// Fetch the next page while there is still a screenful to scroll through, so
  /// a fast flick never lands on a blank grid.
  void _onScroll() {
    if (!_scroll.hasClients) return;
    final remaining = _scroll.position.maxScrollExtent - _scroll.position.pixels;
    if (remaining < 900) context.read<ProductListCubit>().loadMore();
  }

  @override
  Widget build(BuildContext context) {
    final funnelState = context.watch<FunnelCubit>().state;
    final list = context.watch<ProductListCubit>();
    final state = list.state;

    final scaffold = ShowcaseScaffold(
      topBar: AppTopBar(
        leading: IconSquare(Icons.arrow_back, size: 38, onTap: () => context.go(Routes.brand)),
        title: context.isTablet
            ? null
            : FunnelBreadcrumbs(
                state: funnelState,
                current: FunnelStep.results,
                onReopen: (step) => reopenFunnelStep(context, step),
              ),
      ),
      leftColumn: SingleChildScrollView(
        padding: const EdgeInsets.fromLTRB(14, 16, 14, 24),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.stretch,
          children: [
            FunnelColumn(
              state: funnelState,
              current: FunnelStep.results,
              onReopen: (step) => reopenFunnelStep(context, step),
            ),
            const SizedBox(height: 6),
            FilterPanel(
              state: state,
              onChanged: (filters) => list.apply(filters),
            ),
          ],
        ),
      ),
      body: Column(
        children: [
          _Toolbar(state: state, onChanged: list.apply),
          Expanded(child: _Results(state: state, scroll: _scroll)),
        ],
      ),
      bottomBar: context.isTablet
          ? null
          : PinnedBar(
              child: PearlButton(
                label: state.filters.activeCount == 0
                    ? 'Filter and sort'
                    : 'Filters · ${state.filters.activeCount}',
                icon: Icons.tune,
                ghost: true,
                onTap: () => showFilterSheet(context, list),
              ),
            ),
    );

    // "In stock" is owned by the top bar, not by this list, so the grid follows
    // it rather than keeping a copy that can drift out of step.
    return BlocListener<FunnelCubit, FunnelState>(
      listenWhen: (before, after) => before.inStockOnly != after.inStockOnly,
      listener: (context, funnel) =>
          list.apply(state.filters.copyWith(inStockOnly: funnel.inStockOnly)),
      child: scaffold,
    );
  }
}

class _Toolbar extends StatelessWidget {
  const _Toolbar({required this.state, required this.onChanged});

  final ProductListState state;
  final void Function(ProductFilters) onChanged;

  @override
  Widget build(BuildContext context) {
    final p = context.pearl;
    final ascending = state.filters.sortDirection == 'asc';
    return Container(
      padding: const EdgeInsets.fromLTRB(PearlMetrics.pad, 14, PearlMetrics.pad, 14),
      decoration: BoxDecoration(border: Border(bottom: BorderSide(color: p.line))),
      child: Row(
        children: [
          Expanded(
            child: Text(
              state.status.isWaiting && state.items.isEmpty
                  ? 'Loading…'.toUpperCase()
                  : state.totalIsExact
                      ? '${state.total} products'.toUpperCase()
                      : '${state.items.length} shown'.toUpperCase(),
              style: PearlText.section.copyWith(color: p.ink),
            ),
          ),
          _SortButton(
            label: state.filters.sortBy == 'name' ? 'Name' : 'Price',
            ascending: ascending,
            onTap: () {
              final nextBy = state.filters.sortBy == 'name' ? 'mrp' : 'name';
              onChanged(state.filters.copyWith(sortBy: nextBy));
            },
            onToggleDirection: () => onChanged(
              state.filters.copyWith(sortDirection: ascending ? 'desc' : 'asc'),
            ),
          ),
        ],
      ),
    );
  }
}

class _SortButton extends StatelessWidget {
  const _SortButton({
    required this.label,
    required this.ascending,
    required this.onTap,
    required this.onToggleDirection,
  });

  final String label;
  final bool ascending;
  final VoidCallback onTap;
  final VoidCallback onToggleDirection;

  @override
  Widget build(BuildContext context) {
    final p = context.pearl;
    return Row(
      children: [
        InkWell(
          onTap: onTap,
          child: Container(
            height: 34,
            padding: const EdgeInsets.symmetric(horizontal: 13),
            alignment: Alignment.center,
            decoration: BoxDecoration(border: Border.all(color: p.line)),
            child: Text(
              'Sort · $label'.toUpperCase(),
              style: PearlText.micro.copyWith(fontSize: 8.5, color: p.ink),
            ),
          ),
        ),
        const SizedBox(width: 6),
        IconSquare(
          ascending ? Icons.arrow_upward : Icons.arrow_downward,
          size: 34,
          onTap: onToggleDirection,
        ),
      ],
    );
  }
}

class _Results extends StatelessWidget {
  const _Results({required this.state, required this.scroll});

  final ProductListState state;
  final ScrollController scroll;

  @override
  Widget build(BuildContext context) {
    if (state.status.isFailed && state.items.isEmpty) {
      return MessageState(
        title: 'The list did not load',
        detail: state.errorMessage,
        actionLabel: 'Try again',
        onAction: context.read<ProductListCubit>().load,
      );
    }
    if (state.status.isWaiting && state.items.isEmpty) {
      return const _GridSkeleton();
    }
    if (state.items.isEmpty) {
      return MessageState(
        title: 'Nothing matches',
        detail: 'Try clearing the filters, or change the store at the top of the screen.',
        actionLabel: 'Clear filters',
        onAction: () => context.read<ProductListCubit>().apply(
              ProductFilters(
                mainCategoryId: state.filters.mainCategoryId,
                brandId: state.filters.brandId,
                size: state.filters.size,
              ),
            ),
      );
    }

    return ProductGrid(
      products: state.items,
      controller: scroll,
      padding: const EdgeInsets.fromLTRB(
          PearlMetrics.pad, 18, PearlMetrics.pad, 30),
      onTap: (product) => context.push(Routes.productById(product.id)),
      footer: _Footer(state: state),
    );
  }
}

class _Footer extends StatelessWidget {
  const _Footer({required this.state});

  final ProductListState state;

  @override
  Widget build(BuildContext context) {
    final p = context.pearl;
    final label = state.loadingMore
        ? 'Loading more'
        : state.hasMore
            ? 'Scroll for more'
            : '${state.items.length} of ${state.total}';
    return Padding(
      padding: const EdgeInsets.fromLTRB(PearlMetrics.pad, 4, PearlMetrics.pad, 40),
      child: Center(
        child: Text(
          label.toUpperCase(),
          style: PearlText.micro.copyWith(color: p.faint),
        ),
      ),
    );
  }
}

class _GridSkeleton extends StatelessWidget {
  const _GridSkeleton();

  @override
  Widget build(BuildContext context) => GridView.builder(
        padding: const EdgeInsets.all(PearlMetrics.pad),
        gridDelegate: const SliverGridDelegateWithFixedCrossAxisCount(
          crossAxisCount: 3,
          crossAxisSpacing: PearlMetrics.gap,
          mainAxisSpacing: 24,
          childAspectRatio: .74,
        ),
        itemCount: 9,
        itemBuilder: (_, __) => const SkeletonBlock(height: double.infinity),
      );
}
