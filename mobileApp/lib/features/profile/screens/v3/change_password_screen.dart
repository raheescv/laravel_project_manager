import 'dart:async';
import 'package:invo/features/profile/logic/profile_cubit/profile_cubit.dart';
import 'package:flutter/material.dart';
import 'package:go_router/go_router.dart';

import 'package:invo/shared/domain/helpers/responsive.dart';
import 'package:invo/shared/utils/components/theme/index.dart';
import 'package:invo/shared/widgets/astra_widgets.dart';
import 'package:invo/shared/widgets/tablet_widgets.dart';

/// Wired to POST /change-password — the account password used for
/// username/password (credential) login, alongside the PIN.
///
/// On a tablet this normally runs *embedded* in the Profile screen's detail
/// pane, so the profile stays on screen; the route stays for phones and deep
/// links.
class ChangePasswordScreen extends StatefulWidget {
  const ChangePasswordScreen({super.key, this.embedded = false, this.onDone});

  /// Render only the form body — the host supplies the pane and its head.
  final bool embedded;

  /// Where "done" goes when there is no route to pop. Ignored unless [embedded].
  final VoidCallback? onDone;
  @override
  State<ChangePasswordScreen> createState() => _ChangePasswordScreenState();
}

class _ChangePasswordScreenState extends State<ChangePasswordScreen> {
  final _profile = ProfileCubit();
  final _current = TextEditingController();
  final _next = TextEditingController();
  final _confirm = TextEditingController();
  bool _busy = false;
  bool _obscure = true;

  @override
  void dispose() {
    unawaited(_profile.close());
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
    // The repository call, and its error handling, live in the cubit (§10).
    final ok = await _profile.changePassword(_current.text, _next.text);
    if (!mounted) return;
    if (ok) {
      ScaffoldMessenger.of(context).showSnackBar(const SnackBar(content: Text('Password updated')));
      _close();
    } else {
      _snack(_profile.state.errorMessage ?? 'Could not update password.');
    }
    if (mounted) setState(() => _busy = false);
  }

  void _snack(String m) => ScaffoldMessenger.of(context)
    ..clearSnackBars()
    ..showSnackBar(SnackBar(content: Text(m)));

  /// Leave the form: hand back to the host when embedded (no route to pop —
  /// the detail pane just switches), otherwise pop the route.
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

    if (widget.embedded) return _tabletBody(context);

    if (context.isTablet) {
      return Scaffold(
        backgroundColor: Colors.transparent,
        body: AstraBackground(
          child: SafeArea(
            bottom: false,
            child: Column(
              children: [
                TabletPageHead(
                  title: 'Change Password',
                  subtitle: 'Your account login password',
                  leading: TabletIconButton(icon: Icons.chevron_left, onTap: () => context.pop()),
                ),
                Expanded(
                  child: SingleChildScrollView(
                    padding: const EdgeInsets.fromLTRB(24, 24, 24, 40),
                    child: MaxWidthBox(maxWidth: 620, child: _tabletBody(context)),
                  ),
                ),
              ],
            ),
          ),
        ),
      );
    }

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
                      AstraButton(label: 'Cancel', expand: false, gold: false, onTap: _close),
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

  /// The tablet form — identical embedded in the profile pane or standalone.
  Widget _tabletBody(BuildContext context) {
    final p = context.astra;
    return Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        Container(
          padding: const EdgeInsets.all(14),
          decoration: BoxDecoration(
            color: p.tint,
            borderRadius: BorderRadius.circular(14),
            border: Border.all(color: p.primary.withValues(alpha: 0.18)),
          ),
          child: Row(
            children: [
              Icon(Icons.shield_outlined, size: 17, color: p.primary),
              const SizedBox(width: 11),
              Expanded(
                child: Text('Use at least 8 characters you don’t use elsewhere.',
                    style: ui(size: 11.5, weight: FontWeight.w600, color: p.textSecondary, height: 1.35)),
              ),
            ],
          ),
        ),
        const SizedBox(height: 18),
        TabletPanel(
          title: 'Password',
          padding: const EdgeInsets.fromLTRB(16, 16, 16, 18),
          child: Column(
            children: [
              _passwordField('Current password', _current),
              const SizedBox(height: 14),
              _passwordField('New password', _next),
              const SizedBox(height: 14),
              _passwordField('Confirm new password', _confirm),
            ],
          ),
        ),
        const SizedBox(height: 18),
        Row(
          mainAxisAlignment: MainAxisAlignment.end,
          children: [
            TabletActionButton(label: 'Cancel', onTap: _busy ? null : _close),
            const SizedBox(width: 10),
            TabletActionButton(
              label: _busy ? 'Updating…' : 'Update password',
              icon: Icons.check,
              primary: true,
              onTap: _busy ? null : _save,
            ),
          ],
        ),
      ],
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
