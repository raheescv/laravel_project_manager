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
                childAspectRatio: tileW / (tileW + scale.captionHeight),
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
  /// left, name + meta, and a serif price at the end.
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

  /// The list's row carries the same bargain as the grid tile: no add button,
  /// because the row itself adds — and the count badge on the thumbnail is what
  /// says the tap landed.
  Widget _serviceListRow(Product s) {
    final p = context.astra;
    return BlocSelector<CartCubit, CartState, double>(
      key: ValueKey(s.id),
      selector: (cart) =>
          cart.lines.where((l) => l.productId == s.id).fold(0.0, (sum, l) => sum + l.qty),
      builder: (context, qty) => GestureDetector(
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
              SizedBox(
                width: 84,
                height: 84,
                child: Stack(
                  fit: StackFit.expand,
                  children: [
                    _tileImage(s, 84),
                    if (qty > 0) Positioned(top: 6, left: 6, child: _qtyBadge(qty, 22)),
                  ],
                ),
              ),
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
              // Sized to the amount, never flexed: as a Flexible it split the
              // row's spare width evenly with the name column, so a two-word
              // product wrapped while the price sat in the middle of an empty
              // half. Inflexible, the name gets everything the price doesn't.
              _priceText(s.mrp),
              const SizedBox(width: 16),
            ],
          ),
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

  /// The category rail, in whichever shape this till chose (Settings →
  /// Category display). Text chips are the default and the cheapest — the rail
  /// is pinned above the grid, so the photo layouts buy recognition with header
  /// height that never comes back.
  ///
  /// Built lazily: a catalog with fifty categories builds the handful on screen.
  Widget _categoryChips(CatalogCubit cat, {bool tablet = false}) {
    final display = context.watch<PosSettingsCubit>().categoryDisplay;
    final gap = switch (display) {
      CategoryDisplay.card => 11.0,
      CategoryDisplay.tile => 10.0,
      _ => 8.0,
    };
    return SizedBox(
      height: display.railHeight,
      child: ListView.separated(
        scrollDirection: Axis.horizontal,
        padding: EdgeInsets.symmetric(horizontal: tablet ? 14 : 16),
        itemCount: cat.categories.length + 1,
        separatorBuilder: (_, __) => SizedBox(width: gap),
        itemBuilder: (_, i) {
          // Index 0 is "All", which has no category behind it — hence the
          // nullable [category] every builder below takes.
          if (i == 0) {
            return _catItem(display, null, cat.selectedCategoryId == null,
                () => cat.selectCategory(null));
          }
          final c = cat.categories[i - 1];
          return _catItem(display, c, cat.selectedCategoryId == c.id,
              () => cat.selectCategory(c.id));
        },
      ),
    );
  }

  /// One entry in the rail. [category] is null for "All".
  Widget _catItem(CategoryDisplay display, Category? category, bool active, VoidCallback onTap) {
    final label = category?.name ?? 'All';
    return GestureDetector(
      onTap: () {
        HapticFeedback.selectionClick();
        onTap();
      },
      behavior: HitTestBehavior.opaque,
      child: switch (display) {
        CategoryDisplay.nameOnly => _catChip(label, active),
        CategoryDisplay.avatar =>
          _catChip(label, active, leading: _catThumb(category, 30, 30, 15, iconSize: 16)),
        CategoryDisplay.card => _catCard(category, label, active),
        CategoryDisplay.tile => _catTile(category, label, active),
      },
    );
  }

  /// Pill category chip — filled ink when active, hairline outline otherwise.
  /// [leading] is the photo puck on the "Name with photo" layout.
  Widget _catChip(String label, bool active, {Widget? leading}) {
    final p = context.astra;
    return Container(
      alignment: Alignment.center,
      padding: EdgeInsets.fromLTRB(leading == null ? 16 : 5, 0, 16, 0),
      decoration: BoxDecoration(
        color: active ? p.ink : p.card,
        borderRadius: BorderRadius.circular(999),
        border: Border.all(color: active ? p.ink : p.hairline),
      ),
      child: Row(
        mainAxisSize: MainAxisSize.min,
        children: [
          if (leading != null) ...[leading, const SizedBox(width: 8)],
          Text(label,
              style: ui(
                  size: 12.5,
                  weight: FontWeight.w700,
                  color: active ? p.canvas : p.textSecondary)),
        ],
      ),
    );
  }

  /// "Photo card" — the photo is the thing you tap, the name labels it.
  /// Selection is a ring and a bolder name rather than a fill, so the photo
  /// keeps its own colour.
  Widget _catCard(Category? category, String label, bool active) {
    final p = context.astra;
    return SizedBox(
      width: 66,
      child: Column(
        mainAxisSize: MainAxisSize.min,
        children: [
          AnimatedContainer(
            duration: const Duration(milliseconds: 160),
            padding: const EdgeInsets.all(2),
            decoration: BoxDecoration(
              borderRadius: BorderRadius.circular(20),
              border: Border.all(color: active ? p.ink : Colors.transparent, width: 2),
            ),
            child: _catThumb(category, 56, 56, 15, iconSize: 24),
          ),
          const SizedBox(height: 5),
          Flexible(
            child: Text(label,
                maxLines: 1,
                overflow: TextOverflow.ellipsis,
                style: ui(
                    size: 10.5,
                    weight: active ? FontWeight.w800 : FontWeight.w700,
                    color: active ? p.ink : p.textSecondary)),
          ),
        ],
      ),
    );
  }

  /// "Photo tile" — a wide photo with the name over a bottom scrim. The scrim
  /// is what makes a white name legible over a pale photo; it is not optional.
  Widget _catTile(Category? category, String label, bool active) {
    final p = context.astra;
    return Container(
      width: 104,
      clipBehavior: Clip.antiAlias,
      decoration: BoxDecoration(
        borderRadius: BorderRadius.circular(16),
        border: Border.all(color: active ? p.ink : p.hairline, width: active ? 2 : 1),
      ),
      child: Stack(
        fit: StackFit.expand,
        children: [
          _catThumb(category, 104, 64, 0, iconSize: 26),
          Align(
            alignment: Alignment.bottomCenter,
            child: Container(
              width: double.infinity,
              padding: const EdgeInsets.fromLTRB(9, 14, 9, 6),
              decoration: BoxDecoration(
                gradient: LinearGradient(
                  begin: Alignment.topCenter,
                  end: Alignment.bottomCenter,
                  colors: [Colors.transparent, Colors.black.withValues(alpha: 0.62)],
                ),
              ),
              child: Text(label,
                  maxLines: 1,
                  overflow: TextOverflow.ellipsis,
                  style: ui(size: 11, weight: FontWeight.w800, color: Colors.white)),
            ),
          ),
        ],
      ),
    );
  }

  /// The category's photo, decoded at the size it is painted, or the tinted
  /// fallback panel. Storage paths are resolved onto the reachable base URL the
  /// same way product thumbnails are, and go through [OfflineImage] so a rail
  /// with photos still draws with no network.
  Widget _catThumb(Category? category, double w, double h, double radius, {double iconSize = 18}) {
    if (category == null || !category.hasImage) {
      return _catThumbFallback(category, w, h, radius, iconSize);
    }
    final cfg = context.read<AuthCubit>().config;
    return ClipRRect(
      borderRadius: BorderRadius.circular(radius),
      child: Image(
        image: OfflineImage.provider(
          cfg.assetUrl(category.imageUrl),
          headers: cfg.assetHeaders,
          cacheWidth: decodeWidthFor(context, w),
        ),
        fit: BoxFit.cover,
        width: w,
        height: h,
        gaplessPlayback: true,
        errorBuilder: (_, __, ___) => _catThumbFallback(category, w, h, radius, iconSize),
        loadingBuilder: (context, child, progress) =>
            progress == null ? child : _catThumbFallback(category, w, h, radius, iconSize),
      ),
    );
  }

  /// No photo (or one that failed): a tinted panel carrying the same
  /// name-derived icon the product tiles fall back to, so a catalog only half
  /// photographed still reads as one rail rather than a row of holes.
  Widget _catThumbFallback(Category? category, double w, double h, double radius, double iconSize) {
    final p = context.astra;
    return Container(
      width: w,
      height: h,
      decoration: BoxDecoration(
        borderRadius: BorderRadius.circular(radius),
        gradient: LinearGradient(
          begin: Alignment.topLeft,
          end: Alignment.bottomRight,
          colors: [p.tint, p.tint.withValues(alpha: p.isDark ? 0.35 : 0.55)],
        ),
      ),
      alignment: Alignment.center,
      child: Icon(
        category == null ? Icons.apps_rounded : iconForName(category.name),
        size: iconSize,
        color: p.primary.withValues(alpha: 0.85),
      ),
    );
  }

  /// Boutique product tile — the "Gallery Frame" card. A square photo (or a
  /// tinted category panel where there's no picture) crowned by a frosted price
  /// capsule, then a clean caption: name over code. There is no add button —
  /// the whole tile has always added to the cart on tap, so what the button
  /// really carried was feedback, and that now comes from a count badge on
  /// anything already on the ticket. [k] trims the chrome down as the grid gets
  /// denser.
  Widget _serviceTile(Product s, double width, _TileScale k) {
    final p = context.astra;
    // Only the badge depends on the ticket, so a tile subscribes to its own
    // quantity rather than to the whole cart — adding a line repaints that one
    // tile instead of the visible catalog. Keyed by product because a selector
    // is only re-run when the cubit emits: without the key, changing the
    // category hands the same element a different product and the badge stays
    // on the tile the old one used to occupy.
    return BlocSelector<CartCubit, CartState, double>(
      key: ValueKey(s.id),
      selector: (cart) =>
          cart.lines.where((l) => l.productId == s.id).fold(0.0, (sum, l) => sum + l.qty),
      builder: (context, qty) {
        final inCart = qty > 0;
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
              // No selected-state ring: the tile is a button, not a selection —
              // its only job is to add. The count badge alone says what's on the
              // ticket, and the grid stays quiet while a ticket fills up.
              border: Border.all(color: p.hairline),
              boxShadow: context.astraTheme.softShadow,
            ),
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.stretch,
              children: [
                Expanded(
                  child: Stack(
                    fit: StackFit.expand,
                    children: [
                      _tileImage(s, width, iconSize: k.icon),
                      // Badge and capsule share one bounded row, so a long
                      // amount on a narrow tile ellipsizes inside the capsule
                      // instead of running off the photo.
                      Positioned(
                        top: k.inset,
                        left: k.inset,
                        right: k.inset,
                        child: Row(
                          children: [
                            if (inCart) ...[_qtyBadge(qty, k.badge), SizedBox(width: k.inset)],
                            Expanded(
                              child: Align(alignment: Alignment.centerRight, child: _pricePill(s, k)),
                            ),
                          ],
                        ),
                      ),
                    ],
                  ),
                ),
                Padding(
                  padding: EdgeInsets.fromLTRB(k.padH, k.padTop, k.padH, k.padBottom),
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
                            style: ui(size: k.meta, weight: FontWeight.w700, color: p.textMuted)),
                      ],
                    ],
                  ),
                ),
              ],
            ),
          ),
        );
      },
    );
  }

  /// The price, lifted off the caption and onto the photo. Frosted-looking but
  /// deliberately not blurred: a grid paints dozens of these at once and a
  /// [BackdropFilter] apiece is a cost the till would pay on every scroll frame.
  Widget _pricePill(Product s, _TileScale k) {
    final p = context.astra;
    return Container(
      padding: EdgeInsets.fromLTRB(k.pillH, k.pillV, k.pillH, k.pillV + 1),
      decoration: BoxDecoration(
        color: p.cardSolid.withValues(alpha: p.isDark ? 0.82 : 0.92),
        borderRadius: BorderRadius.circular(999),
        border: Border.all(color: Colors.white.withValues(alpha: p.isDark ? 0.12 : 0.6)),
        boxShadow: [
          BoxShadow(color: Colors.black.withValues(alpha: 0.16), blurRadius: 10, offset: const Offset(0, 4)),
        ],
      ),
      child: _priceText(s.mrp, currency: k.currency, amount: k.price),
    );
  }

  /// What stands in for the old add button, in both the grid and the list: how
  /// many of this product are on the ticket, so a tap that lands is visible on
  /// the tile itself. [size] is the badge's diameter — a capsule once the count
  /// runs to two digits.
  Widget _qtyBadge(double qty, double size) {
    final p = context.astra;
    return Container(
      height: size,
      constraints: BoxConstraints(minWidth: size),
      padding: EdgeInsets.symmetric(horizontal: size * 0.24),
      alignment: Alignment.center,
      decoration: BoxDecoration(
        gradient: p.primaryGradient,
        borderRadius: BorderRadius.circular(999),
        boxShadow: context.astraTheme.floatShadow(p.primary),
      ),
      child: Text(qtyLabel(qty),
          style: ui(size: size * 0.47, weight: FontWeight.w800, color: Colors.white, height: 1.1)),
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
/// two-up type, the meta line and the price capsule have nowhere to go.
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
    required this.inset,
    required this.pillH,
    required this.pillV,
    required this.badge,
    required this.icon,
  });

  /// Roomy above ~150px (two-up on a phone, and most tablet grids), trimmed
  /// through the middle band, stripped back to a name under ~108px.
  factory _TileScale.forWidth(double w) {
    if (w >= 150) {
      return const _TileScale._(
          padH: 12, padTop: 10, padBottom: 12, radius: 20,
          name: 12.5, nameLines: 2, meta: 10.5, currency: 10, price: 15,
          inset: 8, pillH: 9, pillV: 5, badge: 24, icon: 40);
    }
    if (w >= 108) {
      return const _TileScale._(
          padH: 9, padTop: 8, padBottom: 10, radius: 16,
          name: 11.5, nameLines: 2, meta: 9.5, currency: 9, price: 13,
          inset: 6, pillH: 7, pillV: 4, badge: 20, icon: 32);
    }
    return const _TileScale._(
        padH: 8, padTop: 7, padBottom: 9, radius: 13,
        name: 10.5, nameLines: 1, meta: 0, currency: 8, price: 11.5,
        inset: 5, pillH: 5, pillV: 3, badge: 17, icon: 24);
  }

  final double padH, padTop, padBottom, radius, name, meta, currency, price;
  final double inset, pillH, pillV, badge, icon;
  final int nameLines;

  bool get showMeta => meta > 0;

  /// Height of the caption under the photo. The grid takes its aspect ratio
  /// from this, so the text is always given the room it asks for and a dense
  /// tile can't overflow. The price no longer lives here — it sits on the
  /// image — so this is only the name and, where it fits, the code line.
  double get captionHeight =>
      padTop + padBottom + name * 1.2 * nameLines + (showMeta ? meta * 1.35 + 3 : 0);
}
