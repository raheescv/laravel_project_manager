import 'dart:async';

import 'dart:ui' show ImageFilter;

import 'package:flutter/material.dart';
import 'package:flutter/services.dart';
import 'package:go_router/go_router.dart';
import 'package:flutter_bloc/flutter_bloc.dart';

import 'package:invo/shared/domain/constants/global_variables.dart';
import 'package:invo/shared/domain/constants/mobile_permissions.dart';
import 'package:invo/shared/domain/helpers/formatters.dart';
import 'package:invo/shared/domain/helpers/icons.dart';
import 'package:invo/shared/domain/helpers/responsive.dart';
import 'package:invo/shared/domain/models/index.dart';
import 'package:invo/shared/domain/repository/catalog_snapshot_repository.dart';
import 'package:invo/features/auth/logic/auth_cubit/auth_cubit.dart';
import 'package:invo/features/sale/logic/cart_cubit/cart_cubit.dart';
import 'package:invo/features/sale/logic/catalog_cubit/catalog_cubit.dart';
import 'package:invo/features/sale/logic/stylist_cubit/stylist_cubit.dart';
import 'package:invo/features/settings/logic/pos_settings_cubit/pos_settings_cubit.dart';
import 'package:invo/shared/utils/camera_permission.dart';
import 'package:invo/shared/utils/local_storage/local_storage_service.dart';
import 'package:invo/shared/utils/components/app_strings.dart';
import 'package:invo/shared/utils/components/theme/index.dart';
import 'package:invo/shared/utils/router/routes.dart';
import 'package:invo/shared/widgets/astra_widgets.dart';
import 'package:invo/shared/widgets/continuous_scanner_screen.dart';
import 'package:invo/shared/widgets/offline_image.dart';
import 'package:invo/features/sale/screens/v3/pending_sales_screen.dart';
import 'package:invo/features/sale/widgets/v3/cart_widgets.dart';
import 'package:invo/features/sale/widgets/v3/stylist_sheet.dart';
import 'package:invo/shared/widgets/astra_snack.dart';

part 'new_sale_catalog_views.dart';

/// Mobile number the backend's default walk-in customer carries. Treated as
/// "no number given" in the client form so the cashier isn't editing a
/// placeholder — see [_NewSaleScreenState._pickClient].
///
/// TODO: this is tenant data, not code. It belongs in the sale settings the app
/// already syncs from the web (`/settings/sale`), or the API should return an
/// empty mobile for walk-in sales.
const String _walkInPlaceholderMobile = '9633155669';

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
      // An employee who may only assign themselves (see StylistCubit.all) is
      // always themselves — a colleague remembered on this device (a shared
      // till) must not carry over.
      if (cart.stylistName.isEmpty) {
        final storage = serviceLocator<LocalStorageService>();
        final savedId = storage.saleStylistId;
        final savedName = storage.saleStylistName;
        final selfOnly = user?.isNonAdminEmployee ?? false;
        if (!selfOnly && savedId != null && savedName != null && savedName.isNotEmpty) {
          cart.setStylist(savedId, savedName);
        } else if (user != null) {
          cart.setStylist(int.tryParse(user.id), user.name);
        }
      }
      // Prompt for the client up front so the ticket starts with who's in the
      // chair. Only on a fresh ticket (still the default Walk-in, no items), and
      // only where the till asked for it — Settings → New Sale. A counter
      // serving a queue dismisses this form on every sale.
      if (context.read<PosSettingsCubit>().askClientOnNewSale &&
          cart.customerName == AppStrings.walkInCustomer &&
          cart.isEmpty) {
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

  /// Add [product] to the ticket, warning when the cached catalog says there is
  /// none left.
  ///
  /// Only a warning, never a block. Offline the figure is as of the last
  /// snapshot less whatever queued sales have taken, so it is an estimate — and
  /// refusing a sale on an estimate, with the customer standing there, is worse
  /// than the server refusing it later. Services have no stock to check.
  void _addToCart(Product product) {
    final cart = context.read<CartCubit>();
    final cat = context.read<CatalogCubit>();
    final onTicket = cart.lines
        .where((l) => l.productId == product.id)
        .fold<double>(0, (sum, l) => sum + l.qty);
    cart.add(product);
    if (!cat.state.servingCached || product.isService) return;
    if (product.totalStock > onTicket) return;
    AstraSnack.show(context, '${product.name} may be out of stock — the catalog is offline');
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
    unawaited(HapticFeedback.selectionClick());
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
      context.go(canViewAdmin ? Routes.home : Routes.sale);
    }
  }

  @override
  Widget build(BuildContext context) {
    final tablet = context.isTablet;
    final p = context.astra;
    // Flat "Clean Boutique" surface — a calm near-white (the preset canvas
    // lifted toward white in light, canvas as-is in dark) rather than the
    // app-wide aurora, so the product photos read on a clean ground.
    final surface = p.isDark ? p.canvas : Color.lerp(p.canvas, Colors.white, 0.5)!;

    // Scoped rather than one `context.watch` per cubit at the top: paging the
    // catalog appends 20 products and would otherwise rebuild the cart header
    // and bar, and editing the ticket would rebuild the whole product grid.
    Widget cartPart(Widget Function(CartCubit) build) =>
        BlocBuilder<CartCubit, CartState>(builder: (context, _) => build(context.read<CartCubit>()));

    // Tablet: two panes that each carry their own head, and no band across the
    // top of the window. The phone header stretched over ~1200pt put the title
    // and the client selector in a sea of empty space and pushed the first row
    // of products past halfway down the screen, so on a tablet the catalog
    // column keeps only the controls that filter it and everything about the
    // ticket — title, sale day, client, staff, lines, total — moves into the
    // rail on the right, where it is all one subject.
    if (tablet) {
      return Scaffold(
        backgroundColor: surface,
        body: SafeArea(
          top: false,
          bottom: false,
          child: Row(
            crossAxisAlignment: CrossAxisAlignment.stretch,
            children: [
              Expanded(
                child: BlocBuilder<CatalogCubit, CatalogState>(
                  builder: (context, _) {
                    final cat = context.read<CatalogCubit>();
                    return Column(
                      children: [
                        _tabletToolbar(cat),
                        const Padding(
                          padding: EdgeInsets.fromLTRB(14, 0, 14, 2),
                          child: _CachedCatalogNotice(),
                        ),
                        _pinnedCategories(cat, tablet: true),
                        Expanded(child: _body(cat, tablet: true)),
                      ],
                    );
                  },
                ),
              ),
              cartPart(_tabletCartPanel),
            ],
          ),
        ),
      );
    }

    return Scaffold(
      backgroundColor: surface,
      body: Column(
        children: [
          cartPart(_header),
          Expanded(
            child: BlocBuilder<CatalogCubit, CatalogState>(
              builder: (context, _) {
                final cat = context.read<CatalogCubit>();
                return Column(
                  children: [
                    _phoneToolbar(cat),
                    Expanded(child: _body(cat)),
                  ],
                );
              },
            ),
          ),
        ],
      ),
      // The phone keeps its floating cart bar; the tablet rail is always there.
      bottomNavigationBar: cartPart((cart) => cart.isEmpty
          ? const SizedBox.shrink()
          : SafeArea(
              child: Padding(
                padding: const EdgeInsets.fromLTRB(14, 0, 14, 12),
                child: _cartBar(cart),
              ),
            )),
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

  /// Title, sale day and the two ghost buttons on one line, then the joined
  /// Client / Staff selector.
  ///
  /// The title used to be centred with the day pill on a line of its own, which
  /// spent a whole row on a pill and left the space either side of the title
  /// empty. Left-aligning it puts both on one line and gives the catalog the
  /// row back — the same move the tablet rail makes.
  Widget _header(CartCubit cart) {
    final p = context.astra;
    return SafeArea(
      bottom: false,
      child: Padding(
        padding: const EdgeInsets.fromLTRB(11, 6, 14, 8),
        child: Column(
          children: [
            Row(
              children: [
                _ghostBtn(Icons.chevron_left, _close, size: 38),
                Text(cart.isEditing ? 'Edit Sale' : 'New Sale', style: serif(size: 21, color: p.ink)),
                const SizedBox(width: 9),
                const Expanded(
                  child: FittedBox(
                    fit: BoxFit.scaleDown,
                    alignment: Alignment.centerLeft,
                    child: _SessionDateLine(compact: true),
                  ),
                ),
                const SizedBox(width: 6),
                const PendingSalesBadge(),
                const SizedBox(width: 2),
                _ghostBtn(Icons.close, _close, size: 38),
              ],
            ),
            const SizedBox(height: 11),
            _whoRow(cart),
            const _CachedCatalogNotice(),
          ],
        ),
      ),
    );
  }

  /// Borderless header icon button (back / close).
  Widget _ghostBtn(IconData icon, VoidCallback onTap, {double size = 42}) {
    final p = context.astra;
    return GestureDetector(
      behavior: HitTestBehavior.opaque,
      onTap: () {
        HapticFeedback.selectionClick();
        onTap();
      },
      child: SizedBox(
        width: size,
        height: size,
        child: Icon(icon, size: size * 0.55, color: p.textSecondary),
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
            child: Image(
              image: OfflineImage.provider(
                avatarUrl,
                headers: avatarHeaders,
                cacheWidth: decodeWidthFor(context, 38),
              ),
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

  /// The catalog column's own chrome: search, scanner, type filter and the
  /// grid/list switch, on one line when there is room for it.
  ///
  /// This is all the tablet keeps at the top of the catalog — everything else
  /// the phone header carries belongs to the ticket, and lives in the rail.
  Widget _tabletToolbar(CatalogCubit cat) => Padding(
        padding: EdgeInsets.fromLTRB(14, 8 + MediaQuery.viewPaddingOf(context).top, 14, 4),
        child: LayoutBuilder(
          builder: (context, c) {
            // Under ~660pt (a small tablet in portrait, beside the rail) the
            // four controls can't share a line without the search field
            // shrinking to a slot, so the filters drop to their own row.
            if (c.maxWidth < 660) {
              return Column(
                children: [
                  _searchRow(cat, height: 46),
                  const SizedBox(height: 10),
                  _typeFilterRow(cat),
                ],
              );
            }
            return Row(
              children: [
                Expanded(child: _searchRow(cat, height: 46)),
                const SizedBox(width: 10),
                _typeSegmented(cat, compact: true),
                const SizedBox(width: 8),
                _viewToggle(),
              ],
            );
          },
        ),
      );

  /// Client and staff stacked in one card — the rail is too narrow for the
  /// phone's side-by-side pair, where each half would ellipsis away the name it
  /// exists to show.
  Widget _whoStack(CartCubit cart) {
    final p = context.astra;
    return Container(
      decoration: BoxDecoration(
        color: p.card,
        borderRadius: BorderRadius.circular(16),
        border: Border.all(color: p.hairline),
        boxShadow: context.astraTheme.softShadow,
      ),
      child: Column(
        children: [
          _whoSeg(Icons.person_outline, 'CLIENT', cart.customerName, _pickClient),
          Container(height: 1, color: p.hairline),
          _whoSeg(
            Icons.brush,
            'STAFF',
            cart.stylistName.isEmpty ? 'Me' : cart.stylistName,
            _pickStylist,
            avatarUrl: _staffAvatarUrl(cart),
            avatarHeaders: context.read<AuthCubit>().config.assetHeaders,
          ),
        ],
      ),
    );
  }

  /// The ticket rail — the whole sale in one column: what screen this is, which
  /// day it books into, who it is for, what is on it and what it comes to.
  ///
  /// Width tracks the window so a 13" landscape gives the lines room to breathe
  /// without a small tablet in portrait handing the rail half its screen.
  Widget _tabletCartPanel(CartCubit cart) {
    final p = context.astra;
    final railW = (MediaQuery.sizeOf(context).width * 0.32).clamp(320.0, 430.0);
    return Container(
      width: railW,
      // Docked, not floating: the rail runs to the right and bottom edges of the
      // window so the total is at the foot of the screen instead of above a band
      // of empty canvas. Only the left corners are rounded — that edge is the
      // one that meets the catalog.
      padding: EdgeInsets.fromLTRB(
        14,
        10 + MediaQuery.viewPaddingOf(context).top,
        14,
        6 + MediaQuery.viewPaddingOf(context).bottom,
      ),
      decoration: BoxDecoration(
        color: p.sheet,
        borderRadius: const BorderRadius.horizontal(left: Radius.circular(26)),
        border: Border(left: BorderSide(color: p.hairline)),
      ),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.stretch,
        children: [
          Row(
            children: [
              Text(cart.isEditing ? 'Edit Sale' : 'New Sale',
                  maxLines: 1, overflow: TextOverflow.ellipsis, style: serif(size: 21, color: p.ink)),
              const SizedBox(width: 10),
              const Expanded(
                child: FittedBox(
                  fit: BoxFit.scaleDown,
                  alignment: Alignment.centerLeft,
                  child: _SessionDateLine(compact: true),
                ),
              ),
              const SizedBox(width: 6),
              const PendingSalesBadge(),
              const SizedBox(width: 2),
              _ghostBtn(Icons.close, _close, size: 34),
            ],
          ),
          const SizedBox(height: 12),
          _whoStack(cart),
          const SizedBox(height: 14),
          Row(
            children: [
              Icon(Icons.shopping_bag_outlined, size: 16, color: p.primary),
              const SizedBox(width: 8),
              Expanded(
                child: Text('Current ticket',
                    maxLines: 1,
                    overflow: TextOverflow.ellipsis,
                    style: ui(size: 12, weight: FontWeight.w800, color: p.ink, letterSpacing: 0.5)),
              ),
              Text('${cart.count} ${cart.count == 1 ? 'item' : 'items'}',
                  style: ui(size: 11.5, weight: FontWeight.w700, color: p.textMuted)),
            ],
          ),
          const SizedBox(height: 10),
          Expanded(
            child: cart.isEmpty
                ? EmptyState(
                    icon: Icons.add_shopping_cart,
                    title: 'No items yet',
                    message: 'Tap a service to add it.',
                  )
                : ListView(
                    padding: EdgeInsets.zero,
                    children: [for (final line in cart.lines) cartLineCard(context, line)],
                  ),
          ),
          // The discount and the total are pinned to the foot of the rail rather
          // than riding the end of the line list: on the phone you scroll to the
          // bottom to pay, but here the total is always on screen, and a discount
          // parked below the fold is a discount nobody applies. It also closes
          // the gap that was left under the Charge card.
          if (!cart.isEmpty) ...[
            const SizedBox(height: 2),
            OrderDiscountRow(cart: cart),
            const SizedBox(height: 10),
            cartSummaryCard(context, cart, onCharge: () => context.push(Routes.review)),
          ],
        ],
      ),
    );
  }

  /// The phone's catalog head — search, type filter and the category rail —
  /// held above the scroll view rather than riding the top of it. They used to
  /// be the first slivers in the list, so a couple of rows in they were gone
  /// and switching category or typing a code cost a scroll back to the top.
  ///
  /// The tablet keeps its own toolbar and only pins the rail; both end at the
  /// same hairline.
  Widget _phoneToolbar(CatalogCubit cat) => Column(
        children: [
          Padding(
            padding: const EdgeInsets.fromLTRB(16, 12, 16, 0),
            child: Column(
              children: [
                _searchRow(cat),
                const SizedBox(height: 16),
                _typeFilterRow(cat),
              ],
            ),
          ),
          _pinnedCategories(cat),
        ],
      );

  /// Category rail plus the hairline that marks where the fixed head stops and
  /// the products start scrolling under it.
  Widget _pinnedCategories(CatalogCubit cat, {bool tablet = false}) => Column(
        children: [
          Padding(
            padding: EdgeInsets.only(top: tablet ? 6 : 14, bottom: 10),
            child: _categoryChips(cat, tablet: tablet),
          ),
          Container(height: 1, color: context.astra.hairline),
        ],
      );

  Widget _body(CatalogCubit cat, {bool tablet = false}) {
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
            // Watched, so changing the density in Settings reflows the catalog
            // without the till having to leave and come back.
            _productGrid(cat, context.watch<PosSettingsCubit>().gridColumns, tablet: tablet)
          else
            _productList(cat, tablet: tablet),
          SliverToBoxAdapter(child: _footer(cat, tablet: tablet)),
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

  /// Bottom of the list: a spinner while the next page loads, otherwise just
  /// breathing room so the floating cart bar never covers the last row.
  Widget _footer(CatalogCubit cat, {bool tablet = false}) {
    if (cat.loadingMore) {
      return const Padding(
        padding: EdgeInsets.symmetric(vertical: 22),
        child: Center(
          child: SizedBox(width: 22, height: 22, child: CircularProgressIndicator(strokeWidth: 2.4)),
        ),
      );
    }
    // Room for the phone's floating cart bar; on a tablet the rail sits beside
    // the grid and covers nothing, so the last row only needs breathing room —
    // plus whatever the home indicator takes, since the column runs to the edge.
    return SizedBox(height: tablet ? 24 + MediaQuery.viewPaddingOf(context).bottom : 120);
  }

  String _metaLine(Product s) => [
        s.code,
        if (s.duration.isNotEmpty && s.duration != '0') '${s.duration} min',
      ].where((e) => e.isNotEmpty).join(' · ');

  Widget _cartBar(CartCubit cart) {
    final p = context.astra;
    return GestureDetector(
      onTap: () {
        HapticFeedback.selectionClick();
        context.push(Routes.cart);
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
    unawaited(serviceLocator<LocalStorageService>().setSaleStylist(chosen.id, chosen.name));
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
    // An unnamed ticket carries the walk-in name and, from the server's default
    // customer record, the placeholder mobile. Blank both so the cashier types
    // into empty fields — but only together: a real customer who happens to have
    // the placeholder number must keep it rather than have it silently wiped.
    final isWalkIn = cart.customerName == AppStrings.walkInCustomer;
    final nameCtl = TextEditingController(text: isWalkIn ? '' : cart.customerName);
    final mobileCtl = TextEditingController(
        text: isWalkIn && cart.customerMobile == _walkInPlaceholderMobile
            ? ''
            : cart.customerMobile);
    final p = context.astra;
    try {
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
              _sheetField(ctx, 'Name', nameCtl, hint: AppStrings.walkInCustomer),
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
    } finally {
      // Owned by this method, not by a State — release them when the sheet closes.
      nameCtl.dispose();
      mobileCtl.dispose();
    }
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
        final onTicket = cart.lines
            .where((l) => l.productId == product.id)
            .fold<double>(0, (sum, l) => sum + l.qty);
        cart.add(product);
        final qty = cart.defaultQty;
        // The scanner owns the whole screen, so the low-stock warning rides on
        // its own feedback line rather than a snackbar nobody would see.
        final lowStock = cat.state.servingCached &&
            !product.isService &&
            product.totalStock <= onTicket;
        return ScanFeedback(
          title: product.name,
          detail: lowStock
              ? 'Added ${qtyLabel(qty)} · may be out of stock'
              : 'Added ${qtyLabel(qty)}${product.code.isEmpty ? '' : ' · ${product.code}'}',
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

/// Says out loud that the grid is showing a stored copy of the catalog rather
/// than live data, and how old it is.
///
/// Prices and stock move on the web; a cashier selling from a snapshot is
/// selling from figures that were true at [CatalogState.cachedAt] and no later,
/// which is exactly the sort of thing that has to be on screen rather than in a
/// release note.
/// The sale day this ticket lands on, as a quiet pill under the title: the
/// business date the cashier is selling into, and whether a day is open at all.
///
/// Reads [AuthCubit] rather than [DaySessionCubit] — the signed-in user carries
/// the branch's session date, and [AuthCubit.syncDaySession] refreshes it the
/// moment the day is opened or closed, so this line follows without the POS
/// having to own a day-session cubit.
///
/// While the day is closed the cached date is only "today as of the last
/// sign-in", so the device's own date is shown instead; an open session keeps
/// its real date, which is the case worth seeing (a day opened yesterday and
/// never closed still books today's sales under yesterday).
class _SessionDateLine extends StatelessWidget {
  const _SessionDateLine({this.compact = false});

  /// Inline beside the screen title (the tablet rail), where the word SESSION
  /// only restates what the title already says. A closed day still shouts —
  /// that one is the whole reason the line is on screen.
  final bool compact;

  @override
  Widget build(BuildContext context) {
    return BlocSelector<AuthCubit, AuthState, ApiUser?>(
      selector: (state) => state.user,
      builder: (context, user) {
        final p = context.astra;
        final open = user?.dayOpen ?? false;
        final date = open ? DateTime.tryParse(user?.daySessionDate ?? '') : DateTime.now();
        if (date == null) return const SizedBox.shrink();
        final accent = open ? AstraPalette.success : AstraPalette.danger;
        final showLabel = !(compact && open);
        return Padding(
          padding: EdgeInsets.only(top: compact ? 0 : 2),
          child: Container(
            padding: const EdgeInsets.fromLTRB(9, 5, 11, 5),
            decoration: BoxDecoration(
              color: open ? p.tint : accent.withValues(alpha: 0.12),
              borderRadius: BorderRadius.circular(999),
            ),
            child: Row(
              mainAxisSize: MainAxisSize.min,
              children: [
                Container(
                  width: 6,
                  height: 6,
                  decoration: BoxDecoration(color: accent, shape: BoxShape.circle),
                ),
                const SizedBox(width: 7),
                if (showLabel) ...[
                  Text(open ? 'SESSION' : 'DAY CLOSED',
                      style: ui(
                          size: 9.5,
                          weight: FontWeight.w800,
                          color: open ? p.goldText : accent,
                          letterSpacing: 1.2)),
                  const SizedBox(width: 7),
                ],
                Text(Dates.weekday(date),
                    style: ui(size: 11.5, weight: FontWeight.w700, color: p.ink)),
              ],
            ),
          ),
        );
      },
    );
  }
}

class _CachedCatalogNotice extends StatelessWidget {
  const _CachedCatalogNotice();

  @override
  Widget build(BuildContext context) {
    return BlocBuilder<CatalogCubit, CatalogState>(
      buildWhen: (a, b) => a.servingCached != b.servingCached || a.cachedAt != b.cachedAt,
      builder: (context, state) {
        if (!state.servingCached) return const SizedBox.shrink();
        final p = context.astra;
        final freshness = CatalogFreshness.of(
            state.cachedAt == null ? null : DateTime.now().difference(state.cachedAt!));
        // Past the staleness thresholds the notice stops being a quiet aside and
        // takes the warning colour, because by then the figures in the grid — not
        // the connection — are the thing to be careful about. It never stops the
        // cashier selling; see [CatalogFreshness].
        final stale = freshness == CatalogFreshness.stale;
        final colour = stale ? const Color(0xFFB4441F) : p.goldText;
        return Padding(
          padding: const EdgeInsets.only(top: 10),
          child: Container(
            width: double.infinity,
            padding: const EdgeInsets.symmetric(horizontal: 11, vertical: 8),
            decoration: BoxDecoration(
              color: stale ? colour.withValues(alpha: 0.12) : p.tint,
              borderRadius: BorderRadius.circular(11),
            ),
            child: Row(
              children: [
                Icon(stale ? Icons.warning_amber_rounded : Icons.cloud_off_outlined,
                    size: 14, color: colour),
                const SizedBox(width: 7),
                Expanded(
                  child: Text(
                    freshness.needsSaying
                        ? 'Catalog is ${catalogAgeLabel(state.cachedAt)} — verify prices'
                        : 'Offline · catalog from ${catalogAgeLabel(state.cachedAt)}',
                    style: ui(size: 11, weight: FontWeight.w700, color: colour),
                  ),
                ),
              ],
            ),
          ),
        );
      },
    );
  }
}
