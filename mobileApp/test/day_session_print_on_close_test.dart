import 'package:flutter_test/flutter_test.dart';
import 'package:invo/features/admin/logic/day_session_cubit/day_session_cubit.dart';
import 'package:invo/shared/domain/models/index.dart';

import 'support/test_harness.dart';

/// The close sheet's "Print the Sale Bill Report" toggle: remembered per user
/// on this device, so each cashier on a shared till keeps their own answer.
void main() {
  TestWidgetsFlutterBinding.ensureInitialized();

  late TestHarness harness;
  late DaySessionCubit cubit;

  setUp(() async {
    harness = TestHarness();
    await harness.init();
    cubit = DaySessionCubit();
  });

  tearDown(() async {
    await cubit.close();
    await harness.dispose();
  });

  void signInAs(String id) => harness.authCubit.seedSession(ApiUser(
      id: id, name: 'Sam Lee', code: 'EMP-$id', email: '', mobile: '', isAdmin: false,
      designation: '', role: 'staff', branchId: '3', daySessionStatus: 'open', daySessionDate: ''));

  test('prints until the user first taps the toggle', () {
    expect(cubit.printOnClose, isTrue);
  });

  test('is saved for the signed-in user, not the whole till', () async {
    await cubit.setPrintOnClose(false);
    expect(cubit.printOnClose, isFalse);
    expect(harness.storage.daySessionPrintOnCloseFor('14'), isFalse);
    expect(harness.storage.daySessionPrintOnCloseFor('22'), isNull);

    signInAs('22');
    expect(cubit.printOnClose, isTrue);

    await cubit.setPrintOnClose(false);
    await cubit.setPrintOnClose(true);
    expect(cubit.printOnClose, isTrue);
    expect(harness.storage.daySessionPrintOnCloseFor('14'), isFalse);
  });

  test('a device-wide answer from an older build applies until the user taps', () async {
    await harness.storage.setDaySessionPrintOnCloseFor(null, false);
    expect(cubit.printOnClose, isFalse);

    await cubit.setPrintOnClose(true);
    expect(cubit.printOnClose, isTrue);
    expect(harness.storage.daySessionPrintOnCloseFor(null), isFalse);
  });
}
