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
  final http = HttpService(config: config);

  // Drives the offline banner. Wired to the one HttpService so every request,
  // from every feature, reports reachability without a call site remembering to.
  final connectivity = ConnectivityCubit();
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
