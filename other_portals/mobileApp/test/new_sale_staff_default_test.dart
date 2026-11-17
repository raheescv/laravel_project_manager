import 'package:flutter/material.dart';
import 'package:flutter_test/flutter_test.dart';

import 'package:invo/features/sale/screens/v3/new_sale_screen.dart';
import 'package:invo/shared/domain/models/index.dart';

import 'support/test_harness.dart';

/// A fresh ticket's STAFF is the signed-in user (Maya, id 14) whenever they are
/// on the staff list — the employee last picked on this device only fills in
/// for a user who isn't assignable staff (e.g. an office admin).
void main() {
  TestWidgetsFlutterBinding.ensureInitialized();

  const maya = Employee(id: 14, name: 'Maya Chen', code: 'EMP-014', mobile: '', designation: 'Senior Stylist');
  const omar = Employee(id: 30, name: 'Omar Haddad', code: 'EMP-030', mobile: '', designation: 'Barber');

  const haneefa = ApiUser(
    id: '44',
    name: 'Haneefa Babu',
    code: 'EMP-044',
    email: '',
    mobile: '',
    isAdmin: false,
    designation: 'Cashier',
    role: 'staff',
    branchId: '3',
    daySessionStatus: 'open',
    daySessionDate: '2026-06-14',
    permissions: [],
  );

  /// Sale 41 as the server returns it, with [lines] rung up by Omar.
  Sale omarsSale({int lines = 1}) => Sale.fromJson({
        'id': '41',
        'invoice_no': 'INV-41',
        'status': 'completed',
        'customer': {'name': 'Walk-in'},
        'items': [
          for (var i = 0; i < lines; i++)
            {'id': 900 + i, 'product_id': 7 + i, 'employee_id': omar.id, 'employee': omar.name, 'name': 'Cut', 'quantity': 1, 'unit_price': 50},
        ],
        'payments': const [],
        'summary': const {'grand_total': 50},
      });

  Future<TestHarness> harness(WidgetTester tester, List<Employee> staff,
      {bool admin = true, Future<void> Function(TestHarness d)? before}) async {
    final d = TestHarness();
    await d.init(admin: admin);
    d.lookup.staff = staff;
    await d.storage.setSaleStylist(omar.id, omar.name);
    await before?.call(d);
    tester.view.physicalSize = const Size(390, 760);
    tester.view.devicePixelRatio = 1.0;
    addTearDown(tester.view.resetPhysicalSize);
    addTearDown(tester.view.resetDevicePixelRatio);
    await tester.pumpWidget(d.wrap(const NewSaleScreen()));
    await tester.pump();
    await tester.pump(const Duration(milliseconds: 400));
    return d;
  }

  testWidgets('signed-in user on the staff list beats the remembered employee', (tester) async {
    final d = await harness(tester, const [omar, maya]);
    addTearDown(d.dispose);
    expect(d.cart.stylistId, maya.id);
    expect(d.cart.stylistName, maya.name);
  });

  testWidgets('falls back to the remembered employee when the user is not staff', (tester) async {
    final d = await harness(tester, const [omar]);
    addTearDown(d.dispose);
    expect(d.cart.stylistId, omar.id);
    expect(d.cart.stylistName, omar.name);
  });

  testWidgets('a self-only cashier taking over a till left mid-edit is the staff', (tester) async {
    final d = await harness(tester, const [omar], admin: false, before: (d) async {
      d.cart.seedFromSale(omarsSale());
      d.authCubit.seedSession(haneefa);
      await tester.pump();
    });
    addTearDown(d.dispose);
    expect(d.cart.isEditing, isFalse);
    expect(d.cart.isEmpty, isTrue);
    expect(d.cart.stylistId, 44);
    expect(d.cart.stylistName, haneefa.name);
  });

  testWidgets('an edit emptied and left behind reopens as a new sale for the signed-in user', (tester) async {
    final d = await harness(tester, const [omar], admin: false, before: (d) async {
      d.cart.seedFromSale(omarsSale(lines: 0));
      d.cart.setStylist(omar.id, omar.name);
    });
    addTearDown(d.dispose);
    expect(d.cart.isEditing, isFalse);
    expect(d.cart.stylistId, maya.id);
    expect(d.cart.stylistName, maya.name);
  });

  testWidgets('a real edit keeps the staff the sale was rung up under', (tester) async {
    final d = await harness(tester, const [omar], admin: false, before: (d) async {
      d.cart.seedFromSale(omarsSale());
    });
    addTearDown(d.dispose);
    expect(d.cart.isEditing, isTrue);
    expect(d.cart.stylistId, omar.id);
    expect(d.cart.stylistName, omar.name);
  });
}
