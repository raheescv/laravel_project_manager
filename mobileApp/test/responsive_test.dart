import 'package:invo/core/api_client.dart';
import 'package:invo/core/api_service.dart';
import 'package:invo/core/config.dart';
import 'package:invo/core/responsive.dart';
import 'package:invo/core/storage.dart';
import 'package:invo/features/admin/dashboard_screen.dart';
import 'package:invo/features/admin/reports_screen.dart';
import 'package:invo/features/auth/login_screen.dart';
import 'package:invo/features/profile/change_pin_screen.dart';
import 'package:invo/features/profile/edit_profile_screen.dart';
import 'package:invo/features/profile/profile_screen.dart';
import 'package:invo/features/sale/cart_screen.dart';
import 'package:invo/features/sale/invoice_screen.dart';
import 'package:invo/features/sale/new_sale_screen.dart';
import 'package:invo/features/sale/review_pay_screen.dart';
import 'package:invo/features/sales/sales_list_screen.dart';
import 'package:invo/features/settings/settings_screen.dart';
import 'package:invo/features/shell/home_shell.dart';
import 'package:invo/models/models.dart';
import 'package:invo/state/admin_controller.dart';
import 'package:invo/state/auth_controller.dart';
import 'package:invo/state/cart_controller.dart';
import 'package:invo/state/catalog_controller.dart';
import 'package:invo/state/currency_controller.dart';
import 'package:invo/state/theme_controller.dart';
import 'package:invo/theme/palette.dart';
import 'package:invo/theme/theme.dart';
import 'package:flutter/material.dart';
import 'package:flutter_test/flutter_test.dart';
import 'package:go_router/go_router.dart';
import 'package:google_fonts/google_fonts.dart';
import 'package:provider/provider.dart';
import 'package:shared_preferences/shared_preferences.dart';

/// Canned API so screens render with realistic data and never hit the network.
class FakeApiService extends ApiService {
  FakeApiService(super.client);

  Product _p(int id, String code, String name, String cat, double price, String type) =>
      Product(id: id, code: code, name: name, barcode: '', mrp: price, type: type, categoryName: cat, duration: '45', totalStock: 5, thumbnail: '');

  @override
  Future<Paginated<Product>> products({String? search, int? mainCategoryId, String? type, int page = 1, int perPage = 50}) async =>
      Paginated(items: [
        _p(1, 'SC-01', 'Signature Cut', 'Hair', 45, 'service'),
        _p(2, 'BL-10', 'Balayage', 'Color', 180, 'service'),
        _p(3, 'SP-20', 'Spa Ritual', 'Spa', 90, 'service'),
        _p(4, 'PR-01', 'Shampoo Bottle', 'Retail', 22, 'product'),
      ], currentPage: 1, lastPage: 1, total: 4);

  @override
  Future<List<Category>> categories() async => [
        Category(id: 1, name: 'Hair', productCount: 1),
        Category(id: 2, name: 'Color', productCount: 1),
        Category(id: 3, name: 'Spa', productCount: 1),
      ];

  @override
  Future<DashboardData> dashboard({int? branchId}) async => DashboardData(
        today: [
          Metric(title: "Today's Sales", value: 4200, type: 'currency'),
          Metric(title: "Today's Bills", value: 18, type: 'count'),
        ],
        inventory: [
          Metric(title: 'Active Employees', value: 6, type: 'count'),
          Metric(title: 'Customers', value: 540, type: 'count'),
        ],
        business: [
          Metric(title: 'weekly sales', value: 21000, type: 'currency', percentage: '12%'),
          Metric(title: 'Monthly sales', value: 86000, type: 'currency', percentage: '-4%'),
        ],
      );

  @override
  Future<Map<String, dynamic>> report({required String type, String? startDate, String? endDate}) async => {
        'rows': type == 'employeewise'
            ? [
                {'employee_name': 'Maya Chen', 'bills_count': 94, 'items_count': 120, 'revenue': 2540},
                {'employee_name': 'Liam Ortiz', 'bills_count': 78, 'items_count': 90, 'revenue': 2110},
              ]
            : [
                {'invoice_no': '#1042', 'customer': 'Walk-in', 'date': '2026-06-14', 'paid': 248.4},
                {'invoice_no': '#1041', 'customer': 'A. Rivera', 'date': '2026-06-13', 'paid': 92.0},
              ],
      };

  @override
  Future<List<Map<String, dynamic>>> sales({String? status, bool mineOnly = false}) async => [
        {'id': '1', 'invoice_no': '#1042', 'customer_name': 'Walk-in', 'date': '2026-06-14', 'paid': 248.4, 'status': 'completed'},
        {'id': '2', 'invoice_no': '#1041', 'customer_name': 'A. Rivera', 'date': '2026-06-13', 'paid': 92.0, 'status': 'completed'},
      ];

  @override
  Future<List<PaymentMethod>> paymentMethods() async => [
        PaymentMethod(id: 1, name: 'Cash'),
        PaymentMethod(id: 2, name: 'Card'),
        PaymentMethod(id: 3, name: 'Bank Transfer'),
      ];
}

class Deps {
  late ApiClient client;
  late FakeApiService service;
  late AuthController auth;
  late ThemeController theme;
  late CurrencyController currency;
  late CartController cart;

  Future<void> init({bool admin = false}) async {
    SharedPreferences.setMockInitialValues({});
    final storage = await Storage.create();
    client = ApiClient(storage: storage, config: AppConfig(baseUrl: 'http://test.local', tenant: ''));
    service = FakeApiService(client);
    auth = AuthController(client: client, service: service, storage: storage);
    auth.user = ApiUser(
      id: '14', name: 'Maya Chen', code: 'EMP-014', email: 'maya@astra.co', mobile: '+1 415 555 0142',
      isAdmin: admin, designation: 'Senior Stylist', branchId: '3', daySessionStatus: 'open', daySessionDate: '2026-06-14',
    );
    auth.status = AuthStatus.signedIn;
    theme = ThemeController(storage);
    currency = CurrencyController(storage);
    cart = CartController();
  }

  Widget wrap(Widget child) => MultiProvider(
        providers: [
          Provider<ApiClient>.value(value: client),
          Provider<ApiService>.value(value: service),
          ChangeNotifierProvider<AuthController>.value(value: auth),
          ChangeNotifierProvider<ThemeController>.value(value: theme),
          ChangeNotifierProvider<CurrencyController>.value(value: currency),
          ChangeNotifierProvider<CartController>.value(value: cart),
          ChangeNotifierProvider<CatalogController>(create: (_) => CatalogController(service)),
          ChangeNotifierProvider<AdminController>(create: (_) => AdminController(service)),
        ],
        // A GoRouter so context.push/go inside callbacks resolve (not invoked here).
        child: MaterialApp.router(
          theme: buildAstraTheme(AstraPresets.emeraldGold),
          routerConfig: GoRouter(routes: [GoRoute(path: '/', builder: (_, __) => child)]),
        ),
      );
}

Sale _demoSale() => Sale.fromJson({
      'id': '5001', 'invoice_no': 'INV-0001', 'date': '2026-06-14', 'status': 'completed', 'branch': 'Downtown',
      'customer': {'name': 'Walk-in', 'mobile': ''},
      'items': [
        {'name': 'Signature Cut', 'type': 'service', 'employee': 'Maya', 'quantity': 1, 'unit_price': 45, 'discount': 0, 'total': 45},
        {'name': 'Balayage', 'type': 'service', 'employee': 'Liam', 'quantity': 1, 'unit_price': 180, 'discount': 18, 'total': 162},
      ],
      'payments': [{'method': 'Card', 'amount': 207}],
      'summary': {'gross_amount': 225, 'item_discount': 18, 'other_discount': 0, 'tax_amount': 0, 'paid': 207},
      'created_by': 'Maya',
    });

const phone = Size(390, 844);
const tablet = Size(1194, 834);

void main() {
  setUpAll(() => GoogleFonts.config.allowRuntimeFetching = false);

  Future<void> pumpAt(WidgetTester tester, Size size, Widget child, Deps d) async {
    tester.view.physicalSize = size;
    tester.view.devicePixelRatio = 1.0;
    addTearDown(tester.view.resetPhysicalSize);
    addTearDown(tester.view.resetDevicePixelRatio);
    await tester.pumpWidget(d.wrap(child));
    await tester.pump();
    await tester.pump(const Duration(milliseconds: 400));
    expect(tester.takeException(), isNull);
  }

  /// Each entry renders cleanly (no overflow / no thrown exception) at BOTH sizes.
  final screens = <String, Future<Widget> Function(Deps d)>{
    'Login': (d) async => const LoginScreen(),
    'New Sale': (d) async => const NewSaleScreen(),
    'Cart (filled)': (d) async {
      d.cart.add((await d.service.products()).items.first);
      d.cart.add((await d.service.products()).items[1]);
      return const CartScreen();
    },
    'Review & Pay': (d) async {
      d.cart.add((await d.service.products()).items.first);
      return const ReviewPayScreen();
    },
    'Invoice': (d) async => InvoiceScreen(sale: _demoSale()),
    'Sales list': (d) async => const SalesListScreen(),
    'Dashboard': (d) async => const DashboardScreen(),
    'Reports': (d) async => const ReportsScreen(),
    'Settings': (d) async => const SettingsScreen(),
    'Profile': (d) async => const ProfileScreen(),
    'Change PIN': (d) async => const ChangePinScreen(),
    'Edit Profile': (d) async => const EditProfileScreen(),
    'Admin shell': (d) async => const HomeShell(),
  };

  for (final entry in screens.entries) {
    testWidgets('${entry.key} renders without overflow on phone', (tester) async {
      final d = Deps();
      await d.init(admin: true);
      await pumpAt(tester, phone, await entry.value(d), d);
    });
    testWidgets('${entry.key} renders without overflow on tablet', (tester) async {
      final d = Deps();
      await d.init(admin: true);
      await pumpAt(tester, tablet, await entry.value(d), d);
    });
  }

  testWidgets('Cart line items render and Edit sheet opens', (tester) async {
    final d = Deps();
    await d.init();
    d.cart.add((await d.service.products()).items.first);
    tester.view.physicalSize = const Size(430, 920);
    tester.view.devicePixelRatio = 1.0;
    addTearDown(tester.view.resetPhysicalSize);
    addTearDown(tester.view.resetDevicePixelRatio);
    await tester.pumpWidget(d.wrap(const CartScreen()));
    await tester.pump();
    await tester.pump(const Duration(milliseconds: 300));
    expect(find.text('Edit details'), findsWidgets, reason: 'cart line should render');
    await tester.tap(find.text('Edit details').first);
    await tester.pump();
    await tester.pump(const Duration(milliseconds: 450));
    expect(tester.takeException(), isNull, reason: 'opening the edit sheet must not throw');
    expect(find.text('Save changes'), findsOneWidget, reason: 'edit sheet should be visible');
    expect(find.text('LINE TOTAL'), findsOneWidget);
  });

  testWidgets('Review & Pay: Credit zeroes the paid amount and Custom sheet opens', (tester) async {
    final d = Deps();
    await d.init();
    d.cart.add((await d.service.products()).items.first); // Signature Cut, $45
    tester.view.physicalSize = const Size(430, 1100);
    tester.view.devicePixelRatio = 1.0;
    addTearDown(tester.view.resetPhysicalSize);
    addTearDown(tester.view.resetDevicePixelRatio);
    await tester.pumpWidget(d.wrap(const ReviewPayScreen()));
    await tester.pump();
    await tester.pump(const Duration(milliseconds: 400));

    // Cash is the default → fully paid, ready to submit.
    expect(find.text('Ready to Submit'), findsOneWidget);

    // Credit records no payment → a remaining balance.
    await tester.tap(find.text('Credit'));
    await tester.pump();
    expect(d.cart.payMode, PayMode.credit);
    expect(d.cart.paidAmount, 0);
    expect(find.text('Partial Payment'), findsOneWidget);

    // Custom opens the split-payment sheet.
    await tester.tap(find.text('Custom'));
    await tester.pump();
    await tester.pump(const Duration(milliseconds: 400));
    expect(tester.takeException(), isNull, reason: 'opening the custom payment sheet must not throw');
    expect(find.text('Custom Payment'), findsOneWidget);
    expect(find.text('Save Payment'), findsOneWidget);
  });

  testWidgets('New Sale with a cart item keeps the catalog visible (phone)', (tester) async {
    final d = Deps();
    await d.init();
    d.cart.add((await d.service.products()).items.first);
    tester.view.physicalSize = const Size(390, 760);
    tester.view.devicePixelRatio = 1.0;
    addTearDown(tester.view.resetPhysicalSize);
    addTearDown(tester.view.resetDevicePixelRatio);
    await tester.pumpWidget(d.wrap(const NewSaleScreen()));
    await tester.pump();
    await tester.pump(const Duration(milliseconds: 400));
    expect(tester.takeException(), isNull);
    // The cart bar shows (non-empty cart) but must NOT balloon and squeeze the body.
    expect(find.text('View Cart'), findsOneWidget);
    expect(find.text('Signature Cut'), findsOneWidget, reason: 'catalog must keep its space when the cart bar is shown');
  });

  // The Password tab (username/password form) must also render cleanly.
  for (final size in [phone, tablet]) {
    final label = size.width < Breakpoints.tablet ? 'phone' : 'tablet';
    testWidgets('Login password tab renders without overflow on $label', (tester) async {
      final d = Deps();
      await d.init(admin: true);
      tester.view.physicalSize = size;
      tester.view.devicePixelRatio = 1.0;
      addTearDown(tester.view.resetPhysicalSize);
      addTearDown(tester.view.resetDevicePixelRatio);
      await tester.pumpWidget(d.wrap(const LoginScreen()));
      await tester.pump();
      await tester.tap(find.text('Password'));
      await tester.pump(const Duration(milliseconds: 300));
      expect(tester.takeException(), isNull);
    });
  }
}
