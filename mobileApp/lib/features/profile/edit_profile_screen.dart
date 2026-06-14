import 'package:flutter/material.dart';
import 'package:go_router/go_router.dart';
import 'package:provider/provider.dart';

import '../../core/responsive.dart';
import '../../state/auth_controller.dart';
import '../../theme/theme.dart';
import '../../widgets/astra_widgets.dart';

/// Edit Profile form (matches the design). The current API has no profile-update
/// endpoint, so Save explains that — add one server-side to make it live.
class EditProfileScreen extends StatelessWidget {
  const EditProfileScreen({super.key});

  @override
  Widget build(BuildContext context) {
    final p = context.astra;
    final user = context.watch<AuthController>().user;
    if (user == null) return const Scaffold(body: SizedBox());

    return Scaffold(
      body: AstraBackground(
        child: Column(
          children: [
            EmeraldHeader(
              leading: HeaderIconButton(icon: Icons.close, onTap: () => context.pop()),
              title: 'Edit Profile',
              trailing: GestureDetector(
                onTap: () {
                  ScaffoldMessenger.of(context).showSnackBar(
                    const SnackBar(content: Text('Saving needs a profile-update API endpoint (see BUILD_PROMPT §8).')),
                  );
                },
                child: Text('Save', style: ui(size: 12.5, weight: FontWeight.w800, color: p.accent)),
              ),
            ),
            const SizedBox(height: 16),
            Center(
              child: Stack(
                clipBehavior: Clip.none,
                children: [
                  Monogram(letter: user.initial, size: 78, fontSize: 32),
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
            Expanded(
              child: MaxWidthBox(
                maxWidth: 560,
                child: ListView(
                padding: const EdgeInsets.fromLTRB(18, 16, 18, 24),
                children: [
                  _field(context, 'Full name', user.name),
                  const SizedBox(height: 11),
                  _field(context, 'Phone', user.mobile.isEmpty ? '—' : user.mobile),
                  const SizedBox(height: 11),
                  _field(context, 'Email', user.email.isEmpty ? '—' : user.email),
                  const SizedBox(height: 11),
                  Row(
                    children: [
                      Expanded(child: _field(context, 'Role', user.designation.isEmpty ? 'Staff' : user.designation, chevron: true)),
                      const SizedBox(width: 11),
                      Expanded(child: _field(context, 'Branch', user.branchId ?? '—', chevron: true)),
                    ],
                  ),
                  const SizedBox(height: 11),
                  AstraCard(
                    radius: 13,
                    child: Row(
                      children: [
                        IconChip(icon: Icons.check_circle_outline, size: 30, radius: 9, bg: p.tint),
                        const SizedBox(width: 11),
                        Expanded(
                          child: Column(
                            crossAxisAlignment: CrossAxisAlignment.start,
                            children: [
                              Text('Active employee', style: ui(size: 12.5, weight: FontWeight.w700, color: p.ink)),
                              Text('Can log in & take tickets', style: ui(size: 10, weight: FontWeight.w600, color: p.textMuted)),
                            ],
                          ),
                        ),
                        Container(
                          width: 42,
                          height: 24,
                          decoration: BoxDecoration(color: p.primary, borderRadius: BorderRadius.circular(14)),
                          alignment: Alignment.centerRight,
                          padding: const EdgeInsets.all(2),
                          child: Container(width: 20, height: 20, decoration: const BoxDecoration(color: Colors.white, shape: BoxShape.circle)),
                        ),
                      ],
                    ),
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

  Widget _field(BuildContext context, String label, String value, {bool chevron = false}) {
    final p = context.astra;
    return AstraCard(
      radius: 13,
      padding: const EdgeInsets.symmetric(horizontal: 13, vertical: 10),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Text(label.toUpperCase(), style: ui(size: 9.5, weight: FontWeight.w800, color: p.textMuted, letterSpacing: 0.6)),
          const SizedBox(height: 3),
          Row(
            children: [
              Expanded(child: Text(value, maxLines: 1, overflow: TextOverflow.ellipsis, style: ui(size: 13, weight: FontWeight.w700, color: p.ink))),
              if (chevron) Icon(Icons.keyboard_arrow_down, size: 16, color: p.goldText),
            ],
          ),
        ],
      ),
    );
  }
}
