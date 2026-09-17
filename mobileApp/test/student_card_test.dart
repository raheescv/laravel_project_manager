import 'package:flutter_test/flutter_test.dart';
import 'package:invo/features/sale/logic/cart_cubit/cart_cubit.dart';
import 'package:invo/features/student_card/domain/models/student_card.dart';
import 'package:invo/features/student_card/domain/services/nfc_card_reader.dart';
import 'package:invo/shared/domain/models/index.dart';
import 'package:invo/shared/utils/components/app_strings.dart';

import 'support/test_harness.dart';

/// A tapped student card on the ticket: the student is the customer, the card
/// pays, and the payload carries the card so the server can check it.
void main() {
  TestWidgetsFlutterBinding.ensureInitialized();

  late TestHarness d;
  late CartCubit cart;

  setUp(() async {
    d = TestHarness();
    await d.init();
    cart = d.cart;
  });

  tearDown(() async => d.dispose());

  final card = StudentCard.fromJson({
    'account_id': 14,
    'name': 'Sara Ahmed',
    'admission_no': 'ADM-1',
    'grade': 'Grade 5',
    'section': 'B',
    'card_uid': '04A21B9C',
    'card_status': 'active',
    'balance': '30.00',
    'overdraft_limit': 10,
    'available': 40,
    'card_method_id': 9,
  });

  test('student cards are off until the server says the business runs the School module', () async {
    expect(cart.schoolEnabled, isFalse);

    await d.storage.setSchoolEnabled(true);
    expect(cart.schoolEnabled, isTrue);
  });

  test('parses the card lookup defensively', () {
    expect(card.accountId, 14);
    expect(card.balance, 30.0);
    expect(card.available, 40.0);
    expect(card.classLabel, 'Grade 5 - B');
    expect(card.isBlocked, isFalse);
    expect(card.cardMethodId, 9);
  });

  test('normalises a typed or read UID like the server', () {
    expect(NfcCardReader.normalize('04:a2:1b 9c'), '04A21B9C');
    expect(NfcCardReader.normalize('  04-A2-1B-9C\n'), '04A21B9C');
  });

  test('a tapped card makes the student the customer and pays by card', () {
    cart.setStudent(card);

    expect(cart.customerName, 'Sara Ahmed');
    expect(cart.payMode, PayMode.studentCard);
    expect(cart.state.paysByStudentCard, isTrue);

    final payload = cart.toPayload();
    expect(payload['paymentMethod'], 'student_card');
    expect(payload['studentAccountId'], 14);
    expect(payload['cardUid'], '04A21B9C');
  });

  test('a card + cash split still counts as a card sale', () {
    cart.setStudent(card);
    cart.setCustomPayments([
      CustomPayment(methodId: 9, methodName: 'Student Wallet', amount: 40),
      CustomPayment(methodId: 1, methodName: 'Cash', amount: 20),
    ]);

    expect(cart.state.paysByStudentCard, isTrue);
    expect(cart.toPayload()['paymentMethod'], 'custom');
  });

  test('typing a client or removing the card takes the card off the ticket', () {
    cart.setStudent(card);
    cart.setClient('Walk-in parent', '');
    expect(cart.student, isNull);
    expect(cart.payMode, PayMode.cash);
    expect(cart.toPayload().containsKey('studentAccountId'), isFalse);

    cart.setStudent(card);
    cart.clearStudent();
    expect(cart.customerName, AppStrings.walkInCustomer);
    expect(cart.payMode, PayMode.cash);
  });

  test('reopening a card-paid sale keeps the student wallet payment', () {
    cart.seedFromSale(Sale.fromJson({
      'id': '41',
      'invoice_no': 'INV-41',
      'status': 'completed',
      'customer': {'name': 'Sara Ahmed', 'mobile': ''},
      'items': const [],
      'payments': [
        {'id': 3, 'payment_method_id': 9, 'method': 'Student Wallet', 'amount': 25},
      ],
      'summary': {'grand_total': 25, 'paid': 25, 'balance': 0, 'tip': 0},
      'student': {'account_id': 14, 'admission_no': 'ADM-1', 'class': 'Grade 5 - B', 'card_balance': 5},
    }));

    expect(cart.payMode, PayMode.studentCard);
    // No card is re-tapped on an edit; the server keeps the sale's student.
    expect(cart.toPayload().containsKey('cardUid'), isFalse);
  });

  test('reads the card balance printed on a student receipt', () {
    final sale = Sale.fromJson({
      'id': '1',
      'student': {'account_id': 14, 'card_balance': '12.50'},
    });
    expect(sale.student?.cardBalance, 12.5);
    expect(Sale.fromJson({'id': '2'}).student, isNull);
  });

  group('pre-order from the parent portal', () {
    Map<String, dynamic> product(int id, String name, num mrp) => {'id': id, 'code': 'P$id', 'name': name, 'type': 'product', 'mrp': mrp, 'tax': 0};

    final withOrder = StudentCard.fromJson({
      'account_id': 14,
      'name': 'Sara Ahmed',
      'card_uid': '04A21B9C',
      'available': 40,
      'pre_order': {
        'id': 77,
        'source': 'weekly',
        'note': 'No sauce',
        'total': '25.00',
        'missing': 1,
        'items': [
          {'quantity': 1, 'product': product(50, 'Happy Meal', 15)},
          {'quantity': 2, 'product': product(51, 'Apple Juice', 5)},
          {'quantity': 1, 'product': null},
        ],
      },
    });

    test('parses today\'s pre-order from the card lookup', () {
      final order = withOrder.preOrder!;
      expect(order.id, 77);
      expect(order.isWeekly, isTrue);
      expect(order.items, hasLength(2), reason: 'an item without a product is dropped');
      expect(order.count, 3);
      expect(order.summary, 'Happy Meal, 2 × Apple Juice');
      expect(order.missing, 1);
      expect(card.preOrder, isNull);
    });

    test('puts the items in the cart and sends the pre-order with the sale', () {
      cart.setStudent(withOrder);
      cart.applyPreOrder(withOrder.preOrder!);
      // A second tap of the same card adds nothing more.
      cart.applyPreOrder(withOrder.preOrder!);

      expect(cart.lines.map((l) => '${l.productId}x${l.qty}'), ['50x1.0', '51x2.0']);
      expect(cart.lines.first.unitPrice, 15);
      expect(cart.toPayload()['preOrderId'], 77);
    });

    test('taking the card or the pre-order off leaves what the cashier added', () {
      cart.setStudent(withOrder);
      cart.add(Product.fromJson(product(51, 'Apple Juice', 5)));
      cart.applyPreOrder(withOrder.preOrder!);
      expect(cart.lines.map((l) => '${l.productId}x${l.qty}'), ['51x3.0', '50x1.0']);

      cart.removePreOrder();
      expect(cart.lines.map((l) => '${l.productId}x${l.qty}'), ['51x1.0']);
      expect(cart.toPayload().containsKey('preOrderId'), isFalse);

      cart.applyPreOrder(withOrder.preOrder!);
      cart.clearStudent();
      expect(cart.lines.map((l) => '${l.productId}x${l.qty}'), ['51x1.0']);
      expect(cart.preOrder, isNull);
    });

    test('another student\'s card takes the previous pre-order off', () {
      cart.setStudent(withOrder);
      cart.applyPreOrder(withOrder.preOrder!);
      cart.setStudent(StudentCard.fromJson({'account_id': 15, 'name': 'Omar', 'card_uid': '0A0B0C0D'}));

      expect(cart.lines, isEmpty);
      expect(cart.preOrder, isNull);
    });
  });
}
