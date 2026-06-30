import 'package:flutter/material.dart';
import 'package:flutter_bloc/flutter_bloc.dart';
import 'package:flutter_screenutil/flutter_screenutil.dart';
import 'package:go_router/go_router.dart';

import 'package:invo/features/admin/domain/repository/admin_repository.dart';
import 'package:invo/features/admin/logic/admin_cubit/admin_cubit.dart';
import 'package:invo/features/admin/logic/day_session_cubit/day_session_cubit.dart';
import 'package:invo/features/auth/domain/repository/auth_repository.dart';
import 'package:invo/features/auth/logic/auth_cubit/auth_cubit.dart';
import 'package:invo/features/sale/domain/repository/sale_repository.dart';
import 'package:invo/features/sale/logic/cart_cubit/cart_cubit.dart';
import 'package:invo/features/sale/logic/catalog_cubit/catalog_cubit.dart';
import 'package:invo/features/sale/logic/stylist_cubit/stylist_cubit.dart';
import 'package:invo/features/sale_return/domain/repository/sale_return_repository.dart';
import 'package:invo/features/sale_return/logic/return_draft_cubit/return_draft_cubit.dart';
import 'package:invo/features/settings/logic/print_settings_cubit/print_settings_cubit.dart';
import 'package:invo/shared/domain/constants/app_config.dart';
import 'package:invo/shared/domain/constants/global_variables.dart';
import 'package:invo/shared/domain/models/index.dart';
import 'package:invo/shared/domain/repository/lookup_repository.dart';
import 'package:invo/shared/logic/branch_cubit/branch_cubit.dart';
import 'package:invo/shared/logic/currency_cubit/currency_cubit.dart';
import 'package:invo/shared/logic/theme_cubit/theme_cubit.dart';
import 'package:invo/shared/utils/components/theme/index.dart';
import 'package:invo/shared/utils/local_storage/local_storage_service.dart';
import 'package:invo/shared/utils/router/http_utils/http_service.dart';
import 'package:shared_preferences/shared_preferences.dart';

import 'fake_lookup_repository.dart';
import 'fake_repositories.dart';
import 'fake_sale_return_repository.dart';

/// Wires the service locator with concrete singletons + fake repositories and
/// exposes the app-wide cubits, mirroring `app.dart`'s provider list so screens
/// can be pumped in isolation.
class TestHarness {
  late final LocalStorageService storage;
  late final HttpService http;
  late final FakeLookupRepository lookup;
  late final FakeSaleRepository sale;
  late final FakeAuthRepository auth;
  late final FakeAdminRepository admin;
  late final FakeSaleReturnRepository saleReturn;

  AuthCubit authCubit = AuthCubit();
  late CartCubit cart;

  /// Register everything and seed a signed-in admin user.
  Future<void> init({bool admin = true}) async {
    SharedPreferences.setMockInitialValues({});
    await serviceLocator.reset();

    storage = await LocalStorageService.create();
    http = HttpService(
      storage: storage,
      config: AppConfig(baseUrl: 'http://test.local', tenant: ''),
    );
    lookup = DemoLookupRepository();
    sale = FakeSaleRepository();
    auth = FakeAuthRepository();
    this.admin = FakeAdminRepository();
    saleReturn = FakeSaleReturnRepository();

    serviceLocator
      ..registerSingleton<LocalStorageService>(storage)
      ..registerSingleton<HttpService>(http)
      ..registerLazySingleton<LookupRepository>(() => lookup)
      ..registerLazySingleton<SaleRepository>(() => sale)
      ..registerLazySingleton<AuthRepository>(() => auth)
      ..registerLazySingleton<AdminRepository>(() => this.admin)
      ..registerLazySingleton<SaleReturnRepository>(() => saleReturn);

    authCubit = AuthCubit();
    authCubit.user = ApiUser(
      id: '14',
      name: 'Maya Chen',
      code: 'EMP-014',
      email: 'maya@astra.co',
      mobile: '+1 415 555 0142',
      isAdmin: admin,
      designation: 'Senior Stylist',
      branchId: '3',
      daySessionStatus: 'open',
      daySessionDate: '2026-06-14',
    );
    authCubit.status = AuthStatus.signedIn;
    // DaySessionCubit resolves AuthCubit from the locator.
    serviceLocator.registerSingleton<AuthCubit>(authCubit);

    cart = CartCubit();
  }

  /// Convenience: the four demo catalog products (Signature Cut, etc).
  Future<List<Product>> demoProducts() async => (await lookup.products()).items;

  Future<void> dispose() async {
    await serviceLocator.reset();
  }

  /// The full provider stack (mirrors app.dart). [extraRoutes] lets a screen's
  /// navigation targets resolve when pumped behind a router.
  List<BlocProvider> providers() => [
        BlocProvider<AuthCubit>.value(value: authCubit),
        BlocProvider<ThemeCubit>(create: (_) => ThemeCubit()),
        BlocProvider<CurrencyCubit>(create: (_) => CurrencyCubit()),
        BlocProvider<BranchCubit>(create: (_) => BranchCubit(userBranchId: 3)),
        BlocProvider<PrintSettingsCubit>(create: (_) => PrintSettingsCubit()),
        BlocProvider<CartCubit>.value(value: cart),
        BlocProvider<ReturnDraftCubit>(create: (_) => ReturnDraftCubit()),
        BlocProvider<CatalogCubit>(create: (_) => CatalogCubit()),
        BlocProvider<StylistCubit>(create: (_) => StylistCubit()),
        BlocProvider<AdminCubit>(create: (_) => AdminCubit()),
        BlocProvider<DaySessionCubit>(create: (_) => DaySessionCubit()),
      ];

  /// Wrap [child] in the responsive frame + all cubits + a router whose home is
  /// the screen under test, plus catch-all stub routes for any navigation.
  Widget wrap(Widget child) {
    final router = GoRouter(
      routes: [
        GoRoute(path: '/', builder: (_, __) => child),
        GoRoute(path: '/invoice', builder: (_, __) => const Scaffold()),
        GoRoute(path: '/new-sale', builder: (_, __) => const Scaffold()),
        GoRoute(path: '/cart', builder: (_, __) => const Scaffold()),
        GoRoute(path: '/review', builder: (_, __) => const Scaffold()),
        GoRoute(path: '/login', builder: (_, __) => const Scaffold()),
      ],
    );
    return ScreenUtilInit(
      designSize: const Size(393, 865),
      builder: (_, __) => MultiBlocProvider(
        providers: providers(),
        child: MaterialApp.router(
          theme: buildAstraTheme(AstraPresets.emeraldGold),
          routerConfig: router,
        ),
      ),
    );
  }
}
