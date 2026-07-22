import 'dart:ui' show ImageFilter;

import 'package:flutter/material.dart';
import 'package:flutter/services.dart';
import 'package:go_router/go_router.dart';
import 'package:provider/provider.dart';

import 'package:invo/shared/domain/constants/global_variables.dart';
import 'package:invo/shared/domain/constants/mobile_permissions.dart';
import 'package:invo/shared/domain/helpers/formatters.dart';
import 'package:invo/shared/domain/helpers/icons.dart';
import 'package:invo/shared/domain/helpers/responsive.dart';
import 'package:invo/shared/domain/models/index.dart';
import 'package:invo/features/auth/logic/auth_cubit/auth_cubit.dart';
import 'package:invo/features/sale/logic/cart_cubit/cart_cubit.dart';
import 'package:invo/features/sale/logic/catalog_cubit/catalog_cubit.dart';
import 'package:invo/features/sale/logic/stylist_cubit/stylist_cubit.dart';
import 'package:invo/shared/utils/camera_permission.dart';
import 'package:invo/shared/utils/local_storage/local_storage_service.dart';
import 'package:invo/shared/utils/components/theme/index.dart';
import 'package:invo/shared/widgets/astra_widgets.dart';
import 'package:invo/shared/widgets/continuous_scanner_screen.dart';
import 'package:invo/features/sale/widgets/v3/cart_widgets.dart';
import 'package:invo/features/sale/widgets/v3/stylist_sheet.dart';

/// How the catalog renders on the New Sale screen — a full-image tile grid or a
/// compact row list. Persisted per device (LocalStorageService.saleView).
enum _ProductView { grid, list }

class NewSaleScreen extends StatefulWidget {
  const NewSaleScreen({super.key});
  @override
  State<NewSaleScreen> createState() => _NewSaleScreenState();
}

class _NewSaleScreenState extends State<NewSaleScreen> {
  final _searchCtl = TextEditingController();
  final _scrollCtl = ScrollController();

  /// Grid (image tiles) vs list — restored from the last choice on this device.
  _ProductView _view = serviceLocator<LocalStorageService>().saleView == 'list'
      ? _ProductView.list
      : _ProductView.grid;

  @override
  void initState() {
    super.initState();
    _scrollCtl.addListener(_onScroll);
    WidgetsBinding.instance.addPostFrameCallback((_) {
      final cat = context.read<CatalogCubit>();
      cat.loadIfNeeded();
      // Warm the stylist list so the STAFF selector can show the assigned
      // staff's photo (e.g. when editing a sale) without opening the picker.
      context.read<StylistCubit>().loadIfNeeded();
      // Default stylist = logged-in user.
      final user = context.read<AuthCubit>().user;
      final cart = context.read<CartCubit>();
      // Pull the latest sale settings (Settings → Sale Configuration) so the
      // default quantity, tip availability and default Product/Service filter
      // reflect the current web setting; then preselect that type on the catalog.
      cart.syncSaleSettings().then((_) {
        if (mounted) cat.applyDefaultType();
      });
      // Preselect the staff: the last employee used on a ticket (remembered
      // across sales) wins; otherwise default to the logged-in user. Skipped
      // while editing, where the ticket already carries its own stylist.
      if (cart.stylistName.isEmpty) {
        final storage = serviceLocator<LocalStorageService>();
        final savedId = storage.saleStylistId;
        final savedName = storage.saleStylistName;
        if (savedId != null && savedName != null && savedName.isNotEmpty) {
          cart.setStylist(savedId, savedName);
        } else if (user != null) {
          cart.setStylist(int.tryParse(user.id), user.name);
        }
      }
      // Prompt for the client up front so the ticket starts with who's in the
      // chair. Only on a fresh ticket (still the default Walk-in, no items).
      if (cart.customerName == 'Walk-in' && cart.isEmpty) {
        _pickClient();
      }
    });
  }

  @override
  void dispose() {
    _searchCtl.dispose();
    _scrollCtl.dispose();
    super.dispose();
  }

  /// Infinite scroll: pull the next page in once the user nears the bottom.
  void _onScroll() {
    if (!_scrollCtl.hasClients) return;
    final pos = _scrollCtl.position;
    if (pos.pixels >= pos.maxScrollExtent - 600) {
      context.read<CatalogCubit>().loadMore();
    }
  }

  /// Close / cancel the ticket: confirm if there are items, then leave the
  /// screen — pop back to whatever pushed New Sale (dashboard, sales list, …)
  /// when there's a back stack, otherwise land on a sensible home so the
  /// buttons are never a dead-end.
  Future<void> _close() async {
    HapticFeedback.selectionClick();
    final cart = context.read<CartCubit>();
    if (!cart.isEmpty) {
      final discard = await showDialog<bool>(
        context: context,
        builder: (ctx) => AlertDialog(
          title: const Text('Discard this sale?'),
          content: const Text('The current ticket will be cleared.'),
          actions: [
            TextButton(onPressed: () => Navigator.pop(ctx, false), child: const Text('Keep')),
            TextButton(onPressed: () => Navigator.pop(ctx, true), child: const Text('Discard')),
          ],
        ),
      );
      if (discard != true) return;
      cart.clear();
    }
    if (!mounted) return;
    // If New Sale was pushed (from the dashboard, sales list, home FAB, …) pop
    // straight back to it. With no back stack — the signed-in landing screen,
    // or after a completed sale reset the stack via `go('/sale')` — fall back
    // to a home that won't bounce us. `/home` redirects non-admins back to
    // `/sale` (app_router redirect), so only send admins there; a cashier's
    // home IS the sale screen, so reset it in place.
    if (context.canPop()) {
      context.pop();
    } else {
      final canViewAdmin =
          context.read<AuthCubit>().hasPermission(PermissionSlug.salesOverview);
      context.go(canViewAdmin ? '/home' : '/sale');
    }
  }

  @override
  Widget build(BuildContext context) {
    final cat = context.watch<CatalogCubit>();
    final cart = context.watch<CartCubit>();
    final tablet = context.isTablet;
    final p = context.astra;
    // Flat "Clean Boutique" surface — a calm near-white (the preset canvas
    // lifted toward white in light, canvas as-is in dark) rather than the
    // app-wide aurora, so the product photos read on a clean ground.
    final surface = p.isDark ? p.canvas : Color.lerp(p.canvas, Colors.white, 0.5)!;

    return Scaffold(
      backgroundColor: surface,
      body: Column(
        children: [
          _header(cart),
          Expanded(
            child: tablet
                ? Row(
                    crossAxisAlignment: CrossAxisAlignment.stretch,
                    children: [
                      Expanded(child: _body(cat)),
                      _tabletCartPanel(cart),
                    ],
                  )
                : _body(cat),
          ),
        ],
      ),
      // Phone keeps the floating cart bar; tablet has the persistent panel.
      bottomNavigationBar: (tablet || cart.isEmpty)
          ? null
          : SafeArea(
              child: Padding(
                padding: const EdgeInsets.fromLTRB(14, 0, 14, 12),
                child: _cartBar(cart),
              ),
            ),
    );
  }

  // ─── Header (Atelier — light editorial, token-driven) ──────────────────────

  /// A subtle recessed fill for the search field + segmented control. Neutral so
  /// it reads the same on every preset (light or dark) rather than picking up a
  /// brand tint.
  Color get _soft {
    final p = context.astra;
    return p.isDark
        ? Colors.white.withValues(alpha: 0.06)
        : Colors.black.withValues(alpha: 0.04);
  }

  /// Flat centered serif title framed by two borderless ghost buttons, then the
  /// joined Client / Staff selector.
  Widget _header(CartCubit cart) {
    final p = context.astra;
    return SafeArea(
      bottom: false,
      child: Padding(
        padding: const EdgeInsets.fromLTRB(16, 6, 16, 8),
        child: Column(
          children: [
            Row(
              children: [
                _ghostBtn(Icons.chevron_left, _close),
                Expanded(
                  child: Text(
                    cart.isEditing ? 'Edit Sale' : 'New Sale',
                    textAlign: TextAlign.center,
                    style: serif(size: 23, color: p.ink),
                  ),
                ),
                _ghostBtn(Icons.close, _close),
              ],
            ),
            const SizedBox(height: 16),
            _whoRow(cart),
          ],
        ),
      ),
    );
  }

  /// Borderless header icon button (back / close).
  Widget _ghostBtn(IconData icon, VoidCallback onTap) {
    final p = context.astra;
    return GestureDetector(
      behavior: HitTestBehavior.opaque,
      onTap: () {
        HapticFeedback.selectionClick();
        onTap();
      },
      child: SizedBox(
        width: 42,
        height: 42,
        child: Icon(icon, size: 23, color: p.textSecondary),
      ),
    );
  }

  /// Joined Client | Staff selector — one rounded card split by a hairline, each
  /// half a tappable segment with a gold micro-label and circular avatar.
  Widget _whoRow(CartCubit cart) {
    final p = context.astra;
    return Container(
      decoration: BoxDecoration(
        color: p.card,
        borderRadius: BorderRadius.circular(18),
        border: Border.all(color: p.hairline),
        boxShadow: context.astraTheme.softShadow,
      ),
      child: Row(
        children: [
          Expanded(child: _whoSeg(Icons.person_outline, 'CLIENT', cart.customerName, _pickClient)),
          Container(width: 1, height: 46, color: p.hairline),
          Expanded(
            child: _whoSeg(
              Icons.brush,
              'STAFF',
              cart.stylistName.isEmpty ? 'Me' : cart.stylistName,
              _pickStylist,
              avatarUrl: _staffAvatarUrl(cart),
              avatarHeaders: context.read<AuthCubit>().config.assetHeaders,
            ),
          ),
        ],
      ),
    );
  }

  /// One half of the joined selector — circular avatar (staff photo when set),
  /// gold micro-label, value and a chevron.
  Widget _whoSeg(IconData icon, String label, String value, VoidCallback onTap,
      {String? avatarUrl, Map<String, String>? avatarHeaders}) {
    final p = context.astra;
    final fallback = Container(
      width: 38,
      height: 38,
      decoration: BoxDecoration(shape: BoxShape.circle, color: p.tint),
      child: Icon(icon, size: 17, color: p.primary),
    );
    final leading = (avatarUrl != null && avatarUrl.startsWith('http'))
        ? ClipOval(
            child: Image.network(
              avatarUrl,
              headers: avatarHeaders,
              width: 38,
              height: 38,
              fit: BoxFit.cover,
              gaplessPlayback: true,
              errorBuilder: (_, __, ___) => fallback,
            ),
          )
        : fallback;
    return GestureDetector(
      behavior: HitTestBehavior.opaque,
      onTap: () {
        HapticFeedback.selectionClick();
        onTap();
      },
      child: Padding(
        padding: const EdgeInsets.symmetric(horizontal: 13, vertical: 12),
        child: Row(
          children: [
            leading,
            const SizedBox(width: 11),
            Expanded(
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  Text(label, style: ui(size: 9.5, weight: FontWeight.w800, color: p.goldText, letterSpacing: 1.5)),
                  const SizedBox(height: 1),
                  Text(value,
                      maxLines: 1,
                      overflow: TextOverflow.ellipsis,
                      style: ui(size: 14, weight: FontWeight.w700, color: p.ink)),
                ],
              ),
            ),
            Icon(Icons.keyboard_arrow_down, color: p.textMuted, size: 16),
          ],
        ),
      ),
    );
  }

  /// Persistent live-cart panel for the tablet split layout.
  Widget _tabletCartPanel(CartCubit cart) {
    final p = context.astra;
    return Container(
      width: 380,
      margin: const EdgeInsets.fromLTRB(0, 14, 14, 14),
      padding: const EdgeInsets.all(14),
      decoration: BoxDecoration(
        color: p.sheet,
        borderRadius: BorderRadius.circular(24),
        border: Border.all(color: p.hairline),
        boxShadow: context.astraTheme.softShadow,
      ),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.stretch,
        children: [
          Row(
            children: [
              Icon(Icons.shopping_bag_outlined, size: 18, color: p.primary),
              const SizedBox(width: 9),
              Flexible(
                child: Text('Current ticket',
                    maxLines: 1, overflow: TextOverflow.ellipsis, style: serif(size: 18, color: p.ink)),
              ),
              const SizedBox(width: 8),
              const Spacer(),
              Text('${cart.count} ${cart.count == 1 ? 'item' : 'items'}',
                  style: ui(size: 11.5, weight: FontWeight.w700, color: p.textMuted)),
            ],
          ),
          const SizedBox(height: 12),
          Expanded(
            child: cart.isEmpty
                ? EmptyState(
                    icon: Icons.add_shopping_cart,
                    title: 'No items yet',
                    message: 'Tap a service to add it.',
                  )
                : ListView(
                    padding: EdgeInsets.zero,
                    children: [
                      for (final line in cart.lines) cartLineCard(context, line),
                      const SizedBox(height: 3),
                      OrderDiscountRow(cart: cart),
                    ],
                  ),
          ),
          if (!cart.isEmpty) ...[
            const SizedBox(height: 12),
            cartSummaryCard(context, cart, onCharge: () => context.push('/review')),
          ],
        ],
      ),
    );
  }

  Widget _body(CatalogCubit cat) {
    // Full-screen states only apply before anything has loaded; once products
    // exist we keep them on screen while a search/filter reloads in place.
    if (cat.loading && cat.isEmpty) {
      return const Center(child: CircularProgressIndicator());
    }
    if (cat.error != null && cat.isEmpty) {
      return EmptyState(
        icon: Icons.wifi_off,
        title: 'Couldn’t load services',
        message: cat.error,
        action: AstraButton(label: 'Retry', icon: Icons.refresh, expand: false, onTap: cat.load),
      );
    }
    return RefreshIndicator(
      onRefresh: cat.load,
      child: CustomScrollView(
        controller: _scrollCtl,
        keyboardDismissBehavior: ScrollViewKeyboardDismissBehavior.onDrag,
        slivers: [
          SliverToBoxAdapter(
            child: Padding(
              padding: const EdgeInsets.fromLTRB(16, 12, 16, 0),
              child: Column(
                children: [
                  _searchRow(cat),
                  const SizedBox(height: 16),
                  _typeFilterRow(cat),
                ],
              ),
            ),
          ),
          SliverToBoxAdapter(
            child: Padding(
              padding: const EdgeInsets.only(top: 14, bottom: 4),
              child: _categoryChips(cat),
            ),
          ),
          if (cat.isEmpty)
            SliverToBoxAdapter(
              child: Padding(
                padding: const EdgeInsets.only(top: 60),
                child: EmptyState(
                    icon: Icons.search_off,
                    title: 'No services found',
                    message: 'Try another search or category.'),
              ),
            )
          else if (_view == _ProductView.grid)
            _productGrid(cat)
          else
            _productList(cat),
          SliverToBoxAdapter(child: _footer(cat)),
        ],
      ),
    );
  }

  /// Two-up boutique tile grid (more columns on tablet/desktop — ≈200px each).
  /// Lazily built so long catalogs stay smooth as pages stream in.
  Widget _productGrid(CatalogCubit cat) => SliverPadding(
        padding: const EdgeInsets.fromLTRB(16, 4, 16, 16),
        sliver: SliverLayoutBuilder(
          builder: (context, constraints) {
            final cols = (constraints.crossAxisExtent / 200).floor().clamp(2, 5);
            return SliverGrid(
              gridDelegate: SliverGridDelegateWithFixedCrossAxisCount(
                crossAxisCount: cols,
                crossAxisSpacing: 13,
                mainAxisSpacing: 13,
                childAspectRatio: 0.72,
              ),
              delegate: SliverChildBuilderDelegate(
                (context, i) => _serviceTile(cat.products[i]),
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

  void _setView(_ProductView mode) {
    if (_view == mode) return;
    HapticFeedback.selectionClick();
    setState(() => _view = mode);
    serviceLocator<LocalStorageService>()
        .setSaleView(mode == _ProductView.list ? 'list' : 'grid');
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
            SizedBox(width: 84, height: 84, child: _tileImage(s)),
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

  /// Bottom of the list: a spinner while the next page loads, otherwise just
  /// breathing room so the floating cart bar never covers the last row.
  Widget _footer(CatalogCubit cat) {
    if (cat.loadingMore) {
      return const Padding(
        padding: EdgeInsets.symmetric(vertical: 22),
        child: Center(
          child: SizedBox(width: 22, height: 22, child: CircularProgressIndicator(strokeWidth: 2.4)),
        ),
      );
    }
    return const SizedBox(height: 120);
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
  Widget _serviceTile(Product s) {
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
            Expanded(child: _tileImage(s)),
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
  Widget _tileImage(Product s) {
    if (s.thumbnail.isEmpty) return _tileFallback(s);
    final cfg = context.read<AuthCubit>().config;
    return Image.network(
      cfg.assetUrl(s.thumbnail),
      headers: cfg.assetHeaders,
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

  String _metaLine(Product s) => [
        s.code,
        if (s.duration.isNotEmpty && s.duration != '0') '${s.duration} min',
      ].where((e) => e.isNotEmpty).join(' · ');

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

  Widget _cartBar(CartCubit cart) {
    final p = context.astra;
    return GestureDetector(
      onTap: () {
        HapticFeedback.selectionClick();
        context.push('/cart');
      },
      child: ClipRRect(
        borderRadius: BorderRadius.circular(22),
        child: BackdropFilter(
          filter: ImageFilter.blur(sigmaX: 18, sigmaY: 18),
          child: Container(
            margin: const EdgeInsets.symmetric(horizontal: 2),
            padding: const EdgeInsets.fromLTRB(13, 13, 15, 13),
            decoration: BoxDecoration(
              gradient: LinearGradient(colors: [
                p.primaryDark.withValues(alpha: 0.74),
                Color.lerp(p.primaryDark, Colors.black, 0.25)!.withValues(alpha: 0.8),
              ]),
              borderRadius: BorderRadius.circular(22),
              border: Border.all(color: p.accent.withValues(alpha: 0.3)),
              boxShadow: [BoxShadow(color: Colors.black.withValues(alpha: 0.3), blurRadius: 30, offset: const Offset(0, 14))],
            ),
            child: Row(
          children: [
            Stack(
              clipBehavior: Clip.none,
              children: [
                Container(
                  width: 42,
                  height: 42,
                  decoration: BoxDecoration(
                    color: Colors.white.withValues(alpha: 0.12),
                    borderRadius: BorderRadius.circular(13),
                  ),
                  child: Icon(Icons.shopping_bag_outlined, color: p.accent, size: 18),
                ),
                Positioned(
                  top: -6,
                  right: -6,
                  child: Container(
                    padding: const EdgeInsets.all(4),
                    constraints: const BoxConstraints(minWidth: 20, minHeight: 20),
                    decoration: BoxDecoration(gradient: p.accentGradient, shape: BoxShape.circle),
                    alignment: Alignment.center,
                    child: Text('${cart.count}',
                        style: ui(size: 10.5, weight: FontWeight.w800, color: p.primaryDark)),
                  ),
                ),
              ],
            ),
            const SizedBox(width: 13),
            Expanded(
              child: Column(
                // bottomNavigationBar gives a loose (full-screen) height; without
                // .min the column would expand and balloon the whole bar.
                mainAxisSize: MainAxisSize.min,
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  Text('${cart.count} ${cart.count == 1 ? 'service' : 'services'} · Total',
                      style: ui(size: 10.5, weight: FontWeight.w600, color: Colors.white70)),
                  Text(Money.of(cart.total), style: serif(size: 21, color: Colors.white)),
                ],
              ),
            ),
            Container(
              padding: const EdgeInsets.symmetric(horizontal: 18, vertical: 12),
              decoration: BoxDecoration(gradient: p.accentGradient, borderRadius: BorderRadius.circular(14)),
              child: Row(
                children: [
                  Text('View Cart', style: ui(size: 13.5, weight: FontWeight.w800, color: p.primaryDark)),
                  const SizedBox(width: 7),
                  Icon(Icons.arrow_forward, size: 15, color: p.primaryDark),
                ],
              ),
            ),
          ],
        ),
          ),
        ),
      ),
    );
  }

  Future<void> _pickStylist() async {
    final cart = context.read<CartCubit>();
    final chosen = await pickStylist(context, selectedId: cart.stylistId);
    if (chosen == null || !mounted) return;
    // Ticket-level stylist: applies to existing lines and becomes the default
    // for new ones. Per-line overrides can still be set in the edit sheet.
    cart.setStylist(chosen.id, chosen.name);
    // Remember this employee so the next new sale preselects them.
    serviceLocator<LocalStorageService>().setSaleStylist(chosen.id, chosen.name);
  }

  /// Absolute avatar URL for the ticket's selected staff (or null). Resolves the
  /// logged-in user ("Me") from AuthCubit and any other stylist from the loaded
  /// stylist list — the cart only stores the stylist id + name.
  String? _staffAvatarUrl(CartCubit cart) {
    final auth = context.read<AuthCubit>();
    final user = auth.user;
    final id = cart.stylistId;
    var raw = '';
    if (id == null || (user != null && int.tryParse(user.id) == id)) {
      raw = user?.photoUrl ?? '';
    } else {
      for (final e in context.read<StylistCubit>().all) {
        if (e.id == id) {
          raw = e.photoUrl;
          break;
        }
      }
    }
    return raw.isEmpty ? null : auth.config.assetUrl(raw);
  }

  Future<void> _pickClient() async {
    final cart = context.read<CartCubit>();
    final nameCtl = TextEditingController(text: cart.customerName == 'Walk-in' ? '' : cart.customerName);
    final mobileCtl = TextEditingController(text: cart.customerMobile == '9633155669' ? '' : cart.customerMobile);
    final p = context.astra;
    await showModalBottomSheet(
      context: context,
      isScrollControlled: true,
      backgroundColor: Colors.transparent,
      builder: (ctx) => Padding(
        padding: EdgeInsets.only(bottom: MediaQuery.of(ctx).viewInsets.bottom),
        child: Container(
          decoration: BoxDecoration(color: p.sheet, borderRadius: const BorderRadius.vertical(top: Radius.circular(30))),
          padding: const EdgeInsets.fromLTRB(20, 16, 20, 24),
          // Scrollable so the form never overflows when the keyboard is up on a
          // short phone or in landscape.
          child: SingleChildScrollView(
            child: Column(
            mainAxisSize: MainAxisSize.min,
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              const SectionLabel('Client'),
              const SizedBox(height: 4),
              Text('Client Details?', style: serif(size: 22, color: p.ink)),
              const SizedBox(height: 16),
              _sheetField(ctx, 'Name', nameCtl, hint: 'Walk-in'),
              const SizedBox(height: 12),
              _sheetField(ctx, 'Mobile', mobileCtl, hint: 'Optional', number: true),
              const SizedBox(height: 18),
              AstraButton(
                label: 'Set client',
                icon: Icons.check,
                onTap: () {
                  HapticFeedback.lightImpact();
                  cart.setClient(nameCtl.text.trim(), mobileCtl.text.trim());
                  Navigator.pop(ctx);
                },
              ),
            ],
            ),
          ),
        ),
      ),
    );
  }

  Widget _sheetField(BuildContext ctx, String label, TextEditingController c, {String? hint, bool number = false}) {
    final p = ctx.astra;
    return Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        Text(label.toUpperCase(), style: ui(size: 10, weight: FontWeight.w800, color: p.textMuted, letterSpacing: 0.8)),
        const SizedBox(height: 6),
        TextField(
          controller: c,
          keyboardType: number ? TextInputType.phone : TextInputType.text,
          style: ui(size: 14, weight: FontWeight.w600, color: p.ink),
          decoration: InputDecoration(
            hintText: hint,
            filled: true,
            fillColor: p.card,
            contentPadding: const EdgeInsets.symmetric(horizontal: 14, vertical: 14),
            border: OutlineInputBorder(borderRadius: BorderRadius.circular(14), borderSide: BorderSide.none),
          ),
        ),
      ],
    );
  }

  Future<void> _scanBarcode(CatalogCubit cat) async {
    // Gate on camera access first (re-prompts, or routes to Settings).
    if (!await ensureCameraPermission(context)) return;
    if (!mounted) return;
    // Open the shared continuous scanner: it stays open and adds each scanned
    // product straight to the cart. The cart list updates live underneath.
    final cart = context.read<CartCubit>();
    await ContinuousScannerScreen.open(
      context,
      title: 'ADD TO SALE',
      tallyLabel: 'ADDED THIS SESSION',
      emptyHint: 'Point the camera at a product barcode to add it to the sale.',
      onScan: (code) async {
        final product = await cat.findByBarcode(code);
        if (product == null) return ScanFeedback.error(code, 'No product for this code');
        cart.add(product);
        final qty = cart.defaultQty;
        return ScanFeedback(
          title: product.name,
          detail: 'Added ${qtyLabel(qty)}${product.code.isEmpty ? '' : ' · ${product.code}'}',
          undo: () async {
            final matches = cart.lines.where((l) => l.productId == product.id);
            if (matches.isEmpty) return null;
            cart.changeQty(matches.first, -qty);
            return ScanFeedback(title: product.name, detail: 'Removed from sale');
          },
        );
      },
    );
  }
}
