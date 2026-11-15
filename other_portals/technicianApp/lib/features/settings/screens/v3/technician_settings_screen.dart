import 'package:flutter/material.dart';
import 'package:go_router/go_router.dart';
import 'package:provider/provider.dart';

import 'package:invo/features/auth/logic/auth_cubit/auth_cubit.dart';
import 'package:invo/features/auth/widgets/v3/connection_sheet.dart';
import 'package:invo/features/profile/screens/v3/change_password_screen.dart';
import 'package:invo/features/profile/screens/v3/change_pin_screen.dart';
import 'package:invo/features/profile/screens/v3/edit_profile_screen.dart';
import 'package:invo/features/profile/widgets/v3/account_overview.dart';
import 'package:invo/features/settings/widgets/v3/appearance_sheet.dart';
import 'package:invo/features/settings/widgets/v3/theme_sheet.dart';
import 'package:invo/features/settings/widgets/v3/typography_sheet.dart';
import 'package:invo/shared/domain/constants/app_info.dart';
import 'package:invo/shared/domain/helpers/responsive.dart';
import 'package:invo/shared/domain/models/index.dart';
import 'package:invo/shared/logic/haptics_cubit/haptics_cubit.dart';
import 'package:invo/shared/logic/theme_cubit/theme_cubit.dart';
import 'package:invo/shared/utils/components/theme/index.dart';
import 'package:invo/shared/utils/router/routes.dart';
import 'package:invo/shared/widgets/astra_side_rail.dart';
import 'package:invo/shared/widgets/astra_widgets.dart';
import 'package:invo/shared/widgets/tablet_widgets.dart';

/// Technician settings, built on the POS app's settings screen: a single card
/// list on phones, a true two-pane on tablets (category nav left, the live
/// detail pane right, controls inline — nothing pushes a page on a tablet).
///
/// Categories are the ones a technician actually has: account, appearance,
/// security, this device (haptics + server) and about. The POS-only ones
/// (New Sale, products per row, printer, offline data, currency, branch,
/// permissions, start screen) are deliberately absent.
class TechnicianSettingsScreen extends StatefulWidget {
  const TechnicianSettingsScreen({super.key, this.onSelectTab});

  /// Switches the shell to another destination (Account → My Profile on a
  /// tablet). A screen inside the shell must use this, never `go('/home')`.
  final ValueChanged<int>? onSelectTab;

  @override
  State<TechnicianSettingsScreen> createState() => _TechnicianSettingsScreenState();
}

class _TechnicianSettingsScreenState extends State<TechnicianSettingsScreen> {
  /// Tablet only: the open category.
  int _sel = 0;

  /// Bumped when an embedded security form finishes, so it rebuilds empty.
  int _securityRound = 0;

  /// Tablet Account category: the overview, or one of its forms in-pane.
  _AccountView _account = _AccountView.overview;

  void _openProfile() => context.isTablet && widget.onSelectTab != null
      ? widget.onSelectTab!(kProfileTab)
      : context.push(Routes.profile);

  @override
  Widget build(BuildContext context) {
    final theme = context.watch<ThemeCubit>();
    final user = context.select<AuthCubit, ApiUser?>((c) => c.user);
    return Scaffold(
      backgroundColor: Colors.transparent,
      body: AstraBackground(
        child: context.isTablet
            ? SafeArea(bottom: false, child: _tabletTwoPane(context, theme, user))
            : Column(children: [
                const EmeraldHeader(title: 'Settings'),
                Expanded(
                  child: MaxWidthBox(
                    maxWidth: 560,
                    child: ListView(
                      padding: const EdgeInsets.fromLTRB(16, 16, 16, 120),
                      children: [
                        _profileCard(context, user),
                        const SizedBox(height: 14),
                        for (final c in [
                          _presetCard(context, theme),
                          _modeCard(context, theme),
                          _typographyCard(context, theme),
                          _hapticsCard(context),
                          _linkCard(context, Icons.pin_outlined, 'Change PIN', 'Update your login MPIN',
                              () => context.push(Routes.changePin)),
                          _linkCard(context, Icons.lock_outline, 'Change password', 'Update your account password',
                              () => context.push(Routes.changePassword)),
                          _serverCard(context),
                        ]) ...[c, const SizedBox(height: 11)],
                        _logoutCard(context),
                        const SizedBox(height: 14),
                        _versionLine(context),
                      ],
                    ),
                  ),
                ),
              ]),
      ),
    );
  }

  // ---- Tablet two-pane --------------------------------------------------------

  List<(IconData, String, String)> _cats(ThemeCubit theme, ApiUser? user) {
    final auth = context.read<AuthCubit>();
    final haptics = context.watch<HapticsCubit>().enabled;
    return [
      (Icons.person_outline, 'Account', user?.name ?? 'Signed in'),
      (Icons.palette_outlined, 'Appearance', '${theme.preset.name} · ${theme.mode.label} · ${theme.chrome.label}'),
      (Icons.lock_outline, 'Security', 'MPIN & password'),
      (Icons.tune, 'This device', '${haptics ? 'Haptics on' : 'Haptics off'} · ${_host(auth.config.baseUrl)}'),
      (Icons.info_outline, 'About', '${AppInfo.name} ${AppInfo.version}'),
    ];
  }

  String _host(String url) {
    final h = Uri.tryParse(url)?.host ?? '';
    return h.isEmpty ? url : h;
  }

  Widget _tabletTwoPane(BuildContext context, ThemeCubit theme, ApiUser? user) {
    final cats = _cats(theme, user);
    final sel = _sel.clamp(0, cats.length - 1);
    return LayoutBuilder(builder: (ctx, c) {
      final m = TabletMetrics.forWidth(c.maxWidth);
      return Row(crossAxisAlignment: CrossAxisAlignment.stretch, children: [
        TabletPane(
          width: m.settingsNav,
          child: Column(children: [
            const TabletPaneHead(title: 'Settings', subtitle: 'Device and account preferences'),
            Expanded(
              child: ListView(
                padding: const EdgeInsets.fromLTRB(12, 12, 12, 24),
                children: [
                  for (var i = 0; i < cats.length; i++)
                    _navTile(context, cats[i].$1, cats[i].$2, cats[i].$3,
                        active: i == sel,
                        onTap: () => setState(() {
                              _sel = i;
                              _account = _AccountView.overview;
                            })),
                  const SizedBox(height: 16),
                  _logoutCard(context),
                  const SizedBox(height: 14),
                  _versionLine(context),
                ],
              ),
            ),
          ]),
        ),
        Expanded(
          child: SingleChildScrollView(
            padding: m.detailPadding.copyWith(top: 24, bottom: 40),
            child: MaxWidthBox(
              // The account record is a grid and gets the wider cap; forms and
              // the other categories stay a readable column.
              maxWidth: sel == 0 && _account == _AccountView.overview ? 880 : 620,
              child: astraPaneSwitcher(
                child: KeyedSubtree(key: ValueKey('$sel-${_account.name}'), child: _panel(context, sel, theme, user)),
              ),
            ),
          ),
        ),
      ]);
    });
  }

  Widget _navTile(BuildContext context, IconData icon, String title, String sub,
      {required bool active, required VoidCallback onTap}) {
    final p = context.astra;
    return GestureDetector(
      behavior: HitTestBehavior.opaque,
      onTap: onTap,
      child: AnimatedContainer(
        duration: AstraMotion.fast,
        curve: AstraMotion.curve,
        margin: const EdgeInsets.only(bottom: 6),
        padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 12),
        decoration: BoxDecoration(color: active ? p.tint : Colors.transparent, borderRadius: BorderRadius.circular(14)),
        child: Row(children: [
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
            child: Column(crossAxisAlignment: CrossAxisAlignment.start, children: [
              Text(title, style: ui(size: 13, weight: active ? FontWeight.w800 : FontWeight.w700, color: p.ink)),
              Text(sub,
                  maxLines: 1, overflow: TextOverflow.ellipsis, style: ui(size: 10.5, weight: FontWeight.w600, color: p.textMuted)),
            ]),
          ),
        ]),
      ),
    );
  }

  /// The detail pane for category [i]. Simple settings get real inline
  /// controls; pickers reuse their sheet behind an action button.
  Widget _panel(BuildContext context, int i, ThemeCubit theme, ApiUser? user) {
    final p = context.astra;
    final auth = context.read<AuthCubit>();
    switch (i) {
      case 0:
        void back() => setState(() => _account = _AccountView.overview);
        if (_account != _AccountView.overview) {
          final (title, desc, form) = switch (_account) {
            _AccountView.edit => ('Edit profile', 'Name, phone, email & photo.', EditProfileScreen(embedded: true, onDone: back)),
            _AccountView.pin => ('Change MPIN', 'Your 4–6 digit login PIN.', ChangePinScreen(embedded: true, onDone: back) as Widget),
            _ => ('Change password', 'Your account login password.', ChangePasswordScreen(embedded: true, onDone: back) as Widget),
          };
          return Column(crossAxisAlignment: CrossAxisAlignment.start, children: [
            GestureDetector(
              onTap: back,
              behavior: HitTestBehavior.opaque,
              child: Padding(
                padding: const EdgeInsets.only(bottom: 14),
                child: Row(mainAxisSize: MainAxisSize.min, children: [
                  Icon(Icons.chevron_left, size: 18, color: p.primary),
                  Text('Account', style: ui(size: 12.5, weight: FontWeight.w800, color: p.primary)),
                ]),
              ),
            ),
            _panelShell(context, Icons.person_outline, title, desc, [form]),
          ]);
        }
        return _panelShell(context, Icons.person_outline, 'Account', 'Who is signed in on this device.', [
          AccountOverview(
            onEditProfile: () => setState(() => _account = _AccountView.edit),
            onChangePin: () => setState(() => _account = _AccountView.pin),
            onChangePassword: () => setState(() => _account = _AccountView.password),
          ),
        ]);
      case 1:
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
      case 2:
        void reset() => setState(() => _securityRound++);
        return _panelShell(context, Icons.lock_outline, 'Security', 'Change how you sign in.', [
          _panelSection(context, 'MPIN', 'The 4–6 digit PIN you sign in with.', top: 0),
          ChangePinScreen(key: ValueKey('pin$_securityRound'), embedded: true, onDone: reset),
          _panelSection(context, 'Password', 'Your account password, for credential sign-in.'),
          ChangePasswordScreen(key: ValueKey('pw$_securityRound'), embedded: true, onDone: reset),
        ]);
      case 3:
        final haptics = context.watch<HapticsCubit>();
        final cfg = auth.config;
        return _panelShell(context, Icons.tune, 'This device', 'Feedback, and the server this app talks to.', [
          _panelSection(context, 'Haptics', 'Vibration feedback on every tap.', top: 0),
          _toggleRow(context, 'Haptic feedback', haptics.enabled ? 'On — a tick on each tap' : 'Off', haptics.enabled,
              () => context.read<HapticsCubit>().toggle()),
          _panelSection(context, 'Server connection', 'The API base URL and tenant this app talks to.'),
          TabletPanel(
            title: 'Connected to',
            child: Column(children: [
              _kv(context, Icons.cloud_outlined, 'Server', cfg.baseUrl, divider: false),
              _kv(context, Icons.apartment_outlined, 'Tenant', cfg.tenant.isEmpty ? '—' : cfg.tenant),
            ]),
          ),
          const SizedBox(height: 12),
          AstraButton(label: 'Connection settings', icon: Icons.cloud_outlined, onTap: () => ConnectionSheet.show(context)),
        ]);
      default:
        return _panelShell(context, Icons.info_outline, 'About', 'This build of the app.', [
          TabletPanel(
            title: AppInfo.name,
            child: Column(children: [
              _kv(context, Icons.handyman_outlined, 'App', '${AppInfo.name} · ${AppInfo.tagline}', divider: false),
              _kv(context, Icons.new_releases_outlined, 'Version', AppInfo.version),
              _kv(context, Icons.person_outline, 'Signed in as', user?.name ?? '—'),
            ]),
          ),
          const SizedBox(height: 16),
          Text('Need help? Contact your maintenance coordinator.',
              style: ui(size: 12, weight: FontWeight.w600, color: p.textMuted)),
        ]);
    }
  }

  Widget _panelShell(BuildContext context, IconData icon, String title, String desc, List<Widget> children) {
    final p = context.astra;
    return Column(crossAxisAlignment: CrossAxisAlignment.start, children: [
      Row(children: [
        IconChip(icon: icon, size: 44, radius: 14, bg: p.tint),
        const SizedBox(width: 13),
        Expanded(
          child: Column(crossAxisAlignment: CrossAxisAlignment.start, children: [
            Text(title, style: serif(size: 22, color: p.ink)),
            const SizedBox(height: 2),
            Text(desc, style: ui(size: 12, weight: FontWeight.w600, color: p.textMuted)),
          ]),
        ),
      ]),
      const SizedBox(height: 20),
      ...children,
    ]);
  }

  /// A labelled rule between sections of one panel; the first passes `top: 0`.
  Widget _panelSection(BuildContext context, String title, String desc, {double top = 22}) {
    final p = context.astra;
    return Container(
      margin: EdgeInsets.only(top: top),
      padding: EdgeInsets.only(top: top > 0 ? 18 : 0, bottom: 12),
      decoration: top > 0 ? BoxDecoration(border: Border(top: BorderSide(color: p.hairline))) : null,
      child: Column(crossAxisAlignment: CrossAxisAlignment.start, children: [
        Text(title.toUpperCase(), style: ui(size: 10.5, weight: FontWeight.w800, color: p.textMuted, letterSpacing: 1.1)),
        const SizedBox(height: 3),
        Text(desc, style: ui(size: 12, weight: FontWeight.w600, color: p.textSecondary)),
      ]),
    );
  }

  Widget _kv(BuildContext context, IconData icon, String label, String value, {bool divider = true}) {
    final p = context.astra;
    return Container(
      decoration: divider ? BoxDecoration(border: Border(top: BorderSide(color: p.hairline))) : null,
      padding: const EdgeInsets.symmetric(horizontal: 4, vertical: 11),
      child: Row(children: [
        Icon(icon, size: 16, color: p.textMuted),
        const SizedBox(width: 11),
        Text(label, style: ui(size: 12.5, weight: FontWeight.w600, color: p.ink)),
        const SizedBox(width: 12),
        Expanded(
          child: Text(value,
              textAlign: TextAlign.right,
              maxLines: 1,
              overflow: TextOverflow.ellipsis,
              style: ui(size: 12, weight: FontWeight.w700, color: p.textSecondary)),
        ),
      ]),
    );
  }

  /// One window-chrome option drawn as a miniature of the layout it picks —
  /// the whole choice is a shape, so the row shows the shape. Applies on tap.
  Widget _chromeRow(BuildContext context, ThemeCubit theme, AstraChrome c) {
    final p = context.astra;
    final active = theme.chrome == c;
    return GestureDetector(
      behavior: HitTestBehavior.opaque,
      onTap: () => context.read<ThemeCubit>().setChrome(c),
      child: AnimatedContainer(
        duration: AstraMotion.fast,
        margin: const EdgeInsets.only(bottom: 10),
        padding: const EdgeInsets.all(12),
        decoration: BoxDecoration(
          color: active ? p.tint : p.card,
          borderRadius: BorderRadius.circular(14),
          border: Border.all(color: active ? p.primary : p.cardBorder, width: active ? 1.5 : 1),
        ),
        child: Row(children: [
          _chromeThumb(context, c),
          const SizedBox(width: 13),
          Expanded(
            child: Column(crossAxisAlignment: CrossAxisAlignment.start, children: [
              Text(c.label, style: ui(size: 12.5, weight: FontWeight.w700, color: p.ink)),
              const SizedBox(height: 2),
              Text(c.tagline, style: ui(size: 10.5, weight: FontWeight.w600, color: p.textMuted)),
            ]),
          ),
          if (active) Icon(Icons.check_circle, size: 20, color: p.primary),
        ]),
      ),
    );
  }

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
        borderRadius: c == AstraChrome.docked || c == AstraChrome.unified ? null : BorderRadius.circular(4),
      ),
    );
    final pad = switch (c) {
      AstraChrome.insetCanvas => const EdgeInsets.fromLTRB(3, 3, 3, 3),
      AstraChrome.peers => const EdgeInsets.fromLTRB(3, 0, 0, 0),
      _ => EdgeInsets.zero,
    };
    final inner = Row(children: [rail, Expanded(child: Padding(padding: pad, child: page))]);
    return Container(
      width: 56,
      height: 40,
      padding: switch (c) {
        AstraChrome.peers => const EdgeInsets.all(3),
        AstraChrome.unified => const EdgeInsets.all(4),
        _ => EdgeInsets.zero,
      },
      decoration: BoxDecoration(
        color: c == AstraChrome.insetCanvas ? p.darkSurface : p.canvas,
        borderRadius: BorderRadius.circular(9),
        border: Border.all(color: p.hairline),
      ),
      clipBehavior: Clip.antiAlias,
      child: c == AstraChrome.unified ? ClipRRect(borderRadius: BorderRadius.circular(6), child: inner) : inner,
    );
  }

  IconData _modeIcon(AstraMode m) => switch (m) {
        AstraMode.light => Icons.light_mode_outlined,
        AstraMode.dark => Icons.dark_mode_outlined,
        AstraMode.system => Icons.brightness_auto_outlined,
      };

  Widget _modeRow(BuildContext context, ThemeCubit theme, AstraMode m) {
    final p = context.astra;
    final active = theme.mode == m;
    return GestureDetector(
      behavior: HitTestBehavior.opaque,
      onTap: () => context.read<ThemeCubit>().setMode(m),
      child: AnimatedContainer(
        duration: AstraMotion.fast,
        margin: const EdgeInsets.only(bottom: 10),
        padding: const EdgeInsets.symmetric(horizontal: 14, vertical: 14),
        decoration: BoxDecoration(
          color: active ? p.tint : p.card,
          borderRadius: BorderRadius.circular(14),
          border: Border.all(color: active ? p.primary : p.cardBorder, width: active ? 1.5 : 1),
        ),
        child: Row(children: [
          Icon(_modeIcon(m), size: 20, color: active ? p.primary : p.textSecondary),
          const SizedBox(width: 12),
          Expanded(child: Text(m.label, style: ui(size: 14, weight: FontWeight.w700, color: p.ink))),
          if (active) Icon(Icons.check_circle_rounded, size: 20, color: p.primary),
        ]),
      ),
    );
  }

  Widget _toggleRow(BuildContext context, String title, String sub, bool value, VoidCallback onTap) {
    final p = context.astra;
    return AstraCard(
      radius: 14,
      onTap: onTap,
      child: Row(children: [
        Expanded(
          child: Column(crossAxisAlignment: CrossAxisAlignment.start, children: [
            Text(title, style: ui(size: 13.5, weight: FontWeight.w700, color: p.ink)),
            Text(sub, style: ui(size: 11, weight: FontWeight.w600, color: p.textMuted)),
          ]),
        ),
        _switch(context, value),
      ]),
    );
  }

  // ---- Shared cards (phone list + tablet panels) ----------------------------

  Widget _profileCard(BuildContext context, ApiUser? user) {
    final p = context.astra;
    final cfg = context.read<AuthCubit>().config;
    final name = user?.name ?? 'Technician';
    return AstraCard(
      radius: 18,
      onTap: _openProfile,
      child: Row(children: [
        ProfileAvatar(
          letter: name.isNotEmpty ? name[0].toUpperCase() : 'T',
          imageUrl: user != null && user.hasPhoto ? cfg.assetUrl(user.photoUrl) : null,
          headers: cfg.assetHeaders,
          size: 52,
        ),
        const SizedBox(width: 14),
        Expanded(
          child: Column(crossAxisAlignment: CrossAxisAlignment.start, children: [
            Text(name, maxLines: 1, overflow: TextOverflow.ellipsis, style: serif(size: 19, color: p.ink)),
            const SizedBox(height: 3),
            Text(
              [
                if ((user?.designation ?? '').isNotEmpty) user!.designation else 'Technician',
                if ((user?.code ?? '').isNotEmpty) 'Code ${user!.code}',
              ].join(' · '),
              style: ui(size: 11.5, weight: FontWeight.w600, color: p.textMuted),
            ),
          ]),
        ),
        Icon(Icons.chevron_right, color: p.textMuted, size: 18),
      ]),
    );
  }

  Widget _rowCard(BuildContext context,
      {required Widget leading, required String title, required String subtitle, VoidCallback? onTap, Widget? trailing}) {
    final p = context.astra;
    return AstraCard(
      radius: 14,
      onTap: onTap,
      child: Row(children: [
        leading,
        const SizedBox(width: 11),
        Expanded(
          child: Column(crossAxisAlignment: CrossAxisAlignment.start, children: [
            Text(title, style: ui(size: 12.5, weight: FontWeight.w700, color: p.ink)),
            Text(subtitle,
                maxLines: 1, overflow: TextOverflow.ellipsis, style: ui(size: 10, weight: FontWeight.w600, color: p.textMuted)),
          ]),
        ),
        trailing ?? Icon(Icons.chevron_right, color: p.textMuted, size: 18),
      ]),
    );
  }

  Widget _linkCard(BuildContext context, IconData icon, String title, String subtitle, VoidCallback onTap) =>
      _rowCard(context,
          leading: IconChip(icon: icon, size: 34, radius: 9, bg: context.astra.tint),
          title: title,
          subtitle: subtitle,
          onTap: onTap);

  Widget _presetCard(BuildContext context, ThemeCubit theme) {
    final p = context.astra;
    final preset = theme.preset;
    return _rowCard(
      context,
      onTap: () => showThemeSheet(context),
      title: 'Colour preset',
      subtitle: '${preset.name} · ${preset.tagline}',
      leading: SizedBox(
        width: 18.0 + 12 * 3,
        height: 34,
        child: Stack(alignment: Alignment.centerLeft, children: [
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
        ]),
      ),
    );
  }

  Widget _modeCard(BuildContext context, ThemeCubit theme) {
    final mode = theme.mode;
    return _linkCard(context, _modeIcon(mode), 'Light & dark',
        mode == AstraMode.system ? 'System · ${theme.isDark ? 'Dark' : 'Light'}' : mode.label, () => showAppearanceSheet(context));
  }

  Widget _typographyCard(BuildContext context, ThemeCubit theme) {
    final p = context.astra;
    final face = theme.typeface;
    return _rowCard(
      context,
      onTap: () => showTypographySheet(context),
      title: 'Typography',
      subtitle: '${face.name} · ${face.tagline}',
      leading: Container(
        width: 34,
        height: 34,
        alignment: Alignment.center,
        decoration: BoxDecoration(color: p.tint, borderRadius: BorderRadius.circular(9)),
        child: Text('Aa', style: face.displayStyle(size: 15, color: p.primary)),
      ),
    );
  }

  Widget _hapticsCard(BuildContext context) {
    final on = context.watch<HapticsCubit>().enabled;
    return _rowCard(
      context,
      onTap: () => context.read<HapticsCubit>().toggle(),
      title: 'Haptics',
      subtitle: on ? 'Vibration feedback on tap' : 'Vibration feedback off',
      leading: IconChip(icon: on ? Icons.vibration : Icons.smartphone_outlined, size: 34, radius: 9, bg: context.astra.tint),
      trailing: _switch(context, on),
    );
  }

  Widget _serverCard(BuildContext context) {
    final cfg = context.read<AuthCubit>().config;
    return _linkCard(context, Icons.cloud_outlined, 'Server connection',
        '${_host(cfg.baseUrl)}${cfg.tenant.isEmpty ? '' : ' · ${cfg.tenant}'}', () => ConnectionSheet.show(context));
  }

  Widget _switch(BuildContext context, bool value) {
    final p = context.astra;
    return AnimatedContainer(
      duration: AstraMotion.fast,
      width: 44,
      height: 26,
      padding: const EdgeInsets.all(3),
      alignment: value ? Alignment.centerRight : Alignment.centerLeft,
      decoration: BoxDecoration(
        gradient: value ? p.primaryGradient : null,
        color: value ? null : p.hairline,
        borderRadius: BorderRadius.circular(20),
      ),
      child: Container(width: 20, height: 20, decoration: const BoxDecoration(color: Colors.white, shape: BoxShape.circle)),
    );
  }

  Widget _logoutCard(BuildContext context) => GestureDetector(
        onTap: () async {
          if (await confirmLogout(context) && context.mounted) {
            await context.read<AuthCubit>().logout();
          }
        },
        child: AstraCard(
          radius: 14,
          child: Center(
            child: Row(mainAxisSize: MainAxisSize.min, children: [
              const Icon(Icons.logout, size: 16, color: AstraPalette.danger),
              const SizedBox(width: 8),
              Text('Log out', style: ui(size: 12.5, weight: FontWeight.w700, color: AstraPalette.danger)),
            ]),
          ),
        ),
      );

  Widget _versionLine(BuildContext context) => Center(
        child: Text('${AppInfo.name} · ${AppInfo.version}',
            style: ui(size: 10.5, weight: FontWeight.w600, color: context.astra.textMuted)),
      );
}

/// What the tablet Account category is showing.
enum _AccountView { overview, edit, pin, password }
