import 'package:flutter/material.dart';
import 'package:go_router/go_router.dart';
import 'package:provider/provider.dart';

import '../../core/responsive.dart';
import '../../state/auth_controller.dart';
import '../../theme/theme.dart';
import '../../widgets/astra_widgets.dart';

class ProfileScreen extends StatelessWidget {
  const ProfileScreen({super.key});

  @override
  Widget build(BuildContext context) {
    final p = context.astra;
    final user = context.watch<AuthController>().user;
    if (user == null) return const Scaffold(body: Center(child: CircularProgressIndicator()));

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
                      Monogram(letter: user.initial, size: 78, fontSize: 32),
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
                        _infoRow(context, Icons.badge_outlined, 'Employee', user.code.isEmpty ? user.id : user.code, trailing: true),
                        _infoRow(context, Icons.location_on_outlined, 'Branch', user.branchId ?? '—', trailing: true),
                        _infoRow(context, Icons.event_available_outlined, 'Day status',
                            user.dayOpen ? 'Open' : 'Closed', trailing: true),
                      ],
                    ),
                  ),
                  const SizedBox(height: 11),
                  AstraCard(
                    radius: 15,
                    onTap: () => context.push('/change-pin'),
                    child: Row(
                      children: [
                        IconChip(icon: Icons.lock_outline, size: 30, radius: 9, bg: p.tint),
                        const SizedBox(width: 11),
                        Expanded(child: Text('MPIN & security', style: ui(size: 12.5, weight: FontWeight.w700, color: p.ink))),
                        Icon(Icons.chevron_right, color: p.textMuted, size: 18),
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

  Widget _infoRow(BuildContext context, IconData icon, String label, String value, {bool trailing = false}) {
    final p = context.astra;
    return Container(
      decoration: BoxDecoration(border: Border(top: BorderSide(color: p.hairline))),
      padding: const EdgeInsets.symmetric(horizontal: 14, vertical: 11),
      child: Row(
        children: [
          Icon(icon, size: 16, color: p.textMuted),
          const SizedBox(width: 11),
          Expanded(child: Text(label, style: ui(size: 12.5, weight: FontWeight.w600, color: trailing ? p.ink : p.ink))),
          Text(value, style: ui(size: 12, weight: FontWeight.w700, color: trailing ? p.textSecondary : p.ink)),
        ],
      ),
    );
  }
}
