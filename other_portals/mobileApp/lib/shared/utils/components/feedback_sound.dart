import 'dart:async';

import 'package:flutter/foundation.dart';
import 'package:flutter/services.dart';

/// Short feedback sounds for the till.
///
/// [cardRefused] is the "failed card" buzz: Android beeps the moment an NFC
/// card is read, before the app knows whether it can pay, so a card that turns
/// out unknown, blocked or unreadable gets a sound of its own (and a vibration)
/// the cashier hears without looking at the screen.
///
/// Android only — native ToneGenerator on the `qloud/sound` channel
/// (SoundPlugin.kt). Elsewhere it is the vibration alone. Never throws: a
/// missing sound must not break the sale.
class FeedbackSound {
  const FeedbackSound._();

  static const _channel = MethodChannel('qloud/sound');

  static bool get _android => !kIsWeb && defaultTargetPlatform == TargetPlatform.android;

  static void cardRefused() {
    unawaited(HapticFeedback.vibrate());
    if (!_android) return;
    unawaited(() async {
      try {
        await _channel.invokeMethod<void>('error');
      } on PlatformException catch (_) {
        // The tone could not play; the vibration and the message still do.
      } on MissingPluginException catch (_) {
        // Tests, or an engine without the channel.
      }
    }());
  }
}
