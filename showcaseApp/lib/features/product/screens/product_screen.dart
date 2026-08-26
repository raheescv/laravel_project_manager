import 'package:flutter/material.dart';
import 'package:flutter_bloc/flutter_bloc.dart';
import 'package:go_router/go_router.dart';

import '../../../shared/domain/constants/data_fetching_status.dart';
import '../../../shared/domain/helpers/formatters.dart';
import '../../../shared/domain/helpers/responsive.dart';
import '../../../shared/domain/models/index.dart';
import '../../../shared/logic/branch_cubit/branch_cubit.dart';
import '../../../shared/utils/components/theme/pearl_theme.dart';
import '../../../shared/utils/router/routes.dart';
import '../../../shared/widgets/chrome/showcase_scaffold.dart';
import '../../../shared/widgets/pearl_widgets.dart';
import '../../../shared/widgets/photo.dart';
import '../../../shared/widgets/product_card.dart';
import '../logic/product_cubit/product_cubit.dart';
import 'reserve_sheet.dart';

/// The product page.
///
/// Tablet splits it: a full-height gallery on the left, a standing info panel on
/// the right whose call to action is pinned and never scrolls away, and the
/// related rail spanning the full width beneath both. Phone stacks the same
/// pieces in the same order.
class ProductScreen extends StatelessWidget {
  const ProductScreen({super.key, required this.productId});

  final int productId;

  @override
  Widget build(BuildContext context) => BlocProvider<ProductCubit>(
        create: (_) => ProductCubit(productId: productId),
        child: const _ProductView(),
      );
}

class _ProductView extends StatelessWidget {
  const _ProductView();

  @override
  Widget build(BuildContext context) {
    final cubit = context.watch<ProductCubit>();
    final state = cubit.state;
    final p = context.pearl;

    if (state.status.isFailed) {
      return ShowcaseScaffold(
        showRail: false,
        topBar: const _ProductTopBar(title: 'Product'),
        body: MessageState(
          title: 'This product did not load',
          detail: state.errorMessage,
          actionLabel: 'Try again',
          onAction: cubit.load,
        ),
      );
    }

    final product = state.product;
    if (product == null) {
      return ShowcaseScaffold(
        showRail: false,
        topBar: const _ProductTopBar(title: 'Loading'),
        body: Container(color: p.bg),
      );
    }

    return ShowcaseScaffold(
      showRail: false,
      topBar: _ProductTopBar(title: product.name),
      body: context.isTablet
          ? _TabletBody(state: state, product: product)
          : _PhoneBody(state: state, product: product),
      bottomBar: context.isTablet ? null : _Actions(product: product, state: state),
    );
  }
}

class _ProductTopBar extends StatelessWidget {
  const _ProductTopBar({required this.title});

  final String title;

  @override
  Widget build(BuildContext context) {
    final p = context.pearl;
    return Container(
      padding: const EdgeInsets.symmetric(horizontal: 14, vertical: 12),
      decoration: BoxDecoration(border: Border(bottom: BorderSide(color: p.line))),
      child: Row(
        children: [
          IconSquare(Icons.arrow_back, size: 38, onTap: () => context.pop()),
          const SizedBox(width: 14),
          Expanded(
            child: Text(
              title.toUpperCase(),
              maxLines: 1,
              overflow: TextOverflow.ellipsis,
              style: PearlText.section.copyWith(color: p.ink),
            ),
          ),
        ],
      ),
    );
  }
}

// ---------------------------------------------------------------- tablet

class _TabletBody extends StatelessWidget {
  const _TabletBody({required this.state, required this.product});

  final ProductState state;
  final Product product;

  @override
  Widget build(BuildContext context) {
    final p = context.pearl;
    return Column(
      children: [
        Expanded(
          child: Row(
            crossAxisAlignment: CrossAxisAlignment.stretch,
            children: [
              Expanded(child: _Gallery(state: state, product: product)),
              Container(
                width: PearlMetrics.infoPanel,
                decoration: BoxDecoration(
                  border: Border(left: BorderSide(color: p.line)),
                ),
                child: Column(
                  children: [
                    // The panel scrolls; the actions below it do not.
                    Expanded(
                      child: SingleChildScrollView(
                        padding: const EdgeInsets.fromLTRB(20, 20, 20, 14),
                        child: _Info(state: state, product: product),
                      ),
                    ),
                    Padding(
                      padding: const EdgeInsets.fromLTRB(20, 0, 20, 18),
                      child: _Actions(product: product, state: state, bare: true),
                    ),
                  ],
                ),
              ),
            ],
          ),
        ),
        _RelatedRail(state: state),
      ],
    );
  }
}

// ----------------------------------------------------------------- phone

class _PhoneBody extends StatelessWidget {
  const _PhoneBody({required this.state, required this.product});

  final ProductState state;
  final Product product;

  @override
  Widget build(BuildContext context) => ListView(
        padding: EdgeInsets.zero,
        children: [
          SizedBox(height: 300, child: _Gallery(state: state, product: product)),
          Padding(
            padding: const EdgeInsets.fromLTRB(
                PearlMetrics.pad, 18, PearlMetrics.pad, 10),
            child: _Info(state: state, product: product),
          ),
          _RelatedRail(state: state, height: 248),
        ],
      );
}

// --------------------------------------------------------------- gallery

class _Gallery extends StatelessWidget {
  const _Gallery({required this.state, required this.product});

  final ProductState state;
  final Product product;

  @override
  Widget build(BuildContext context) {
    final p = context.pearl;
    final urls = product.galleryUrls;
    final index = state.galleryIndex.clamp(0, urls.isEmpty ? 0 : urls.length - 1);
    final tablet = context.isTablet;

    return Stack(
      children: [
        Positioned.fill(
          child: Stage(
            border: false,
            child: LayoutBuilder(
              builder: (context, constraints) => Photo(
                url: urls.isEmpty ? '' : urls[index],
                width: constraints.maxWidth,
                padding: EdgeInsets.all(tablet ? 40 : 26),
              ),
            ),
          ),
        ),
        if (urls.length > 1)
          Positioned(
            left: 0,
            top: 0,
            bottom: 0,
            child: _ThumbStrip(urls: urls, index: index),
          ),
        if (product.hasSpin)
          Positioned(
            bottom: 18,
            left: 0,
            right: 0,
            child: Center(
              child: _SpinEntry(
                frames: product.images360.length,
                onTap: () => context.push(Routes.spinFor(product.id), extra: product),
              ),
            ),
          )
        else if (urls.length > 1)
          Positioned(
            bottom: 16,
            left: 0,
            right: 0,
            child: Center(
              child: Text(
                '${index + 1} / ${urls.length}',
                style: PearlText.micro.copyWith(color: p.muted),
              ),
            ),
          ),
      ],
    );
  }
}

class _ThumbStrip extends StatelessWidget {
  const _ThumbStrip({required this.urls, required this.index});

  final List<String> urls;
  final int index;

  @override
  Widget build(BuildContext context) {
    final p = context.pearl;
    return SizedBox(
      width: 78,
      child: ListView.separated(
        padding: const EdgeInsets.symmetric(vertical: 16, horizontal: 11),
        itemCount: urls.length,
        separatorBuilder: (_, __) => const SizedBox(height: 9),
        itemBuilder: (context, i) => InkWell(
          onTap: () => context.read<ProductCubit>().showImage(i),
          child: Container(
            height: 56,
            decoration: BoxDecoration(
              color: p.bg,
              border: Border.all(
                color: i == index ? p.accent : p.line,
                width: i == index ? 1.5 : 1,
              ),
            ),
            child: Photo(url: urls[i], width: 56, padding: const EdgeInsets.all(6)),
          ),
        ),
      ),
    );
  }
}

class _SpinEntry extends StatelessWidget {
  const _SpinEntry({required this.frames, required this.onTap});

  final int frames;
  final VoidCallback onTap;

  @override
  Widget build(BuildContext context) {
    final p = context.pearl;
    return InkWell(
      onTap: onTap,
      child: Container(
        padding: const EdgeInsets.symmetric(horizontal: 18, vertical: 12),
        decoration: BoxDecoration(color: p.accent),
        child: Row(
          mainAxisSize: MainAxisSize.min,
          children: [
            Icon(Icons.threesixty_outlined, size: 16, color: p.accentInk),
            const SizedBox(width: 10),
            Text(
              'Spin 360° · $frames frames'.toUpperCase(),
              style: PearlText.button.copyWith(color: p.accentInk),
            ),
          ],
        ),
      ),
    );
  }
}

// ------------------------------------------------------------ info panel

class _Info extends StatelessWidget {
  const _Info({required this.state, required this.product});

  final ProductState state;
  final Product product;

  @override
  Widget build(BuildContext context) {
    final p = context.pearl;
    final branchId = context.watch<BranchCubit>().selectedId;
    final sizes = _sizeRun(product);

    return Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        Row(
          children: [
            Expanded(
              child: Text(
                product.brandName.toUpperCase(),
                style: PearlText.micro.copyWith(color: p.ink),
              ),
            ),
            StockPill(
              label: switch (product.availabilityStatus) {
                'in_stock' => 'In stock · ${product.totalStock}',
                'available_in_other_branches' => 'Other stores',
                _ => 'Sold out',
              },
              positive: product.availabilityStatus == 'in_stock',
            ),
          ],
        ),
        const SizedBox(height: 12),
        Text(product.name, style: PearlText.display(24).copyWith(color: p.ink)),
        if (product.nameArabic.isNotEmpty) ...[
          const SizedBox(height: 8),
          Text(
            product.nameArabic,
            textDirection: TextDirection.rtl,
            style: PearlText.body(14).copyWith(color: p.muted),
          ),
        ],
        const SizedBox(height: 10),
        Text(
          [
            product.code,
            if (product.color.isNotEmpty) product.color,
            if (product.unitName.isNotEmpty) product.unitName,
          ].join(' · ').toUpperCase(),
          style: PearlText.micro.copyWith(fontSize: 8.5, color: p.faint),
        ),
        const SizedBox(height: 16),
        Text(money(product.mrp), style: PearlText.price(26).copyWith(color: p.ink)),
        if (sizes.isNotEmpty) ...[
          _PanelHeading('Size run', trailing: state.selectedSize),
          _SizeRun(
            sizes: sizes,
            selected: state.selectedSize,
            branchId: branchId,
          ),
        ],
        _PanelHeading('Availability', trailing: '${product.inventories.length} stores'),
        ..._branchRows(context, product, branchId),
        if (product.description.isNotEmpty) ...[
          const _PanelHeading('Details'),
          Text(
            product.description,
            style: PearlText.body(12).copyWith(color: p.muted),
          ),
        ],
        const SizedBox(height: 8),
      ],
    );
  }

  /// `related_sizes` is the richer source — it carries stock per branch. Fall
  /// back to `available_sizes`, which is only a list of labels.
  static List<RelatedSize> _sizeRun(Product product) {
    if (product.relatedSizes.isNotEmpty) {
      final rows = [...product.relatedSizes];
      rows.sort((a, b) => _sizeOrder(a.size).compareTo(_sizeOrder(b.size)));
      return rows;
    }
    return product.availableSizes
        .map((s) => RelatedSize(
              size: s,
              totalStock: 0,
              isOutOfStock: false,
              branches: const [],
            ))
        .toList(growable: false);
  }

  /// Sizes sort numerically where they can ("9.5" before "10"), alphabetically
  /// where they cannot ("10C"). A plain string sort puts 10 before 9.
  static double _sizeOrder(String size) =>
      double.tryParse(size.replaceAll(RegExp('[^0-9.]'), '')) ?? 0;

  static List<Widget> _branchRows(BuildContext context, Product product, int? branchId) {
    final rows = product.branchesByStock(branchId);
    if (rows.isEmpty) {
      return [
        Text(
          'No stock recorded for this product.',
          style: PearlText.body(11.5).copyWith(color: context.pearl.faint),
        ),
      ];
    }
    return [
      for (final line in rows.take(5))
        _BranchRow(line: line, isActive: line.branchId == branchId),
    ];
  }
}

class _PanelHeading extends StatelessWidget {
  const _PanelHeading(this.text, {this.trailing});

  final String text;
  final String? trailing;

  @override
  Widget build(BuildContext context) {
    final p = context.pearl;
    return Padding(
      padding: const EdgeInsets.only(top: 22, bottom: 11),
      child: Row(
        children: [
          Expanded(
            child: Text(
              text.toUpperCase(),
              style: PearlText.micro.copyWith(fontSize: 8.5, color: p.faint),
            ),
          ),
          if (trailing != null)
            Text(
              trailing!.toUpperCase(),
              style: PearlText.micro.copyWith(fontSize: 8.5, color: p.ink),
            ),
        ],
      ),
    );
  }
}

class _SizeRun extends StatelessWidget {
  const _SizeRun({required this.sizes, required this.selected, required this.branchId});

  final List<RelatedSize> sizes;
  final String? selected;
  final int? branchId;

  @override
  Widget build(BuildContext context) {
    return LayoutBuilder(
      builder: (context, constraints) {
        const gap = 8.0;
        const columns = 4;
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
                  height: 46,
                  selected: size.size == selected,
                  available: !size.isOutOfStock,
                  onTap: () => context.read<ProductCubit>().selectSize(size.size),
                ),
              ),
          ],
        );
      },
    );
  }
}

class _BranchRow extends StatelessWidget {
  const _BranchRow({required this.line, required this.isActive});

  final InventoryLine line;
  final bool isActive;

  @override
  Widget build(BuildContext context) {
    final p = context.pearl;
    return Opacity(
      opacity: line.hasStock ? 1 : .45,
      child: Container(
        margin: const EdgeInsets.only(bottom: 7),
        padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 11),
        decoration: BoxDecoration(
          border: Border.all(color: isActive ? p.ink : p.line),
        ),
        child: Row(
          children: [
            Icon(Icons.place_outlined, size: 14, color: p.faint),
            const SizedBox(width: 9),
            Expanded(
              child: Text(
                line.branchName,
                maxLines: 1,
                overflow: TextOverflow.ellipsis,
                style: PearlText.label.copyWith(color: p.ink, fontSize: 11),
              ),
            ),
            Text(
              line.hasStock ? '${line.available}' : 'none'.toUpperCase(),
              style: PearlText.micro.copyWith(fontSize: 8.5, color: p.faint),
            ),
          ],
        ),
      ),
    );
  }
}

// --------------------------------------------------------------- actions

class _Actions extends StatelessWidget {
  const _Actions({required this.product, required this.state, this.bare = false});

  final Product product;
  final ProductState state;
  final bool bare;

  @override
  Widget build(BuildContext context) {
    final row = Row(
      children: [
        Expanded(
          child: PearlButton(
            label: 'Reserve in store',
            icon: Icons.store_outlined,
            onTap: () => showReserveSheet(
              context,
              product: product,
              size: state.selectedSize,
            ),
          ),
        ),
      ],
    );
    return bare ? row : PinnedBar(child: row);
  }
}

// ----------------------------------------------------------------- rail

class _RelatedRail extends StatelessWidget {
  const _RelatedRail({required this.state, this.height = 248});

  final ProductState state;
  final double height;

  @override
  Widget build(BuildContext context) {
    final p = context.pearl;
    if (state.related.isEmpty) return const SizedBox.shrink();
    const cardWidth = 128.0;
    return Container(
      height: height,
      decoration: BoxDecoration(border: Border(top: BorderSide(color: p.line))),
      padding: const EdgeInsets.fromLTRB(PearlMetrics.pad, 14, 0, 10),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          const Padding(
            padding: EdgeInsets.only(right: PearlMetrics.pad),
            child: SectionHeading('You may also like', meta: 'same category'),
          ),
          Expanded(
            child: ListView.separated(
              scrollDirection: Axis.horizontal,
              padding: const EdgeInsets.only(right: PearlMetrics.pad),
              itemCount: state.related.length,
              separatorBuilder: (_, __) => const SizedBox(width: PearlMetrics.gap),
              itemBuilder: (context, i) => SizedBox(
                width: cardWidth,
                child: ProductCard(
                  product: state.related[i],
                  width: cardWidth,
                  compact: true,
                  onTap: () => context.push(Routes.productById(state.related[i].id)),
                ),
              ),
            ),
          ),
        ],
      ),
    );
  }
}
