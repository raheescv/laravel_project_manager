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
import '../../catalog/logic/funnel_cubit/funnel_cubit.dart';
import '../logic/product_cubit/product_cubit.dart';
import 'reserve_sheet.dart';
import '../../../l10n/app_localizations.dart';

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
        create: (_) => ProductCubit(
          productId: productId,
          inStockOnly: context.read<FunnelCubit>().state.inStockOnly,
        ),
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
        topBar: _ProductTopBar(title: L.of(context).product),
        body: MessageState(
          title: L.of(context).productDidNotLoad,
          detail: state.errorMessage,
          actionLabel: L.of(context).tryAgain,
          onAction: cubit.load,
        ),
      );
    }

    final product = state.product;
    if (product == null) {
      return ShowcaseScaffold(
        showRail: false,
        topBar: _ProductTopBar(title: L.of(context).loading),
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
          _RelatedRail(state: state),
        ],
      );
}

// --------------------------------------------------------------- gallery

class _Gallery extends StatefulWidget {
  const _Gallery({required this.state, required this.product});

  final ProductState state;
  final Product product;

  @override
  State<_Gallery> createState() => _GalleryState();
}

class _GalleryState extends State<_Gallery> {
  /// The width the shots were warmed at, so a rotation re-warms and a rebuild
  /// at the same size does not.
  double? _warmedAt;

  /// The gallery index whose zoom-resolution copy is warm.
  int? _warmedZoomFor;

  /// Fetch everything this page can lead to, the moment the stage knows how
  /// wide it is, all at once.
  ///
  /// Three sets, because the image cache is keyed on the decoded size and each
  /// destination decodes differently:
  ///
  /// * the gallery shots at stage width — the gallery paints one at a time, so
  ///   otherwise the second photo only started downloading when the customer
  ///   tapped its thumbnail;
  /// * the 360° frames at screen width — two dozen of them, and the spin is
  ///   not interactive until the last one lands;
  /// * the shot on screen at zoom width, so tapping it opens instantly.
  ///
  /// The zoom copies are deliberately not warmed for every shot: they decode at
  /// twice the screen, and five of those would evict everything else in the
  /// cache — including each other. The one being looked at is the one that is
  /// about to be tapped.
  void _warm(double width) {
    if (width <= 0 || _warmedAt == width) return;
    _warmedAt = width;
    WidgetsBinding.instance.addPostFrameCallback((_) {
      if (!mounted) return;
      final product = widget.product;
      final screen = MediaQuery.sizeOf(context).width;
      precachePhotos(context, product.galleryUrls, width);
      precachePhotos(
        context,
        product.images360.map((frame) => frame.url),
        screen,
      );
    });
  }

  /// Warm the zoom-resolution copy of whichever shot is showing.
  void _warmZoom(int index, List<String> urls) {
    if (urls.isEmpty || _warmedZoomFor == index) return;
    _warmedZoomFor = index;
    final width = zoomDecodeWidth(context);
    WidgetsBinding.instance.addPostFrameCallback((_) {
      if (mounted) precachePhotos(context, [urls[index]], width);
    });
  }

  @override
  Widget build(BuildContext context) {
    final state = widget.state;
    final product = widget.product;
    final p = context.pearl;
    final urls = product.galleryUrls;
    final index = state.galleryIndex.clamp(0, urls.isEmpty ? 0 : urls.length - 1);
    final tablet = context.isTablet;

    return Stack(
      children: [
        Positioned.fill(
          child: GestureDetector(
            // The photo is the decision on a shoe, and here it shares the
            // screen with a price and a size run. Tapping gives it all of it.
            onTap: urls.isEmpty
                ? null
                : () => context.push(
                      Routes.photoFor(product.id, index),
                      extra: product,
                    ),
            child: Stage(
              border: false,
              child: LayoutBuilder(
                builder: (context, constraints) {
                  _warm(constraints.maxWidth);
                  _warmZoom(index, urls);
                  return Photo(
                    url: urls.isEmpty ? '' : urls[index],
                    width: constraints.maxWidth,
                    padding: EdgeInsets.all(tablet ? 40 : 26),
                  );
                },
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
        // The stage is tappable but a photo does not look like a control, so it
        // is labelled. Top-right: the thumbs are on the left and the spin entry
        // is along the bottom.
        if (urls.isNotEmpty)
          Positioned(
            top: 14,
            right: 14,
            child: IgnorePointer(
              child: Container(
                padding: const EdgeInsets.symmetric(horizontal: 9, vertical: 6),
                decoration: BoxDecoration(
                  color: p.bg.withValues(alpha: .72),
                  border: Border.all(color: p.line),
                ),
                child: Row(
                  mainAxisSize: MainAxisSize.min,
                  children: [
                    Icon(Icons.zoom_in, size: 13, color: p.muted),
                    const SizedBox(width: 6),
                    Text(
                      L.of(context).tapToZoom.toUpperCase(),
                      style: PearlText.micro.copyWith(fontSize: 8, color: p.muted),
                    ),
                  ],
                ),
              ),
            ),
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
              L.of(context).spin360(frames).toUpperCase(),
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
                'in_stock' => L.of(context).inStockCount(product.totalStock.toInt()),
                'available_in_other_branches' => L.of(context).otherStores,
                _ => L.of(context).soldOut,
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
          // No unit: every line in this catalogue is sold by the piece, so
          // "Nos" on a shoe told a customer nothing they did not assume.
          [
            product.code,
            if (product.color.isNotEmpty) product.color,
          ].join(' · ').toUpperCase(),
          style: PearlText.micro.copyWith(fontSize: 8.5, color: p.faint),
        ),
        const SizedBox(height: 16),
        Text(money(product.mrp), style: PearlText.price(26).copyWith(color: p.ink)),
        if (sizes.isNotEmpty) ...[
          _PanelHeading(L.of(context).sizeRun, trailing: state.selectedSize),
          _SizeRun(
            sizes: sizes,
            selected: state.selectedSize,
            branchId: branchId,
          ),
        ],
        _Availability(product: product, branchId: branchId),
        if (product.description.isNotEmpty) ...[
          _PanelHeading(L.of(context).details),
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

/// Where you can pick this up, as a line of shop codes.
///
/// Only the shops that actually have it, and only their short code: a customer
/// is deciding whether to walk across the mall, not reading a stock report. The
/// full names ("Sizerun Mall of Qatar") wrapped to two lines each and turned
/// five branches into a panel taller than the price — and the shops that had
/// none of it took up exactly as much room as the ones that did.
class _Availability extends StatelessWidget {
  const _Availability({required this.product, required this.branchId});

  final Product product;
  final int? branchId;

  @override
  Widget build(BuildContext context) {
    final p = context.pearl;
    final stocked = product
        .branchesByStock(branchId)
        .where((line) => line.hasStock && line.branchName.isNotEmpty)
        .toList(growable: false);
    final labels = shortenBranchNames(stocked.map((line) => line.branchName).toList());

    if (stocked.isEmpty) {
      return Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          _PanelHeading(L.of(context).availability),
          Text(
            L.of(context).notOnShelf,
            style: PearlText.body(11.5).copyWith(color: p.faint),
          ),
        ],
      );
    }

    return Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        _PanelHeading(
          L.of(context).availability,
          trailing: stocked.length == 1
              ? L.of(context).storeCount(1)
              : L.of(context).storesCount(stocked.length),
        ),
        Wrap(
          spacing: 7,
          runSpacing: 7,
          children: [
            for (var i = 0; i < stocked.length; i++)
              _BranchName(
                name: labels[i],
                here: stocked[i].branchId == branchId,
              ),
          ],
        ),
      ],
    );
  }
}

/// Drop a leading word every shop shares.
///
/// The branches here are "SIZERUN MALL OF QATAR", "SIZERUN GALLERIA MALL",
/// "SIZERUN DOHA MALL" — the tenant's own name, repeated on every chip, inside
/// an app that already says it at the top of the screen. Removing it is what
/// lets the shops stay on one line and still read as names rather than codes.
///
/// Only ever strips a word *all* of them start with, and never the last word,
/// so a single branch or a set with nothing in common is left exactly as it is.
@visibleForTesting
List<String> shortenBranchNames(List<String> names) {
  if (names.length < 2) return names;

  var out = names.map((n) => n.trim()).toList();
  while (true) {
    final heads = out.map((n) => n.split(RegExp(r'\s+')).first).toSet();
    if (heads.length != 1) return out;
    final trimmed = out
        .map((n) => n.split(RegExp(r'\s+')).skip(1).join(' ').trim())
        .toList();
    if (trimmed.any((n) => n.isEmpty)) return out;
    out = trimmed;
  }
}

class _BranchName extends StatelessWidget {
  const _BranchName({required this.name, required this.here});

  final String name;

  /// The shop the tablet is standing in, filled so it reads first.
  final bool here;

  @override
  Widget build(BuildContext context) {
    final p = context.pearl;
    return Container(
      padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 7),
      decoration: BoxDecoration(
        color: here ? p.accent : null,
        border: Border.all(color: here ? p.accent : p.line),
      ),
      child: Text(
        name.toUpperCase(),
        style: PearlText.micro.copyWith(
          fontSize: 9,
          letterSpacing: 1.8,
          color: here ? p.accentInk : p.ink,
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
            label: L.of(context).reserveInStore,
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
  const _RelatedRail({required this.state});

  final ProductState state;

  @override
  Widget build(BuildContext context) {
    final p = context.pearl;
    if (state.related.isEmpty) return const SizedBox.shrink();
    const cardWidth = 128.0;
    // As tall as one card and no taller. A fixed height was measured against
    // English and cropped the price off the bottom of every card in Arabic.
    final rowHeight =
        cardWidth + ProductCard.captionHeight(context, compact: true);
    return Container(
      decoration: BoxDecoration(border: Border(top: BorderSide(color: p.line))),
      padding: const EdgeInsets.fromLTRB(PearlMetrics.pad, 14, 0, 10),
      child: Column(
        mainAxisSize: MainAxisSize.min,
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Padding(
            padding: const EdgeInsets.only(right: PearlMetrics.pad),
            child: SectionHeading(L.of(context).youMayAlsoLike),
          ),
          SizedBox(
            height: rowHeight,
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
