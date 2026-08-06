import 'package:flutter/material.dart';
import 'package:go_router/go_router.dart';
import 'package:provider/provider.dart';

import 'package:invo/shared/domain/helpers/responsive.dart';
import 'package:invo/features/auth/logic/auth_cubit/auth_cubit.dart';
import 'package:invo/shared/logic/branch_cubit/branch_cubit.dart';
import 'package:invo/shared/logic/currency_cubit/currency_cubit.dart';
import 'package:invo/features/settings/logic/pos_settings_cubit/pos_settings_cubit.dart';
import 'package:invo/features/settings/logic/print_settings_cubit/print_settings_cubit.dart';
import 'package:invo/shared/logic/haptics_cubit/haptics_cubit.dart';
import 'package:invo/shared/logic/theme_cubit/theme_cubit.dart';
import 'package:invo/shared/utils/components/theme/index.dart';
import 'package:invo/shared/utils/router/routes.dart';
import 'package:invo/shared/widgets/astra_side_rail.dart';
import 'package:invo/shared/widgets/astra_widgets.dart';
import 'package:invo/shared/widgets/tablet_widgets.dart';
import 'package:invo/features/auth/widgets/v3/connection_sheet.dart';
import 'package:invo/features/settings/widgets/v3/appearance_sheet.dart';
import 'package:invo/features/settings/widgets/v3/branch_sheet.dart';
import 'package:invo/features/settings/widgets/v3/currency_sheet.dart';
import 'package:invo/features/settings/widgets/v3/theme_sheet.dart';
import 'package:invo/features/settings/widgets/v3/typography_sheet.dart';

class SettingsScreen extends StatefulWidget {
  const SettingsScreen({super.key, this.onSelectTab});

  /// Switches the shell to another destination when this screen is inside it —
  /// see [_openPermissions].
  final ValueChanged<int>? onSelectTab;
  @override
  State<SettingsScreen> createState() => _SettingsScreenState();
}

class _SettingsScreenState extends State<SettingsScreen> {
  /// Tablet two-pane only: which settings category is open in the right panel.
  /// Ignored on phones, which keep the single scrolling card list.
  int _sel = 0;

  /// My Permissions is a shell destination on a tablet, so open it the same
  /// instant-swap way the rail and the dashboard tiles do. Pushing the route
  /// would slide a second copy over the shell.
  void _openPermissions() => context.isTablet && widget.onSelectTab != null
      ? widget.onSelectTab!(kPermissionsTab)
      : context.push(Routes.permissions);

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
                        _permissionsCard(context),
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
    final haptics = context.watch<HapticsCubit>();
    final user = context.watch<AuthCubit>().user;
    final permCount = user?.permissions.length ?? 0;
    final sel = branch.selected;
    return [
      (Icons.palette_outlined, 'Colour preset', theme.preset.name),
      (_modeIcon(theme.mode), 'Appearance', theme.mode == AstraMode.system ? 'System · ${theme.isDark ? 'Dark' : 'Light'}' : theme.mode.label),
      (Icons.text_fields_outlined, 'Typography', theme.typeface.name),
      (haptics.enabled ? Icons.vibration : Icons.smartphone_outlined, 'Haptics', haptics.enabled ? 'On' : 'Off'),
      (Icons.payments_outlined, 'Currency', currency.currency.code),
      (Icons.business, 'Branch', sel == null ? 'Not set' : sel.name),
      (Icons.receipt_long_outlined, 'Printer & receipt', '${print.style.label} · ${print.width.label}'),
      (pos.lockAfterSale ? Icons.lock_clock_outlined : Icons.lock_open_outlined, 'Shared till',
          pos.lockAfterSale ? 'Locks after each sale' : 'Stays unlocked'),
      (Icons.verified_user_outlined, 'My permissions', (user?.isAdmin ?? false) ? 'Administrator' : '$permCount granted'),
      (Icons.cloud_outlined, 'Server connection', 'Base URL & tenant'),
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
        return _panelShell(context, Icons.palette_outlined, 'Colour preset', 'The accent palette used across the app.', [
          _presetCard(context, theme),
          const SizedBox(height: 12),
          AstraButton(label: 'Choose a preset', icon: Icons.palette_outlined, onTap: () => showThemeSheet(context)),
        ]);
      case 1:
        return _panelShell(context, _modeIcon(theme.mode), 'Appearance', 'Light, dark, or follow the system setting.', [
          for (final m in AstraMode.values) _modeRow(context, theme, m),
        ]);
      case 2:
        return _panelShell(context, Icons.text_fields_outlined, 'Typography', 'The typeface the whole app is set in.', [
          _typographyCard(context, theme),
          const SizedBox(height: 12),
          AstraButton(label: 'Choose a typeface', icon: Icons.text_fields_outlined, onTap: () => showTypographySheet(context)),
        ]);
      case 3:
        final haptics = context.watch<HapticsCubit>();
        return _panelShell(context, haptics.enabled ? Icons.vibration : Icons.smartphone_outlined, 'Haptics', 'Vibration feedback on every tap.', [
          _toggleRow(context, 'Haptic feedback', haptics.enabled ? 'On — a tick on each tap' : 'Off', haptics.enabled,
              () => context.read<HapticsCubit>().toggle()),
        ]);
      case 4:
        return _panelShell(context, Icons.payments_outlined, 'Currency', 'Base currency and the rates used for conversion.', [
          _currencyCard(context, currency),
          const SizedBox(height: 12),
          AstraButton(label: 'Manage currencies', icon: Icons.tune, onTap: () => showCurrencySheet(context)),
        ]);
      case 5:
        return _panelShell(context, Icons.business, 'Branch', 'The branch this device bills against.', [
          _branchCard(context, branch),
          const SizedBox(height: 12),
          AstraButton(label: 'Switch branch', icon: Icons.swap_horiz, onTap: () => showBranchSheet(context)),
        ]);
      case 6:
        return _panelShell(context, Icons.receipt_long_outlined, 'Printer & receipt', 'Receipt style, paper width and print options.', [
          _printerCard(context),
          const SizedBox(height: 12),
          AstraButton(label: 'Open printer settings', icon: Icons.print_outlined, onTap: () => context.push(Routes.printSettings)),
        ]);
      case 7:
        final pos = context.watch<PosSettingsCubit>();
        return _panelShell(context, pos.lockAfterSale ? Icons.lock_clock_outlined : Icons.lock_open_outlined,
            'Shared till', 'For a counter more than one cashier serves from.', [
          _toggleRow(
              context,
              'Lock after each sale',
              pos.lockAfterSale
                  ? 'On — MPIN or fingerprint to carry on; the session stays open'
                  : 'Off — this device stays unlocked between sales',
              pos.lockAfterSale,
              () => context.read<PosSettingsCubit>().toggleLockAfterSale()),
        ]);
      case 8:
        return _panelShell(context, Icons.verified_user_outlined, 'My permissions', 'What this account is allowed to do.', [
          _permissionsCard(context),
          const SizedBox(height: 12),
          AstraButton(label: 'View permissions', icon: Icons.list_alt, onTap: _openPermissions),
        ]);
      default:
        return _panelShell(context, Icons.cloud_outlined, 'Server connection', 'The API base URL and tenant this app talks to.', [
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
      child: Text('Invo · Astra POS · v1.0.0',
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
                Text('Appearance', style: ui(size: 12.5, weight: FontWeight.w700, color: p.ink)),
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
    final user = context.watch<AuthCubit>().user;
    final count = user?.permissions.length ?? 0;
    final subtitle = (user?.isAdmin ?? false)
        ? 'Administrator · full access'
        : '$count ${count == 1 ? 'permission' : 'permissions'} granted';
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
