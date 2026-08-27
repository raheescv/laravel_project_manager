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
import '../../../shared/widgets/product_card.dart';
import '../logic/funnel_cubit/funnel_cubit.dart';
import '../logic/product_list_cubit/product_list_cubit.dart';
import 'funnel_navigation.dart';

/// Step 2 — the size run.
///
/// Tapping a size is the answer: it commits the choice and moves to the brand
/// step, because a chip that only highlights and waits for a second tap on a
/// button somewhere else is a step people stall on.
///
/// The wide layout is the exception. There the aside can show the consequence
/// of the choice — a live count and the first few products in that size — so a
/// tap fills it and Continue commits. Deciding without seeing what it gets you
/// is the thing the phone funnel cannot avoid and the big screen can.
class SizeScreen extends StatelessWidget {
  const SizeScreen({super.key});

  @override
  Widget build(BuildContext context) {
    return BlocProvider<ProductListCubit>(
      create: (_) => ProductListCubit(),
      child: const _SizeView(),
    );
  }
}

class _SizeView extends StatefulWidget {
  const _SizeView();

  @override
  State<_SizeView> createState() => _SizeViewState();
}

class _SizeViewState extends State<_SizeView> {
  String? _previewSize;

  /// Wide tablet only: fill the aside with what this size gets you. Narrower
  /// layouts have nowhere to show it, so there a tap goes straight on.
  void _preview(BuildContext context, FunnelState state, String size) {
    if (_previewSize == size) return;
    setState(() => _previewSize = size);
    context.read<ProductListCubit>().apply(ProductFilters(
          mainCategoryId: state.category?.id,
          size: size,
          inStockOnly: state.inStockOnly,
        ));
  }

  Future<void> _choose(BuildContext context, String size) async {
    await context.read<FunnelCubit>().chooseSize(size);
    if (context.mounted) context.go(Routes.brand);
  }

  @override
  Widget build(BuildContext context) {
    final funnel = context.watch<FunnelCubit>();
    final state = funnel.state;

    return ShowcaseScaffold(
      topBar: AppTopBar(
        leading: IconSquare(Icons.arrow_back, size: 38, onTap: () => context.go(Routes.browse)),
        title: context.isTablet
            ? null
            : FunnelBreadcrumbs(
                state: state,
                current: FunnelStep.size,
                onReopen: (step) => reopenFunnelStep(context, step),
              ),
      ),
      leftColumn: _LeftColumn(state: state),
      rightColumn: _Aside(state: state, previewSize: _previewSize),
      body: switch (state.sizesStatus) {
        DataFetchStatus.failed => MessageState(
            title: 'Sizes did not load',
            detail: state.errorMessage,
            actionLabel: 'Try again',
            onAction: funnel.loadSizes,
          ),
        DataFetchStatus.waiting when state.sizes.isEmpty => const _SizeSkeleton(),
        _ => _SizeBody(
            state: state,
            previewSize: _previewSize,
            onTapSize: (size) => context.isWide
                ? _preview(context, state, size)
                : _choose(context, size),
          ),
      },
      bottomBar: PinnedBar(
        child: Row(
          children: [
            Expanded(
              child: PearlButton(
                label: 'Any size',
                ghost: true,
                onTap: () async {
                  await funnel.skipSize();
                  if (context.mounted) context.go(Routes.brand);
                },
              ),
            ),
            // Only the wide layout previews first, so only it needs a separate
            // Continue. Everywhere else the chip itself is the button.
            if (context.isWide) ...[
              const SizedBox(width: 10),
              Expanded(
                flex: 2,
                child: PearlButton(
                  label: _previewSize == null ? 'Choose a size' : 'Continue · ${_previewSize!}',
                  icon: Icons.arrow_forward,
                  onTap: _previewSize == null ? null : () => _choose(context, _previewSize!),
                ),
              ),
            ],
          ],
        ),
      ),
    );
  }
}

class _SizeBody extends StatelessWidget {
  const _SizeBody({
    required this.state,
    required this.previewSize,
    required this.onTapSize,
  });

  final FunnelState state;
  final String? previewSize;
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
          'Sizes with nothing on the shelf are struck through — ask a colleague and '
          'we can check the other stores.',
          style: PearlText.body(12).copyWith(color: p.muted),
        ),
        if (young.isNotEmpty) ...[
          const ColumnHeading('Young'),
          _SizeWrap(sizes: young, selected: previewSize, onTap: onTapSize),
        ],
        if (adult.isNotEmpty) ...[
          const ColumnHeading('Adult'),
          _SizeWrap(sizes: adult, selected: previewSize, onTap: onTapSize),
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
        final columns = switch (constraints.maxWidth) {
          >= 900 => 7,
          >= 640 => 6,
          >= 420 => 5,
          _ => 4,
        };
        const gap = 10.0;
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

/// The right-hand column: what choosing this size actually gets you.
class _Aside extends StatelessWidget {
  const _Aside({required this.state, required this.previewSize});

  final FunnelState state;
  final String? previewSize;

  @override
  Widget build(BuildContext context) {
    final p = context.pearl;
    final list = context.watch<ProductListCubit>().state;
    final count = previewSize == null ? null : list.total;
    return SingleChildScrollView(
      padding: const EdgeInsets.fromLTRB(16, 16, 16, 16),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.stretch,
        children: [
          Hairline(
            filled: true,
            padding: const EdgeInsets.all(15),
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Text(
                  count?.toString() ?? '—',
                  style: PearlText.display(30).copyWith(color: p.ink),
                ),
                const SizedBox(height: 6),
                Text(
                  previewSize == null
                      ? 'Pick a size to see what is in it'
                      : 'products in size $previewSize',
                  style: PearlText.body(11).copyWith(color: p.muted),
                ),
              ],
            ),
          ),
          const ColumnHeading('In this size'),
          _LivePreview(previewSize: previewSize, columns: 2),
        ],
      ),
    );
  }
}

class _LivePreview extends StatelessWidget {
  const _LivePreview({required this.previewSize, this.columns = 3});

  final String? previewSize;
  final int columns;

  @override
  Widget build(BuildContext context) {
    final p = context.pearl;
    final state = context.watch<ProductListCubit>().state;

    if (previewSize == null) {
      return Text(
        'Nothing chosen yet.',
        style: PearlText.body(11.5).copyWith(color: p.faint),
      );
    }
    if (state.status.isWaiting && state.items.isEmpty) {
      return const SkeletonBlock(height: 150);
    }
    if (state.items.isEmpty) {
      return Text(
        'Nothing in this size at this store.',
        style: PearlText.body(11.5).copyWith(color: p.faint),
      );
    }

    final rows = state.items.take(columns * 2).toList();
    return LayoutBuilder(
      builder: (context, constraints) {
        const gap = PearlMetrics.gap;
        final width = (constraints.maxWidth - gap * (columns - 1)) / columns;
        return Wrap(
          spacing: gap,
          runSpacing: 18,
          children: [
            for (final product in rows)
              SizedBox(
                width: width,
                child: ProductCard(
                  product: product,
                  width: width,
                  compact: true,
                  onTap: () => context.push(Routes.productById(product.id)),
                ),
              ),
          ],
        );
      },
    );
  }
}
