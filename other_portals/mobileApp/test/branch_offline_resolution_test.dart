import 'package:dio/dio.dart';
import 'package:flutter_test/flutter_test.dart';
import 'package:invo/shared/domain/constants/app_config.dart';
import 'package:invo/shared/domain/constants/global_variables.dart';
import 'package:invo/shared/domain/models/index.dart';
import 'package:invo/shared/domain/repository/lookup_repository.dart';
import 'package:invo/shared/logic/branch_cubit/branch_cubit.dart';
import 'package:invo/shared/utils/local_storage/local_storage_service.dart';
import 'package:invo/shared/utils/router/http_utils/http_service.dart';

import 'support/fake_lookup_repository.dart';
import 'support/offline_harness.dart';

/// Everything this till holds for selling with no network — the catalog snapshot,
/// the staff and customer lookups, the queued sales — is keyed by branch. So a
/// launch that resolves a *different* branch than the one the snapshot was written
/// under reads as a till with nothing on it: no products, no staff, "nothing is
/// stored yet". Which is indistinguishable, to the person holding it, from the
/// offline data having been wiped.
///
/// That is exactly what used to happen to an account with no home branch: online
/// the app falls back to the first branch the server lists and provisions under
/// it, but only an explicit pick was ever persisted — so the next launch with no
/// network had nothing to resolve from.
void main() {
  late _OfflineLookups offline;

  setUp(() async {
    await setUpOfflineHarness();
    offline = _OfflineLookups();
    serviceLocator.registerSingleton<HttpService>(HttpService(
      storage: serviceLocator<LocalStorageService>(),
      config: AppConfig(baseUrl: 'http://localhost', tenant: 't'),
    ));
  });

  tearDown(tearDownOfflineHarness);

  /// Builds a BranchCubit over [repo] and lets its initial load settle.
  Future<BranchCubit> boot(LookupRepository repo, {int? userBranchId}) async {
    if (serviceLocator.isRegistered<LookupRepository>()) {
      serviceLocator.unregister<LookupRepository>();
    }
    serviceLocator.registerSingleton<LookupRepository>(repo);
    final branch = BranchCubit(userBranchId: userBranchId);
    addTearDown(branch.close);
    await Future<void>.delayed(Duration.zero);
    return branch;
  }

  test('a resolved branch is remembered, not just an explicitly picked one', () async {
    // No home branch, so the app falls back to the first the server lists (3).
    final online = await boot(FakeLookupRepository());

    expect(online.selectedId, 3);
    expect(serviceLocator<LocalStorageService>().lastBranchId, 3);
    // Still not an explicit pick — the user's home branch must keep applying.
    expect(serviceLocator<LocalStorageService>().branchId, isNull);
  });

  test('a launch with no network lands on the branch it provisioned under', () async {
    await boot(FakeLookupRepository());

    // Restart with the network down: the branch list cannot be fetched at all.
    final offlineBoot = await boot(offline);

    expect(offlineBoot.selectedId, 3, reason: 'the snapshot on this device is branch 3’s');
  });

  test('with nothing remembered, an offline launch has no branch at all', () async {
    // The state before the fix: nothing persisted, no home branch, no network.
    final cold = await boot(offline);

    expect(cold.selectedId, isNull);
  });

  test('an explicit pick still wins over the remembered one', () async {
    final branch = await boot(FakeLookupRepository());
    await branch.setBranch(Branch(id: 4, name: 'Uptown', location: 'Uptown', code: 'UP-04'));

    final next = await boot(offline);

    expect(next.selectedId, 4);
  });
}

/// A repository whose every call fails the way an unreachable server does.
class _OfflineLookups extends FakeLookupRepository {
  @override
  Future<List<Branch>> branches() async => throw DioException.connectionError(
      requestOptions: RequestOptions(), reason: 'no route to host');
}
