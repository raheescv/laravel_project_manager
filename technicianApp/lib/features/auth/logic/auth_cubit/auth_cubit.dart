import 'dart:convert';

import 'package:invo/flavors.dart';
import 'package:invo/shared/domain/constants/app_config.dart';
import 'package:invo/shared/domain/constants/global_variables.dart';
import 'package:invo/shared/domain/models/index.dart';
import 'package:invo/shared/logic/base/holder_cubit.dart';
import 'package:invo/shared/utils/local_storage/local_storage_service.dart';
import 'package:invo/shared/utils/router/http_utils/common_exception.dart';
import 'package:invo/shared/utils/router/http_utils/http_service.dart';
import 'package:local_auth/local_auth.dart';

import '../../domain/repository/auth_repository.dart';

enum AuthStatus { unknown, signedOut, signedIn }

/// Owns the session: PIN login, token, current user, and connection config.
class AuthCubit extends HolderCubit {
  AuthCubit();

  HttpService get _http => serviceLocator<HttpService>();
  AuthRepository get _repo => serviceLocator<AuthRepository>();
  LocalStorageService get _storage => serviceLocator<LocalStorageService>();

  AuthStatus status = AuthStatus.unknown;
  ApiUser? user;
  String? error;
  bool busy = false;

  /// Fired after a successful sign-in (used to default the active branch to the
  /// freshly-authenticated user's home branch).
  void Function(ApiUser user)? onAuthenticated;

  final LocalAuthentication _localAuth = LocalAuthentication();

  AppConfig get config => _http.config;

  Future<void> bootstrap() async {
    _http.onUnauthorized = _forceSignOut;
    final token = await _storage.readToken();
    final cached = _storage.userJson;
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
    refresh();
  }

  Future<void> updateConnection(
      {required String baseUrl, required String tenant}) async {
    _http.config = AppConfig(baseUrl: baseUrl.trim(), tenant: tenant.trim());
    await _storage.setBaseUrl(baseUrl.trim());
    await _storage.setTenant(tenant.trim());
    refresh();
  }

  Future<bool> login(String pin) =>
      _runLogin(() => _repo.login(pin), biometric: {'mode': 'pin', 'pin': pin});

  Future<bool> loginWithCredential(String username, String password) =>
      _runLogin(
        () => _repo.loginCredential(username, password),
        biometric: {'mode': 'cred', 'username': username, 'password': password},
      );

  Future<bool> _runLogin(
    Future<({String token, ApiUser user})> Function() attempt, {
    required Map<String, String> biometric,
  }) async {
    busy = true;
    error = null;
    refresh();
    try {
      final res = await attempt();
      await _storage.writeToken(res.token);
      await _storage.setUserJson(jsonEncode(res.user.toJson()));
      await _storage.writeBiometric(jsonEncode(biometric));
      user = res.user;
      status = AuthStatus.signedIn;
      busy = false;
      refresh();
      onAuthenticated?.call(res.user);
      return true;
    } on ApiException catch (e) {
      error = e.message;
    } catch (e) {
      error = 'Could not reach ${config.baseUrl}. Check the Base URL & tenant '
          '(gear icon) and that the server is running.';
      if (F.isDev) {
        error = '$error\n\n[debug] $e';
      }
    }
    busy = false;
    refresh();
    return false;
  }

  // ---- Biometric (Touch ID / Face ID / fingerprint) ----

  Future<bool> biometricReady() async {
    try {
      final supported = await _localAuth.isDeviceSupported();
      if (!supported) return false;
      return (await _storage.readBiometric()) != null;
    } catch (_) {
      return false;
    }
  }

  /// Mode ('pin' | 'cred') of the last successful sign-in, used to default the
  /// login screen to whichever method the user actually uses.
  Future<String?> lastLoginMode() async {
    final saved = await _storage.readBiometric();
    if (saved == null) return null;
    try {
      return (jsonDecode(saved) as Map<String, dynamic>)['mode'] as String?;
    } catch (_) {
      return null;
    }
  }

  Future<String?> loginWithBiometric() async {
    final saved = await _storage.readBiometric();
    if (saved == null) {
      return 'Sign in once with your PIN or password to enable biometric login.';
    }
    bool ok;
    try {
      ok = await _localAuth.authenticate(
        localizedReason: 'Authenticate to sign in to Invo',
        options: const AuthenticationOptions(
            biometricOnly: false, stickyAuth: true),
      );
    } catch (e) {
      return 'Biometric authentication is unavailable on this device.';
    }
    if (!ok) return null; // user cancelled — no error toast

    final cred = Map<String, dynamic>.from(jsonDecode(saved));
    final success = cred['mode'] == 'cred'
        ? await loginWithCredential(
            cred['username'].toString(), cred['password'].toString())
        : await login(cred['pin'].toString());
    return success
        ? null
        : (error ?? 'Saved credential no longer valid. Please sign in again.');
  }

  Future<void> syncDaySession({
    required String status,
    String? openedAt,
    String? date,
    String? lastClosedAt,
  }) async {
    final u = user;
    if (u == null) return;
    user = u.copyWith(
      daySessionStatus: status,
      daySessionDate: date,
      daySessionOpenedAt:
          status == 'open' ? (openedAt ?? u.daySessionOpenedAt) : '',
      lastClosedSessionAt: lastClosedAt,
    );
    refresh();
    await _storage.setUserJson(jsonEncode(user!.toJson()));
  }

  Future<void> logout() async {
    try {
      await _repo.logout();
    } catch (_) {
      // Best-effort; clear locally regardless.
    }
    await _clear();
  }

  void _forceSignOut() {
    _clear();
  }

  Future<void> _clear() async {
    await _storage.clearToken();
    await _storage.clearUser();
    user = null;
    status = AuthStatus.signedOut;
    refresh();
  }
}
