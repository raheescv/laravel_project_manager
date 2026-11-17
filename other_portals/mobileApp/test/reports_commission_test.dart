import 'package:flutter/material.dart';
import 'package:flutter_bloc/flutter_bloc.dart';
import 'package:flutter_test/flutter_test.dart';
import 'package:google_fonts/google_fonts.dart';

import 'package:invo/features/admin/logic/admin_cubit/admin_cubit.dart';
import 'package:invo/features/admin/screens/v3/reports_screen.dart';
import 'package:invo/shared/domain/constants/mobile_permissions.dart';
import 'package:invo/shared/domain/models/index.dart';

import 'support/test_harness.dart';

/// Reports ▸ Breakdown ▸ Commission: the web Employee Report's Sales Details
/// table — one line per staff member × product, net of returns, at the rate
/// set for the pair. An admin sees every line and can narrow it to one
/// person; a rank-and-file employee sees their own lines and no staff filter.
void main() {
  setUpAll(() => GoogleFonts.config.allowRuntimeFetching = false);

  const phone = Size(392, 812);

  Future<void> settle(WidgetTester tester) async {
    await tester.pump();
    await tester.pump(const Duration(milliseconds: 400));
  }

  Future<void> openCommission(WidgetTester tester, TestHarness d, {Size size = const Size(1180, 900)}) async {
    tester.view.physicalSize = size;
    tester.view.devicePixelRatio = 1.0;
    addTearDown(tester.view.resetPhysicalSize);
    addTearDown(tester.view.resetDevicePixelRatio);
    await tester.pumpWidget(d.wrap(const ReportsScreen()));
    await settle(tester);
    await tester.tap(find.text('Breakdown'));
    await settle(tester);
    await tester.tap(find.text('Commission'));
    await settle(tester);
  }

  AdminCubit adminOf(WidgetTester tester) => tester.element(find.byType(ReportsScreen)).read<AdminCubit>();

  testWidgets('an admin sees every staff × product line under named columns', (tester) async {
    final d = TestHarness();
    await d.init(admin: true);
    addTearDown(d.dispose);
    await openCommission(tester, d);

    for (final label in ['STAFF', 'PRODUCT', 'QTY', 'NET SALES', 'RATE']) {
      expect(find.text(label), findsOneWidget, reason: '$label should head its column');
    }
    expect(find.textContaining('COMMISSION'), findsOneWidget);
    expect(find.text('Liam Ortiz'), findsNWidgets(2));
    expect(find.text('Balayage'), findsOneWidget);
    expect(find.text('10%'), findsOneWidget);
    // A product with no rate set earns nothing, and says so.
    expect(find.text('—'), findsNWidgets(2), reason: 'Styling Wax shows a dash for both rate and commission');
    expect(find.text('3 lines · 2 staff'), findsOneWidget);
    expect(find.text('Staff · All'), findsOneWidget, reason: 'the staff filter sits on the toolbar');
    expect(d.admin.lastSort, 'amount');
    expect(tester.takeException(), isNull);
  });

  testWidgets('the grand total stands on the commission grid', (tester) async {
    final d = TestHarness();
    await d.init(admin: true);
    addTearDown(d.dispose);
    await openCommission(tester, d);

    double right(Finder f) => tester.getRect(f).right;
    expect(right(find.text('46')), closeTo(right(find.text('QTY')), 1.5));
    expect(right(find.text('4,305.00')), closeTo(right(find.text('NET SALES')), 1.5));
    expect(right(find.textContaining('330')), closeTo(right(find.text('240.00')), 1.5));
  });

  testWidgets('a big screen re-ranks by net sales from the head', (tester) async {
    final d = TestHarness();
    await d.init(admin: true);
    addTearDown(d.dispose);
    await openCommission(tester, d);

    await tester.tap(find.text('NET SALES'));
    await settle(tester);
    expect(d.admin.lastSort, 'net');

    // Net sales is a commission-only sort, so the next breakdown goes back to
    // the default rather than asking the server for a sort it doesn't carry.
    await tester.tap(find.text('By Item'));
    await settle(tester);
    expect(d.admin.lastSort, 'amount');
  });

  testWidgets('narrowing to one staff member drops the Staff column', (tester) async {
    final d = TestHarness();
    await d.init(admin: true);
    addTearDown(d.dispose);
    await openCommission(tester, d);

    adminOf(tester).setCommissionStaff(15, name: 'Liam Ortiz');
    await settle(tester);

    expect(d.admin.lastEmployeeId, 15);
    expect(find.text('STAFF'), findsNothing);
    // The toolbar button names who it is for. A big screen's toolbar row has
    // no separate staff chip beside it — only Reset to undo it.
    expect(find.text('Staff · Liam Ortiz'), findsOneWidget);

    await tester.tap(find.text('Reset'));
    await settle(tester);
    expect(d.admin.lastEmployeeId, isNull);
    expect(find.text('STAFF'), findsOneWidget);
  });

  testWidgets('a phone fits four segments and keeps who earned it in the caption', (tester) async {
    final d = TestHarness();
    await d.init(admin: true);
    addTearDown(d.dispose);
    await openCommission(tester, d, size: phone);

    for (final label in ['Item', 'Category', 'Staff']) {
      expect(find.text(label), findsOneWidget);
    }
    // The fourth segment, and the sort button it ranks by: `Sort · Commission`.
    expect(find.text('Commission'), findsNWidgets(2));
    expect(find.text('STAFF'), findsNothing);
    expect(find.text('NET SALES'), findsNothing);
    expect(find.text('Maya Chen · 6 sold · 2,400.00 × 10%'), findsOneWidget);
    expect(find.text('Grand total'), findsOneWidget);
    expect(tester.takeException(), isNull);
  });

  testWidgets('a rank-and-file employee reads their own commission, with no staff filter', (tester) async {
    final d = TestHarness();
    await d.init(admin: false);
    addTearDown(d.dispose);
    d.authCubit.seedSession(const ApiUser(
      id: '14',
      name: 'Maya Chen',
      code: 'EMP-014',
      email: 'maya@astra.co',
      mobile: '',
      isAdmin: false,
      type: 'employee',
      designation: 'Stylist',
      role: 'staff',
      branchId: '3',
      daySessionStatus: 'open',
      daySessionDate: '2026-06-14',
      permissions: [PermissionSlug.report],
    ));
    await openCommission(tester, d);

    expect(find.text('Only your sales'), findsOneWidget);
    expect(find.textContaining('Staff · '), findsNothing);
    expect(find.text('STAFF'), findsNothing);
    expect(find.text('YOU'), findsNothing, reason: 'every line is theirs, so none is singled out');
    expect(tester.takeException(), isNull);
  });
}
