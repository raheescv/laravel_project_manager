import 'dart:async';

import 'package:dio/dio.dart';
import 'package:flutter/material.dart';
import 'package:flutter_bloc/flutter_bloc.dart';
import 'package:flutter_test/flutter_test.dart';
import 'package:go_router/go_router.dart';
import 'package:google_fonts/google_fonts.dart';
import 'package:invo/features/auth/domain/repository/auth_repository.dart';
import 'package:invo/features/auth/logic/auth_cubit/auth_cubit.dart';
import 'package:invo/features/auth/screens/v3/branch_select_screen.dart';
import 'package:invo/shared/domain/constants/app_config.dart';
import 'package:invo/shared/domain/constants/global_variables.dart';
import 'package:invo/shared/domain/models/index.dart';
import 'package:invo/shared/domain/repository/lookup_repository.dart';
import 'package:invo/shared/logic/branch_cubit/branch_cubit.dart';
import 'package:invo/shared/utils/components/theme/index.dart';
import 'package:invo/shared/utils/local_storage/local_storage_service.dart';
import 'package:invo/shared/utils/router/http_utils/common_exception.dart';
import 'package:invo/shared/utils/router/http_utils/http_service.dart';
import 'package:invo/shared/utils/router/routes.dart';

import 'support/fake_lookup_repository.dart';
import 'support/offline_harness.dart';
import 'support/test_harness.dart';

/// A user assigned to more than one branch says which one they are working as
/// on every sign-in — the branch is the stock they sell from and where their
/// sales are booked. An unlock asks again too, unless the till turned that off.
void main() {
  const main = Branch(id: 1, name: 'Main Store', location: 'Pulparambu', code: 'M');
  const market = Branch(id: 2, name: 'Market City', location: 'Perinthalmanna', code: 'MC');
  const sobha = Branch(id: 3, name: 'Sobha City', location: 'Thrissur', code: 'SC');

  group('AuthCubit', () {
    late _StubAuthRepository repo;
    late AuthCubit auth;

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
    });

    tearDown(() async {
      await auth.close();
      await tearDownOfflineHarness();
    });

    LocalStorageService storage() => serviceLocator<LocalStorageService>();

    test('a single-branch user goes straight in', () async {
      repo.branches = const [main];

      expect(await auth.login('1234'), isTrue);

      expect(auth.state.isSignedIn, isTrue);
      expect(auth.state.branchPending, isFalse);
    });

    test('a multi-branch user is held for a branch until they pick one', () async {
      repo.branches = const [main, market];

      await auth.login('1234');

      expect(auth.state.isSignedIn, isTrue);
      expect(auth.state.branchPending, isTrue);
      expect(storage().authBranchPending, isTrue);

      await auth.confirmBranch();

      expect(auth.state.branchPending, isFalse);
      expect(storage().authBranchPending, isFalse);
    });

    test('quitting on the picker brings it back on the next launch', () async {
      repo.branches = const [main, market];
      await auth.login('1234');

      final relaunched = AuthCubit();
      addTearDown(relaunched.close);
      await relaunched.bootstrap();

      expect(relaunched.state.isSignedIn, isTrue);
      expect(relaunched.state.branchPending, isTrue);
    });

    test('the same cashier unlocking is asked again by default', () async {
      repo.branches = const [main, market];
      await auth.login('1234');
      await auth.confirmBranch();
      await auth.lock();

      expect(await auth.unlock('1234'), isTrue);

      expect(auth.state.isSignedIn, isTrue);
      expect(auth.state.branchPending, isTrue);
    });

    test('an unlock keeps the last branch when the till turned the question off', () async {
      repo.branches = const [main, market];
      await auth.login('1234');
      await auth.confirmBranch();
      await auth.lock();
      await storage().setPosAskBranchOnUnlock(false);

      expect(await auth.unlock('1234'), isTrue);

      expect(auth.state.branchPending, isFalse);
    });

    test('an offline sign-in still asks — the branches came with the cached user', () async {
      repo.branches = const [main, market];
      await auth.login('1234');
      await auth.confirmBranch();
      await auth.logout();

      repo.failWith = DioException.connectionError(requestOptions: RequestOptions(), reason: 'no route');
      expect(await auth.login('1234'), isTrue);

      expect(auth.user?.branches, const [main, market]);
      expect(auth.state.branchPending, isTrue);
    });

    test('signing out drops a pending choice', () async {
      repo.branches = const [main, market];
      await auth.login('1234');

      await auth.logout();

      expect(auth.state.branchPending, isFalse);
      expect(storage().authBranchPending, isFalse);
    });
  });

  group('BranchCubit', () {
    setUp(() async {
      await setUpOfflineHarness();
      await registerBranchContext();
    });

    tearDown(tearDownOfflineHarness);

    ApiUser user({required List<Branch> branches, String home = '2'}) =>
        ApiUser.fromJson({'id': '7', 'name': 'Sara', 'branch_id': home})
            .copyWithBranches(branches);

    test('offers only the signed-in user\'s own branches', () async {
      final branch = serviceLocator<BranchCubit>();

      await branch.applyUser(user(branches: const [market, sobha]));

      expect(branch.branches, const [market, sobha]);
    });

    test('lands on the home branch when the current one is not theirs', () async {
      final branch = serviceLocator<BranchCubit>();
      final changes = <int>[];
      final sub = branch.onBranchChanged.listen(changes.add);
      addTearDown(sub.cancel);
      // Neither is the branch the till booted on.
      const east = Branch(id: 40, name: 'East', location: 'East', code: 'E');
      const west = Branch(id: 41, name: 'West', location: 'West', code: 'W');

      await branch.applyUser(user(branches: const [east, west], home: '41'));
      await Future<void>.delayed(Duration.zero);

      expect(branch.selectedId, 41);
      expect(serviceLocator<HttpService>().activeBranchId, 41);
      expect(changes, [41]);
    });

    test('stays on the current branch when the user works there too', () async {
      final branch = serviceLocator<BranchCubit>();
      final current = branch.selectedId!;
      final here = Branch(id: current, name: 'Here', location: 'Here', code: 'H');

      await branch.applyUser(user(branches: [here, sobha], home: '3'));

      expect(branch.selectedId, current);
    });

    test('a branch list still loading at sign-in does not replace the user\'s own branches', () async {
      final slow = _SlowLookups();
      serviceLocator
        ..unregister<LookupRepository>()
        ..registerSingleton<LookupRepository>(slow);
      final branch = BranchCubit(userBranchId: 1);
      addTearDown(branch.close);

      await branch.applyUser(user(branches: const [market, sobha]));
      slow.release(); // the launch-time fetch of every branch finally answers
      await Future<void>.delayed(Duration.zero);

      expect(branch.branches, const [market, sobha]);
    });

    test('picking the branch already in use still replaces an earlier explicit pick', () async {
      final branch = serviceLocator<BranchCubit>();
      final storage = serviceLocator<LocalStorageService>();
      await storage.setBranchId(99); // a previous cashier's pick
      final current = branch.selected!;

      await branch.setBranch(current);

      expect(storage.branchId, current.id);
    });
  });

  test('a user\'s branches survive the cached-user round trip', () {
    final u = ApiUser.fromJson({
      'id': '7',
      'name': 'Sara',
      'branches': [
        {'id': 1, 'name': 'Main Store', 'code': 'M', 'location': 'Pulparambu'},
        {'id': 2, 'name': 'Market City', 'code': 'MC', 'location': 'Perinthalmanna'},
      ],
    });

    expect(u.hasBranchChoice, isTrue);
    expect(ApiUser.fromJson(u.toJson()).branches, const [main, market]);
  });

  group('BranchSelectScreen', () {
    late TestHarness harness;

    setUpAll(() => GoogleFonts.config.allowRuntimeFetching = false);

    setUp(() async {
      harness = TestHarness();
      await harness.init();
    });

    tearDown(() async => harness.dispose());

    testWidgets('tapping a branch sets it and lets the session through', (tester) async {
      final signedIn = ApiUser.fromJson({'id': '14', 'name': 'Maya Chen', 'branch_id': '1'})
          .copyWithBranches(const [main, market]);
      harness.authCubit.seedSession(signedIn, branchPending: true);
      await harness.branch.applyUser(signedIn);

      final router = GoRouter(
        initialLocation: Routes.selectBranch,
        refreshListenable: _AuthListenable(harness.authCubit),
        redirect: (_, state) => harness.authCubit.state.branchPending
            ? (state.matchedLocation == Routes.selectBranch ? null : Routes.selectBranch)
            : (state.matchedLocation == Routes.selectBranch ? Routes.sale : null),
        routes: [
          GoRoute(path: Routes.selectBranch, builder: (_, __) => const BranchSelectScreen()),
          GoRoute(path: Routes.sale, builder: (_, __) => const Scaffold(body: Text('POS'))),
        ],
      );
      await tester.pumpWidget(MultiBlocProvider(
        providers: harness.providers(),
        child: MaterialApp.router(theme: buildAstraTheme(AstraPresets.emeraldGold), routerConfig: router),
      ));
      await tester.pumpAndSettle();

      expect(find.text('Your branches'), findsOneWidget);
      expect(find.text('Main Store'), findsOneWidget);
      expect(find.text('Market City'), findsOneWidget);

      await tester.tap(find.text('Market City'));
      await tester.pumpAndSettle();

      expect(harness.branch.selectedId, 2);
      expect(harness.storage.branchId, 2);
      expect(harness.authCubit.state.branchPending, isFalse);
      expect(find.text('POS'), findsOneWidget);
    });

    // Phone, portrait tablet, landscape tablet — each is its own layout, and a
    // long list (search on) must scroll rather than overflow.
    for (final size in const [Size(390, 844), Size(834, 1194), Size(1194, 834)]) {
      for (final count in const [2, 6, 12]) {
        testWidgets('lays out $count branches at ${size.width.toInt()}×${size.height.toInt()} without overflow',
            (tester) async {
          tester.view
            ..physicalSize = size
            ..devicePixelRatio = 1;
          addTearDown(tester.view.reset);
          final branches = [
            for (var i = 1; i <= count; i++)
              Branch(
                  id: i,
                  name: i.isEven ? 'SULTHAN BATTERY $i' : 'Market City $i',
                  location: 'GROUND FLOOR, MALL OF TRAVANCORE, THIRUVANANTHAPURAM $i',
                  code: 'B$i'),
          ];
          final signedIn = ApiUser.fromJson({'id': '14', 'name': 'Lehen Rahman', 'branch_id': '1'})
              .copyWithBranches(branches);
          harness.authCubit.seedSession(signedIn, branchPending: true);
          await harness.branch.applyUser(signedIn);

          await tester.pumpWidget(MultiBlocProvider(
            providers: harness.providers(),
            child: MaterialApp(theme: buildAstraTheme(AstraPresets.emeraldGold), home: const BranchSelectScreen()),
          ));
          await tester.pumpAndSettle();

          expect(tester.takeException(), isNull);
          expect(find.text('Your branches'), findsOneWidget);
          expect(find.byType(TextField), count > 6 ? findsOneWidget : findsNothing);
        });
      }
    }
  });
}

extension on ApiUser {
  ApiUser copyWithBranches(List<Branch> branches) =>
      ApiUser.fromJson({...toJson(), 'branches': [for (final b in branches) b.toJson()]});
}

/// A branch list that only answers when told to — the launch-time fetch still
/// in flight when somebody signs in.
class _SlowLookups extends FakeLookupRepository {
  final _gate = Completer<void>();

  void release() => _gate.complete();

  @override
  Future<List<Branch>> branches() async {
    await _gate.future;
    return super.branches();
  }
}

class _AuthListenable extends ChangeNotifier {
  _AuthListenable(AuthCubit auth) {
    auth.stream.listen((_) => notifyListeners());
  }
}

class _StubAuthRepository implements AuthRepository {
  Object? failWith;
  List<Branch> branches = const [];

  ApiUser _user() => ApiUser.fromJson({
        'id': '7',
        'name': 'Sara',
        'branch_id': '1',
        'branches': [for (final b in branches) b.toJson()],
      });

  @override
  Future<({String token, ApiUser user})> login(String pin) async {
    if (failWith case final failure?) throw failure;
    if (pin != '1234') throw ApiException('Invalid PIN', statusCode: 401);
    return (token: 'token-7', user: _user());
  }

  @override
  Future<({String token, ApiUser user})> loginCredential(String username, String password) async {
    if (failWith case final failure?) throw failure;
    return (token: 'token-7', user: _user());
  }

  @override
  Future<void> logout() async {}

  @override
  Future<void> changePin(String currentPin, String newPin) async {}

  @override
  Future<void> changePassword(String current, String next) async {}
}
