import 'package:flutter/material.dart';
import 'package:flutter/services.dart';
import 'package:provider/provider.dart';

import 'package:invo/shared/domain/helpers/responsive.dart';
import 'package:invo/features/auth/logic/auth_cubit/auth_cubit.dart';
import 'package:invo/shared/utils/components/theme/index.dart';
import 'package:invo/shared/widgets/invo_logo.dart';
import 'package:invo/features/auth/widgets/v3/connection_sheet.dart';

class LoginScreen extends StatefulWidget {
  const LoginScreen({super.key});
  @override
  State<LoginScreen> createState() => _LoginScreenState();
}

class _LoginScreenState extends State<LoginScreen> with SingleTickerProviderStateMixin {
  String _pin = '';
  String _mode = 'pin'; // 'pin' | 'password'
  final _userCtl = TextEditingController();
  final _passCtl = TextEditingController();
  bool _obscure = true;
  bool _bioReady = false;
  late final AnimationController _shake =
      AnimationController(vsync: this, duration: const Duration(milliseconds: 420));

  @override
  void initState() {
    super.initState();
    WidgetsBinding.instance.addPostFrameCallback((_) async {
      final auth = context.read<AuthCubit>();
      final lastMode = await auth.lastLoginMode();
      final ready = await auth.biometricReady();
      if (mounted) {
        setState(() {
          if (lastMode == 'cred') _mode = 'password';
          _bioReady = ready;
        });
      }
    });
  }

  @override
  void dispose() {
    _shake.dispose();
    _userCtl.dispose();
    _passCtl.dispose();
    super.dispose();
  }

  Future<void> _submit() async {
    final auth = context.read<AuthCubit>();
    final ok = await auth.login(_pin);
    if (!ok && mounted) {
      HapticFeedback.heavyImpact();
      _shake.forward(from: 0);
      setState(() => _pin = '');
      ScaffoldMessenger.of(context)
        ..clearSnackBars()
        ..showSnackBar(SnackBar(content: Text(auth.error ?? 'Login failed')));
    }
  }

  void _tap(String d) {
    if (_pin.length >= 6) return;
    HapticFeedback.selectionClick();
    setState(() => _pin += d);
    if (_pin.length == 4) _submit();
  }

  void _backspace() {
    if (_pin.isEmpty) return;
    setState(() => _pin = _pin.substring(0, _pin.length - 1));
  }

  @override
  Widget build(BuildContext context) {
    final p = context.astra;
    final auth = context.watch<AuthCubit>();
    return Scaffold(
      body: Container(
        decoration: BoxDecoration(
          gradient: LinearGradient(
            begin: Alignment.topCenter,
            end: Alignment.bottomCenter,
            colors: [p.primary, p.primaryDark, Color.lerp(p.primaryDark, Colors.black, 0.2)!],
          ),
        ),
        child: Stack(
          children: [
            Positioned(
              right: -80,
              top: -70,
              child: Container(
                width: 260,
                height: 260,
                decoration: BoxDecoration(
                  shape: BoxShape.circle,
                  gradient: RadialGradient(
                    colors: [p.accent.withValues(alpha: 0.4), Colors.transparent],
                  ),
                ),
              ),
            ),
            Positioned(
              right: -34,
              bottom: 10,
              child: Opacity(
                opacity: 0.06,
                child: InvoLogomark(height: 320, color: Colors.white),
              ),
            ),
            SafeArea(
              child: MaxWidthBox(
                maxWidth: 460,
                child: LayoutBuilder(
                  builder: (context, constraints) => SingleChildScrollView(
                    child: ConstrainedBox(
                      constraints: BoxConstraints(minHeight: constraints.maxHeight),
                      child: IntrinsicHeight(
                        child: Padding(
                padding: const EdgeInsets.fromLTRB(28, 8, 28, 18),
                child: Column(
                  children: [
                    Align(
                      alignment: Alignment.centerRight,
                      child: IconButton(
                        onPressed: () => ConnectionSheet.show(context),
                        icon: Icon(Icons.settings_outlined,
                            color: Colors.white.withValues(alpha: 0.8)),
                        tooltip: 'Connection settings',
                      ),
                    ),
                    const SizedBox(height: 4),
                    const InvoLogomark(height: 70),
                    const SizedBox(height: 16),
                    Text('INVO',
                        style: ui(size: 13, weight: FontWeight.w700, color: p.accent, letterSpacing: 7)),
                    const SizedBox(height: 4),
                    Text('SALON POS',
                        style: ui(size: 9, weight: FontWeight.w700, color: Colors.white54, letterSpacing: 4)),
                    const SizedBox(height: 12),
                    Text('Welcome back', style: serif(size: 29, color: Colors.white)),
                    const SizedBox(height: 4),
                    Text(_mode == 'pin' ? 'Enter your MPIN to continue' : 'Sign in with your username & password',
                        style: ui(size: 12.5, weight: FontWeight.w500, color: Colors.white70)),
                    const SizedBox(height: 22),
                    if (_mode == 'pin') ...[
                      AnimatedBuilder(
                        animation: _shake,
                        builder: (_, child) {
                          final dx = (_shake.value == 0)
                              ? 0.0
                              : 8 * (1 - _shake.value) *
                                  (1 - 2 * ((_shake.value * 5) % 1).abs());
                          return Transform.translate(offset: Offset(dx, 0), child: child);
                        },
                        child: _dots(p.accent),
                      ),
                      const Spacer(),
                      _keypad(),
                    ] else ...[
                      _passwordForm(auth, p.accent),
                      const Spacer(),
                    ],
                    const SizedBox(height: 16),
                    _footerToggle(p.accent),
                  ],
                ),
              ),
              ),
              ),
              ),
              ),
              ),
            ),
            // Loading overlay — sits above the layout so the keypad never shifts.
            if (auth.busy)
              Positioned.fill(
                child: AbsorbPointer(
                  child: Container(
                    color: Colors.black.withValues(alpha: 0.28),
                    alignment: Alignment.center,
                    child: Container(
                      padding: const EdgeInsets.all(22),
                      decoration: BoxDecoration(
                        color: p.primaryDark,
                        borderRadius: BorderRadius.circular(20),
                        border: Border.all(color: p.accent.withValues(alpha: 0.35)),
                        boxShadow: [BoxShadow(color: Colors.black.withValues(alpha: 0.4), blurRadius: 30)],
                      ),
                      child: CircularProgressIndicator(color: p.accent),
                    ),
                  ),
                ),
              ),
          ],
        ),
      ),
    );
  }

  Widget _dots(Color gold) => Row(
        mainAxisAlignment: MainAxisAlignment.center,
        children: List.generate(4, (i) {
          final filled = i < _pin.length;
          return Container(
            margin: const EdgeInsets.symmetric(horizontal: 7),
            width: 13,
            height: 13,
            decoration: BoxDecoration(
              shape: BoxShape.circle,
              color: filled ? gold : Colors.white.withValues(alpha: 0.18),
            ),
          );
        }),
      );

  Widget _keypad() {
    // Built from Rows (not a GridView) so it has an intrinsic height and works
    // inside IntrinsicHeight / the scroll-when-short layout.
    const rows = [
      ['1', '2', '3'],
      ['4', '5', '6'],
      ['7', '8', '9'],
      ['', '0', '<'],
    ];
    Widget cell(String k) {
      if (k.isEmpty) return const Expanded(child: SizedBox(height: 50));
      if (k == '<') {
        return Expanded(
          child: _key(
            child: const Icon(Icons.backspace_outlined, color: Colors.white70, size: 20),
            onTap: _backspace,
            bare: true,
          ),
        );
      }
      return Expanded(
        child: _key(child: Text(k, style: serif(size: 24, color: Colors.white)), onTap: () => _tap(k)),
      );
    }

    return Column(
      children: [
        for (final row in rows)
          Padding(
            padding: const EdgeInsets.only(bottom: 9),
            child: Row(
              children: [
                for (var i = 0; i < row.length; i++) ...[
                  cell(row[i]),
                  if (i < row.length - 1) const SizedBox(width: 9),
                ],
              ],
            ),
          ),
      ],
    );
  }

  Widget _key({required Widget child, required VoidCallback onTap, bool bare = false}) =>
      GestureDetector(
        onTap: onTap,
        child: Container(
          height: 50,
          decoration: bare
              ? null
              : BoxDecoration(
                  color: Colors.white.withValues(alpha: 0.1),
                  borderRadius: BorderRadius.circular(14),
                  border: Border.all(color: Colors.white.withValues(alpha: 0.14)),
                ),
          alignment: Alignment.center,
          child: child,
        ),
      );

  Widget _footerToggle(Color gold) {
    final p = context.astra;
    Widget seg(String label, String mode) {
      final active = _mode == mode;
      return Expanded(
        child: GestureDetector(
          onTap: () => setState(() {
            _mode = mode;
            _pin = '';
          }),
          child: Container(
            padding: const EdgeInsets.symmetric(vertical: 9),
            decoration: BoxDecoration(
              gradient: active ? p.accentGradient : null,
              borderRadius: BorderRadius.circular(30),
            ),
            alignment: Alignment.center,
            child: Text(label,
                style: ui(
                    size: 12,
                    weight: active ? FontWeight.w800 : FontWeight.w700,
                    color: active ? p.primaryDark : Colors.white70)),
          ),
        ),
      );
    }

    return Row(
      children: [
        Expanded(
          child: Container(
            padding: const EdgeInsets.all(4),
            decoration: BoxDecoration(
              color: Colors.white.withValues(alpha: 0.1),
              borderRadius: BorderRadius.circular(30),
              border: Border.all(color: Colors.white.withValues(alpha: 0.14)),
            ),
            child: Row(children: [seg('MPIN', 'pin'), seg('Password', 'password')]),
          ),
        ),
        const SizedBox(width: 10),
        GestureDetector(
          onTap: _biometric,
          child: Container(
            width: 48,
            height: 48,
            decoration: BoxDecoration(
              shape: BoxShape.circle,
              gradient: _bioReady ? p.accentGradient : null,
              color: _bioReady ? null : Colors.white.withValues(alpha: 0.1),
              border: _bioReady ? null : Border.all(color: Colors.white.withValues(alpha: 0.14)),
            ),
            child: Icon(Icons.fingerprint,
                color: _bioReady ? p.primaryDark : Colors.white.withValues(alpha: 0.6), size: 22),
          ),
        ),
      ],
    );
  }

  Widget _passwordForm(AuthCubit auth, Color gold) {
    Widget field({
      required IconData icon,
      required String hint,
      required TextEditingController controller,
      bool obscure = false,
      Widget? suffix,
      TextInputType? keyboard,
      void Function(String)? onSubmitted,
    }) {
      return Container(
        margin: const EdgeInsets.only(bottom: 12),
        padding: const EdgeInsets.symmetric(horizontal: 14),
        decoration: BoxDecoration(
          color: Colors.white.withValues(alpha: 0.1),
          borderRadius: BorderRadius.circular(14),
          border: Border.all(color: Colors.white.withValues(alpha: 0.16)),
        ),
        child: Row(
          children: [
            Icon(icon, color: gold, size: 18),
            const SizedBox(width: 11),
            Expanded(
              child: TextField(
                controller: controller,
                obscureText: obscure,
                keyboardType: keyboard,
                onSubmitted: onSubmitted,
                style: ui(size: 14, weight: FontWeight.w600, color: Colors.white),
                cursorColor: gold,
                decoration: InputDecoration(
                  isDense: true,
                  hintText: hint,
                  hintStyle: ui(size: 13, weight: FontWeight.w500, color: Colors.white54),
                  border: InputBorder.none,
                  contentPadding: const EdgeInsets.symmetric(vertical: 15),
                ),
              ),
            ),
            if (suffix != null) suffix,
          ],
        ),
      );
    }

    return Column(
      children: [
        field(
          icon: Icons.person_outline,
          hint: 'Username, email or code',
          controller: _userCtl,
          keyboard: TextInputType.emailAddress,
        ),
        field(
          icon: Icons.lock_outline,
          hint: 'Password',
          controller: _passCtl,
          obscure: _obscure,
          onSubmitted: (_) => _submitCredential(),
          suffix: GestureDetector(
            onTap: () => setState(() => _obscure = !_obscure),
            child: Icon(_obscure ? Icons.visibility_outlined : Icons.visibility_off_outlined,
                color: Colors.white60, size: 18),
          ),
        ),
        const SizedBox(height: 6),
        GestureDetector(
          onTap: auth.busy ? null : _submitCredential,
          child: Container(
            width: double.infinity,
            padding: const EdgeInsets.symmetric(vertical: 15),
            decoration: BoxDecoration(gradient: context.astra.accentGradient, borderRadius: BorderRadius.circular(14)),
            alignment: Alignment.center,
            child: Text('Sign in', style: ui(size: 14.5, weight: FontWeight.w800, color: context.astra.primaryDark)),
          ),
        ),
      ],
    );
  }

  Future<void> _submitCredential() async {
    FocusScope.of(context).unfocus();
    final username = _userCtl.text.trim();
    final password = _passCtl.text;
    if (username.isEmpty || password.isEmpty) {
      _toast('Enter your username and password.');
      return;
    }
    final auth = context.read<AuthCubit>();
    final ok = await auth.loginWithCredential(username, password);
    if (!ok && mounted) _toast(auth.error ?? 'Login failed');
  }

  Future<void> _biometric() async {
    final auth = context.read<AuthCubit>();
    final msg = await auth.loginWithBiometric();
    if (msg != null && mounted) _toast(msg);
    // Refresh readiness in case a fresh credential was just stored elsewhere.
    if (mounted) {
      final ready = await auth.biometricReady();
      if (mounted) setState(() => _bioReady = ready);
    }
  }

  void _toast(String m) => ScaffoldMessenger.of(context)
    ..clearSnackBars()
    ..showSnackBar(SnackBar(content: Text(m)));
}
