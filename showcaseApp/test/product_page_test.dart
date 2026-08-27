import 'package:flutter/material.dart';
import 'package:flutter_bloc/flutter_bloc.dart';
import 'package:flutter_test/flutter_test.dart';
import 'package:go_router/go_router.dart';
import 'package:shared_preferences/shared_preferences.dart';
import 'package:showcase/shared/logic/funnel_cubit/funnel_cubit.dart';
import 'package:showcase/l10n/app_localizations.dart';
import 'package:showcase/shared/domain/constants/app_config.dart';
import 'package:showcase/shared/domain/constants/global_variables.dart';
import 'package:showcase/shared/domain/models/index.dart';
import 'package:showcase/shared/domain/repository/catalog_repository.dart';
import 'package:showcase/shared/logic/branch_cubit/branch_cubit.dart';
import 'package:showcase/shared/logic/connectivity_cubit/connectivity_cubit.dart';
import 'package:showcase/shared/logic/locale_cubit/locale_cubit.dart';
import 'package:showcase/shared/logic/theme_cubit/theme_cubit.dart';
import 'package:showcase/shared/utils/components/theme/panel_scale.dart';
import 'package:showcase/shared/utils/components/theme/pearl_theme.dart';
import 'package:showcase/shared/utils/local_storage/local_storage_service.dart';
import 'package:showcase/shared/utils/router/app_router.dart';
import 'package:showcase/shared/utils/router/http_utils/http_service.dart';
import 'package:showcase/shared/widgets/pearl_widgets.dart';
import 'package:showcase/shared/widgets/photo.dart';

/// The product page is the end of the funnel and the deepest the app goes.
///
/// Two things it owes a customer standing in front of a kiosk: the photograph
/// they are deciding on, at a size worth deciding from, and a way out that does
/// not walk them back through the last customer's answers.
void main() {
  const panel = Size(1080, 1920);

  late GoRouter router;
  late FunnelCubit funnel;

  Future<void> pumpProduct(WidgetTester tester) async {
    SharedPreferences.setMockInitialValues({});
    final config = AppConfig.resolve(savedBaseUrl: null, savedTenant: null);
    serviceLocator
      ..registerSingleton<LocalStorageService>(await LocalStorageService.create())
      ..registerSingleton<AppConfig>(config)
      ..registerSingleton<HttpService>(HttpService(config: config))
      ..registerSingleton<CatalogRepository>(_Stub())
      ..registerSingleton<ConnectivityCubit>(ConnectivityCubit())
      ..registerSingleton<ThemeCubit>(ThemeCubit())
      ..registerSingleton<BranchCubit>(BranchCubit());
    await serviceLocator<BranchCubit>().load();
    addTearDown(() => serviceLocator.reset());

    tester.view
      ..physicalSize = panel
      ..devicePixelRatio = 1;
    addTearDown(tester.view.reset);

    router = createRouter();
    addTearDown(router.dispose);
    funnel = FunnelCubit();
    addTearDown(funnel.close);

    await tester.pumpWidget(MultiBlocProvider(
      providers: [
        BlocProvider<LocaleCubit>(create: (_) => LocaleCubit()),
        BlocProvider<FunnelCubit>.value(value: funnel),
        BlocProvider<BranchCubit>.value(value: serviceLocator<BranchCubit>()),
        BlocProvider<ThemeCubit>.value(value: serviceLocator<ThemeCubit>()),
        BlocProvider<ConnectivityCubit>.value(
            value: serviceLocator<ConnectivityCubit>()),
      ],
      child: MaterialApp.router(
        theme: buildPearlTheme(PearlPalette.light),
        supportedLocales: L.supportedLocales,
        localizationsDelegates: L.localizationsDelegates,
        routerConfig: router,
        builder: (context, child) => PanelScale(child: child ?? const SizedBox()),
      ),
    ));
    await tester.pumpAndSettle();

    // Walk in the way a customer does, so there is a stack behind the page and
    // answers on the funnel for Home to have to clear.
    await funnel.chooseSize('42');
    await funnel.setInStockOnly(false);
    router.push('/product/1');
    await tester.pumpAndSettle();
  }

  testWidgets('half the panel is the photograph', (tester) async {
    await pumpProduct(tester);

    // Measured on the glass: `PanelScale` lays the page out on a smaller canvas
    // and scales it up, so the only number that means anything to a customer is
    // the one after the transform.
    final stage = tester.getRect(find.byType(Stage).first);
    expect(stage.height, closeTo(panel.height * .5, 1));
  });

  testWidgets('Home clears the last customer and returns to step one',
      (tester) async {
    await pumpProduct(tester);

    expect(funnel.state.size, '42');
    expect(funnel.state.inStockOnly, isFalse);

    await tester.tap(find.widgetWithIcon(IconSquare, Icons.home_outlined));
    await tester.pumpAndSettle();

    // Step one, with nothing of the last visit behind it.
    expect(router.state.uri.path, '/');
    expect(router.canPop(), isFalse);

    // The size and the brand go; the stock filter goes back on.
    expect(funnel.state.size, isNull);
    expect(funnel.state.brand, isNull);
    expect(funnel.state.inStockOnly, isTrue);
    // And the shop goes back to every shop.
    expect(serviceLocator<BranchCubit>().state.showingAll, isTrue);
  });
}

/// One product with no photographs, so nothing here reaches the network.
class _Stub implements CatalogRepository {
  @override
  Future<List<Branch>> branches() async => const [
        Branch(id: 1, name: 'Doha', code: 'DOH', location: 'Doha', mobile: ''),
        Branch(id: 2, name: 'Lusail', code: 'LUS', location: 'Lusail', mobile: ''),
      ];

  @override
  Future<Product> product(int id) async => Product.fromJson({
        'id': id,
        'name': 'Fresh Foam X 1080 v13',
        'code': 'NB-1080',
        'brand': {'id': 1, 'name': 'New Balance'},
        'mrp': 1249.5,
        'size': '42',
        'total_stock': 3,
        'availability_status': 'in_stock',
      });

  @override
  Future<List<Product>> related(Product product,
          {int limit = 12, bool inStockOnly = true}) async =>
      const [];

  @override
  Future<List<SizeOption>> sizes({int? mainCategoryId, int? brandId, int? branchId}) async => [
        for (final label in ['41', '42', '43'])
          SizeOption(size: label, group: SizeGroup.adult, stockTotal: 4, inStock: true),
      ];

  @override
  Future<List<BrandOption>> brands({
    int? mainCategoryId,
    String? size,
    bool inStockOnly = true,
  }) async =>
      const [];

  @override
  Future<List<CategoryOption>> categories({
    String? size,
    int? branchId,
    bool inStockOnly = true,
  }) async =>
      const [];

  @override
  dynamic noSuchMethod(Invocation invocation) => super.noSuchMethod(invocation);
}
