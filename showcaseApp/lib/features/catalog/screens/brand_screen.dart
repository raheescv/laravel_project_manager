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
import '../../../shared/widgets/photo.dart';
import '../logic/funnel_cubit/funnel_cubit.dart';
import 'funnel_navigation.dart';

/// Step 2 — brand, and the most skippable step in the funnel. "Every brand" is
/// a full-width button rather than a link, because most customers want it.
///
/// The brands are a grid of logos rather than a list of names: on a shop floor
/// a customer recognises the mark long before they read the word, and the same
/// tile density as the size run keeps the funnel feeling like one screen
/// repeated rather than three different screens.
class BrandScreen extends StatelessWidget {
  const BrandScreen({super.key});

  @override
  Widget build(BuildContext context) {
    final funnel = context.watch<FunnelCubit>();
    final state = funnel.state;

    return ShowcaseScaffold(
      topBar: AppTopBar(
        leading: IconSquare(
          Icons.arrow_back,
          size: 38,
          onTap: () => context.leaveFunnelStep(FunnelStep.size),
        ),
        title: context.isTablet
            ? null
            : FunnelBreadcrumbs(
                state: state,
                current: FunnelStep.brand,
                onReopen: (step) => reopenFunnelStep(context, step),
              ),
      ),
      leftColumn: _LeftColumn(state: state),
      body: switch (state.brandsStatus) {
        DataFetchStatus.failed => MessageState(
            title: 'Brands did not load',
            detail: state.errorMessage,
            actionLabel: 'Try again',
            onAction: funnel.loadBrands,
          ),
        DataFetchStatus.waiting when state.brands.isEmpty => const _BrandSkeleton(),
        _ => _BrandBody(state: state),
      },
      bottomBar: PinnedBar(
        child: PearlButton(
          label: state.size == null
              ? 'Show every brand'
              : 'Show all ${state.brandTotal} in size ${state.size}',
          icon: Icons.arrow_forward,
          onTap: () {
            funnel.skipBrand();
            context.goToFunnelStep(FunnelStep.results);
          },
        ),
      ),
    );
  }
}

class _BrandBody extends StatelessWidget {
  const _BrandBody({required this.state});

  final FunnelState state;

  @override
  Widget build(BuildContext context) {
    final p = context.pearl;
    if (state.brands.isEmpty) {
      final inSize = state.size == null ? '' : ' in size ${state.size}';
      return MessageState(
        title: state.size == null ? 'No brands here' : 'No brands in this size',
        detail: state.inStockOnly
            ? 'Nothing$inSize is on the shelf here right now. Turn off "In stock" '
                'at the top to see what we can order in.'
            : 'Nothing in the catalogue carries a brand$inSize.',
        actionLabel: state.size == null ? null : 'Choose another size',
        onAction: state.size == null ? null : () => reopenFunnelStep(context, FunnelStep.size),
      );
    }

    return ListView(
      padding: const EdgeInsets.fromLTRB(
          PearlMetrics.pad, 18, PearlMetrics.pad, 30),
      children: [
        Text(
          'Which brand?',
          style: PearlText.display(context.isTablet ? 30 : 26).copyWith(color: p.ink),
        ),
        const SizedBox(height: 18),
        SectionHeading(
          'Available',
          meta: '${state.brands.length}'
              '${state.inStockOnly ? ' with stock' : ''}'
              '${state.size == null ? '' : ' in size ${state.size}'}',
        ),
        _BrandGrid(
          brands: state.brands,
          onTap: (brand) {
            context.read<FunnelCubit>().chooseBrand(brand);
            context.goToFunnelStep(FunnelStep.results);
          },
        ),
      ],
    );
  }
}

class _BrandGrid extends StatelessWidget {
  const _BrandGrid({required this.brands, required this.onTap});

  final List<BrandOption> brands;
  final void Function(BrandOption) onTap;

  @override
  Widget build(BuildContext context) {
    return LayoutBuilder(
      builder: (context, constraints) {
        // Wider tiles than the size chips: a logo needs room to be readable at
        // arm's length, where a two-character size does not.
        final columns = switch (constraints.maxWidth) {
          >= 900 => 5,
          >= 640 => 4,
          >= 420 => 3,
          _ => 2,
        };
        const gap = 10.0;
        final width = (constraints.maxWidth - gap * (columns - 1)) / columns;
        return Wrap(
          spacing: gap,
          runSpacing: gap,
          children: [
            for (final brand in brands)
              SizedBox(
                width: width,
                child: _BrandTile(brand: brand, width: width, onTap: () => onTap(brand)),
              ),
          ],
        );
      },
    );
  }
}

class _BrandTile extends StatelessWidget {
  const _BrandTile({required this.brand, required this.width, required this.onTap});

  final BrandOption brand;
  final double width;
  final VoidCallback onTap;

  @override
  Widget build(BuildContext context) {
    final p = context.pearl;
    return InkWell(
      onTap: onTap,
      child: Container(
        decoration: BoxDecoration(border: Border.all(color: p.line)),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.stretch,
          children: [
            AspectRatio(
              aspectRatio: 1.5,
              child: brand.hasLogo
                  // The mark sits on the tile's own ground rather than a white
                  // card: most of these are transparent PNGs and a white plate
                  // behind them is the only thing that would show in dark mode.
                  ? Photo(
                      url: brand.imagePath,
                      width: width,
                      padding: const EdgeInsets.all(14),
                    )
                  : Center(
                      child: Text(
                        brand.monogram,
                        style: PearlText.display(22).copyWith(color: p.muted),
                      ),
                    ),
            ),
            Container(
              padding: const EdgeInsets.fromLTRB(10, 9, 10, 10),
              decoration: BoxDecoration(
                border: Border(top: BorderSide(color: p.line)),
              ),
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  Text(
                    brand.name.toUpperCase(),
                    maxLines: 1,
                    overflow: TextOverflow.ellipsis,
                    style: PearlText.productName(11).copyWith(color: p.ink),
                  ),
                  const SizedBox(height: 4),
                  Text(
                    '${brand.productCount}',
                    style: PearlText.micro.copyWith(fontSize: 8.5, color: p.faint),
                  ),
                ],
              ),
            ),
          ],
        ),
      ),
    );
  }
}

class _BrandSkeleton extends StatelessWidget {
  const _BrandSkeleton();

  @override
  Widget build(BuildContext context) => Padding(
        padding: const EdgeInsets.all(PearlMetrics.pad),
        child: LayoutBuilder(
          builder: (context, constraints) {
            final columns = switch (constraints.maxWidth) {
              >= 900 => 5,
              >= 640 => 4,
              >= 420 => 3,
              _ => 2,
            };
            const gap = 10.0;
            final width = (constraints.maxWidth - gap * (columns - 1)) / columns;
            return Wrap(
              spacing: gap,
              runSpacing: gap,
              children: [
                for (var i = 0; i < columns * 2; i++)
                  SkeletonBlock(height: width / 1.5 + 46, width: width),
              ],
            );
          },
        ),
      );
}

class _LeftColumn extends StatelessWidget {
  const _LeftColumn({required this.state});

  final FunnelState state;

  @override
  Widget build(BuildContext context) {
    final p = context.pearl;
    return SingleChildScrollView(
      padding: const EdgeInsets.fromLTRB(14, 16, 14, 16),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.stretch,
        children: [
          FunnelColumn(
            state: state,
            current: FunnelStep.brand,
            onReopen: (step) => reopenFunnelStep(context, step),
          ),
          const ColumnHeading('Skip ahead'),
          Hairline(
            filled: true,
            padding: const EdgeInsets.all(15),
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Text(
                  '${state.brandTotal}',
                  style: PearlText.display(28).copyWith(color: p.ink),
                ),
                const SizedBox(height: 6),
                Text(
                  state.size == null
                      ? 'products across every brand'
                      : 'products across every brand in size ${state.size}',
                  style: PearlText.body(11).copyWith(color: p.muted),
                ),
              ],
            ),
          ),
        ],
      ),
    );
  }
}
