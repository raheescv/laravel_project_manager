import 'package:flutter/material.dart';
import 'package:go_router/go_router.dart';
import 'package:provider/provider.dart';

import 'package:invo/shared/domain/helpers/responsive.dart';
import 'package:invo/shared/domain/models/index.dart';
import 'package:invo/features/auth/logic/auth_cubit/auth_cubit.dart';
import 'package:invo/features/profile/screens/v3/change_password_screen.dart';
import 'package:invo/features/profile/screens/v3/change_pin_screen.dart';
import 'package:invo/features/profile/screens/v3/edit_profile_screen.dart';
import 'package:invo/shared/logic/branch_cubit/branch_cubit.dart';
import 'package:invo/shared/utils/components/theme/index.dart';
import 'package:invo/shared/utils/router/routes.dart';
import 'package:invo/shared/widgets/astra_side_rail.dart';
import 'package:invo/shared/widgets/astra_widgets.dart';
import 'package:invo/shared/widgets/tablet_widgets.dart';

/// My Profile — the signed-in user's identity, work context and security actions.
///
/// Phone keeps the gradient header band and pushes Edit / MPIN / Password as
/// their own routes. Tablet follows the pane vocabulary from
/// `docs/mobile-tablet-screens.html`: an identity + section pane on the left,
/// and a detail side on the right that *swaps* between the profile record and
/// the three account forms — nothing takes over the window, so the side-rail
/// and the identity stay visible throughout. The forms are the real screens,
/// rendered in their `embedded` mode.
class ProfileScreen extends StatefulWidget {
  const ProfileScreen({super.key, this.onSelectTab});

  /// Switches the shell to another destination when this screen is inside it
  /// (it is a destination itself on a tablet) — see [_openPermissions].
  final ValueChanged<int>? onSelectTab;

  @override
  State<ProfileScreen> createState() => _ProfileScreenState();
}

/// Which section the tablet detail pane is showing.
enum _Section { details, edit, pin, password }

class _ProfileScreenState extends State<ProfileScreen> {
  _Section _sel = _Section.details;

  /// My Permissions is a shell destination on a tablet, so open it with the
  /// same instant swap the rail and the dashboard tiles use; pushing the route
  /// from inside the shell would stack a second copy over it.
  void _openPermissions() => context.isTablet && widget.onSelectTab != null
      ? widget.onSelectTab!(kPermissionsTab)
      : context.push(Routes.permissions);

  @override
  Widget build(BuildContext context) {
    final user = context.select<AuthCubit, ApiUser?>((c) => c.state.user);
    if (user == null) return const Scaffold(body: Center(child: CircularProgressIndicator()));

    final cfg = context.read<AuthCubit>().config;
    final photoUrl = user.hasPhoto ? cfg.assetUrl(user.photoUrl) : null;

    final branchCtrl = context.watch<BranchCubit>();
    final match = branchCtrl.branches.where((b) => b.id.toString() == user.branchId);
    final branchName = match.isNotEmpty ? match.first.name : (branchCtrl.selected?.name ?? '—');

    return Scaffold(
      backgroundColor: Colors.transparent,
      body: AstraBackground(
        child: context.isTablet
            ? SafeArea(bottom: false, child: _tablet(context, user, photoUrl, cfg.assetHeaders, branchName))
            : _phone(context, user, photoUrl, cfg.assetHeaders, branchName),
      ),
    );
  }

  static const _sectionTitles = {
    _Section.details: ('Profile details', 'Your record as the system holds it'),
    _Section.edit: ('Edit profile', 'Name, phone, email & photo'),
    _Section.pin: ('Change MPIN', 'Your 4–6 digit login PIN'),
    _Section.password: ('Change password', 'Your account login password'),
  };

  String _role(ApiUser user) =>
      user.designation.isEmpty ? (user.isAdmin ? 'Administrator' : 'Staff') : user.designation;

  // ---------------------------------------------------------------- tablet --

  Widget _tablet(BuildContext context, ApiUser user, String? photoUrl, Map<String, String>? headers,
      String branchName) {
    final section = _sectionTitles[_sel]!;
    return LayoutBuilder(
      builder: (ctx, c) {
        final m = TabletMetrics.forWidth(c.maxWidth);
        return Row(
          crossAxisAlignment: CrossAxisAlignment.stretch,
          children: [
            TabletPane(
              width: m.settingsNav,
              child: Column(
                children: [
                  TabletPaneHead(
                    title: 'My Profile',
                    subtitle: 'Account & security',
                    // On a tablet this is normally a shell destination (swapped
                    // in by the dashboard avatar / drawer), so there is nothing
                    // to pop — only a pushed route gets a back affordance.
                    leading: context.canPop()
                        ? TabletIconButton(icon: Icons.chevron_left, onTap: () => context.pop())
                        : null,
                  ),
                  Expanded(
                    child: ListView(
                      padding: const EdgeInsets.fromLTRB(14, 16, 14, 28),
                      children: [
                        _identityCard(context, user, photoUrl, headers),
                        const SizedBox(height: 18),
                        const Padding(
                          padding: EdgeInsets.only(left: 2, bottom: 8),
                          child: SectionLabel('Account'),
                        ),
                        _navTile(context, Icons.person_outline, _Section.details),
                        _navTile(context, Icons.edit_outlined, _Section.edit),
                        _navTile(context, Icons.lock_outline, _Section.pin),
                        _navTile(context, Icons.password_outlined, _Section.password),
                      ],
                    ),
                  ),
                ],
              ),
            ),
            // The detail side names the open section and swaps its body — the
            // forms never take the window, so the identity pane and the rail
            // stay put while you edit.
            Expanded(
              child: Column(
                children: [
                  TabletPageHead(title: section.$1, subtitle: section.$2),
                  Expanded(
                    child: SingleChildScrollView(
                      padding: m.detailPadding.copyWith(top: 22, bottom: 40),
                      child: MaxWidthBox(maxWidth: 620, child: _detail(context, user, branchName)),
                    ),
                  ),
                ],
              ),
            ),
          ],
        );
      },
    );
  }

  Widget _detail(BuildContext context, ApiUser user, String branchName) {
    // Keyed so switching sections rebuilds the form fresh (seeded controllers,
    // cleared PIN boxes) instead of reusing the previous section's state.
    switch (_sel) {
      case _Section.edit:
        return EditProfileScreen(
            key: const ValueKey('edit'), embedded: true, onDone: () => setState(() => _sel = _Section.details));
      case _Section.pin:
        return ChangePinScreen(
            key: const ValueKey('pin'), embedded: true, onDone: () => setState(() => _sel = _Section.details));
      case _Section.password:
        return ChangePasswordScreen(
            key: const ValueKey('password'), embedded: true, onDone: () => setState(() => _sel = _Section.details));
      case _Section.details:
        return Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            TabletPanel(
              title: 'Personal',
              child: Column(
                children: [
                  _infoRow(context, Icons.person_outline, 'Full name', user.name, divider: false),
                  _infoRow(context, Icons.phone, 'Phone', user.mobile.isEmpty ? '—' : user.mobile),
                  _infoRow(context, Icons.mail_outline, 'Email', user.email.isEmpty ? '—' : user.email),
                ],
              ),
            ),
            const SizedBox(height: 16),
            TabletPanel(
              title: 'Work',
              child: Column(
                children: [
                  _infoRow(context, Icons.badge_outlined, 'Role', user.role.isEmpty ? _role(user) : user.role,
                      muted: true, divider: false),
                  _infoRow(context, Icons.location_on_outlined, 'Branch', branchName, muted: true),
                ],
              ),
            ),
            const SizedBox(height: 16),
            TabletPanel(
              title: 'Access',
              child: _infoRow(context, Icons.verified_user_outlined, 'Permissions',
                  user.isAdmin ? 'Administrator' : '${user.permissions.length} granted',
                  muted: true, divider: false, onTap: _openPermissions),
            ),
          ],
        );
    }
  }

  /// A left-pane section link — the same idiom as the settings category rail.
  Widget _navTile(BuildContext context, IconData icon, _Section section) {
    final p = context.astra;
    final active = _sel == section;
    final labels = _sectionTitles[section]!;
    return GestureDetector(
      onTap: () => setState(() => _sel = section),
      behavior: HitTestBehavior.opaque,
      child: Container(
        margin: const EdgeInsets.only(bottom: 6),
        padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 11),
        decoration: BoxDecoration(
            color: active ? p.tint : Colors.transparent, borderRadius: BorderRadius.circular(14)),
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
                  Text(labels.$1,
                      style: ui(size: 12.5, weight: active ? FontWeight.w800 : FontWeight.w700, color: p.ink)),
                  const SizedBox(height: 2),
                  Text(labels.$2,
                      maxLines: 1,
                      overflow: TextOverflow.ellipsis,
                      style: ui(size: 10.5, weight: FontWeight.w600, color: p.textMuted)),
                ],
              ),
            ),
          ],
        ),
      ),
    );
  }

  /// The left pane's hero — the gradient identity block, inset as a card since a
  /// tablet pane has no header band for it to bleed into.
  Widget _identityCard(BuildContext context, ApiUser user, String? photoUrl, Map<String, String>? headers) {
    final p = context.astra;
    return Container(
      clipBehavior: Clip.antiAlias,
      decoration: BoxDecoration(
        gradient: p.heroGradient,
        borderRadius: BorderRadius.circular(22),
        boxShadow: context.astraTheme.floatShadow(p.primary),
      ),
      child: Stack(
        children: [
          Positioned(
            right: -46,
            top: -56,
            child: Container(
              width: 190,
              height: 190,
              decoration: BoxDecoration(
                shape: BoxShape.circle,
                gradient: RadialGradient(colors: [p.accent.withValues(alpha: 0.22), Colors.transparent]),
              ),
            ),
          ),
          Padding(
            padding: const EdgeInsets.fromLTRB(16, 22, 16, 20),
            child: Column(
              children: [
                ProfileAvatar(
                  letter: user.initial,
                  imageUrl: photoUrl,
                  headers: headers,
                  size: 86,
                  fontSize: 34,
                ),
                const SizedBox(height: 12),
                Text(user.name,
                    textAlign: TextAlign.center,
                    maxLines: 2,
                    overflow: TextOverflow.ellipsis,
                    style: serif(size: 21, color: Colors.white)),
                const SizedBox(height: 4),
                Text(_role(user),
                    maxLines: 1,
                    overflow: TextOverflow.ellipsis,
                    style: ui(size: 11.5, weight: FontWeight.w600, color: Colors.white70)),
                const SizedBox(height: 11),
                Container(
                  padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 5),
                  decoration: BoxDecoration(
                    color: Colors.white.withValues(alpha: 0.12),
                    borderRadius: BorderRadius.circular(20),
                  ),
                  child: Row(
                    mainAxisSize: MainAxisSize.min,
                    children: [
                      Container(
                          width: 6,
                          height: 6,
                          decoration: const BoxDecoration(color: Color(0xFF7BE0A8), shape: BoxShape.circle)),
                      const SizedBox(width: 6),
                      Text('Active',
                          style: ui(size: 10.5, weight: FontWeight.w800, color: const Color(0xFF7BE0A8))),
                    ],
                  ),
                ),
              ],
            ),
          ),
        ],
      ),
    );
  }

  // ----------------------------------------------------------------- phone --

  Widget _phone(BuildContext context, ApiUser user, String? photoUrl, Map<String, String>? headers,
      String branchName) {
    final p = context.astra;
    return MaxWidthBox(
      maxWidth: 560,
      child: ListView(
        padding: EdgeInsets.zero,
        children: [
          Container(
            decoration: BoxDecoration(
              gradient: p.heroGradient,
              borderRadius: const BorderRadius.vertical(bottom: Radius.circular(30)),
            ),
            child: SafeArea(
              bottom: false,
              child: Padding(
                padding: const EdgeInsets.fromLTRB(16, 6, 16, 26),
                child: Column(
                  children: [
                    Row(
                      children: [
                        HeaderIconButton(icon: Icons.chevron_left, onTap: () => context.pop()),
                        Expanded(child: Center(child: Text('My Profile', style: serif(size: 18, color: Colors.white)))),
                        HeaderIconButton(icon: Icons.edit_outlined, gold: true, onTap: () => context.push(Routes.editProfile)),
                      ],
                    ),
                    const SizedBox(height: 14),
                    ProfileAvatar(
                      letter: user.initial,
                      imageUrl: photoUrl,
                      headers: headers,
                      size: 78,
                      fontSize: 32,
                    ),
                    const SizedBox(height: 11),
                    Text(user.name, style: serif(size: 23, color: Colors.white)),
                    const SizedBox(height: 5),
                    Row(
                      mainAxisAlignment: MainAxisAlignment.center,
                      children: [
                        Flexible(
                          child: Text(_role(user),
                              maxLines: 1, overflow: TextOverflow.ellipsis,
                              style: ui(size: 11.5, weight: FontWeight.w600, color: Colors.white70)),
                        ),
                        const SizedBox(width: 7),
                        Container(width: 3, height: 3, decoration: const BoxDecoration(color: Colors.white54, shape: BoxShape.circle)),
                        const SizedBox(width: 7),
                        Row(
                          children: [
                            Container(width: 6, height: 6, decoration: const BoxDecoration(color: Color(0xFF7BE0A8), shape: BoxShape.circle)),
                            const SizedBox(width: 4),
                            Text('Active', style: ui(size: 11.5, weight: FontWeight.w700, color: const Color(0xFF7BE0A8))),
                          ],
                        ),
                      ],
                    ),
                  ],
                ),
              ),
            ),
          ),
          Padding(
            padding: const EdgeInsets.fromLTRB(16, 14, 16, 24),
            child: Column(
              children: [
                AstraCard(
                  radius: 16,
                  padding: EdgeInsets.zero,
                  child: Column(
                    children: [
                      _sectionHeader('Personal'),
                      _infoRow(context, Icons.phone, 'Phone', user.mobile.isEmpty ? '—' : user.mobile),
                      _infoRow(context, Icons.mail_outline, 'Email', user.email.isEmpty ? '—' : user.email),
                      _sectionHeader('Work'),
                      _infoRow(context, Icons.badge_outlined, 'Role',
                          user.role.isEmpty ? _role(user) : user.role, muted: true),
                      _infoRow(context, Icons.location_on_outlined, 'Branch', branchName, muted: true),
                    ],
                  ),
                ),
                const SizedBox(height: 11),
                AstraCard(
                  radius: 16,
                  padding: EdgeInsets.zero,
                  child: Column(
                    children: [
                      _sectionHeader('MPIN & security'),
                      _securityRow(context, Icons.lock_outline, 'Change MPIN',
                          'Update your 4–6 digit login PIN', () => context.push(Routes.changePin)),
                      _securityRow(context, Icons.password_outlined, 'Change password',
                          'Update your account password', () => context.push(Routes.changePassword),
                          topBorder: true),
                    ],
                  ),
                ),
              ],
            ),
          ),
        ],
      ),
    );
  }

  Widget _sectionHeader(String text) => Builder(
        builder: (context) => Container(
          width: double.infinity,
          padding: const EdgeInsets.fromLTRB(14, 12, 14, 7),
          child: SectionLabel(text),
        ),
      );

  Widget _securityRow(BuildContext context, IconData icon, String title, String subtitle,
      VoidCallback onTap, {bool topBorder = false}) {
    final p = context.astra;
    return InkWell(
      onTap: onTap,
      child: Container(
        decoration: topBorder
            ? BoxDecoration(border: Border(top: BorderSide(color: p.hairline)))
            : null,
        padding: const EdgeInsets.symmetric(horizontal: 14, vertical: 11),
        child: Row(
          children: [
            IconChip(icon: icon, size: 30, radius: 9, bg: p.tint),
            const SizedBox(width: 11),
            Expanded(
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  Text(title, style: ui(size: 12.5, weight: FontWeight.w700, color: p.ink)),
                  const SizedBox(height: 2),
                  Text(subtitle, style: ui(size: 10.5, weight: FontWeight.w500, color: p.textMuted)),
                ],
              ),
            ),
            Icon(Icons.chevron_right, color: p.textMuted, size: 18),
          ],
        ),
      ),
    );
  }

  /// One label→value line. [divider] draws the hairline that separates it from
  /// the row above (off for the first row in a panel); [muted] softens the value
  /// for context fields the user can't change here.
  Widget _infoRow(BuildContext context, IconData icon, String label, String value,
      {bool muted = false, bool divider = true, VoidCallback? onTap}) {
    final p = context.astra;
    final row = Container(
      decoration: divider ? BoxDecoration(border: Border(top: BorderSide(color: p.hairline))) : null,
      padding: const EdgeInsets.symmetric(horizontal: 14, vertical: 11),
      child: Row(
        children: [
          Icon(icon, size: 16, color: p.textMuted),
          const SizedBox(width: 11),
          Text(label, style: ui(size: 12.5, weight: FontWeight.w600, color: p.ink)),
          const SizedBox(width: 12),
          Expanded(
            child: Text(value,
                textAlign: TextAlign.right,
                maxLines: 1,
                overflow: TextOverflow.ellipsis,
                style: ui(size: 12, weight: FontWeight.w700, color: muted ? p.textSecondary : p.ink)),
          ),
          if (onTap != null) ...[
            const SizedBox(width: 6),
            Icon(Icons.chevron_right, size: 16, color: p.textMuted),
          ],
        ],
      ),
    );
    if (onTap == null) return row;
    return InkWell(onTap: onTap, child: row);
  }
}
