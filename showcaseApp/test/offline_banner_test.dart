import 'dart:async';

import 'package:flutter/material.dart';
import 'package:flutter_bloc/flutter_bloc.dart';
import 'package:flutter_test/flutter_test.dart';
import 'package:showcase/l10n/app_localizations.dart';
import 'package:showcase/shared/logic/connectivity_cubit/connectivity_cubit.dart';
import 'package:showcase/shared/utils/components/theme/pearl_theme.dart';
import 'package:showcase/shared/widgets/chrome/showcase_scaffold.dart';

/// The banner says what is being done about it.
///
/// "Offline" alone, on a kiosk nobody can tap, reads as broken. While the
/// cubit is asking the server on its own the banner says so — and only then,
/// so it never promises a reconnect nobody is attempting.
void main() {
  /// The cubit's stream hands the provider its new state in a microtask that
  /// lands after the frame `pump` builds, so the banner is one frame behind
  /// the state. Two frames is what the real panel draws in the same instant.
  Future<void> settle(WidgetTester tester) async {
    await tester.pump();
    await tester.pump();
  }

  Future<void> pump(WidgetTester tester, ConnectivityCubit cubit) async {
    addTearDown(cubit.close);
    await tester.pumpWidget(MaterialApp(
      theme: buildPearlTheme(PearlPalette.light),
      supportedLocales: L.supportedLocales,
      localizationsDelegates: L.localizationsDelegates,
      home: BlocProvider<ConnectivityCubit>.value(
        value: cubit,
        child: const ShowcaseScaffold(body: SizedBox.shrink()),
      ),
    ));
    await tester.pump();
  }

  testWidgets('says it is reconnecting while a probe is running, and clears when it lands',
      (tester) async {
    // A probe that never answers: the outage is ended by hand below.
    final cubit = ConnectivityCubit(probe: () => Completer<void>().future);
    await pump(tester, cubit);
    expect(find.textContaining('OFFLINE'), findsNothing);

    cubit.reportOutcome(reachable: false);
    await settle(tester);
    expect(find.textContaining('OFFLINE'), findsOneWidget);
    expect(find.textContaining('RECONNECTING'), findsOneWidget);

    cubit.reportOutcome(reachable: true);
    await settle(tester);
    expect(find.textContaining('OFFLINE'), findsNothing);
  });

  testWidgets('with nothing to probe, it says offline and nothing more', (tester) async {
    final cubit = ConnectivityCubit();
    await pump(tester, cubit);
    cubit.reportOutcome(reachable: false);
    await settle(tester);
    expect(find.textContaining('OFFLINE'), findsOneWidget);
    expect(find.textContaining('RECONNECTING'), findsNothing);
  });
}
