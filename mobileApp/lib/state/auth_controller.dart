import 'dart:convert';

import 'package:flutter/foundation.dart';
import 'package:local_auth/local_auth.dart';

import '../core/api_client.dart';
import '../core/api_service.dart';
import '../core/config.dart';
import '../core/storage.dart';
import '../models/models.dart';

enum AuthStatus { unknown, signedOut, signedIn }

/// Owns the session: PIN login, token, current user, and connection config.
class AuthController extends ChangeNotifier {
  AuthController({required this.client, required this.service, required this.storage});

  final ApiClient client;
  final ApiService service;
  final Storage storage;

  AuthStatus status = AuthStatus.unknown;
  ApiUser? user;
  String? error;
  bool busy = false;

  final LocalAuthentication _localAuth = LocalAuthentication();

  AppConfig get config => client.config;

  Future<void> bootstrap() async {
    client.onUnauthorized = _forceSignOut;
    final token = await storage.readToken();
    final cached = storage.userJson;
    if (token != null && cached != null) {
      try {
        user = ApiUser.fromJson(Map<String, dynamic>.from(jsonDecode(cached)));
        status = AuthStatus.signedIn;
      } catch (_) {
        status = AuthStatus.signedOut;
      }
    } else {
      status = AuthStatus.signedOut;
    }
    notifyListeners();
  }

  Future<void> updateConnection({required String baseUrl, required String tenant}) async {
    client.config = AppConfig(baseUrl: baseUrl.trim(), tenant: tenant.trim());
    await storage.setBaseUrl(baseUrl.trim());
    await storage.setTenant(tenant.trim());
    notifyListeners();
  }

  Future<bool> login(String pin) =>
      _runLogin(() => service.login(pin), biometric: {'mode': 'pin', 'pin': pin});

  Future<bool> loginWithCredential(String username, String password) => _runLogin(
        () => service.loginCredential(username, password),
        biometric: {'mode': 'cred', 'username': username, 'password': password},
      );

  /// Shared login pipeline: run [attempt], persist token + user, and remember the
  /// credential for biometric replay on success.
  Future<bool> _runLogin(
    Future<({String token, ApiUser user})> Function() attempt, {
    required Map<String, String> biometric,
  }) async {
    busy = true;
    error = null;
    notifyListeners();
    try {
      final res = await attempt();
      await storage.writeToken(res.token);
      await storage.setUserJson(jsonEncode(res.user.toJson()));
      await storage.writeBiometric(jsonEncode(biometric));
      user = res.user;
      status = AuthStatus.signedIn;
      busy = false;
      notifyListeners();
      return true;
    } on ApiException catch (e) {
      error = e.message;
    } catch (e) {
      error = 'Could not reach ${config.baseUrl}. Check the Base URL & tenant '
          '(gear icon) and that the server is running.';
      // In debug, surface the underlying transport error (TLS/DNS/socket) so a
      // generic "could not reach" is never a black box during development.
      if (kDebugMode) {
        debugPrint('login() transport error -> $e');
        error = '$error\n\n[debug] $e';
      }
    }
    busy = false;
    notifyListeners();
    return false;
  }

  // ---- Biometric (Touch ID / Face ID / fingerprint) ----

  /// True when the device supports biometrics AND a credential has been saved
  /// from a previous successful login.
  Future<bool> biometricReady() async {
    try {
      final supported = await _localAuth.isDeviceSupported();
      if (!supported) return false;
      return (await storage.readBiometric()) != null;
    } catch (_) {
      return false;
    }
  }

  /// Prompt for biometrics; on success replay the saved credential to sign in.
  /// Returns null on success, or a user-facing message on failure.
  Future<String?> loginWithBiometric() async {
    final saved = await storage.readBiometric();
    if (saved == null) {
      return 'Sign in once with your PIN or password to enable biometric login.';
    }
    bool ok;
    try {
      ok = await _localAuth.authenticate(
        localizedReason: 'Authenticate to sign in to Astra Salon',
        options: const AuthenticationOptions(biometricOnly: false, stickyAuth: true),
      );
    } catch (e) {
      return 'Biometric authentication is unavailable on this device.';
    }
    if (!ok) return null; // user cancelled — no error toast

    final cred = Map<String, dynamic>.from(jsonDecode(saved));
    final success = cred['mode'] == 'cred'
        ? await loginWithCredential(cred['username'].toString(), cred['password'].toString())
        : await login(cred['pin'].toString());
    return success ? null : (error ?? 'Saved credential no longer valid. Please sign in again.');
  }

  Future<void> logout() async {
    try {
      await service.logout();
    } catch (_) {
      // Best-effort; clear locally regardless.
    }
    await _clear();
  }

  void _forceSignOut() {
    _clear();
  }

  Future<void> _clear() async {
    await storage.clearToken();
    await storage.clearUser();
    user = null;
    status = AuthStatus.signedOut;
    notifyListeners();
  }
}
