import 'package:flutter/material.dart';
import 'package:flutter/services.dart';

import '../utils/friendly_error.dart';

/// Shown when the boot sequence in `main()` throws.
///
/// Deliberately dependency-free: it renders before the service locator, theme
/// and router exist, so it cannot use `context.astra`, `ui()` or anything that
/// assumes a successful boot.
///
/// What it puts first is the plain-language reading of the failure
/// ([FriendlyError]) and the one thing worth trying, because the person holding
/// the device is a cashier with a queue, not an engineer. The raw exception is
/// still here — folded away, copyable in one tap — because support needs it.
class BootFailureApp extends StatefulWidget {
  const BootFailureApp({super.key, required this.error, this.onRetry});

  final Object error;

  /// Re-runs the boot sequence. When it succeeds this screen is replaced by the
  /// app, so a transient failure costs a tap instead of a force-quit.
  final Future<void> Function()? onRetry;

  @override
  State<BootFailureApp> createState() => _BootFailureAppState();
}

class _BootFailureAppState extends State<BootFailureApp> {
  static const _ink = Color(0xFF0C1E1A);
  static const _muted = Color(0xFFA9C2BA);
  static const _gold = Color(0xFFE8C36A);

  bool _showDetails = false;
  bool _retrying = false;
  bool _copied = false;

  String get _details => '${widget.error}';

  Future<void> _retry() async {
    final retry = widget.onRetry;
    if (retry == null || _retrying) return;
    setState(() => _retrying = true);
    try {
      await retry();
    } finally {
      // A successful retry has already replaced this screen; if we're still
      // mounted the second attempt failed too, so give the button back.
      if (mounted) setState(() => _retrying = false);
    }
  }

  Future<void> _copy() async {
    await Clipboard.setData(ClipboardData(text: _details));
    if (!mounted) return;
    setState(() => _copied = true);
  }

  @override
  Widget build(BuildContext context) {
    final reading = FriendlyError.forBoot(widget.error);

    return MaterialApp(
      debugShowCheckedModeBanner: false,
      home: Scaffold(
        backgroundColor: _ink,
        body: SafeArea(
          child: Center(
            child: SingleChildScrollView(
              padding: const EdgeInsets.all(28),
              child: ConstrainedBox(
                constraints: const BoxConstraints(maxWidth: 460),
                child: Column(
                  mainAxisAlignment: MainAxisAlignment.center,
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    const Icon(Icons.warning_amber_rounded, size: 44, color: _gold),
                    const SizedBox(height: 16),
                    Text(reading.title,
                        style: const TextStyle(
                            color: Colors.white,
                            fontSize: 22,
                            fontWeight: FontWeight.w800,
                            height: 1.25)),
                    const SizedBox(height: 10),
                    Text(reading.message,
                        style: const TextStyle(
                            color: _muted, fontSize: 14, height: 1.45)),
                    const SizedBox(height: 14),
                    _hint(reading.hint),
                    const SizedBox(height: 22),
                    if (widget.onRetry != null) _retryButton(),
                    const SizedBox(height: 10),
                    _detailsToggle(),
                    if (_showDetails) ...[
                      const SizedBox(height: 10),
                      _detailsBox(),
                    ],
                  ],
                ),
              ),
            ),
          ),
        ),
      ),
    );
  }

  /// The one next step, set apart from the explanation so it survives a glance.
  Widget _hint(String hint) => Container(
        width: double.infinity,
        padding: const EdgeInsets.symmetric(horizontal: 14, vertical: 12),
        decoration: BoxDecoration(
          color: _gold.withValues(alpha: 0.10),
          borderRadius: BorderRadius.circular(12),
          border: Border.all(color: _gold.withValues(alpha: 0.35)),
        ),
        child: Row(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            const Icon(Icons.lightbulb_outline_rounded, size: 17, color: _gold),
            const SizedBox(width: 10),
            Expanded(
              child: Text(hint,
                  style: const TextStyle(
                      color: Color(0xFFF0DFB4),
                      fontSize: 13.5,
                      height: 1.4,
                      fontWeight: FontWeight.w600)),
            ),
          ],
        ),
      );

  Widget _retryButton() => SizedBox(
        width: double.infinity,
        height: 50,
        child: FilledButton.icon(
          onPressed: _retrying ? null : _retry,
          style: FilledButton.styleFrom(
            backgroundColor: _gold,
            foregroundColor: _ink,
            disabledBackgroundColor: _gold.withValues(alpha: 0.35),
            shape: RoundedRectangleBorder(
                borderRadius: BorderRadius.circular(14)),
          ),
          icon: _retrying
              ? const SizedBox(
                  width: 16,
                  height: 16,
                  child: CircularProgressIndicator(
                      strokeWidth: 2, color: _ink))
              : const Icon(Icons.refresh_rounded, size: 19),
          label: Text(_retrying ? 'Starting…' : 'Try again',
              style: const TextStyle(fontSize: 15, fontWeight: FontWeight.w700)),
        ),
      );

  Widget _detailsToggle() => Align(
        alignment: Alignment.centerLeft,
        child: TextButton.icon(
          onPressed: () => setState(() => _showDetails = !_showDetails),
          style: TextButton.styleFrom(
            foregroundColor: _muted,
            padding: const EdgeInsets.symmetric(horizontal: 6, vertical: 4),
            minimumSize: Size.zero,
            tapTargetSize: MaterialTapTargetSize.shrinkWrap,
          ),
          icon: Icon(
              _showDetails
                  ? Icons.keyboard_arrow_up_rounded
                  : Icons.keyboard_arrow_down_rounded,
              size: 18),
          label: const Text('Technical details',
              style: TextStyle(fontSize: 13, fontWeight: FontWeight.w600)),
        ),
      );

  Widget _detailsBox() => Container(
        width: double.infinity,
        padding: const EdgeInsets.all(14),
        decoration: BoxDecoration(
          color: Colors.white.withValues(alpha: 0.06),
          borderRadius: BorderRadius.circular(12),
        ),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            // Bounded: a platform exception carries a whole Java stack, and an
            // unbounded one pushes the retry button off a phone screen.
            ConstrainedBox(
              constraints: const BoxConstraints(maxHeight: 220),
              child: SingleChildScrollView(
                child: SelectableText(
                  _details,
                  style: const TextStyle(
                      color: Color(0xFFCFE3DC),
                      fontSize: 12,
                      height: 1.35,
                      fontFamily: 'monospace'),
                ),
              ),
            ),
            const SizedBox(height: 8),
            Align(
              alignment: Alignment.centerRight,
              child: TextButton.icon(
                onPressed: _copy,
                style: TextButton.styleFrom(
                  foregroundColor: _copied ? _gold : _muted,
                  padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 4),
                  minimumSize: Size.zero,
                  tapTargetSize: MaterialTapTargetSize.shrinkWrap,
                ),
                icon: Icon(
                    _copied
                        ? Icons.check_rounded
                        : Icons.copy_all_rounded,
                    size: 16),
                label: Text(_copied ? 'Copied' : 'Copy for support',
                    style: const TextStyle(
                        fontSize: 12.5, fontWeight: FontWeight.w600)),
              ),
            ),
          ],
        ),
      );
}
