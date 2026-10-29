import 'dart:async';
import 'dart:math' as math;
import 'dart:ui' show ImageFilter;

import 'package:flutter/material.dart';
import 'package:flutter/services.dart';
import 'package:intl/intl.dart';
import 'package:provider/provider.dart';

import 'package:invo/shared/domain/helpers/responsive.dart';
import 'package:invo/features/auth/logic/auth_cubit/auth_cubit.dart';
import 'package:invo/shared/utils/components/theme/index.dart';
import 'package:invo/shared/widgets/app_version.dart';
import 'package:invo/shared/widgets/qloud_logo.dart';
import 'package:invo/features/auth/widgets/v3/connection_sheet.dart';
import 'package:invo/shared/widgets/astra_snack.dart';

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
  // The tablet card shows a clock; this ticks it over on the minute.
  DateTime _now = DateTime.now();
  Timer? _clock;

  @override
  void initState() {
    super.initState();
    _clock = Timer.periodic(const Duration(seconds: 15), (_) {
      final now = DateTime.now();
      if (mounted && now.minute != _now.minute) setState(() => _now = now);
    });
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
    _clock?.cancel();
    _shake.dispose();
    _userCtl.dispose();
    _passCtl.dispose();
    super.dispose();
  }

  Future<void> _submit() async {
    final auth = context.read<AuthCubit>();
    // Locked till: the cashier who locked it is matched locally and comes
    // straight back in. Anyone else falls through to a real login inside
    // `unlock`, so taking over the terminal still just works.
    final ok = auth.isLocked ? await auth.unlock(_pin) : await auth.login(_pin);
    if (!ok && mounted) {
      unawaited(HapticFeedback.heavyImpact());
      unawaited(_shake.forward(from: 0));
      setState(() => _pin = '');
      AstraSnack.error(context, auth.error ?? 'Login failed');
    }
  }

  /// First name of whoever locked the till — enough to confirm whose session is
  /// waiting, without putting the full record on a counter-facing screen.
  String _firstName(AuthCubit auth) {
    final name = (auth.user?.name ?? '').trim();
    if (name.isEmpty) return 'This till';
    return name.split(RegExp(r'\s+')).first;
  }

  String _subtitle(AuthCubit auth, bool locked) => locked
      ? '${_firstName(auth)} · enter your MPIN to carry on'
      : _mode == 'pin'
          ? 'Enter your MPIN to continue'
          : 'Sign in with your username & password';

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
    // Locked = a live session waiting behind the lock screen, not a signed-out
    // device. Same screen, because it's the same keypad and the same shake —
    // only the wording and where the PIN is checked differ.
    final locked = auth.isLocked;
    final tablet = context.isTablet;
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
            if (tablet) ..._tabletBackdrop()
            else ...[
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
                child: QloudLogomark(height: 320, color: Colors.white),
              ),
            ),
            ],
            if (tablet) _tabletLayout(auth, locked)
            else SafeArea(
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
                    // White, not the palette inks: the mark sits on a
                    // primary→primaryDark gradient, so a preset-coloured
                    // logo would be the same hue as its own background.
                    const QloudLogomark(height: 70, color: Colors.white),
                    const SizedBox(height: 16),
                    Text('QLOUD',
                        style: ui(size: 13, weight: FontWeight.w800, color: p.accent, letterSpacing: 7)),
                    const SizedBox(height: 4),
                    Text('POS',
                        style: ui(size: 9, weight: FontWeight.w700, color: Colors.white54, letterSpacing: 6)),
                    const SizedBox(height: 12),
                    Text(locked ? 'Locked' : 'Welcome back',
                        style: serif(size: 29, color: Colors.white)),
                    const SizedBox(height: 4),
                    Text(_subtitle(auth, locked),
                        style: ui(size: 12.5, weight: FontWeight.w500, color: Colors.white70)),
                    const SizedBox(height: 22),
                    if (_mode == 'pin') ...[
                      _shakingDots(p.accent),
                      const Spacer(),
                      _keypad(),
                    ] else ...[
                      _passwordForm(auth, p.accent),
                      const Spacer(),
                    ],
                    const SizedBox(height: 16),
                    _footerToggle(p.accent),
                    if (locked) _signOutLink(),
                    const SizedBox(height: 14),
                    AppVersionText(
                        style: ui(size: 10, weight: FontWeight.w600, color: Colors.white38, letterSpacing: 1.2)),
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

  /// Tablet: one frosted card on the gradient — identity | keypad side by side
  /// in landscape, stacked in portrait — instead of the phone column stranded
  /// mid-screen (sample B in docs/mobile-tablet-login-preview.html). Scrolls
  /// only when the keyboard squeezes it, so the till's 686pt never does.
  Widget _tabletLayout(AuthCubit auth, bool locked) {
    final gold = context.astra.accent;
    return SafeArea(
      child: Stack(
        children: [
          LayoutBuilder(
            builder: (context, constraints) {
              final portrait = constraints.maxHeight > constraints.maxWidth;
              final keyHeight = portrait ? 66.0 : constraints.maxHeight < 650 ? 54.0 : 62.0;
              final keypadSide = _tabletKeypadSide(auth, locked, gold, keyHeight, portrait: portrait);
              return SingleChildScrollView(
                child: ConstrainedBox(
                  constraints: BoxConstraints(minHeight: constraints.maxHeight),
                  child: Center(
                    child: Padding(
                      padding: const EdgeInsets.symmetric(horizontal: 40, vertical: 24),
                      child: Column(
                        mainAxisSize: MainAxisSize.min,
                        children: [
                          portrait
                              ? _glassCard(
                                  width: 470,
                                  padding: const EdgeInsets.fromLTRB(40, 36, 40, 30),
                                  child: Column(
                                    mainAxisSize: MainAxisSize.min,
                                    children: [
                                      _tabletIdentity(auth, locked, portrait: true),
                                      const SizedBox(height: 26),
                                      keypadSide,
                                    ],
                                  ),
                                )
                              : _glassCard(
                                  width: math.min(900, constraints.maxWidth - 120),
                                  padding: const EdgeInsets.symmetric(horizontal: 38, vertical: 34),
                                  child: IntrinsicHeight(
                                    child: Row(
                                      crossAxisAlignment: CrossAxisAlignment.stretch,
                                      children: [
                                        Expanded(flex: 10, child: _tabletIdentity(auth, locked, portrait: false)),
                                        Container(
                                          width: 1,
                                          margin: const EdgeInsets.symmetric(horizontal: 36),
                                          color: Colors.white.withValues(alpha: 0.14),
                                        ),
                                        Expanded(flex: 11, child: keypadSide),
                                      ],
                                    ),
                                  ),
                                ),
                          const SizedBox(height: 16),
                          AppVersionText(
                              prefix: 'QLOUD POS · ',
                              style: ui(size: 10, weight: FontWeight.w600, color: Colors.white38, letterSpacing: 1.2)),
                        ],
                      ),
                    ),
                  ),
                ),
              );
            },
          ),
          Positioned(
            top: 8,
            right: 12,
            child: IconButton(
              onPressed: () => ConnectionSheet.show(context),
              icon: Icon(Icons.settings_outlined, color: Colors.white.withValues(alpha: 0.8)),
              tooltip: 'Connection settings',
            ),
          ),
        ],
      ),
    );
  }

  /// Two soft glows and a large watermark behind the frosted card, so the blur
  /// has something to catch.
  List<Widget> _tabletBackdrop() {
    final p = context.astra;
    Widget glow(Color color) => Container(
          width: 520,
          height: 520,
          decoration: BoxDecoration(
            shape: BoxShape.circle,
            gradient: RadialGradient(colors: [color, Colors.transparent]),
          ),
        );
    return [
      Positioned(left: -160, top: -200, child: glow(p.accent.withValues(alpha: 0.42))),
      Positioned(right: -180, bottom: -220, child: glow(p.primary.withValues(alpha: 0.55))),
      const Positioned(
        right: -40,
        bottom: -30,
        child: Opacity(opacity: 0.06, child: QloudLogomark(height: 380, color: Colors.white)),
      ),
    ];
  }

  /// No drop shadow: through a translucent card it reads as a dark slab inside
  /// the glass, not as lift — the hairline border and the blur do that job.
  Widget _glassCard({required double width, required EdgeInsets padding, required Widget child}) {
    final radius = BorderRadius.circular(30);
    return SizedBox(
      width: width,
      child: ClipRRect(
        borderRadius: radius,
        child: BackdropFilter(
          filter: ImageFilter.blur(sigmaX: 18, sigmaY: 18),
          child: Container(
            padding: padding,
            decoration: BoxDecoration(
              color: Colors.white.withValues(alpha: 0.075),
              borderRadius: radius,
              border: Border.all(color: Colors.white.withValues(alpha: 0.16)),
            ),
            child: child,
          ),
        ),
      ),
    );
  }

  /// Brand, greeting and — in landscape — the clock, spread down the card's
  /// left half. A locked till shows whose session is waiting instead of the mark.
  Widget _tabletIdentity(AuthCubit auth, bool locked, {required bool portrait}) {
    final p = context.astra;
    final align = portrait ? CrossAxisAlignment.center : CrossAxisAlignment.start;
    final textAlign = portrait ? TextAlign.center : TextAlign.start;
    final brand = Column(
      crossAxisAlignment: align,
      children: [
        locked ? _lockedAvatar(auth) : const QloudLogomark(height: 62, color: Colors.white),
        const SizedBox(height: 14),
        Text('QLOUD', style: ui(size: 13, weight: FontWeight.w800, color: p.accent, letterSpacing: 7)),
        const SizedBox(height: 4),
        Text('POS', style: ui(size: 9, weight: FontWeight.w700, color: Colors.white54, letterSpacing: 6)),
      ],
    );
    final greeting = Column(
      crossAxisAlignment: align,
      children: [
        Text(locked ? 'Locked' : 'Welcome back',
            textAlign: textAlign, style: serif(size: portrait ? 32 : 36, color: Colors.white)),
        const SizedBox(height: 6),
        Text(_subtitle(auth, locked),
            textAlign: textAlign, style: ui(size: 13.5, weight: FontWeight.w500, color: Colors.white70)),
      ],
    );
    if (portrait) return Column(children: [brand, const SizedBox(height: 18), greeting]);
    return Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      mainAxisAlignment: MainAxisAlignment.spaceBetween,
      children: [
        brand,
        Padding(padding: const EdgeInsets.symmetric(vertical: 24), child: greeting),
        Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Text(DateFormat('HH:mm').format(_now),
                style: ui(size: 30, weight: FontWeight.w800, color: Colors.white, letterSpacing: -0.5)),
            const SizedBox(height: 2),
            Text(DateFormat('EEEE, d MMMM').format(_now),
                style: ui(size: 12, weight: FontWeight.w600, color: Colors.white54)),
          ],
        ),
      ],
    );
  }

  Widget _lockedAvatar(AuthCubit auth) => Container(
        width: 60,
        height: 60,
        alignment: Alignment.center,
        decoration: BoxDecoration(
          shape: BoxShape.circle,
          color: Colors.white.withValues(alpha: 0.14),
          border: Border.all(color: context.astra.accent.withValues(alpha: 0.6), width: 2),
        ),
        child: Text(_firstName(auth).characters.first.toUpperCase(),
            style: ui(size: 23, weight: FontWeight.w800, color: Colors.white)),
      );

  Widget _tabletKeypadSide(AuthCubit auth, bool locked, Color gold, double keyHeight, {required bool portrait}) =>
      Column(
        mainAxisSize: MainAxisSize.min,
        mainAxisAlignment: MainAxisAlignment.center,
        crossAxisAlignment: CrossAxisAlignment.stretch,
        children: [
          if (_mode == 'pin') ...[
            Padding(padding: const EdgeInsets.symmetric(vertical: 4), child: _shakingDots(gold)),
            SizedBox(height: portrait ? 16 : 14),
            _keypad(keyHeight: keyHeight, gap: 12, digitSize: 26),
            const SizedBox(height: 4),
          ] else ...[
            _passwordForm(auth, gold),
            const SizedBox(height: 14),
          ],
          _footerToggle(gold),
          if (locked) Center(child: _signOutLink()),
        ],
      );

  Widget _shakingDots(Color gold) => AnimatedBuilder(
        animation: _shake,
        builder: (_, child) {
          final dx = (_shake.value == 0)
              ? 0.0
              : 8 * (1 - _shake.value) *
                  (1 - 2 * ((_shake.value * 5) % 1).abs());
          return Transform.translate(offset: Offset(dx, 0), child: child);
        },
        child: _dots(gold),
      );

  // Ending the session is the slow path, so it stays a deliberate choice
  // rather than something a sale does.
  Widget _signOutLink() => Padding(
        padding: const EdgeInsets.only(top: 10),
        child: GestureDetector(
          onTap: () => context.read<AuthCubit>().logout(),
          child: Text('Sign out completely',
              style: ui(size: 11.5, weight: FontWeight.w700, color: Colors.white60)),
        ),
      );

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

  Widget _keypad({double keyHeight = 50, double gap = 9, double digitSize = 24}) {
    // Built from Rows (not a GridView) so it has an intrinsic height and works
    // inside IntrinsicHeight / the scroll-when-short layout.
    const rows = [
      ['1', '2', '3'],
      ['4', '5', '6'],
      ['7', '8', '9'],
      ['', '0', '<'],
    ];
    Widget cell(String k) {
      if (k.isEmpty) return Expanded(child: SizedBox(height: keyHeight));
      if (k == '<') {
        return Expanded(
          child: _key(
            child: const Icon(Icons.backspace_outlined, color: Colors.white70, size: 20),
            onTap: _backspace,
            bare: true,
            height: keyHeight,
          ),
        );
      }
      return Expanded(
        child: _key(
            child: Text(k, style: serif(size: digitSize, color: Colors.white)),
            onTap: () => _tap(k),
            height: keyHeight),
      );
    }

    return Column(
      children: [
        for (final row in rows)
          Padding(
            padding: EdgeInsets.only(bottom: gap),
            child: Row(
              children: [
                for (var i = 0; i < row.length; i++) ...[
                  cell(row[i]),
                  if (i < row.length - 1) SizedBox(width: gap),
                ],
              ],
            ),
          ),
      ],
    );
  }

  Widget _key({required Widget child, required VoidCallback onTap, bool bare = false, double height = 50}) =>
      GestureDetector(
        onTap: onTap,
        child: Container(
          height: height,
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
    // Locked: the local prompt is the whole check, so this never touches the API.
    final msg = await auth.unlockWithBiometric();
    if (msg != null && mounted) _toast(msg);
    // Refresh readiness in case a fresh credential was just stored elsewhere.
    if (mounted) {
      final ready = await auth.biometricReady();
      if (mounted) setState(() => _bioReady = ready);
    }
  }

  void _toast(String m) => AstraSnack.error(context, m);
}
