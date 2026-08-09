import 'package:dio/dio.dart';
import 'package:flutter_test/flutter_test.dart';
import 'package:invo/features/auth/domain/repository/auth_repository.dart';
import 'package:invo/features/auth/domain/services/device_account_store.dart';
import 'package:invo/features/auth/logic/auth_cubit/auth_cubit.dart';
import 'package:invo/shared/domain/constants/app_config.dart';
import 'package:invo/shared/domain/constants/global_variables.dart';
import 'package:invo/shared/domain/models/index.dart';
import 'package:invo/shared/logic/connectivity_cubit/connectivity_cubit.dart';
import 'package:invo/shared/utils/local_storage/local_storage_service.dart';
import 'package:invo/shared/utils/router/http_utils/common_exception.dart';
import 'package:invo/shared/utils/router/http_utils/http_service.dart';

import 'support/offline_harness.dart';

/// A shared till hands over between cashiers all day. If an offline till cannot
/// authenticate the next cashier, it can take no further sales the moment anyone
/// locks or signs out — the queue would hold what was already rung up and nothing
/// more, which is the exact failure offline selling exists to prevent.
///
/// So a user who has signed in on this device before can sign in again with no
/// network. These tests pin the boundaries of that, which is where the risk is:
/// only a *previous* user of *this* device, only when the server was genuinely
/// unreachable, and never with more than their last online session had.
void main() {
  late _StubAuthRepository repo;
  late AuthCubit auth;
  late DeviceAccountStore accounts;

  setUp(() async {
    await setUpOfflineHarness();
    repo = _StubAuthRepository();
    serviceLocator
      ..registerSingleton<HttpService>(HttpService(
        storage: serviceLocator<LocalStorageService>(),
        config: AppConfig(baseUrl: 'http://localhost', tenant: 't'),
      ))
      ..registerSingleton<AuthRepository>(repo);
    auth = AuthCubit();
    accounts = DeviceAccountStore();
  });

  tearDown(() async {
    await auth.close();
    await tearDownOfflineHarness();
  });

  void goOffline() => repo.failWith =
      DioException.connectionError(requestOptions: RequestOptions(), reason: 'no route');

  group('remembering who uses this till', () {
    test('a successful sign-in adds the user to the device roster', () async {
      expect(await auth.login('1234'), isTrue);

      final roster = await accounts.all();
      expect(roster, hasLength(1));
      expect(roster.single.userId, '7');
      expect(roster.single.name, 'Sara');
    });

    test('signing in again replaces that user rather than adding a second entry',
        () async {
      await auth.login('1234');
      // Their PIN changed on the web and they signed in with the new one.
      repo.pinAccepted = '9999';
      await auth.login('9999');

      final roster = await accounts.all();
      expect(roster, hasLength(1));
      // And the old PIN must no longer open a session offline.
      expect(await accounts.byPin('1234'), isNull);
      expect(await accounts.byPin('9999'), isNotNull);
    });

    test('two cashiers on one till are both remembered', () async {
      await auth.login('1234');
      repo
        ..pinAccepted = '5678'
        ..userId = '8'
        ..userName = 'Omar';
      await auth.login('5678');

      final roster = await accounts.all();
      expect(roster, hasLength(2));
      // Newest first, so the list reads as "who uses this till".
      expect(roster.first.name, 'Omar');
    });
  });

  group('signing in with no network', () {
    test('a previous user of this device gets in', () async {
      await auth.login('1234');
      await auth.logout();
      goOffline();

      expect(await auth.login('1234'), isTrue);
      expect(auth.status, AuthStatus.signedIn);
      expect(auth.user?.name, 'Sara');
    });

    test('a cashier who has never signed in here does not', () async {
      await auth.login('1234');
      await auth.logout();
      goOffline();

      expect(await auth.login('0000'), isFalse);
      expect(auth.status, AuthStatus.signedOut);
      // And it says what is actually wrong, rather than "wrong PIN".
      expect(auth.error, contains('internet'));
    });

    test('the failure is a plain sentence, never a raw exception', () async {
      goOffline();

      await auth.login('1234');

      // A cashier cannot act on a DioException, and a stack trace in a snackbar
      // reads as a broken app rather than a network they can fix. The detail goes
      // to the console instead.
      expect(auth.error, 'No internet connection. Please connect and try again.');
      expect(auth.error, isNot(contains('DioException')));
      expect(auth.error, isNot(contains('[debug]')));
      expect(auth.error, isNot(contains('localhost')));
    });

    test('a live network that cannot reach the server says so instead', () async {
      final network = ConnectivityCubit();
      addTearDown(network.close);
      serviceLocator.registerSingleton<ConnectivityCubit>(network);
      // Shop wifi is up; the uplink is not. Different problem, different fix.
      network.reportOutcome(reachable: true);
      network.reportInterfacesForTest(hasInterface: true);
      goOffline();

      await auth.login('1234');

      expect(auth.error, 'Can’t reach the server. Please check your connection and try again.');
    });

    test('nobody gets in on a device that has never signed anyone in', () async {
      goOffline();

      expect(await auth.login('1234'), isFalse);
      // No session at all — the status is still `unknown` here because nothing has
      // ever bootstrapped one, which is the point: the fallback created nothing.
      expect(auth.user, isNull);
      expect(auth.status, isNot(AuthStatus.signedIn));
    });

    test('the restored session carries exactly the cached permissions', () async {
      await auth.login('1234');
      final onlinePermissions = auth.user?.permissions;
      await auth.logout();
      goOffline();

      await auth.login('1234');

      // Not "everything" and not "nothing" — what the last online sign-in granted.
      // The server re-checks every request regardless.
      expect(auth.user?.permissions, onlinePermissions);
      expect(auth.hasPermission('sale.create'), isTrue);
      expect(auth.hasPermission('sale.delete'), isFalse);
    });

    test('the stored token comes back, so the outbox can drain under them', () async {
      await auth.login('1234');
      await auth.logout();
      goOffline();

      await auth.login('1234');

      expect(await serviceLocator<LocalStorageService>().readToken(), 'token-7');
    });

    test('credentials work offline too, for a user who signed in that way', () async {
      expect(await auth.loginWithCredential('sara@shop.test', 'secret'), isTrue);
      await auth.logout();
      goOffline();

      expect(await auth.loginWithCredential('sara@shop.test', 'secret'), isTrue);
      expect(auth.status, AuthStatus.signedIn);
    });

    test('a wrong password offline is refused, not waved through', () async {
      await auth.loginWithCredential('sara@shop.test', 'secret');
      await auth.logout();
      goOffline();

      expect(await auth.loginWithCredential('sara@shop.test', 'guess'), isFalse);
      expect(auth.status, AuthStatus.signedOut);
    });
  });

  group('what the fallback must never do', () {
    test('a server that REFUSED the PIN is never second-guessed locally', () async {
      await auth.login('1234');
      await auth.logout();
      // The server is reachable and says no — the account was deactivated, say.
      repo.failWith = ApiException('These credentials do not match our records.', statusCode: 401);

      expect(await auth.login('1234'), isFalse);
      expect(auth.status, AuthStatus.signedOut);
      // The roster holds a matching PIN, and it must not be consulted at all.
      expect(auth.error, 'These credentials do not match our records.');
    });

    test('signing out keeps the roster, because that is the handover', () async {
      await auth.login('1234');

      await auth.logout();

      expect(await accounts.all(), hasLength(1));
    });

    test('repointing the device at another business forgets everyone', () async {
      await auth.login('1234');

      await auth.updateConnection(baseUrl: 'http://other-shop.test', tenant: 'other');

      // A PIN remembered for one tenant must never open a session against another.
      expect(await accounts.all(), isEmpty);
    });

    test('re-saving the same connection does not forget anyone', () async {
      await auth.login('1234');

      await auth.updateConnection(baseUrl: 'http://localhost', tenant: 't');

      expect(await accounts.all(), hasLength(1));
    });

    test('the roster is capped so a busy till does not grow it forever', () async {
      for (var i = 0; i < DeviceAccountStore.maxAccounts + 4; i++) {
        repo
          ..pinAccepted = 'pin$i'
          ..userId = 'u$i'
          ..userName = 'Cashier $i';
        await auth.login('pin$i');
      }

      expect(await accounts.all(), hasLength(DeviceAccountStore.maxAccounts));
    });
  });
}

/// An [AuthRepository] that accepts one PIN / credential pair and can be told to
/// fail in a specific way.
class _StubAuthRepository implements AuthRepository {
  Object? failWith;
  String pinAccepted = '1234';
  String userId = '7';
  String userName = 'Sara';

  ApiUser _user() => ApiUser.fromJson({
        'id': userId,
        'name': userName,
        'email': 'sara@shop.test',
        'branch_id': '1',
        'permissions': const ['sale.create', 'sale.edit'],
      });

  @override
  Future<({String token, ApiUser user})> login(String pin) async {
    if (failWith case final failure?) throw failure;
    if (pin != pinAccepted) throw ApiException('Invalid PIN', statusCode: 401);
    return (token: 'token-$userId', user: _user());
  }

  @override
  Future<({String token, ApiUser user})> loginCredential(String username, String password) async {
    if (failWith case final failure?) throw failure;
    if (password != 'secret') throw ApiException('Invalid credentials', statusCode: 401);
    return (token: 'token-$userId', user: _user());
  }

  @override
  Future<void> logout() async {}

  @override
  Future<void> changePin(String currentPin, String newPin) async {}

  @override
  Future<void> changePassword(String current, String next) async {}
}
