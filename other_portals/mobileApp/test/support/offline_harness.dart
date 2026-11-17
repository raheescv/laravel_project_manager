import 'package:flutter_test/flutter_test.dart';
import 'package:invo/shared/domain/constants/app_config.dart';
import 'package:invo/shared/domain/constants/global_variables.dart';
import 'package:invo/shared/domain/repository/lookup_repository.dart';
import 'package:invo/shared/logic/branch_cubit/branch_cubit.dart';
import 'package:invo/shared/utils/local_storage/local_storage_service.dart';
import 'package:invo/shared/utils/local_storage/offline_db.dart';
import 'package:invo/shared/utils/router/http_utils/http_service.dart';
import 'package:flutter_secure_storage/flutter_secure_storage.dart';
import 'package:shared_preferences/shared_preferences.dart';
import 'package:sqflite_common_ffi/sqflite_ffi.dart';

import 'fake_lookup_repository.dart';

/// Wires the offline stack up in-process: the sqflite database runs on the FFI
/// factory against an in-memory file, and prefs are mocked, so the outbox and
/// the catalog snapshot can be exercised without a device.
///
/// Call from `setUp`; it leaves a clean database and an empty locator behind on
/// every run so tests cannot leak state into each other.
Future<void> setUpOfflineHarness() async {
  TestWidgetsFlutterBinding.ensureInitialized();
  sqfliteFfiInit();
  databaseFactory = databaseFactoryFfi;

  // A fresh in-memory database per test. `resetForTests` drops the memoised
  // handle, and an in-memory path is emptied by that close.
  await OfflineDb.resetForTests();
  OfflineDb.debugPathOverride = inMemoryDatabasePath;

  SharedPreferences.setMockInitialValues({});
  // The token, the biometric credential and the device-account roster all live in
  // `flutter_secure_storage`, whose platform channel has no implementation in a
  // unit test — without this every read/write throws MissingPluginException.
  FlutterSecureStorage.setMockInitialValues({});
  await serviceLocator.reset();
  serviceLocator.registerSingleton<LocalStorageService>(await LocalStorageService.create());
}

/// Adds the app-wide pieces [OfflineSyncCubit] resolves in its constructor: a
/// branch (whose change stream it subscribes to) over a fake lookup repository,
/// and the HttpService that BranchCubit stamps the active branch onto.
///
/// Registered for real rather than guarded away in production code — the sync
/// engine genuinely cannot do its job without knowing the branch.
/// Returns the branch id that ended up active, which is whichever branch the
/// fake repository offered — NOT necessarily the one asked for. Tests must seed
/// their snapshot against this, or they will write to one branch and read from
/// another and see a confusing empty result.
Future<int> registerBranchContext({LookupRepository? lookup}) async {
  final storage = serviceLocator<LocalStorageService>();
  serviceLocator
    ..registerSingleton<HttpService>(
      HttpService(storage: storage, config: AppConfig(baseUrl: 'http://localhost', tenant: 't')),
    )
    ..registerLazySingleton<LookupRepository>(() => lookup ?? FakeLookupRepository())
    ..registerLazySingleton<BranchCubit>(() => BranchCubit(userBranchId: 1));
  // Force construction now so its initial branch load settles before a test
  // starts asserting on what the sync engine did.
  final branch = serviceLocator<BranchCubit>();
  await Future<void>.delayed(Duration.zero);
  return branch.selectedId ?? 1;
}

Future<void> tearDownOfflineHarness() async {
  await OfflineDb.resetForTests();
  await serviceLocator.reset();
}
