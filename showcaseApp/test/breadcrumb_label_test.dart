import 'package:flutter/material.dart';
import 'package:flutter_test/flutter_test.dart';
import 'package:showcase/shared/logic/funnel_cubit/funnel_cubit.dart';
import 'package:showcase/l10n/app_localizations.dart';
import 'package:showcase/shared/domain/models/index.dart';
import 'package:showcase/shared/utils/components/theme/pearl_theme.dart';
import 'package:showcase/shared/widgets/chrome/funnel_breadcrumbs.dart';

/// A crumb has to say what its answer answered. "49 › HOKA" leaves the number
/// to be guessed at, and 49 is a size here and a price on every other screen a
/// customer has ever read.
void main() {
  const state = FunnelState(
    size: '43.5',
    brand: BrandOption(id: 1, name: 'New Balance', productCount: 9),
  );

  Future<void> pump(
    WidgetTester tester, {
    void Function(FunnelStep)? onReopen,
    Locale locale = const Locale('en'),
  }) {
    return tester.pumpWidget(MaterialApp(
      theme: buildPearlTheme(PearlPalette.light),
      locale: locale,
      supportedLocales: L.supportedLocales,
      localizationsDelegates: L.localizationsDelegates,
      home: Scaffold(
        body: FunnelBreadcrumbs(
          state: state,
          current: FunnelStep.results,
          onReopen: onReopen ?? (_) {},
        ),
      ),
    ));
  }

  testWidgets('each crumb names the question as well as the answer',
      (tester) async {
    await pump(tester);

    expect(find.text('SIZE'), findsOneWidget);
    expect(find.text('43.5'), findsOneWidget);
    expect(find.text('BRAND'), findsOneWidget);
    expect(find.text('NEW BALANCE'), findsOneWidget);
  });

  testWidgets('the answer is the part set in weight', (tester) async {
    await pump(tester);

    final p = PearlPalette.light;
    final answer = tester.widget<Text>(find.text('43.5')).style!;
    final label = tester.widget<Text>(find.text('SIZE')).style!;

    expect(answer.fontWeight, FontWeight.w700);
    expect(answer.color, p.ink);
    // The label stays out of the way: lighter ink and smaller than its answer.
    expect(label.color, p.faint);
    expect(label.fontSize! < answer.fontSize!, isTrue);
  });

  testWidgets('a chevron only ever points at another crumb', (tester) async {
    await pump(tester);

    // Two crumbs, one gap. The strip used to draw one past the last crumb too,
    // aimed at the empty half of the bar.
    expect(find.byIcon(Icons.chevron_right), findsOneWidget);
  });

  testWidgets('the chevron points the way the strip runs', (tester) async {
    await pump(tester, locale: const Locale('ar'));

    // Arabic lays the crumbs out right to left, and a fixed right chevron
    // pointed back at the step the customer had already taken.
    expect(find.byIcon(Icons.chevron_left), findsOneWidget);
    expect(find.byIcon(Icons.chevron_right), findsNothing);
  });

  testWidgets('a crumb still reopens its step', (tester) async {
    FunnelStep? reopened;
    await pump(tester, onReopen: (step) => reopened = step);

    await tester.tap(find.text('NEW BALANCE'));
    expect(reopened, FunnelStep.brand);
  });
}
