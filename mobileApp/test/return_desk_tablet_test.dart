import 'package:flutter/material.dart';
import 'package:flutter_bloc/flutter_bloc.dart';
import 'package:flutter_test/flutter_test.dart';
import 'package:google_fonts/google_fonts.dart';

import 'package:invo/features/sale/logic/cart_cubit/cart_cubit.dart' show PayMode;
import 'package:invo/features/sale_return/logic/return_draft_cubit/return_draft_cubit.dart';
import 'package:invo/features/sale_return/screens/v3/new_sale_return_screen.dart';
import 'package:invo/shared/domain/helpers/formatters.dart';
import 'package:invo/shared/domain/models/index.dart';

import 'support/test_harness.dart';

/// The tablet return is the "Receipt Mirror": the invoice as paper on the
/// left, the return slip and refund on the right — one screen, no Review step.
/// Split works as on Review & Pay, and follows the total as lines change.
void main() {
  setUpAll(() => GoogleFonts.config.allowRuntimeFetching = false);

  const cash = PaymentMethod(id: 1, name: 'Cash');
  const card = PaymentMethod(id: 2, name: 'Card');
  const bank = PaymentMethod(id: 3, name: 'Bank Transfer');

  final sale = ReturnableSale.fromJson(const {
    'sale_id': '412',
    'invoice_no': 'INV-0412',
    'date': '2026-10-28',
    'customer': {'name': 'Fatima Al-Kuwari', 'mobile': '55123390'},
    'items': [
      {'sale_item_id': 1, 'name': 'Hydrating Facial', 'type': 'service', 'unit_price': 180, 'sold_quantity': 1, 'returnable_quantity': 1},
      {'sale_item_id': 2, 'name': 'Argan Oil Shampoo', 'type': 'product', 'unit_price': 65, 'sold_quantity': 3, 'returned_quantity': 1, 'returnable_quantity': 2},
      {'sale_item_id': 3, 'name': 'Gel Polish', 'type': 'product', 'unit_price': 35, 'sold_quantity': 1, 'returned_quantity': 1, 'returnable_quantity': 0},
    ],
  });

  double paid(ReturnDraftCubit d) => round2(d.customPayments.fold(0.0, (a, p) => a + p.amount));

  group('ReturnDraftCubit split', () {
    test('the split re-shares as lines change', () {
      final d = ReturnDraftCubit()..seed(sale);
      d.setQty(d.lines[0], 1);
      d.splitBetween(const [cash, card]);
      expect([for (final p in d.customPayments) p.amount], [90, 90]);

      d.setQty(d.lines[1], 2);
      expect(d.total, 310);
      expect(paid(d), 310, reason: 'the split follows the new total');

      d.setSplitAmount(cash.id, 100);
      d.clearQtys();
      expect(d.customPayments.first.amount, 100, reason: 'a typed amount stays put');
      expect(d.customPayments.last.amount, 0);
    });

    test('Return all takes every returnable line to its limit', () {
      final d = ReturnDraftCubit()..seed(sale);
      d.returnAll();
      expect([for (final l in d.lines) l.returnQty], [1, 2, 0]);
      d.clearQtys();
      expect(d.isEmpty, isTrue);
    });

    test('removing the last split method falls back to cash', () {
      final d = ReturnDraftCubit()..seed(sale);
      d.setQty(d.lines[0], 1);
      d.splitBetween(const [cash]);
      d.toggleSplitMethod(cash);
      expect(d.payMode, PayMode.cash);
      expect(d.customPayments, isEmpty);
    });
  });

  Future<ReturnDraftCubit> open(WidgetTester tester, Size size) async {
    final h = TestHarness();
    await h.init();
    addTearDown(h.dispose);
    h.lookup.methods = const [cash, card, bank];
    tester.view.physicalSize = size;
    tester.view.devicePixelRatio = 1.0;
    addTearDown(tester.view.resetPhysicalSize);
    addTearDown(tester.view.resetDevicePixelRatio);
    await tester.pumpWidget(h.wrap(const NewSaleReturnScreen()));
    final draft = BlocProvider.of<ReturnDraftCubit>(tester.element(find.byType(NewSaleReturnScreen)))..seed(sale);
    await settle(tester);
    return draft;
  }

  testWidgets('tablet shows the invoice paper and the return slip together', (tester) async {
    final draft = await open(tester, const Size(1194, 834));
    expect(find.text('NEW RETURN · INVOICE'), findsOneWidget);
    expect(find.text('RETURN SLIP · DRAFT'), findsOneWidget);
    expect(find.text('Tick the lines coming back'), findsOneWidget);

    await tester.tap(find.text('1× Hydrating Facial'));
    await settle(tester);
    expect(draft.lines[0].returnQty, 1);
    expect(find.text('Refund ${Money.of(180)}'), findsOneWidget);

    // Fully returned lines don't tick in.
    await tester.tap(find.text('1× Gel Polish'));
    await settle(tester);
    expect(draft.lines[2].returnQty, 0);

    await tester.tap(find.text('Return all'));
    await settle(tester);
    expect(draft.total, 310);
    expect(find.text('Return all'), findsNothing, reason: 'nothing more to return');

    // Clear sits on the slip, where the header's used to be.
    await tester.tap(find.text('Clear'));
    await settle(tester);
    expect(draft.isEmpty, isTrue);
    expect(tester.takeException(), isNull);
  });

  testWidgets('split asks which two, and the keypad refunds live', (tester) async {
    final draft = await open(tester, const Size(1194, 834));
    draft.returnAll();
    await settle(tester);

    await tester.tap(find.text('Split'));
    await settle(tester);
    expect(find.text('Split the refund'), findsOneWidget);
    await tester.tap(find.text('Cash').last);
    await tester.tap(find.text('Card').last);
    await settle(tester);
    expect(draft.payMode, PayMode.custom);
    expect(paid(draft), 310);
    await tester.tap(find.text('Apply split · 2 ways'));
    await settle(tester);
    expect(find.text('Split the refund'), findsNothing);

    await tester.tap(find.text(Money.plain(155)).first);
    await settle(tester);
    expect(find.textContaining('Cash refunds'), findsOneWidget);
    await tester.tap(find.text('1').last);
    await tester.pump();
    expect([for (final p in draft.customPayments) p.amount], [1, 309]);
    expect(tester.takeException(), isNull);
  });

  for (final size in const [Size(1097, 686), Size(1194, 834), Size(834, 1194), Size(744, 1133)]) {
    testWidgets('a three-way split fits at ${size.width.toInt()}×${size.height.toInt()}', (tester) async {
      final draft = await open(tester, size);
      draft
        ..returnAll()
        ..splitBetween(const [cash, card, bank]);
      await settle(tester);
      expect(tester.takeException(), isNull, reason: 'overflows at $size');
      expect(find.text('Bank Transfer'), findsWidgets);
      expect(find.text('Confirm Anyway'), findsNothing);
    });
  }

  testWidgets('the phone keeps its own screen', (tester) async {
    await open(tester, const Size(390, 844));
    expect(find.text('RETURN SLIP · DRAFT'), findsNothing);
    expect(find.text('New Return'), findsOneWidget);
    expect(tester.takeException(), isNull);
  });
}

Future<void> settle(WidgetTester tester) async {
  await tester.pump();
  await tester.pump(const Duration(milliseconds: 400));
}
