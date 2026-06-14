import 'dart:typed_data';

import 'package:pdf/pdf.dart';
import 'package:pdf/widgets.dart' as pw;

import '../../core/formatters.dart';
import '../../models/models.dart';

/// Build a printable / shareable receipt for a [Sale] as an 80mm roll PDF —
/// the same content shown on the Invoice screen, laid out like a POS receipt.
Future<Uint8List> buildReceiptPdf(Sale sale) async {
  final doc = pw.Document(title: 'Invo receipt');
  final invoiceNo = sale.invoiceNo.isEmpty ? '#${sale.id}' : sale.invoiceNo;

  doc.addPage(
    pw.Page(
      pageFormat: PdfPageFormat.roll80,
      margin: const pw.EdgeInsets.all(14),
      build: (context) => pw.Column(
        crossAxisAlignment: pw.CrossAxisAlignment.stretch,
        mainAxisSize: pw.MainAxisSize.min,
        children: [
          pw.Center(
            child: pw.Text('INVO',
                style: pw.TextStyle(fontSize: 22, fontWeight: pw.FontWeight.bold, letterSpacing: 4)),
          ),
          pw.SizedBox(height: 2),
          pw.Center(
            child: pw.Text(sale.branch.isEmpty ? 'Salon POS' : sale.branch,
                style: const pw.TextStyle(fontSize: 8, color: PdfColors.grey700)),
          ),
          pw.SizedBox(height: 10),
          _kv('Invoice', invoiceNo),
          _kv('Date', Dates.human(sale.date)),
          if (sale.customerName.isNotEmpty) _kv('Customer', sale.customerName),
          if (sale.createdBy.isNotEmpty) _kv('Stylist', sale.createdBy),
          _divider(),
          for (final l in sale.lines)
            pw.Padding(
              padding: const pw.EdgeInsets.only(bottom: 5),
              child: pw.Column(
                crossAxisAlignment: pw.CrossAxisAlignment.stretch,
                children: [
                  pw.Row(
                    crossAxisAlignment: pw.CrossAxisAlignment.start,
                    children: [
                      pw.Expanded(child: pw.Text(l.name, style: const pw.TextStyle(fontSize: 9))),
                      pw.SizedBox(width: 6),
                      pw.Text(Money.of(l.total),
                          style: pw.TextStyle(fontSize: 9, fontWeight: pw.FontWeight.bold)),
                    ],
                  ),
                  pw.Text(
                    '${_qty(l.quantity)} x ${Money.of(l.unitPrice)}${l.employee.isEmpty ? '' : '   ${l.employee}'}',
                    style: const pw.TextStyle(fontSize: 7, color: PdfColors.grey700),
                  ),
                ],
              ),
            ),
          _divider(),
          _amount('Subtotal', Money.of(sale.grossAmount)),
          if (sale.discount > 0) _amount('Discount', '- ${Money.of(sale.discount)}'),
          _amount('Tax', Money.of(sale.taxAmount)),
          pw.SizedBox(height: 6),
          _amount('TOTAL PAID', Money.of(sale.paid), bold: true, size: 12),
          if (sale.payments.isNotEmpty) ...[
            pw.SizedBox(height: 5),
            pw.Text('Paid by ${sale.payments.map((p) => p.method).join(', ')}',
                style: const pw.TextStyle(fontSize: 8, color: PdfColors.grey800)),
          ],
          _divider(),
          pw.SizedBox(height: 4),
          pw.Center(child: pw.Text('Thank you!', style: const pw.TextStyle(fontSize: 9))),
          pw.SizedBox(height: 2),
          pw.Center(
            child: pw.Text('Powered by Invo',
                style: const pw.TextStyle(fontSize: 6, color: PdfColors.grey600)),
          ),
        ],
      ),
    ),
  );

  return doc.save();
}

String _qty(double q) => q % 1 == 0 ? q.toStringAsFixed(0) : q.toStringAsFixed(1);

pw.Widget _kv(String label, String value) => pw.Padding(
      padding: const pw.EdgeInsets.only(bottom: 2),
      child: pw.Row(
        mainAxisAlignment: pw.MainAxisAlignment.spaceBetween,
        children: [
          pw.Text(label, style: const pw.TextStyle(fontSize: 8, color: PdfColors.grey700)),
          pw.Text(value, style: pw.TextStyle(fontSize: 8, fontWeight: pw.FontWeight.bold)),
        ],
      ),
    );

pw.Widget _amount(String label, String value, {bool bold = false, double size = 9}) => pw.Padding(
      padding: const pw.EdgeInsets.only(bottom: 3),
      child: pw.Row(
        mainAxisAlignment: pw.MainAxisAlignment.spaceBetween,
        children: [
          pw.Text(label,
              style: pw.TextStyle(fontSize: size, fontWeight: bold ? pw.FontWeight.bold : pw.FontWeight.normal)),
          pw.Text(value,
              style: pw.TextStyle(fontSize: size, fontWeight: bold ? pw.FontWeight.bold : pw.FontWeight.normal)),
        ],
      ),
    );

pw.Widget _divider() => pw.Padding(
      padding: const pw.EdgeInsets.symmetric(vertical: 6),
      child: pw.Divider(height: 0.5, color: PdfColors.grey400, borderStyle: pw.BorderStyle.dashed),
    );
