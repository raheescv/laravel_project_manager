import 'dart:typed_data';

import 'package:flutter/material.dart';
import 'package:go_router/go_router.dart';
import 'package:image_picker/image_picker.dart';
import 'package:provider/provider.dart';

import 'package:invo/shared/domain/constants/global_variables.dart';
import 'package:invo/shared/domain/helpers/responsive.dart';
import 'package:invo/features/auth/logic/auth_cubit/auth_cubit.dart';
import 'package:invo/features/profile/domain/repository/profile_repository.dart';
import 'package:invo/features/profile/screens/v3/crop_photo_screen.dart';
import 'package:invo/shared/logic/branch_cubit/branch_cubit.dart';
import 'package:invo/shared/utils/components/theme/index.dart';
import 'package:invo/shared/utils/router/http_utils/common_exception.dart';
import 'package:invo/shared/widgets/astra_widgets.dart';

/// Edit Profile — the signed-in user updates their own name / phone / email and
/// avatar. Wired to PUT /profile and POST /profile/photo; on success the cached
/// [AuthCubit] user is replaced so every screen reflects the change live.
class EditProfileScreen extends StatefulWidget {
  const EditProfileScreen({super.key});

  @override
  State<EditProfileScreen> createState() => _EditProfileScreenState();
}

class _EditProfileScreenState extends State<EditProfileScreen> {
  final _name = TextEditingController();
  final _phone = TextEditingController();
  final _email = TextEditingController();

  bool _busy = false; // saving name/phone/email
  bool _photoBusy = false; // uploading a new avatar
  Uint8List? _preview; // locally-picked bytes shown instantly while uploading
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
    _name.dispose();
    _phone.dispose();
    _email.dispose();
    super.dispose();
  }

  void _snack(String m) => ScaffoldMessenger.of(context)
    ..clearSnackBars()
    ..showSnackBar(SnackBar(content: Text(m)));

  Future<void> _save() async {
    if (_name.text.trim().isEmpty) {
      _snack('Please enter your name.');
      return;
    }
    if (_email.text.trim().isEmpty) {
      _snack('Please enter your email.');
      return;
    }
    setState(() => _busy = true);
    try {
      final updated = await serviceLocator<ProfileRepository>().updateProfile(
        name: _name.text.trim(),
        email: _email.text.trim(),
        mobile: _phone.text.trim(),
      );
      if (!mounted) return;
      await context.read<AuthCubit>().applyUser(updated);
      if (!mounted) return;
      _snack('Profile updated');
      context.pop();
    } on ApiException catch (e) {
      _snack(e.message);
    } catch (_) {
      _snack('Could not update profile.');
    }
    if (mounted) setState(() => _busy = false);
  }

  Future<void> _changePhoto() async {
    final source = await _pickSource();
    if (source == null) return;
    final XFile? file = await ImagePicker().pickImage(
      source: source,
      imageQuality: 92,
      maxWidth: 2048,
      maxHeight: 2048,
    );
    if (file == null) return;

    final raw = await file.readAsBytes();
    if (!mounted) return;

    // Premium in-app crop step — returns the cropped square bytes (or null).
    final cropped = await Navigator.of(context).push<Uint8List>(
      MaterialPageRoute(builder: (_) => CropPhotoScreen(imageBytes: raw)),
    );
    if (cropped == null || !mounted) return;

    setState(() {
      _preview = cropped;
      _photoBusy = true;
    });
    try {
      final updated = await serviceLocator<ProfileRepository>().updatePhoto(cropped);
      if (!mounted) return;
      await context.read<AuthCubit>().applyUser(updated);
      if (mounted) _snack('Profile photo updated');
    } on ApiException catch (e) {
      if (mounted) setState(() => _preview = null);
      _snack(e.message);
    } catch (_) {
      if (mounted) setState(() => _preview = null);
      _snack('Could not update photo.');
    }
    if (mounted) setState(() => _photoBusy = false);
  }

  Future<ImageSource?> _pickSource() {
    final p = context.astra;
    return showModalBottomSheet<ImageSource>(
      context: context,
      backgroundColor: p.card,
      shape: const RoundedRectangleBorder(
        borderRadius: BorderRadius.vertical(top: Radius.circular(22)),
      ),
      builder: (ctx) => SafeArea(
        child: Column(
          mainAxisSize: MainAxisSize.min,
          children: [
            const SizedBox(height: 8),
            Container(width: 38, height: 4, decoration: BoxDecoration(color: p.hairline, borderRadius: BorderRadius.circular(3))),
            const SizedBox(height: 10),
            _sourceTile(ctx, Icons.camera_alt_outlined, 'Take photo', ImageSource.camera),
            _sourceTile(ctx, Icons.photo_library_outlined, 'Choose from library', ImageSource.gallery),
            const SizedBox(height: 6),
          ],
        ),
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

  @override
  Widget build(BuildContext context) {
    final p = context.astra;
    final user = context.watch<AuthCubit>().user;
    if (user == null) return const Scaffold(body: SizedBox());

    final cfg = context.read<AuthCubit>().config;
    final photoUrl = user.hasPhoto ? cfg.assetUrl(user.photoUrl) : null;

    // Resolve the branch id to its display name (mirrors profile_screen).
    final branchCtrl = context.watch<BranchCubit>();
    final match = branchCtrl.branches.where((b) => b.id.toString() == user.branchId);
    final branchName = match.isNotEmpty
        ? match.first.name
        : (branchCtrl.selected?.name ?? '—');
    final roleName = user.designation.isEmpty ? (user.isAdmin ? 'Administrator' : 'Staff') : user.designation;

    return Scaffold(
      body: AstraBackground(
        child: Column(
          children: [
            EmeraldHeader(
              leading: HeaderIconButton(icon: Icons.close, onTap: () => context.pop()),
              title: 'Edit Profile',
              trailing: _busy
                  ? Padding(
                      padding: const EdgeInsets.only(right: 4),
                      child: SizedBox(
                        width: 16,
                        height: 16,
                        child: CircularProgressIndicator(strokeWidth: 2, color: p.accent),
                      ),
                    )
                  : GestureDetector(
                      onTap: _save,
                      child: Text('Save', style: ui(size: 12.5, weight: FontWeight.w800, color: p.accent)),
                    ),
            ),
            const SizedBox(height: 16),
            Center(
              child: GestureDetector(
                onTap: _photoBusy ? null : _changePhoto,
                child: Stack(
                  clipBehavior: Clip.none,
                  children: [
                    _avatar(user.initial, photoUrl, cfg.assetHeaders),
                    Positioned(
                      right: -2,
                      bottom: -2,
                      child: Container(
                        width: 28,
                        height: 28,
                        decoration: BoxDecoration(
                          color: p.primary,
                          shape: BoxShape.circle,
                          border: Border.all(color: p.canvas, width: 3),
                        ),
                        child: const Icon(Icons.camera_alt, size: 13, color: Colors.white),
                      ),
                    ),
                  ],
                ),
              ),
            ),
            const SizedBox(height: 6),
            Text(
              _photoBusy ? 'Uploading photo…' : 'Tap the photo to change it',
              style: ui(size: 10.5, weight: FontWeight.w600, color: p.textMuted),
            ),
            Expanded(
              child: MaxWidthBox(
                maxWidth: 560,
                child: ListView(
                  padding: const EdgeInsets.fromLTRB(18, 16, 18, 24),
                  children: [
                    _editField('Full name', _name, icon: Icons.person_outline, textCapitalization: TextCapitalization.words),
                    const SizedBox(height: 12),
                    _editField('Phone', _phone, icon: Icons.phone_outlined, keyboardType: TextInputType.phone),
                    const SizedBox(height: 12),
                    _editField('Email', _email, icon: Icons.mail_outline, keyboardType: TextInputType.emailAddress),
                    const SizedBox(height: 12),
                    Row(
                      children: [
                        Expanded(child: _readOnly('Role', roleName)),
                        const SizedBox(width: 11),
                        Expanded(child: _readOnly('Branch', branchName)),
                      ],
                    ),
                  ],
                ),
              ),
            ),
          ],
        ),
      ),
    );
  }

  Widget _avatar(String initial, String? photoUrl, Map<String, String>? headers) {
    if (_preview != null) {
      // Instant local preview while the upload is in flight.
      return Container(
        width: 78,
        height: 78,
        padding: const EdgeInsets.all(2),
        decoration: BoxDecoration(shape: BoxShape.circle, gradient: context.astra.accentGradient),
        child: Stack(
          alignment: Alignment.center,
          children: [
            ClipOval(child: Image.memory(_preview!, width: 74, height: 74, fit: BoxFit.cover)),
            if (_photoBusy)
              Container(
                width: 74,
                height: 74,
                decoration: const BoxDecoration(shape: BoxShape.circle, color: Colors.black38),
                alignment: Alignment.center,
                child: const SizedBox(
                  width: 22,
                  height: 22,
                  child: CircularProgressIndicator(strokeWidth: 2, color: Colors.white),
                ),
              ),
          ],
        ),
      );
    }
    return ProfileAvatar(letter: initial, imageUrl: photoUrl, headers: headers, size: 78, fontSize: 32);
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
    return Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
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
      ],
    );
  }

  Widget _readOnly(String label, String value) {
    final p = context.astra;
    return AstraCard(
      radius: 13,
      padding: const EdgeInsets.symmetric(horizontal: 13, vertical: 11),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Text(label.toUpperCase(), style: ui(size: 9.5, weight: FontWeight.w800, color: p.textMuted, letterSpacing: 0.6)),
          const SizedBox(height: 4),
          Text(value, maxLines: 1, overflow: TextOverflow.ellipsis, style: ui(size: 13, weight: FontWeight.w700, color: p.ink)),
        ],
      ),
    );
  }
}
