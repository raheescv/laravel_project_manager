import 'package:flutter/material.dart';

import '../domain/helpers/formatters.dart';
import '../domain/models/index.dart';
import '../utils/components/theme/pearl_theme.dart';
import 'photo.dart';
import '../../l10n/app_localizations.dart';

/// The gaps down the caption block, named because [ProductCard.captionHeight]
/// has to add up the same column that [ProductCard.build] lays out.
const double _gapUnderStage = 9;
const double _gapUnderBrand = 4;
const double _gapUnderName = 5;

double _nameSize(bool compact) => compact ? 9 : 10.5;

double _priceSize(bool compact) => compact ? 11 : 12.5;

int _nameLines(bool compact) => compact ? 1 : 2;

/// A product tile: stage, brand, name, price. Used at three sizes — grid card,
/// preview card, related rail — so the type scale is a parameter rather than
/// three near-identical widgets.
///
/// Give it a bounded height, sized from [captionHeight]: the stage is flexible
/// and a flexible child cannot live in a column with no height to divide.
class ProductCard extends StatelessWidget {
  const ProductCard({
    super.key,
    required this.product,
    required this.width,
    this.aspectRatio = 1,
    this.compact = false,
    this.onTap,
  });

  final Product product;
  final double width;
  final double aspectRatio;

  /// Smaller type for the related rail and the live preview column.
  final bool compact;
  final VoidCallback? onTap;

  /// How tall everything under the stage is: the brand line, the name lines,
  /// the price, and the gaps between them.
  ///
  /// Measured rather than assumed. A grid hands its tiles a height before the
  /// words inside them are laid out, so something has to ask — and the answer
  /// moves: Arabic sits taller in its line box than the Latin faces do, the
  /// typeface is a setting, and the OS text size multiplies whatever comes out.
  /// The constant this replaced was measured against Jost at text size 1, and
  /// every other combination overflowed the tile.
  static double captionHeight(BuildContext context, {bool compact = false}) {
    final scaler = MediaQuery.textScalerOf(context);
    // Measured the way a Text is drawn, not the way the style reads: a Text
    // merges the ambient default before it paints, and the leading it inherits
    // from the theme is most of the line box these styles do not set one for.
    final base = DefaultTextStyle.of(context).style;
    double lines(TextStyle style, int count) {
      final painter = TextPainter(
        text: TextSpan(
          text: List.filled(count, 'X').join('\n'),
          style: base.merge(style),
        ),
        textDirection: TextDirection.ltr,
        maxLines: count,
        textScaler: scaler,
      )..layout();
      // Whole pixels, so the tile is never a rounding error short of its words.
      return painter.height.ceilToDouble();
    }

    return _gapUnderStage +
        lines(PearlText.brand, 1) +
        _gapUnderBrand +
        lines(PearlText.productName(_nameSize(compact)), _nameLines(compact)) +
        _gapUnderName +
        lines(PearlText.price(_priceSize(compact)), 1);
  }

  @override
  Widget build(BuildContext context) {
    final p = context.pearl;
    return InkWell(
      onTap: onTap,
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        mainAxisSize: MainAxisSize.min,
        children: [
          // The stage gives up its room when the words want more than the box
          // has. Whoever sized the box did it before the fonts finished
          // downloading, and a photo a few pixels shorter than square is worth
          // rather less than the price being painted over warning stripes.
          Flexible(
            child: Stage(
              aspectRatio: aspectRatio,
              child: Stack(
                fit: StackFit.expand,
                children: [
                  Photo(url: product.thumbnail, width: width),
                  if (product.hasSpin)
                    const Positioned(right: 7, bottom: 7, child: _SpinBadge()),
                  if (product.isOutOfStock)
                    Positioned(
                      left: 7,
                      top: 7,
                      child: _Tag(text: L.of(context).soldOut, palette: p),
                    )
                  else if (product.totalStock > 0 && product.totalStock <= 2)
                    Positioned(
                      left: 7,
                      top: 7,
                      child: _Tag(
                          text: L.of(context).onlyLeft(product.totalStock.toInt()),
                          palette: p),
                    ),
                ],
              ),
            ),
          ),
          const SizedBox(height: _gapUnderStage),
          if (product.brandName.isNotEmpty)
            Text(
              product.brandName.toUpperCase(),
              maxLines: 1,
              overflow: TextOverflow.ellipsis,
              style: PearlText.brand.copyWith(color: p.faint),
            ),
          const SizedBox(height: _gapUnderBrand),
          Text(
            product.name.toUpperCase(),
            maxLines: _nameLines(compact),
            overflow: TextOverflow.ellipsis,
            style: PearlText.productName(_nameSize(compact)).copyWith(color: p.ink),
          ),
          const SizedBox(height: _gapUnderName),
          Text(
            money(product.mrp),
            maxLines: 1,
            style: PearlText.price(_priceSize(compact)).copyWith(color: p.ink),
          ),
        ],
      ),
    );
  }
}

class _SpinBadge extends StatelessWidget {
  const _SpinBadge();

  @override
  Widget build(BuildContext context) {
    final p = context.pearl;
    return Container(
      padding: const EdgeInsets.symmetric(horizontal: 7, vertical: 4),
      decoration: BoxDecoration(color: p.bg, border: Border.all(color: p.line)),
      child: Row(
        mainAxisSize: MainAxisSize.min,
        children: [
          Icon(Icons.threesixty_outlined, size: 11, color: p.ink),
          const SizedBox(width: 4),
          Text('360', style: PearlText.micro.copyWith(fontSize: 8, color: p.ink)),
        ],
      ),
    );
  }
}

class _Tag extends StatelessWidget {
  const _Tag({required this.text, required this.palette});

  final String text;
  final PearlPalette palette;

  // Filled, not outlined. This badge sits on the product photo rather than on
  // the page, and catalogue photos are shot on white — an outlined badge that
  // borrowed the page's ink colour vanished against them in the dark theme.
  @override
  Widget build(BuildContext context) => Container(
        padding: const EdgeInsets.symmetric(horizontal: 7, vertical: 4),
        decoration: BoxDecoration(
          color: palette.bg,
          border: Border.all(color: palette.line),
        ),
        child: Text(
          text.toUpperCase(),
          style: PearlText.micro.copyWith(fontSize: 8, letterSpacing: 1.4, color: palette.ink),
        ),
      );
}

/// The grid the list and the browse screen both use. Column count comes from
/// the painted width, so the same widget is 2-up on a phone and 4-up on a
/// tablet without a device check.
class ProductGrid extends StatelessWidget {
  const ProductGrid({
    super.key,
    required this.products,
    required this.onTap,
    this.controller,
    this.padding = EdgeInsets.zero,
    this.footer,
    this.header,
  });

  final List<Product> products;
  final void Function(Product) onTap;
  final ScrollController? controller;
  final EdgeInsets padding;
  final Widget? footer;
  final Widget? header;

  static int columnsFor(double width) {
    if (width >= 1000) return 4;
    if (width >= 700) return 3;
    if (width >= 420) return 3;
    return 2;
  }

  @override
  Widget build(BuildContext context) {
    return LayoutBuilder(
      builder: (context, constraints) {
        final columns = columnsFor(constraints.maxWidth);
        const gap = PearlMetrics.gap;
        final cardWidth =
            (constraints.maxWidth - padding.horizontal - gap * (columns - 1)) / columns;
        final caption = ProductCard.captionHeight(context);
        return CustomScrollView(
          controller: controller,
          slivers: [
            if (header != null) SliverToBoxAdapter(child: header),
            SliverPadding(
              padding: padding,
              sliver: SliverGrid(
                gridDelegate: SliverGridDelegateWithFixedCrossAxisCount(
                  crossAxisCount: columns,
                  crossAxisSpacing: gap,
                  mainAxisSpacing: 24,
                  // Stage, then however tall this locale's words turn out.
                  childAspectRatio: cardWidth / (cardWidth + caption),
                ),
                delegate: SliverChildBuilderDelegate(
                  (context, i) => ProductCard(
                    product: products[i],
                    width: cardWidth,
                    onTap: () => onTap(products[i]),
                  ),
                  childCount: products.length,
                ),
              ),
            ),
            if (footer != null) SliverToBoxAdapter(child: footer),
          ],
        );
      },
    );
  }
}
