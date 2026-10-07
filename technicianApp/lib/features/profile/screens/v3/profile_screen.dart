import 'package:flutter/material.dart';
import 'package:go_router/go_router.dart';
import 'package:provider/provider.dart';

import 'package:invo/features/auth/logic/auth_cubit/auth_cubit.dart';
import 'package:invo/shared/domain/helpers/responsive.dart';
import 'package:invo/shared/domain/models/index.dart';
import 'package:invo/shared/utils/components/theme/index.dart';
import 'package:invo/shared/utils/router/routes.dart';
import 'package:invo/shared/widgets/astra_widgets.dart';
import 'package:invo/shared/widgets/tablet_widgets.dart';

import 'change_password_screen.dart';
import 'change_pin_screen.dart';
import 'edit_profile_screen.dart';
import '../../widgets/v3/account_overview.dart';

/// Which section the tablet detail pane is showing.
enum ProfileSection { details, edit, pin, password }

/// My Profile — the signed-in technician's identity, contact details and
/// security actions (ported from the POS app's profile).
///
/// Phone keeps a gradient header band and pushes Edit / MPIN / Password as
/// their own routes. Tablet is the whole account area in one screen: identity +
/// section pane on the left, and a detail side on the right that *swaps*
/// between the record and the three forms (rendered in their `embedded` mode),
/// so nothing takes over the window.
class ProfileScreen extends StatefulWidget {
  const ProfileScreen({super.key, this.initialSection = ProfileSection.details});

  final ProfileSection initialSection;

  @override
  State<ProfileScreen> createState() => _ProfileScreenState();
}

class _ProfileScreenState extends State<ProfileScreen> {
  late ProfileSection _sel = widget.initialSection;

  static const _sectionTitles = {
    ProfileSection.details: ('Profile details', 'Your record as the system holds it'),
    ProfileSection.edit: ('Edit profile', 'Name, phone, email & photo'),
    ProfileSection.pin: ('Change MPIN', 'Your 4–6 digit login PIN'),
    ProfileSection.password: ('Change password', 'Your account login password'),
  };

  String _role(ApiUser user) => user.designation.isNotEmpty
      ? user.designation
      : (user.role.isNotEmpty ? user.role : (user.isAdmin ? 'Administrator' : 'Technician'));

  @override
  Widget build(BuildContext context) {
    final user = context.select<AuthCubit, ApiUser?>((c) => c.user);
    if (user == null) return const Scaffold(body: SizedBox.shrink());

    final cfg = context.read<AuthCubit>().config;
    final photoUrl = user.hasPhoto ? cfg.assetUrl(user.photoUrl) : null;

    return Scaffold(
      backgroundColor: Colors.transparent,
      body: AstraBackground(
        child: context.isTablet
            ? SafeArea(bottom: false, child: _tablet(context, user, photoUrl, cfg.assetHeaders))
            : _phone(context, user, photoUrl, cfg.assetHeaders),
      ),
    );
  }

  // ---------------------------------------------------------------- tablet --

  Widget _tablet(BuildContext context, ApiUser user, String? photoUrl, Map<String, String>? headers) {
    final section = _sectionTitles[_sel]!;
    return LayoutBuilder(builder: (ctx, c) {
      final m = TabletMetrics.forWidth(c.maxWidth);
      return Row(crossAxisAlignment: CrossAxisAlignment.stretch, children: [
        TabletPane(
          width: m.settingsNav,
          child: Column(children: [
            TabletPaneHead(
              title: 'My Profile',
              subtitle: 'Account & security',
              // Normally a shell destination — only a pushed route can go back.
              leading: context.canPop() ? TabletIconButton(icon: Icons.chevron_left, onTap: () => context.pop()) : null,
            ),
            Expanded(
              child: ListView(
                padding: const EdgeInsets.fromLTRB(14, 16, 14, 28),
                children: [
                  ProfileIdentityCard(user: user, photoUrl: photoUrl, headers: headers, role: _role(user)),
                  const SizedBox(height: 18),
                  const Padding(padding: EdgeInsets.only(left: 2, bottom: 8), child: SectionLabel('Account')),
                  _navTile(context, Icons.person_outline, ProfileSection.details),
                  _navTile(context, Icons.edit_outlined, ProfileSection.edit),
                  _navTile(context, Icons.lock_outline, ProfileSection.pin),
                  _navTile(context, Icons.password_outlined, ProfileSection.password),
                ],
              ),
            ),
          ]),
        ),
        Expanded(
          child: Column(children: [
            TabletPageHead(title: section.$1, subtitle: section.$2),
            Expanded(
              child: SingleChildScrollView(
                padding: m.detailPadding.copyWith(top: 22, bottom: 40),
                child: MaxWidthBox(
                  maxWidth: _sel == ProfileSection.details ? 880 : 620,
                  child: astraPaneSwitcher(child: KeyedSubtree(key: ValueKey(_sel), child: _detail(context, user))),
                ),
              ),
            ),
          ]),
        ),
      ]);
    });
  }

  void _back() => setState(() => _sel = ProfileSection.details);

  Widget _detail(BuildContext context, ApiUser user) {
    switch (_sel) {
      case ProfileSection.edit:
        return EditProfileScreen(embedded: true, onDone: _back);
      case ProfileSection.pin:
        return ChangePinScreen(embedded: true, onDone: _back);
      case ProfileSection.password:
        return ChangePasswordScreen(embedded: true, onDone: _back);
      case ProfileSection.details:
        // Same record grid as Settings → Account; the identity hero is
        // already in this screen's left pane, so it is left out here.
        return AccountOverview(
          showHero: false,
          onEditProfile: () => setState(() => _sel = ProfileSection.edit),
          onChangePin: () => setState(() => _sel = ProfileSection.pin),
          onChangePassword: () => setState(() => _sel = ProfileSection.password),
        );
    }
  }

  Widget _navTile(BuildContext context, IconData icon, ProfileSection section) {
    final p = context.astra;
    final active = _sel == section;
    final labels = _sectionTitles[section]!;
    return GestureDetector(
      onTap: () => setState(() => _sel = section),
      behavior: HitTestBehavior.opaque,
      child: AnimatedContainer(
        duration: AstraMotion.fast,
        curve: AstraMotion.curve,
        margin: const EdgeInsets.only(bottom: 6),
        padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 11),
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
              Text(labels.$1, style: ui(size: 12.5, weight: active ? FontWeight.w800 : FontWeight.w700, color: p.ink)),
              const SizedBox(height: 2),
              Text(labels.$2,
                  maxLines: 1, overflow: TextOverflow.ellipsis, style: ui(size: 10.5, weight: FontWeight.w600, color: p.textMuted)),
            ]),
          ),
        ]),
      ),
    );
  }

  // ----------------------------------------------------------------- phone --

  Widget _phone(BuildContext context, ApiUser user, String? photoUrl, Map<String, String>? headers) {
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
                child: Column(children: [
                  Row(children: [
                    HeaderIconButton(icon: Icons.chevron_left, onTap: () => context.pop()),
                    Expanded(child: Center(child: Text('My Profile', style: serif(size: 18, color: Colors.white)))),
                    HeaderIconButton(icon: Icons.edit_outlined, gold: true, onTap: () => context.push(Routes.editProfile)),
                  ]),
                  const SizedBox(height: 14),
                  ProfileAvatar(letter: user.initial, imageUrl: photoUrl, headers: headers, size: 78, fontSize: 32),
                  const SizedBox(height: 11),
                  Text(user.name, textAlign: TextAlign.center, style: serif(size: 23, color: Colors.white)),
                  const SizedBox(height: 5),
                  Row(mainAxisAlignment: MainAxisAlignment.center, children: [
                    Flexible(
                      child: Text(_role(user),
                          maxLines: 1,
                          overflow: TextOverflow.ellipsis,
                          style: ui(size: 11.5, weight: FontWeight.w600, color: Colors.white70)),
                    ),
                    const SizedBox(width: 7),
                    Container(width: 3, height: 3, decoration: const BoxDecoration(color: Colors.white54, shape: BoxShape.circle)),
                    const SizedBox(width: 7),
                    const ActiveDot(),
                  ]),
                ]),
              ),
            ),
          ),
          Padding(
            padding: const EdgeInsets.fromLTRB(16, 14, 16, 24),
            child: Column(children: [
              AstraCard(
                radius: 16,
                padding: EdgeInsets.zero,
                child: Column(children: [
                  _sectionHeader('Personal'),
                  _infoRow(context, Icons.phone, 'Phone', user.mobile.isEmpty ? '—' : user.mobile, divider: false),
                  _infoRow(context, Icons.mail_outline, 'Email', user.email.isEmpty ? '—' : user.email),
                  _sectionHeader('Work'),
                  _infoRow(context, Icons.badge_outlined, 'Role', _role(user), muted: true, divider: false),
                  _infoRow(context, Icons.tag, 'Code', user.code.isEmpty ? '—' : user.code, muted: true),
                ]),
              ),
              const SizedBox(height: 11),
              AstraCard(
                radius: 16,
                padding: EdgeInsets.zero,
                child: Column(children: [
                  _sectionHeader('MPIN & security'),
                  _securityRow(context, Icons.lock_outline, 'Change MPIN', 'Update your 4–6 digit login PIN',
                      () => context.push(Routes.changePin)),
                  _securityRow(context, Icons.password_outlined, 'Change password', 'Update your account password',
                      () => context.push(Routes.changePassword),
                      topBorder: true),
                ]),
              ),
            ]),
          ),
        ],
      ),
    );
  }

  Widget _sectionHeader(String text) => Container(
        width: double.infinity,
        padding: const EdgeInsets.fromLTRB(14, 12, 14, 7),
        child: SectionLabel(text),
      );

  Widget _securityRow(BuildContext context, IconData icon, String title, String subtitle, VoidCallback onTap,
      {bool topBorder = false}) {
    final p = context.astra;
    return InkWell(
      onTap: onTap,
      child: Container(
        decoration: topBorder ? BoxDecoration(border: Border(top: BorderSide(color: p.hairline))) : null,
        padding: const EdgeInsets.symmetric(horizontal: 14, vertical: 11),
        child: Row(children: [
          IconChip(icon: icon, size: 30, radius: 9, bg: p.tint),
          const SizedBox(width: 11),
          Expanded(
            child: Column(crossAxisAlignment: CrossAxisAlignment.start, children: [
              Text(title, style: ui(size: 12.5, weight: FontWeight.w700, color: p.ink)),
              const SizedBox(height: 2),
              Text(subtitle, style: ui(size: 10.5, weight: FontWeight.w500, color: p.textMuted)),
            ]),
          ),
          Icon(Icons.chevron_right, color: p.textMuted, size: 18),
        ]),
      ),
    );
  }

  /// One label → value line; [divider] draws the hairline above it.
  Widget _infoRow(BuildContext context, IconData icon, String label, String value,
      {bool muted = false, bool divider = true}) {
    final p = context.astra;
    return Container(
      decoration: divider ? BoxDecoration(border: Border(top: BorderSide(color: p.hairline))) : null,
      padding: const EdgeInsets.symmetric(horizontal: 14, vertical: 11),
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
              style: ui(size: 12, weight: FontWeight.w700, color: muted ? p.textSecondary : p.ink)),
        ),
      ]),
    );
  }
}

/// The gradient identity block — the profile's hero, inset as a card wherever
/// a tablet pane needs it (profile, settings → account).
class ProfileIdentityCard extends StatelessWidget {
  const ProfileIdentityCard({super.key, required this.user, required this.role, this.photoUrl, this.headers});

  final ApiUser user;
  final String role;
  final String? photoUrl;
  final Map<String, String>? headers;

  @override
  Widget build(BuildContext context) {
    final p = context.astra;
    return Container(
      clipBehavior: Clip.antiAlias,
      decoration: BoxDecoration(
        gradient: p.heroGradient,
        borderRadius: BorderRadius.circular(22),
        boxShadow: context.astraTheme.floatShadow(p.primary),
      ),
      child: Stack(children: [
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
          child: Column(children: [
            ProfileAvatar(letter: user.initial, imageUrl: photoUrl, headers: headers, size: 86, fontSize: 34),
            const SizedBox(height: 12),
            Text(user.name,
                textAlign: TextAlign.center,
                maxLines: 2,
                overflow: TextOverflow.ellipsis,
                style: serif(size: 21, color: Colors.white)),
            const SizedBox(height: 4),
            Text(role,
                maxLines: 1,
                overflow: TextOverflow.ellipsis,
                style: ui(size: 11.5, weight: FontWeight.w600, color: Colors.white70)),
            const SizedBox(height: 11),
            Container(
              padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 5),
              decoration: BoxDecoration(color: Colors.white.withValues(alpha: 0.12), borderRadius: BorderRadius.circular(20)),
              child: const ActiveDot(),
            ),
          ]),
        ),
      ]),
    );
  }
}

/// "● Active" in the soft success green used on dark heroes.
class ActiveDot extends StatelessWidget {
  const ActiveDot({super.key});

  @override
  Widget build(BuildContext context) {
    final green = Color.lerp(ColorManager.success, Colors.white, 0.45)!;
    return Row(mainAxisSize: MainAxisSize.min, children: [
      Container(width: 6, height: 6, decoration: BoxDecoration(color: green, shape: BoxShape.circle)),
      const SizedBox(width: 5),
      Text('Active', style: ui(size: 11, weight: FontWeight.w800, color: green)),
    ]);
  }
}
