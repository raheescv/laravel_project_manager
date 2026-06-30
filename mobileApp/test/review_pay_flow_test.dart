import 'package:flutter/material.dart';
import 'package:flutter_bloc/flutter_bloc.dart';
import 'package:flutter_screenutil/flutter_screenutil.dart';
import 'package:flutter_test/flutter_test.dart';
import 'package:go_router/go_router.dart';
import 'package:google_fonts/google_fonts.dart';

import 'package:invo/features/auth/domain/repository/auth_repository.dart';
import 'package:invo/features/auth/logic/auth_cubit/auth_cubit.dart';
import 'package:invo/features/sale/domain/repository/sale_repository.dart';
import 'package:invo/features/sale/logic/cart_cubit/cart_cubit.dart';
import 'package:invo/features/sale/screens/v3/invoice_screen.dart';
import 'package:invo/features/sale/screens/v3/review_pay_screen.dart';
import 'package:invo/features/sale_return/logic/return_draft_cubit/return_draft_cubit.dart';
import 'package:invo/features/settings/logic/print_settings_cubit/print_settings_cubit.dart';
import 'package:invo/shared/domain/constants/app_config.dart';
import 'package:invo/shared/domain/constants/global_variables.dart';
import 'package:invo/shared/domain/models/index.dart';
import 'package:invo/shared/domain/repository/lookup_repository.dart';
import 'package:invo/shared/utils/components/theme/index.dart';
import 'package:invo/shared/utils/local_storage/local_storage_service.dart';
import 'package:invo/shared/utils/router/http_utils/http_service.dart';
import 'package:shared_preferences/shared_preferences.dart';

import 'support/fake_lookup_repository.dart';
import 'support/fake_repositories.dart';

void main() {
  setUpAll(() => GoogleFonts.config.allowRuntimeFetching = false);
  tearDown(() async => serviceLocator.reset());

  testWidgets('Charge on Review & Pay navigates to the Invoice screen', (tester) async {
    SharedPreferences.setMockInitialValues({});
    await serviceLocator.reset();

    final storage = await LocalStorageService.create();
    final http = HttpService(
      storage: storage,
      config: AppConfig(baseUrl: 'http://test.local', tenant: ''),
    );
    final sale = FakeSaleRepository();

    serviceLocator
      ..registerSingleton<LocalStorageService>(storage)
      ..registerSingleton<HttpService>(http)
      ..registerLazySingleton<LookupRepository>(() => FakeLookupRepository())
      ..registerLazySingleton<AuthRepository>(() => FakeAuthRepository())
      ..registerLazySingleton<SaleRepository>(() => sale);

    final authCubit = AuthCubit()..status = AuthStatus.signedIn;
    final cart = CartCubit()
      ..add(Product(
        id: 1,
        code: 'SC-01',
        name: 'Signature Cut',
        barcode: '',
        mrp: 45,
        tax: 0,
        type: 'service',
        categoryName: 'Hair',
        duration: '45',
        totalStock: 5,
        thumbnail: '',
      ));

    final router = GoRouter(
      initialLocation: '/review',
      routes: [
        GoRoute(path: '/review', builder: (_, __) => const ReviewPayScreen()),
        GoRoute(path: '/invoice', builder: (_, state) => InvoiceScreen(sale: state.extra as Sale)),
      ],
    );

    tester.view.physicalSize = const Size(430, 1100);
    tester.view.devicePixelRatio = 1.0;
    addTearDown(tester.view.resetPhysicalSize);
    addTearDown(tester.view.resetDevicePixelRatio);

    await tester.pumpWidget(ScreenUtilInit(
      designSize: const Size(393, 865),
      builder: (_, __) => MultiBlocProvider(
        providers: [
          BlocProvider<AuthCubit>.value(value: authCubit),
          BlocProvider<CartCubit>.value(value: cart),
          BlocProvider<PrintSettingsCubit>(create: (_) => PrintSettingsCubit()),
          BlocProvider<ReturnDraftCubit>(create: (_) => ReturnDraftCubit()),
        ],
        child: MaterialApp.router(
          theme: buildAstraTheme(AstraPresets.emeraldGold),
          routerConfig: router,
        ),
      ),
    ));
    await tester.pump();
    await tester.pump(const Duration(milliseconds: 400));
    expect(tester.takeException(), isNull, reason: 'Review & Pay must render');

    // Tap the Charge button.
    await tester.tap(find.textContaining('Charge'));
    await tester.pump();
    await tester.pump(const Duration(milliseconds: 600));

    expect(tester.takeException(), isNull, reason: 'charging must not throw');
    // The default Cash mode → paymentMethod "Cash", sendToWhatsapp flag present.
    expect(sale.lastPayload?['paymentMethod'], 'Cash');
    expect(sale.lastPayload?.containsKey('sendToWhatsapp'), true);
    // We must now be on the Invoice screen (the invoice number is shown in the
    // hero line, e.g. "Invoice  INV-9001  ·  Jun 14, 2026").
    expect(find.textContaining('INV-9001'), findsWidgets, reason: 'should have navigated to the invoice');
  });
}
