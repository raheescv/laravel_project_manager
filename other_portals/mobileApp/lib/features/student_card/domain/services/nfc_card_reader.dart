import 'package:flutter/foundation.dart';
import 'package:flutter/services.dart';
import 'package:nfc_manager/nfc_manager.dart';
import 'package:nfc_manager/nfc_manager_android.dart';
import 'package:nfc_manager/nfc_manager_ios.dart';

/// Reads a student card's UID over NFC (13.56 MHz — MIFARE / NTAG / ISO 14443).
///
/// Only the tag's hardware identifier is read; nothing is written and no data
/// on the card is trusted. The UID is returned as uppercase hex with no
/// separators, which is the one spelling the server stores and matches
/// (StudentDetail::normalizeCardUid).
///
/// 125 kHz RFID cards cannot be read by a phone at all — those need a USB/Bluetooth
/// reader that types the number, which the tap sheet's text field accepts.
///
/// **One session, many listeners (Android).** The platform has a single reader
/// session for the whole app: starting one replaces the last and stopping one
/// ends it for everybody. Each [NfcCardReader] is therefore a *listener* on one
/// shared session — [start] puts it on top, [stop] takes it off, and only the
/// top listener hears a tap. The session itself runs while any listener is
/// registered and stops when the last leaves, so screens and sheets can open
/// over each other in any order with no gap in between. A gap is what lets
/// Android show its own "New tag collected" screen instead of the app.
///
/// The app also holds the session open on its own ([holdAppSession]) for as
/// long as it is in the foreground, so a card tapped on a screen that does not
/// read cards (Sales, Reports, Link Card…) is absorbed — the reader beeps and
/// nothing happens — instead of Android opening "New tag collected".
///
/// iOS keeps one session per [start]: there a session raises the system NFC
/// sheet, so it must only run while a sheet is actually asking for a card.
class NfcCardReader {
  NfcCardReader();

  static final List<_Listener> _listeners = [];
  static bool _sessionOn = false;

  /// The app itself keeps the session open (see [holdAppSession]).
  static bool _appHold = false;

  /// Keep the shared session open while the app is in the foreground, whether
  /// or not any screen is listening. Call at start-up and again on every
  /// resume: re-enabling is harmless, and it recovers a session that could not
  /// start earlier (NFC switched off at launch, then on).
  static Future<void> holdAppSession() async {
    if (!_shared) return;
    _appHold = true;
    _sessionOn = true;
    await _startSession(_dispatch, null);
  }

  static bool get _shared => !kIsWeb && defaultTargetPlatform == TargetPlatform.android;

  Future<bool> get isAvailable async {
    try {
      return await NfcManager.instance.checkAvailability() == NfcAvailability.enabled;
    } on PlatformException catch (_) {
      // Devices without an NFC stack throw rather than report "unsupported".
      return false;
    } on MissingPluginException catch (_) {
      return false;
    }
  }

  /// Start listening; [onUid] fires once per tapped card, for as long as this
  /// reader is the most recent one started. Call [stop] when done.
  ///
  /// [underneath] registers below every other listener instead of on top: for a
  /// screen-wide listener (the POS) that must only hear taps no sheet wants,
  /// whichever of them happened to register first.
  ///
  /// [accepts] lets a listener pass a tap down to the one below it — a screen
  /// listener says no while its screen is covered, so a screen on top of it
  /// (or the app's own hold) gets the tap instead.
  Future<void> start({
    required void Function(String uid) onUid,
    void Function(String message)? onError,
    bool underneath = false,
    bool Function()? accepts,
  }) async {
    if (!_shared) {
      return _startSession((tag) => _deliver(tag, onUid, onError), onError);
    }
    _listeners.removeWhere((l) => l.owner == this);
    final listener = _Listener(this, onUid, onError, accepts);
    underneath ? _listeners.insert(0, listener) : _listeners.add(listener);
    if (_sessionOn) return;
    _sessionOn = true;
    await _startSession(_dispatch, onError);
  }

  /// Stop listening. On Android the shared session carries on for whoever
  /// started before this reader, and ends only when nobody is left.
  Future<void> stop() async {
    if (!_shared) return _stopSession();
    _listeners.removeWhere((l) => l.owner == this);
    if (_listeners.isNotEmpty || _appHold || !_sessionOn) return;
    _sessionOn = false;
    await _stopSession();
  }

  static void _dispatch(NfcTag tag) {
    // Nobody on this screen reads cards: absorb the tap (see [holdAppSession]).
    final target = _target();
    if (target == null) return;
    _deliver(tag, target.onUid, target.onError);
  }

  /// The most recent listener that will take a tap now.
  static _Listener? _target() {
    for (final l in _listeners.reversed) {
      if (l.accepts?.call() ?? true) return l;
    }
    return null;
  }

  /// A card tapped, as the shared session would report it — for tests, which
  /// have no NFC stack to produce a real tag.
  @visibleForTesting
  static void debugTap(String uid) => _target()?.onUid(uid);

  @visibleForTesting
  static int get debugListenerCount => _listeners.length;

  static void _deliver(NfcTag tag, void Function(String uid) onUid, void Function(String message)? onError) {
    final uid = uidOf(tag);
    if (uid == null) {
      onError?.call('This card could not be read. Try again or type its number.');
      return;
    }
    onUid(uid);
  }

  static Future<void> _startSession(void Function(NfcTag) onDiscovered, void Function(String message)? onError) async {
    try {
      await NfcManager.instance.startSession(
        pollingOptions: {NfcPollingOption.iso14443, NfcPollingOption.iso15693},
        alertMessageIos: 'Hold the student card near the top of the iPhone.',
        invalidateAfterFirstReadIos: true,
        onDiscovered: onDiscovered,
      );
    } on PlatformException catch (e) {
      _sessionOn = false;
      onError?.call(e.message ?? 'NFC is not available on this device.');
    } on MissingPluginException catch (_) {
      // No NFC stack at all (desktop, tests) — same answer as an unavailable one.
      _sessionOn = false;
      onError?.call('NFC is not available on this device.');
    }
  }

  static Future<void> _stopSession() async {
    try {
      await NfcManager.instance.stopSession();
    } on PlatformException catch (_) {
      // No session was running.
    } on MissingPluginException catch (_) {
      // No NFC stack — nothing was started.
    }
  }

  static String? uidOf(NfcTag tag) {
    final Uint8List? id = NfcTagAndroid.from(tag)?.id ??
        MiFareIos.from(tag)?.identifier ??
        Iso15693Ios.from(tag)?.identifier ??
        Iso7816Ios.from(tag)?.identifier;
    if (id == null || id.isEmpty) return null;
    return id.map((b) => b.toRadixString(16).padLeft(2, '0')).join().toUpperCase();
  }

  /// "04:a2:1b 9c" → "04A21B9C". Mirrors the server's normalisation, so a number
  /// typed by a keyboard-wedge reader and one read over NFC compare equal.
  static String normalize(String raw) => raw.replaceAll(RegExp('[^0-9a-fA-F]'), '').toUpperCase();
}

class _Listener {
  _Listener(this.owner, this.onUid, this.onError, this.accepts);

  final NfcCardReader owner;
  final void Function(String uid) onUid;
  final void Function(String message)? onError;
  final bool Function()? accepts;
}
