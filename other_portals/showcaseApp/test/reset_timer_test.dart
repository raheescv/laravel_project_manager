import 'package:flutter/material.dart';
import 'package:flutter_test/flutter_test.dart';
import 'package:shared_preferences/shared_preferences.dart';
import 'package:showcase/shared/domain/constants/global_variables.dart';
import 'package:showcase/shared/logic/theme_cubit/theme_cubit.dart';
import 'package:showcase/shared/utils/local_storage/local_storage_service.dart';
import 'package:showcase/shared/widgets/chrome/idle_reset.dart';

/// How long the panel waits before it belongs to the next customer is the
/// shop's call, not ours — a tablet by a till and one on a shelf want very
/// different numbers, which is why it is typed rather than picked.
void main() {
  Future<ThemeCubit> cubit({int? saved}) async {
    SharedPreferences.setMockInitialValues(
        saved == null ? {} : {'idle_minutes': saved});
    if (serviceLocator.isRegistered<LocalStorageService>()) {
      await serviceLocator.reset();
    }
    serviceLocator
        .registerSingleton<LocalStorageService>(await LocalStorageService.create());
    return ThemeCubit();
  }

  tearDown(() => serviceLocator.reset());

  test('an untouched tablet waits ten minutes', () async {
    expect((await cubit()).state.idleMinutes, 10);
  });

  test('a typed wait is kept across a restart', () async {
    await (await cubit()).setIdleMinutes(25);
    // A second cubit over the same store is what a relaunch looks like.
    expect((await cubit(saved: 25)).state.idleMinutes, 25);
  });

  test('a number outside the range is pulled to the nearest end', () async {
    final c = await cubit();
    await c.setIdleMinutes(0);
    expect(c.state.idleMinutes, ThemeCubit.minIdleMinutes,
        reason: 'a zero-minute wait resets the panel out from under someone');
    await c.setIdleMinutes(9999);
    expect(c.state.idleMinutes, ThemeCubit.maxIdleMinutes);
  });

  testWidgets('the panel resets on the wait it was given, not before',
      (tester) async {
    var fired = 0;
    await tester.pumpWidget(MaterialApp(
      home: IdleReset(
        after: const Duration(minutes: 3),
        onIdle: () => fired++,
        child: const ColoredBox(color: Color(0xFFFFFFFF), child: SizedBox.expand()),
      ),
    ));

    await tester.pump(const Duration(minutes: 2, seconds: 59));
    expect(fired, 0);
    await tester.pump(const Duration(seconds: 2));
    expect(fired, 1);
  });

  testWidgets('a touch puts the whole wait back', (tester) async {
    var fired = 0;
    await tester.pumpWidget(MaterialApp(
      home: IdleReset(
        after: const Duration(minutes: 3),
        onIdle: () => fired++,
        child: const ColoredBox(color: Color(0xFFFFFFFF), child: SizedBox.expand()),
      ),
    ));

    await tester.pump(const Duration(minutes: 2, seconds: 50));
    await tester.tapAt(const Offset(200, 300));
    await tester.pump(const Duration(minutes: 2, seconds: 50));
    expect(fired, 0, reason: 'the tap restarts the clock, it does not extend it');
    await tester.pump(const Duration(seconds: 20));
    expect(fired, 1);
  });

  testWidgets('changing the setting re-arms the timer at the new wait',
      (tester) async {
    var fired = 0;
    Widget panel(Duration after) => MaterialApp(
          home: IdleReset(
            after: after,
            onIdle: () => fired++,
            child: const ColoredBox(color: Color(0xFFFFFFFF), child: SizedBox.expand()),
          ),
        );

    await tester.pumpWidget(panel(const Duration(minutes: 10)));
    await tester.pump(const Duration(minutes: 5));

    // Somebody shortens it in Settings while the old clock is half spent.
    await tester.pumpWidget(panel(const Duration(minutes: 2)));
    await tester.pump(const Duration(minutes: 1, seconds: 55));
    expect(fired, 0, reason: 'the new wait starts from now, not from five minutes ago');
    await tester.pump(const Duration(seconds: 10));
    expect(fired, 1);

    // And the ten-minute timer it replaced must not still be out there. From
    // here the panel goes on waiting the two minutes it was given, so what
    // follows is a count of two-minute waits — a leftover ten-minute timer
    // would show up as one fire more than that.
    await tester.pump(const Duration(minutes: 4, seconds: 10));
    expect(fired, 3, reason: 'the old timer was left running');
  });
}
