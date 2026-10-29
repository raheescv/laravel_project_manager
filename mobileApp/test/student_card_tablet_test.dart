import 'package:flutter/material.dart';
import 'package:flutter_bloc/flutter_bloc.dart';
import 'package:flutter_test/flutter_test.dart';
import 'package:go_router/go_router.dart';
import 'package:google_fonts/google_fonts.dart';

import 'package:invo/features/sale/screens/v3/new_sale_screen.dart';
import 'package:invo/features/student_card/domain/models/student_card.dart';
import 'package:invo/features/student_card/domain/repository/student_card_repository.dart';
import 'package:invo/features/student_card/logic/student_card_cubit/student_card_cubit.dart';
import 'package:invo/features/student_card/screens/link_card_screen.dart';
import 'package:invo/features/student_card/widgets/tap_card_sheet.dart';
import 'package:invo/shared/domain/constants/global_variables.dart';
import 'package:invo/shared/domain/constants/mobile_permissions.dart';
import 'package:invo/shared/domain/models/index.dart';
import 'package:invo/shared/logic/connectivity_cubit/connectivity_cubit.dart';
import 'package:invo/shared/utils/components/theme/index.dart';
import 'package:invo/shared/widgets/offline_banner.dart';

import 'support/test_harness.dart';

/// The canteen till is a 1200×1920 @ 280dpi Android tablet (I24T02): 1097×686
/// logical in landscape, where it lives, and 686×1097 in portrait. The student
/// card flow must fit both — and on a tablet the offline pill floats at the
/// bottom centre, so a sheet's buttons have to sit clear of it.
const _till = <String, Size>{
  'till landscape': Size(1097, 686),
  'till portrait': Size(686, 1097),
};

StudentCard _student(int id, String name, {bool preOrder = false}) => StudentCard.fromJson({
      'account_id': id,
      'name': name,
      'admission_no': 'NR$id',
      'grade': 'Grade 5',
      'section': 'B',
      'status': 'active',
      'card_uid': '04A21B${id}C',
      'card_status': 'active',
      'balance': 379,
      'overdraft_limit': 30,
      'available': 409,
      'card_method_id': 9,
      if (preOrder)
        'pre_order': {
          'id': 1,
          'source': 'daily',
          'items': [
            {'product': {'id': 1, 'name': 'Water', 'price': 1}, 'quantity': 1},
          ],
          'total': 1,
        },
    });

class _FakeStudentCards implements StudentCardRepository {
  final students = [for (var i = 1; i <= 11; i++) _student(i, 'Student number $i with a long name', preOrder: i == 1)];

  @override
  Future<StudentCard> lookup(String uid) async => students.first;

  @override
  Future<List<StudentCard>> search(String query) async => students;

  @override
  Future<StudentCard> link(int accountId, String uid) async => students.first;
}

void main() {
  setUpAll(() => GoogleFonts.config.allowRuntimeFetching = false);

  /// The harness's providers behind a router, with the app's offline banner
  /// around the navigator — as app.dart has it — so sheets open under the pill.
  Future<TestHarness> pump(WidgetTester tester, Size size, Widget home, {bool offline = false}) async {
    final d = TestHarness();
    await d.init(admin: true);
    addTearDown(d.dispose);
    await d.storage.setSchoolEnabled(true);
    // The harness admin holds the menu's slugs; the till's card button also
    // needs the Link Card one.
    d.authCubit.seedSession(ApiUser(
      id: '14',
      name: 'Maya Chen',
      code: 'EMP-014',
      email: 'maya@astra.co',
      mobile: '',
      designation: 'Canteen',
      role: 'admin',
      isAdmin: true,
      branchId: '3',
      daySessionStatus: 'open',
      daySessionDate: '2026-06-14',
      permissions: [...mobilePermissions.map((m) => m.slug), PermissionSlug.studentCardAssign],
    ));
    final connectivity = ConnectivityCubit();
    addTearDown(connectivity.close);
    serviceLocator
      ..registerLazySingleton<StudentCardRepository>(_FakeStudentCards.new)
      ..registerFactory<StudentCardCubit>(() => StudentCardCubit(serviceLocator()))
      ..registerSingleton<ConnectivityCubit>(connectivity);
    connectivity.reportOutcome(reachable: !offline);

    tester.view.physicalSize = size;
    tester.view.devicePixelRatio = 1.0;
    addTearDown(tester.view.resetPhysicalSize);
    addTearDown(tester.view.resetDevicePixelRatio);
    await tester.pumpWidget(MultiBlocProvider(
      providers: d.providers(),
      child: MaterialApp.router(
        theme: buildAstraTheme(AstraPresets.emeraldGold),
        routerConfig: GoRouter(routes: [GoRoute(path: '/', builder: (_, __) => home)]),
        builder: (context, child) => OfflineBanner(child: child!),
      ),
    ));
    await tester.pump();
    await tester.pump(const Duration(milliseconds: 500));
    return d;
  }

  /// [label]'s button is fully on screen and, when the pill is showing, above it.
  void expectClear(WidgetTester tester, Size size, String label) {
    final button = tester.getRect(find.text(label));
    expect(button.bottom, lessThanOrEqualTo(size.height), reason: '$label is cut off');
    final pill = find.textContaining('no catalog');
    if (pill.evaluate().isNotEmpty) {
      expect(button.bottom, lessThan(tester.getRect(pill).top), reason: 'the offline pill covers $label');
    }
  }

  for (final vp in _till.entries) {
    testWidgets('New Sale opens the Client sheet clear of the offline pill on the ${vp.key}', (tester) async {
      await pump(tester, vp.value, const NewSaleScreen(), offline: true);

      expect(tester.takeException(), isNull);
      expect(find.text('Client Details?'), findsOneWidget);
      expect(find.text('Tap student card'), findsOneWidget);
      expectClear(tester, vp.value, 'Set client');
    });

    testWidgets('a card read on the Client sheet opens straight on the student on the ${vp.key}', (tester) async {
      await pump(tester, vp.value, Builder(builder: (context) {
        return Scaffold(
          body: Center(
            child: TextButton(onPressed: () => showTapCardSheet(context, uid: '04A21B1C', onBack: () {}), child: const Text('open')),
          ),
        );
      }), offline: true);
      await tester.tap(find.text('open'));
      await tester.pump();
      await tester.pump(const Duration(milliseconds: 500));

      expect(tester.takeException(), isNull);
      expect(find.textContaining('Parent pre-ordered'), findsOneWidget);
      expect(find.byTooltip('Back'), findsOneWidget);
      expectClear(tester, vp.value, 'Use this card');
    });

    testWidgets('the tap sheet offers admission number entry and a way back on the ${vp.key}', (tester) async {
      var wentBack = false;
      await pump(tester, vp.value, Builder(builder: (context) {
        return Scaffold(
          body: Center(
            child: TextButton(onPressed: () => showTapCardSheet(context, onBack: () => wentBack = true), child: const Text('open')),
          ),
        );
      }), offline: true);
      await tester.tap(find.text('open'));
      await tester.pump();
      await tester.pump(const Duration(milliseconds: 500));

      expect(tester.takeException(), isNull);
      expect(find.text('OR ENTER THE CARD OR ADMISSION NUMBER'), findsOneWidget);
      expectClear(tester, vp.value, 'Go');

      await tester.tap(find.byTooltip('Back'));
      await tester.pump();
      await tester.pump(const Duration(milliseconds: 500));
      expect(wentBack, isTrue);
      expect(find.text('Tap the card'), findsNothing);
    });

    testWidgets('Link Card lays out every student on the ${vp.key}', (tester) async {
      await pump(tester, vp.value, const LinkCardScreen());

      expect(tester.takeException(), isNull);
      expect(find.text('Link Student Card'), findsOneWidget);
      expect(find.text('Student number 1 with a long name'), findsOneWidget);
    });
  }
}
