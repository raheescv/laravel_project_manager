import 'dart:typed_data';

import 'package:flutter/services.dart' show rootBundle;
import 'package:pdf/pdf.dart';
import 'package:pdf/widgets.dart' as pw;

import '../../core/formatters.dart';
import '../../models/models.dart';
import '../../state/print_settings_controller.dart';

/// Arabic translations of the receipt labels — kept in lock-step with the web
/// `lang.*` (ar) strings used by resources/views/sale/print.blade.php so the
/// bilingual mobile receipt reads identically to the printed web invoice.
const _ar = {
  'invoice': 'فاتورة',
  'invoice_no': 'رقم الفاتورة',
  'date': 'التاريخ',
  'customer': 'العميل',
  'payment_mode': 'طريقة الدفع',
  'price': 'السعر',
  'quantity': 'الكمية',
  'amount': 'المبلغ',
  'item': 'الصنف',
  'total_quantity': 'إجمالي الكمية',
  'net_value': 'القيمة الصافية',
  'discount': 'خصم',
  'tax': 'ضريبة',
  'total': 'المجموع',
  'paid': 'المدفوع',
  'balance': 'الرصيد',
  'served_by': 'خدم بواسطة',
};

// Bundled IBM Plex Sans Arabic (assets/fonts) — loaded once and reused. Lets
// Arabic receipts print at the POS without a network round-trip.
pw.Font? _arabicFont;
pw.Font? _arabicFontBold;

Future<void> _loadArabicFonts() async {
  if (_arabicFont != null) return;
  try {
    _arabicFont = pw.Font.ttf(await rootBundle.load('assets/fonts/IBMPlexSansArabic-Regular.ttf'));
    _arabicFontBold = pw.Font.ttf(await rootBundle.load('assets/fonts/IBMPlexSansArabic-Bold.ttf'));
  } catch (_) {
    // Asset unavailable (e.g. a stripped build) — fall back to Latin so the
    // English content still prints.
    _arabicFont = null;
    _arabicFontBold = null;
  }
}

/// Build a printable / shareable thermal receipt for a [Sale] that mirrors the
/// web sale-print design (resources/views/sale/print.blade.php): bordered
/// tables, the boxed invoice header, served-by box, barcode + QR and dashed
/// dividers — honouring the in-app [PrintSettings].
Future<Uint8List> buildReceiptPdf(Sale sale, PrintSettings settings) async {
  final ar = settings.style.isArabic;
  if (ar) await _loadArabicFonts();

  // Bilingual receipts use IBM Plex Sans Arabic as the BASE font — it carries
  // Latin glyphs too. This matters: as a *fallback* font the pdf package
  // mis-shapes RTL Arabic (letters reversed and disconnected); as the *base*
  // font it joins and shapes correctly. English-only receipts keep Helvetica.
  final theme = ar && _arabicFont != null
      ? pw.ThemeData.withFont(base: _arabicFont!, bold: _arabicFontBold ?? _arabicFont!)
      : pw.ThemeData.withFont(base: pw.Font.helvetica(), bold: pw.Font.helveticaBold());

  // Always render at the printer's native thermal width — print on the actual
  // 80mm/57mm roll, never scaled to the OS-reported paper (e.g. A4), which would
  // shift the centred header off the printable area. On-screen sizing is handled
  // separately by the in-app PdfPreview.
  final is58 = settings.width == PaperWidth.mm58;
  final format = is58 ? PdfPageFormat.roll57 : PdfPageFormat.roll80;
  final pad = is58 ? 6.0 : 10.0;
  final k = is58 ? 0.84 : 1.0;
  double s(double v) => v * k;

  final invoiceNo = sale.invoiceNo.isEmpty ? '#${sale.id}' : sale.invoiceNo;
  final codeData = sale.invoiceNo.isEmpty ? sale.id : sale.invoiceNo;

  final netValue = sale.grossAmount;
  final discount = sale.discount;
  final tax = sale.taxAmount;
  final grandTotal = netValue - discount + tax;
  final balance = grandTotal - sale.paid;
  final totalQty = sale.lines.fold<double>(0, (t, l) => t + l.quantity);

  final doc = pw.Document(title: 'Invoice $invoiceNo');

  doc.addPage(
    pw.Page(
      pageFormat: format,
      theme: theme,
      margin: pw.EdgeInsets.all(pad),
      build: (context) => pw.Column(
        crossAxisAlignment: pw.CrossAxisAlignment.stretch,
        mainAxisSize: pw.MainAxisSize.min,
        children: [
          // ---- store header ----
          pw.Center(
            child: pw.Text((sale.branch.isEmpty ? 'INVO' : sale.branch).toUpperCase(),
                style: pw.TextStyle(fontSize: s(14), fontWeight: pw.FontWeight.bold, letterSpacing: 0.4)),
          ),
          _dashed(),
          // ---- invoice header box (left accent) ----
          pw.Container(
            decoration: const pw.BoxDecoration(
              border: pw.Border(left: pw.BorderSide(width: 2, color: PdfColors.black)),
            ),
            padding: const pw.EdgeInsets.fromLTRB(6, 4, 4, 4),
            child: pw.Center(
              // English + Arabic as separate, correctly-directioned spans — a
              // single mixed string with no rtl direction reverses the Arabic.
              child: ar
                  ? pw.Row(
                      mainAxisSize: pw.MainAxisSize.min,
                      mainAxisAlignment: pw.MainAxisAlignment.center,
                      children: [
                        pw.Text('INVOICE  |  ',
                            style: pw.TextStyle(fontSize: s(12), fontWeight: pw.FontWeight.bold)),
                        pw.Text(_ar['invoice']!,
                            textDirection: pw.TextDirection.rtl,
                            style: pw.TextStyle(fontSize: s(12), fontWeight: pw.FontWeight.bold)),
                      ],
                    )
                  : pw.Text('INVOICE',
                      style: pw.TextStyle(fontSize: s(12), fontWeight: pw.FontWeight.bold)),
            ),
          ),
          pw.SizedBox(height: 6),
          // ---- meta ----
          _metaTable(sale, invoiceNo, ar, s),
          _dashed(),
          // ---- items ----
          _itemsTable(sale, ar, s),
          _dashed(),
          // ---- totals ----
          _totalsTable(
            ar: ar,
            s: s,
            showTotalQty: settings.showTotalQty,
            showDiscount: settings.showDiscount,
            totalQty: totalQty,
            netValue: netValue,
            discount: discount,
            tax: tax,
            grandTotal: grandTotal,
            paid: sale.paid,
            balance: balance,
          ),
          // ---- barcode + qr ----
          if (settings.showBarcode) ...[
            pw.SizedBox(height: 8),
            _codes(codeData, s),
          ],
          pw.SizedBox(height: 8),
          // ---- served by ----
          _servedBy(sale, ar, s),
          // ---- footer ----
          _dashed(),
          if (settings.footerEnglish.trim().isNotEmpty)
            pw.Center(
              child: pw.Text(settings.footerEnglish.trim(),
                  textAlign: pw.TextAlign.center,
                  style: pw.TextStyle(fontSize: s(8), fontWeight: pw.FontWeight.bold)),
            ),
          if (ar && settings.footerArabic.trim().isNotEmpty) ...[
            pw.SizedBox(height: 3),
            pw.Center(
              child: pw.Text(settings.footerArabic.trim(),
                  textAlign: pw.TextAlign.center,
                  textDirection: pw.TextDirection.rtl,
                  style: pw.TextStyle(fontSize: s(8), fontWeight: pw.FontWeight.bold)),
            ),
          ],
          pw.SizedBox(height: 5),
          pw.Center(
            child: pw.Text('Powered by Invo',
                style: pw.TextStyle(fontSize: s(6), color: PdfColors.grey600)),
          ),
        ],
      ),
    ),
  );

  return doc.save();
}

// ---- meta table ----------------------------------------------------------

pw.Widget _metaTable(Sale sale, String invoiceNo, bool ar, double Function(double) s) {
  final rows = <List<String>>[
    ['Invoice No', invoiceNo, _ar['invoice_no']!],
    ['Date', Dates.human(sale.date), _ar['date']!],
    if (sale.customerName.isNotEmpty) ['Customer', sale.customerName, _ar['customer']!],
    if (sale.payments.isNotEmpty)
      ['Payment Mode', sale.payments.map((p) => p.method).join(', '), _ar['payment_mode']!],
  ];

  return pw.Table(
    border: pw.TableBorder.all(width: 0.5, color: PdfColors.black),
    columnWidths: ar
        ? {0: const pw.FlexColumnWidth(1.1), 1: const pw.FlexColumnWidth(1.5), 2: const pw.FlexColumnWidth(1.1)}
        : {0: const pw.FlexColumnWidth(1), 1: const pw.FlexColumnWidth(1.7)},
    children: [
      for (final r in rows)
        pw.TableRow(
          children: [
            _cell(r[0], s(8)),
            _cell(r[1], s(8)),
            if (ar) _cell(r[2], s(8), align: pw.Alignment.centerRight, rtl: true),
          ],
        ),
    ],
  );
}

// ---- items table ---------------------------------------------------------

pw.Widget _itemsTable(Sale sale, bool ar, double Function(double) s) {
  return pw.Table(
    border: pw.TableBorder.all(width: 0.5, color: PdfColors.black),
    columnWidths: {
      0: const pw.FlexColumnWidth(2.7),
      1: const pw.FlexColumnWidth(0.8),
      2: const pw.FlexColumnWidth(1.1),
      3: const pw.FlexColumnWidth(1.2),
    },
    children: [
      // header
      pw.TableRow(
        children: [
          _hcell('Item', ar ? _ar['item'] : null, s(8)),
          _hcell('Qty', ar ? _ar['quantity'] : null, s(8), align: pw.Alignment.center),
          _hcell('Price', ar ? _ar['price'] : null, s(8), align: pw.Alignment.centerRight),
          _hcell('Amount', ar ? _ar['amount'] : null, s(8), align: pw.Alignment.centerRight),
        ],
      ),
      for (var i = 0; i < sale.lines.length; i++)
        pw.TableRow(
          children: [
            // item name (+ Arabic name beneath, right-aligned)
            pw.Padding(
              padding: const pw.EdgeInsets.symmetric(horizontal: 3, vertical: 2),
              child: pw.Column(
                crossAxisAlignment: pw.CrossAxisAlignment.stretch,
                children: [
                  pw.Text('${i + 1}. ${sale.lines[i].name}',
                      style: pw.TextStyle(fontSize: s(8), fontWeight: pw.FontWeight.bold)),
                  if (ar && sale.lines[i].nameArabic.isNotEmpty)
                    pw.Text(sale.lines[i].nameArabic,
                        textAlign: pw.TextAlign.right,
                        textDirection: pw.TextDirection.rtl,
                        style: pw.TextStyle(fontSize: s(8), fontWeight: pw.FontWeight.bold)),
                ],
              ),
            ),
            _cell(_num(sale.lines[i].quantity), s(8), align: pw.Alignment.center),
            _cell(_amt(sale.lines[i].unitPrice), s(8), align: pw.Alignment.centerRight),
            _cell(_amt(sale.lines[i].total), s(8), align: pw.Alignment.centerRight),
          ],
        ),
    ],
  );
}

// ---- totals table --------------------------------------------------------

pw.Widget _totalsTable({
  required bool ar,
  required double Function(double) s,
  required bool showTotalQty,
  required bool showDiscount,
  required double totalQty,
  required double netValue,
  required double discount,
  required double tax,
  required double grandTotal,
  required double paid,
  required double balance,
}) {
  // (english label, value, arabic label, bold)
  final rows = <List<dynamic>>[
    if (showTotalQty) ['Total Qty', _num(totalQty), _ar['total_quantity'], false],
    ['Net Value', Money.of(netValue), _ar['net_value'], false],
    if (showDiscount && discount != 0) ['Discount', '- ${Money.of(discount)}', _ar['discount'], false],
    if (tax != 0) ['Tax', Money.of(tax), _ar['tax'], false],
    ['Total', Money.of(grandTotal), _ar['total'], true],
    ['Paid', Money.of(paid), _ar['paid'], false],
    if (balance.abs() >= 0.005) ['Balance', Money.of(balance), _ar['balance'], false],
  ];

  return pw.Table(
    border: pw.TableBorder.all(width: 0.5, color: PdfColors.black),
    columnWidths: ar
        ? {0: const pw.FlexColumnWidth(1.3), 1: const pw.FlexColumnWidth(1.4), 2: const pw.FlexColumnWidth(1.3)}
        : {0: const pw.FlexColumnWidth(1.4), 1: const pw.FlexColumnWidth(1.3)},
    children: [
      for (final r in rows)
        pw.TableRow(
          children: [
            _cell(r[0] as String, s(r[3] == true ? 9 : 8), bold: true),
            _cell(r[1] as String, s(r[3] == true ? 9 : 8), bold: true, align: pw.Alignment.centerRight),
            if (ar) _cell(r[2] as String, s(r[3] == true ? 9 : 8), bold: true, align: pw.Alignment.centerRight, rtl: true),
          ],
        ),
    ],
  );
}

// ---- barcode + qr --------------------------------------------------------

pw.Widget _codes(String data, double Function(double) s) => pw.Row(
      crossAxisAlignment: pw.CrossAxisAlignment.center,
      children: [
        pw.Expanded(
          child: pw.Container(
            decoration: pw.BoxDecoration(border: pw.Border.all(width: 0.5, color: PdfColors.black)),
            padding: const pw.EdgeInsets.all(4),
            child: pw.Column(
              children: [
                pw.BarcodeWidget(
                  barcode: pw.Barcode.code128(),
                  data: data,
                  height: s(34),
                  drawText: false,
                ),
                pw.SizedBox(height: 2),
                pw.Text(data, style: pw.TextStyle(fontSize: s(8), fontWeight: pw.FontWeight.bold)),
              ],
            ),
          ),
        ),
        pw.SizedBox(width: 8),
        pw.BarcodeWidget(barcode: pw.Barcode.qrCode(), data: data, width: s(52), height: s(52)),
      ],
    );

// ---- served-by box -------------------------------------------------------

pw.Widget _servedBy(Sale sale, bool ar, double Function(double) s) => pw.Container(
      decoration: pw.BoxDecoration(border: pw.Border.all(width: 0.5, color: PdfColors.black)),
      padding: const pw.EdgeInsets.all(5),
      child: pw.Column(
        crossAxisAlignment: pw.CrossAxisAlignment.stretch,
        children: [
          pw.Row(
            children: [
              pw.Expanded(
                child: pw.Text('Served By: ${sale.createdBy.isEmpty ? '-' : sale.createdBy}',
                    style: pw.TextStyle(fontSize: s(9), fontWeight: pw.FontWeight.bold)),
              ),
              if (ar)
                pw.Text('${_ar['served_by']} :',
                    textDirection: pw.TextDirection.rtl,
                    style: pw.TextStyle(fontSize: s(9), fontWeight: pw.FontWeight.bold)),
            ],
          ),
          pw.SizedBox(height: 2),
          pw.Text(Dates.human(sale.date),
              style: pw.TextStyle(fontSize: s(8), fontWeight: pw.FontWeight.bold)),
        ],
      ),
    );

// ---- shared cell / divider helpers ---------------------------------------

pw.Widget _cell(String text, double size,
        {bool bold = true, pw.Alignment align = pw.Alignment.centerLeft, bool rtl = false}) =>
    pw.Container(
      alignment: align,
      padding: const pw.EdgeInsets.symmetric(horizontal: 3, vertical: 2),
      child: pw.Text(
        text,
        textDirection: rtl ? pw.TextDirection.rtl : null,
        textAlign: align == pw.Alignment.centerRight
            ? pw.TextAlign.right
            : (align == pw.Alignment.center ? pw.TextAlign.center : pw.TextAlign.left),
        style: pw.TextStyle(fontSize: size, fontWeight: bold ? pw.FontWeight.bold : pw.FontWeight.normal),
      ),
    );

pw.Widget _hcell(String en, String? ar, double size, {pw.Alignment align = pw.Alignment.centerLeft}) {
  final textAlign = align == pw.Alignment.centerRight
      ? pw.TextAlign.right
      : (align == pw.Alignment.center ? pw.TextAlign.center : pw.TextAlign.left);
  final cross = align == pw.Alignment.centerRight
      ? pw.CrossAxisAlignment.end
      : (align == pw.Alignment.center ? pw.CrossAxisAlignment.center : pw.CrossAxisAlignment.start);
  final style = pw.TextStyle(fontSize: size, fontWeight: pw.FontWeight.bold);
  return pw.Container(
    alignment: align,
    padding: const pw.EdgeInsets.symmetric(horizontal: 3, vertical: 3),
    // English and Arabic stacked as separate Texts — the Arabic line marked rtl
    // so it shapes/joins instead of rendering reversed.
    child: pw.Column(
      crossAxisAlignment: cross,
      children: [
        pw.Text(en, textAlign: textAlign, style: style),
        if (ar != null) pw.Text(ar, textDirection: pw.TextDirection.rtl, textAlign: textAlign, style: style),
      ],
    ),
  );
}

pw.Widget _dashed() => pw.Padding(
      padding: const pw.EdgeInsets.symmetric(vertical: 5),
      child: pw.Divider(height: 0.5, thickness: 0.7, color: PdfColors.black, borderStyle: pw.BorderStyle.dashed),
    );

String _num(double q) {
  if (q % 1 == 0) return q.toStringAsFixed(0);
  return q.toStringAsFixed(3).replaceFirst(RegExp(r'0+$'), '').replaceFirst(RegExp(r'\.$'), '');
}

String _amt(double v) => v.toStringAsFixed(Money.decimals);
