import 'dart:convert';

import 'package:invo/shared/domain/constants/app_config.dart';
import 'package:invo/shared/domain/constants/global_variables.dart';
import 'package:invo/shared/domain/models/index.dart';
import 'package:flutter/foundation.dart';
import 'package:equatable/equatable.dart';
import 'package:flutter_bloc/flutter_bloc.dart';
import 'package:invo/shared/domain/repository/catalog_snapshot_repository.dart';
import 'package:invo/shared/logic/connectivity_cubit/connectivity_cubit.dart';
import 'package:invo/shared/utils/local_storage/local_storage_service.dart';
import 'package:invo/shared/utils/local_storage/offline_db.dart';
import 'package:invo/shared/utils/router/http_utils/common_exception.dart';
import 'package:invo/shared/utils/router/http_utils/http_service.dart';
import 'package:invo/shared/utils/router/http_utils/reachability.dart';
import 'package:local_auth/local_auth.dart';

import '../../domain/models/device_account.dart';
import '../../domain/repository/auth_repository.dart';
import '../../domain/services/device_account_store.dart';

part 'auth_state.dart';

/// [locked] is a live session behind the lock screen: the token, the cached
/// user and every warm cubit are still there, so coming back is a local PIN
/// check rather than a fresh sign-in. Everything outside this file treats it
/// as "not signed in", which is what keeps the router honest.
enum AuthStatus { unknown, signedOut, locked, signedIn }

/// Owns the session: PIN login, token, current user, and connection config.
class AuthCubit extends Cubit<AuthState> {
  AuthCubit() : super(const AuthState());

  HttpService get _http => serviceLocator<HttpService>();
  AuthRepository get _repo => serviceLocator<AuthRepository>();
  LocalStorageService get _storage => serviceLocator<LocalStorageService>();

  final DeviceAccountStore _accounts = DeviceAccountStore();

  // Read facade over `state` — existing call sites (including the router's
  // redirect, which reads `auth.status`) were left untouched.
  AuthStatus get status => state.status;
  ApiUser? get user => state.user;
  String? get error => state.errorMessage;
  bool get busy => state.busy;

  /// Fired after a successful sign-in (used to default the active branch to the
  /// freshly-authenticated user's home branch).
  void Function(ApiUser user)? onAuthenticated;

  final LocalAuthentication _localAuth = LocalAuthentication();

  AppConfig get config => _http.config;

  /// Whether the signed-in user holds the given Spatie permission slug.
  /// Mirrors the backend's EnsureMobilePermission gate so UI stays in sync
  /// with what the API will actually allow.
  bool hasPermission(String permission) => state.hasPermission(permission);

  /// Test seam: establish a session without a round-trip. Production code goes
  /// through [bootstrap] or [login] — the state is otherwise read-only.
  @visibleForTesting
  void seedSession(ApiUser user, {AuthStatus status = AuthStatus.signedIn}) =>
      emit(state.copyWith(user: user, status: status));

  Future<void> bootstrap() async {
    _http.onUnauthorized = _forceSignOut;
    final token = await _storage.readToken();
    final cached = _storage.userJson;
    if (token != null && cached != null) {
      try {
        emit(state.copyWith(
          user: ApiUser.fromJson(Map<String, dynamic>.from(jsonDecode(cached))),
          // A till locked when the app was last closed comes back locked —
          // otherwise force-quitting would be a way around the lock screen.
          status: _storage.authLocked ? AuthStatus.locked : AuthStatus.signedIn,
        ));
      } catch (_) {
        emit(state.copyWith(status: AuthStatus.signedOut, clearUser: true));
      }
    } else {
      emit(state.copyWith(status: AuthStatus.signedOut));
    }
  }

  Future<void> updateConnection(
      {required String baseUrl, required String tenant}) async {
    // Repointing the device at a different business invalidates everything this
    // device holds for the old one: a PIN remembered for one tenant must never
    // open a session against another, and the cached catalog, lookups and photos
    // are that shop's stock and staff, not this one's.
    final changed = baseUrl.trim() != config.baseUrl || tenant.trim() != config.tenant;
    if (changed) {
      await _accounts.clear();
      await _clearOfflineData();
    }
    _http.config = AppConfig(baseUrl: baseUrl.trim(), tenant: tenant.trim());
    await _storage.setBaseUrl(baseUrl.trim());
    await _storage.setTenant(tenant.trim());
    // The connection lives on HttpService, not in the state — emit so screens
    // showing the base URL / tenant repaint.
    emit(state.copyWith());
  }

  /// Drop the catalog snapshot, the lookups and the cached photos.
  ///
  /// Goes through [CatalogSnapshotRepository] when it is registered, because the
  /// photo files are as much the old shop's catalog as the prices are; the raw
  /// database wipe is the fallback for a boot that has not built the locator yet.
  Future<void> _clearOfflineData() async {
    try {
      if (serviceLocator.isRegistered<CatalogSnapshotRepository>()) {
        await serviceLocator<CatalogSnapshotRepository>().clear();
        return;
      }
      await OfflineDb.clearCatalog();
    } catch (_) {
      // Never block repointing the device on a cache that would not drop; the
      // snapshot is re-provisioned against the new connection anyway.
    }
  }

  Future<bool> login(String pin) => _runLogin(
        () => _repo.login(pin),
        biometric: {'mode': 'pin', 'pin': pin},
        offline: () => _accounts.byPin(pin),
      );

  Future<bool> loginWithCredential(String username, String password) => _runLogin(
        () => _repo.loginCredential(username, password),
        biometric: {'mode': 'cred', 'username': username, 'password': password},
        offline: () => _accounts.byCredential(username, password),
      );

  /// [offline] resolves the same person from the device's own roster, and is used
  /// only when the server could not be reached at all. See [_signInOffline].
  Future<bool> _runLogin(
    Future<({String token, ApiUser user})> Function() attempt, {
    required Map<String, String> biometric,
    required Future<DeviceAccount?> Function() offline,
  }) async {
    emit(state.copyWith(busy: true, clearError: true));
    try {
      final res = await attempt();
      await _storage.writeToken(res.token);
      await _storage.setUserJson(jsonEncode(res.user.toJson()));
      // Stamped with the user: the blob is one slot on a till several people
      // use, and a saved PIN nobody owns cannot be checked against anybody.
      await _storage.writeBiometric(
          jsonEncode({...biometric, 'user_id': res.user.id}));
      await _storage.setAuthLocked(false);
      // Remember them as a user of this till, so the next outage does not lock
      // them out of it.
      await _remember(res, biometric);
      emit(state.copyWith(
          user: res.user, status: AuthStatus.signedIn, busy: false));
      onAuthenticated?.call(res.user);
      return true;
    } on ApiException catch (e) {
      // The server answered and refused. That is a real verdict — a wrong PIN, a
      // deactivated account — and must never be second-guessed from a local cache.
      emit(state.copyWith(busy: false, errorMessage: e.message));
    } catch (e) {
      // Never reached the server. Whether this person may work the till is a
      // question this device has answered before, so it can answer it again.
      if (isServerUnreachable(e) && await _signInOffline(await offline())) return true;

      // The exception itself goes to the console, never to the person signing in.
      // A cashier at a till cannot act on a DioException, and a stack trace in a
      // snackbar reads as a broken app rather than as a network they can fix.
      debugPrint('[auth] sign-in could not reach ${config.baseUrl}: $e');
      emit(state.copyWith(busy: false, errorMessage: _connectionMessage()));
    }
    return false;
  }

  /// What to tell someone whose sign-in never reached the server.
  ///
  /// Split on whether the device has a network at all, because the two have
  /// different fixes and only one of them is the user's to make: no interface means
  /// turn wifi or data on, while a live interface that cannot reach the server means
  /// the connection or the server itself needs looking at.
  String _connectionMessage() {
    final connectivity = serviceLocator.isRegistered<ConnectivityCubit>()
        ? serviceLocator<ConnectivityCubit>()
        : null;
    // Null-safe on purpose: before the first signal nothing is known, and the
    // no-network wording is the likelier and more actionable of the two.
    final hasInterface = connectivity?.state.hasInterface ?? false;

    return hasInterface
        ? 'Can’t reach the server. Please check your connection and try again.'
        : 'No internet connection. Please connect and try again.';
  }

  /// Restore [account]'s session without a round-trip.
  ///
  /// The session restored is exactly the one their last **online** sign-in cached,
  /// permissions and all — this grants nothing the server had not already granted,
  /// and the server re-checks every request regardless. A user who has since been
  /// deactivated gets in far enough to keep selling into the outbox, and their first
  /// request on reconnect 401s onto the normal forced-sign-out path.
  ///
  /// Returns false when there is no such account, which lets the caller fall through
  /// to the ordinary "could not reach the server" error.
  Future<bool> _signInOffline(DeviceAccount? account) async {
    if (account == null) return false;
    try {
      final user = ApiUser.fromJson(Map<String, dynamic>.from(jsonDecode(account.userJson)));
      // The stored token is what lets the outbox drain post under this user once
      // the network is back.
      await _storage.writeToken(account.token);
      await _storage.setUserJson(account.userJson);
      // Point the saved credential at whoever is actually signed in. Leaving the
      // previous cashier's there is what used to let their PIN unlock this
      // session — and would have signed them in again on the biometric tap.
      await _storage.writeBiometric(jsonEncode(account.credential));
      await _storage.setAuthLocked(false);
      emit(state.copyWith(user: user, status: AuthStatus.signedIn, busy: false));
      // Best-effort: an offline sign-in still counts as using this till.
      try {
        await _accounts.touch(account, DateTime.now());
      } catch (_) {
        // Costs only the roster's ordering.
      }
      // Fired as on any sign-in: a different cashier may work a different branch,
      // and the branch is what scopes the cached catalog they are about to sell from.
      onAuthenticated?.call(user);
      return true;
    } catch (_) {
      // A corrupt cached payload is not something to sign anybody in on.
      return false;
    }
  }

  /// Record this sign-in against the device, keyed on the user.
  Future<void> _remember(
    ({String token, ApiUser user}) res,
    Map<String, String> biometric,
  ) async {
    try {
      await _accounts.remember(DeviceAccount(
        userId: res.user.id,
        name: res.user.name,
        token: res.token,
        userJson: jsonEncode(res.user.toJson()),
        lastSignInAt: DateTime.now(),
        pin: biometric['pin'] ?? '',
        username: biometric['username'] ?? '',
        password: biometric['password'] ?? '',
      ));
    } catch (_) {
      // Best-effort: failing to remember costs an offline sign-in later, never
      // this one.
    }
  }

  /// Users who have signed in on this device — for a "not you?" style picker, and
  /// for tests.
  Future<List<DeviceAccount>> deviceAccounts() => _accounts.all();

  // ---- Terminal lock ----
  //
  // A shared till hands over between cashiers constantly, so the handover has
  // to be cheap. Signing out and back in is not: it costs a login round-trip
  // plus everything that hangs off `onAuthenticated` — the branch default, and
  // through it a full catalog refetch. Locking keeps the session and every warm
  // cubit exactly where they are, so the only work an unlock does is comparing
  // a PIN in memory.

  bool get isLocked => status == AuthStatus.locked;

  /// Locks the till without ending the session. Falls back to a real sign-out
  /// if there's somehow no session to come back to.
  Future<void> lock() async {
    if (state.user == null) return logout();
    emit(state.copyWith(status: AuthStatus.locked, clearError: true));
    await _storage.setAuthLocked(true);
  }

  /// Unlocks with [pin]. The fast path resumes the locked session in place — no
  /// API call, no re-bootstrap, no catalog refetch — but **only** for the PIN of
  /// the cashier the session belongs to.
  ///
  /// Any other PIN is a handover, and falls through to a normal login, which
  /// signs that person in as themselves: their name on the sales they ring, their
  /// permissions, their branch. Offline, [login] resolves them from this device's
  /// own roster, so a handover works through an outage too — that is the whole
  /// reason the roster keeps every user's PIN rather than just the last one's.
  Future<bool> unlock(String pin) async {
    if (await _isCurrentUsersPin(pin)) {
      emit(state.copyWith(status: AuthStatus.signedIn, clearError: true));
      await _storage.setAuthLocked(false);
      return true;
    }
    return login(pin);
  }

  /// Whether [pin] belongs to the user this session is for.
  ///
  /// Answered from the device roster, the only store that knows *whose* each PIN
  /// is. A shared till holds several, so matching a PIN without matching its
  /// owner is how a colleague's PIN would resume the locked cashier's session —
  /// their dashboard, their permissions, their name on the next sale.
  ///
  /// The saved biometric blob is the fallback for a session whose roster entry is
  /// gone, and only when it names this same user; an unstamped one (written by a
  /// build before the stamp existed) is not trusted, which costs a round-trip
  /// through [login] and nothing else.
  Future<bool> _isCurrentUsersPin(String pin) async {
    final current = user;
    if (current == null || pin.isEmpty) return false;
    try {
      final account = await _accounts.byId(current.id);
      if (account != null) return account.matchesPin(pin);

      final saved = await _storage.readBiometric();
      if (saved == null) return false;
      final cred = Map<String, dynamic>.from(jsonDecode(saved));
      return cred['mode'] == 'pin' &&
          cred['user_id']?.toString() == current.id &&
          cred['pin'].toString() == pin;
    } catch (_) {
      return false;
    }
  }

  /// Keeps this device in step with a PIN the user just changed from the app.
  ///
  /// Both stores replay a PIN, and a stale one fails in opposite directions: the
  /// roster would keep opening offline sessions on a PIN the server has already
  /// stopped accepting, while the biometric blob would replay it into a login the
  /// server now refuses. Best-effort — the change itself has already succeeded.
  Future<void> applyChangedPin(String pin) async {
    final current = user;
    if (current == null || pin.isEmpty) return;
    try {
      await _accounts.setPin(current.id, pin);
      final saved = await _storage.readBiometric();
      if (saved == null) return;
      final cred = Map<String, dynamic>.from(jsonDecode(saved));
      // Only rewrite a PIN blob of this user's: someone who signs in with a
      // username and password keeps doing so, PIN change or not.
      if (cred['mode'] == 'pin' && cred['user_id']?.toString() == current.id) {
        await _storage.writeBiometric(jsonEncode({...cred, 'pin': pin}));
      }
    } catch (_) {
      // Costs an offline sign-in on the new PIN, never the change itself.
    }
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

  /// Biometric unlock. The session is still alive, so a successful local prompt
  /// is the whole check — nothing to re-authenticate against the server. Falls
  /// back to a full biometric *login* when there's no live session (e.g. after
  /// a real sign-out).
  Future<String?> unlockWithBiometric() async {
    if (!isLocked || user == null) return loginWithBiometric();
    bool ok;
    try {
      ok = await _localAuth.authenticate(
        localizedReason: 'Authenticate to unlock Invo',
        options: const AuthenticationOptions(
            biometricOnly: false, stickyAuth: true),
      );
    } catch (_) {
      return 'Biometric authentication is unavailable on this device.';
    }
    if (!ok) return null; // cancelled — no error toast
    emit(state.copyWith(status: AuthStatus.signedIn, clearError: true));
    await _storage.setAuthLocked(false);
    return null;
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

  /// Replaces the cached user with a freshly-returned one (e.g. after a profile
  /// or avatar update) so every screen watching [AuthCubit] reflects it live,
  /// and re-persists it for the next launch.
  Future<void> applyUser(ApiUser updated) async {
    emit(state.copyWith(user: updated));
    await _storage.setUserJson(jsonEncode(updated.toJson()));
  }

  Future<void> syncDaySession({
    required String status,
    String? openedAt,
    String? date,
    String? lastClosedAt,
  }) async {
    final u = state.user;
    if (u == null) return;
    final next = u.copyWith(
      daySessionStatus: status,
      daySessionDate: date,
      daySessionOpenedAt:
          status == 'open' ? (openedAt ?? u.daySessionOpenedAt) : '',
      lastClosedSessionAt: lastClosedAt,
    );
    emit(state.copyWith(user: next));
    await _storage.setUserJson(jsonEncode(next.toJson()));
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

  /// Ends the session.
  ///
  /// The device roster is deliberately **kept**: signing out is how a shared till
  /// is handed over, and forgetting who uses it would mean the next cashier could
  /// not get in at all while the network is down.
  ///
  /// So is everything the till sells from — the catalog snapshot, the staff,
  /// customer and payment-method lookups, the cached photos. None of it belongs
  /// to the person who was signed in: it is this shop's, at this branch, and the
  /// next cashier needs exactly the same rows. Dropping it here made a handover
  /// with no network hand over an empty till — no products, no staff, no
  /// categories — which is the one moment offline selling exists for. What that
  /// wipe was actually protecting against is the device being pointed at a
  /// different business, and that is handled where it happens, in
  /// [updateConnection].
  ///
  /// The offline outbox is left alone for its own reason: an unsynced sale is
  /// money that was taken, and it has to outlive the session that rang it up —
  /// including a forced sign-out on a 401.
  Future<void> _clear() async {
    await _storage.clearToken();
    await _storage.clearUser();
    await _storage.setAuthLocked(false);
    emit(state.copyWith(status: AuthStatus.signedOut, clearUser: true));
  }
}
