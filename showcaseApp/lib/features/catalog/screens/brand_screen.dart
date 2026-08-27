import 'package:flutter/material.dart';
import 'package:flutter_bloc/flutter_bloc.dart';

import '../../../shared/domain/constants/data_fetching_status.dart';
import '../../../shared/domain/models/index.dart';
import '../../../shared/logic/funnel_cubit/funnel_cubit.dart';
import '../../../shared/utils/components/theme/pearl_theme.dart';
import '../../../shared/utils/router/funnel_navigation.dart';
import '../../../shared/widgets/chrome/app_top_bar.dart';
import '../../../shared/widgets/chrome/funnel_breadcrumbs.dart';
import '../../../shared/widgets/chrome/showcase_scaffold.dart';
import '../../../shared/widgets/pearl_widgets.dart';
import '../../../shared/widgets/photo.dart';
import '../../../shared/widgets/product_card.dart';
import '../../../l10n/app_localizations.dart';

/// Step 2 — brand, and the most skippable step in the funnel. "Every brand" is
/// the tile that leads the wall rather than a link, because most customers want
/// it.
///
/// The brands are a grid of logos rather than a list of names: on a shop floor
/// a customer recognises the mark long before they read the word.
///
/// It is the *same* grid as the results, and deliberately: same column count
/// off the same Appearance setting, same gaps, same square stage, same caption
/// set the same way underneath it. The wall used to be its own drawing — three
/// across on a rule of its own, rounded plates, centred names — so stepping
/// from it into the results was stepping into a different app. A brand tile is
/// a product tile whose photograph happens to be a logo.
///
/// The count stays on the stage, where a product tile carries "sold out" and
/// "only 2 left". It is not the depth of the brand in the catalogue — it is how
/// many of that brand are in *this* customer's size and on the shelf of the
/// store they are standing in, which is the number they walked up to the panel
/// to ask about. A wall of marks with nothing on them makes every brand look
/// equally worth pressing; the badge is what tells them which one has anything
/// behind it.
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
          prominent: true,
          onTap: () => context.leaveFunnelStep(FunnelStep.size),
        ),
        title: FunnelBreadcrumbs(
          state: state,
          current: FunnelStep.brand,
          onReopen: (step) => reopenFunnelStep(context, step),
        ),
      ),
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
      // "Show every brand" is not down here. It was a filled button pinned
      // under the grid, which asked the screen's question twice in two
      // different shapes — a wall of marks, and a button somewhere else
      // answering the same thing. It is the first tile now, the same as "All"
      // leads the size run.
      //
      // The way out is, though. A customer who walks up to a panel somebody
      // else left on this step can start their own visit without pressing Back
      // through a stranger's answer — the same escape the results screen
      // offers, in the same words and the same place, so it does not read as a
      // different control on a different screen.
      //
      // The full width of the bar, unlike the results screen, where it shares
      // the row with Filter and sort. It has nothing to share with here, and a
      // share of the bar sized for a pair left the label ellipsised to "BAC…" —
      // a house and three letters, which is the one control on the panel that
      // must not need working out.
      bottomBar: PinnedBar(
        child: Row(
          children: [
            Expanded(
              child: PearlButton(
                label: L.of(context).backToHome,
                icon: Icons.home_outlined,
                onTap: () => goHome(context),
              ),
            ),
          ],
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
          // Capitals, and the weight to carry them — the same way the size run
          // asks its question, read from the same distance. Raised here rather
          // than in the string table so the wording stays ordinary sentence
          // case for whoever edits the translations; in Arabic there is no case
          // to raise, and this is simply the heading in the bold weight.
          t.whichBrand.toUpperCase(),
          style: PearlText.displayCaps(26).copyWith(color: p.ink),
        ),
        // No heading over the grid. A line of small caps telling you they are
        // brands, above a wall of brand marks, was labelling the obvious.
        const SizedBox(height: 22),
        _BrandGrid(
          brands: state.brands,
          // The answer already given, for someone who came back a step to
          // change it — the same thing the size run marks on its plates. Null
          // is not "unanswered", it is "every brand", and the first tile wears
          // it.
          selectedId: state.brand?.id,
          onTap: (brand) {
            context.read<FunnelCubit>().chooseBrand(brand);
            context.goToFunnelStep(FunnelStep.results);
          },
          onTapAll: () {
            context.read<FunnelCubit>().skipBrand();
            context.goToFunnelStep(FunnelStep.results);
          },
        ),
      ],
    );
  }
}

class _BrandGrid extends StatelessWidget {
  const _BrandGrid({
    required this.brands,
    required this.selectedId,
    required this.onTap,
    required this.onTapAll,
  });

  final List<BrandOption> brands;
  final int? selectedId;
  final void Function(BrandOption) onTap;
  final VoidCallback onTapAll;

  /// How far apart the rows sit: [ProductGrid]'s own `mainAxisSpacing`, so a
  /// wall of marks and a wall of products breathe at the same rate. Sideways
  /// they are [PearlMetrics.gap] apart, which is the product grid's
  /// `crossAxisSpacing`.
  static const double _run = 24;

  /// The same count the results grid draws at — Appearance's "products per
  /// row". It used to be a width rule of this screen's own, which is how the
  /// wall ended up three across while the results behind it were two: one shop
  /// setting, two densities, and no way for a shop to change the wall.
  static int columnsOf(BuildContext context) => ProductGrid.columnsOf(context);

  /// What one tile gets, given the room the grid has been handed.
  static double widthOf(double available, int columns) =>
      (available - PearlMetrics.gap * (columns - 1)) / columns;

  @override
  Widget build(BuildContext context) {
    final columns = columnsOf(context);
    return LayoutBuilder(
      builder: (context, constraints) {
        final width = widthOf(constraints.maxWidth, columns);
        // A Wrap rather than the results' SliverGrid: a brand name is one or
        // two lines depending on the brand, and a grid has to be told one
        // height for every tile in it before a word is laid out. The Wrap tops
        // its run, so the stages stay in line and only a long name hangs
        // lower — which is the one thing this wall has that a product grid,
        // where every caption is the same three lines, does not.
        return Wrap(
          spacing: PearlMetrics.gap,
          runSpacing: _run,
          children: [
            SizedBox(
              width: width,
              child: _BrandTile.all(
                width: width,
                label: L.of(context).anyBrand,
                // The wall added up. Every tile on this screen is already
                // scoped to the size and the store, so the sum is what
                // pressing this tile actually leads to — the same arithmetic a
                // customer would do reading down the grid, done for them.
                count: brands.fold(0, (sum, b) => sum + b.productCount),
                selected: selectedId == null,
                onTap: onTapAll,
              ),
            ),
            for (final brand in brands)
              SizedBox(
                width: width,
                child: _BrandTile(
                  brand: brand,
                  width: width,
                  selected: brand.id == selectedId,
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
  const _BrandTile({
    required BrandOption this.brand,
    required this.width,
    required this.onTap,
    this.selected = false,
  })  : label = null,
        count = null;

  /// The tile that leads the grid: the same stage and the same caption, with a
  /// mark of its own because "every brand" has no logo to show, and a count of
  /// its own because it stands for all of them at once.
  const _BrandTile.all({
    required this.width,
    required this.onTap,
    required String this.label,
    required int this.count,
    this.selected = false,
  }) : brand = null;

  /// Null on the leading tile — see [_BrandTile.all].
  final BrandOption? brand;
  final String? label;

  /// Set only on the leading tile; every other tile reads its own brand's
  /// figure. Both mean the same thing — how many products this tile leads to,
  /// in the size and the store the funnel is already scoped by.
  final int? count;

  /// The column share this tile has been given. Every proportion below is read
  /// off it, so a dense wall and a two-up wall are the same drawing at two
  /// sizes rather than two drawings.
  final double width;
  final VoidCallback onTap;

  /// The brand this funnel is already filtered by, if the customer stepped back
  /// to this screen rather than arriving at it.
  final bool selected;

  /// The stage is square, exactly as the product grid's is.
  ///
  /// It was landscape once, on the argument that logos are wider than they are
  /// tall. They are, but they are not all wider by the same amount — a jumpman
  /// and a wordmark in the same landscape frame are one mark floating in air
  /// and one filling the width, and the run stopped reading as a set. A square
  /// gives every mark the same room in both directions and lets
  /// [BoxFit.contain] settle the difference.
  static const double _ratio = 1;

  /// The mark sits on a lit plinth rather than on the palette's stage
  /// gradient — and always a light one. This is the one place the wall cannot
  /// copy the product grid: these are black marks on transparency, and the dark
  /// theme's stage would swallow them whole. A photograph brings its own
  /// ground; a logo does not.
  static const LinearGradient _plinth = LinearGradient(
    begin: Alignment.topLeft,
    end: Alignment.bottomRight,
    colors: [Color(0xFFFFFFFF), Color(0xFFFBFBFC), Color(0xFFF1F1F3)],
    stops: [0, .46, 1],
  );

  /// The stage's own hairline, not the palette's.
  ///
  /// [PearlPalette.line] is drawn to sit on the page, and this plate is light
  /// whatever the page is doing — on the dark theme the palette's line around a
  /// white card is a dark ring nobody drew on purpose. It belongs to the plate,
  /// so it is fixed like the plate is.
  static const Color _cardLine = Color(0xFFE3E3E8);

  /// The two inks the plinth carries when there is no logo to show.
  static const Color _mark = Color(0xFF3A3B44);
  static const Color _faintMark = Color(0xFF9A9BA4);

  /// The count's own two inks, fixed like the plate they sit on and for the
  /// same reason as [_cardLine]: the tag chrome a product tile uses is painted
  /// in the palette, and a palette-coloured badge disappears into this
  /// always-light plate the moment the panel is switched over. Same shape as a
  /// product's "only 2 left", drawn once instead of twice.
  static const Color _badgeGround = Color(0xFF23242B);
  static const Color _badgeInk = Color(0xFFFFFFFF);

  /// Where the badge sits on the stage: the product card's own 7pt inset for
  /// the tags it hangs on a photograph.
  static const double _badgeInset = 7;

  /// The gap between the stage and the caption under it — the product card's
  /// `_gapUnderStage`, so a row of brands and a row of products caption at the
  /// same height off the bottom of their stage.
  static const double _captionGap = 9;

  /// How much of the stage the mark is kept away from. Generous on purpose: a
  /// logo run to the edge of its plate reads as a cropped image rather than as
  /// a mark on a card, and these sit in a wall of them.
  double get _inset => width * .20;

  /// The name, set to the product grid's own name scale — a fraction of the
  /// tile, clamped at both ends, so the wall and the results read as one
  /// typeface at one size whatever the shop sets its columns to.
  double get _nameSize => (width * .034).clamp(10.5, 17.0);

  /// The count, on the product caption's scale rather than the flat 8pt a
  /// product tag uses. A stock tag qualifies a tile a customer has already
  /// chosen to look at; this number is the reason to press the tile at all, and
  /// it has to be readable from where the logo is recognised.
  double get _countSize => (width * .026).clamp(8.5, 13.0);

  /// What the badge says: the brand's own figure, or the wall's total on the
  /// leading tile.
  int get _shownCount => count ?? brand!.productCount;

  @override
  Widget build(BuildContext context) {
    final p = context.pearl;
    // The tile owns its Material.
    //
    // [Ink] paints its decoration into the nearest Material ancestor, and the
    // nearest one was the Scaffold's — the sheet under the whole app, top bar
    // included. A Material paints its ink features itself, outside the clip the
    // list applies to its children, so every plinth on this wall was drawn
    // across the top bar as the grid scrolled under it: a stack of white
    // rectangles sliding over the wordmark. A Material of the tile's own puts
    // that ink layer inside the scroll view, where the viewport clips it like
    // anything else, and keeps the press feedback on the card the finger is on.
    return Material(
      type: MaterialType.transparency,
      child: InkWell(
        onTap: onTap,
        child: Column(
          // Left, like a product caption. The stage is fixed by its ratio and
          // the caption takes what it needs, so the tile is as tall as its own
          // contents.
          crossAxisAlignment: CrossAxisAlignment.start,
          mainAxisSize: MainAxisSize.min,
          children: [
            AspectRatio(
              aspectRatio: _ratio,
              child: Ink(
                decoration: BoxDecoration(
                  gradient: _plinth,
                  // Square, like every other stage in the app. Pearl has no
                  // corner radius; the rounded plate this wall used to draw was
                  // the loudest thing that made it look like a different
                  // screen.
                  border: Border.all(
                    // Marked the way a chosen size is: the accent at full
                    // strength, drawn heavier than the hairline every other
                    // stage carries.
                    color: selected ? p.accent : _cardLine,
                    width: selected ? 2 : PearlMetrics.hairline,
                  ),
                ),
                // The mark fills the stage and the count rides over it, the way
                // a product's tags ride its photograph.
                child: Stack(
                  fit: StackFit.expand,
                  children: [
                    switch (brand) {
                      // Every brand: a mark rather than a logo, drawn in the
                      // plinth's own ink so it sits in the wall of black marks
                      // beside it instead of announcing itself as different.
                      null => Center(
                          child: Icon(
                            Icons.sell_outlined,
                            size: width * .30,
                            color: _mark,
                          ),
                        ),
                      final b when b.hasLogo => Photo(
                          url: b.imagePath,
                          width: width,
                          padding: EdgeInsets.all(_inset),
                        ),
                      final b => Center(
                          child: Text(
                            b.monogram,
                            style: PearlText.display(width * .22)
                                .copyWith(color: _faintMark),
                          ),
                        ),
                    },
                    // Directional, so the badge crosses to the other corner in
                    // Arabic along with everything else the page mirrors.
                    //
                    // Nothing is drawn for a count of zero. The server does not
                    // send a brand with nothing behind it, so this is the
                    // leading tile on an empty wall — and a "0" on the one tile
                    // that means "show me everything" is the worst thing this
                    // screen could say.
                    if (_shownCount > 0)
                      PositionedDirectional(
                        top: _badgeInset,
                        end: _badgeInset,
                        child: _CountBadge(
                          count: _shownCount,
                          fontSize: _countSize,
                        ),
                      ),
                  ],
                ),
              ),
            ),
            const SizedBox(height: _captionGap),
            // The name where a product tile puts its own: under the stage, hard
            // to the reading edge, uppercase in the product grid's name style.
            // It was set in the brand's own casing and centred, which was a
            // reasonable thing for a tile that was a plate with a word under
            // it; on a grid that is the results grid, a caption that sets
            // itself differently is the tile announcing it is not one of them.
            Text(
              (label ?? brand!.name).toUpperCase(),
              maxLines: 2,
              overflow: TextOverflow.ellipsis,
              style: PearlText.productName(_nameSize)
                  .copyWith(color: selected ? p.accent : p.ink),
            ),
          ],
        ),
      ),
    );
  }
}

/// The number on the corner of a brand stage.
///
/// A filled lozenge rather than a bare number: these sit on top of logos, and a
/// figure laid straight over a mark reads as part of the artwork. The ground
/// gives it its own plate to stand on and keeps it legible over whatever the
/// brand happens to have uploaded — a dark wordmark, a photograph, a wall of
/// stickers.
class _CountBadge extends StatelessWidget {
  const _CountBadge({required this.count, required this.fontSize});

  final int count;
  final double fontSize;

  @override
  Widget build(BuildContext context) {
    return Container(
      // A single digit in a lozenge padded for three is a sliver; this holds
      // the small counts round rather than letting them shrink to a stripe.
      constraints: BoxConstraints(minWidth: fontSize * 2.1),
      alignment: Alignment.center,
      padding: EdgeInsets.symmetric(
        horizontal: fontSize * .52,
        vertical: fontSize * .28,
      ),
      decoration: BoxDecoration(
        color: _BrandTile._badgeGround,
        borderRadius: BorderRadius.circular(fontSize * 2),
      ),
      child: Text(
        '$count',
        maxLines: 1,
        style: PearlText.label.copyWith(
          fontSize: fontSize,
          fontWeight: FontWeight.w700,
          // Digits, not a label: the tracking Pearl puts on small caps pushes a
          // three-figure count off centre in its own lozenge.
          letterSpacing: 0,
          height: 1,
          color: _BrandTile._badgeInk,
        ),
      ),
    );
  }
}

class _BrandSkeleton extends StatelessWidget {
  const _BrandSkeleton();

  @override
  Widget build(BuildContext context) {
    final columns = _BrandGrid.columnsOf(context);
    return Padding(
      padding: const EdgeInsets.all(PearlMetrics.pad),
      // Rehearses the real grid — same column count, same gaps, same square
      // stage with a caption under it — so the page does not reflow the moment
      // the brands land.
      child: LayoutBuilder(
        builder: (context, constraints) {
          final width = _BrandGrid.widthOf(constraints.maxWidth, columns);
          return Wrap(
            spacing: PearlMetrics.gap,
            runSpacing: _BrandGrid._run,
            children: [
              for (var i = 0; i < columns * 3; i++)
                SizedBox(
                  width: width,
                  child: Column(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    mainAxisSize: MainAxisSize.min,
                    children: [
                      SkeletonBlock(
                        height: width / _BrandTile._ratio,
                        width: width,
                        radius: 0,
                      ),
                      const SizedBox(height: _BrandTile._captionGap),
                      // Short, the way a brand name sits: a bar the full width
                      // of the stage would rehearse a caption nobody is about
                      // to see.
                      SkeletonBlock(
                        height: (width * .034).clamp(10.5, 17.0),
                        width: width * .55,
                        radius: 3,
                      ),
                    ],
                  ),
                ),
            ],
          );
        },
      ),
    );
  }
}
