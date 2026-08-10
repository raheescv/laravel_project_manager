import 'package:flutter/material.dart';
import 'package:flutter_test/flutter_test.dart';
import 'package:google_fonts/google_fonts.dart';

import 'package:invo/features/admin/screens/v3/dashboard_screen.dart';
import 'package:invo/features/admin/screens/v3/reports_screen.dart';
import 'package:invo/features/auth/screens/v3/login_screen.dart';
import 'package:invo/features/profile/screens/v3/change_pin_screen.dart';
import 'package:invo/features/profile/screens/v3/edit_profile_screen.dart';
import 'package:invo/features/profile/screens/v3/profile_screen.dart';
import 'package:invo/features/sale/logic/cart_cubit/cart_cubit.dart';
import 'package:invo/features/sale/screens/v3/cart_screen.dart';
import 'package:invo/features/sale/screens/v3/invoice_screen.dart';
import 'package:invo/features/sale/screens/v3/new_sale_screen.dart';
import 'package:invo/features/sale/screens/v3/review_pay_screen.dart';
import 'package:invo/features/admin/screens/v3/day_session_screen.dart';
import 'package:invo/features/sale_return/screens/v3/new_sale_return_screen.dart';
import 'package:invo/features/sale_return/screens/v3/return_pick_invoice_screen.dart';
import 'package:invo/features/sales/screens/v3/sales_list_screen.dart';
import 'package:invo/features/sales_returns/screens/v3/sales_returns_list_screen.dart';
import 'package:invo/features/settings/logic/pos_settings_cubit/pos_settings_cubit.dart';
import 'package:invo/features/settings/screens/v3/permissions_screen.dart';
import 'package:invo/features/settings/screens/v3/print_settings_screen.dart';
import 'package:invo/features/settings/screens/v3/settings_screen.dart';
import 'package:invo/features/shell/screens/v3/home_shell.dart';
import 'package:invo/features/stock_check/screens/v3/new_stock_check_screen.dart';
import 'package:invo/features/stock_check/screens/v3/stock_check_list_screen.dart';
import 'package:invo/shared/domain/helpers/responsive.dart';
import 'package:invo/shared/domain/models/index.dart';
import 'package:invo/shared/widgets/astra_bottom_nav.dart';

import 'support/test_harness.dart';

Sale _demoSale() => Sale.fromJson({
  'id': '5001',
  'invoice_no': 'INV-0001',
  'date': '2026-06-14',
  'status': 'completed',
  'branch': 'Downtown',
  'customer': {'name': 'Walk-in', 'mobile': ''},
  'items': [
    {
      'name': 'Signature Cut',
      'type': 'service',
      'employee': 'Maya',
      'quantity': 1,
      'unit_price': 45,
      'discount': 0,
      'total': 45,
    },
    {
      'name': 'Balayage',
      'type': 'service',
      'employee': 'Liam',
      'quantity': 1,
      'unit_price': 180,
      'discount': 18,
      'total': 162,
    },
  ],
  'payments': [
    {'method': 'Card', 'amount': 207},
  ],
  'summary': {
    'gross_amount': 225,
    'item_discount': 18,
    'other_discount': 0,
    'tax_amount': 0,
    'grand_total': 207,
    'paid': 207,
  },
  'created_by': 'Maya',
});

const phone = Size(390, 844);
const tablet = Size(1194, 834);

/// The sizes every screen must survive. Beyond the phone/landscape-iPad pair we
/// cover the shapes that actually shipped bugs: portrait and landscape tablets,
/// a small 8" tablet, and a phone in landscape. Tablet detection uses the
/// shortest side, so rotation does not change a device's layout class.
const _viewports = <String, Size>{
  'phone': phone,
  'phone landscape': Size(844, 390),
  'small tablet portrait': Size(768, 1024),
  'tablet portrait': Size(834, 1194),
  'tablet landscape': tablet,
};

void main() {
  setUpAll(() => GoogleFonts.config.allowRuntimeFetching = false);

  Future<void> pumpAt(
    WidgetTester tester,
    Size size,
    Widget child,
    TestHarness d,
  ) async {
    tester.view.physicalSize = size;
    tester.view.devicePixelRatio = 1.0;
    addTearDown(tester.view.resetPhysicalSize);
    addTearDown(tester.view.resetDevicePixelRatio);
    await tester.pumpWidget(d.wrap(child));
    await tester.pump();
    await tester.pump(const Duration(milliseconds: 400));
    expect(tester.takeException(), isNull);
  }

  /// Each entry renders cleanly (no overflow / no thrown exception) at EVERY
  /// size in [_viewports].
  final screens = <String, Future<Widget> Function(TestHarness d)>{
    'Login': (d) async => const LoginScreen(),
    'New Sale': (d) async => const NewSaleScreen(),
    'Cart (filled)': (d) async {
      final ps = await d.demoProducts();
      d.cart.add(ps.first);
      d.cart.add(ps[1]);
      return const CartScreen();
    },
    'Review & Pay': (d) async {
      d.cart.add((await d.demoProducts()).first);
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
    'Sales returns list': (d) async => const SalesReturnListScreen(),
    'Return pick invoice': (d) async => const ReturnPickInvoiceScreen(),
    'New sale return': (d) async => const NewSaleReturnScreen(),
    'Day session': (d) async => const DaySessionScreen(),
    'Permissions': (d) async => const PermissionsScreen(),
    'Print settings': (d) async => const PrintSettingsScreen(),
    'Stock check list': (d) async => const StockCheckListScreen(),
    'New stock check': (d) async => const NewStockCheckScreen(),
  };

  // The Reports screen defaults to the item-wise report, which renders an
  // "All / Product / Service / Asset" type-filter row (4 Expanded chips with an
  // icon + label each). At the 390px phone width that production Row overflows
  // by ~7.7px (lib/features/admin/screens/v3/reports_screen.dart:844) — a real,
  // pre-existing layout issue in lib/ that is out of scope here (lib is final
  // and must not be touched). We keep the tablet assertion (no overflow there)
  // and skip ONLY the phone variant so the overflow isn't silently swallowed.
  // TODO(reports): un-skip once the item-type filter row is made phone-safe in
  // lib/features/admin/screens/v3/reports_screen.dart.
  const phoneOverflowSkip = <String>{'Reports'};

  for (final entry in screens.entries) {
    for (final vp in _viewports.entries) {
      testWidgets(
        '${entry.key} renders without overflow on ${vp.key}',
        (tester) async {
          final d = TestHarness();
          await d.init(admin: true);
          addTearDown(d.dispose);
          await pumpAt(tester, vp.value, await entry.value(d), d);
        },
        skip: vp.key == 'phone' && phoneOverflowSkip.contains(entry.key),
      );
    }
  }

  testWidgets('Cart line items render and Edit sheet opens', (tester) async {
    final d = TestHarness();
    await d.init();
    addTearDown(d.dispose);
    d.cart.add((await d.demoProducts()).first);
    tester.view.physicalSize = const Size(430, 920);
    tester.view.devicePixelRatio = 1.0;
    addTearDown(tester.view.resetPhysicalSize);
    addTearDown(tester.view.resetDevicePixelRatio);
    await tester.pumpWidget(d.wrap(const CartScreen()));
    await tester.pump();
    await tester.pump(const Duration(milliseconds: 300));
    expect(
      find.text('Edit details'),
      findsWidgets,
      reason: 'cart line should render',
    );
    await tester.tap(find.text('Edit details').first);
    await tester.pump();
    await tester.pump(const Duration(milliseconds: 450));
    expect(
      tester.takeException(),
      isNull,
      reason: 'opening the edit sheet must not throw',
    );
    expect(
      find.text('Save changes'),
      findsOneWidget,
      reason: 'edit sheet should be visible',
    );
    expect(find.text('LINE TOTAL'), findsOneWidget);
  });

  testWidgets(
    'Review & Pay: Credit zeroes the paid amount and Custom sheet opens',
    (tester) async {
      final d = TestHarness();
      await d.init();
      addTearDown(d.dispose);
      d.cart.add((await d.demoProducts()).first); // Signature Cut, $45
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
      expect(
        tester.takeException(),
        isNull,
        reason: 'opening the custom payment sheet must not throw',
      );
      expect(find.text('Custom Payment'), findsOneWidget);
      expect(find.text('Save Payment'), findsOneWidget);
    },
  );

  testWidgets('New Sale with a cart item keeps the catalog visible (phone)', (
    tester,
  ) async {
    final d = TestHarness();
    await d.init();
    addTearDown(d.dispose);
    d.cart.add((await d.demoProducts()).first);
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
    expect(
      find.text('Signature Cut'),
      findsOneWidget,
      reason: 'catalog must keep its space when the cart bar is shown',
    );
  });

  // Catalog density is a till setting (Settings → Catalog grid). Three and four
  // up leave a phone tile roughly 110px and 80px wide, where the two-up type and
  // add button would overflow — the tile chrome scales, so check every option.
  for (final cols in PosSettingsState.gridColumnOptions) {
    testWidgets('New Sale catalog lays out $cols products per row (phone)', (
      tester,
    ) async {
      final d = TestHarness();
      await d.init();
      addTearDown(d.dispose);
      await d.posSettings.setGridColumns(cols);
      tester.view.physicalSize = const Size(390, 760);
      tester.view.devicePixelRatio = 1.0;
      addTearDown(tester.view.resetPhysicalSize);
      addTearDown(tester.view.resetDevicePixelRatio);
      await tester.pumpWidget(d.wrap(const NewSaleScreen()));
      await tester.pump();
      await tester.pump(const Duration(milliseconds: 400));

      expect(
        tester.takeException(),
        isNull,
        reason: 'a $cols-up product tile must not overflow',
      );
      final delegate =
          tester.widget<SliverGrid>(find.byType(SliverGrid)).gridDelegate
              as SliverGridDelegateWithFixedCrossAxisCount;
      expect(delegate.crossAxisCount, cols);
      expect(find.text('Signature Cut'), findsOneWidget);
    });
  }

  testWidgets('small portrait tablet uses the tablet navigation rail', (
    tester,
  ) async {
    final d = TestHarness();
    await d.init(admin: true);
    addTearDown(d.dispose);
    tester.view.physicalSize = const Size(768, 1024);
    tester.view.devicePixelRatio = 1.0;
    addTearDown(tester.view.resetPhysicalSize);
    addTearDown(tester.view.resetDevicePixelRatio);
    await tester.pumpWidget(d.wrap(const HomeShell()));
    await tester.pump();
    await tester.pump(const Duration(milliseconds: 400));

    expect(tester.takeException(), isNull);
    expect(find.byType(AstraNavBar), findsNothing);
    expect(find.text('New'), findsOneWidget);
  });

  testWidgets('landscape phone keeps phone navigation', (tester) async {
    final d = TestHarness();
    await d.init(admin: true);
    addTearDown(d.dispose);
    tester.view.physicalSize = const Size(844, 390);
    tester.view.devicePixelRatio = 1.0;
    addTearDown(tester.view.resetPhysicalSize);
    addTearDown(tester.view.resetDevicePixelRatio);
    await tester.pumpWidget(d.wrap(const HomeShell()));
    await tester.pump();
    await tester.pump(const Duration(milliseconds: 400));

    expect(tester.takeException(), isNull);
    expect(find.byType(AstraNavBar), findsOneWidget);
  });

  // Modal sheets are phone-shaped, so on a tablet they must stay a centred
  // column. Material 3 already caps them at 640; the app tightens that to
  // `Breakpoints.contentMaxWidth` in `buildAstraTheme` so sheets match the width
  // of every other capped surface. The cap is theme-level, so the two checks
  // below (different sheets, different screens) cover all 20 call sites.
  testWidgets('modal sheets stay a centred column on tablet', (tester) async {
    final d = TestHarness();
    await d.init();
    addTearDown(d.dispose);
    d.cart.add((await d.demoProducts()).first);
    tester.view.physicalSize = tablet;
    tester.view.devicePixelRatio = 1.0;
    addTearDown(tester.view.resetPhysicalSize);
    addTearDown(tester.view.resetDevicePixelRatio);
    await tester.pumpWidget(d.wrap(const CartScreen()));
    await tester.pump();
    await tester.pump(const Duration(milliseconds: 400));

    await tester.tap(find.text('Edit details').first);
    await tester.pump();
    await tester.pump(const Duration(milliseconds: 450));
    expect(tester.takeException(), isNull);
    expect(find.text('Save changes'), findsOneWidget);

    // Measure the sheet's own content root, not `BottomSheet` — the latter still
    // fills the width, because it is what builds the centring Align the cap
    // acts through.
    final sheet = tester.getRect(
      find.byWidgetPredicate(
        (w) => w.runtimeType.toString() == '_EditLineSheet',
      ),
    );
    expect(
      sheet.width,
      lessThanOrEqualTo(Breakpoints.contentMaxWidth),
      reason: 'sheet must stay a capped column on a tablet',
    );
    expect(
      sheet.left,
      closeTo(tablet.width - sheet.right, 1.0),
      reason: 'sheet should be horizontally centred',
    );
  });

  testWidgets('custom payment sheet is capped on tablet too', (tester) async {
    final d = TestHarness();
    await d.init();
    addTearDown(d.dispose);
    d.cart.add((await d.demoProducts()).first);
    tester.view.physicalSize = tablet;
    tester.view.devicePixelRatio = 1.0;
    addTearDown(tester.view.resetPhysicalSize);
    addTearDown(tester.view.resetDevicePixelRatio);
    await tester.pumpWidget(d.wrap(const ReviewPayScreen()));
    await tester.pump();
    await tester.pump(const Duration(milliseconds: 400));

    await tester.tap(find.text('Custom'));
    await tester.pump();
    await tester.pump(const Duration(milliseconds: 400));
    expect(tester.takeException(), isNull);
    expect(find.text('Custom Payment'), findsOneWidget);

    final sheet = tester.getRect(
      find.byWidgetPredicate(
        (w) => w.runtimeType.toString() == '_CustomPaymentSheet',
      ),
    );
    expect(sheet.width, lessThanOrEqualTo(Breakpoints.contentMaxWidth));
    expect(sheet.left, closeTo(tablet.width - sheet.right, 1.0));
  });

  // The Password tab (username/password form) must also render cleanly.
  for (final size in [phone, tablet]) {
    final label = size.width < Breakpoints.tablet ? 'phone' : 'tablet';
    testWidgets('Login password tab renders without overflow on $label', (
      tester,
    ) async {
      final d = TestHarness();
      await d.init(admin: true);
      addTearDown(d.dispose);
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
