import 'package:flutter/material.dart';
import 'package:go_router/go_router.dart';
import 'package:provider/provider.dart';

import 'package:invo/shared/domain/constants/mobile_permissions.dart';
import 'package:invo/shared/domain/helpers/responsive.dart';
import 'package:invo/features/auth/logic/auth_cubit/auth_cubit.dart';
import 'package:invo/shared/logic/branch_cubit/branch_cubit.dart';
import 'package:invo/shared/utils/components/theme/index.dart';
import 'package:invo/shared/widgets/astra_widgets.dart';

class ProfileScreen extends StatelessWidget {
  const ProfileScreen({super.key});

  @override
  Widget build(BuildContext context) {
    final p = context.astra;
    final user = context.watch<AuthCubit>().user;
    if (user == null) return const Scaffold(body: Center(child: CircularProgressIndicator()));

    final cfg = context.read<AuthCubit>().config;
    final photoUrl = user.hasPhoto ? cfg.assetUrl(user.photoUrl) : null;

    final branchCtrl = context.watch<BranchCubit>();
    final match = branchCtrl.branches.where((b) => b.id.toString() == user.branchId);
    final branchName = match.isNotEmpty
        ? match.first.name
        : (branchCtrl.selected?.name ?? '—');

    return Scaffold(
      body: AstraBackground(
        child: MaxWidthBox(
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
                          HeaderIconButton(icon: Icons.edit_outlined, gold: true, onTap: () => context.push('/edit-profile')),
                        ],
                      ),
                      const SizedBox(height: 14),
                      ProfileAvatar(
                        letter: user.initial,
                        imageUrl: photoUrl,
                        headers: cfg.assetHeaders,
                        size: 78,
                        fontSize: 32,
                      ),
                      const SizedBox(height: 11),
                      Text(user.name, style: serif(size: 23, color: Colors.white)),
                      const SizedBox(height: 5),
                      Row(
                        mainAxisAlignment: MainAxisAlignment.center,
                        children: [
                          Text(user.designation.isEmpty ? (user.isAdmin ? 'Administrator' : 'Staff') : user.designation,
                              style: ui(size: 11.5, weight: FontWeight.w600, color: Colors.white70)),
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
                            user.role.isEmpty ? '—' : user.role, trailing: true),
                        _infoRow(context, Icons.location_on_outlined, 'Branch', branchName, trailing: true),
                        _infoRow(context, Icons.event_available_outlined, 'Day status',
                            user.dayOpen ? 'Open' : 'Closed',
                            trailing: user.hasPermission(PermissionSlug.daySession),
                            onTap: user.hasPermission(PermissionSlug.daySession)
                                ? () => context.push('/day-session')
                                : null),
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
                            'Update your 4–6 digit login PIN', () => context.push('/change-pin')),
                        _securityRow(context, Icons.password_outlined, 'Change password',
                            'Update your account password', () => context.push('/change-password'),
                            topBorder: true),
                      ],
                    ),
                  ),
                ],
              ),
            ),
          ],
        ),
        ),
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

  Widget _infoRow(BuildContext context, IconData icon, String label, String value,
      {bool trailing = false, VoidCallback? onTap}) {
    final p = context.astra;
    final row = Container(
      decoration: BoxDecoration(border: Border(top: BorderSide(color: p.hairline))),
      padding: const EdgeInsets.symmetric(horizontal: 14, vertical: 11),
      child: Row(
        children: [
          Icon(icon, size: 16, color: p.textMuted),
          const SizedBox(width: 11),
          Expanded(child: Text(label, style: ui(size: 12.5, weight: FontWeight.w600, color: p.ink))),
          Text(value, style: ui(size: 12, weight: FontWeight.w700, color: trailing ? p.textSecondary : p.ink)),
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
