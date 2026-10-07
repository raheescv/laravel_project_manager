// Settings, Profile and the account forms — phone (pushed routes) and tablet
// (two-pane, embedded forms) — always with a signed-in user so the identity
// hero is actually built.
import 'package:flutter/material.dart';
import 'package:flutter_test/flutter_test.dart';

import 'package:invo/features/profile/screens/v3/change_password_screen.dart';
import 'package:invo/features/profile/screens/v3/change_pin_screen.dart';
import 'package:invo/features/profile/screens/v3/edit_profile_screen.dart';
import 'package:invo/features/profile/screens/v3/profile_screen.dart';
import 'package:invo/features/settings/screens/v3/technician_settings_screen.dart';

import '../support/harness.dart';

void main() {
  // ---------------------------------------------------------------- settings --

  for (final dark in [false, true]) {
    testWidgets('settings phone list (${dark ? 'dark' : 'light'})', (tester) async {
      await setUpEnv(dark: dark);
      await pumpScreen(tester, const TechnicianSettingsScreen());
      expectNoErrors(tester);
      expect(find.text('Rahul Nair'), findsOneWidget);
      await tester.scrollUntilVisible(find.text('Log out'), 300, scrollable: find.byType(Scrollable).first);
      expectNoErrors(tester);
    });
  }

  for (final (label, size) in [('tablet', tabletSize), ('wide', wideSize), ('tablet portrait', const Size(834, 1194))]) {
    testWidgets('settings tablet: every category ($label)', (tester) async {
      await setUpEnv();
      await pumpScreen(tester, const TechnicianSettingsScreen(), size: size);
      expectNoErrors(tester);
      // Account (default) builds the hero for the signed-in user.
      expect(find.text('SIGNED IN AS'), findsOneWidget);
      expect(find.text('Edit profile'), findsWidgets);
      for (final cat in ['Appearance', 'Security', 'This device', 'About', 'Account']) {
        await tester.tap(find.text(cat).first);
        await settle(tester);
        expectNoErrors(tester);
      }
    });
  }

  testWidgets('settings tablet: account forms open in the pane and save', (tester) async {
    final env = await setUpEnv();
    await pumpScreen(tester, const TechnicianSettingsScreen(), size: tabletSize);

    await tester.tap(find.text('Edit profile').first);
    await settle(tester);
    expectNoErrors(tester);
    await tester.enterText(find.byType(TextField).first, 'Rahul N.');
    await tester.tap(find.text('Save changes'));
    await settle(tester);
    expectNoErrors(tester);
    expect(env.profile.calls, contains('updateProfile:Rahul N.'));
    expect(env.authCubit.user?.name, 'Rahul N.');
    expect(find.text('SIGNED IN AS'), findsOneWidget); // back on the overview

    await tester.tap(find.text('Change MPIN'));
    await settle(tester);
    expectNoErrors(tester);
    final fields = find.byType(TextField);
    await tester.enterText(fields.at(0), '1111');
    await tester.enterText(fields.at(1), '2468');
    await tester.enterText(fields.at(2), '2468');
    await tester.tap(find.text('Update PIN'));
    await settle(tester);
    expectNoErrors(tester);
    expect(env.auth.calls, contains('changePin:2468'));
  });

  testWidgets('settings: theme, chrome and haptics apply on tap', (tester) async {
    await setUpEnv();
    await pumpScreen(tester, const TechnicianSettingsScreen(), size: tabletSize);
    await tester.tap(find.text('Appearance').first);
    await settle(tester);
    await tester.tap(find.text('Dark'));
    await settle(tester);
    await tester.scrollUntilVisible(find.text('Docked rail'), 200, scrollable: find.byType(Scrollable).last);
    await tester.tap(find.text('Docked rail'));
    await settle(tester);
    expectNoErrors(tester);
    await tester.tap(find.text('This device').first);
    await settle(tester);
    await tester.tap(find.text('Haptic feedback'));
    await settle(tester);
    expectNoErrors(tester);
  });

  // ----------------------------------------------------------------- profile --

  for (final (label, size) in [('phone', phoneSize), ('tablet', tabletSize), ('wide', wideSize)]) {
    testWidgets('profile ($label)', (tester) async {
      await setUpEnv();
      await pumpScreen(tester, const ProfileScreen(), size: size, pushed: true);
      expectNoErrors(tester);
      expect(find.text('My Profile'), findsOneWidget);
      expect(find.text('rahul@employee.local'), findsWidgets);
    });
  }

  testWidgets('profile tablet: every section embedded', (tester) async {
    await setUpEnv();
    await pumpScreen(tester, const ProfileScreen(), size: tabletSize);
    for (final section in ['Edit profile', 'Change MPIN', 'Change password', 'Profile details']) {
      await tester.tap(find.text(section).first);
      await settle(tester);
      expectNoErrors(tester);
    }
  });

  // ------------------------------------------------------- standalone forms --

  for (final (label, size) in [('phone', phoneSize), ('tablet', tabletSize)]) {
    testWidgets('edit profile saves and pops ($label)', (tester) async {
      final env = await setUpEnv();
      await pumpScreen(tester, const EditProfileScreen(), size: size, pushed: true);
      expectNoErrors(tester);
      await tester.enterText(find.byType(TextField).first, 'Rahul Nair Jr');
      await tester.tap(find.text(size == phoneSize ? 'Save' : 'Save changes'));
      await settle(tester);
      expectNoErrors(tester);
      expect(env.profile.calls, contains('updateProfile:Rahul Nair Jr'));
      expect(find.text('host page'), findsOneWidget);
    });

    testWidgets('change PIN validates, reports a server error, then succeeds ($label)', (tester) async {
      final env = await setUpEnv();
      await pumpScreen(tester, const ChangePinScreen(), size: size, pushed: true);
      final fields = find.byType(TextField);
      await tester.enterText(fields.at(0), '1111');
      await tester.enterText(fields.at(1), '2468');
      await tester.enterText(fields.at(2), '1357');
      await tester.tap(find.text('Update PIN'));
      await settle(tester);
      expect(find.text('New PIN and confirmation don’t match.'), findsOneWidget);
      expect(env.auth.calls, isEmpty);

      await tester.enterText(fields.at(0), '0000'); // the fake rejects this
      await tester.enterText(fields.at(2), '2468');
      await tester.tap(find.text('Update PIN'));
      await settle(tester);
      expect(find.text('Current PIN is wrong'), findsOneWidget);

      await tester.enterText(fields.at(0), '1111');
      await tester.tap(find.text('Update PIN'));
      await settle(tester);
      expectNoErrors(tester);
      expect(find.text('host page'), findsOneWidget);
    });

    testWidgets('change password ($label)', (tester) async {
      final env = await setUpEnv();
      await pumpScreen(tester, const ChangePasswordScreen(), size: size, pushed: true);
      final fields = find.byType(TextField);
      await tester.enterText(fields.at(0), 'old-pass-1');
      await tester.enterText(fields.at(1), 'new-pass-22');
      await tester.enterText(fields.at(2), 'new-pass-22');
      await tester.tap(find.text('Update password'));
      await settle(tester);
      expectNoErrors(tester);
      expect(env.auth.calls, contains('changePassword'));
    });
  }
}
