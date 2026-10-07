// One regression test per finding from the round-5 review (numbers match the
// review list). Each fails on the code as it was before its fix.
import 'dart:convert';

import 'package:flutter/material.dart';
import 'package:flutter_bloc/flutter_bloc.dart';
import 'package:flutter_test/flutter_test.dart';
import 'package:intl/intl.dart';

import 'package:invo/features/auth/screens/v3/login_screen.dart';
import 'package:invo/features/checklist/logic/checklist_inbox_cubit/checklist_inbox_cubit.dart';
import 'package:invo/features/checklist/screens/v3/checklist_handover_screen.dart';
import 'package:invo/features/checklist/screens/v3/checklist_inbox_screen.dart';
import 'package:invo/features/checklist/screens/v3/checklist_room_screen.dart';
import 'package:invo/features/checklist/screens/v3/checklist_rooms_screen.dart';
import 'package:invo/features/checklist/logic/checklist_detail_cubit/checklist_detail_cubit.dart';
import 'package:invo/features/profile/screens/v3/change_pin_screen.dart';
import 'package:invo/features/technician/logic/complaint_detail_cubit/complaint_detail_cubit.dart';
import 'package:invo/features/technician/logic/complaints_cubit/complaints_cubit.dart';
import 'package:invo/features/technician/logic/dashboard_cubit/dashboard_cubit.dart';
import 'package:invo/features/technician/screens/v3/complaint_detail_screen.dart';
import 'package:invo/features/technician/screens/v3/complaints_list_screen.dart';
import 'package:invo/features/technician/widgets/v3/edit_supply_item_sheet.dart';
import 'package:invo/shared/domain/constants/global_variables.dart';
import 'package:invo/shared/domain/helpers/formatters.dart';
import 'package:invo/shared/logic/theme_cubit/theme_cubit.dart';
import 'package:invo/shared/utils/components/theme/index.dart';
import 'package:invo/shared/utils/local_storage/local_storage_service.dart';

import 'support/fakes.dart';
import 'support/harness.dart';
import 'support/sample_data.dart';

BlocProvider<ChecklistDetailCubit> _detail(int id) =>
    BlocProvider<ChecklistDetailCubit>(create: (_) => serviceLocator<ChecklistDetailCubit>()..load(id));

Future<void> _typePin(WidgetTester tester, String pin) async {
  for (final d in pin.split('')) {
    await tester.tap(find.text(d).last);
    await tester.pump();
  }
}

void main() {
  // ------------------------------------------------------------------ #1 --
  group('#1 MPIN length', () {
    testWidgets('a remembered 6-digit PIN does not auto-submit at 4, and shows 6 dots', (tester) async {
      final env = await setUpEnv(signedIn: false);
      await serviceLocator<LocalStorageService>().setPinLength(6);
      await pumpScreen(tester, const LoginScreen());
      await _typePin(tester, '1234');
      await settle(tester, frames: 3);
      expect(env.auth.calls, isEmpty);
      await _typePin(tester, '56');
      await settle(tester);
      expect(env.auth.calls, ['login:123456']);
      expectNoErrors(tester);
    });

    testWidgets('✓ submits a 5-digit PIN and remembers its length', (tester) async {
      final env = await setUpEnv(signedIn: false);
      await pumpScreen(tester, const LoginScreen());
      await _typePin(tester, '1234'); // remembered default 4 → auto-submit
      await settle(tester);
      expect(env.auth.calls.single, 'login:1234');

      env.authCubit.status = env.authCubit.status; // still signed in; sign out to retry
      await env.authCubit.logout();
      await pumpScreen(tester, const LoginScreen());
      await serviceLocator<LocalStorageService>().setPinLength(6);
      await pumpScreen(tester, const LoginScreen());
      await _typePin(tester, '24680');
      await tester.tap(find.byIcon(Icons.check_rounded));
      await settle(tester);
      expect(env.auth.calls.last, 'login:24680');
      expect(serviceLocator<LocalStorageService>().pinLength, 5);
    });

    testWidgets('Change MPIN stores the new length', (tester) async {
      await setUpEnv();
      await pumpScreen(tester, const ChangePinScreen(), pushed: true);
      final fields = find.byType(TextField);
      await tester.enterText(fields.at(0), '1111');
      await tester.enterText(fields.at(1), '246813');
      await tester.enterText(fields.at(2), '246813');
      await tester.tap(find.text('Update PIN'));
      await settle(tester);
      expect(serviceLocator<LocalStorageService>().pinLength, 6);
    });
  });

  // ------------------------------------------------------------------ #2 --
  testWidgets('#2 sign-out wipes the dashboard and My Jobs', (tester) async {
    final env = await setUpEnv();
    await pumpApp(tester);
    await tester.tap(find.text('My Jobs').last);
    await settle(tester);
    final ctx = tester.element(find.byType(ComplaintsListScreen));
    final complaints = ctx.read<ComplaintsCubit>();
    final dashboard = ctx.read<TechnicianDashboardCubit>();
    expect(complaints.rows, isNotEmpty);
    expect(dashboard.data, isNotNull);
    complaints.setSearch('leak');
    await settle(tester);

    await env.authCubit.logout();
    await settle(tester);
    expect(complaints.rows, isEmpty);
    expect(complaints.search, isEmpty);
    expect(dashboard.data, isNull);
    expectNoErrors(tester);
  });

  // ------------------------------------------------------------------ #3 --
  test('#3 My Jobs defaults to all dates', () {
    final cubit = ComplaintsCubit();
    expect(cubit.datePreset, 'all');
    addTearDown(cubit.close);
  });

  testWidgets('#3 the first My Jobs query is not date-limited', (tester) async {
    final env = await setUpEnv();
    await pumpScreen(tester, const ComplaintsListScreen());
    expect(env.tech.complaintQueries.first['from'], isNull);
    expect(env.tech.complaintQueries.first['to'], isNull);
  });

  // ------------------------------------------------------------------ #4 --
  testWidgets('#4 shell tabs keep their state across chrome changes and the 600pt line', (tester) async {
    await setUpEnv();
    await pumpApp(tester, size: tabletSize);
    await tester.tap(find.text('My Jobs').last);
    await settle(tester);
    final before = tester.state(find.byType(ComplaintsListScreen));

    await serviceLocator<ThemeCubit>().setChrome(AstraChrome.docked);
    await settle(tester, frames: 4);
    expect(tester.state(find.byType(ComplaintsListScreen)), same(before));

    tester.view.physicalSize = phoneSize;
    await settle(tester, frames: 4);
    expect(tester.state(find.byType(ComplaintsListScreen)), same(before));
    expectNoErrors(tester);
  });

  // ------------------------------------------------------------------ #5 --
  testWidgets('#5 the My Jobs detail pane keeps a remark draft through a rotation', (tester) async {
    await setUpEnv();
    await pumpScreen(tester, const ComplaintsListScreen(), size: wideSize);
    final remark = find.byType(TextField).last;
    await tester.scrollUntilVisible(find.text('TECHNICIAN REMARKS'), 200, scrollable: find.byType(Scrollable).last);
    await tester.enterText(find.byType(TextField).at(1), 'Capacitor ordered');
    expect(remark, findsOneWidget);
    final pane = tester.state(find.byType(ComplaintDetailScreen));

    tester.view.physicalSize = const Size(700, 1000); // single-pane tablet
    await settle(tester, frames: 4);
    tester.view.physicalSize = wideSize;
    await settle(tester, frames: 4);
    expect(tester.state(find.byType(ComplaintDetailScreen)), same(pane));
    expect(find.text('Capacitor ordered'), findsOneWidget);
    expectNoErrors(tester);
  });

  // ------------------------------------------------------------------ #6 --
  testWidgets('#6 product search is debounced and drops stale responses', (tester) async {
    final env = await setUpEnv();
    final cubit = ComplaintDetailCubit(3);
    addTearDown(cubit.close);

    cubit.searchProducts('a');
    await tester.pump(const Duration(milliseconds: 100));
    cubit.searchProducts('ab');
    await tester.pump(const Duration(milliseconds: 400));
    expect(env.tech.productSearches, ['ab']); // 'a' never fired

    env.tech.productDelays['slow'] = const Duration(milliseconds: 600);
    cubit.loadProducts('slow');
    await tester.pump(const Duration(milliseconds: 50));
    cubit.loadProducts('fast');
    await tester.pump(const Duration(milliseconds: 50));
    expect(cubit.products.single.name, 'Result for fast');
    expect(cubit.productsLoading, isFalse);
    await tester.pump(const Duration(milliseconds: 700)); // the slow one lands late
    expect(cubit.products.single.name, 'Result for fast');
    expect(cubit.productsLoading, isFalse);
  });

  // ------------------------------------------------------------------ #7 --
  group('#7 biometrics', () {
    testWidgets('logout keeps the saved credential; a PIN change rewrites it', (tester) async {
      final env = await setUpEnv(signedIn: false);
      await env.authCubit.login('1234');
      final key = secureStore.keys.firstWhere((k) => k.contains('biometric'));
      expect(jsonDecode(secureStore[key]!), {'mode': 'pin', 'pin': '1234'});

      await env.authCubit.logout();
      expect(secureStore[key], isNotNull);

      await env.authCubit.login('1234');
      await env.authCubit.applyChangedPin('97531');
      expect(jsonDecode(secureStore[key]!), {'mode': 'pin', 'pin': '97531'});
      await env.authCubit.applyChangedPassword('ignored'); // PIN-mode: untouched
      expect(jsonDecode(secureStore[key]!)['pin'], '97531');
    });

    testWidgets('a password change rewrites a credential-mode enrolment', (tester) async {
      final env = await setUpEnv(signedIn: false);
      await env.authCubit.loginWithCredential('rahul', 'old-pass');
      await env.authCubit.applyChangedPassword('new-pass-1');
      final key = secureStore.keys.firstWhere((k) => k.contains('biometric'));
      expect(jsonDecode(secureStore[key]!), {'mode': 'cred', 'username': 'rahul', 'password': 'new-pass-1'});
    });
  });

  // ------------------------------------------------------------------ #8 --
  testWidgets('#8 "To sign" counts unsealed hand-overs still missing signatures', (tester) async {
    final env = await setUpEnv();
    await pumpScreen(tester, const ChecklistInboxScreen());
    final expected = env.checklist.jobsList.where((j) => !j.sealed && j.signaturesDone < j.signaturesRequired).length;
    expect(expected, greaterThan(0));
    await tester.tap(find.text('To sign'));
    await settle(tester);
    final inbox = serviceLocator<ChecklistInboxCubit>();
    expect(inbox.state.visibleJobs.length, expected);
    expect(inbox.state.visibleJobs.every((j) => !j.readyToSeal && !j.sealed), isTrue);
  });

  // ------------------------------------------------------------------ #9 --
  testWidgets('#9 dismissing the item sheet keeps a dirty edit', (tester) async {
    final env = await setUpEnv();
    await pumpScreen(tester, const ChecklistRoomScreen(roomName: 'Kitchen'), providers: [_detail(12)]);
    await tester.tap(find.text('Gas hob 4-burner'));
    await settle(tester);
    await tester.tap(find.text('Damaged').last);
    await tester.pump();
    await tester.tapAt(const Offset(20, 40)); // tap the barrier
    await settle(tester);
    expectNoErrors(tester);
    expect(env.checklist.lineChanges.single, containsPair('status', 'not_ok'));
  });

  // ----------------------------------------------------------------- #10 --
  testWidgets('#10 owner acceptance is saved after the window crosses the wide line mid-signature', (tester) async {
    final env = await setUpEnv();
    await pumpScreen(tester, const ChecklistRoomScreen(roomName: 'Living Room'), size: wideSize, providers: [_detail(12)]);
    final scroll = tester.state(find.descendant(of: find.byType(CustomScrollView).first, matching: find.byType(Scrollable)).first);
    await tester.tap(find.text('Owner acceptance'));
    await settle(tester);
    await tester.enterText(find.byType(TextField).first, 'Mr. Khalid');

    tester.view.physicalSize = tabletSize; // fixture section moves into the item scroll
    await settle(tester, frames: 4);
    await tester.drag(signaturePad, const Offset(120, 20));
    await tester.pump();
    await tester.runAsync(() async {
      await tester.tap(find.text('Save signature'));
      await Future<void>.delayed(const Duration(milliseconds: 300));
    });
    await settle(tester);
    expectNoErrors(tester);
    expect(env.checklist.calls, contains('signFixtureArea:8'));
    expect(tester.state(find.descendant(of: find.byType(CustomScrollView).first, matching: find.byType(Scrollable)).first), same(scroll));
  });

  // ----------------------------------------------------------------- #11 --
  testWidgets('#11 completing a job also refreshes the dashboard', (tester) async {
    final env = await setUpEnv();
    await pumpScreen(
      tester,
      BlocProvider(create: (_) => ComplaintDetailCubit(3)..load(), child: const ComplaintDetailScreen()),
      size: tabletSize, // the tablet body builds every card (no lazy list)
    );
    final before = env.tech.dashboardCalls;
    await tester.enterText(find.byType(TextField).first, 'Replaced the capacitor');
    await tester.tap(find.text('Complete'));
    await settle(tester);
    await tester.tap(find.text('Complete').last); // confirm
    await settle(tester);
    expect(env.tech.calls, contains('complete:3'));
    expect(env.tech.dashboardCalls, greaterThan(before));
  });

  // ----------------------------------------------------------------- #12 --
  group('#12 seal', () {
    testWidgets('empty remarks are still sent (so they can be cleared)', (tester) async {
      final env = await setUpEnv(checklist: FakeChecklistRepository(details: {12: rentalMoveOut(signed: 3)}));
      await pumpScreen(tester, const ChecklistHandoverScreen(), providers: [_detail(12)]);
      await tester.scrollUntilVisible(find.text('Seal hand-over'), 200, scrollable: find.byType(Scrollable).first);
      await tester.tap(find.text('Seal hand-over'));
      await settle(tester);
      expect(env.checklist.calls.singleWhere((c) => c.startsWith('seal:')), endsWith(':'));
    });

    testWidgets('the actual-date picker ends today', (tester) async {
      await setUpEnv();
      await pumpScreen(tester, const ChecklistHandoverScreen(), providers: [_detail(12)]);
      await tester.tap(find.text('ACTUAL MOVE-OUT DATE'));
      await settle(tester);
      final picker = tester.widget<DatePickerDialog>(find.byType(DatePickerDialog));
      final now = DateTime.now();
      expect(picker.lastDate, DateTime(now.year, now.month, now.day));
    });
  });

  // ----------------------------------------------------------------- #13 --
  testWidgets('#13 the item comment is capped at 255 characters', (tester) async {
    await setUpEnv();
    await pumpScreen(tester, const ChecklistRoomScreen(roomName: 'Kitchen'), providers: [_detail(12)]);
    await tester.tap(find.text('Cooker hood'));
    await settle(tester);
    final field = find.byType(TextField).first;
    await tester.enterText(field, 'x' * 300);
    expect(tester.widget<TextField>(field).controller!.text.length, 255);
  });

  // ----------------------------------------------------------------- #14 --
  testWidgets('#14 the add-item sheet pre-selects the first store once branches arrive', (tester) async {
    final env = await setUpEnv();
    env.tech.branchesDelay = const Duration(milliseconds: 300);
    await pumpScreen(
      tester,
      BlocProvider(create: (_) => ComplaintDetailCubit(3)..load(), child: const ComplaintDetailScreen()),
      size: tabletSize, // the tablet body builds every card (no lazy list)
    );
    await tester.tap(find.text('Add').first);
    await tester.pump(const Duration(milliseconds: 100));
    expect(find.text('Select a store'), findsOneWidget);
    await settle(tester);
    expect(find.text('Main Store'), findsWidgets);
    expectNoErrors(tester);
  });

  // ----------------------------------------------------------------- #15 --
  group('#15 stale responses and double taps', () {
    testWidgets('an older dashboard response cannot overwrite a newer one', (tester) async {
      final env = await setUpEnv();
      final cubit = TechnicianDashboardCubit();
      addTearDown(cubit.close);
      env.tech.dashboardDelays.addAll([const Duration(milliseconds: 500), Duration.zero]);
      cubit.load(); // slow
      await tester.pump(const Duration(milliseconds: 50));
      cubit.reset(); // e.g. sign-out
      await tester.pump(const Duration(milliseconds: 600));
      expect(cubit.data, isNull);
    });

    testWidgets('a double tap on an inbox row opens the hand-over once', (tester) async {
      final env = await setUpEnv();
      await pumpApp(tester);
      await tester.tap(find.text('Checklists').last);
      await settle(tester);
      final row = find.textContaining('Unit 305').first;
      await tester.tap(row);
      await tester.tap(row, warnIfMissed: false);
      await settle(tester);
      expect(env.checklist.calls.where((c) => c == 'detail:12').length, 1);
      expect(find.byType(ChecklistRoomsScreen), findsOneWidget);
    });
  });

  // ----------------------------------------------------------------- #16 --
  testWidgets('#16 the edit-item sheet saves through the cubit and disposes cleanly', (tester) async {
    final env = await setUpEnv();
    await pumpScreen(
      tester,
      BlocProvider(create: (_) => ComplaintDetailCubit(3)..load(), child: const ComplaintDetailScreen()),
      size: tabletSize, // the tablet body builds every card (no lazy list)
    );
    await tester.tap(find.text('Edit').first);
    await settle(tester);
    await tester.enterText(find.descendant(of: find.byType(EditSupplyItemSheet), matching: find.byType(TextField)).first, '3');
    await tester.tap(find.text('Save changes'));
    await settle(tester);
    expectNoErrors(tester);
    expect(env.tech.calls.any((c) => c.startsWith('updateSupplyItem:1:New:3.0')), isTrue);
  });

  // ----------------------------------------------------------------- #17 --
  group('#17 offset timestamps', () {
    test('parse to local time', () {
      final d = Dates.parseLocal('2026-10-08T10:15:00+03:00')!;
      expect(d.isUtc, isFalse);
      expect(d, DateTime.utc(2026, 10, 8, 7, 15).toLocal());
      expect(Dates.humanDateTime('2026-10-08T10:15:00+03:00'),
          DateFormat('d MMM yyyy · h:mm a').format(DateTime.utc(2026, 10, 8, 7, 15).toLocal()));
      expect(Dates.human('2026-10-08'), '8 Oct 2026'); // plain dates untouched
    });

    testWidgets('signature times render in local time', (tester) async {
      await setUpEnv();
      await pumpScreen(tester, const ChecklistHandoverScreen(), providers: [_detail(12)]);
      final local = DateTime.parse(rentalMoveOut().signatures.first.signedAt!).toLocal();
      expect(find.text(Dates.time(local)), findsOneWidget);
    });
  });
}
