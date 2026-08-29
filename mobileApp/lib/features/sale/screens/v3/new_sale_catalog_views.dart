part of 'new_sale_screen.dart';

// The catalog half of New Sale — search, type/category filters and the
// product grid/list tiles. Split out to keep new_sale_screen.dart readable;
// as a `part` these stay library-private and no call site changed.

extension _CatalogViews on _NewSaleScreenState {
  /// Boutique tile grid. How many tiles fit across is the till's own choice
  /// (Settings → Catalog grid, two-up by default) — a catalog carrying no
  /// photos reads better three or four up, where a tile is just a name and a
  /// price. A screen wide enough for more than the preference asks for still
  /// gets more, so a tablet is never left with two enormous tiles.
  /// Lazily built so long catalogs stay smooth as pages stream in.
  Widget _productGrid(CatalogCubit cat, int preferredCols, {bool tablet = false}) => SliverPadding(
        padding: EdgeInsets.fromLTRB(tablet ? 14 : 16, 4, tablet ? 14 : 16, 16),
        sliver: SliverLayoutBuilder(
          builder: (context, constraints) {
            const gap = 13.0;
            // A tablet is held further away and has the room, so tiles pack a
            // little tighter there — 200pt apiece leaves a wide grid emptier
            // than it needs to be.
            final fits = (constraints.crossAxisExtent / (tablet ? 176 : 200)).floor();
            final cols = (fits > preferredCols ? fits : preferredCols).clamp(2, 6);
            // Actual painted tile width, so the photo is decoded to the size it
            // is drawn at rather than at full source resolution — and so the
            // tile's own chrome can shrink along with it.
            final tileW = (constraints.crossAxisExtent - gap * (cols - 1)) / cols;
            final scale = _TileScale.forWidth(tileW);
            return SliverGrid(
              gridDelegate: SliverGridDelegateWithFixedCrossAxisCount(
                crossAxisCount: cols,
                crossAxisSpacing: gap,
                mainAxisSpacing: gap,
                childAspectRatio: tileW / (tileW * 0.75 + scale.captionHeight),
              ),
              delegate: SliverChildBuilderDelegate(
                (context, i) => _serviceTile(cat.products[i], tileW, scale),
                childCount: cat.products.length,
              ),
            );
          },
        ),
      );

  /// Single-column list mode — a premium row with a full-height image on the
  /// left, name + meta, and a serif price with the add button.
  Widget _productList(CatalogCubit cat, {bool tablet = false}) => SliverPadding(
        padding: EdgeInsets.fromLTRB(tablet ? 14 : 16, 4, tablet ? 14 : 16, 16),
        // One row per line is a phone shape: at ~770pt the name and the price
        // end up at opposite ends of a near-empty band. On a tablet the rows
        // column up instead — the row keeps its height (the 84pt thumbnail
        // sets it), the column just fits as many across as 460pt allows.
        sliver: tablet
            ? SliverGrid(
                gridDelegate: const SliverGridDelegateWithMaxCrossAxisExtent(
                  maxCrossAxisExtent: 460,
                  mainAxisExtent: 84,
                  crossAxisSpacing: 12,
                  mainAxisSpacing: 10,
                ),
                delegate: SliverChildBuilderDelegate(
                  (context, i) => _serviceListRow(cat.products[i]),
                  childCount: cat.products.length,
                ),
              )
            : SliverList(
                delegate: SliverChildBuilderDelegate(
                  (context, i) => Padding(
                    padding: const EdgeInsets.only(bottom: 10),
                    child: _serviceListRow(cat.products[i]),
                  ),
                  childCount: cat.products.length,
                ),
              ),
      );

  /// Compact grid / list switcher — a small segmented control pinned to the
  /// right of the category row. The choice is remembered per device.
  Widget _viewToggle() {
    final p = context.astra;
    Widget btn(IconData icon, _ProductView mode) {
      final active = _view == mode;
      return GestureDetector(
        onTap: () => _setView(mode),
        child: AnimatedContainer(
          duration: const Duration(milliseconds: 160),
          width: 34,
          height: 30,
          alignment: Alignment.center,
          decoration: BoxDecoration(
            gradient: active ? p.primaryGradient : null,
            borderRadius: BorderRadius.circular(9),
          ),
          child: Icon(icon, size: 17, color: active ? Colors.white : p.textMuted),
        ),
      );
    }

    return Container(
      padding: const EdgeInsets.all(3),
      decoration: BoxDecoration(
        color: p.card,
        borderRadius: BorderRadius.circular(12),
        border: Border.all(color: p.hairline),
        boxShadow: context.astraTheme.softShadow,
      ),
      child: Row(
        mainAxisSize: MainAxisSize.min,
        children: [
          btn(Icons.grid_view_rounded, _ProductView.grid),
          const SizedBox(width: 2),
          btn(Icons.view_agenda_outlined, _ProductView.list),
        ],
      ),
    );
  }

  Widget _serviceListRow(Product s) {
    final p = context.astra;
    return GestureDetector(
      onTap: () {
        HapticFeedback.lightImpact();
        _addToCart(s);
      },
      child: Container(
        clipBehavior: Clip.antiAlias,
        decoration: BoxDecoration(
          color: p.card,
          borderRadius: BorderRadius.circular(18),
          border: Border.all(color: p.hairline),
          boxShadow: context.astraTheme.softShadow,
        ),
        child: Row(
          children: [
            SizedBox(width: 84, height: 84, child: _tileImage(s, 84)),
            const SizedBox(width: 13),
            Expanded(
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                mainAxisAlignment: MainAxisAlignment.center,
                children: [
                  Text(s.name,
                      maxLines: 2,
                      overflow: TextOverflow.ellipsis,
                      style: ui(size: 13.5, weight: FontWeight.w800, color: p.ink, height: 1.2)),
                  const SizedBox(height: 4),
                  Text(_metaLine(s),
                      maxLines: 1,
                      overflow: TextOverflow.ellipsis,
                      style: ui(size: 11, weight: FontWeight.w600, color: p.textMuted)),
                ],
              ),
            ),
            const SizedBox(width: 12),
            Flexible(child: _priceText(s.mrp)),
            const SizedBox(width: 12),
            Container(
              width: 34,
              height: 34,
              decoration: BoxDecoration(
                gradient: p.primaryGradient,
                borderRadius: BorderRadius.circular(12),
                boxShadow: context.astraTheme.floatShadow(p.primary),
              ),
              child: const Icon(Icons.add, color: Colors.white, size: 18),
            ),
            const SizedBox(width: 12),
          ],
        ),
      ),
    );
  }

  /// Recessed soft search field with the barcode scanner tucked inside it.
  Widget _searchRow(CatalogCubit cat, {double height = 54}) {
    final p = context.astra;
    return Container(
      height: height,
      padding: const EdgeInsets.only(left: 16, right: 8),
      decoration: BoxDecoration(
        color: _soft,
        borderRadius: BorderRadius.circular(16),
      ),
      child: Row(
        children: [
          Icon(Icons.search, color: p.textSecondary, size: 21),
          const SizedBox(width: 10),
          Expanded(
            child: TextField(
              controller: _searchCtl,
              onChanged: cat.setSearch,
              style: ui(size: 13.5, weight: FontWeight.w600, color: p.ink),
              decoration: InputDecoration(
                isDense: true,
                hintText: 'Search name or code',
                hintStyle: ui(size: 13, weight: FontWeight.w500, color: p.textMuted),
                border: InputBorder.none,
                contentPadding: EdgeInsets.zero,
              ),
            ),
          ),
          const SizedBox(width: 8),
          GestureDetector(
            onTap: () {
              HapticFeedback.selectionClick();
              _scanBarcode(cat);
            },
            child: Container(
              width: 38,
              height: 38,
              decoration: BoxDecoration(
                color: p.card,
                borderRadius: BorderRadius.circular(11),
                boxShadow: context.astraTheme.softShadow,
              ),
              child: Icon(Icons.qr_code_scanner, color: p.primary, size: 19),
            ),
          ),
        ],
      ),
    );
  }

  /// Product / Service filter (segmented control) + the grid/list switcher, on
  /// one neat row. Default type comes from Settings → Sale Configuration.
  Widget _typeFilterRow(CatalogCubit cat) {
    return Row(
      children: [
        Expanded(child: _typeSegmented(cat)),
        const SizedBox(width: 10),
        _viewToggle(),
      ],
    );
  }

  /// [compact] sizes each segment to its label instead of splitting the row
  /// evenly, so the control can sit beside a search field on the tablet toolbar
  /// rather than owning a line of its own.
  Widget _typeSegmented(CatalogCubit cat, {bool compact = false}) {
    const options = <(String?, String)>[
      (null, 'All Types'),
      ('product', 'Products'),
      ('service', 'Services'),
    ];
    return Container(
      padding: const EdgeInsets.all(4),
      decoration: BoxDecoration(
        color: _soft,
        borderRadius: BorderRadius.circular(14),
      ),
      child: Row(
        mainAxisSize: compact ? MainAxisSize.min : MainAxisSize.max,
        children: [
          for (final (value, label) in options) _typeSeg(cat, value, label, compact),
        ],
      ),
    );
  }

  Widget _typeSeg(CatalogCubit cat, String? value, String label, bool compact) {
    final p = context.astra;
    final active = cat.selectedType == value;
    final seg = GestureDetector(
      onTap: () {
        HapticFeedback.selectionClick();
        cat.selectType(value);
      },
      child: AnimatedContainer(
        duration: const Duration(milliseconds: 160),
        padding: EdgeInsets.symmetric(vertical: 10, horizontal: compact ? 15 : 0),
        alignment: Alignment.center,
        decoration: BoxDecoration(
          color: active ? p.card : Colors.transparent,
          borderRadius: BorderRadius.circular(10),
          boxShadow: active ? context.astraTheme.softShadow : null,
        ),
        child: Text(
          label,
          maxLines: 1,
          overflow: TextOverflow.ellipsis,
          style: ui(size: 12.5, weight: FontWeight.w700, color: active ? p.primary : p.textSecondary),
        ),
      ),
    );
    return compact ? seg : Expanded(child: seg);
  }

  Widget _categoryChips(CatalogCubit cat, {bool tablet = false}) {
    return SizedBox(
      height: 36,
      child: ListView(
        scrollDirection: Axis.horizontal,
        padding: EdgeInsets.symmetric(horizontal: tablet ? 14 : 16),
        children: [
          _catChip('All', cat.selectedCategoryId == null, () => cat.selectCategory(null)),
          for (final c in cat.categories) ...[
            const SizedBox(width: 8),
            _catChip(c.name, cat.selectedCategoryId == c.id, () => cat.selectCategory(c.id)),
          ],
        ],
      ),
    );
  }

  /// Pill category chip — filled ink when active, hairline outline otherwise.
  Widget _catChip(String label, bool active, VoidCallback onTap) {
    final p = context.astra;
    return GestureDetector(
      onTap: () {
        HapticFeedback.selectionClick();
        onTap();
      },
      child: Container(
        alignment: Alignment.center,
        padding: const EdgeInsets.symmetric(horizontal: 16),
        decoration: BoxDecoration(
          color: active ? p.ink : p.card,
          borderRadius: BorderRadius.circular(999),
          border: Border.all(color: active ? p.ink : p.hairline),
        ),
        child: Text(label,
            style: ui(
                size: 12.5,
                weight: FontWeight.w700,
                color: active ? p.canvas : p.textSecondary)),
      ),
    );
  }

  /// Boutique product tile: a full-bleed image (or a tinted category panel when
  /// there's no photo) crowning the card, then name, meta and a serif price with
  /// a corner add-button. [k] trims that chrome down as the grid gets denser.
  Widget _serviceTile(Product s, double width, _TileScale k) {
    final p = context.astra;
    return GestureDetector(
      onTap: () {
        HapticFeedback.lightImpact();
        _addToCart(s);
      },
      child: Container(
        clipBehavior: Clip.antiAlias,
        decoration: BoxDecoration(
          color: p.card,
          borderRadius: BorderRadius.circular(k.radius),
          border: Border.all(color: p.hairline),
          boxShadow: context.astraTheme.softShadow,
        ),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.stretch,
          children: [
            Expanded(child: _tileImage(s, width, iconSize: k.icon)),
            Padding(
              padding: EdgeInsets.fromLTRB(k.padH, k.padTop, k.padH - 2, k.padBottom),
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                mainAxisSize: MainAxisSize.min,
                children: [
                  Text(s.name,
                      maxLines: k.nameLines,
                      overflow: TextOverflow.ellipsis,
                      style: ui(size: k.name, weight: FontWeight.w800, color: p.ink, height: 1.2)),
                  if (k.showMeta) ...[
                    const SizedBox(height: 3),
                    Text(_metaLine(s),
                        maxLines: 1,
                        overflow: TextOverflow.ellipsis,
                        style: ui(size: k.meta, weight: FontWeight.w600, color: p.textMuted)),
                  ],
                  SizedBox(height: k.gapAbovePrice),
                  // Below ~110px the price and the button can't share a row
                  // without the amount ellipsing away — the whole tile adds to
                  // the cart on tap anyway, so the button is what gives.
                  if (k.showAdd)
                    Row(
                      children: [
                        Flexible(child: _priceText(s.mrp, currency: k.currency, amount: k.price)),
                        const SizedBox(width: 8),
                        Container(
                          width: k.add,
                          height: k.add,
                          decoration: BoxDecoration(
                            gradient: p.primaryGradient,
                            borderRadius: BorderRadius.circular(k.add * 0.35),
                            boxShadow: context.astraTheme.floatShadow(p.primary),
                          ),
                          child: Icon(Icons.add, color: Colors.white, size: k.add * 0.53),
                        ),
                      ],
                    )
                  else
                    _priceText(s.mrp, currency: k.currency, amount: k.price),
                ],
              ),
            ),
          ],
        ),
      ),
    );
  }

  /// The tile's crowning image — cover-fills the whole slot when the product has
  /// a photo, otherwise a tinted panel with a large category icon so the frame
  /// is never empty. Storage paths are resolved onto the reachable base URL with
  /// the same host header the API uses (mirrors [ProductThumb]).
  Widget _tileImage(Product s, double width, {double iconSize = 40}) {
    if (s.thumbnail.isEmpty) return _tileFallback(s, iconSize: iconSize);
    final cfg = context.read<AuthCubit>().config;
    return Image(
      image: OfflineImage.provider(
        cfg.assetUrl(s.thumbnail),
        headers: cfg.assetHeaders,
        cacheWidth: decodeWidthFor(context, width),
      ),
      fit: BoxFit.cover,
      width: double.infinity,
      height: double.infinity,
      gaplessPlayback: true,
      errorBuilder: (_, __, ___) => _tileFallback(s, iconSize: iconSize),
      loadingBuilder: (context, child, progress) =>
          progress == null ? child : _tileFallback(s, loading: true, iconSize: iconSize),
    );
  }

  Widget _tileFallback(Product s, {bool loading = false, double iconSize = 40}) {
    final p = context.astra;
    return Container(
      decoration: BoxDecoration(
        gradient: LinearGradient(
          begin: Alignment.topLeft,
          end: Alignment.bottomRight,
          colors: [p.tint, p.tint.withValues(alpha: p.isDark ? 0.35 : 0.55)],
        ),
      ),
      alignment: Alignment.center,
      child: loading
          ? SizedBox(width: 22, height: 22, child: CircularProgressIndicator(strokeWidth: 2, color: p.primary))
          : Icon(iconForName('${s.categoryName} ${s.name}'), size: iconSize, color: p.primary.withValues(alpha: 0.85)),
    );
  }

  /// Two-tone serif price: gold currency mark + ink amount.
  Widget _priceText(double v, {double currency = 12, double amount = 17}) {
    final p = context.astra;
    return RichText(
      maxLines: 1,
      overflow: TextOverflow.ellipsis,
      text: TextSpan(children: [
        TextSpan(text: '${Money.symbol.trim()} ', style: serif(size: currency, color: p.goldText)),
        TextSpan(text: Money.plain(v), style: serif(size: amount, color: p.ink)),
      ]),
    );
  }
}

/// The product tile's chrome, sized to the width it is actually painted at.
/// A four-up grid on a phone leaves each tile barely 80px across, where the
/// two-up type, the meta line and the 34px add button have nowhere to go.
class _TileScale {
  const _TileScale._({
    required this.padH,
    required this.padTop,
    required this.padBottom,
    required this.radius,
    required this.name,
    required this.nameLines,
    required this.meta,
    required this.currency,
    required this.price,
    required this.add,
    required this.icon,
  });

  /// Roomy above ~150px (two-up on a phone, and most tablet grids), trimmed
  /// through the middle band, stripped back to name + price under ~108px.
  factory _TileScale.forWidth(double w) {
    if (w >= 150) {
      return const _TileScale._(
          padH: 12, padTop: 10, padBottom: 11, radius: 20,
          name: 12.5, nameLines: 2, meta: 10.5, currency: 12, price: 17, add: 34, icon: 40);
    }
    if (w >= 108) {
      return const _TileScale._(
          padH: 9, padTop: 8, padBottom: 9, radius: 16,
          name: 11.5, nameLines: 2, meta: 0, currency: 10.5, price: 14, add: 26, icon: 32);
    }
    return const _TileScale._(
        padH: 8, padTop: 7, padBottom: 8, radius: 13,
        name: 10.5, nameLines: 1, meta: 0, currency: 9.5, price: 12.5, add: 0, icon: 24);
  }

  final double padH, padTop, padBottom, radius, name, meta, currency, price, add, icon;
  final int nameLines;

  bool get showMeta => meta > 0;
  bool get showAdd => add > 0;
  double get gapAbovePrice => showMeta ? 9 : 7;

  /// Height of the caption under the photo. The grid takes its aspect ratio
  /// from this, so the text is always given the room it asks for and a dense
  /// tile can't overflow.
  double get captionHeight =>
      padTop +
      padBottom +
      name * 1.2 * nameLines +
      (showMeta ? meta * 1.35 + 3 : 0) +
      gapAbovePrice +
      (showAdd ? add : price * 1.35);
}
