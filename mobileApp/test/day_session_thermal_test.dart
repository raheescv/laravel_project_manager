import 'dart:typed_data';

import 'package:flutter_test/flutter_test.dart';
import 'package:invo/features/admin/widgets/day_session_report_pdf.dart';
import 'package:invo/shared/domain/models/index.dart';

/// The Sale Bill Report roll drops DUE AMOUNT DETAILS and DUE PAYMENT RECEIVED
/// when the session has neither — an empty table is paper spent on saying
/// nothing. Content isn't readable out of a built PDF, so the roll's own
/// height (its `/MediaBox`) stands in: a dropped section makes the roll
/// shorter, and its heading no longer costs a line.
DaySessionReport _report({List<Map<String, dynamic>> dues = const [], List<Map<String, dynamic>> duePayments = const []}) =>
    DaySessionReport.fromJson({
      'session': {
        'id': '12',
        'branch': 'Downtown',
        'status': 'closed',
        'opened_at': '2026-09-10 09:00:00',
        'closed_at': '2026-09-10 17:00:00',
        'opened_by': 'Maya',
        'closed_by': 'Liam',
      },
      'transactions': [
        {
          'source': 'Sale',
          'reference_no': 'INV-0001',
          'amount': 100,
          'paid_amount': 100,
          'due_amount': 0,
          'payments': [
            {'method': 'Cash', 'amount': 100},
          ],
        },
      ],
      'due_transactions': dues,
      'due_payments': duePayments,
      'totals': {'sale_tailoring_amount': 100, 'payment_total': 100},
    });

const _due = {'source': 'Sale', 'reference_no': 'INV-0002', 'due_amount': 40};
const _duePayment = {
  'source': 'Sale',
  'reference_no': 'INV-0003',
  'payment_method': 'Cash',
  'amount': 25,
};

const _settings = PrintSettings(
  style: PrintStyle.englishOnly,
  width: PaperWidth.mm80,
  showDiscount: true,
  showTotalQty: true,
  showBarcode: true,
  footerEnglish: '',
  footerArabic: '',
  companyName: 'Astra',
);

final _mediaBox = RegExp(r'/MediaBox\s*\[[\d.\s]+?\s([\d.]+)\s*\]');

double _rollHeight(Uint8List bytes) {
  final match = _mediaBox.firstMatch(String.fromCharCodes(bytes));
  expect(match, isNotNull, reason: 'the roll should declare a page height');
  return double.parse(match!.group(1)!);
}

void main() {
  TestWidgetsFlutterBinding.ensureInitialized();

  test('a session with no dues and no due payments prints a shorter roll', () async {
    final bare = _rollHeight(await buildDaySessionThermalPdf(_report(), _settings));
    final withDues = _rollHeight(
        await buildDaySessionThermalPdf(_report(dues: [_due], duePayments: [_duePayment]), _settings));

    expect(bare, lessThan(withDues),
        reason: 'both due sections should be off the roll entirely when there is nothing to show');
  });

  test('each due section is dropped on its own', () async {
    final bare = _rollHeight(await buildDaySessionThermalPdf(_report(), _settings));
    final duesOnly = _rollHeight(await buildDaySessionThermalPdf(_report(dues: [_due]), _settings));
    final paymentsOnly =
        _rollHeight(await buildDaySessionThermalPdf(_report(duePayments: [_duePayment]), _settings));

    expect(duesOnly, greaterThan(bare), reason: 'DUE AMOUNT DETAILS still prints when something is due');
    expect(paymentsOnly, greaterThan(bare),
        reason: 'DUE PAYMENT RECEIVED still prints when a due payment came in');
  });

  test('the roll is still a valid PDF with both sections dropped', () async {
    final bytes = await buildDaySessionThermalPdf(_report(), _settings);

    expect(bytes.sublist(0, 4), [0x25, 0x50, 0x44, 0x46]);
    expect(String.fromCharCodes(bytes.sublist(bytes.length - 16)).contains('%%EOF'), isTrue);
  });
}
