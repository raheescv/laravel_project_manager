import 'package:dio/dio.dart';
import 'package:flutter/material.dart';
import 'package:flutter_test/flutter_test.dart';
import 'package:google_fonts/google_fonts.dart';

import 'package:invo/features/sales/screens/v3/sales_list_screen.dart';
import 'package:invo/shared/domain/constants/global_variables.dart';
import 'package:invo/shared/domain/models/index.dart';
import 'package:invo/shared/domain/repository/lookup_repository.dart';

import 'support/fake_lookup_repository.dart';
import 'support/test_harness.dart';

/// The Sales list loads its payment methods once, when the screen is built, and
/// swallows the failure so the list itself stays usable. The shell then keeps the
/// screen alive for the rest of the session — so a load that happened while the
/// till was offline used to leave the filter permanently empty, with no way to ask
/// again short of restarting the app.
void main() {
  setUpAll(() => GoogleFonts.config.allowRuntimeFetching = false);

  testWidgets('the payment filter recovers from a load that failed', (tester) async {
    final harness = TestHarness();
    await harness.init();
    addTearDown(harness.dispose);

    final lookup = _FlakyLookup();
    serviceLocator.unregister<LookupRepository>();
    serviceLocator.registerSingleton<LookupRepository>(lookup);

    tester.view.physicalSize = const Size(430, 1400);
    tester.view.devicePixelRatio = 1.0;
    addTearDown(tester.view.resetPhysicalSize);
    addTearDown(tester.view.resetDevicePixelRatio);

    // The screen is built during the outage: its one load fails.
    lookup.offline = true;
    await tester.pumpWidget(harness.wrap(const SalesListScreen()));
    await tester.pump(const Duration(milliseconds: 400));
    expect(lookup.methodCalls, 1);

    // The list is readable again — from the snapshot, or because the network is
    // back. Either way nothing has rebuilt this screen.
    lookup.offline = false;
    await tester.tap(find.text('All methods'));
    await tester.pumpAndSettle();

    expect(lookup.methodCalls, 2, reason: 'opening the sheet asks again');
    expect(find.text('Cash'), findsOneWidget);
    expect(find.text('Card'), findsOneWidget);
  });
}

/// A lookup repository that fails the way an unreachable server does, until it
/// is told not to.
class _FlakyLookup extends FakeLookupRepository {
  bool offline = false;
  int methodCalls = 0;

  @override
  Future<List<PaymentMethod>> paymentMethods() async {
    methodCalls++;
    if (offline) {
      throw DioException.connectionError(
          requestOptions: RequestOptions(), reason: 'no route to host');
    }
    return [PaymentMethod(id: 1, name: 'Cash'), PaymentMethod(id: 2, name: 'Card')];
  }
}
