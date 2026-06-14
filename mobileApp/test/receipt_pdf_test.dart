import 'package:flutter_test/flutter_test.dart';
import 'package:invo/features/sale/receipt_pdf.dart';
import 'package:invo/models/models.dart';

Sale _demoSale() => Sale.fromJson({
      'id': '5001', 'invoice_no': 'INV-0001', 'date': '2026-06-14', 'status': 'completed', 'branch': 'Downtown',
      'customer': {'name': 'A. Rivera', 'mobile': '+1 415 555 0142'},
      'items': [
        {'name': 'Signature Cut', 'type': 'service', 'employee': 'Maya', 'quantity': 1, 'unit_price': 45, 'discount': 0, 'total': 45},
        {'name': 'Balayage', 'type': 'service', 'employee': 'Liam', 'quantity': 2, 'unit_price': 90, 'discount': 18, 'total': 162},
      ],
      'payments': [{'method': 'Cash', 'amount': 120}, {'method': 'Card', 'amount': 87}],
      'summary': {'gross_amount': 225, 'item_discount': 18, 'other_discount': 0, 'tax_amount': 0, 'paid': 207},
      'created_by': 'Maya',
    });

void main() {
  test('buildReceiptPdf produces a valid, non-empty PDF', () async {
    final bytes = await buildReceiptPdf(_demoSale());

    expect(bytes.length, greaterThan(1000), reason: 'a real receipt PDF should be more than a stub');
    // PDF magic header "%PDF".
    expect(bytes.sublist(0, 4), [0x25, 0x50, 0x44, 0x46]);
    // EOF marker present somewhere near the tail.
    final tail = String.fromCharCodes(bytes.sublist(bytes.length - 16));
    expect(tail.contains('%%EOF'), isTrue, reason: 'PDF should be terminated');
  });

  test('buildReceiptPdf handles a credit sale (zero paid) without throwing', () async {
    final sale = Sale.fromJson({
      'id': '7', 'invoice_no': '', 'date': '2026-06-14', 'status': 'completed', 'branch': '',
      'customer': {'name': 'Walk-in', 'mobile': ''},
      'items': [
        {'name': 'Spa Ritual', 'type': 'service', 'employee': '', 'quantity': 1, 'unit_price': 90, 'discount': 0, 'total': 90},
      ],
      'payments': <Map<String, dynamic>>[],
      'summary': {'gross_amount': 90, 'item_discount': 0, 'other_discount': 0, 'tax_amount': 0, 'paid': 0},
      'created_by': '',
    });
    final bytes = await buildReceiptPdf(sale);
    expect(bytes.sublist(0, 4), [0x25, 0x50, 0x44, 0x46]);
  });
}
