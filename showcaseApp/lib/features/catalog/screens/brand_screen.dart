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
import '../../../l10n/app_localizations.dart';

/// Step 2 — brand, and the most skippable step in the funnel. "Every brand" is
/// a full-width button rather than a link, because most customers want it.
///
/// The brands are a grid of logos rather than a list of names: on a shop floor
/// a customer recognises the mark long before they read the word.
///
/// The mark is the tile. It gets a landscape plinth with real room around it —
/// logos are wider than they are tall, and a mark squeezed into a thumbnail is
/// a mark nobody recognises at arm's length. The count rides the corner in the
/// accent, the one loud element, so the depth of a brand in the chosen size
/// reads before its name does.
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
            title: L.of(context).brandsDidNotLoad,
            detail: state.errorMessage,
            actionLabel: L.of(context).tryAgain,
            onAction: funnel.loadBrands,
          ),
        DataFetchStatus.waiting when state.brands.isEmpty => const _BrandSkeleton(),
        _ => _BrandBody(state: state),
      },
      bottomBar: PinnedBar(
        child: PearlButton(
          label: state.size == null
              ? L.of(context).showEveryBrand
              : L.of(context).showAllInSize(state.brandTotal, state.size!),
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
    final t = L.of(context);
    if (state.brands.isEmpty) {
      final inSize = state.size == null ? '' : t.inSizeSuffix(state.size!);
      return MessageState(
        title: state.size == null ? t.noBrandsHere : t.noBrandsInSize,
        detail: state.inStockOnly
            ? t.noBrandsInStock(inSize)
            : t.noBrandsAtAll(inSize),
        actionLabel: state.size == null ? null : t.chooseAnotherSize,
        onAction: state.size == null ? null : () => reopenFunnelStep(context, FunnelStep.size),
      );
    }

    return ListView(
      padding: const EdgeInsets.fromLTRB(
          PearlMetrics.pad, 18, PearlMetrics.pad, 30),
      children: [
        Text(
          t.whichBrand,
          style: PearlText.display(context.isTablet ? 30 : 26).copyWith(color: p.ink),
        ),
        const SizedBox(height: 18),
        SectionHeading(
          t.available,
          // Names the unit for the number on every tile, so the badge does not
          // have to carry a label of its own.
          meta: state.size == null
              ? t.stylesPerBrand
              : t.stylesPerBrandInSize(state.size!),
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

  /// Wide enough for a logo to be recognised across a counter. The column
  /// count gives, not the tile — same rule as the size run, one notch up,
  /// because a mark needs more room than four characters do.
  static const double _target = 252;
  static const double _gap = 14;

  @override
  Widget build(BuildContext context) {
    return LayoutBuilder(
      builder: (context, constraints) {
        final columns =
            ((constraints.maxWidth + _gap) / (_target + _gap)).round().clamp(2, 6);
        final width = (constraints.maxWidth - _gap * (columns - 1)) / columns;
        return Wrap(
          spacing: _gap,
          runSpacing: _gap,
          children: [
            for (final brand in brands)
              SizedBox(
                width: width,
                child: _BrandTile(
                  brand: brand,
                  width: width,
                  onTap: () => onTap(brand),
                ),
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

  /// The plate's proportions. Logos are wider than they are tall far more often
  /// than the reverse, so the plate is landscape and the mark is given the
  /// width rather than being centred in a square of empty space.
  static const double _plateRatio = 1.45;

  /// The mark sits on a lit plinth rather than a flat cut-out — and always a
  /// light one. These are black marks on transparency: on the dark theme any
  /// theme-coloured ground would swallow them.
  static const LinearGradient _plinth = LinearGradient(
    begin: Alignment.topLeft,
    end: Alignment.bottomRight,
    colors: [Color(0xFFFFFFFF), Color(0xFFFBFBFC), Color(0xFFF1F1F3)],
    stops: [0, .46, 1],
  );

  @override
  Widget build(BuildContext context) {
    final p = context.pearl;
    return InkWell(
      onTap: onTap,
      child: Container(
        decoration: BoxDecoration(color: p.surface, border: Border.all(color: p.line)),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.stretch,
          children: [
            Stack(
              children: [
                AspectRatio(
                  aspectRatio: _plateRatio,
                  child: DecoratedBox(
                    decoration: const BoxDecoration(gradient: _plinth),
                    child: brand.hasLogo
                        ? Photo(
                            url: brand.imagePath,
                            width: width,
                            padding: EdgeInsets.symmetric(
                              horizontal: width * .12,
                              vertical: width * .10,
                            ),
                          )
                        : Center(
                            child: Text(
                              brand.monogram,
                              style: PearlText.display(width * .18)
                                  .copyWith(color: const Color(0xFF9A9BA4)),
                            ),
                          ),
                  ),
                ),
                // The count is the one loud thing on the tile: a block in the
                // accent, so the depth of a brand reads before its name does.
                Positioned(
                  top: 0,
                  right: 0,
                  child: Container(
                    constraints: const BoxConstraints(minWidth: 34),
                    height: 28,
                    alignment: Alignment.center,
                    padding: const EdgeInsets.symmetric(horizontal: 9),
                    color: p.accent,
                    child: Text(
                      '${brand.productCount}',
                      style: PearlText.label.copyWith(fontSize: 12, color: p.accentInk),
                    ),
                  ),
                ),
              ],
            ),
            Container(
              padding: const EdgeInsets.fromLTRB(13, 12, 13, 13),
              decoration: BoxDecoration(
                border: Border(top: BorderSide(color: p.line)),
              ),
              // The name, and only the name. The count is the corner block —
              // spelling "styles" out here too left the word sitting next to
              // nothing, since its number is up in the badge.
              child: Text(
                brand.name.toUpperCase(),
                maxLines: 1,
                overflow: TextOverflow.ellipsis,
                style: PearlText.productName(11).copyWith(color: p.ink),
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
        // Rehearses the real grid — same column maths, same tile height — so
        // the page does not reflow the moment the brands land.
        child: LayoutBuilder(
          builder: (context, constraints) {
            final columns = ((constraints.maxWidth + _BrandGrid._gap) /
                    (_BrandGrid._target + _BrandGrid._gap))
                .round()
                .clamp(2, 6);
            final width =
                (constraints.maxWidth - _BrandGrid._gap * (columns - 1)) / columns;
            return Wrap(
              spacing: _BrandGrid._gap,
              runSpacing: _BrandGrid._gap,
              children: [
                for (var i = 0; i < columns * 2; i++)
                  SkeletonBlock(
                    height: width / _BrandTile._plateRatio + 47,
                    width: width,
                  ),
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
          ColumnHeading(L.of(context).skipAhead),
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
