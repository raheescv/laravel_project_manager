import 'package:flutter_test/flutter_test.dart';

import 'package:invo/features/auth/screens/v3/login_screen.dart';
import 'package:invo/features/auth/logic/auth_cubit/auth_cubit.dart';

import '../support/harness.dart';

void main() {
  for (final (label, size) in [('phone', phoneSize), ('tablet', tabletSize)]) {
    testWidgets('login renders and a 4-digit PIN signs in ($label)', (tester) async {
      final env = await setUpEnv(signedIn: false);
      await pumpScreen(tester, const LoginScreen(), size: size);
      expectNoErrors(tester);
      expect(find.text('TECHNICIAN'), findsOneWidget);

      for (final d in ['1', '2', '3', '4']) {
        await tester.tap(find.text(d).last);
        await tester.pump();
      }
      await settle(tester);
      expectNoErrors(tester);
      expect(env.auth.calls, contains('login:1234'));
      expect(env.authCubit.status, AuthStatus.signedIn);
    });
  }
}
