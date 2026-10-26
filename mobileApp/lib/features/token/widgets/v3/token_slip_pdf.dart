import 'dart:typed_data';

import 'package:intl/intl.dart';
import 'package:pdf/pdf.dart';
import 'package:pdf/widgets.dart' as pw;

import 'package:invo/features/token/domain/models/token_models.dart';
import 'package:invo/features/token/widgets/v3/token_slip_view.dart';
import 'package:invo/shared/domain/models/print_settings.dart';
import 'package:invo/shared/widgets/receipt_pdf.dart';

/// The printable token slip: the same layout as [TokenSlipView], at the paired
/// roll's native width. It goes out through `printRollPdf`, which handles
/// direct ESC/POS, the OS printer and the dialog fallback.
Future<Uint8List> buildTokenPdf(
  TokenSlip slip,
  TokenSettings settings, {
  required PaperWidth paper,
  Uint8List? logo,
  String companyName = '',
}) async {
  // IBM Plex Sans Arabic as the BASE font: it carries Latin too, and Arabic only
  // shapes correctly from the base font, never as a fallback.
  final (arabic, arabicBold) = await loadBundledArabicFonts();
  final theme = arabic != null
      ? pw.ThemeData.withFont(base: arabic, bold: arabicBold ?? arabic)
      : pw.ThemeData.withFont(base: pw.Font.helvetica(), bold: pw.Font.helveticaBold());

  final narrow = paper == PaperWidth.mm58;
  final format = narrow ? PdfPageFormat.roll57 : PdfPageFormat.roll80;
  final k = narrow ? 0.84 : 1.0;
  final bold = pw.FontWeight.bold;
  final name = companyName.trim();
  final header = settings.header.trim();
  final footerEn = settings.footerEnglish.trim();
  final footerAr = settings.footerArabic.trim();
  final counterName = slip.counterName.trim();

  pw.Widget? brand;
  if (settings.showLogo) {
    brand = pdfLogo(logo, width: 70 * k);
    if (brand == null && name.isNotEmpty) {
      brand = pw.Text(name, maxLines: 2, style: pw.TextStyle(fontSize: 12 * k, fontWeight: bold, letterSpacing: 1));
    }
  }

  final doc = pw.Document(title: 'Token ${slip.displayNo}');
  doc.addPage(
    pw.Page(
      pageFormat: format,
      theme: theme,
      margin: pw.EdgeInsets.fromLTRB(narrow ? 6 : 10, 8, narrow ? 6 : 10, 6),
      build: (_) => pw.Column(
        crossAxisAlignment: pw.CrossAxisAlignment.stretch,
        mainAxisSize: pw.MainAxisSize.min,
        children: [
          if (brand != null || settings.showDateTime) ...[
            pw.Row(
              crossAxisAlignment: pw.CrossAxisAlignment.start,
              children: [
                pw.Expanded(child: pw.Align(alignment: pw.Alignment.centerLeft, child: brand ?? pw.SizedBox())),
                if (settings.showDateTime)
                  pw.Text(
                      '${DateFormat('dd MMM yyyy').format(slip.issuedAt)}\n${DateFormat('hh:mm a').format(slip.issuedAt)}',
                      textAlign: pw.TextAlign.right,
                      style: pw.TextStyle(fontSize: 8.5 * k, lineSpacing: 1.5)),
              ],
            ),
            pw.SizedBox(height: 6 * k),
          ],
          pw.Container(height: 1.6, color: PdfColors.black),
          if (header.isNotEmpty)
            pw.Padding(
              padding: pw.EdgeInsets.only(top: 6 * k),
              child: pw.Text(header, textAlign: pw.TextAlign.center, style: pw.TextStyle(fontSize: 9 * k)),
            ),
          pw.SizedBox(height: 7 * k),
          pw.Row(
            children: [
              pw.Text(kTokenLabelEn, style: pw.TextStyle(fontSize: 9.5 * k, fontWeight: bold, letterSpacing: 2.4 * k)),
              pw.Spacer(),
              pw.Text(kTokenLabelAr,
                  textDirection: pw.TextDirection.rtl, style: pw.TextStyle(fontSize: 10 * k, fontWeight: bold)),
            ],
          ),
          pw.SizedBox(height: 2 * k),
          pw.FittedBox(
            fit: pw.BoxFit.scaleDown,
            child: pw.Text(slip.displayNo,
                style: pw.TextStyle(
                    fontSize: (narrow ? 46 : 60) * settings.numberSize.scale, fontWeight: bold, letterSpacing: -0.5)),
          ),
          if (counterName.isNotEmpty)
            pw.Center(
                child: pw.Text(counterName.toUpperCase(),
                    style: pw.TextStyle(fontSize: 10.5 * k, fontWeight: bold, letterSpacing: 0.8))),
          if (footerEn.isNotEmpty || footerAr.isNotEmpty) ...[
            pw.SizedBox(height: 8 * k),
            pw.Divider(height: 1, thickness: 1, borderStyle: pw.BorderStyle.dashed),
            pw.SizedBox(height: 5 * k),
            if (footerEn.isNotEmpty)
              pw.Text(footerEn, textAlign: pw.TextAlign.center, style: pw.TextStyle(fontSize: 8.5 * k)),
            if (footerAr.isNotEmpty)
              pw.Text(footerAr,
                  textAlign: pw.TextAlign.center,
                  textDirection: pw.TextDirection.rtl,
                  style: pw.TextStyle(fontSize: 9 * k)),
          ],
        ],
      ),
    ),
  );
  return doc.save();
}
