// Dashboard, My Jobs (phone list + tablet master–detail) and complaint detail
// (pushed and embedded) render and react without framework errors.
import 'package:flutter/material.dart';
import 'package:flutter_bloc/flutter_bloc.dart';
import 'package:flutter_test/flutter_test.dart';

import 'package:invo/features/technician/logic/complaint_detail_cubit/complaint_detail_cubit.dart';
import 'package:invo/features/technician/screens/v3/complaint_detail_screen.dart';
import 'package:invo/features/technician/screens/v3/complaints_list_screen.dart';
import 'package:invo/features/technician/screens/v3/technician_dashboard_screen.dart';

import '../support/fakes.dart';
import '../support/harness.dart';

void main() {
  const sizes = [('phone', phoneSize), ('tablet', tabletSize), ('wide', wideSize)];

  for (final (label, size) in sizes) {
    testWidgets('dashboard ($label)', (tester) async {
      await setUpEnv();
      await pumpScreen(tester, const TechnicianDashboardScreen(), size: size);
      expectNoErrors(tester);
      expect(find.textContaining('Welcome back'), findsOneWidget);
      expect(find.text('UP NEXT'), findsOneWidget);
      await tester.scrollUntilVisible(find.text('RECENT COMPLAINTS'), 300, scrollable: find.byType(Scrollable).first);
      expectNoErrors(tester);
    });

    testWidgets('My Jobs list and filters ($label)', (tester) async {
      final env = await setUpEnv();
      await pumpScreen(tester, const ComplaintsListScreen(), size: size);
      expectNoErrors(tester);
      expect(find.text('My Jobs'), findsWidgets);

      await tester.tap(find.text('Completed').first);
      await settle(tester);
      await tester.tap(find.text('Critical').first);
      await settle(tester);
      await tester.tap(find.text('7 days').first);
      await settle(tester);
      expectNoErrors(tester);
      final last = env.tech.complaintQueries.last;
      expect(last['status'], 'completed');
      expect(last['priority'], 'critical');
      expect(last['from'], isNotNull);
    });

    testWidgets('complaint detail, pushed ($label)', (tester) async {
      await setUpEnv();
      await pumpScreen(
        tester,
        BlocProvider(create: (_) => ComplaintDetailCubit(3)..load(), child: const ComplaintDetailScreen()),
        size: size,
      );
      expectNoErrors(tester);
      expect(find.textContaining('MR-1003'), findsWidgets);
      await tester.scrollUntilVisible(find.text('AC capacitor 35uF'), 300, scrollable: find.byType(Scrollable).first);
      await tester.scrollUntilVisible(find.text('Customer available after 4pm'), 300, scrollable: find.byType(Scrollable).first);
      await tester.scrollUntilVisible(find.text('Complete'), 300, scrollable: find.byType(Scrollable).first);
      expectNoErrors(tester);
    });
  }

  testWidgets('complaint detail shows retry when it cannot load', (tester) async {
    await setUpEnv(tech: FakeTechnicianRepository(fail: true));
    await pumpScreen(
      tester,
      BlocProvider(create: (_) => ComplaintDetailCubit(3)..load(), child: const ComplaintDetailScreen()),
    );
    expectNoErrors(tester);
    expect(find.text('Could not load'), findsOneWidget);
  });

  testWidgets('tablet master–detail: switching jobs while a detail is loading', (tester) async {
    final env = await setUpEnv();
    env.tech.detailDelay = const Duration(milliseconds: 500);
    await pumpScreen(tester, const ComplaintsListScreen(), size: tabletSize, settleAfter: false);
    // Switch rows without letting the first detail finish — the old pane's
    // cubit is closed mid-load and must not emit afterwards.
    await tester.pump();
    await tester.pump();
    final rows = find.text('Door lock jammed');
    expect(rows, findsWidgets);
    await tester.tap(rows.first);
    await tester.pump(const Duration(milliseconds: 100));
    await tester.tap(find.text('Light flickering').first);
    await tester.pump(const Duration(milliseconds: 100));
    await settle(tester);
    expectNoErrors(tester);
    expect(env.tech.calls.where((c) => c.startsWith('detail:')).length, greaterThanOrEqualTo(2));

    // Close the pane, then reopen by selecting a row.
    await tester.tap(find.byIcon(Icons.close).first);
    await settle(tester);
    expect(find.text('Select a job'), findsOneWidget);
    await tester.tap(find.text('Door lock jammed').first);
    await settle(tester);
    expectNoErrors(tester);
  });

  testWidgets('phone ↔ tablet resize keeps My Jobs working', (tester) async {
    await setUpEnv();
    await pumpScreen(tester, const ComplaintsListScreen(), size: phoneSize);
    for (final size in [tabletSize, phoneSize, wideSize, phoneSize]) {
      tester.view.physicalSize = size;
      await settle(tester);
      expectNoErrors(tester);
    }
  });
}
