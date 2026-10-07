// Risky flows through the REAL app (router, shell, rail, providers).
import 'package:flutter/material.dart';
import 'package:flutter_test/flutter_test.dart';

import 'package:invo/features/checklist/screens/v3/checklist_room_screen.dart';
import 'package:invo/features/checklist/screens/v3/checklist_rooms_screen.dart';
import 'package:invo/features/shell/screens/v3/technician_shell.dart';

import 'support/harness.dart';

Future<void> _openChecklists(WidgetTester tester) async {
  await tester.tap(find.text('Checklists').last);
  await settle(tester);
}

void main() {
  testWidgets('sign out then sign in: the inbox starts clean for the new session', (tester) async {
    final env = await setUpEnv();
    await pumpApp(tester);
    await _openChecklists(tester);
    expect(env.inbox.state.jobs, isNotEmpty);
    env.inbox.setSearch('villa');
    await settle(tester);
    expect(env.inbox.state.search, 'villa');

    await env.authCubit.logout();
    await settle(tester);
    expectNoErrors(tester);
    expect(find.byType(TechnicianShell), findsNothing);
    expect(env.auth.calls, contains('logout'));

    for (final d in ['1', '2', '3', '4']) {
      await tester.tap(find.text(d).last);
      await tester.pump();
    }
    await settle(tester);
    expectNoErrors(tester);
    expect(find.byType(TechnicianShell), findsOneWidget);
    // The shell was rebuilt: the inbox was reset and reloaded without the old search.
    expect(env.inbox.state.search, isEmpty);
    expect(env.checklist.jobQueries.last['search'], isEmpty);
  });

  for (final (label, size) in [('phone', phoneSize), ('tablet', tabletSize)]) {
    testWidgets('inbox → rooms → room → item sheet → back ($label)', (tester) async {
      final env = await setUpEnv();
      await pumpApp(tester, size: size);
      await _openChecklists(tester);

      await tester.tap(find.textContaining('Unit 305').first);
      await settle(tester);
      expectNoErrors(tester);
      expect(find.byType(ChecklistRoomsScreen), findsOneWidget);

      await tester.tap(find.text('Kitchen').first);
      await settle(tester);
      expectNoErrors(tester);
      expect(find.byType(ChecklistRoomScreen), findsOneWidget);

      await tester.tap(find.text('Refrigerator'));
      await settle(tester);
      await tester.tap(find.text('Damaged').last);
      await tester.pump();
      await tester.tap(find.text('Save & next'));
      await settle(tester);
      expectNoErrors(tester);
      expect(env.checklist.lineChanges.single['line'], 504);

      // Close the sheet, then back out to the inbox.
      await tester.tapAt(const Offset(20, 60));
      await settle(tester);
      await tester.binding.handlePopRoute();
      await settle(tester);
      await tester.binding.handlePopRoute();
      await settle(tester);
      expectNoErrors(tester);
      expect(find.byType(TechnicianShell), findsOneWidget);
    });
  }

  testWidgets('tablet: the rail on a pushed checklist route lands on My Jobs', (tester) async {
    await setUpEnv();
    await pumpApp(tester, size: tabletSize);
    await _openChecklists(tester);
    await tester.tap(find.textContaining('Unit 305').first);
    await settle(tester);
    expect(find.byType(ChecklistRoomsScreen), findsOneWidget);

    await tester.tap(find.text('My Jobs').last);
    await settle(tester);
    expectNoErrors(tester);
    expect(find.byType(ChecklistRoomsScreen), findsNothing);
    expect(find.text('My Jobs'), findsWidgets);
  });

  testWidgets('rotate phone ↔ tablet with a fixture sheet open on a pushed route, then save', (tester) async {
    final env = await setUpEnv();
    await pumpApp(tester);
    await _openChecklists(tester);
    await tester.tap(find.textContaining('Unit 305').first);
    await settle(tester);
    await tester.tap(find.text('Kitchen').first);
    await settle(tester);
    await tester.scrollUntilVisible(find.text('Re-grout tiles behind the sink'), 200,
        scrollable: find.byType(Scrollable).last);
    await tester.tap(find.text('Re-grout tiles behind the sink'));
    await settle(tester);

    for (final size in [tabletSize, phoneSize, tabletSize]) {
      tester.view.physicalSize = size;
      await settle(tester, frames: 5);
      expectNoErrors(tester);
    }
    await tester.tap(find.text('Completed').last);
    await tester.pump();
    await tester.tap(find.text('Save'));
    await settle(tester);
    expectNoErrors(tester);
    expect(env.checklist.calls, contains('updateFixtureEntry:91:null:completed'));
  });

  testWidgets('a toast never blocks the room\'s action bar', (tester) async {
    await setUpEnv();
    await pumpApp(tester);
    await _openChecklists(tester);
    await tester.tap(find.textContaining('Unit 305').first);
    await settle(tester);
    await tester.tap(find.text('Kitchen').first);
    await settle(tester);

    await tester.tap(find.text('Rest are good'));
    await tester.pump(const Duration(milliseconds: 400)); // toast showing
    expect(find.text('Remaining items marked good'), findsOneWidget);
    await tester.tap(find.text('Next room').hitTestable());
    await settle(tester);
    expectNoErrors(tester);
    expect(find.text('Master Bedroom'), findsWidgets);
  });

  testWidgets('My Jobs deep link and a complaint opened from the dashboard', (tester) async {
    final env = await setUpEnv();
    await pumpApp(tester);
    await tester.scrollUntilVisible(find.text('Open job'), 200, scrollable: find.byType(Scrollable).first);
    await tester.tap(find.text('Open job'));
    await settle(tester);
    expectNoErrors(tester);
    expect(env.tech.calls, contains('detail:1'));
    await tester.binding.handlePopRoute();
    await settle(tester);
    expectNoErrors(tester);
  });
}
