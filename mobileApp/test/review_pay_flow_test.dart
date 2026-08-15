import 'package:flutter/material.dart';
import 'package:flutter_bloc/flutter_bloc.dart';
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
import 'package:invo/features/settings/logic/pos_settings_cubit/pos_settings_cubit.dart';
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

  /// Charges a ticket on Review & Pay and returns the seeded session, with the
  /// two till-behaviour toggles set explicitly: a shared till locks after every
  /// sale and prints without being asked, both on by default, and what happens
  /// after a charge depends entirely on them.
  Future<AuthCubit> chargeATicket(
    WidgetTester tester, {
    required bool lockAfterSale,
    required bool autoPrint,
  }) async {
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

    final authCubit = AuthCubit()
      ..seedSession(ApiUser(
        id: '1',
        name: 'Test',
        code: 'T-1',
        email: 't@astra.co',
        mobile: '',
        isAdmin: true,
        designation: '',
        role: 'admin',
        branchId: '3',
        daySessionStatus: 'open',
        daySessionDate: '2026-06-14',
      ));
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

    final pos = PosSettingsCubit();
    await pos.setLockAfterSale(lockAfterSale);
    final print = PrintSettingsCubit();
    await print.setAutoPrint(autoPrint);

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

    await tester.pumpWidget(MultiBlocProvider(
        providers: [
          BlocProvider<AuthCubit>.value(value: authCubit),
          BlocProvider<CartCubit>.value(value: cart),
          BlocProvider<PrintSettingsCubit>.value(value: print),
          BlocProvider<PosSettingsCubit>.value(value: pos),
          BlocProvider<ReturnDraftCubit>(create: (_) => ReturnDraftCubit()),
        ],
        child: MaterialApp.router(
          theme: buildAstraTheme(AstraPresets.emeraldGold),
          routerConfig: router,
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
    return authCubit;
  }

  testWidgets('Charge on Review & Pay navigates to the Invoice screen', (tester) async {
    await chargeATicket(tester, lockAfterSale: false, autoPrint: false);

    // The invoice number is shown in the hero line, e.g.
    // "Invoice  INV-9001  ·  Jun 14, 2026".
    expect(find.textContaining('INV-9001'), findsWidgets, reason: 'should have navigated to the invoice');
  });

  testWidgets('a shared till locks itself after the charge instead', (tester) async {
    final auth = await chargeATicket(tester, lockAfterSale: true, autoPrint: false);

    // The session survives — coming back is a local PIN check, not a fresh
    // login — but the till is closed to the next person until they identify
    // themselves, so the invoice screen is deliberately not reached.
    expect(auth.status, AuthStatus.locked);
    expect(auth.user, isNotNull);
    expect(find.byType(InvoiceScreen), findsNothing);
    // The cashier is still told what happened, in the snackbar the lock leaves
    // behind — otherwise the till would just lock with no word of the sale.
    expect(find.textContaining('INV-9001'), findsWidgets);
  });
}
