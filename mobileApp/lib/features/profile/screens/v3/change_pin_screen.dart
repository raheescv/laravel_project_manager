import 'package:flutter/material.dart';
import 'package:invo/features/auth/domain/repository/auth_repository.dart';
import 'package:flutter/services.dart';
import 'package:go_router/go_router.dart';

import 'package:invo/shared/utils/router/http_utils/common_exception.dart';
import 'package:invo/shared/domain/constants/global_variables.dart';
import 'package:invo/shared/domain/helpers/responsive.dart';
import 'package:invo/shared/utils/components/theme/index.dart';
import 'package:invo/shared/widgets/astra_widgets.dart';
import 'package:invo/shared/widgets/tablet_widgets.dart';

/// Wired to POST /change-pin. The backend auth is PIN-based, so this is the
/// real "Change Password" equivalent from the design.
///
/// On a tablet this normally runs *embedded* in the Profile screen's detail
/// pane, so changing a PIN never takes the window away from the profile; the
/// route stays for phones and deep links.
class ChangePinScreen extends StatefulWidget {
  const ChangePinScreen({super.key, this.embedded = false, this.onDone});

  /// Render only the form body — the host supplies the pane and its head.
  final bool embedded;

  /// Where "done" goes when there is no route to pop. Ignored unless [embedded].
  final VoidCallback? onDone;
  @override
  State<ChangePinScreen> createState() => _ChangePinScreenState();
}

class _ChangePinScreenState extends State<ChangePinScreen> {
  final _current = TextEditingController();
  final _next = TextEditingController();
  final _confirm = TextEditingController();
  bool _busy = false;

  @override
  void dispose() {
    _current.dispose();
    _next.dispose();
    _confirm.dispose();
    super.dispose();
  }

  Future<void> _save() async {
    if (_next.text != _confirm.text) {
      _snack('New PIN and confirmation don’t match.');
      return;
    }
    if (_next.text.length < 4) {
      _snack('Use at least a 4-digit PIN.');
      return;
    }
    setState(() => _busy = true);
    try {
      await serviceLocator<AuthRepository>().changePin(_current.text, _next.text);
      if (mounted) {
        ScaffoldMessenger.of(context).showSnackBar(const SnackBar(content: Text('PIN updated')));
        _close();
      }
    } on ApiException catch (e) {
      _snack(e.message);
    } catch (e) {
      _snack('Could not update PIN.');
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
                  title: 'Change MPIN',
                  subtitle: 'Your 4–6 digit login PIN',
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
              title: 'Change MPIN',
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
                          child: Text('Choose a 4–6 digit PIN you don’t use elsewhere.',
                              style: ui(size: 11, weight: FontWeight.w600, color: p.textSecondary, height: 1.35)),
                        ),
                      ],
                    ),
                  ),
                  const SizedBox(height: 18),
                  _pinField('Current PIN', _current),
                  const SizedBox(height: 14),
                  _pinField('New PIN', _next),
                  const SizedBox(height: 14),
                  _pinField('Confirm new PIN', _confirm),
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
                    Expanded(child: AstraButton(label: 'Update PIN', busy: _busy, onTap: _save)),
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
                child: Text('Choose a 4–6 digit PIN you don’t use elsewhere.',
                    style: ui(size: 11.5, weight: FontWeight.w600, color: p.textSecondary, height: 1.35)),
              ),
            ],
          ),
        ),
        const SizedBox(height: 18),
        TabletPanel(
          title: 'MPIN',
          padding: const EdgeInsets.fromLTRB(16, 16, 16, 18),
          child: Column(
            children: [
              _pinField('Current PIN', _current),
              const SizedBox(height: 14),
              _pinField('New PIN', _next),
              const SizedBox(height: 14),
              _pinField('Confirm new PIN', _confirm),
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
              label: _busy ? 'Updating…' : 'Update PIN',
              icon: Icons.check,
              primary: true,
              onTap: _busy ? null : _save,
            ),
          ],
        ),
      ],
    );
  }

  Widget _pinField(String label, TextEditingController c) {
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
            obscureText: true,
            keyboardType: TextInputType.number,
            maxLength: 6,
            inputFormatters: [FilteringTextInputFormatter.digitsOnly],
            style: ui(size: 16, weight: FontWeight.w700, color: p.ink, letterSpacing: 4),
            decoration: InputDecoration(
              counterText: '',
              prefixIcon: Icon(Icons.lock_outline, color: p.textMuted, size: 18),
              border: InputBorder.none,
              contentPadding: const EdgeInsets.symmetric(vertical: 14),
            ),
          ),
        ),
      ],
    );
  }
}
