import 'package:flutter/material.dart';

import '../domain/helpers/formatters.dart';
import '../domain/models/index.dart';
import '../utils/components/theme/pearl_theme.dart';
import 'photo.dart';

/// A product tile: stage, brand, name, price. Used at three sizes — grid card,
/// preview card, related rail — so the type scale is a parameter rather than
/// three near-identical widgets.
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

  @override
  Widget build(BuildContext context) {
    final p = context.pearl;
    final double nameSize = compact ? 9 : 10.5;
    return InkWell(
      onTap: onTap,
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        mainAxisSize: MainAxisSize.min,
        children: [
          Stage(
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
                    child: _Tag(text: 'Sold out', palette: p),
                  )
                else if (product.totalStock > 0 && product.totalStock <= 2)
                  Positioned(
                    left: 7,
                    top: 7,
                    child: _Tag(text: 'Only ${product.totalStock}', palette: p),
                  ),
              ],
            ),
          ),
          const SizedBox(height: 9),
          if (product.brandName.isNotEmpty)
            Text(
              product.brandName.toUpperCase(),
              maxLines: 1,
              overflow: TextOverflow.ellipsis,
              style: PearlText.brand.copyWith(color: p.faint),
            ),
          const SizedBox(height: 4),
          Text(
            product.name.toUpperCase(),
            maxLines: compact ? 1 : 2,
            overflow: TextOverflow.ellipsis,
            style: PearlText.productName(nameSize).copyWith(color: p.ink),
          ),
          const SizedBox(height: 5),
          Text(
            money(product.mrp),
            maxLines: 1,
            style: PearlText.price(compact ? 11 : 12.5).copyWith(color: p.ink),
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
                  // Stage + brand + two name lines + price.
                  childAspectRatio: cardWidth / (cardWidth + 78),
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
