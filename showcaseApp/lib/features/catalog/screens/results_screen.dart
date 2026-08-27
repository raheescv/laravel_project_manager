import 'package:flutter/material.dart';
import 'package:flutter_bloc/flutter_bloc.dart';
import 'package:go_router/go_router.dart';

import '../../../shared/domain/constants/data_fetching_status.dart';
import '../../../shared/logic/funnel_cubit/funnel_cubit.dart';
import '../../../shared/logic/product_list_cubit/product_list_cubit.dart';
import '../../../shared/utils/components/theme/pearl_theme.dart';
import '../../../shared/utils/router/funnel_navigation.dart';
import '../../../shared/utils/router/routes.dart';
import '../../../shared/widgets/chrome/app_top_bar.dart';
import '../../../shared/widgets/chrome/funnel_breadcrumbs.dart';
import '../../../shared/widgets/chrome/showcase_scaffold.dart';
import '../../../shared/widgets/pearl_widgets.dart';
import '../../../shared/widgets/product_card.dart';
import '../widgets/filter_panel.dart';
import '../../../l10n/app_localizations.dart';

/// Step 3 — the results.
///
/// One column of tiles with the filters behind the bottom bar. There is no
/// aside: the panel is portrait and a customer reads it standing up, so width
/// spent on a permanent filter rail is width taken off the photographs.
class ResultsScreen extends StatelessWidget {
  const ResultsScreen({super.key});

  @override
  Widget build(BuildContext context) {
    final funnel = context.read<FunnelCubit>().state;
    return BlocProvider<ProductListCubit>(
      create: (_) => ProductListCubit(
        filters: ProductFilters(
          brandId: funnel.brand?.id,
          size: funnel.size,
          inStockOnly: funnel.inStockOnly,
        ),
      )
        ..load()
        ..loadColors()
        ..loadCategories(),
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
    // Read, not watched. What the list is doing belongs to the grid and to the
    // one label that counts the filters, and both say so for themselves below.
    // Watched here, every page appended on a scroll and every flick of the
    // "loading more" flag rebuilt the whole screen with it: the wordmark, the
    // language and shop pills, the search field, the breadcrumb strip and both
    // halves of the bottom bar — none of which have anything to do with the
    // answer that just landed.
    final list = context.read<ProductListCubit>();

    final scaffold = ShowcaseScaffold(
      topBar: AppTopBar(
        leading: IconSquare(
          Icons.arrow_back,
          size: 38,
          prominent: true,
          onTap: () => context.leaveFunnelStep(FunnelStep.brand),
        ),
        title: FunnelBreadcrumbs(
          state: funnelState,
          current: FunnelStep.results,
          onReopen: (step) => reopenFunnelStep(context, step),
        ),
      ),
      body: BlocBuilder<ProductListCubit, ProductListState>(
        builder: (context, state) => Column(
          children: [
            _Toolbar(state: state, onChanged: list.apply),
            Expanded(child: _Results(state: state, scroll: _scroll)),
          ],
        ),
      ),
      bottomBar: PinnedBar(
        child: Row(
          children: [
            // The way out, beside the way to narrow down.
            //
            // The results are the deepest a customer gets without committing to
            // a product, and the only way back to step one from here was to
            // press Back through every answer they had given — or somebody
            // else's. It clears the visit exactly as the idle timer does.
            //
            // Named rather than left as a glyph: a house on its own is a guess,
            // and this is the one control somebody standing in a stranger's
            // results must not have to work out. Filled in the accent against
            // the ghost outline beside it, so which of the two takes you
            // somewhere is legible from across the shop.
            //
            // Half the bar each. The earlier two-sevenths said the right thing
            // about how often each is pressed, but it left "Back to home" too
            // narrow to spell itself — the label ellipsised to "Bac…", which
            // is worse than a button that looks slightly over-important. Equal
            // shares rather than each label's own width, so the bar fills the
            // panel at every text size and in both languages, and the longer
            // of the two still has room before it starts to clip.
            Expanded(
              child: PearlButton(
                label: L.of(context).backToHome,
                icon: Icons.home_outlined,
                height: _barControl,
                onTap: () => goHome(context),
              ),
            ),
            const SizedBox(width: PearlMetrics.gap),
            Expanded(
              // Only the count moves, so only the count listens.
              child: BlocSelector<ProductListCubit, ProductListState, int>(
                selector: (state) => state.filters.activeCount,
                builder: (context, active) => PearlButton(
                  label: active == 0
                      ? L.of(context).filterAndSort
                      : L.of(context).filtersCount(active),
                  icon: Icons.tune,
                  ghost: true,
                  height: _barControl,
                  onTap: () => showFilterSheet(context, list),
                ),
              ),
            ),
          ],
        ),
      ),
    );

    // "In stock" is owned by the top bar, not by this list, so the grid follows
    // it rather than keeping a copy that can drift out of step.
    return BlocListener<FunnelCubit, FunnelState>(
      listenWhen: (before, after) => before.inStockOnly != after.inStockOnly,
      // The filters as they are when the toggle is pressed, not as they were
      // when this frame was built — which is the same answer as before while
      // the screen rebuilt on every emit, and the right one now that it does
      // not.
      listener: (context, funnel) => list
          .apply(list.state.filters.copyWith(inStockOnly: funnel.inStockOnly)),
      child: scaffold,
    );
  }
}

/// One height for both halves of the bottom bar, so the square and the button
/// beside it read as a pair rather than as two controls that happen to share a
/// row. It is [PearlButton]'s own default; named here because the square has to
/// be told the same number.
const double _barControl = 46;

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
                  ? L.of(context).loadingEllipsis.toUpperCase()
                  // Every filter is the server's now, so the count it returns
                  // is the count on screen.
                  : L.of(context).productsCount(state.total).toUpperCase(),
              style: PearlText.section.copyWith(color: p.ink),
            ),
          ),
          _SortButton(
            label: state.filters.sortBy == 'name'
                ? L.of(context).sortName
                : L.of(context).sortPrice,
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
              L.of(context).sortBy(label).toUpperCase(),
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
        title: L.of(context).listDidNotLoad,
        detail: state.errorMessage,
        actionLabel: L.of(context).tryAgain,
        onAction: context.read<ProductListCubit>().load,
      );
    }
    if (state.status.isWaiting && state.items.isEmpty) {
      return const _GridSkeleton();
    }
    if (state.items.isEmpty) {
      return MessageState(
        title: L.of(context).nothingMatches,
        detail: L.of(context).nothingMatchesDetail,
        actionLabel: L.of(context).clearFilters,
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
        ? L.of(context).loadingMore
        : state.hasMore
            ? L.of(context).scrollForMore
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

/// Rehearses the real grid rather than guessing at it: the placeholder used to
/// be a fixed three across, so on the panel the page laid out three columns,
/// then dropped to two the moment the products landed. It reads the same
/// Appearance count the grid does, so the change of hands is invisible.
class _GridSkeleton extends StatelessWidget {
  const _GridSkeleton();

  @override
  Widget build(BuildContext context) {
    final columns = ProductGrid.columnsOf(context);
    return GridView.builder(
      padding: const EdgeInsets.all(PearlMetrics.pad),
      gridDelegate: SliverGridDelegateWithFixedCrossAxisCount(
        crossAxisCount: columns,
        crossAxisSpacing: PearlMetrics.gap,
        mainAxisSpacing: 24,
        childAspectRatio: .74,
      ),
      itemCount: columns * 3,
      itemBuilder: (_, __) => const SkeletonBlock(height: double.infinity),
    );
  }
}
