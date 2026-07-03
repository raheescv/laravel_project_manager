import 'package:invo/features/auth/domain/repository/auth_repository.dart';
import 'package:invo/features/auth/domain/services/auth_service.dart';
import 'package:invo/features/auth/logic/auth_cubit/auth_cubit.dart';
import 'package:invo/features/technician/domain/repository/technician_repository.dart';
import 'package:invo/features/technician/domain/services/technician_service.dart';
import 'package:invo/shared/domain/constants/app_config.dart';
import 'package:invo/shared/domain/constants/global_variables.dart';
import 'package:invo/shared/logic/theme_cubit/theme_cubit.dart';
import 'package:invo/shared/utils/local_storage/local_storage_service.dart';
import 'package:invo/shared/utils/router/http_utils/http_service.dart';

/// Registers every app-wide dependency for the Technician app. Called once at
/// boot before `runApp`. Mirrors the POS app's wiring, trimmed to the
/// technician surface (auth + theme + the technician data layer).
Future<void> setUpServiceLocator() async {
  final storage = await LocalStorageService.create();
  final config = AppConfig.resolve(
    savedBaseUrl: storage.baseUrl,
    savedTenant: storage.tenant,
  );
  final http = HttpService(storage: storage, config: config);

  serviceLocator
    ..registerSingleton<LocalStorageService>(storage)
    ..registerSingleton<HttpService>(http)
    // ---- Repositories (abstract → concrete) ----
    ..registerLazySingleton<AuthRepository>(AuthService.new)
    ..registerLazySingleton<TechnicianRepository>(TechnicianService.new)
    // ---- App-wide cubits ----
    ..registerLazySingleton<AuthCubit>(AuthCubit.new)
    ..registerLazySingleton<ThemeCubit>(ThemeCubit.new);
}
