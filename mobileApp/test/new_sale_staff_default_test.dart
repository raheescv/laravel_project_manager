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

  Future<TestHarness> harness(WidgetTester tester, List<Employee> staff) async {
    final d = TestHarness();
    await d.init();
    d.lookup.staff = staff;
    await d.storage.setSaleStylist(omar.id, omar.name);
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
}
