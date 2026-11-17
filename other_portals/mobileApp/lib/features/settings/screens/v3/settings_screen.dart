import 'package:flutter/material.dart';
import 'package:flutter_bloc/flutter_bloc.dart';
import 'package:go_router/go_router.dart';

import 'package:invo/features/sale/logic/cart_cubit/cart_cubit.dart';
import 'package:invo/features/sale/logic/offline_sync_cubit/offline_sync_cubit.dart';
import 'package:invo/shared/domain/constants/global_variables.dart';
import 'package:invo/shared/domain/constants/mobile_permissions.dart';
import 'package:invo/shared/domain/models/product_sort.dart';
import 'package:invo/shared/domain/repository/catalog_snapshot_repository.dart';
import 'package:invo/shared/domain/helpers/responsive.dart';
import 'package:invo/features/auth/logic/auth_cubit/auth_cubit.dart';
import 'package:invo/shared/logic/branch_cubit/branch_cubit.dart';
import 'package:invo/shared/logic/currency_cubit/currency_cubit.dart';
import 'package:invo/features/settings/logic/pos_settings_cubit/pos_settings_cubit.dart';
import 'package:invo/features/settings/logic/print_settings_cubit/print_settings_cubit.dart';
import 'package:invo/shared/logic/haptics_cubit/haptics_cubit.dart';
import 'package:invo/shared/logic/theme_cubit/theme_cubit.dart';
import 'package:invo/shared/utils/components/theme/index.dart';
import 'package:invo/shared/utils/local_storage/local_storage_service.dart';
import 'package:invo/shared/utils/router/routes.dart';
import 'package:invo/shared/widgets/app_version.dart';
import 'package:invo/shared/widgets/astra_side_rail.dart';
import 'package:invo/shared/widgets/astra_snack.dart';
import 'package:invo/shared/widgets/astra_widgets.dart';
import 'package:invo/shared/widgets/tablet_widgets.dart';
import 'package:invo/features/auth/widgets/v3/connection_sheet.dart';
import 'package:invo/features/catalog/domain/models/catalog_models.dart';
import 'package:invo/features/catalog/screens/v3/product_config_screen.dart';
import 'package:invo/features/settings/screens/v3/offline_data_screen.dart';
import 'package:invo/features/settings/screens/v3/permissions_screen.dart';
import 'package:invo/features/settings/screens/v3/print_settings_screen.dart';
import 'package:invo/features/settings/widgets/v3/branch_sheet.dart';
import 'package:invo/features/settings/widgets/v3/category_display_sheet.dart';
import 'package:invo/features/settings/widgets/v3/currency_sheet.dart';
import 'package:invo/features/settings/widgets/v3/start_screen_sheet.dart';
import 'package:invo/features/settings/widgets/v3/theme_sheet.dart';
import 'package:invo/features/settings/widgets/v3/typography_sheet.dart';

/// The settings categories, in display order. [group] is the heading each one
/// sits under — on the phone's category list and the tablet's rail alike.
enum _Cat {
  sale('Selling'),
  productConfig('Selling'),
  catalog('Selling'),
  printer('Selling'),
  appearance('You'),
  startScreen('You'),
  permissions('You'),
  system('This device');

  const _Cat(this.group);
  final String group;
}

/// One category as the lists show it: `sub` is a short live state line.
typedef _CatTile = ({_Cat cat, IconData icon, String title, String sub});

class SettingsScreen extends StatefulWidget {
  const SettingsScreen({super.key, this.onSelectTab, this.active = true, this.rootRequest = 0});

  /// Inside the home shell: opens a catalog page as a shell destination so it
  /// keeps its place while the user visits other modules. Without it (a deep
  /// link) the catalog pages are pushed as routes.
  final ValueChanged<int>? onSelectTab;

  /// Whether the shell is showing Settings. It keeps this screen alive behind
  /// other tabs, so back may only close a category page while it is on show.
  final bool active;

  /// Bumped by the shell when Settings is tapped while Settings (or a page
  /// under it) is already showing: back to the category list.
  final int rootRequest;

  @override
  State<SettingsScreen> createState() => _SettingsScreenState();
}

class _SettingsScreenState extends State<SettingsScreen> {
  /// Tablet two-pane only: which category is open in the right panel.
  _Cat _sel = _Cat.sale;

  /// Phones only: the category page open over the category list, or null for
  /// the list itself.
  _Cat? _phoneCat;

  /// Phones only: the card in the list pushes the permissions page. A tablet
  /// never gets here — the list is rendered inside the detail pane instead, so
  /// it stays inside Settings (see [_body]).
  void _openPermissions() => context.push(Routes.permissions);

  @override
  void didUpdateWidget(SettingsScreen old) {
    super.didUpdateWidget(old);
    if (widget.rootRequest != old.rootRequest) _phoneCat = null;
  }

  @override
  Widget build(BuildContext context) {
    final theme = context.watch<ThemeCubit>();
    final currencyCtl = context.watch<CurrencyCubit>();
    final branch = context.watch<BranchCubit>();
    final cats = _cats(context, theme, currencyCtl, branch);
    final open = cats.where((t) => t.cat == _phoneCat).firstOrNull;

    return PopScope(
      // A category page is a step inside Settings: back closes it rather than
      // leaving the shell.
      canPop: context.isTablet || open == null || !widget.active,
      onPopInvokedWithResult: (didPop, _) {
        if (!didPop) setState(() => _phoneCat = null);
      },
      child: Scaffold(
        backgroundColor: Colors.transparent,
        body: AstraBackground(
          // Tablet has no header band — the category rail carries the title
          // (the preview's `.setnav`); the side-rail is the chrome.
          child: context.isTablet
              ? SafeArea(bottom: false, child: _tabletTwoPane(context, cats, theme, currencyCtl, branch))
              : open == null
                  ? _phoneHub(context, cats)
                  : _phoneCategory(context, open, theme, currencyCtl, branch),
        ),
      ),
    );
  }

  // ---- Phone: a list of categories, each opening a page of its own ----

  Widget _phoneHub(BuildContext context, List<_CatTile> cats) {
    return Column(
      children: [
        const EmeraldHeader(title: 'Settings', subtitle: 'Device and account preferences'),
        Expanded(
          child: MaxWidthBox(
            maxWidth: 560,
            child: ListView(
              padding: const EdgeInsets.fromLTRB(16, 16, 16, 110),
              children: [
                ..._grouped(context, cats,
                    (t) => Padding(padding: const EdgeInsets.only(bottom: 8), child: _catRow(context, t))),
                const SizedBox(height: 8),
                _logoutCard(context),
                const SizedBox(height: 14),
                _versionLine(context),
              ],
            ),
          ),
        ),
      ],
    );
  }

  /// Printer & receipt and My permissions are full screens of their own, and
  /// Start screen is a single pick — they open directly rather than through a
  /// page holding one item.
  void _openOnPhone(_Cat cat) {
    switch (cat) {
      case _Cat.printer:
        context.push(Routes.printSettings);
      case _Cat.permissions:
        _openPermissions();
      case _Cat.startScreen:
        showStartScreenSheet(context);
      default:
        setState(() => _phoneCat = cat);
    }
  }

  Widget _catRow(BuildContext context, _CatTile t) {
    final p = context.astra;
    return AstraCard(
      radius: 15,
      onTap: () => _openOnPhone(t.cat),
      child: Row(
        children: [
          Container(
            width: 40,
            height: 40,
            alignment: Alignment.center,
            decoration: BoxDecoration(gradient: p.primaryGradient, borderRadius: BorderRadius.circular(12)),
            child: Icon(t.icon, size: 20, color: Colors.white),
          ),
          const SizedBox(width: 12),
          Expanded(
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Text(t.title, style: ui(size: 13.5, weight: FontWeight.w700, color: p.ink)),
                Text(t.sub, maxLines: 1, overflow: TextOverflow.ellipsis,
                    style: ui(size: 10.5, weight: FontWeight.w600, color: p.textMuted)),
              ],
            ),
          ),
          Icon(Icons.chevron_right, color: p.textMuted, size: 18),
        ],
      ),
    );
  }

  Widget _phoneCategory(BuildContext context, _CatTile t, ThemeCubit theme, CurrencyCubit currency, BranchCubit branch) {
    return Column(
      children: [
        EmeraldHeader(
          title: t.title,
          subtitle: _blurb(t.cat),
          leading: HeaderIconButton(icon: Icons.arrow_back, onTap: () => setState(() => _phoneCat = null)),
        ),
        Expanded(
          child: MaxWidthBox(
            maxWidth: 560,
            child: ListView(
              padding: const EdgeInsets.fromLTRB(16, 16, 16, 110),
              children: _body(context, t.cat, theme, currency, branch),
            ),
          ),
        ),
      ],
    );
  }

  // ---- Shared by both size classes ----

  /// The categories this account can see, in [_Cat] order.
  List<_CatTile> _cats(BuildContext context, ThemeCubit theme, CurrencyCubit currency, BranchCubit branch) {
    final print = context.watch<PrintSettingsCubit>();
    final pos = context.watch<PosSettingsCubit>();
    final auth = context.watch<AuthCubit>();
    // Count only what the permissions screen lists — the app's own gates —
    // not every backend permission the account happens to hold.
    final permCount = mobilePermissions.where((m) => auth.hasPermission(m.slug)).length;
    final sel = branch.selected;
    final lists = [for (final type in CatalogType.values) if (auth.hasPermission(type.viewPermission)) type.plural];
    return [
      // One category for the whole selling flow — opening the ticket, laying
      // the catalog out, and what the till does once the sale is charged.
      (cat: _Cat.sale, icon: Icons.point_of_sale_outlined, title: 'Sale Configuration',
          sub: '${pos.askClientOnNewSale ? 'Asks for client' : 'No client prompt'} · ${_tipShown(context) ? 'tip on' : 'tip off'} · ${pos.lockAfterSale ? 'locks after sale' : 'stays unlocked'}'),
      // Its own category, not a link inside Catalog: these are settings, and a
      // till sets them where it sets everything else.
      if (auth.hasPermission(PermissionSlug.settings))
        (cat: _Cat.productConfig, icon: Icons.qr_code_2, title: 'Product configuration',
            sub: 'Barcode type, prefix and out-of-stock items'),
      if (lists.isNotEmpty)
        (cat: _Cat.catalog, icon: Icons.inventory_2_outlined, title: 'Catalog', sub: lists.join(' · ')),
      (cat: _Cat.printer, icon: Icons.receipt_long_outlined, title: 'Printer & receipt',
          sub: '${print.style.label} · ${print.width.label}'),
      // Preset, brightness and typeface are one subject — how the app looks —
      // so they are one category with sections, not three entries.
      (cat: _Cat.appearance, icon: Icons.palette_outlined, title: 'Appearance',
          sub: '${theme.preset.name} · ${theme.mode.label} · ${theme.typeface.name}'),
      (cat: _Cat.startScreen, icon: startScreenIcon(_effectiveStartScreen(context)), title: 'Start screen',
          sub: 'Opens on ${_effectiveStartScreen(context).label}'),
      (cat: _Cat.permissions, icon: Icons.verified_user_outlined, title: 'My permissions', sub: '$permCount granted'),
      // Haptics, currency, branch, offline data and the server are all "how
      // this device is set up" — plumbing you visit once and leave alone.
      (cat: _Cat.system, icon: Icons.tune, title: 'System configuration',
          sub: '${currency.currency.code} · ${sel == null ? 'No branch' : sel.name}'),
    ];
  }

  /// [tiles] built by [build], with a group heading above the first of each group.
  List<Widget> _grouped(BuildContext context, List<_CatTile> tiles, Widget Function(_CatTile) build) {
    final out = <Widget>[];
    String? group;
    for (final t in tiles) {
      if (t.cat.group != group) {
        out.add(_groupLabel(context, t.cat.group, first: group == null));
        group = t.cat.group;
      }
      out.add(build(t));
    }
    return out;
  }

  Widget _groupLabel(BuildContext context, String label, {required bool first}) {
    final p = context.astra;
    return Padding(
      padding: EdgeInsets.fromLTRB(6, first ? 0 : 12, 6, 8),
      child: Text(label.toUpperCase(),
          style: ui(size: 10.5, weight: FontWeight.w800, color: p.textMuted, letterSpacing: 1.1)),
    );
  }

  String _blurb(_Cat cat) => switch (cat) {
        _Cat.sale => 'How this till rings a sale — from opening the ticket to locking up after it.',
        _Cat.productConfig => 'Barcodes and stock rules — shared with the web, so a change applies on every till.',
        _Cat.catalog => 'Products and services — each opens as a page of its own.',
        _Cat.printer => 'Receipt style, paper width and print options.',
        _Cat.appearance => 'Colour, brightness and type — how the app looks.',
        _Cat.startScreen => 'Where this device opens for you once you sign in — and after an unlock.',
        _Cat.permissions => 'What this account is allowed to do.',
        _Cat.system => 'How this device is set up — feedback, money, branch, offline data and the server behind it.',
      };

  /// Settings → Catalog: Products and Services, each offered only to an
  /// account with its permission.
  List<Widget> _catalogCards(BuildContext context) {
    final auth = context.watch<AuthCubit>();
    return [
      for (final type in CatalogType.values)
        if (auth.hasPermission(type.viewPermission))
          _linkCard(context, type.icon, type.plural, 'List, add and edit ${type.plural.toLowerCase()}',
              () => _openCatalog(type.isProduct ? kProductsTab : kServicesTab, Routes.catalogList(type.key))),
    ];
  }

  void _openCatalog(int tab, String route) =>
      widget.onSelectTab != null ? widget.onSelectTab!(tab) : context.push(route);

  Widget _linkCard(BuildContext context, IconData icon, String title, String subtitle, VoidCallback onTap) {
    final p = context.astra;
    return AstraCard(
      radius: 14,
      onTap: onTap,
      child: Row(
        children: [
          IconChip(icon: icon, size: 34, radius: 9, bg: p.tint),
          const SizedBox(width: 11),
          Expanded(
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Text(title, style: ui(size: 12.5, weight: FontWeight.w700, color: p.ink)),
                Text(subtitle, style: ui(size: 10, weight: FontWeight.w600, color: p.textMuted)),
              ],
            ),
          ),
          Icon(Icons.chevron_right, color: p.textMuted, size: 18),
        ],
      ),
    );
  }

  IconData _modeIcon(AstraMode m) => switch (m) {
        AstraMode.light => Icons.light_mode_outlined,
        AstraMode.dark => Icons.dark_mode_outlined,
        AstraMode.system => Icons.brightness_auto_outlined,
      };

  // ---- Tablet two-pane: category nav (left) + live detail panel (right) ----

  Widget _tabletTwoPane(BuildContext context, List<_CatTile> cats, ThemeCubit theme, CurrencyCubit currency, BranchCubit branch) {
    final sel = cats.firstWhere((t) => t.cat == _sel, orElse: () => cats.first);
    return LayoutBuilder(
      builder: (ctx, c) => _twoPaneRow(context, cats, sel, theme, currency, branch, TabletMetrics.forWidth(c.maxWidth)),
    );
  }

  Widget _twoPaneRow(BuildContext context, List<_CatTile> cats, _CatTile sel, ThemeCubit theme,
      CurrencyCubit currency, BranchCubit branch, TabletMetrics m) {
    return Row(
      crossAxisAlignment: CrossAxisAlignment.stretch,
      children: [
        // The category rail is a surfaced pane (the preview's `.setnav`), so the
        // nav tiles read as one list against the detail side rather than as
        // loose cards floating on the page background.
        TabletPane(
          width: m.settingsNav,
          child: Column(
            children: [
              const TabletPaneHead(title: 'Settings', subtitle: 'Device and account preferences'),
              Expanded(
                child: ListView(
                  padding: const EdgeInsets.fromLTRB(12, 12, 12, 24),
                  children: [
                    ..._grouped(context, cats, (t) => _navTile(context, t.icon, t.title, t.sub,
                        active: t.cat == sel.cat, onTap: () => setState(() => _sel = t.cat))),
                    const SizedBox(height: 16),
                    _logoutCard(context),
                    const SizedBox(height: 14),
                    _versionLine(context),
                  ],
                ),
              ),
            ],
          ),
        ),
        Expanded(
          child: SingleChildScrollView(
            padding: m.detailPadding.copyWith(top: 24, bottom: 40),
            child: MaxWidthBox(
                maxWidth: 620,
                child: _panelShell(context, sel.icon, sel.title, _blurb(sel.cat), _body(context, sel.cat, theme, currency, branch))),
          ),
        ),
      ],
    );
  }

  Widget _navTile(BuildContext context, IconData icon, String title, String sub, {required bool active, required VoidCallback onTap}) {
    final p = context.astra;
    return GestureDetector(
      behavior: HitTestBehavior.opaque,
      onTap: onTap,
      child: Container(
        margin: const EdgeInsets.only(bottom: 6),
        padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 12),
        decoration: BoxDecoration(color: active ? p.tint : Colors.transparent, borderRadius: BorderRadius.circular(14)),
        child: Row(
          children: [
            Container(
              width: 36,
              height: 36,
              alignment: Alignment.center,
              decoration: BoxDecoration(
                gradient: active ? p.primaryGradient : null,
                color: active ? null : p.tint,
                borderRadius: BorderRadius.circular(11),
              ),
              child: Icon(icon, size: 18, color: active ? Colors.white : p.textSecondary),
            ),
            const SizedBox(width: 12),
            Expanded(
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  Text(title, style: ui(size: 13, weight: active ? FontWeight.w800 : FontWeight.w700, color: p.ink)),
                  Text(sub, maxLines: 1, overflow: TextOverflow.ellipsis, style: ui(size: 10.5, weight: FontWeight.w600, color: p.textMuted)),
                ],
              ),
            ),
          ],
        ),
      ),
    );
  }

  /// A category's settings — the tablet's detail panel and the phone's category
  /// page alike. Simple settings (mode, haptics) get real inline controls;
  /// pickers reuse their existing sheet so nothing loses functionality.
  List<Widget> _body(BuildContext context, _Cat cat, ThemeCubit theme, CurrencyCubit currency, BranchCubit branch) {
    switch (cat) {
      case _Cat.appearance:
        return [
          _panelSection(context, 'Colour preset', 'The accent palette used across the app.', top: 0),
          _presetCard(context, theme),
          const SizedBox(height: 12),
          AstraButton(label: 'Choose a preset', icon: Icons.palette_outlined, onTap: () => showThemeSheet(context)),
          _panelSection(context, 'Light & dark', 'Light, dark, or follow the system setting.'),
          for (final m in AstraMode.values) _modeRow(context, theme, m),
          _panelSection(context, 'Typography', 'The typeface the whole app is set in.'),
          _typographyCard(context, theme),
          const SizedBox(height: 12),
          AstraButton(label: 'Choose a typeface', icon: Icons.text_fields_outlined, onTap: () => showTypographySheet(context)),
          // Only a tablet has the rail this frames.
          if (context.isTablet) ...[
            _panelSection(context, 'Window', 'Where the rail sits, and how the page is framed beside it.'),
            for (final c in AstraChrome.values) _chromeRow(context, theme, c),
          ],
        ];
      case _Cat.printer:
        // The real screen, hosted in the pane — not a summary card behind a
        // button that throws the whole window away to show it. Settings on a
        // tablet is one place you stay in; a category that pushes a page reads
        // as leaving, and losing the rail to change a paper width is a poor
        // trade. Phones still push it: there is no second pane to host it in.
        return const [PrintSettingsScreen(embedded: true)];
      case _Cat.sale:
        return [
          _askClientCard(context),
          const SizedBox(height: 11),
          _tipCard(context),
          const SizedBox(height: 11),
          // The grid lays out exactly the count chosen on a tablet too, so a
          // till sets its density here rather than inheriting one from width.
          _gridColumnsCard(context),
          const SizedBox(height: 11),
          _categoryDisplayCard(context),
          const SizedBox(height: 11),
          _productOrderCard(context),
          const SizedBox(height: 11),
          // It carries the lock chip and the auto-print caveat, which a bare
          // toggle row did not.
          _lockAfterSaleCard(context),
          const SizedBox(height: 11),
          _askBranchOnUnlockCard(context),
        ];
      case _Cat.productConfig:
        return const [ProductConfigScreen()];
      case _Cat.catalog:
        // The lists open as full-width pages — a table needs the room.
        return [for (final card in _catalogCards(context)) ...[card, const SizedBox(height: 11)]];
      case _Cat.startScreen:
        return [
          for (final option in startScreenOptions(context))
            _startScreenRow(context, option, option == _effectiveStartScreen(context)),
        ];
      case _Cat.permissions:
        // The real list, hosted in the pane — same reasoning as the printer
        // category above.
        return const [PermissionsScreen(embedded: true)];
      case _Cat.system:
        final haptics = context.watch<HapticsCubit>();
        return [
          _panelSection(context, 'Haptics', 'Vibration feedback on every tap.', top: 0),
          _toggleRow(context, 'Haptic feedback', haptics.enabled ? 'On — a tick on each tap' : 'Off', haptics.enabled,
              () => context.read<HapticsCubit>().toggle()),
          _panelSection(context, 'Currency', 'Base currency and the rates used for conversion.'),
          _currencyCard(context, currency),
          const SizedBox(height: 12),
          AstraButton(label: 'Manage currencies', icon: Icons.tune, onTap: () => showCurrencySheet(context)),
          _panelSection(context, 'Branch', 'The branch this device bills against.'),
          _branchCard(context, branch),
          const SizedBox(height: 12),
          AstraButton(label: 'Switch branch', icon: Icons.swap_horiz, onTap: () => showBranchSheet(context)),
          // The live sync line, not a static blurb: it is the one thing on this
          // panel that changes on its own.
          _panelSection(context, 'Offline data', _offlineSummary(_syncState)),
          const OfflineDataScreen(embedded: true),
          _panelSection(context, 'Server connection', 'The API base URL and tenant this app talks to.'),
          AstraButton(label: 'Connection settings', icon: Icons.cloud_outlined, onTap: () => ConnectionSheet.show(context)),
        ];
    }
  }

  Widget _panelShell(BuildContext context, IconData icon, String title, String desc, List<Widget> children) {
    final p = context.astra;
    return Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        Row(
          children: [
            IconChip(icon: icon, size: 44, radius: 14, bg: p.tint),
            const SizedBox(width: 13),
            Expanded(
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  Text(title, style: serif(size: 22, color: p.ink)),
                  const SizedBox(height: 2),
                  Text(desc, style: ui(size: 12, weight: FontWeight.w600, color: p.textMuted)),
                ],
              ),
            ),
          ],
        ),
        const SizedBox(height: 20),
        ...children,
      ],
    );
  }

  /// A labelled divider inside a detail panel, for categories that hold more
  /// than one setting (Appearance: preset, brightness, typeface). The first
  /// section passes `top: 0` — [_panelShell] has already spaced it.
  Widget _panelSection(BuildContext context, String title, String desc, {double top = 22}) {
    final p = context.astra;
    return Container(
      // A rule across the panel, not just a gap: these sections used to be
      // separate categories, and one scroll of cards runs them together — the
      // last control of one section and the next section's label read as a pair
      // without a line to end it on. Suppressed above the first section, which
      // has the panel head over it already.
      margin: EdgeInsets.only(top: top),
      padding: EdgeInsets.only(top: top > 0 ? 18 : 0, bottom: 12),
      decoration: top > 0
          ? BoxDecoration(border: Border(top: BorderSide(color: p.hairline)))
          : null,
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Text(title.toUpperCase(), style: ui(size: 10.5, weight: FontWeight.w800, color: p.textMuted, letterSpacing: 1.1)),
          const SizedBox(height: 3),
          Text(desc, style: ui(size: 12, weight: FontWeight.w600, color: p.textSecondary)),
        ],
      ),
    );
  }

  /// One window-chrome option, drawn as a miniature of the layout it picks:
  /// the rail's bar on the left and the page beside it, framed the way that
  /// chrome frames it. A one-line label cannot distinguish "docked" from
  /// "inset canvas" — the whole choice is a shape, so the row shows the shape.
  ///
  /// Applies on tap, like every other picker here.
  Widget _chromeRow(BuildContext context, ThemeCubit theme, AstraChrome c) {
    final p = context.astra;
    final active = theme.chrome == c;
    return GestureDetector(
      behavior: HitTestBehavior.opaque,
      onTap: () => context.read<ThemeCubit>().setChrome(c),
      child: Container(
        margin: const EdgeInsets.only(bottom: 10),
        padding: const EdgeInsets.all(12),
        decoration: BoxDecoration(
          color: active ? p.tint : p.card,
          borderRadius: BorderRadius.circular(14),
          border: Border.all(color: active ? p.primary : p.cardBorder, width: active ? 1.5 : 1),
        ),
        child: Row(
          children: [
            _chromeThumb(context, c),
            const SizedBox(width: 13),
            Expanded(
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  Text(c.label, style: ui(size: 12.5, weight: FontWeight.w700, color: p.ink)),
                  const SizedBox(height: 2),
                  Text(c.tagline, style: ui(size: 10.5, weight: FontWeight.w600, color: p.textMuted)),
                ],
              ),
            ),
            if (active) Icon(Icons.check_circle, size: 20, color: p.primary),
          ],
        ),
      ),
    );
  }

  /// A 56x40 sketch of the window under [c] — same geometry as the real thing,
  /// scaled down: rail bar, page surface, and whatever gutter sits between them.
  Widget _chromeThumb(BuildContext context, AstraChrome c) {
    final p = context.astra;
    final rail = Container(
      width: 13,
      decoration: BoxDecoration(
        color: p.darkSurface,
        borderRadius: c == AstraChrome.peers ? BorderRadius.circular(4) : null,
      ),
    );
    final page = Container(
      decoration: BoxDecoration(
        color: p.isDark ? Colors.white24 : Colors.white,
        borderRadius: c == AstraChrome.docked || c == AstraChrome.unified
            ? null
            : BorderRadius.circular(4),
      ),
    );
    final pad = switch (c) {
      AstraChrome.insetCanvas => const EdgeInsets.fromLTRB(0, 3, 3, 3),
      AstraChrome.peers => const EdgeInsets.fromLTRB(3, 0, 0, 0),
      _ => EdgeInsets.zero,
    };
    final inner = Row(children: [
      rail,
      Expanded(child: Padding(padding: pad, child: page)),
    ]);
    return Container(
      width: 56,
      height: 40,
      padding: switch (c) {
        AstraChrome.peers => const EdgeInsets.all(3),
        AstraChrome.unified => const EdgeInsets.all(4),
        _ => EdgeInsets.zero,
      },
      decoration: BoxDecoration(
        // The backdrop is what each chrome actually shows behind everything:
        // the rail's own colour for inset canvas, the page canvas otherwise.
        color: c == AstraChrome.insetCanvas ? p.darkSurface : p.canvas,
        borderRadius: BorderRadius.circular(9),
        border: Border.all(color: p.hairline),
      ),
      clipBehavior: Clip.antiAlias,
      child: c == AstraChrome.unified
          ? ClipRRect(borderRadius: BorderRadius.circular(6), child: inner)
          : inner,
    );
  }

  /// An inline appearance-mode option (Light / Dark / System).
  Widget _modeRow(BuildContext context, ThemeCubit theme, AstraMode m) {
    final p = context.astra;
    final active = theme.mode == m;
    return GestureDetector(
      behavior: HitTestBehavior.opaque,
      onTap: () => context.read<ThemeCubit>().setMode(m),
      child: Container(
        margin: const EdgeInsets.only(bottom: 10),
        padding: const EdgeInsets.symmetric(horizontal: 14, vertical: 14),
        decoration: BoxDecoration(
          color: active ? p.tint : p.card,
          borderRadius: BorderRadius.circular(14),
          border: Border.all(color: active ? p.primary : p.cardBorder, width: active ? 1.5 : 1),
        ),
        child: Row(
          children: [
            Icon(_modeIcon(m), size: 20, color: active ? p.primary : p.textSecondary),
            const SizedBox(width: 12),
            Expanded(child: Text(m.label, style: ui(size: 14, weight: FontWeight.w700, color: p.ink))),
            if (active) Icon(Icons.check_circle_rounded, size: 20, color: p.primary),
          ],
        ),
      ),
    );
  }

  /// An inline labelled toggle row (used for Haptics in the detail pane).
  /// Whether the business offers a tip at all, from the web's Sale
  /// Configuration. The device switch below can only hide the row, never bring
  /// it back, so this is what decides whether that switch does anything.
  bool _tipAllowedByBusiness() =>
      serviceLocator<LocalStorageService>().tipEnabled ?? true;

  /// Whether the tip row actually appears at checkout — both answers agreeing.
  bool _tipShown(BuildContext context) =>
      _tipAllowedByBusiness() && context.watch<PosSettingsCubit>().showTip;

  /// Show or hide the "Add a Tip" row at Review & Pay, on this device only.
  ///
  /// When the web has tips switched off there is nothing for the till to decide,
  /// so the row says so and stays inert rather than offering a switch that would
  /// change nothing.
  Widget _tipCard(BuildContext context) {
    final pos = context.watch<PosSettingsCubit>();
    if (!_tipAllowedByBusiness()) {
      return _staticRow(context, 'Ask for a tip',
          'Turned off for the business in Sale Configuration on the web.',
          icon: Icons.volunteer_activism_outlined);
    }
    return _toggleRow(
      context,
      'Ask for a tip',
      pos.showTip
          ? 'On — the tip row shows at Review & Pay'
          : 'Off — hidden on this device; other tills are unaffected',
      pos.showTip,
      icon: Icons.volunteer_activism_outlined,
      () {
        final next = !pos.showTip;
        pos.setShowTip(next);
        // A tip already chosen on the open ticket would otherwise stay on the
        // total with no row left to change it.
        if (!next) context.read<CartCubit>().setTip(0);
      },
    );
  }

  /// A settings row with nothing to toggle — a state the till is told about
  /// rather than one it chooses.
  Widget _staticRow(BuildContext context, String title, String sub, {IconData? icon}) {
    final p = context.astra;
    return AstraCard(
      radius: 14,
      child: Row(
        children: [
          // Given a chip, the row takes the icon-card metrics so it sits level
          // with the cards above and below it rather than beside them.
          if (icon != null) ...[
            IconChip(icon: icon, size: 34, radius: 9, bg: p.tint),
            const SizedBox(width: 11),
          ],
          Expanded(
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Text(title, style: ui(size: icon == null ? 13.5 : 12.5, weight: FontWeight.w700, color: p.textMuted)),
                Text(sub, style: ui(size: icon == null ? 11 : 10, weight: FontWeight.w600, color: p.textMuted)),
              ],
            ),
          ),
          Icon(Icons.lock_outline, size: 15, color: p.textMuted),
        ],
      ),
    );
  }

  Widget _toggleRow(BuildContext context, String title, String sub, bool value, VoidCallback onTap,
      {IconData? icon}) {
    final p = context.astra;
    return GestureDetector(
      onTap: onTap,
      child: AstraCard(
        radius: 14,
        child: Row(
          children: [
            if (icon != null) ...[
              IconChip(icon: icon, size: 34, radius: 9, bg: p.tint),
              const SizedBox(width: 11),
            ],
            Expanded(
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  Text(title, style: ui(size: icon == null ? 13.5 : 12.5, weight: FontWeight.w700, color: p.ink)),
                  Text(sub, style: ui(size: icon == null ? 11 : 10, weight: FontWeight.w600, color: p.textMuted)),
                ],
              ),
            ),
            _switch(context, value),
          ],
        ),
      ),
    );
  }

  /// The sync engine is an app-wide singleton that boots with the session, so a
  /// widget test rendering this screen on its own has none. Guarded the same way
  /// [OfflineBanner] guards it — a settings list is not worth crashing over a
  /// dependency that only exists once someone has signed in.
  bool get _hasSync => serviceLocator.isRegistered<OfflineSyncCubit>();

  OfflineSyncState get _syncState =>
      _hasSync ? serviceLocator<OfflineSyncCubit>().state : const OfflineSyncState();

  /// One line for how ready this till is to sell with no network. Shared by the
  /// phone card and the tablet nav sub-label so the two never disagree.
  String _offlineSummary(OfflineSyncState sync) {
    if (sync.catalogRefreshing) return 'Preparing…';
    if (!sync.hasCatalog) return 'Not prepared — can’t sell offline';
    if (sync.provisionIncomplete.isNotEmpty) return 'Incomplete — reconnect to finish';
    return 'Synced ${catalogAgeLabel(sync.catalogSyncedAt)}';
  }

  Widget _logoutCard(BuildContext context) {
    return GestureDetector(
      onTap: () => _confirmLogout(context),
      child: AstraCard(
        radius: 14,
        child: Center(
          child: Row(
            mainAxisSize: MainAxisSize.min,
            children: [
              const Icon(Icons.logout, size: 16, color: AstraPalette.danger),
              const SizedBox(width: 8),
              Text('Log out', style: ui(size: 12.5, weight: FontWeight.w700, color: AstraPalette.danger)),
            ],
          ),
        ),
      ),
    );
  }

  Widget _versionLine(BuildContext context) {
    final p = context.astra;
    return Center(
      child: AppVersionText(
          prefix: 'QLOUD POS · ',
          style: ui(size: 10.5, weight: FontWeight.w600, color: p.textMuted)),
    );
  }

  Widget _presetCard(BuildContext context, ThemeCubit theme) {
    final p = context.astra;
    final preset = theme.preset;
    return AstraCard(
      radius: 14,
      onTap: () => showThemeSheet(context),
      child: Row(
        children: [
          SizedBox(
            width: 18.0 + 12 * 3,
            height: 34,
            child: Stack(
              alignment: Alignment.centerLeft,
              children: [
                for (var i = 0; i < preset.swatch.length; i++)
                  Positioned(
                    left: i * 12.0,
                    child: Container(
                      width: 20,
                      height: 20,
                      decoration: BoxDecoration(
                        color: preset.swatch[i],
                        shape: BoxShape.circle,
                        border: Border.all(color: p.cardSolid, width: 2),
                      ),
                    ),
                  ),
              ],
            ),
          ),
          const SizedBox(width: 11),
          Expanded(
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Text('Colour preset', style: ui(size: 12.5, weight: FontWeight.w700, color: p.ink)),
                Text('${preset.name} · ${preset.tagline}',
                    maxLines: 1,
                    overflow: TextOverflow.ellipsis,
                    style: ui(size: 10, weight: FontWeight.w600, color: p.textMuted)),
              ],
            ),
          ),
          Icon(Icons.chevron_right, color: p.textMuted, size: 18),
        ],
      ),
    );
  }

  Widget _typographyCard(BuildContext context, ThemeCubit theme) {
    final p = context.astra;
    final face = theme.typeface;
    return AstraCard(
      radius: 14,
      onTap: () => showTypographySheet(context),
      child: Row(
        children: [
          // The "Aa" is set in the live display face, so the card shows the
          // choice rather than describing it.
          Container(
            width: 34,
            height: 34,
            alignment: Alignment.center,
            decoration: BoxDecoration(color: p.tint, borderRadius: BorderRadius.circular(9)),
            child: Text('Aa', style: face.displayStyle(size: 15, color: p.primary)),
          ),
          const SizedBox(width: 11),
          Expanded(
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Text('Typography', style: ui(size: 12.5, weight: FontWeight.w700, color: p.ink)),
                Text('${face.name} · ${face.tagline}',
                    maxLines: 1,
                    overflow: TextOverflow.ellipsis,
                    style: ui(size: 10, weight: FontWeight.w600, color: p.textMuted)),
              ],
            ),
          ),
          Icon(Icons.chevron_right, color: p.textMuted, size: 18),
        ],
      ),
    );
  }

  /// Shared-till mode. Ringing a sale locks the terminal, so it's never left
  /// open as whoever served the last customer — but the session stays alive, so
  /// the next unlock is a PIN tap rather than a sign-in.
  Widget _lockAfterSaleCard(BuildContext context) {
    final p = context.astra;
    final pos = context.watch<PosSettingsCubit>();
    final autoPrint = context.watch<PrintSettingsCubit>().autoPrint;
    final on = pos.lockAfterSale;
    return AstraCard(
      radius: 14,
      onTap: () => context.read<PosSettingsCubit>().toggleLockAfterSale(),
      child: Row(
        children: [
          IconChip(
            icon: on ? Icons.lock_clock_outlined : Icons.lock_open_outlined,
            size: 34,
            radius: 9,
            bg: p.tint,
          ),
          const SizedBox(width: 11),
          Expanded(
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Text('Lock after each sale',
                    style: ui(size: 12.5, weight: FontWeight.w700, color: p.ink)),
                // With lock on and auto-print off nobody ever reaches the Print
                // button — say so before a till is set up that way.
                Text(
                    !on
                        ? 'Stay unlocked between sales'
                        : autoPrint
                            ? 'MPIN or fingerprint to carry on — no re-login'
                            : 'Locks once charged — turn on auto-print so receipts still print',
                    style: ui(size: 10, weight: FontWeight.w600, color: p.textMuted)),
              ],
            ),
          ),
          _switch(context, on),
        ],
      ),
    );
  }

  /// Whether unlocking the till asks a user with more than one branch which
  /// branch again. Off keeps the last chosen branch for every request; signing
  /// in always asks.
  Widget _askBranchOnUnlockCard(BuildContext context) {
    final p = context.astra;
    final on = context.watch<PosSettingsCubit>().askBranchOnUnlock;
    return AstraCard(
      radius: 14,
      onTap: () => context.read<PosSettingsCubit>().toggleAskBranchOnUnlock(),
      child: Row(
        children: [
          IconChip(
            icon: on ? Icons.storefront_outlined : Icons.store_mall_directory_outlined,
            size: 34,
            radius: 9,
            bg: p.tint,
          ),
          const SizedBox(width: 11),
          Expanded(
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Text('Ask for branch on unlock',
                    style: ui(size: 12.5, weight: FontWeight.w700, color: p.ink)),
                Text(
                    on
                        ? 'Multi-branch staff pick a branch after each unlock'
                        : 'Unlock keeps the last chosen branch — sign-in still asks',
                    style: ui(size: 10, weight: FontWeight.w600, color: p.textMuted)),
              ],
            ),
          ),
          _switch(context, on),
        ],
      ),
    );
  }

  /// Whether New Sale opens the client form the moment the screen loads, on a
  /// ticket that is still an empty walk-in.
  ///
  /// A salon takes the name before anything else; a counter serving a queue
  /// wants the catalog on screen, and pays a tap per sale dismissing a form it
  /// never fills in. The client can still be set from the CLIENT selector at any
  /// point, so turning this off removes a prompt, not a capability.
  Widget _askClientCard(BuildContext context) {
    final p = context.astra;
    final on = context.watch<PosSettingsCubit>().askClientOnNewSale;
    return AstraCard(
      radius: 14,
      onTap: () => context.read<PosSettingsCubit>().toggleAskClientOnNewSale(),
      child: Row(
        children: [
          IconChip(
            icon: on ? Icons.person_add_alt_1_outlined : Icons.person_outline,
            size: 34,
            radius: 9,
            bg: p.tint,
          ),
          const SizedBox(width: 11),
          Expanded(
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Text('Ask for client on new sale',
                    style: ui(size: 12.5, weight: FontWeight.w700, color: p.ink)),
                Text(
                    on
                        ? 'Opens the client form as New Sale loads'
                        : 'Starts on the catalog — set the client from CLIENT',
                    style: ui(size: 10, weight: FontWeight.w600, color: p.textMuted)),
              ],
            ),
          ),
          _switch(context, on),
        ],
      ),
    );
  }

  /// How densely New Sale lays the catalog out. A shop whose products carry no
  /// photos wants four or five across; three-up is for a catalog of images.
  /// Applies on tap — there's nothing to confirm.
  Widget _gridColumnsCard(BuildContext context) {
    final p = context.astra;
    final pos = context.watch<PosSettingsCubit>();
    return AstraCard(
      radius: 14,
      child: Row(
        children: [
          IconChip(icon: Icons.grid_view_rounded, size: 34, radius: 9, bg: p.tint),
          const SizedBox(width: 11),
          Expanded(
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Text('Products per row',
                    style: ui(size: 12.5, weight: FontWeight.w700, color: p.ink)),
                Text('New Sale grid view · ${pos.gridColumns} across',
                    style: ui(size: 10, weight: FontWeight.w600, color: p.textMuted)),
              ],
            ),
          ),
          Container(
            padding: const EdgeInsets.all(3),
            decoration: BoxDecoration(color: p.tint, borderRadius: BorderRadius.circular(11)),
            child: Row(
              mainAxisSize: MainAxisSize.min,
              children: [
                for (final n in PosSettingsState.gridColumnOptions)
                  GestureDetector(
                    onTap: () => context.read<PosSettingsCubit>().setGridColumns(n),
                    child: AnimatedContainer(
                      duration: const Duration(milliseconds: 160),
                      width: 32,
                      height: 28,
                      alignment: Alignment.center,
                      decoration: BoxDecoration(
                        gradient: pos.gridColumns == n ? p.primaryGradient : null,
                        borderRadius: BorderRadius.circular(9),
                      ),
                      child: Text('$n',
                          style: ui(
                              size: 12.5,
                              weight: FontWeight.w800,
                              color: pos.gridColumns == n ? Colors.white : p.textSecondary)),
                    ),
                  ),
              ],
            ),
          ),
        ],
      ),
    );
  }

  /// The order New Sale lists products in — by priority or name, either way
  /// round. Shared with every till and the web Sale Configuration, so it saves
  /// to the server; without `configuration.settings` it is shown, not offered.
  Widget _productOrderCard(BuildContext context) {
    final p = context.astra;
    final sort = context.watch<PosSettingsCubit>().productSort;
    final canEdit = context.read<AuthCubit>().hasPermission(PermissionSlug.settings);
    final directionLabels = sort.isByName ? const ('A – Z', 'Z – A') : const ('Low first', 'High first');
    final summary = '${sort.isByName ? 'Name' : 'Priority'} · ${sort.isDescending ? directionLabels.$2 : directionLabels.$1}';

    Future<void> save(ProductSort next) async {
      final ok = await context.read<PosSettingsCubit>().setProductSort(next);
      if (!ok && context.mounted) AstraSnack.error(context, 'Couldn\'t save — check your connection.');
    }

    Widget segment(List<(String, String)> options, String selected, ValueChanged<String> onPick) => Container(
          padding: const EdgeInsets.all(3),
          decoration: BoxDecoration(color: p.tint, borderRadius: BorderRadius.circular(11)),
          child: Row(
            children: [
              for (final (value, label) in options)
                Expanded(
                  child: GestureDetector(
                    onTap: canEdit && value != selected ? () => onPick(value) : null,
                    child: AnimatedContainer(
                      duration: const Duration(milliseconds: 160),
                      height: 28,
                      alignment: Alignment.center,
                      decoration: BoxDecoration(
                        gradient: value == selected ? p.primaryGradient : null,
                        borderRadius: BorderRadius.circular(9),
                      ),
                      child: Text(label,
                          style: ui(
                              size: 11.5,
                              weight: FontWeight.w800,
                              color: value == selected ? Colors.white : p.textSecondary)),
                    ),
                  ),
                ),
            ],
          ),
        );

    return AstraCard(
      radius: 14,
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Row(
            children: [
              IconChip(icon: Icons.sort_rounded, size: 34, radius: 9, bg: p.tint),
              const SizedBox(width: 11),
              Expanded(
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    Text('Product order', style: ui(size: 12.5, weight: FontWeight.w700, color: p.ink)),
                    Text(canEdit ? 'New Sale · $summary · all tills' : 'New Sale · $summary · set on the web',
                        maxLines: 1,
                        overflow: TextOverflow.ellipsis,
                        style: ui(size: 10, weight: FontWeight.w600, color: p.textMuted)),
                  ],
                ),
              ),
              if (!canEdit) Icon(Icons.lock_outline, size: 15, color: p.textMuted),
            ],
          ),
          const SizedBox(height: 10),
          segment(const [(ProductSort.byPriority, 'Priority'), (ProductSort.byName, 'Name')], sort.by,
              (v) => save(sort.copyWith(by: v))),
          const SizedBox(height: 6),
          segment([(ProductSort.asc, directionLabels.$1), (ProductSort.desc, directionLabels.$2)], sort.direction,
              (v) => save(sort.copyWith(direction: v))),
        ],
      ),
    );
  }

  /// How the category rail across the top of New Sale is drawn — plain chips,
  /// or one of three layouts that show the category's photo. Opens the picker,
  /// which previews each rail at its real height: the rail is pinned, so a
  /// taller one is permanently less catalog on screen.
  Widget _categoryDisplayCard(BuildContext context) {
    final p = context.astra;
    final display = context.watch<PosSettingsCubit>().categoryDisplay;
    return AstraCard(
      radius: 14,
      onTap: () => showCategoryDisplaySheet(context),
      child: Row(
        children: [
          IconChip(icon: Icons.style_outlined, size: 34, radius: 9, bg: p.tint),
          const SizedBox(width: 11),
          Expanded(
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Text('Category display', style: ui(size: 12.5, weight: FontWeight.w700, color: p.ink)),
                Text('New Sale rail · ${display.label}',
                    maxLines: 1,
                    overflow: TextOverflow.ellipsis,
                    style: ui(size: 10, weight: FontWeight.w600, color: p.textMuted)),
              ],
            ),
          ),
          Icon(Icons.chevron_right, color: p.textMuted, size: 18),
        ],
      ),
    );
  }

  /// What the device opens on after sign-in or an unlock.
  StartScreen _effectiveStartScreen(BuildContext context) => context.watch<PosSettingsCubit>().startScreen;

  /// An inline start-screen option, click-and-go like the
  /// appearance rows beside it.
  Widget _startScreenRow(BuildContext context, StartScreen screen, bool active) {
    final p = context.astra;
    return GestureDetector(
      behavior: HitTestBehavior.opaque,
      onTap: () => context.read<PosSettingsCubit>().setStartScreen(screen),
      child: Container(
        margin: const EdgeInsets.only(bottom: 10),
        padding: const EdgeInsets.symmetric(horizontal: 14, vertical: 13),
        decoration: BoxDecoration(
          color: active ? p.tint : p.card,
          borderRadius: BorderRadius.circular(14),
          border: Border.all(color: active ? p.primary : p.cardBorder, width: active ? 1.5 : 1),
        ),
        child: Row(
          children: [
            Icon(startScreenIcon(screen), size: 20, color: active ? p.primary : p.textSecondary),
            const SizedBox(width: 12),
            Expanded(
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  Text(screen.label, style: ui(size: 14, weight: FontWeight.w700, color: p.ink)),
                  Text(screen.blurb,
                      style: ui(size: 10.5, weight: FontWeight.w600, color: p.textMuted)),
                ],
              ),
            ),
            if (active) Icon(Icons.check_circle_rounded, size: 20, color: p.primary),
          ],
        ),
      ),
    );
  }

  Widget _switch(BuildContext context, bool value) {
    final p = context.astra;
    return AnimatedContainer(
      duration: const Duration(milliseconds: 160),
      width: 44,
      height: 26,
      padding: const EdgeInsets.all(3),
      alignment: value ? Alignment.centerRight : Alignment.centerLeft,
      decoration: BoxDecoration(
        gradient: value ? p.primaryGradient : null,
        color: value ? null : p.hairline,
        borderRadius: BorderRadius.circular(20),
      ),
      child: Container(
        width: 20,
        height: 20,
        decoration: const BoxDecoration(color: Colors.white, shape: BoxShape.circle),
      ),
    );
  }

  Widget _currencyCard(BuildContext context, CurrencyCubit controller) {
    final p = context.astra;
    final currency = controller.currency;
    final count = controller.available.length;
    final subtitle = count > 1
        ? '${currency.code} · $count available${controller.isCached ? ' · cached' : ''}'
        : '${currency.name} · ${currency.code}';
    return AstraCard(
      radius: 14,
      onTap: () => showCurrencySheet(context),
      child: Row(
        children: [
          Container(
            width: 34,
            height: 34,
            alignment: Alignment.center,
            decoration: BoxDecoration(color: p.tint, borderRadius: BorderRadius.circular(9)),
            child: Text(currency.symbol.trim(), style: serif(size: 16, color: p.primaryDark)),
          ),
          const SizedBox(width: 11),
          Expanded(
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Text('Currency', style: ui(size: 12.5, weight: FontWeight.w700, color: p.ink)),
                Text(subtitle, style: ui(size: 10, weight: FontWeight.w600, color: p.textMuted)),
              ],
            ),
          ),
          Icon(Icons.chevron_right, color: p.textMuted, size: 18),
        ],
      ),
    );
  }

  Widget _branchCard(BuildContext context, BranchCubit branch) {
    final p = context.astra;
    final selected = branch.selected;
    final subtitle = branch.loading && selected == null
        ? 'Loading branches…'
        : selected == null
            ? (branch.error ?? 'Tap to choose a branch')
            : (selected.location.isEmpty
                ? selected.name
                : '${selected.name} · ${selected.location}');
    return AstraCard(
      radius: 14,
      onTap: () => showBranchSheet(context),
      child: Row(
        children: [
          IconChip(icon: Icons.business, size: 34, radius: 9, bg: p.tint),
          const SizedBox(width: 11),
          Expanded(
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Text('Branch',
                    style: ui(size: 12.5, weight: FontWeight.w700, color: p.ink)),
                Text(subtitle, style: ui(size: 10, weight: FontWeight.w600, color: p.textMuted)),
              ],
            ),
          ),
          Icon(Icons.chevron_right, color: p.textMuted, size: 18),
        ],
      ),
    );
  }

  Future<void> _confirmLogout(BuildContext context) async {
    final ok = await showDialog<bool>(
      context: context,
      builder: (ctx) => AlertDialog(
        title: const Text('Log out?'),
        content: const Text('You’ll need your MPIN to sign back in.'),
        actions: [
          TextButton(onPressed: () => Navigator.pop(ctx, false), child: const Text('Cancel')),
          TextButton(onPressed: () => Navigator.pop(ctx, true), child: const Text('Log out')),
        ],
      ),
    );
    if (ok == true && context.mounted) {
      await context.read<AuthCubit>().logout();
    }
  }
}
