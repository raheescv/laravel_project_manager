// The hand-over checklist flow end to end: inbox, rooms, room (+ fixtures),
// item sheet / dialog, fixture sheet, hand-over, signature capture, photo
// viewer and PDF preview — at phone and tablet size.
import 'dart:typed_data';

import 'package:flutter/material.dart';
import 'package:flutter_bloc/flutter_bloc.dart';
import 'package:flutter_test/flutter_test.dart';

import 'package:invo/features/checklist/domain/models/checklist_models.dart';
import 'package:invo/features/checklist/logic/checklist_detail_cubit/checklist_detail_cubit.dart';
import 'package:invo/features/checklist/screens/v3/checklist_handover_screen.dart';
import 'package:invo/features/checklist/screens/v3/checklist_inbox_screen.dart';
import 'package:invo/features/checklist/screens/v3/checklist_room_screen.dart';
import 'package:invo/features/checklist/screens/v3/checklist_rooms_screen.dart';
import 'package:invo/features/checklist/widgets/v3/checklist_pdf.dart';
import 'package:invo/features/checklist/widgets/v3/photo_viewer.dart';
import 'package:invo/features/checklist/widgets/v3/signature_capture.dart';
import 'package:invo/shared/domain/constants/global_variables.dart';

import '../support/fakes.dart';
import '../support/harness.dart';
import '../support/sample_data.dart';

/// The detail cubit a checklist route provides, loaded for [id].
BlocProvider<ChecklistDetailCubit> _detail(int id) =>
    BlocProvider<ChecklistDetailCubit>(create: (_) => serviceLocator<ChecklistDetailCubit>()..load(id));

Future<void> _pumpWithDetail(WidgetTester tester, int id, Widget screen, {Size size = phoneSize}) =>
    pumpScreen(tester, screen, size: size, providers: [_detail(id)]);

void main() {
  const sizes = [('phone', phoneSize), ('tablet', tabletSize)];

  // ---------------------------------------------------------------- inbox --

  for (final (label, size) in [...sizes, ('wide', wideSize)]) {
    testWidgets('inbox with jobs in every group ($label)', (tester) async {
      await setUpEnv();
      await pumpScreen(tester, const ChecklistInboxScreen(), size: size);
      expectNoErrors(tester);
      expect(find.text('TODAY'), findsWidgets);
      expect(find.text('OVERDUE'), findsWidgets);
      expect(find.textContaining('Unit 305'), findsWidgets);
    });
  }

  testWidgets('inbox empty and failed states', (tester) async {
    await setUpEnv(checklist: FakeChecklistRepository(jobs: const []));
    await pumpScreen(tester, const ChecklistInboxScreen());
    expectNoErrors(tester);
    expect(find.text('No hand-overs'), findsOneWidget);
  });

  testWidgets('inbox failed state offers retry', (tester) async {
    final env = await setUpEnv(checklist: FakeChecklistRepository(failJobs: true));
    await pumpScreen(tester, const ChecklistInboxScreen(), size: tabletSize);
    expectNoErrors(tester);
    expect(find.text('Could not load'), findsOneWidget);
    env.checklist.failJobs = false;
    await tester.tap(find.text('Retry'));
    await settle(tester);
    expectNoErrors(tester);
    expect(find.text('No hand-overs'), findsNothing);
  });

  testWidgets('inbox filters send the right server params', (tester) async {
    final env = await setUpEnv();
    await pumpScreen(tester, const ChecklistInboxScreen());

    // Status segment.
    await tester.tap(find.text('Completed'));
    await settle(tester);
    expect(env.checklist.jobQueries.last['status'], 'completed');

    // Date preset "This week" → Monday..Sunday of the current week.
    await tester.tap(find.text('All dates'));
    await settle(tester);
    await tester.tap(find.text('This week').last);
    await settle(tester);
    final now = DateTime.now();
    final monday = DateTime(now.year, now.month, now.day - (now.weekday - 1));
    final sunday = DateTime(monday.year, monday.month, monday.day + 6);
    String iso(DateTime d) => '${d.year}-${d.month.toString().padLeft(2, '0')}-${d.day.toString().padLeft(2, '0')}';
    expect(env.checklist.jobQueries.last['from_date'], iso(monday));
    expect(env.checklist.jobQueries.last['to_date'], iso(sunday));

    // "Next 30 days" → today .. today+29.
    await tester.tap(find.text('This week').first);
    await settle(tester);
    await tester.tap(find.text('Next 30 days').last);
    await settle(tester);
    final today = DateTime(now.year, now.month, now.day);
    expect(env.checklist.jobQueries.last['from_date'], iso(today));
    expect(env.checklist.jobQueries.last['to_date'], iso(DateTime(today.year, today.month, today.day + 29)));

    // Move-out tab → date_basis.
    await tester.tap(find.text('Move-out'));
    await settle(tester);
    expect(env.checklist.jobQueries.last['date_basis'], 'move_out');

    // Clear (×) on the summary chip → defaults again.
    await tester.tap(find.byIcon(Icons.close).last);
    await settle(tester);
    final q = env.checklist.jobQueries.last;
    expect(q['status'], 'open');
    expect(q['from_date'], isNull);
    expect(q['date_basis'], isNull);
    expectNoErrors(tester);
  });

  // ---------------------------------------------------------- rooms/room --

  for (final (label, size) in [...sizes, ('wide', wideSize)]) {
    testWidgets('rooms screen ($label)', (tester) async {
      await setUpEnv();
      await _pumpWithDetail(tester, 12, const ChecklistRoomsScreen(), size: size);
      expectNoErrors(tester);
      expect(find.text('Kitchen'), findsWidgets);
      await tester.scrollUntilVisible(find.text('Hand-over & signatures'), 300,
          scrollable: find.byType(Scrollable).last);
      expectNoErrors(tester);
    });

    testWidgets('room with fixtures ($label)', (tester) async {
      await setUpEnv();
      await _pumpWithDetail(tester, 12, const ChecklistRoomScreen(roomName: 'Kitchen'), size: size);
      expectNoErrors(tester);
      expect(find.text('Cooker hood'), findsOneWidget);
      await tester.scrollUntilVisible(find.text('Re-grout tiles behind the sink'), 200,
          scrollable: find.byType(Scrollable).last);
      expectNoErrors(tester);
    });

    testWidgets('signed and ready-for-acceptance fixture areas ($label)', (tester) async {
      await setUpEnv();
      await _pumpWithDetail(tester, 12, const ChecklistRoomScreen(roomName: 'Master Bedroom'), size: size);
      await tester.scrollUntilVisible(find.textContaining('Mr. Khalid'), 200, scrollable: find.byType(Scrollable).last);
      expectNoErrors(tester);
    });
  }

  testWidgets('rooms screen shows retry for a hand-over that is not yours', (tester) async {
    await setUpEnv();
    await _pumpWithDetail(tester, 999, const ChecklistRoomsScreen());
    expectNoErrors(tester);
    expect(find.text('Could not load'), findsOneWidget);
  });

  testWidgets('"Rest are good" marks the unchecked lines', (tester) async {
    final env = await setUpEnv();
    await _pumpWithDetail(tester, 12, const ChecklistRoomScreen(roomName: 'Kitchen'));
    await tester.tap(find.text('Rest are good'));
    await settle(tester);
    expectNoErrors(tester);
    expect(env.checklist.calls, contains('markOk:506'));
  });

  // ---------------------------------------------------------- item sheet --

  for (final (label, size) in sizes) {
    testWidgets('move-out item: Damaged → Good, Save & next ($label)', (tester) async {
      final env = await setUpEnv();
      await _pumpWithDetail(tester, 12, const ChecklistRoomScreen(roomName: 'Kitchen'), size: size);
      await tester.tap(find.text('Cooker hood'));
      await settle(tester);
      expectNoErrors(tester);
      expect(find.text('Damage cost'.toUpperCase()), findsOneWidget);

      await tester.tap(find.text('Good'));
      await tester.pump();
      expect(find.text('Damage cost'.toUpperCase()), findsNothing);
      await tester.tap(find.text('Save & next'));
      await settle(tester);
      expectNoErrors(tester);
      final change = env.checklist.lineChanges.single;
      expect(change['line'], 505);
      expect(change['status'], ChecklistLineStatus.ok);
      expect(change['damage_cost'], 0);
      // Moved on to the next item in the room.
      expect(find.text('Gas hob 4-burner'), findsWidgets);
    });

    testWidgets('move-in item: Present toggle ($label)', (tester) async {
      final env = await setUpEnv();
      await _pumpWithDetail(tester, 13, const ChecklistRoomScreen(roomName: 'Kitchen'), size: size);
      await tester.tap(find.text('Gas hob 4-burner'));
      await settle(tester);
      expectNoErrors(tester);
      expect(find.text('Good'), findsNothing); // binary on move-in
      await tester.tap(find.text('Present'));
      await tester.pump();
      await tester.tap(find.text('Save & close'));
      await settle(tester);
      expectNoErrors(tester);
      expect(env.checklist.lineChanges.single, {'line': 506, 'status': ChecklistLineStatus.ok});
    });

    testWidgets('lease hand-over item uses the server phase label ($label)', (tester) async {
      await setUpEnv();
      await _pumpWithDetail(tester, 20, const ChecklistRoomScreen(roomName: 'Living Room'), size: size);
      await tester.tap(find.text('Ceiling lights'));
      await settle(tester);
      expectNoErrors(tester);
      expect(find.text('Condition at handover'), findsOneWidget);
    });
  }

  testWidgets('item sheet: arrows save a dirty draft; a failed save stays put', (tester) async {
    final env = await setUpEnv();
    await _pumpWithDetail(tester, 12, const ChecklistRoomScreen(roomName: 'Living Room'));
    await tester.tap(find.text('Split AC unit'));
    await settle(tester);
    await tester.enterText(find.byType(TextField).first, 'Remote missing');
    env.checklist.failWrites = true;
    await tester.tap(find.byIcon(Icons.chevron_right).last);
    await settle(tester);
    expectNoErrors(tester);
    expect(find.text('Could not save'), findsOneWidget);
    expect(find.text('Split AC unit'), findsWidgets);

    env.checklist.failWrites = false;
    await tester.tap(find.byIcon(Icons.chevron_right).last);
    await settle(tester);
    expect(env.checklist.lineChanges.last['comment'], 'Remote missing');
    expect(find.text('Ceiling lights'), findsWidgets);
    expectNoErrors(tester);
  });

  // ------------------------------------------------------- fixture sheet --

  for (final (label, size) in sizes) {
    testWidgets('add a fixture note ($label)', (tester) async {
      final env = await setUpEnv();
      await _pumpWithDetail(tester, 12, const ChecklistRoomScreen(roomName: 'Kitchen'), size: size);
      await tester.scrollUntilVisible(find.text('Add fixture'), 200, scrollable: find.byType(Scrollable).last);
      await tester.tap(find.text('Add fixture').first);
      await settle(tester);
      expectNoErrors(tester);
      await tester.enterText(find.byType(TextField).last, 'Seal the worktop joint');
      await tester.tap(find.text('In Progress').last);
      await tester.pump();
      await tester.tap(find.text('Add fixture').last);
      await settle(tester);
      expectNoErrors(tester);
      expect(env.checklist.calls, contains('addFixture:Kitchen:Seal the worktop joint:in_progress'));
    });

    testWidgets('edit and delete a fixture note ($label)', (tester) async {
      final env = await setUpEnv();
      await _pumpWithDetail(tester, 12, const ChecklistRoomScreen(roomName: 'Kitchen'), size: size);
      await tester.scrollUntilVisible(find.text('Re-grout tiles behind the sink'), 200,
          scrollable: find.byType(Scrollable).last);
      await tester.tap(find.text('Re-grout tiles behind the sink'));
      await settle(tester);
      await tester.tap(find.text('Completed'));
      await tester.pump();
      await tester.tap(find.text('Save'));
      await settle(tester);
      expectNoErrors(tester);
      expect(env.checklist.calls, contains('updateFixtureEntry:91:null:completed'));

      await tester.tap(find.text('Re-grout tiles behind the sink'));
      await settle(tester);
      await tester.tap(find.text('Delete'));
      await settle(tester);
      await tester.tap(find.text('Delete').last); // confirm dialog
      await settle(tester);
      expectNoErrors(tester);
      expect(env.checklist.calls, contains('deleteFixtureEntry:91'));
    });
  }

  testWidgets('resize phone → tablet → phone while the fixture sheet is open, then save', (tester) async {
    final env = await setUpEnv();
    await _pumpWithDetail(tester, 12, const ChecklistRoomScreen(roomName: 'Kitchen'));
    await tester.scrollUntilVisible(find.text('Re-grout tiles behind the sink'), 200,
        scrollable: find.byType(Scrollable).last);
    await tester.tap(find.text('Re-grout tiles behind the sink'));
    await settle(tester);
    for (final size in [tabletSize, phoneSize, wideSize]) {
      tester.view.physicalSize = size;
      await settle(tester, frames: 4);
      expectNoErrors(tester);
    }
    await tester.tap(find.text('Completed'));
    await tester.pump();
    await tester.tap(find.text('Save'));
    await settle(tester);
    expectNoErrors(tester);
    expect(env.checklist.calls, contains('updateFixtureEntry:91:null:completed'));
  });

  testWidgets('resize while the item sheet is open, then Save & next', (tester) async {
    final env = await setUpEnv();
    await _pumpWithDetail(tester, 12, const ChecklistRoomScreen(roomName: 'Kitchen'), size: tabletSize);
    await tester.tap(find.text('Refrigerator'));
    await settle(tester);
    tester.view.physicalSize = phoneSize;
    await settle(tester, frames: 4);
    tester.view.physicalSize = tabletSize;
    await settle(tester, frames: 4);
    await tester.tap(find.text('Damaged').last);
    await tester.pump();
    await tester.tap(find.text('Save & next'));
    await settle(tester);
    expectNoErrors(tester);
    expect(env.checklist.lineChanges.single['status'], ChecklistLineStatus.notOk);
  });

  // ------------------------------------------------------------ hand-over --

  for (final (label, size) in sizes) {
    for (final signed in [0, 1, 2, 3]) {
      testWidgets('hand-over with $signed of 3 signatures ($label)', (tester) async {
        await setUpEnv(checklist: FakeChecklistRepository(details: {12: rentalMoveOut(signed: signed)}));
        await _pumpWithDetail(tester, 12, const ChecklistHandoverScreen(), size: size);
        expectNoErrors(tester);
        expect(find.text('Seal hand-over'), findsOneWidget);
        expect(find.text('All three signatures are needed to seal.'), signed == 3 ? findsNothing : findsOneWidget);
      });
    }

    testWidgets('sealed hand-over is read-only ($label)', (tester) async {
      await setUpEnv(checklist: FakeChecklistRepository(details: {12: rentalMoveOut(signed: 3, sealed: true)}));
      await _pumpWithDetail(tester, 12, const ChecklistHandoverScreen(), size: size);
      expectNoErrors(tester);
      expect(find.text('Hand-over sealed'), findsOneWidget);
      expect(find.text('Sign'), findsNothing);
    });

    testWidgets('lease hand-over ($label)', (tester) async {
      await setUpEnv();
      await _pumpWithDetail(tester, 20, const ChecklistHandoverScreen(), size: size);
      expectNoErrors(tester);
      expect(find.text('Actual handover date'.toUpperCase()), findsOneWidget);
    });
  }

  testWidgets('seal a fully signed hand-over returns to the Checklists tab', (tester) async {
    final env = await setUpEnv(checklist: FakeChecklistRepository(details: {12: rentalMoveOut(signed: 3)}));
    await _pumpWithDetail(tester, 12, const ChecklistHandoverScreen());
    await tester.scrollUntilVisible(find.text('Seal hand-over'), 200, scrollable: find.byType(Scrollable).first);
    await tester.tap(find.text('Seal hand-over'));
    await settle(tester);
    expectNoErrors(tester);
    expect(env.checklist.calls.any((c) => c.startsWith('seal:')), isTrue);
    expect(find.text('route:/home'), findsOneWidget);
  });

  testWidgets('PDF button: a failed download shows a toast', (tester) async {
    final env = await setUpEnv();
    env.checklist.failWrites = true;
    await _pumpWithDetail(tester, 12, const ChecklistHandoverScreen());
    await tester.tap(find.byType(ChecklistPdfButton));
    await settle(tester);
    expectNoErrors(tester);
    expect(find.text('Not found'), findsOneWidget);
  });

  testWidgets('PDF preview screen renders', (tester) async {
    await setUpEnv();
    await pumpScreen(
      tester,
      ChecklistPdfPreviewScreen(bytes: Uint8List.fromList('%PDF-1.4\n%%EOF'.codeUnits), fileName: 'x.pdf'),
      size: tabletSize,
    );
    expectNoErrors(tester);
    expect(find.text('Hand-over PDF'), findsOneWidget);
  });

  // ------------------------------------------------ signature / viewer --

  for (final (label, size) in sizes) {
    testWidgets('signature capture: ink enables Save, Clear disables it ($label)', (tester) async {
      await setUpEnv();
      SignatureResult? result;
      await pumpScreen(
        tester,
        Builder(
          builder: (context) => Scaffold(
            body: Center(
              child: TextButton(
                onPressed: () async => result = await showSignatureCapture(context, title: 'Sign as Lessee', initialName: 'Leena'),
                child: const Text('open pad'),
              ),
            ),
          ),
        ),
        size: size,
      );
      await tester.tap(find.text('open pad'));
      await settle(tester);
      expectNoErrors(tester);

      final pad = signaturePad;
      await tester.drag(pad, const Offset(120, 30));
      await tester.pump();
      await tester.tap(find.text('Clear'));
      await tester.pump();
      await tester.drag(pad, const Offset(140, -20));
      await tester.pump();

      await tester.runAsync(() async {
        await tester.tap(find.text('Save signature'));
        await Future<void>.delayed(const Duration(milliseconds: 300));
      });
      await settle(tester);
      expectNoErrors(tester);
      expect(result?.name, 'Leena');
      expect(result?.dataUrl, startsWith('data:image/png;base64,'));
    });

    testWidgets('photo viewer pages and closes ($label)', (tester) async {
      await setUpEnv();
      await pumpScreen(
        tester,
        Builder(
          builder: (context) => Scaffold(
            body: Center(
              child: TextButton(
                onPressed: () => openChecklistPhotoViewer(context, const [
                  ChecklistViewerPhoto(path: '/storage/a.jpg', caption: 'Move-in · Fridge'),
                  ChecklistViewerPhoto(path: '/storage/b.jpg', caption: 'Move-out · Fridge'),
                ]),
                child: const Text('open viewer'),
              ),
            ),
          ),
        ),
        size: size,
      );
      await tester.tap(find.text('open viewer'));
      await settle(tester);
      expectNoErrors(tester);
      expect(find.text('1 / 2'), findsOneWidget);
      await tester.fling(find.byType(PageView), const Offset(-400, 0), 1500);
      await settle(tester);
      expect(find.text('2 / 2'), findsOneWidget);
      await tester.tap(find.byIcon(Icons.close));
      await settle(tester);
      expectNoErrors(tester);
      expect(find.text('open viewer'), findsOneWidget);
    });
  }
}
