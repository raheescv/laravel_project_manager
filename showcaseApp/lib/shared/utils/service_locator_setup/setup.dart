import 'package:flutter/foundation.dart';
import 'package:package_info_plus/package_info_plus.dart';

import '../../../shared/api/end_points.dart';
import '../../domain/constants/app_config.dart';
import '../../domain/constants/global_variables.dart';
import '../../domain/repository/catalog_repository.dart';
import '../../domain/services/catalog_service.dart';
import '../../logic/branch_cubit/branch_cubit.dart';
import '../../logic/connectivity_cubit/connectivity_cubit.dart';
import '../../logic/theme_cubit/theme_cubit.dart';
import '../local_storage/local_storage_service.dart';
import '../router/http_utils/http_service.dart';

/// Registers every app-wide dependency. Called once at boot, before `runApp`.
Future<void> setUpServiceLocator() async {
  final storage = await LocalStorageService.create();
  final config = AppConfig.resolve(
    savedBaseUrl: storage.baseUrl,
    savedTenant: storage.tenant,
  );
  final http = HttpService(config: config)..appHeaders = await _appHeaders();

  // Drives the offline banner. Wired to the one HttpService so every request,
  // from every feature, reports reachability without a call site remembering to.
  // Once offline it probes on its own — the branch list, because it is the
  // cheapest thing the public catalog answers — and the interceptor above
  // reports what the probe found the same way it reports any other request.
  final connectivity = ConnectivityCubit(probe: () => http.get(EndPoints.branches));
  http.onReachability = (reachable) => connectivity.reportOutcome(reachable: reachable);

  serviceLocator
    ..registerSingleton<LocalStorageService>(storage)
    ..registerSingleton<AppConfig>(config)
    ..registerSingleton<HttpService>(http)
    ..registerSingleton<ConnectivityCubit>(connectivity)
    ..registerSingleton<CatalogRepository>(CatalogService())
    ..registerSingleton<ThemeCubit>(ThemeCubit())
    // Constructed last: it reads the saved branch and pushes it onto
    // HttpService, so the first catalog request already carries branch_id.
    ..registerSingleton<BranchCubit>(BranchCubit());
}

/// The build identity sent with every API call. A platform that will not
/// report its package info still sends the app name, so calls stay attributable.
Future<Map<String, String>> _appHeaders() async {
  final platform = kIsWeb ? 'web' : defaultTargetPlatform.name;
  try {
    final info = await PackageInfo.fromPlatform();
    final build = info.buildNumber;
    final version = build.isEmpty || build == info.version ? info.version : '${info.version}+$build';
    return {'X-App-Name': 'showcase', 'X-App-Version': version, 'X-App-Platform': platform};
  } catch (_) {
    return {'X-App-Name': 'showcase', 'X-App-Platform': platform};
  }
}
