import 'package:flutter/material.dart';
import 'package:flutter_bloc/flutter_bloc.dart';
import 'package:go_router/go_router.dart';

import 'package:invo/features/auth/logic/auth_cubit/auth_cubit.dart';

import 'package:invo/shared/domain/constants/global_variables.dart';
import 'package:invo/shared/domain/helpers/responsive.dart';
import 'package:invo/shared/widgets/astra_widgets.dart';
import 'package:invo/shared/widgets/tablet_widgets.dart';

import '../../logic/profile_cubit/profile_cubit.dart';
import 'account_form_parts.dart';

/// The shared body of Change MPIN / Change password: current + new + confirm,
/// validated locally, submitted through [ProfileCubit].
///
/// Three presentations of one form:
/// * phone — gradient header band, form, docked Cancel / Update bar;
/// * tablet standalone (a deep link) — `TabletPageHead` over a capped column;
/// * [embedded] — the body alone, for the Profile / Settings detail pane on a
///   tablet. "Done" then calls [onDone] instead of popping.
class AccountSecretForm extends StatefulWidget {
  const AccountSecretForm({
    super.key,
    required this.title,
    required this.subtitle,
    required this.hint,
    required this.pin,
    required this.minLength,
    required this.tooShort,
    required this.mismatch,
    required this.submitLabel,
    required this.successMessage,
    required this.failureMessage,
    required this.labels,
    this.embedded = false,
    this.onDone,
  });

  final String title;
  final String subtitle;
  final String hint;
  final bool pin;
  final int minLength;
  final String tooShort;
  final String mismatch;
  final String submitLabel;
  final String successMessage;
  final String failureMessage;

  /// (current, new, confirm) field labels.
  final (String, String, String) labels;
  final bool embedded;
  final VoidCallback? onDone;

  @override
  State<AccountSecretForm> createState() => _AccountSecretFormState();
}

class _AccountSecretFormState extends State<AccountSecretForm> {
  final _profile = serviceLocator<ProfileCubit>();
  final _current = TextEditingController();
  final _next = TextEditingController();
  final _confirm = TextEditingController();
  bool _busy = false;
  bool _obscure = true;

  /// Validation / server error, shown inline above the buttons. A snackbar
  /// would float over the phone's docked button bar and swallow the retry tap.
  String? _error;

  @override
  void dispose() {
    _profile.close();
    _current.dispose();
    _next.dispose();
    _confirm.dispose();
    super.dispose();
  }

  Future<void> _save() async {
    if (_next.text != _confirm.text) {
      setState(() => _error = widget.mismatch);
      return;
    }
    if (_next.text.length < widget.minLength) {
      setState(() => _error = widget.tooShort);
      return;
    }
    setState(() {
      _busy = true;
      _error = null;
    });
    final ok = widget.pin
        ? await _profile.changePin(_current.text, _next.text)
        : await _profile.changePassword(_current.text, _next.text);
    if (!mounted) return;
    setState(() => _busy = false);
    if (ok) {
      // Keep this device's sign-in in step: the keypad's PIN length and any
      // biometric credential saved with the old PIN / password.
      final auth = context.read<AuthCubit>();
      await (widget.pin ? auth.applyChangedPin(_next.text) : auth.applyChangedPassword(_next.text));
      if (!mounted) return;
      showAccountSnack(context, widget.successMessage);
      _close();
    } else {
      setState(() => _error = _profile.state.errorMessage ?? widget.failureMessage);
    }
  }

  /// Hand back to the host when embedded, otherwise pop the route.
  void _close() {
    if (widget.embedded) {
      widget.onDone?.call();
      return;
    }
    context.pop();
  }

  List<Widget> _fields() => [
        AccountSecretField(
            label: widget.labels.$1,
            controller: _current,
            pin: widget.pin,
            obscure: _obscure,
            onToggleObscure: () => setState(() => _obscure = !_obscure)),
        const SizedBox(height: 14),
        AccountSecretField(
            label: widget.labels.$2,
            controller: _next,
            pin: widget.pin,
            obscure: _obscure,
            onToggleObscure: () => setState(() => _obscure = !_obscure)),
        const SizedBox(height: 14),
        AccountSecretField(
            label: widget.labels.$3,
            controller: _confirm,
            pin: widget.pin,
            obscure: _obscure,
            onToggleObscure: () => setState(() => _obscure = !_obscure)),
      ];

  @override
  Widget build(BuildContext context) {
    if (widget.embedded) return _tabletBody();

    if (context.isTablet) {
      return Scaffold(
        backgroundColor: Colors.transparent,
        body: AstraBackground(
          child: SafeArea(
            bottom: false,
            child: Column(children: [
              TabletPageHead(
                title: widget.title,
                subtitle: widget.subtitle,
                leading: context.canPop() ? TabletIconButton(icon: Icons.chevron_left, onTap: () => context.pop()) : null,
              ),
              Expanded(
                child: SingleChildScrollView(
                  padding: const EdgeInsets.fromLTRB(24, 24, 24, 40),
                  child: MaxWidthBox(maxWidth: 620, child: _tabletBody()),
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
            leading: HeaderIconButton(icon: Icons.chevron_left, onTap: () => context.pop()),
            title: widget.title,
          ),
          Expanded(
            child: MaxWidthBox(
              maxWidth: 520,
              child: ListView(
                padding: const EdgeInsets.fromLTRB(18, 16, 18, 24),
                children: [
                  AccountHint(text: widget.hint),
                  const SizedBox(height: 18),
                  ..._fields(),
                  if (_error != null) ...[const SizedBox(height: 14), AccountError(message: _error!)],
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
                child: Row(children: [
                  AstraButton(label: 'Cancel', expand: false, onTap: _close),
                  const SizedBox(width: 11),
                  Expanded(child: AstraButton(label: widget.submitLabel, busy: _busy, onTap: _save)),
                ]),
              ),
            ),
          ),
        ]),
      ),
    );
  }

  /// The tablet form — identical embedded in a pane or standalone.
  Widget _tabletBody() => Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          AccountHint(text: widget.hint),
          const SizedBox(height: 18),
          TabletPanel(
            title: widget.pin ? 'MPIN' : 'Password',
            padding: const EdgeInsets.fromLTRB(16, 16, 16, 18),
            child: Column(children: _fields()),
          ),
          if (_error != null) ...[const SizedBox(height: 14), AccountError(message: _error!)],
          const SizedBox(height: 18),
          Row(mainAxisAlignment: MainAxisAlignment.end, children: [
            TabletActionButton(label: 'Cancel', onTap: _busy ? null : _close),
            const SizedBox(width: 10),
            TabletActionButton(
              label: _busy ? 'Updating…' : widget.submitLabel,
              icon: Icons.check,
              primary: true,
              onTap: _busy ? null : _save,
            ),
          ]),
        ],
      );
}
