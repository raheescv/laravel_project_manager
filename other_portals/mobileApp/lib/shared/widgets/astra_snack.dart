import 'package:flutter/material.dart';

import 'package:invo/shared/domain/helpers/responsive.dart';
import 'package:invo/shared/utils/components/theme/index.dart';

/// How a transient message reads — which picks its colour and icon.
enum AstraSnackKind { info, success, error }

/// The app's one transient message.
///
/// Material's default is a full-bleed black band welded to the bottom edge, deep
/// enough to cover the very buttons the message is usually about — the checkout
/// bar being the worst case. This is the same mechanism underneath
/// ([ScaffoldMessenger], so messages still queue, swipe away and survive a route
/// change), dressed as a compact floating pill that clears the bottom bar and
/// says what kind of news it is with a colour and an icon.
///
/// Every message in the app goes through here; the `snackBarTheme` in
/// `buildAstraTheme` shapes anything that somehow doesn't.
class AstraSnack {
  const AstraSnack._();

  /// Take a handle now, show the message later — for the common shape where the
  /// outcome is only known after an await and the context may be gone by then.
  static AstraSnackHandle capture(BuildContext context) => AstraSnackHandle._(
        ScaffoldMessenger.of(context),
        context.astra.darkSurface,
        MediaQuery.sizeOf(context).width > Breakpoints.contentMaxWidth,
      );

  /// Something went wrong and the action did not happen.
  static void error(BuildContext context, String message, {Duration? duration}) =>
      capture(context).error(message, duration: duration);

  /// It worked — a receipt printed, a draft parked.
  static void success(BuildContext context, String message,
          {Duration? duration, SnackBarAction? action}) =>
      capture(context).success(message, duration: duration, action: action);

  /// Neither good nor bad news, just news.
  static void show(BuildContext context, String message,
          {AstraSnackKind kind = AstraSnackKind.info,
          Duration? duration,
          SnackBarAction? action}) =>
      capture(context).show(message, kind: kind, duration: duration, action: action);
}

/// A messenger captured from a context, so the message survives the await that
/// produced it. Get one from [AstraSnack.capture].
class AstraSnackHandle {
  const AstraSnackHandle._(this._messenger, this._infoColor, this._wide);

  final ScaffoldMessengerState _messenger;
  final Color _infoColor;
  final bool _wide;

  void error(String message, {Duration? duration}) =>
      show(message, kind: AstraSnackKind.error, duration: duration);

  void success(String message, {Duration? duration, SnackBarAction? action}) =>
      show(message, kind: AstraSnackKind.success, duration: duration, action: action);

  void show(
    String message, {
    AstraSnackKind kind = AstraSnackKind.info,
    Duration? duration,
    SnackBarAction? action,
  }) {
    final (Color bg, IconData icon) = switch (kind) {
      AstraSnackKind.error => (AstraPalette.danger, Icons.error_outline),
      AstraSnackKind.success => (AstraPalette.success, Icons.check_circle_outline),
      AstraSnackKind.info => (_infoColor, Icons.info_outline),
    };

    _messenger
      ..clearSnackBars()
      ..showSnackBar(SnackBar(
        content: Row(
          children: [
            Icon(icon, size: 17, color: Colors.white),
            const SizedBox(width: 10),
            Expanded(
              child: Text(message,
                  style: ui(size: 12.5, weight: FontWeight.w700, color: Colors.white, height: 1.35)),
            ),
          ],
        ),
        backgroundColor: bg,
        behavior: SnackBarBehavior.floating,
        shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(15)),
        // An action brings its own layout metrics; overriding the padding then
        // squeezes the button rather than the text.
        padding: action == null ? const EdgeInsets.symmetric(horizontal: 14, vertical: 12) : null,
        elevation: 8,
        duration: duration ?? const Duration(seconds: 3),
        // Capping the pill is only worth it on a tablet — a phone's own margins
        // already hold it to a readable column. A SnackBar takes one or the other.
        width: _wide ? 460 : null,
        margin: _wide ? null : const EdgeInsets.fromLTRB(14, 0, 14, 14),
        action: action,
      ));
  }
}
