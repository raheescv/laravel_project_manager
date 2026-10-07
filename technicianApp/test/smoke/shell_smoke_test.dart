// The real app (router, shell, providers) at phone and tablet size, light and
// dark: every primary destination renders without a framework error.
import 'package:flutter_test/flutter_test.dart';

import 'package:invo/features/shell/screens/v3/technician_shell.dart';

import '../support/harness.dart';

void main() {
  for (final (label, size) in [('phone', phoneSize), ('tablet', tabletSize), ('wide', wideSize)]) {
    for (final dark in [false, true]) {
      final mode = dark ? 'dark' : 'light';
      testWidgets('app shell visits every tab ($label, $mode)', (tester) async {
        await setUpEnv(dark: dark);
        await pumpApp(tester, size: size);
        expectNoErrors(tester);
        expect(find.textContaining('Welcome back'), findsWidgets);

        for (final tab in ['My Jobs', 'Checklists', 'Settings', 'Dashboard']) {
          await tester.tap(find.text(tab).last);
          await settle(tester);
          expectNoErrors(tester);
        }
        expect(find.textContaining('Welcome back'), findsWidgets);
      });
    }
  }

  testWidgets('tablet rail opens Profile from the avatar', (tester) async {
    await setUpEnv();
    await pumpApp(tester, size: tabletSize);
    await tester.tap(find.text('Profile').last);
    await settle(tester);
    expectNoErrors(tester);
    expect(find.text('My Profile'), findsOneWidget);
    expect(find.text('rahul@employee.local'), findsWidgets);
  });

  testWidgets('dashboard shows its retry state when the server fails', (tester) async {
    final env = await setUpEnv();
    env.tech.fail = true;
    await pumpApp(tester);
    expectNoErrors(tester);
    expect(find.text('Could not load'), findsWidgets);
  });

  testWidgets('signed out, the app lands on the login screen', (tester) async {
    await setUpEnv(signedIn: false);
    await pumpApp(tester);
    expectNoErrors(tester);
    expect(find.text('TECHNICIAN'), findsOneWidget);
    expect(find.byType(TechnicianShell), findsNothing);
  });
}
