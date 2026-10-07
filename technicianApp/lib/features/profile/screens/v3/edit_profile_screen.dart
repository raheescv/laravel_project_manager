import 'dart:typed_data';

import 'package:flutter/material.dart';
import 'package:go_router/go_router.dart';
import 'package:image_picker/image_picker.dart';
import 'package:provider/provider.dart';

import 'package:invo/features/auth/logic/auth_cubit/auth_cubit.dart';
import 'package:invo/shared/domain/constants/global_variables.dart';
import 'package:invo/shared/domain/helpers/responsive.dart';
import 'package:invo/shared/domain/models/index.dart';
import 'package:invo/shared/utils/components/theme/index.dart';
import 'package:invo/shared/widgets/astra_widgets.dart';
import 'package:invo/shared/widgets/tablet_widgets.dart';

import '../../logic/profile_cubit/profile_cubit.dart';
import '../../widgets/v3/account_form_parts.dart';
import '../../widgets/v3/pick_avatar.dart';

/// Edit Profile — the signed-in user updates their own name / phone / email and
/// avatar (PUT /profile, POST /profile/photo). On success the [AuthCubit] user
/// is replaced, so the dashboard, rail and settings reflect it live.
///
/// On a tablet this is normally [embedded] in the Profile screen's detail pane;
/// the route stays for phones and deep links. Role / designation are read-only
/// and not shown here.
class EditProfileScreen extends StatefulWidget {
  const EditProfileScreen({super.key, this.embedded = false, this.onDone});

  /// Render only the form body, for a host that supplies its own pane + head.
  final bool embedded;

  /// Where "done" goes when there is no route to pop. Ignored unless [embedded].
  final VoidCallback? onDone;

  @override
  State<EditProfileScreen> createState() => _EditProfileScreenState();
}

class _EditProfileScreenState extends State<EditProfileScreen> {
  final _profile = serviceLocator<ProfileCubit>();
  final _name = TextEditingController();
  final _phone = TextEditingController();
  final _email = TextEditingController();

  bool _busy = false; // saving name/phone/email
  bool _photoBusy = false; // uploading a new avatar
  Uint8List? _preview; // picked bytes shown instantly while uploading
  bool _seeded = false;

  @override
  void didChangeDependencies() {
    super.didChangeDependencies();
    if (_seeded) return;
    final user = context.read<AuthCubit>().user;
    if (user != null) {
      _name.text = user.name;
      _phone.text = user.mobile;
      _email.text = user.email;
      _seeded = true;
    }
  }

  @override
  void dispose() {
    _profile.close();
    _name.dispose();
    _phone.dispose();
    _email.dispose();
    super.dispose();
  }

  Future<void> _save() async {
    if (_name.text.trim().isEmpty) {
      showAccountSnack(context, 'Please enter your name.');
      return;
    }
    if (_email.text.trim().isEmpty) {
      showAccountSnack(context, 'Please enter your email.');
      return;
    }
    setState(() => _busy = true);
    final updated = await _profile.updateProfile(
      name: _name.text.trim(),
      email: _email.text.trim(),
      mobile: _phone.text.trim(),
    );
    if (!mounted) return;
    if (updated != null) {
      await context.read<AuthCubit>().applyUser(updated);
      if (!mounted) return;
      showAccountSnack(context, 'Profile updated');
      _close();
    } else {
      showAccountSnack(context, _profile.state.errorMessage ?? 'Could not update profile.');
    }
    if (mounted) setState(() => _busy = false);
  }

  Future<void> _changePhoto() async {
    final source = await _pickSource();
    if (source == null || !mounted) return;
    final bytes = await pickAndCropAvatar(context, source);
    if (bytes == null || !mounted) return;
    setState(() {
      _preview = bytes;
      _photoBusy = true;
    });
    final updated = await _profile.updatePhoto(bytes);
    if (!mounted) return;
    if (updated != null) {
      await context.read<AuthCubit>().applyUser(updated);
      if (mounted) showAccountSnack(context, 'Profile photo updated');
    } else {
      setState(() => _preview = null); // roll the optimistic preview back
      showAccountSnack(context, _profile.state.errorMessage ?? 'Could not update photo.');
    }
    if (mounted) setState(() => _photoBusy = false);
  }

  Future<ImageSource?> _pickSource() {
    final p = context.astra;
    return showModalBottomSheet<ImageSource>(
      context: context,
      backgroundColor: p.cardSolid,
      shape: const RoundedRectangleBorder(borderRadius: BorderRadius.vertical(top: Radius.circular(22))),
      builder: (ctx) => SafeArea(
        child: Column(mainAxisSize: MainAxisSize.min, children: [
          const SizedBox(height: 8),
          Container(width: 38, height: 4, decoration: BoxDecoration(color: p.hairline, borderRadius: BorderRadius.circular(3))),
          const SizedBox(height: 10),
          _sourceTile(ctx, Icons.camera_alt_outlined, 'Take photo', ImageSource.camera),
          _sourceTile(ctx, Icons.photo_library_outlined, 'Choose from library', ImageSource.gallery),
          const SizedBox(height: 6),
        ]),
      ),
    );
  }

  Widget _sourceTile(BuildContext ctx, IconData icon, String label, ImageSource source) {
    final p = ctx.astra;
    return ListTile(
      leading: IconChip(icon: icon, size: 34, radius: 9, bg: p.tint),
      title: Text(label, style: ui(size: 13, weight: FontWeight.w700, color: p.ink)),
      onTap: () => Navigator.pop(ctx, source),
    );
  }

  /// Leave the form: hand back to the host when embedded, otherwise pop.
  void _close() {
    if (widget.embedded) {
      widget.onDone?.call();
      return;
    }
    context.pop();
  }

  @override
  Widget build(BuildContext context) {
    final p = context.astra;
    final user = context.select<AuthCubit, ApiUser?>((c) => c.user);
    if (user == null) return const Scaffold(body: SizedBox());

    final cfg = context.read<AuthCubit>().config;
    final photoUrl = user.hasPhoto ? cfg.assetUrl(user.photoUrl) : null;

    if (widget.embedded) return _formBody(context, user, photoUrl, cfg.assetHeaders);

    if (context.isTablet) {
      return Scaffold(
        backgroundColor: Colors.transparent,
        body: AstraBackground(
          child: SafeArea(
            bottom: false,
            child: Column(children: [
              TabletPageHead(
                title: 'Edit Profile',
                subtitle: 'Your details & photo',
                leading: context.canPop() ? TabletIconButton(icon: Icons.close, onTap: () => context.pop()) : null,
              ),
              Expanded(
                child: SingleChildScrollView(
                  padding: const EdgeInsets.fromLTRB(24, 24, 24, 40),
                  child: MaxWidthBox(maxWidth: 620, child: _formBody(context, user, photoUrl, cfg.assetHeaders)),
                ),
              ),
            ]),
          ),
        ),
      );
    }

    return Scaffold(
      body: AstraBackground(
        child: Column(children: [
          EmeraldHeader(
            leading: HeaderIconButton(icon: Icons.close, onTap: () => context.pop()),
            title: 'Edit Profile',
            trailing: _busy
                ? Padding(
                    padding: const EdgeInsets.only(right: 4),
                    child: SizedBox(width: 16, height: 16, child: CircularProgressIndicator(strokeWidth: 2, color: p.accent)),
                  )
                : GestureDetector(
                    onTap: _save,
                    child: Text('Save', style: ui(size: 12.5, weight: FontWeight.w800, color: p.accent)),
                  ),
          ),
          Expanded(
            child: MaxWidthBox(
              maxWidth: 560,
              child: ListView(
                padding: const EdgeInsets.fromLTRB(18, 16, 18, 24),
                children: [
                  Center(
                    child: GestureDetector(
                      onTap: _photoBusy ? null : _changePhoto,
                      child: Stack(clipBehavior: Clip.none, children: [
                        _avatar(user.initial, photoUrl, cfg.assetHeaders),
                        Positioned(right: -2, bottom: -2, child: _cameraBadge(p, p.canvas)),
                      ]),
                    ),
                  ),
                  const SizedBox(height: 6),
                  Center(
                    child: Text(_photoBusy ? 'Uploading photo…' : 'Tap the photo to change it',
                        style: ui(size: 10.5, weight: FontWeight.w600, color: p.textMuted)),
                  ),
                  const SizedBox(height: 16),
                  ..._fields(),
                ],
              ),
            ),
          ),
        ]),
      ),
    );
  }

  List<Widget> _fields() => [
        _editField('Full name', _name, icon: Icons.person_outline, textCapitalization: TextCapitalization.words),
        const SizedBox(height: 12),
        _editField('Phone', _phone, icon: Icons.phone_outlined, keyboardType: TextInputType.phone),
        const SizedBox(height: 12),
        _editField('Email', _email, icon: Icons.mail_outline, keyboardType: TextInputType.emailAddress),
      ];

  Widget _cameraBadge(AstraPalette p, Color ring) => Container(
        width: 28,
        height: 28,
        decoration: BoxDecoration(color: p.primary, shape: BoxShape.circle, border: Border.all(color: ring, width: 3)),
        child: const Icon(Icons.camera_alt, size: 13, color: Colors.white),
      );

  /// The tablet form — identical embedded in the Profile pane or standalone.
  Widget _formBody(BuildContext context, ApiUser user, String? photoUrl, Map<String, String>? headers) {
    final p = context.astra;
    return Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        AstraCard(
          radius: 18,
          padding: const EdgeInsets.all(16),
          child: Row(children: [
            GestureDetector(
              onTap: _photoBusy ? null : _changePhoto,
              child: Stack(clipBehavior: Clip.none, children: [
                _avatar(user.initial, photoUrl, headers, size: 92),
                Positioned(right: -1, bottom: -1, child: _cameraBadge(p, p.cardSolid)),
              ]),
            ),
            const SizedBox(width: 16),
            Expanded(
              child: Column(crossAxisAlignment: CrossAxisAlignment.start, children: [
                Text(user.name, maxLines: 1, overflow: TextOverflow.ellipsis, style: serif(size: 19, color: p.ink)),
                const SizedBox(height: 4),
                Text(
                  _photoBusy ? 'Uploading photo…' : 'A square photo works best — it is centre-cropped.',
                  style: ui(size: 11, weight: FontWeight.w600, color: p.textMuted, height: 1.35),
                ),
                const SizedBox(height: 11),
                TabletActionButton(
                  label: _photoBusy ? 'Uploading…' : 'Change photo',
                  icon: Icons.photo_camera_outlined,
                  onTap: _photoBusy ? null : _changePhoto,
                ),
              ]),
            ),
          ]),
        ),
        const SizedBox(height: 18),
        TabletPanel(
          title: 'Account details',
          padding: const EdgeInsets.fromLTRB(16, 16, 16, 18),
          child: Column(children: [
            _editField('Full name', _name, icon: Icons.person_outline, textCapitalization: TextCapitalization.words),
            const SizedBox(height: 14),
            _editField('Phone', _phone, icon: Icons.phone_outlined, keyboardType: TextInputType.phone),
            const SizedBox(height: 14),
            _editField('Email', _email, icon: Icons.mail_outline, keyboardType: TextInputType.emailAddress),
          ]),
        ),
        const SizedBox(height: 18),
        Row(mainAxisAlignment: MainAxisAlignment.end, children: [
          TabletActionButton(label: 'Cancel', onTap: _busy ? null : _close),
          const SizedBox(width: 10),
          TabletActionButton(
            label: _busy ? 'Saving…' : 'Save changes',
            icon: Icons.check,
            primary: true,
            onTap: _busy ? null : _save,
          ),
        ]),
      ],
    );
  }

  Widget _avatar(String initial, String? photoUrl, Map<String, String>? headers, {double size = 78}) {
    final preview = _preview;
    if (preview != null) {
      final inner = size - 4;
      final dpr = MediaQuery.devicePixelRatioOf(context);
      return Container(
        width: size,
        height: size,
        padding: const EdgeInsets.all(2),
        decoration: BoxDecoration(shape: BoxShape.circle, gradient: context.astra.accentGradient),
        child: Stack(alignment: Alignment.center, children: [
          ClipOval(
            child: Image.memory(
              preview,
              width: inner,
              height: inner,
              fit: BoxFit.cover,
              cacheWidth: (inner * dpr).round(),
              errorBuilder: (_, __, ___) => SizedBox(width: inner, height: inner),
            ),
          ),
          if (_photoBusy)
            Container(
              width: inner,
              height: inner,
              decoration: const BoxDecoration(shape: BoxShape.circle, color: Colors.black38),
              alignment: Alignment.center,
              child: const SizedBox(width: 22, height: 22, child: CircularProgressIndicator(strokeWidth: 2, color: Colors.white)),
            ),
        ]),
      );
    }
    return ProfileAvatar(letter: initial, imageUrl: photoUrl, headers: headers, size: size, fontSize: size * 0.41);
  }

  Widget _editField(
    String label,
    TextEditingController c, {
    IconData? icon,
    TextInputType? keyboardType,
    TextCapitalization textCapitalization = TextCapitalization.none,
  }) {
    final p = context.astra;
    final t = context.astraTheme;
    return Column(crossAxisAlignment: CrossAxisAlignment.start, children: [
      Text(label.toUpperCase(), style: ui(size: 9.5, weight: FontWeight.w800, color: p.textMuted, letterSpacing: 0.6)),
      const SizedBox(height: 6),
      Container(
        decoration: BoxDecoration(color: p.card, borderRadius: BorderRadius.circular(13), boxShadow: t.softShadow),
        child: TextField(
          controller: c,
          keyboardType: keyboardType,
          textCapitalization: textCapitalization,
          style: ui(size: 14, weight: FontWeight.w700, color: p.ink),
          decoration: InputDecoration(
            prefixIcon: icon == null ? null : Icon(icon, color: p.textMuted, size: 18),
            border: InputBorder.none,
            contentPadding: const EdgeInsets.symmetric(horizontal: 14, vertical: 13),
          ),
        ),
      ),
    ]);
  }
}
