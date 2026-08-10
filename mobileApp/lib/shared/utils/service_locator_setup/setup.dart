import 'package:invo/features/admin/domain/repository/admin_repository.dart';
import 'package:invo/features/admin/domain/services/admin_service.dart';
import 'package:invo/features/auth/domain/repository/auth_repository.dart';
import 'package:invo/features/auth/domain/services/auth_service.dart';
import 'package:invo/features/auth/logic/auth_cubit/auth_cubit.dart';
import 'package:invo/features/profile/domain/repository/profile_repository.dart';
import 'package:invo/features/profile/domain/services/profile_service.dart';
import 'package:invo/features/sale/domain/repository/outbox_repository.dart';
import 'package:invo/features/sale/domain/repository/sale_repository.dart';
import 'package:invo/features/sale/domain/services/offline_sale_service.dart';
import 'package:invo/features/sale/domain/services/outbox_service.dart';
import 'package:invo/features/sale/domain/services/sale_service.dart';
import 'package:invo/features/sale/logic/offline_sync_cubit/offline_sync_cubit.dart';
import 'package:invo/features/sale_return/domain/repository/sale_return_repository.dart';
import 'package:invo/features/sale_return/domain/services/sale_return_service.dart';
import 'package:invo/features/stock_check/domain/repository/stock_check_repository.dart';
import 'package:invo/features/stock_check/domain/services/stock_check_service.dart';
import 'package:invo/features/settings/logic/pos_settings_cubit/pos_settings_cubit.dart';
import 'package:invo/features/settings/logic/print_settings_cubit/print_settings_cubit.dart';
import 'package:invo/shared/domain/constants/app_config.dart';
import 'package:invo/shared/domain/constants/global_variables.dart';
import 'package:invo/shared/domain/repository/catalog_snapshot_repository.dart';
import 'package:invo/shared/domain/repository/lookup_repository.dart';
import 'package:invo/shared/domain/services/catalog_snapshot_service.dart';
import 'package:invo/shared/domain/services/lookup_service.dart';
import 'package:invo/shared/domain/services/offline_first_lookup_service.dart';
import 'package:invo/shared/logic/branch_cubit/branch_cubit.dart';
import 'package:invo/shared/logic/connectivity_cubit/connectivity_cubit.dart';
import 'package:invo/shared/logic/currency_cubit/currency_cubit.dart';
import 'package:invo/shared/logic/haptics_cubit/haptics_cubit.dart';
import 'package:invo/shared/logic/theme_cubit/theme_cubit.dart';
import 'package:invo/shared/utils/local_storage/local_storage_service.dart';
import 'package:invo/shared/utils/router/http_utils/http_service.dart';

/// Registers every app-wide dependency. Called once at boot before `runApp`.
Future<void> setUpServiceLocator() async {
  // Boot-time singletons (need async/eager construction).
  final storage = await LocalStorageService.create();
  final config = AppConfig.resolve(
    savedBaseUrl: storage.baseUrl,
    savedTenant: storage.tenant,
  );
  final http = HttpService(storage: storage, config: config);

  // The plain online sale service, kept aside from the registration below: the
  // registered SaleRepository is the offline-first decorator wrapping this one,
  // and the sync engine needs the unwrapped version to drain the outbox without
  // re-queueing what it is draining.
  final onlineSales = SaleService();

  // Drives the app-wide offline banner. Registered eagerly and wired to the one
  // HttpService so every request, from every feature, reports reachability
  // without any call site having to remember to.
  final connectivity = ConnectivityCubit();
  http.onReachability = (reachable) => connectivity.reportOutcome(reachable: reachable);

  serviceLocator
    ..registerSingleton<LocalStorageService>(storage)
    ..registerSingleton<HttpService>(http)
    ..registerSingleton<ConnectivityCubit>(connectivity)
    // ---- Repositories (abstract → concrete) ----
    // Reference lists (payment methods, staff, customers) fall back to the
    // device snapshot when the server is unreachable, so every existing caller
    // keeps working offline without knowing about it.
    ..registerLazySingleton<LookupRepository>(() => OfflineFirstLookupService(LookupService()))
    ..registerLazySingleton<AuthRepository>(AuthService.new)
    ..registerLazySingleton<ProfileRepository>(ProfileService.new)
    ..registerLazySingleton<CatalogSnapshotRepository>(CatalogSnapshotService.new)
    ..registerLazySingleton<OutboxRepository>(OutboxService.new)
    // Sales go through the decorator so an unreachable server queues the ticket
    // instead of losing it. Everything else on the contract passes straight
    // through to `onlineSales`.
    ..registerLazySingleton<SaleRepository>(() => OfflineFirstSaleService(onlineSales))
    ..registerLazySingleton<SaleReturnRepository>(SaleReturnService.new)
    ..registerLazySingleton<StockCheckRepository>(StockCheckService.new)
    ..registerLazySingleton<AdminRepository>(AdminService.new)
    // ---- App-wide cubits (survive the whole session) ----
    ..registerLazySingleton<AuthCubit>(AuthCubit.new)
    ..registerLazySingleton<ThemeCubit>(ThemeCubit.new)
    ..registerLazySingleton<HapticsCubit>(HapticsCubit.new)
    ..registerLazySingleton<CurrencyCubit>(CurrencyCubit.new)
    ..registerLazySingleton<BranchCubit>(() => BranchCubit(
          userBranchId:
              int.tryParse(serviceLocator<AuthCubit>().user?.branchId ?? ''),
        ))
    ..registerLazySingleton<PrintSettingsCubit>(PrintSettingsCubit.new)
    ..registerLazySingleton<PosSettingsCubit>(PosSettingsCubit.new)
    ..registerLazySingleton<OfflineSyncCubit>(() => OfflineSyncCubit(onlineSales));
}
