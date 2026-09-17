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
class NfcCardReader {
  const NfcCardReader();

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

  /// Start listening; [onUid] fires once per tapped card. Call [stop] when done.
  Future<void> start({required void Function(String uid) onUid, void Function(String message)? onError}) async {
    try {
      await NfcManager.instance.startSession(
        pollingOptions: {NfcPollingOption.iso14443, NfcPollingOption.iso15693},
        alertMessageIos: 'Hold the student card near the top of the iPhone.',
        invalidateAfterFirstReadIos: true,
        onDiscovered: (tag) {
          final uid = uidOf(tag);
          if (uid == null) {
            onError?.call('This card could not be read. Try again or type its number.');
            return;
          }
          onUid(uid);
        },
      );
    } on PlatformException catch (e) {
      onError?.call(e.message ?? 'NFC is not available on this device.');
    }
  }

  Future<void> stop() async {
    try {
      await NfcManager.instance.stopSession();
    } on PlatformException catch (_) {
      // No session was running.
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
