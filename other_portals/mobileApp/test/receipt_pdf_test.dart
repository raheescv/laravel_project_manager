import 'dart:convert';
import 'dart:typed_data';

import 'package:flutter_test/flutter_test.dart';
import 'package:invo/shared/domain/models/index.dart';
import 'package:invo/shared/widgets/receipt_pdf.dart';

Sale _demoSale() => Sale.fromJson(_demoSaleJson);

const Map<String, dynamic> _demoSaleJson = {
      'id': '5001', 'invoice_no': 'INV-0001', 'date': '2026-06-14', 'status': 'completed', 'branch': 'Downtown',
      'customer': {'name': 'A. Rivera', 'mobile': '+1 415 555 0142'},
      'items': [
        {'name': 'Signature Cut', 'name_arabic': 'قصة مميزة', 'type': 'service', 'employee': 'Maya', 'quantity': 1, 'unit_price': 45, 'discount': 0, 'total': 45},
        {'name': 'Balayage', 'name_arabic': 'بالياج', 'type': 'service', 'employee': 'Liam', 'quantity': 2, 'unit_price': 90, 'discount': 18, 'total': 162},
      ],
      'payments': [{'method': 'Cash', 'amount': 120}, {'method': 'Card', 'amount': 87}],
      'summary': {'gross_amount': 225, 'item_discount': 18, 'other_discount': 0, 'tax_amount': 0, 'paid': 207},
      'created_by': 'Maya',
    };

PrintSettings _settings({
  PrintStyle style = PrintStyle.englishOnly,
  PaperWidth width = PaperWidth.mm80,
  Uint8List? logo,
  String companyName = '',
  bool showNotes = false,
}) =>
    PrintSettings(
      style: style,
      width: width,
      showDiscount: true,
      showTotalQty: true,
      showBarcode: true,
      footerEnglish: 'Thank you!',
      footerArabic: 'شكرا لك',
      logo: logo,
      companyName: companyName,
      showNotes: showNotes,
    );

// A 1x1 red pixel PNG — the smallest valid raster logo.
final Uint8List _pngLogo = base64Decode(
    'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mP8z8BQDwAEhQGAhKmMIQAAAABJRU5ErkJggg==');

final Uint8List _svgLogo = Uint8List.fromList(utf8.encode(
    '<svg xmlns="http://www.w3.org/2000/svg" width="80" height="40"><rect width="80" height="40" fill="#0a5"/></svg>'));

void main() {
  TestWidgetsFlutterBinding.ensureInitialized();

  test('buildReceiptPdf produces a valid, non-empty PDF', () async {
    final bytes = await buildReceiptPdf(_demoSale(), _settings());

    expect(bytes.length, greaterThan(1000), reason: 'a real receipt PDF should be more than a stub');
    // PDF magic header "%PDF".
    expect(bytes.sublist(0, 4), [0x25, 0x50, 0x44, 0x46]);
    // EOF marker present somewhere near the tail.
    final tail = String.fromCharCodes(bytes.sublist(bytes.length - 16));
    expect(tail.contains('%%EOF'), isTrue, reason: 'PDF should be terminated');
  });

  test('buildReceiptPdf renders the bilingual Arabic + 58mm style', () async {
    // The Arabic font is fetched lazily and falls back to Latin offline, so this
    // must still yield a valid PDF without a network connection (e.g. in CI).
    final bytes = await buildReceiptPdf(
      _demoSale(),
      _settings(style: PrintStyle.withArabic, width: PaperWidth.mm58),
    );
    expect(bytes.sublist(0, 4), [0x25, 0x50, 0x44, 0x46]);
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
    final bytes = await buildReceiptPdf(sale, _settings());
    expect(bytes.sublist(0, 4), [0x25, 0x50, 0x44, 0x46]);
  });

  test('buildReceiptPdf renders png, svg and garbage logos safely', () async {
    for (final logo in [_pngLogo, _svgLogo, Uint8List.fromList([1, 2, 3])]) {
      final bytes = await buildReceiptPdf(_demoSale(), _settings(logo: logo));
      expect(bytes.sublist(0, 4), [0x25, 0x50, 0x44, 0x46]);
    }
  });

  test('buildReceiptPdf renders the sale note when switched on, and skips it by default', () async {
    final sale = Sale.fromJson({
      'id': '9', 'invoice_no': 'INV-0009', 'date': '2026-06-14', 'status': 'completed', 'branch': 'Downtown',
      'customer': {'name': 'Walk-in', 'mobile': ''},
      'items': [
        {'name': 'Signature Cut', 'type': 'service', 'employee': 'Maya', 'quantity': 1, 'unit_price': 45, 'discount': 0, 'total': 45},
      ],
      'payments': [{'method': 'Cash', 'amount': 45}],
      'summary': {'gross_amount': 45, 'item_discount': 0, 'other_discount': 0, 'tax_amount': 0, 'paid': 45},
      'notes': 'Gift wrap, collect Friday',
      'created_by': 'Maya',
    });
    // The Office bill is a fixed compact layout that never carries the note.
    for (final style in PrintStyle.values.where((s) => s != PrintStyle.officeBill)) {
      final withNote = await buildReceiptPdf(sale, _settings(style: style, showNotes: true));
      final withoutNote = await buildReceiptPdf(sale, _settings(style: style));
      expect(withNote.sublist(0, 4), [0x25, 0x50, 0x44, 0x46]);
      expect(withNote.length, greaterThan(withoutNote.length), reason: 'the note box adds content');
    }
  });

  test('buildReceiptPdf renders the compact Office bill with the biller\'s Bill No', () async {
    final sale = Sale.fromJson({
      ..._demoSaleJson,
      'bill_no': '9/1',
      'created_at': '2026-10-24T13:52:00+03:00',
    });
    final settings = PrintSettings(
      style: PrintStyle.officeBill,
      width: PaperWidth.mm80,
      showDiscount: true,
      showTotalQty: true,
      showBarcode: true,
      footerEnglish: 'Thank you!',
      footerArabic: 'شكرا لك',
      companyName: 'Talent Saloon',
    );

    final office = await buildReceiptPdf(sale, settings);
    final full = await buildReceiptPdf(sale, _settings(style: PrintStyle.withArabic));

    expect(office.sublist(0, 4), [0x25, 0x50, 0x44, 0x46]);
    expect(office.length, lessThan(full.length), reason: 'no barcode, payments or footer on the office bill');
  });

  test('Office bill falls back to the invoice reference for an unsynced sale', () async {
    final bytes = await buildReceiptPdf(
      Sale.fromJson({..._demoSaleJson, 'pending': true}),
      _settings(style: PrintStyle.officeBill, width: PaperWidth.mm58),
    );
    expect(bytes.sublist(0, 4), [0x25, 0x50, 0x44, 0x46]);
  });

  test('Sale and print config read the office bill fields', () {
    expect(Sale.fromJson({..._demoSaleJson, 'bill_no': '9/3'}).billNo, '9/3');
    expect(PrintStyle.fromKey('office_bill'), PrintStyle.officeBill);
    expect(PrintStyle.officeBill.isArabic, isFalse);
  });

  test('buildReceiptPdf renders the company name header', () async {
    final bytes = await buildReceiptPdf(_demoSale(), _settings(companyName: 'Astra Beauty Lounge'));
    expect(bytes.sublist(0, 4), [0x25, 0x50, 0x44, 0x46]);
  });
}
