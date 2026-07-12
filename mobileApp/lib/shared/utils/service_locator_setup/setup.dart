import 'package:invo/features/admin/domain/repository/admin_repository.dart';
import 'package:invo/features/admin/domain/services/admin_service.dart';
import 'package:invo/features/auth/domain/repository/auth_repository.dart';
import 'package:invo/features/auth/domain/services/auth_service.dart';
import 'package:invo/features/auth/logic/auth_cubit/auth_cubit.dart';
import 'package:invo/features/sale/domain/repository/sale_repository.dart';
import 'package:invo/features/sale/domain/services/sale_service.dart';
import 'package:invo/features/sale_return/domain/repository/sale_return_repository.dart';
import 'package:invo/features/sale_return/domain/services/sale_return_service.dart';
import 'package:invo/features/stock_check/domain/repository/stock_check_repository.dart';
import 'package:invo/features/stock_check/domain/services/stock_check_service.dart';
import 'package:invo/features/settings/logic/print_settings_cubit/print_settings_cubit.dart';
import 'package:invo/shared/domain/constants/app_config.dart';
import 'package:invo/shared/domain/constants/global_variables.dart';
import 'package:invo/shared/domain/repository/lookup_repository.dart';
import 'package:invo/shared/domain/services/lookup_service.dart';
import 'package:invo/shared/logic/branch_cubit/branch_cubit.dart';
import 'package:invo/shared/logic/currency_cubit/currency_cubit.dart';
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

  serviceLocator
    ..registerSingleton<LocalStorageService>(storage)
    ..registerSingleton<HttpService>(http)
    // ---- Repositories (abstract → concrete) ----
    ..registerLazySingleton<LookupRepository>(LookupService.new)
    ..registerLazySingleton<AuthRepository>(AuthService.new)
    ..registerLazySingleton<SaleRepository>(SaleService.new)
    ..registerLazySingleton<SaleReturnRepository>(SaleReturnService.new)
    ..registerLazySingleton<StockCheckRepository>(StockCheckService.new)
    ..registerLazySingleton<AdminRepository>(AdminService.new)
    // ---- App-wide cubits (survive the whole session) ----
    ..registerLazySingleton<AuthCubit>(AuthCubit.new)
    ..registerLazySingleton<ThemeCubit>(ThemeCubit.new)
    ..registerLazySingleton<CurrencyCubit>(CurrencyCubit.new)
    ..registerLazySingleton<BranchCubit>(() => BranchCubit(
          userBranchId:
              int.tryParse(serviceLocator<AuthCubit>().user?.branchId ?? ''),
        ))
    ..registerLazySingleton<PrintSettingsCubit>(PrintSettingsCubit.new);
}
