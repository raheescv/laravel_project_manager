import 'package:flutter/material.dart';
import 'package:flutter_test/flutter_test.dart';
import 'package:showcase/l10n/app_localizations.dart';
import 'package:showcase/l10n/app_localizations_ar.dart';
import 'package:showcase/l10n/app_localizations_en.dart';
import 'package:showcase/shared/utils/components/theme/pearl_theme.dart';

/// Arabic is not just a string table here: it flips the layout and changes the
/// typeface, because Pearl is built on wide tracking and Jost has no Arabic
/// glyphs at all.
bool isArabicScript(String s) => RegExp(r'[\u0600-\u06FF]').hasMatch(s);

void main() {
  tearDown(() => PearlText.useArabic(false));

  Future<(TextDirection, String)> pump(WidgetTester tester, Locale locale) async {
    late TextDirection dir;
    late String shown;
    await tester.pumpWidget(MaterialApp(
      locale: locale,
      supportedLocales: L.supportedLocales,
      localizationsDelegates: L.localizationsDelegates,
      home: Builder(builder: (context) {
        dir = Directionality.of(context);
        shown = L.of(context).whichSize;
        return Text(shown);
      }),
    ));
    // The Material/Widgets delegates resolve asynchronously; without settling,
    // the first frame has no localisations and the Builder has not run.
    await tester.pumpAndSettle();
    return (dir, shown);
  }

  testWidgets('English reads left to right', (tester) async {
    final (dir, shown) = await pump(tester, const Locale('en'));
    expect(dir, TextDirection.ltr);
    // Asserted on the wiring, not the wording: the copy is edited by whoever
    // owns the translations, and a test that fails on a reword is a test
    // people learn to ignore.
    expect(shown, isNotEmpty);
    expect(find.text(shown), findsOneWidget);
  });

  testWidgets('Arabic flips the whole tree right to left', (tester) async {
    final (dir, shown) = await pump(tester, const Locale('ar'));
    expect(dir, TextDirection.rtl);
    expect(shown, isNotEmpty);
    // Actually Arabic script, not English left in the file untranslated.
    expect(RegExp(r'[\u0600-\u06FF]').hasMatch(shown), isTrue);
  });

  test('Arabic drops the tracking that would break the joins', () {
    // Pearl's widest tracking is 3.4. On Arabic that separates letters which
    // are supposed to join — the difference between a word and a row of
    // characters. Checked on the decision rather than the resolved style,
    // because resolving one reaches for a font this test cannot fetch.
    PearlText.useArabic(false);
    expect(PearlText.trackingFor(3.4), 3.4);
    expect(PearlText.isArabic, isFalse);

    PearlText.useArabic(true);
    expect(PearlText.trackingFor(3.4), 0);
    expect(PearlText.isArabic, isTrue);
  });

  test('Arabic is given room its line box needs', () {
    PearlText.useArabic(false);
    expect(PearlText.leadingFor(1.08), 1.08);
    PearlText.useArabic(true);
    expect(PearlText.leadingFor(1.08), greaterThan(1.08));
    // Nothing to loosen when the style did not set a height.
    expect(PearlText.leadingFor(null), isNull);
  });

  test('every English key has an Arabic answer', () {
    // A missing translation silently falls back to English, which reads as a
    // bug nobody filed.
    final en = LEn();
    final ar = LAr();
    for (final pair in <(String, String, String)>[
      ('whichSize', en.whichSize, ar.whichSize),
      ('inStock', en.inStock, ar.inStock),
      ('availability', en.availability, ar.availability),
      ('soldOut', en.soldOut, ar.soldOut),
      ('tryAgain', en.tryAgain, ar.tryAgain),
    ]) {
      expect(isArabicScript(pair.$3), isTrue, reason: '${pair.$1} is not Arabic');
      expect(pair.$3, isNot(pair.$2), reason: '${pair.$1} was left in English');
    }
  });
}
