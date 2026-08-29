import 'package:flutter/material.dart';
import 'package:flutter_bloc/flutter_bloc.dart';
import 'package:go_router/go_router.dart';

import 'package:invo/features/sale/logic/cart_cubit/cart_cubit.dart';
import 'package:invo/features/sale/logic/offline_sync_cubit/offline_sync_cubit.dart';
import 'package:invo/shared/domain/constants/global_variables.dart';
import 'package:invo/shared/domain/constants/mobile_permissions.dart';
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
import 'package:invo/shared/widgets/astra_widgets.dart';
import 'package:invo/shared/widgets/tablet_widgets.dart';
import 'package:invo/features/auth/widgets/v3/connection_sheet.dart';
import 'package:invo/features/settings/screens/v3/offline_data_screen.dart';
import 'package:invo/features/settings/screens/v3/permissions_screen.dart';
import 'package:invo/features/settings/screens/v3/print_settings_screen.dart';
import 'package:invo/features/settings/widgets/v3/appearance_sheet.dart';
import 'package:invo/features/settings/widgets/v3/branch_sheet.dart';
import 'package:invo/features/settings/widgets/v3/currency_sheet.dart';
import 'package:invo/features/settings/widgets/v3/start_screen_sheet.dart';
import 'package:invo/features/settings/widgets/v3/theme_sheet.dart';
import 'package:invo/features/settings/widgets/v3/typography_sheet.dart';

class SettingsScreen extends StatefulWidget {
  const SettingsScreen({super.key});

  @override
  State<SettingsScreen> createState() => _SettingsScreenState();
}

class _SettingsScreenState extends State<SettingsScreen> {
  /// Tablet two-pane only: which settings category is open in the right panel.
  /// Ignored on phones, which keep the single scrolling card list.
  int _sel = 0;

  /// Phones only: the card in the list pushes the permissions page. A tablet
  /// never gets here — the list is rendered inside the detail pane instead, so
  /// it stays inside Settings (see [_panel] case 4).
  void _openPermissions() => context.push(Routes.permissions);

  @override
  Widget build(BuildContext context) {
    final theme = context.watch<ThemeCubit>();
    final currencyCtl = context.watch<CurrencyCubit>();
    final branch = context.watch<BranchCubit>();

    return Scaffold(
      backgroundColor: Colors.transparent,
      body: AstraBackground(
        // Tablet has no header band — the category rail carries the title
        // (the preview's `.setnav`); the side-rail is the chrome.
        child: context.isTablet
            ? SafeArea(bottom: false, child: _tabletTwoPane(context, theme, currencyCtl, branch))
            : Column(
                children: [
                  const EmeraldHeader(title: 'Settings'),
                  Expanded(
                    child: MaxWidthBox(
                      maxWidth: 560,
                      child: _phoneList(context, <Widget>[
                        _presetCard(context, theme),
                        _appearanceCard(context, theme),
                        _typographyCard(context, theme),
                        _hapticsCard(context),
                        _currencyCard(context, currencyCtl),
                        _branchCard(context, branch),
                        _printerCard(context),
                        _lockAfterSaleCard(context),
                        _askClientCard(context),
                        _tipCard(context),
                        _gridColumnsCard(context),
                        _startScreenCard(context),
                        _permissionsCard(context),
                        _offlineDataCard(context),
                        _serverCard(context),
                      ]),
                    ),
                  ),
                ],
              ),
      ),
    );
  }

  /// Phone: a single scrolling column of cards (unchanged layout).
  Widget _phoneList(BuildContext context, List<Widget> cards) {
    return ListView(
      padding: const EdgeInsets.fromLTRB(16, 16, 16, 110),
      children: [
        for (final c in cards) ...[c, const SizedBox(height: 11)],
        _logoutCard(context),
        const SizedBox(height: 14),
        _versionLine(context),
      ],
    );
  }

  // ---- Tablet two-pane: category nav (left) + live detail panel (right) ----

  /// The settings categories, in nav order. `sub` is a short state line shown
  /// under each nav label; the detail is rendered by [_panel].
  List<(IconData, String, String)> _cats(BuildContext context, ThemeCubit theme, CurrencyCubit currency, BranchCubit branch) {
    final print = context.watch<PrintSettingsCubit>();
    final pos = context.watch<PosSettingsCubit>();
    final auth = context.watch<AuthCubit>();
    final user = auth.user;
    // Count only what the permissions screen lists — the app's own gates —
    // not every backend permission the account happens to hold.
    final permCount = mobilePermissions.where((m) => auth.hasPermission(m.slug)).length;
    final sel = branch.selected;
    return [
      // Preset, brightness and typeface are one subject — how the app looks —
      // so they are one category with three sections in the panel, not three
      // entries in the rail. "Appearance" is now the group; the brightness
      // switch inside it is "Light & dark".
      (Icons.palette_outlined, 'Appearance',
          '${theme.preset.name} · ${theme.mode.label} · ${theme.chrome.label}'),
      (Icons.receipt_long_outlined, 'Printer & receipt', '${print.style.label} · ${print.width.label}'),
      (Icons.point_of_sale_outlined, 'Sale Configuration',
          '${pos.askClientOnNewSale ? 'Asks for client' : 'No client prompt'} · ${_tipShown(context) ? 'tip on' : 'tip off'} · ${pos.lockAfterSale ? 'locks after sale' : 'stays unlocked'}'),
      (startScreenIcon(_effectiveStartScreen(context)), 'Start screen',
          'Opens on ${_effectiveStartScreen(context).label}'),
      (Icons.verified_user_outlined, 'My permissions', (user?.isAdmin ?? false) ? 'Administrator' : '$permCount granted'),
      // Haptics, currency, branch, offline data and the server are all "how
      // this device is set up" — plumbing you visit once and leave alone, not
      // five separate places. Grouped, the rail is a short list of subjects
      // instead of a scroll of knobs.
      (Icons.tune, 'System configuration', '${currency.currency.code} · ${sel == null ? 'No branch' : sel.name}'),
    ];
  }

  IconData _modeIcon(AstraMode m) => switch (m) {
        AstraMode.light => Icons.light_mode_outlined,
        AstraMode.dark => Icons.dark_mode_outlined,
        AstraMode.system => Icons.brightness_auto_outlined,
      };

  Widget _tabletTwoPane(BuildContext context, ThemeCubit theme, CurrencyCubit currency, BranchCubit branch) {
    final cats = _cats(context, theme, currency, branch);
    final sel = _sel.clamp(0, cats.length - 1);
    return LayoutBuilder(
      builder: (ctx, c) => _twoPaneRow(context, cats, sel, theme, currency, branch, TabletMetrics.forWidth(c.maxWidth)),
    );
  }

  Widget _twoPaneRow(BuildContext context, List<(IconData, String, String)> cats, int sel, ThemeCubit theme,
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
                    for (var i = 0; i < cats.length; i++)
                      _navTile(context, cats[i].$1, cats[i].$2, cats[i].$3,
                          active: i == sel, onTap: () => setState(() => _sel = i)),
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
            child: MaxWidthBox(maxWidth: 620, child: _panel(context, sel, theme, currency, branch)),
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

  /// The right detail panel for the selected category. Simple settings (mode,
  /// haptics) get real inline controls; pickers reuse their existing sheet/route
  /// so nothing loses functionality — just presented as a tablet detail pane.
  Widget _panel(BuildContext context, int i, ThemeCubit theme, CurrencyCubit currency, BranchCubit branch) {
    switch (i) {
      case 0:
        return _panelShell(context, Icons.palette_outlined, 'Appearance', 'Colour, brightness and type — how the app looks.', [
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
          _panelSection(context, 'Window', 'Where the rail sits, and how the page is framed beside it.'),
          for (final c in AstraChrome.values) _chromeRow(context, theme, c),
        ]);
      case 1:
        // The real screen, hosted in the pane — not a summary card behind a
        // button that throws the whole window away to show it. Settings on a
        // tablet is one place you stay in; a category that pushes a page reads
        // as leaving, and losing the rail to change a paper width is a poor
        // trade. Phones still push it: there is no second pane to host it in.
        return _panelShell(context, Icons.receipt_long_outlined, 'Printer & receipt',
            'Receipt style, paper width and print options.', const [
          PrintSettingsScreen(embedded: true),
        ]);
      case 2:
        final pos = context.watch<PosSettingsCubit>();
        // One category for the whole selling flow — opening the ticket, laying
        // the catalog out, and what the till does once the sale is charged. As
        // two they read as unrelated, and "Shared till" hid a switch every
        // counter has an opinion about behind a name only some of them own.
        return _panelShell(context, Icons.point_of_sale_outlined, 'Sale Configuration',
            'How this till rings a sale — from opening the ticket to locking up after it.', [
          _askClientCard(context),
          const SizedBox(height: 11),
          _tipCard(context),
          const SizedBox(height: 11),
          // "Products per row" is deliberately absent here — see the phone list.
          // A tablet takes its column count from the width the catalog is given,
          // and the preference is only ever a floor, so a picker on this screen
          // would set a number the grid then overrides.
          _toggleRow(
              context,
              'Lock after each sale',
              pos.lockAfterSale
                  ? 'On — MPIN or fingerprint to carry on; the session stays open'
                  : 'Off — this device stays unlocked between sales',
              pos.lockAfterSale,
              () => context.read<PosSettingsCubit>().toggleLockAfterSale()),
        ]);
      case 3:
        // No `final` locals here: every case shares the switch's one scope, and
        // case 2 already holds `pos`.
        return _panelShell(context, startScreenIcon(_effectiveStartScreen(context)), 'Start screen',
            'Where this device opens once you sign in — and after an unlock.', [
          for (final option in StartScreen.optionsFor(canViewDashboard: _canViewDashboard(context)))
            _startScreenRow(context, option, option == _effectiveStartScreen(context)),
        ]);
      case 4:
        // The real list, hosted in the pane — same reasoning as the printer
        // category above. Swapping the whole shell to the permissions
        // destination threw the settings rail away just to read a list of
        // gates, and there was no way back to the category you came from.
        return _panelShell(context, Icons.verified_user_outlined, 'My permissions',
            'What this account is allowed to do.', const [
          PermissionsScreen(embedded: true),
        ]);
      default:
        final haptics = context.watch<HapticsCubit>();
        return _panelShell(context, Icons.tune, 'System configuration',
            'How this device is set up — feedback, money, branch, offline data and the server behind it.', [
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
          // panel that changes on its own, and the rail no longer carries it.
          _panelSection(context, 'Offline data', _offlineSummary(_syncState)),
          const OfflineDataScreen(embedded: true),
          _panelSection(context, 'Server connection', 'The API base URL and tenant this app talks to.'),
          AstraButton(label: 'Connection settings', icon: Icons.cloud_outlined, onTap: () => ConnectionSheet.show(context)),
        ]);
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
          'Turned off for the business in Sale Configuration on the web.');
    }
    return _toggleRow(
      context,
      'Ask for a tip',
      pos.showTip
          ? 'On — the tip row shows at Review & Pay'
          : 'Off — hidden on this device; other tills are unaffected',
      pos.showTip,
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
  Widget _staticRow(BuildContext context, String title, String sub) {
    final p = context.astra;
    return AstraCard(
      radius: 14,
      child: Row(
        children: [
          Expanded(
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Text(title, style: ui(size: 13.5, weight: FontWeight.w700, color: p.textMuted)),
                Text(sub, style: ui(size: 11, weight: FontWeight.w600, color: p.textMuted)),
              ],
            ),
          ),
          Icon(Icons.lock_outline, size: 15, color: p.textMuted),
        ],
      ),
    );
  }

  Widget _toggleRow(BuildContext context, String title, String sub, bool value, VoidCallback onTap) {
    final p = context.astra;
    return GestureDetector(
      onTap: onTap,
      child: AstraCard(
        radius: 14,
        child: Row(
          children: [
            Expanded(
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  Text(title, style: ui(size: 13.5, weight: FontWeight.w700, color: p.ink)),
                  Text(sub, style: ui(size: 11, weight: FontWeight.w600, color: p.textMuted)),
                ],
              ),
            ),
            _switch(context, value),
          ],
        ),
      ),
    );
  }

  Widget _serverCard(BuildContext context) {
    final p = context.astra;
    return AstraCard(
      radius: 14,
      onTap: () => ConnectionSheet.show(context),
      child: Row(
        children: [
          IconChip(icon: Icons.cloud_outlined, size: 28, radius: 8, bg: p.tint),
          const SizedBox(width: 11),
          Expanded(child: Text('Server connection', style: ui(size: 12.5, weight: FontWeight.w700, color: p.ink))),
          Icon(Icons.chevron_right, color: p.textMuted, size: 18),
        ],
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

  Widget _offlineDataCard(BuildContext context) {
    if (!_hasSync) return _offlineDataTile(context, const OfflineSyncState());
    // Resolved from the locator rather than the widget tree: OfflineSyncCubit is
    // an app-wide singleton that is deliberately not in the MultiBlocProvider,
    // the same way the offline banner reaches it.
    return BlocBuilder<OfflineSyncCubit, OfflineSyncState>(
      bloc: serviceLocator<OfflineSyncCubit>(),
      buildWhen: (a, b) =>
          a.catalogSyncedAt != b.catalogSyncedAt ||
          a.catalogRefreshing != b.catalogRefreshing ||
          a.hasCatalog != b.hasCatalog ||
          a.provisionIncomplete != b.provisionIncomplete,
      builder: (context, sync) => _offlineDataTile(context, sync),
    );
  }

  Widget _offlineDataTile(BuildContext context, OfflineSyncState sync) {
    final p = context.astra;
    final unready = !sync.hasCatalog || sync.provisionIncomplete.isNotEmpty;
    return AstraCard(
      radius: 14,
      onTap: () => context.push(Routes.offlineData),
      child: Row(
        children: [
          IconChip(
            icon: sync.hasCatalog ? Icons.cloud_done_outlined : Icons.cloud_off_outlined,
            size: 34,
            radius: 9,
            bg: unready ? AstraPalette.danger.withValues(alpha: 0.14) : p.tint,
            fg: unready ? AstraPalette.danger : null,
          ),
          const SizedBox(width: 11),
          Expanded(
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Text('Offline data', style: ui(size: 12.5, weight: FontWeight.w700, color: p.ink)),
                Text(_offlineSummary(sync),
                    maxLines: 1,
                    overflow: TextOverflow.ellipsis,
                    style: ui(
                      size: 10,
                      weight: FontWeight.w600,
                      color: unready ? AstraPalette.danger : p.textMuted,
                    )),
              ],
            ),
          ),
          Icon(Icons.chevron_right, color: p.textMuted, size: 18),
        ],
      ),
    );
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
      child: Text('QLOUD POS · v1.0.0',
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

  Widget _appearanceCard(BuildContext context, ThemeCubit theme) {
    final p = context.astra;
    final mode = theme.mode;
    final icon = switch (mode) {
      AstraMode.light => Icons.light_mode_outlined,
      AstraMode.dark => Icons.dark_mode_outlined,
      AstraMode.system => Icons.brightness_auto_outlined,
    };
    final subtitle = mode == AstraMode.system
        ? 'System · ${theme.isDark ? 'Dark' : 'Light'}'
        : mode.label;
    return AstraCard(
      radius: 14,
      onTap: () => showAppearanceSheet(context),
      child: Row(
        children: [
          IconChip(icon: icon, size: 34, radius: 9, bg: p.tint),
          const SizedBox(width: 11),
          Expanded(
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Text('Light & dark', style: ui(size: 12.5, weight: FontWeight.w700, color: p.ink)),
                Text(subtitle, style: ui(size: 10, weight: FontWeight.w600, color: p.textMuted)),
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

  Widget _hapticsCard(BuildContext context) {
    final p = context.astra;
    final haptics = context.watch<HapticsCubit>();
    final on = haptics.enabled;
    return AstraCard(
      radius: 14,
      onTap: () => context.read<HapticsCubit>().toggle(),
      child: Row(
        children: [
          IconChip(
            icon: on ? Icons.vibration : Icons.smartphone_outlined,
            size: 34,
            radius: 9,
            bg: p.tint,
          ),
          const SizedBox(width: 11),
          Expanded(
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Text('Haptics', style: ui(size: 12.5, weight: FontWeight.w700, color: p.ink)),
                Text(on ? 'Vibration feedback on tap' : 'Vibration feedback off',
                    style: ui(size: 10, weight: FontWeight.w600, color: p.textMuted)),
              ],
            ),
          ),
          _switch(context, on),
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
  /// photos wants three or four across; two-up is for a catalog of images.
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

  /// True when this account may open the home shell — the dashboard and the
  /// Sales tab both live inside it (see the router's redirect).
  bool _canViewDashboard(BuildContext context) =>
      context.watch<AuthCubit>().hasPermission(PermissionSlug.salesOverview);

  /// What the device will *actually* open on. The stored choice normally, but a
  /// till set to the dashboard that a cashier without the permission signs into
  /// lands on New Sale instead — so say New Sale, rather than showing a setting
  /// that doesn't hold.
  StartScreen _effectiveStartScreen(BuildContext context) {
    final chosen = context.watch<PosSettingsCubit>().startScreen;
    return chosen.needsDashboard && !_canViewDashboard(context) ? StartScreen.sale : chosen;
  }

  /// Where signing in lands. Device-local, so the counter tablet can open
  /// straight on the POS while the same manager's phone still opens on the
  /// dashboard — it takes effect on the next sign-in or unlock.
  Widget _startScreenCard(BuildContext context) {
    final p = context.astra;
    final start = _effectiveStartScreen(context);
    return AstraCard(
      radius: 14,
      onTap: () => showStartScreenSheet(context),
      child: Row(
        children: [
          IconChip(icon: startScreenIcon(start), size: 34, radius: 9, bg: p.tint),
          const SizedBox(width: 11),
          Expanded(
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Text('Start screen',
                    style: ui(size: 12.5, weight: FontWeight.w700, color: p.ink)),
                Text('Opens on ${start.label} after sign-in',
                    style: ui(size: 10, weight: FontWeight.w600, color: p.textMuted)),
              ],
            ),
          ),
          Icon(Icons.chevron_right, size: 18, color: p.textMuted),
        ],
      ),
    );
  }

  /// An inline start-screen option (tablet detail pane), click-and-go like the
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

  Widget _printerCard(BuildContext context) {
    final p = context.astra;
    final print = context.watch<PrintSettingsCubit>();
    return AstraCard(
      radius: 14,
      onTap: () => context.push(Routes.printSettings),
      child: Row(
        children: [
          IconChip(icon: Icons.receipt_long_outlined, size: 34, radius: 9, bg: p.tint),
          const SizedBox(width: 11),
          Expanded(
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Text('Printer & receipt', style: ui(size: 12.5, weight: FontWeight.w700, color: p.ink)),
                Text('${print.style.label} · ${print.width.label}',
                    style: ui(size: 10, weight: FontWeight.w600, color: p.textMuted)),
              ],
            ),
          ),
          Icon(Icons.chevron_right, color: p.textMuted, size: 18),
        ],
      ),
    );
  }

  Widget _permissionsCard(BuildContext context) {
    final p = context.astra;
    final auth = context.watch<AuthCubit>();
    final user = auth.user;
    final count = mobilePermissions.where((m) => auth.hasPermission(m.slug)).length;
    final subtitle = (user?.isAdmin ?? false)
        ? 'Administrator · full access'
        : '$count of ${mobilePermissions.length} ${count == 1 ? 'permission' : 'permissions'} granted';
    return AstraCard(
      radius: 14,
      onTap: _openPermissions,
      child: Row(
        children: [
          IconChip(icon: Icons.verified_user_outlined, size: 34, radius: 9, bg: p.tint),
          const SizedBox(width: 11),
          Expanded(
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Text('My permissions', style: ui(size: 12.5, weight: FontWeight.w700, color: p.ink)),
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
