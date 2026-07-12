import 'dart:ui' show ImageFilter;

import 'package:flutter/material.dart';
import 'package:flutter/services.dart';
import 'package:go_router/go_router.dart';
import 'package:provider/provider.dart';

import 'package:invo/shared/domain/helpers/formatters.dart';
import 'package:invo/shared/domain/helpers/icons.dart';
import 'package:invo/shared/domain/helpers/responsive.dart';
import 'package:invo/shared/domain/models/index.dart';
import 'package:invo/features/auth/logic/auth_cubit/auth_cubit.dart';
import 'package:invo/features/sale/logic/cart_cubit/cart_cubit.dart';
import 'package:invo/features/sale/logic/catalog_cubit/catalog_cubit.dart';
import 'package:invo/shared/utils/camera_permission.dart';
import 'package:invo/shared/utils/components/theme/index.dart';
import 'package:invo/shared/widgets/astra_widgets.dart';
import 'package:invo/shared/widgets/continuous_scanner_screen.dart';
import 'package:invo/features/sale/widgets/v3/cart_widgets.dart';
import 'package:invo/features/sale/widgets/v3/stylist_sheet.dart';

class NewSaleScreen extends StatefulWidget {
  const NewSaleScreen({super.key});
  @override
  State<NewSaleScreen> createState() => _NewSaleScreenState();
}

class _NewSaleScreenState extends State<NewSaleScreen> {
  final _searchCtl = TextEditingController();
  final _scrollCtl = ScrollController();

  @override
  void initState() {
    super.initState();
    _scrollCtl.addListener(_onScroll);
    WidgetsBinding.instance.addPostFrameCallback((_) {
      final cat = context.read<CatalogCubit>();
      cat.loadIfNeeded();
      // Default stylist = logged-in user.
      final user = context.read<AuthCubit>().user;
      final cart = context.read<CartCubit>();
      // Pull the latest sale settings (Settings → Sale Configuration) so the
      // default quantity and tip availability reflect the current web setting.
      cart.syncSaleSettings();
      if (user != null && cart.stylistName.isEmpty) {
        cart.setStylist(int.tryParse(user.id), user.name);
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
  /// screen — pop back if New Sale was pushed, otherwise fall back to the Home
  /// shell (dashboard) so the screen is never a dead-end.
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
    if (context.canPop()) {
      context.pop();
    } else {
      context.go('/home');
    }
  }

  @override
  Widget build(BuildContext context) {
    final cat = context.watch<CatalogCubit>();
    final cart = context.watch<CartCubit>();
    final tablet = context.isTablet;

    return Scaffold(
      body: AstraBackground(
        child: Column(
          children: [
            EmeraldHeader(
              leading: HeaderIconButton(icon: Icons.chevron_left, onTap: _close),
              titleWidget: Row(
                children: [
                  Expanded(child: Text(cart.isEditing ? 'Edit Sale' : 'New Sale', style: serif(size: 23, color: Colors.white))),
                  HeaderIconButton(icon: Icons.close, onTap: _close),
                ],
              ),
              bottom: Row(
                children: [
                  Expanded(child: _selector(Icons.person_outline, 'CLIENT', cart.customerName, _pickClient)),
                  const SizedBox(width: 9),
                  Expanded(child: _selector(Icons.brush, 'STAFF', cart.stylistName.isEmpty ? 'Me' : cart.stylistName, _pickStylist)),
                ],
              ),
            ),
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
    final tablet = context.isTablet;
    return RefreshIndicator(
      onRefresh: cat.load,
      child: CustomScrollView(
        controller: _scrollCtl,
        keyboardDismissBehavior: ScrollViewKeyboardDismissBehavior.onDrag,
        slivers: [
          SliverToBoxAdapter(
            child: Padding(
              padding: const EdgeInsets.fromLTRB(16, 14, 16, 14),
              child: Column(
                children: [
                  _searchRow(cat),
                  const SizedBox(height: 13),
                  _categoryChips(cat),
                ],
              ),
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
          else if (tablet)
            _gridSliver(cat)
          else
            _listSliver(cat),
          SliverToBoxAdapter(child: _footer(cat)),
        ],
      ),
    );
  }

  /// Phone: single-column paginated list.
  Widget _listSliver(CatalogCubit cat) => SliverPadding(
        padding: const EdgeInsets.symmetric(horizontal: 16),
        sliver: SliverList(
          delegate: SliverChildBuilderDelegate(
            (context, i) => Padding(
              padding: const EdgeInsets.only(bottom: 8),
              child: _serviceRow(cat.products[i]),
            ),
            childCount: cat.products.length,
          ),
        ),
      );

  /// Tablet/desktop: multi-column paginated grid (lazily built). Column count
  /// tracks the available width (≈340px per tile) so each card stays roomy.
  Widget _gridSliver(CatalogCubit cat) => SliverPadding(
        padding: const EdgeInsets.symmetric(horizontal: 16),
        sliver: SliverLayoutBuilder(
          builder: (context, constraints) {
            final cols = (constraints.crossAxisExtent / 340).floor().clamp(2, 5);
            return SliverGrid(
              gridDelegate: SliverGridDelegateWithFixedCrossAxisCount(
                crossAxisCount: cols,
                mainAxisExtent: 74,
                crossAxisSpacing: 12,
                mainAxisSpacing: 12,
              ),
              delegate: SliverChildBuilderDelegate(
                (context, i) => _serviceRow(cat.products[i]),
                childCount: cat.products.length,
              ),
            );
          },
        ),
      );

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

  Widget _searchRow(CatalogCubit cat) {
    final p = context.astra;
    final t = context.astraTheme;
    return Row(
      children: [
        Expanded(
          child: Container(
            decoration: BoxDecoration(
              color: p.card,
              borderRadius: BorderRadius.circular(14),
              boxShadow: t.softShadow,
            ),
            child: TextField(
              controller: _searchCtl,
              onChanged: cat.setSearch,
              style: ui(size: 13, weight: FontWeight.w600, color: p.ink),
              decoration: InputDecoration(
                isDense: true,
                hintText: 'Search name or code',
                hintStyle: ui(size: 12.5, weight: FontWeight.w500, color: p.textMuted),
                prefixIcon: Icon(Icons.search, color: p.textMuted, size: 20),
                border: InputBorder.none,
                contentPadding: const EdgeInsets.symmetric(vertical: 13),
              ),
            ),
          ),
        ),
        const SizedBox(width: 9),
        GestureDetector(
          onTap: () {
            HapticFeedback.selectionClick();
            _scanBarcode(cat);
          },
          child: Container(
            width: 46,
            height: 46,
            decoration: BoxDecoration(
              gradient: p.primaryGradient,
              borderRadius: BorderRadius.circular(14),
              boxShadow: t.floatShadow(p.primary),
            ),
            child: const Icon(Icons.qr_code_scanner, color: Colors.white, size: 20),
          ),
        ),
      ],
    );
  }

  Widget _categoryChips(CatalogCubit cat) {
    return SizedBox(
      height: 38,
      child: ListView(
        scrollDirection: Axis.horizontal,
        children: [
          AstraChip(
            label: 'All',
            icon: Icons.grid_view,
            active: cat.selectedCategoryId == null,
            onTap: () {
              HapticFeedback.selectionClick();
              cat.selectCategory(null);
            },
          ),
          for (final c in cat.categories) ...[
            const SizedBox(width: 8),
            AstraChip(
              label: c.name,
              active: cat.selectedCategoryId == c.id,
              onTap: () {
                HapticFeedback.selectionClick();
                cat.selectCategory(c.id);
              },
            ),
          ],
        ],
      ),
    );
  }

  Widget _serviceRow(Product s) {
    final p = context.astra;
    final cart = context.read<CartCubit>();
    return AstraCard(
      radius: 15,
      padding: const EdgeInsets.all(11),
      onTap: () {
        HapticFeedback.lightImpact();
        cart.add(s);
      },
      child: Row(
        children: [
          ProductThumb(url: s.thumbnail, fallbackIcon: iconForName('${s.categoryName} ${s.name}')),
          const SizedBox(width: 11),
          Expanded(
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Text(s.name,
                    maxLines: 1,
                    overflow: TextOverflow.ellipsis,
                    style: ui(size: 12.5, weight: FontWeight.w700, color: p.ink)),
                const SizedBox(height: 2),
                Text(
                  [s.code, if (s.duration.isNotEmpty && s.duration != '0') '${s.duration} min']
                      .where((e) => e.isNotEmpty)
                      .join(' · '),
                  style: ui(size: 10.5, weight: FontWeight.w600, color: p.textMuted),
                ),
              ],
            ),
          ),
          const SizedBox(width: 8),
          Text(Money.of(s.mrp), style: serif(size: 15, color: p.ink)),
          const SizedBox(width: 10),
          Container(
            width: 30,
            height: 30,
            decoration: BoxDecoration(gradient: p.primaryGradient, borderRadius: BorderRadius.circular(10)),
            child: const Icon(Icons.add, color: Colors.white, size: 16),
          ),
        ],
      ),
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

  Widget _selector(IconData icon, String label, String value, VoidCallback? onTap) {
    return GestureDetector(
      onTap: onTap == null
          ? null
          : () {
              HapticFeedback.selectionClick();
              onTap();
            },
      child: Container(
        padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 9),
        decoration: BoxDecoration(
          color: Colors.white.withValues(alpha: 0.13),
          borderRadius: BorderRadius.circular(13),
          border: Border.all(color: Colors.white.withValues(alpha: 0.18)),
        ),
        child: Row(
          children: [
            Container(
              width: 26,
              height: 26,
              decoration: BoxDecoration(
                color: Colors.white.withValues(alpha: 0.16),
                borderRadius: BorderRadius.circular(8),
              ),
              child: Icon(icon, size: 13, color: context.astra.accent),
            ),
            const SizedBox(width: 9),
            Expanded(
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  Text(label, style: ui(size: 9, weight: FontWeight.w700, color: Colors.white70, letterSpacing: 0.6)),
                  Text(value, maxLines: 1, overflow: TextOverflow.ellipsis,
                      style: ui(size: 12, weight: FontWeight.w700, color: Colors.white)),
                ],
              ),
            ),
            if (onTap != null) const Icon(Icons.keyboard_arrow_down, color: Colors.white70, size: 16),
          ],
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
