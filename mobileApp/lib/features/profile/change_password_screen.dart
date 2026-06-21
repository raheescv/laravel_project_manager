import 'package:flutter/material.dart';
import 'package:go_router/go_router.dart';
import 'package:provider/provider.dart';

import '../../core/api_client.dart';
import '../../core/api_service.dart';
import '../../core/responsive.dart';
import '../../theme/theme.dart';
import '../../widgets/astra_widgets.dart';

/// Wired to POST /change-password — the account password used for
/// username/password (credential) login, alongside the PIN.
class ChangePasswordScreen extends StatefulWidget {
  const ChangePasswordScreen({super.key});
  @override
  State<ChangePasswordScreen> createState() => _ChangePasswordScreenState();
}

class _ChangePasswordScreenState extends State<ChangePasswordScreen> {
  final _current = TextEditingController();
  final _next = TextEditingController();
  final _confirm = TextEditingController();
  bool _busy = false;
  bool _obscure = true;

  @override
  void dispose() {
    _current.dispose();
    _next.dispose();
    _confirm.dispose();
    super.dispose();
  }

  Future<void> _save() async {
    if (_next.text != _confirm.text) {
      _snack('New password and confirmation don’t match.');
      return;
    }
    if (_next.text.length < 8) {
      _snack('Use at least an 8-character password.');
      return;
    }
    setState(() => _busy = true);
    try {
      await context.read<ApiService>().changePassword(_current.text, _next.text);
      if (mounted) {
        ScaffoldMessenger.of(context).showSnackBar(const SnackBar(content: Text('Password updated')));
        context.pop();
      }
    } on ApiException catch (e) {
      _snack(e.message);
    } catch (e) {
      _snack('Could not update password.');
    }
    if (mounted) setState(() => _busy = false);
  }

  void _snack(String m) => ScaffoldMessenger.of(context)
    ..clearSnackBars()
    ..showSnackBar(SnackBar(content: Text(m)));

  @override
  Widget build(BuildContext context) {
    final p = context.astra;
    return Scaffold(
      body: AstraBackground(
        child: Column(
          children: [
            EmeraldHeader(
              leading: HeaderIconButton(icon: Icons.chevron_left, onTap: () => context.pop()),
              title: 'Change Password',
            ),
            Expanded(
              child: MaxWidthBox(
                maxWidth: 520,
                child: ListView(
                  padding: const EdgeInsets.fromLTRB(18, 16, 18, 24),
                  children: [
                    Container(
                      padding: const EdgeInsets.all(13),
                      decoration: BoxDecoration(
                        color: p.tint,
                        borderRadius: BorderRadius.circular(13),
                        border: Border.all(color: p.primary.withValues(alpha: 0.18)),
                      ),
                      child: Row(
                        children: [
                          Icon(Icons.shield_outlined, size: 16, color: p.primary),
                          const SizedBox(width: 11),
                          Expanded(
                            child: Text('Use at least 8 characters you don’t use elsewhere.',
                                style: ui(size: 11, weight: FontWeight.w600, color: p.textSecondary, height: 1.35)),
                          ),
                        ],
                      ),
                    ),
                    const SizedBox(height: 18),
                    _passwordField('Current password', _current),
                    const SizedBox(height: 14),
                    _passwordField('New password', _next),
                    const SizedBox(height: 14),
                    _passwordField('Confirm new password', _confirm),
                  ],
                ),
              ),
            ),
            SafeArea(
              top: false,
              child: MaxWidthBox(
                maxWidth: 520,
                child: Padding(
                  padding: const EdgeInsets.fromLTRB(14, 0, 14, 14),
                  child: Row(
                    children: [
                      AstraButton(label: 'Cancel', expand: false, gold: false, onTap: () => context.pop()),
                      const SizedBox(width: 11),
                      Expanded(child: AstraButton(label: 'Update password', busy: _busy, onTap: _save)),
                    ],
                  ),
                ),
              ),
            ),
          ],
        ),
      ),
    );
  }

  Widget _passwordField(String label, TextEditingController c) {
    final p = context.astra;
    final t = context.astraTheme;
    return Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        Text(label.toUpperCase(), style: ui(size: 10, weight: FontWeight.w800, color: p.textMuted, letterSpacing: 0.8)),
        const SizedBox(height: 7),
        Container(
          decoration: BoxDecoration(color: p.card, borderRadius: BorderRadius.circular(14), boxShadow: t.softShadow),
          child: TextField(
            controller: c,
            obscureText: _obscure,
            keyboardType: TextInputType.visiblePassword,
            style: ui(size: 15, weight: FontWeight.w700, color: p.ink),
            decoration: InputDecoration(
              prefixIcon: Icon(Icons.lock_outline, color: p.textMuted, size: 18),
              suffixIcon: IconButton(
                icon: Icon(_obscure ? Icons.visibility_outlined : Icons.visibility_off_outlined,
                    color: p.textMuted, size: 18),
                onPressed: () => setState(() => _obscure = !_obscure),
              ),
              border: InputBorder.none,
              contentPadding: const EdgeInsets.symmetric(vertical: 14),
            ),
          ),
        ),
      ],
    );
  }
}
