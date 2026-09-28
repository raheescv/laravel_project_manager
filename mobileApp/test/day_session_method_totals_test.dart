import 'package:flutter_test/flutter_test.dart';
import 'package:invo/shared/domain/models/index.dart';

/// The Total Summary prints one total per payment method. An older server
/// sends no `method_totals`, so the app works them out from the rows itself.
Map<String, dynamic> _json({Object? methodTotals}) => {
      'session': {'id': '1', 'status': 'closed'},
      'transactions': [
        {
          'reference_no': 'INV-1',
          'amount': 100,
          'payments': [
            {'method': 'Cash', 'amount': 60},
            {'method': 'Axis Bank', 'amount': 40},
          ],
        },
        {
          'reference_no': 'INV-2',
          'amount': 30,
          'payments': [
            {'method': 'cash', 'amount': 30},
          ],
        },
      ],
      'due_payments': [
        {'reference_no': 'INV-0', 'payment_method': 'Card', 'amount': 25},
      ],
      'totals': const {},
      if (methodTotals != null) 'method_totals': methodTotals,
    };

void main() {
  test('works out per-method totals when the server sends none', () {
    final report = DaySessionReport.fromJson(_json());

    expect(report.methodTotals, const [
      DaySessionMethodTotal(method: 'Cash', invoice: 90),
      DaySessionMethodTotal(method: 'Axis Bank', invoice: 40),
      DaySessionMethodTotal(method: 'Card', due: 25),
    ]);
  });

  test("takes the server's per-method totals when it sends them", () {
    final report = DaySessionReport.fromJson(_json(methodTotals: [
      {'method': 'Cash', 'invoice': 1, 'due': 2},
    ]));

    expect(report.methodTotals, const [DaySessionMethodTotal(method: 'Cash', invoice: 1, due: 2)]);
  });
}
