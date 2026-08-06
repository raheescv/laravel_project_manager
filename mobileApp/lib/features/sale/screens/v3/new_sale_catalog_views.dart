part of 'new_sale_screen.dart';

// The catalog half of New Sale — search, type/category filters and the
// product grid/list tiles. Split out to keep new_sale_screen.dart readable;
// as a `part` these stay library-private and no call site changed.

extension _CatalogViews on _NewSaleScreenState {
  /// Two-up boutique tile grid (more columns on tablet/desktop — ≈200px each).
  /// Lazily built so long catalogs stay smooth as pages stream in.
  Widget _productGrid(CatalogCubit cat) => SliverPadding(
        padding: const EdgeInsets.fromLTRB(16, 4, 16, 16),
        sliver: SliverLayoutBuilder(
          builder: (context, constraints) {
            const gap = 13.0;
            final cols = (constraints.crossAxisExtent / 200).floor().clamp(2, 5);
            // Actual painted tile width, so the photo is decoded to the size it
            // is drawn at rather than at full source resolution.
            final tileW = (constraints.crossAxisExtent - gap * (cols - 1)) / cols;
            return SliverGrid(
              gridDelegate: SliverGridDelegateWithFixedCrossAxisCount(
                crossAxisCount: cols,
                crossAxisSpacing: gap,
                mainAxisSpacing: gap,
                childAspectRatio: 0.72,
              ),
              delegate: SliverChildBuilderDelegate(
                (context, i) => _serviceTile(cat.products[i], tileW),
                childCount: cat.products.length,
              ),
            );
          },
        ),
      );

  /// Single-column list mode — a premium row with a full-height image on the
  /// left, name + meta, and a serif price with the add button.
  Widget _productList(CatalogCubit cat) => SliverPadding(
        padding: const EdgeInsets.fromLTRB(16, 4, 16, 16),
        sliver: SliverList(
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
    final cart = context.read<CartCubit>();
    return GestureDetector(
      onTap: () {
        HapticFeedback.lightImpact();
        cart.add(s);
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
  Widget _searchRow(CatalogCubit cat) {
    final p = context.astra;
    return Container(
      height: 54,
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

  Widget _typeSegmented(CatalogCubit cat) {
    final p = context.astra;
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
        children: [
          for (final (value, label) in options)
            Expanded(
              child: GestureDetector(
                onTap: () {
                  HapticFeedback.selectionClick();
                  cat.selectType(value);
                },
                child: AnimatedContainer(
                  duration: const Duration(milliseconds: 160),
                  padding: const EdgeInsets.symmetric(vertical: 10),
                  alignment: Alignment.center,
                  decoration: BoxDecoration(
                    color: cat.selectedType == value ? p.card : Colors.transparent,
                    borderRadius: BorderRadius.circular(10),
                    boxShadow: cat.selectedType == value ? context.astraTheme.softShadow : null,
                  ),
                  child: Text(
                    label,
                    maxLines: 1,
                    overflow: TextOverflow.ellipsis,
                    style: ui(
                        size: 12.5,
                        weight: FontWeight.w700,
                        color: cat.selectedType == value ? p.primary : p.textSecondary),
                  ),
                ),
              ),
            ),
        ],
      ),
    );
  }

  Widget _categoryChips(CatalogCubit cat) {
    return SizedBox(
      height: 36,
      child: ListView(
        scrollDirection: Axis.horizontal,
        padding: const EdgeInsets.symmetric(horizontal: 16),
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
  /// a corner add-button.
  Widget _serviceTile(Product s, double width) {
    final p = context.astra;
    final cart = context.read<CartCubit>();
    return GestureDetector(
      onTap: () {
        HapticFeedback.lightImpact();
        cart.add(s);
      },
      child: Container(
        clipBehavior: Clip.antiAlias,
        decoration: BoxDecoration(
          color: p.card,
          borderRadius: BorderRadius.circular(20),
          border: Border.all(color: p.hairline),
          boxShadow: context.astraTheme.softShadow,
        ),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.stretch,
          children: [
            Expanded(child: _tileImage(s, width)),
            Padding(
              padding: const EdgeInsets.fromLTRB(12, 10, 10, 11),
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                mainAxisSize: MainAxisSize.min,
                children: [
                  Text(s.name,
                      maxLines: 2,
                      overflow: TextOverflow.ellipsis,
                      style: ui(size: 12.5, weight: FontWeight.w800, color: p.ink, height: 1.2)),
                  const SizedBox(height: 3),
                  Text(_metaLine(s),
                      maxLines: 1,
                      overflow: TextOverflow.ellipsis,
                      style: ui(size: 10.5, weight: FontWeight.w600, color: p.textMuted)),
                  const SizedBox(height: 9),
                  Row(
                    children: [
                      Flexible(child: _priceText(s.mrp)),
                      const SizedBox(width: 8),
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
                    ],
                  ),
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
  Widget _tileImage(Product s, double width) {
    if (s.thumbnail.isEmpty) return _tileFallback(s);
    final cfg = context.read<AuthCubit>().config;
    return Image.network(
      cfg.assetUrl(s.thumbnail),
      headers: cfg.assetHeaders,
      cacheWidth: decodeWidthFor(context, width),
      fit: BoxFit.cover,
      width: double.infinity,
      height: double.infinity,
      gaplessPlayback: true,
      errorBuilder: (_, __, ___) => _tileFallback(s),
      loadingBuilder: (context, child, progress) =>
          progress == null ? child : _tileFallback(s, loading: true),
    );
  }

  Widget _tileFallback(Product s, {bool loading = false}) {
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
          : Icon(iconForName('${s.categoryName} ${s.name}'), size: 40, color: p.primary.withValues(alpha: 0.85)),
    );
  }

  /// Two-tone serif price: gold currency mark + ink amount.
  Widget _priceText(double v) {
    final p = context.astra;
    return RichText(
      maxLines: 1,
      overflow: TextOverflow.ellipsis,
      text: TextSpan(children: [
        TextSpan(text: '${Money.symbol.trim()} ', style: serif(size: 12, color: p.goldText)),
        TextSpan(text: Money.plain(v), style: serif(size: 17, color: p.ink)),
      ]),
    );
  }
}
