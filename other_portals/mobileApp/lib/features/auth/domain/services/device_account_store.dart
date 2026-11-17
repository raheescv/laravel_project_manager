import 'dart:convert';

import 'package:invo/shared/domain/constants/global_variables.dart';
import 'package:invo/shared/utils/local_storage/local_storage_service.dart';

import '../models/device_account.dart';

/// The roster of users who have signed in on this device, in secure storage.
///
/// Exists so an **offline** till can still authenticate a cashier who has used it
/// before. Without it, a shared till that loses the network can take no further
/// sales the moment anyone locks or signs out — the queue would hold what was
/// already rung up and nothing more, which is the failure offline selling is meant
/// to prevent.
///
/// Deliberately narrow: it only ever answers "has this exact PIN (or username and
/// password) signed in here before, and if so, what session did they have?" It
/// cannot grant a session to someone who has never signed in on this device, and it
/// restores the permissions the last online sign-in cached rather than assuming any.
class DeviceAccountStore {
  /// Newest first, so the list reads as "who uses this till".
  ///
  /// Capped: a till in a busy shop would otherwise accumulate every seasonal member
  /// of staff forever, and an unbounded secret blob is worth avoiding on its own.
  static const int maxAccounts = 12;

  LocalStorageService get _storage => serviceLocator<LocalStorageService>();

  Future<List<DeviceAccount>> all() async {
    try {
      final raw = await _storage.readDeviceAccounts();
      if (raw == null || raw.isEmpty) return const [];
      return [
        for (final row in jsonDecode(raw) as List)
          DeviceAccount.fromJson(Map<String, dynamic>.from(row as Map)),
      ];
    } catch (_) {
      // A corrupt blob must not lock everyone out of the till — the online path
      // still works, and the next successful sign-in rewrites the roster.
      return const [];
    }
  }

  /// Record (or refresh) [account] as a user of this device.
  ///
  /// Keyed on the user id, so signing in again replaces that user's entry rather
  /// than adding a second one — which is what keeps a changed PIN from leaving the
  /// old one working offline.
  Future<void> remember(DeviceAccount account) async {
    final kept = [
      account,
      ...(await all()).where((a) => a.userId != account.userId),
    ];
    await _write(kept.take(maxAccounts).toList());
  }

  /// The rostered entry for [userId], or null. Answers "what does this device
  /// know about the person who is signed in", which is how a PIN is checked
  /// against its owner rather than against whoever used the till last.
  Future<DeviceAccount?> byId(String userId) async {
    if (userId.isEmpty) return null;
    for (final account in await all()) {
      if (account.userId == userId) return account;
    }
    return null;
  }

  /// The rostered user whose PIN is [pin], or null.
  Future<DeviceAccount?> byPin(String pin) async {
    if (pin.isEmpty) return null;
    for (final account in await all()) {
      if (account.matchesPin(pin)) return account;
    }
    return null;
  }

  /// The rostered user matching [username] + [password], or null.
  Future<DeviceAccount?> byCredential(String username, String password) async {
    if (username.trim().isEmpty || password.isEmpty) return null;
    for (final account in await all()) {
      if (account.matchesCredential(username, password)) return account;
    }
    return null;
  }

  /// Replace the PIN kept for [userId] — after they change it from the app.
  ///
  /// Without this the roster keeps opening offline sessions on a PIN the server
  /// has already stopped accepting, and the new one works online only. No-ops
  /// for someone who has never signed in on this device.
  Future<void> setPin(String userId, String pin) async {
    final account = await byId(userId);
    if (account == null || pin.isEmpty) return;
    await remember(account.copyWith(pin: pin));
  }

  /// Move [account] to the front of the roster (an offline sign-in), keeping the
  /// list an honest "who uses this till, most recent first".
  Future<void> touch(DeviceAccount account, DateTime at) =>
      remember(account.copyWith(lastSignInAt: at));

  /// Forget every account. Called when the device is pointed at a different
  /// business — a roster from one tenant must never authenticate against another.
  Future<void> clear() => _storage.clearDeviceAccounts();

  Future<void> _write(List<DeviceAccount> accounts) =>
      _storage.writeDeviceAccounts(jsonEncode([for (final a in accounts) a.toJson()]));
}
