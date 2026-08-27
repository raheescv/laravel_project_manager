import 'package:flutter/material.dart';
import 'package:flutter_test/flutter_test.dart';
import 'package:showcase/shared/utils/components/theme/pearl_theme.dart';
import 'package:showcase/shared/widgets/pearl_widgets.dart';

/// The count belongs in the chip's corner, and "in the corner" is a claim about
/// geometry that only a measurement can keep honest.
///
/// It regressed once already without showing up in review: the chip's Container
/// carried an `alignment`, which wraps its child in an Align and hands the
/// Stack loose constraints. The Stack shrink-wrapped to the label, so every
/// Positioned child measured from the corner of the *text* — the count rendered
/// a few pixels off the digits and the design read as unchanged.
void main() {
  Future<Rect> pumpChip(WidgetTester tester, {bool available = true}) async {
    await tester.pumpWidget(
      MaterialApp(
        theme: buildPearlTheme(PearlPalette.light),
        home: Scaffold(
          body: Center(
            child: SizedBox(
              width: 100,
              child: PearlChip(
                label: '42',
                sub: '80',
                height: 58,
                available: available,
                onTap: () {},
              ),
            ),
          ),
        ),
      ),
    );
    return tester.getRect(find.byType(PearlChip));
  }

  testWidgets('the chip fills the box it is given', (tester) async {
    final chip = await pumpChip(tester);
    expect(chip.width, 100);
    expect(chip.height, 58);
  });

  testWidgets('the count sits in the top-right corner of the chip', (tester) async {
    final chip = await pumpChip(tester);
    final count = tester.getRect(find.text('80'));

    expect(count.center.dx, greaterThan(chip.center.dx),
        reason: 'the count should be in the right half, not beside the label');
    expect(count.center.dy, lessThan(chip.center.dy),
        reason: 'the count should be in the top half, not under the label');
    // Against the corner, not merely on that side of centre.
    expect(chip.right - count.right, lessThan(14));
    expect(count.top - chip.top, lessThan(14));
  });

  testWidgets('the label stays centred in the whole chip', (tester) async {
    final chip = await pumpChip(tester);
    final label = tester.getRect(find.text('42'));

    expect((label.center.dx - chip.center.dx).abs(), lessThan(1));
    expect((label.center.dy - chip.center.dy).abs(), lessThan(1));
  });

  testWidgets('an unavailable chip is struck across its full width', (tester) async {
    final chip = await pumpChip(tester, available: false);

    // Material paints its own CustomPaints, so match on the size rather than on
    // a private painter type: the strike is the one that covers the whole chip.
    // Inset by the chip's own 1px border, so "covers the chip" is within a
    // couple of pixels rather than exact.
    final covers = find
        .byType(CustomPaint)
        .evaluate()
        .map((e) => tester.getRect(find.byWidget(e.widget)))
        .any((r) =>
            r.width >= chip.width - 4 && r.height >= chip.height - 4);

    // The strike used to cover only the text box, which on a wide chip read as
    // a smudge beside the size rather than a line through it.
    expect(covers, isTrue);
  });
}
