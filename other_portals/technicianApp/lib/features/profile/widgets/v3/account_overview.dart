import 'package:flutter/material.dart';
import 'package:flutter/services.dart' show Clipboard, ClipboardData;
import 'package:image_picker/image_picker.dart';
import 'package:provider/provider.dart';

import 'package:invo/features/auth/logic/auth_cubit/auth_cubit.dart';
import 'package:invo/shared/domain/constants/global_variables.dart';
import 'package:invo/shared/domain/helpers/responsive.dart';
import 'package:invo/shared/domain/models/index.dart';
import 'package:invo/shared/utils/components/theme/index.dart';
import 'package:invo/shared/widgets/astra_side_rail.dart';
import 'package:invo/shared/widgets/astra_widgets.dart';

import '../../logic/profile_cubit/profile_cubit.dart';
import 'account_form_parts.dart';
import 'pick_avatar.dart';

/// The tablet account record — shared by Settings → Account and the Profile
/// screen's details pane so both read the same.
///
/// A full-width identity hero (optional — Profile already shows identity in
/// its left pane), then a balanced card grid: Contact | Work, Security |
/// Session. Two columns on a wide pane, one when narrow; content is capped at
/// 880pt and centred. Every action stays in the pane — the callbacks swap the
/// host's detail to an embedded form instead of pushing a page.
class AccountOverview extends StatefulWidget {
  const AccountOverview({
    super.key,
    required this.onEditProfile,
    required this.onChangePin,
    required this.onChangePassword,
    this.showHero = true,
  });

  final VoidCallback onEditProfile;
  final VoidCallback onChangePin;
  final VoidCallback onChangePassword;
  final bool showHero;

  @override
  State<AccountOverview> createState() => _AccountOverviewState();
}

class _AccountOverviewState extends State<AccountOverview> {
  bool _photoBusy = false;

  String _role(ApiUser u) =>
      u.designation.isNotEmpty ? u.designation : (u.role.isNotEmpty ? u.role : 'Technician');

  /// Pick → upload → refresh the signed-in user, without leaving the pane.
  Future<void> _changePhoto() async {
    if (_photoBusy) return;
    final source = await showModalBottomSheet<ImageSource>(
      context: context,
      backgroundColor: Colors.transparent,
      builder: (ctx) => const _PhotoSourceSheet(),
    );
    if (source == null || !mounted) return;
    final bytes = await pickAndCropAvatar(context, source);
    if (bytes == null || !mounted) return;
    setState(() => _photoBusy = true);
    final cubit = serviceLocator<ProfileCubit>();
    final updated = await cubit.updatePhoto(bytes);
    final error = cubit.state.errorMessage;
    await cubit.close();
    if (!mounted) return;
    if (updated != null) {
      await context.read<AuthCubit>().applyUser(updated);
      if (mounted) showAccountSnack(context, 'Profile photo updated');
    } else {
      showAccountSnack(context, error ?? 'Could not update photo.');
    }
    if (mounted) setState(() => _photoBusy = false);
  }

  void _copy(String label, String value) {
    Clipboard.setData(ClipboardData(text: value));
    showAccountSnack(context, '$label copied');
  }

  Future<void> _signOut() async {
    if (await confirmLogout(context) && mounted) {
      await context.read<AuthCubit>().logout();
    }
  }

  @override
  Widget build(BuildContext context) {
    final auth = context.watch<AuthCubit>();
    final user = auth.user;
    if (user == null) return const SizedBox.shrink();
    final cfg = auth.config;
    final host = Uri.tryParse(cfg.baseUrl)?.host ?? '';

    final contact = _AccountCard(
      icon: Icons.contact_mail_outlined,
      title: 'Contact',
      children: [
        _InfoLine(
          icon: Icons.phone_outlined,
          label: 'Phone',
          value: user.mobile,
          onCopy: user.mobile.isEmpty ? null : () => _copy('Phone', user.mobile),
          first: true,
        ),
        _InfoLine(
          icon: Icons.mail_outline,
          label: 'Email',
          value: user.email,
          onCopy: user.email.isEmpty ? null : () => _copy('Email', user.email),
        ),
      ],
    );
    final work = _AccountCard(
      icon: Icons.badge_outlined,
      title: 'Work',
      children: [
        _InfoLine(
          icon: Icons.tag,
          label: 'Employee code',
          value: user.code,
          onCopy: user.code.isEmpty ? null : () => _copy('Code', user.code),
          first: true,
        ),
        _InfoLine(icon: Icons.work_outline, label: 'Role', value: _role(user)),
        _InfoLine(
          icon: Icons.verified_user_outlined,
          label: 'Access',
          value: user.isAdmin ? 'Administrator' : 'Standard',
        ),
      ],
    );
    final security = _AccountCard(
      icon: Icons.shield_outlined,
      title: 'Security',
      children: [
        _ActionLine(
          icon: Icons.pin_outlined,
          title: 'Change MPIN',
          subtitle: 'Your 4–6 digit login PIN',
          onTap: widget.onChangePin,
          first: true,
        ),
        _ActionLine(
          icon: Icons.password_outlined,
          title: 'Change password',
          subtitle: 'For credential sign-in',
          onTap: widget.onChangePassword,
        ),
      ],
    );
    final session = _AccountCard(
      icon: Icons.devices_outlined,
      title: 'Session',
      children: [
        _InfoLine(icon: Icons.cloud_outlined, label: 'Server', value: host.isEmpty ? cfg.baseUrl : host, first: true),
        _InfoLine(icon: Icons.apartment_outlined, label: 'Tenant', value: cfg.tenant),
        _ActionLine(
          icon: Icons.logout,
          title: 'Sign out',
          subtitle: 'Sign back in with MPIN, password or biometrics',
          onTap: _signOut,
          danger: true,
        ),
      ],
    );

    return MaxWidthBox(
      maxWidth: 880,
      child: LayoutBuilder(builder: (context, box) {
        final twoUp = box.maxWidth >= 600;
        Widget pair(Widget a, Widget b) => twoUp
            ? IntrinsicHeight(
                child: Row(crossAxisAlignment: CrossAxisAlignment.stretch, children: [
                  Expanded(child: a),
                  const SizedBox(width: 16),
                  Expanded(child: b),
                ]),
              )
            : Column(children: [a, const SizedBox(height: 16), b]);
        return Column(crossAxisAlignment: CrossAxisAlignment.stretch, children: [
          if (widget.showHero) ...[
            _IdentityHero(
              user: user,
              role: _role(user),
              photoUrl: user.hasPhoto ? cfg.assetUrl(user.photoUrl) : null,
              headers: cfg.assetHeaders,
              photoBusy: _photoBusy,
              onEditProfile: widget.onEditProfile,
              onChangePhoto: _changePhoto,
            ),
            const SizedBox(height: 20),
          ],
          pair(contact, work),
          const SizedBox(height: 16),
          pair(security, session),
        ]);
      }),
    );
  }
}

/// Full-width gradient identity band: avatar in its gold ring, serif name,
/// role and status, and the primary actions on the right.
class _IdentityHero extends StatelessWidget {
  const _IdentityHero({
    required this.user,
    required this.role,
    required this.photoUrl,
    required this.headers,
    required this.photoBusy,
    required this.onEditProfile,
    required this.onChangePhoto,
  });

  final ApiUser user;
  final String role;
  final String? photoUrl;
  final Map<String, String>? headers;
  final bool photoBusy;
  final VoidCallback onEditProfile;
  final VoidCallback onChangePhoto;

  @override
  Widget build(BuildContext context) {
    final p = context.astra;
    final avatar = Stack(alignment: Alignment.center, children: [
      ProfileAvatar(letter: user.initial, imageUrl: photoUrl, headers: headers, size: 84, fontSize: 34),
      if (photoBusy)
        Container(
          width: 80,
          height: 80,
          decoration: BoxDecoration(shape: BoxShape.circle, color: Colors.black.withValues(alpha: 0.4)),
          alignment: Alignment.center,
          child: const SizedBox(width: 22, height: 22, child: CircularProgressIndicator(strokeWidth: 2, color: Colors.white)),
        ),
    ]);
    final identity = Column(crossAxisAlignment: CrossAxisAlignment.start, mainAxisSize: MainAxisSize.min, children: [
      Text('SIGNED IN AS',
          style: ui(size: 9.5, weight: FontWeight.w800, letterSpacing: 1.6, color: p.accent.withValues(alpha: 0.9))),
      const SizedBox(height: 4),
      Text(user.name, maxLines: 1, overflow: TextOverflow.ellipsis, style: serif(size: 26, color: Colors.white, height: 1.15)),
      const SizedBox(height: 6),
      Wrap(spacing: 8, runSpacing: 6, crossAxisAlignment: WrapCrossAlignment.center, children: [
        Text(role, style: ui(size: 12, weight: FontWeight.w600, color: Colors.white.withValues(alpha: 0.78))),
        Container(
          padding: const EdgeInsets.symmetric(horizontal: 9, vertical: 4),
          decoration: BoxDecoration(color: Colors.white.withValues(alpha: 0.12), borderRadius: BorderRadius.circular(20)),
          child: const _ActivePill(),
        ),
      ]),
    ]);
    final actions = [
      _HeroAction(label: 'Edit profile', icon: Icons.edit_outlined, primary: true, onTap: onEditProfile),
      _HeroAction(
        label: photoBusy ? 'Uploading…' : 'Change photo',
        icon: Icons.photo_camera_outlined,
        onTap: photoBusy ? null : onChangePhoto,
      ),
    ];

    return Container(
      clipBehavior: Clip.antiAlias,
      decoration: BoxDecoration(
        gradient: p.heroGradient,
        borderRadius: BorderRadius.circular(24),
        boxShadow: [
          BoxShadow(color: p.primaryDark.withValues(alpha: 0.45), blurRadius: 30, spreadRadius: -16, offset: const Offset(0, 16)),
        ],
      ),
      child: Stack(children: [
        // Soft gold glow from the top-right corner for depth.
        Positioned.fill(
          child: DecoratedBox(
            decoration: BoxDecoration(
              gradient: RadialGradient(
                center: const Alignment(1, -1),
                radius: 1.1,
                colors: [p.accent.withValues(alpha: 0.26), p.accent.withValues(alpha: 0)],
              ),
            ),
          ),
        ),
        // Fine gold hairline inset — the Atelier hero's frame.
        Positioned.fill(
          child: Container(
            margin: const EdgeInsets.all(6),
            decoration: BoxDecoration(
              borderRadius: BorderRadius.circular(19),
              border: Border.all(color: p.accent.withValues(alpha: 0.3)),
            ),
          ),
        ),
        Padding(
          padding: const EdgeInsets.fromLTRB(26, 24, 26, 24),
          child: LayoutBuilder(builder: (context, box) {
            if (box.maxWidth >= 560) {
              return Row(children: [
                avatar,
                const SizedBox(width: 20),
                Expanded(child: identity),
                const SizedBox(width: 16),
                // A Row hands its children unbounded width, so the stretched
                // buttons take the width of the widest one.
                IntrinsicWidth(
                  child: Column(mainAxisSize: MainAxisSize.min, crossAxisAlignment: CrossAxisAlignment.stretch, children: [
                    actions[0],
                    const SizedBox(height: 10),
                    actions[1],
                  ]),
                ),
              ]);
            }
            return Column(crossAxisAlignment: CrossAxisAlignment.start, children: [
              Row(children: [avatar, const SizedBox(width: 16), Expanded(child: identity)]),
              const SizedBox(height: 18),
              Row(children: [
                Expanded(child: actions[0]),
                const SizedBox(width: 10),
                Expanded(child: actions[1]),
              ]),
            ]);
          }),
        ),
      ]),
    );
  }
}

class _HeroAction extends StatelessWidget {
  const _HeroAction({required this.label, required this.icon, this.onTap, this.primary = false});
  final String label;
  final IconData icon;
  final VoidCallback? onTap;
  final bool primary;

  @override
  Widget build(BuildContext context) {
    final p = context.astra;
    final fg = primary ? p.primaryDark : Colors.white;
    return GestureDetector(
      onTap: onTap,
      behavior: HitTestBehavior.opaque,
      child: Opacity(
        opacity: onTap == null ? 0.6 : 1,
        child: Container(
          height: 42,
          padding: const EdgeInsets.symmetric(horizontal: 16),
          alignment: Alignment.center,
          decoration: BoxDecoration(
            gradient: primary ? p.accentGradient : null,
            color: primary ? null : Colors.white.withValues(alpha: 0.12),
            borderRadius: BorderRadius.circular(13),
            border: primary ? null : Border.all(color: Colors.white.withValues(alpha: 0.24)),
          ),
          child: Row(mainAxisSize: MainAxisSize.min, children: [
            Icon(icon, size: 15, color: fg),
            const SizedBox(width: 7),
            Flexible(
              child: Text(label,
                  maxLines: 1, overflow: TextOverflow.ellipsis, style: ui(size: 12.5, weight: FontWeight.w800, color: fg)),
            ),
          ]),
        ),
      ),
    );
  }
}

/// "● Active" in a success green lifted for dark heroes.
class _ActivePill extends StatelessWidget {
  const _ActivePill();

  @override
  Widget build(BuildContext context) {
    final green = Color.lerp(ColorManager.success, Colors.white, 0.45)!;
    return Row(mainAxisSize: MainAxisSize.min, children: [
      Container(width: 6, height: 6, decoration: BoxDecoration(color: green, shape: BoxShape.circle)),
      const SizedBox(width: 5),
      Text('Active', style: ui(size: 10.5, weight: FontWeight.w800, color: green)),
    ]);
  }
}

/// A titled card in the account grid. Fills the height it is given, so two
/// cards in a row always match.
class _AccountCard extends StatelessWidget {
  const _AccountCard({required this.icon, required this.title, required this.children});
  final IconData icon;
  final String title;
  final List<Widget> children;

  @override
  Widget build(BuildContext context) {
    final p = context.astra;
    return Container(
      padding: const EdgeInsets.fromLTRB(18, 16, 18, 8),
      decoration: BoxDecoration(
        color: p.cardSolid,
        borderRadius: BorderRadius.circular(20),
        border: Border.all(color: p.hairline),
        boxShadow: context.astraTheme.softShadow,
      ),
      child: Column(crossAxisAlignment: CrossAxisAlignment.stretch, children: [
        Row(children: [
          Container(
            width: 30,
            height: 30,
            decoration: BoxDecoration(color: p.tint, borderRadius: BorderRadius.circular(9)),
            child: Icon(icon, size: 16, color: p.primary),
          ),
          const SizedBox(width: 10),
          Text(title.toUpperCase(),
              style: ui(size: 10.5, weight: FontWeight.w800, letterSpacing: 1.2, color: p.textSecondary)),
        ]),
        const SizedBox(height: 8),
        ...children,
      ]),
    );
  }
}

/// Label over value, an optional copy affordance, hairline above.
/// An empty value reads "Not set" in muted italics rather than a bare dash.
class _InfoLine extends StatelessWidget {
  const _InfoLine({required this.icon, required this.label, required this.value, this.onCopy, this.first = false});
  final IconData icon;
  final String label;
  final String value;
  final VoidCallback? onCopy;
  final bool first;

  @override
  Widget build(BuildContext context) {
    final p = context.astra;
    final empty = value.trim().isEmpty;
    return Container(
      decoration: first ? null : BoxDecoration(border: Border(top: BorderSide(color: p.hairline))),
      padding: const EdgeInsets.symmetric(vertical: 12),
      child: Row(children: [
        Icon(icon, size: 17, color: p.textMuted),
        const SizedBox(width: 12),
        Expanded(
          child: Column(crossAxisAlignment: CrossAxisAlignment.start, children: [
            Text(label, style: ui(size: 10.5, weight: FontWeight.w700, color: p.textMuted)),
            const SizedBox(height: 2),
            Text(
              empty ? 'Not set' : value,
              maxLines: 1,
              overflow: TextOverflow.ellipsis,
              style: empty
                  ? ui(size: 13, weight: FontWeight.w500, color: p.textMuted).copyWith(fontStyle: FontStyle.italic)
                  : ui(size: 13.5, weight: FontWeight.w700, color: p.ink),
            ),
          ]),
        ),
        if (onCopy != null)
          Tooltip(
            message: 'Copy $label',
            child: GestureDetector(
              onTap: onCopy,
              behavior: HitTestBehavior.opaque,
              child: Container(
                width: 32,
                height: 32,
                decoration: BoxDecoration(color: p.tint, borderRadius: BorderRadius.circular(10)),
                child: Icon(Icons.copy_rounded, size: 15, color: p.primary),
              ),
            ),
          ),
      ]),
    );
  }
}

/// A tappable action row inside an account card.
class _ActionLine extends StatelessWidget {
  const _ActionLine({
    required this.icon,
    required this.title,
    required this.subtitle,
    required this.onTap,
    this.first = false,
    this.danger = false,
  });
  final IconData icon;
  final String title;
  final String subtitle;
  final VoidCallback onTap;
  final bool first;
  final bool danger;

  @override
  Widget build(BuildContext context) {
    final p = context.astra;
    final accent = danger ? ColorManager.danger : p.ink;
    return GestureDetector(
      onTap: onTap,
      behavior: HitTestBehavior.opaque,
      child: Container(
        decoration: first ? null : BoxDecoration(border: Border(top: BorderSide(color: p.hairline))),
        padding: const EdgeInsets.symmetric(vertical: 12),
        child: Row(children: [
          Icon(icon, size: 17, color: danger ? ColorManager.danger : p.textMuted),
          const SizedBox(width: 12),
          Expanded(
            child: Column(crossAxisAlignment: CrossAxisAlignment.start, children: [
              Text(title, style: ui(size: 13.5, weight: FontWeight.w700, color: accent)),
              const SizedBox(height: 2),
              Text(subtitle,
                  maxLines: 1, overflow: TextOverflow.ellipsis, style: ui(size: 10.5, weight: FontWeight.w600, color: p.textMuted)),
            ]),
          ),
          Icon(Icons.chevron_right, size: 18, color: danger ? ColorManager.danger : p.textMuted),
        ]),
      ),
    );
  }
}

class _PhotoSourceSheet extends StatelessWidget {
  const _PhotoSourceSheet();

  @override
  Widget build(BuildContext context) {
    final p = context.astra;
    Widget tile(IconData icon, String label, ImageSource source) => ListTile(
          leading: IconChip(icon: icon, size: 34, radius: 9, bg: p.tint),
          title: Text(label, style: ui(size: 13, weight: FontWeight.w700, color: p.ink)),
          onTap: () => Navigator.pop(context, source),
        );
    return Container(
      decoration: BoxDecoration(color: p.cardSolid, borderRadius: const BorderRadius.vertical(top: Radius.circular(22))),
      child: SafeArea(
        top: false,
        child: Column(mainAxisSize: MainAxisSize.min, children: [
          const SizedBox(height: 8),
          Container(width: 38, height: 4, decoration: BoxDecoration(color: p.hairline, borderRadius: BorderRadius.circular(3))),
          const SizedBox(height: 10),
          tile(Icons.camera_alt_outlined, 'Take photo', ImageSource.camera),
          tile(Icons.photo_library_outlined, 'Choose from library', ImageSource.gallery),
          const SizedBox(height: 6),
        ]),
      ),
    );
  }
}
