import 'package:invo/core/api_client.dart';
import 'package:invo/core/api_service.dart';
import 'package:invo/core/config.dart';
import 'package:invo/core/storage.dart';
import 'package:invo/features/sale/invoice_screen.dart';
import 'package:invo/features/sale/review_pay_screen.dart';
import 'package:invo/models/models.dart';
import 'package:invo/state/auth_controller.dart';
import 'package:invo/state/cart_controller.dart';
import 'package:invo/theme/palette.dart';
import 'package:invo/theme/theme.dart';
import 'package:flutter/material.dart';
import 'package:flutter_test/flutter_test.dart';
import 'package:go_router/go_router.dart';
import 'package:google_fonts/google_fonts.dart';
import 'package:provider/provider.dart';
import 'package:shared_preferences/shared_preferences.dart';

/// A fake that returns a finished sale for createSale (so the Charge → Invoice
/// navigation can be exercised) and a canned payment-method list.
class FlowApiService extends ApiService {
  FlowApiService(super.client);
  Map<String, dynamic>? lastPayload;

  @override
  Future<List<PaymentMethod>> paymentMethods() async =>
      [PaymentMethod(id: 1, name: 'Cash'), PaymentMethod(id: 2, name: 'Card')];

  @override
  Future<Sale> createSale(Map<String, dynamic> payload) async {
    lastPayload = payload;
    return Sale.fromJson({
      'id': '9001', 'invoice_no': 'INV-9001', 'date': '2026-06-14', 'status': 'completed', 'branch': 'Downtown',
      'customer': {'name': 'Walk-in', 'mobile': ''},
      'items': [
        {'name': 'Signature Cut', 'type': 'service', 'employee': 'Maya', 'quantity': 1, 'unit_price': 45, 'discount': 0, 'total': 45},
      ],
      'payments': [{'method': 'Cash', 'amount': 45}],
      'summary': {'gross_amount': 45, 'item_discount': 0, 'other_discount': 0, 'tax_amount': 0, 'paid': 45},
      'created_by': 'Maya',
    });
  }
}

void main() {
  setUpAll(() => GoogleFonts.config.allowRuntimeFetching = false);

  testWidgets('Charge on Review & Pay navigates to the Invoice screen', (tester) async {
    SharedPreferences.setMockInitialValues({});
    final storage = await Storage.create();
    final client = ApiClient(storage: storage, config: AppConfig(baseUrl: 'http://test.local', tenant: ''));
    final service = FlowApiService(client);
    final auth = AuthController(client: client, service: service, storage: storage);
    final cart = CartController()
      ..add(Product(id: 1, code: 'SC-01', name: 'Signature Cut', barcode: '', mrp: 45, type: 'service', categoryName: 'Hair', duration: '45', totalStock: 5, thumbnail: ''));

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

    await tester.pumpWidget(MultiProvider(
      providers: [
        Provider<ApiClient>.value(value: client),
        Provider<ApiService>.value(value: service),
        ChangeNotifierProvider<AuthController>.value(value: auth),
        ChangeNotifierProvider<CartController>.value(value: cart),
      ],
      child: MaterialApp.router(theme: buildAstraTheme(AstraPresets.emeraldGold), routerConfig: router),
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
    expect(service.lastPayload?['paymentMethod'], 'Cash');
    expect(service.lastPayload?.containsKey('sendToWhatsapp'), true);
    // We must now be on the Invoice screen.
    expect(find.text('INV-9001'), findsWidgets, reason: 'should have navigated to the invoice');
  });
}
