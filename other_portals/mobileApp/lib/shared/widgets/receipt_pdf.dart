import 'dart:convert';
import 'dart:typed_data';

import 'package:flutter/services.dart' show rootBundle;
import 'package:intl/intl.dart';
import 'package:pdf/pdf.dart';
import 'package:pdf/widgets.dart' as pw;

import 'package:invo/shared/domain/helpers/formatters.dart';
import 'package:invo/shared/domain/models/index.dart';

/// Arabic translations of the receipt labels — kept in lock-step with the web
/// `lang.*` (ar) strings used by resources/views/sale/print.blade.php so the
/// bilingual mobile receipt reads identically to the printed web invoice.
const _ar = {
  'invoice': 'فاتورة',
  'invoice_no': 'رقم الفاتورة',
  // Matches the wording of `provisional` below, which the customer's original
  // offline copy was printed under.
  'offline_ref': 'المرجع المؤقت',
  'date': 'التاريخ',
  'customer': 'العميل',
  'mobile': 'الجوال',
  'payment_mode': 'طريقة الدفع',
  'price': 'السعر',
  'quantity': 'الكمية',
  'amount': 'المبلغ',
  'item': 'الصنف',
  'total_quantity': 'إجمالي الكمية',
  'net_value': 'القيمة الصافية',
  'discount': 'خصم',
  'tax': 'ضريبة',
  'round_off': 'التقريب',
  'total': 'المجموع',
  'paid': 'المدفوع',
  'balance': 'الرصيد',
  'card_balance': 'رصيد البطاقة',
  'served_by': 'خدم بواسطة',
  'notes': 'ملاحظات',
  'provisional': 'إيصال مؤقت — رقم الفاتورة الضريبية لاحقاً',
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

/// The bundled IBM Plex Sans Arabic pair for on-device PDFs other than the
/// receipt — the A4 report export. Either is null when the asset is missing.
Future<(pw.Font?, pw.Font?)> loadBundledArabicFonts() async {
  await _loadArabicFonts();
  return (_arabicFont, _arabicFontBold);
}

/// Build a printable / shareable thermal receipt for a [Sale] — a copy of the
/// web invoice (resources/views/sale/print.blade.php) block for block: store
/// header with branch contact, the boxed INVOICE title, bordered meta table
/// with payment badges, item rows (name, description, options, Arabic name,
/// then # | Price | Qty | Amount), totals with the currency code, the "You
/// saved" box, notes, barcode + QR, served-by box and footer with the print
/// time. Sizes are the web's CSS pixels at 0.75pt each; the web Sale
/// Configuration decides what shows ([PrintSettings]).
Future<Uint8List> buildReceiptPdf(Sale sale, PrintSettings settings) async {
  final ar = settings.style.isArabic;
  // Always load the bundled font: Arabic receipts use it as the base font, and
  // English receipts use it as a glyph fallback (see below).
  await _loadArabicFonts();

  // Bilingual receipts use IBM Plex Sans Arabic as the BASE font — it carries
  // Latin glyphs too. This matters: as a *fallback* font the pdf package
  // mis-shapes RTL Arabic (letters reversed and disconnected); as the *base*
  // font it joins and shapes correctly. English-only receipts keep Helvetica
  // (the web prints Arial), but register IBM Plex as a *fallback* so currency
  // symbols outside Helvetica's built-in set (₹, €, ﷼, …) still render.
  // The item description prints in italics: IBM Plex has no italic face, so
  // the bilingual receipt sets it upright rather than in an unshaped fallback.
  final theme = (ar || settings.style == PrintStyle.officeBill) && _arabicFont != null
      ? pw.ThemeData.withFont(
          base: _arabicFont!,
          bold: _arabicFontBold ?? _arabicFont!,
          italic: _arabicFont!,
          boldItalic: _arabicFontBold ?? _arabicFont!,
        )
      : pw.ThemeData.withFont(
          base: pw.Font.helvetica(),
          bold: pw.Font.helveticaBold(),
          italic: pw.Font.helveticaOblique(),
          boldItalic: pw.Font.helveticaBoldOblique(),
          fontFallback: [
            if (_arabicFont != null) _arabicFont!,
            if (_arabicFontBold != null) _arabicFontBold!,
          ],
        );

  // Always render at the printer's native thermal width — print on the actual
  // 80mm/57mm roll, never scaled to the OS-reported paper (e.g. A4).
  final is58 = settings.width == PaperWidth.mm58;
  final format = is58 ? PdfPageFormat.roll57 : PdfPageFormat.roll80;
  final pad = is58 ? 6.0 : 10.0;
  final k = is58 ? 0.84 : 1.0;
  double s(double v) => v * k;

  final invoiceNo = sale.invoiceNo.isEmpty ? '#${sale.id}' : sale.invoiceNo;

  if (settings.style == PrintStyle.officeBill) {
    final grandTotal = sale.grossAmount - sale.discount + sale.taxAmount + sale.roundOff;
    return _buildOfficeBill(sale, settings, theme, format, pad, s, invoiceNo, grandTotal);
  }

  final r = _ReceiptText(sale, settings, s);
  final info = sale.receipt;
  final logo = pdfLogo(settings.logo, width: format.availableWidth - pad * 2);
  final branch = (info?.branchLocation.trim().isNotEmpty ?? false) ? info!.branchLocation.trim() : sale.branch.trim();

  final doc = pw.Document(title: 'Customer Invoice');
  doc.addPage(
    pw.Page(
      pageFormat: format,
      theme: theme,
      margin: pw.EdgeInsets.all(pad),
      build: (context) => pw.Column(
        crossAxisAlignment: pw.CrossAxisAlignment.stretch,
        mainAxisSize: pw.MainAxisSize.min,
        children: [
          // ---- store info ----
          if (logo != null) pw.Padding(padding: pw.EdgeInsets.only(bottom: r.px(3)), child: pw.Center(child: logo)),
          if (settings.companyName.trim().isNotEmpty) r.h3(settings.companyName.trim()),
          if (branch.isNotEmpty) r.h3(branch),
          if (info != null && (info.branchMobile.isNotEmpty || info.branchEmail.isNotEmpty || info.gstNo.isNotEmpty))
            pw.Padding(
              padding: pw.EdgeInsets.only(top: r.px(2)),
              child: pw.Column(children: [
                if (info.branchMobile.isNotEmpty) r.centred('Mobile: ${info.branchMobile}', 13, bold: true),
                if (info.branchEmail.isNotEmpty) r.labelled('Email:', info.branchEmail, 13),
                if (info.gstNo.isNotEmpty) r.labelled('GST:', info.gstNo, 13),
              ]),
            ),
          pw.SizedBox(height: r.px(8)),
          r.divider(),
          // ---- invoice title, accented on the left ----
          pw.Container(
            margin: pw.EdgeInsets.only(bottom: r.px(8)),
            padding: pw.EdgeInsets.all(r.px(5)),
            decoration: const pw.BoxDecoration(border: pw.Border(left: pw.BorderSide(width: 1.5))),
            child: pw.Center(
              child: pw.Row(mainAxisSize: pw.MainAxisSize.min, children: [
                r.text('INVOICE', 14, bold: true, spacing: 0.3),
                if (ar) ...[r.text('  |  ', 14, bold: true), r.text(_ar['invoice']!, 14, bold: true, rtl: true)],
              ]),
            ),
          ),
          // ---- meta ----
          ...(ar ? _metaArabic(sale, invoiceNo, r) : [_metaEnglish(sale, invoiceNo, r)]),
          r.divider(),
          // ---- items ----
          _itemsTable(sale, ar, r, settings),
          r.divider(),
          // ---- totals ----
          _totalsTable(sale, ar, r, settings),
          // ---- you saved ----
          if (settings.showDiscount && sale.discount > 0) _savedBox(sale.discount, ar, r),
          // ---- sale note (web `enable_notes_in_print`) ----
          if (settings.showNotes && sale.notes.trim().isNotEmpty) _notes(sale.notes.trim(), ar, r),
          // ---- barcode + qr ----
          if (settings.showBarcode) _codes(sale, invoiceNo, r),
          // ---- served by ----
          _servedBy(sale, ar, r),
          // ---- footer ----
          pw.Container(
            margin: pw.EdgeInsets.only(top: r.px(8)),
            padding: pw.EdgeInsets.symmetric(vertical: r.px(6)),
            decoration: const pw.BoxDecoration(
              border: pw.Border(top: pw.BorderSide(width: 0.75, style: pw.BorderStyle.dashed)),
            ),
            child: pw.Column(children: [
              if (ar && settings.footerArabic.trim().isNotEmpty)
                r.centred(settings.footerArabic.trim(), 10, bold: true, rtl: true),
              if (settings.footerEnglish.trim().isNotEmpty)
                pw.Padding(
                  padding: pw.EdgeInsets.only(top: r.px(4), bottom: r.px(5)),
                  child: r.centred(settings.footerEnglish.trim(), 10, bold: true),
                ),
              pw.SizedBox(height: r.px(4)),
              r.centred(DateFormat('dd/MM/yyyy hh:mm a').format(DateTime.now()), 9, color: _grey),
            ]),
          ),
        ],
      ),
    ),
  );

  return doc.save();
}

const _grey = PdfColor.fromInt(0xFF777777);

/// Text in the web receipt's sizes: [px] is a CSS pixel there, 0.75pt here,
/// shrunk with the roll ([s]) on 58mm paper.
class _ReceiptText {
  _ReceiptText(this.sale, this.settings, this.s);
  final Sale sale;
  final PrintSettings settings;
  final double Function(double) s;

  double px(double v) => s(v * 0.75);

  String get code => (sale.receipt?.currencyCode.isNotEmpty ?? false) ? sale.receipt!.currencyCode : Money.symbol.trim();
  String get symbol => (sale.receipt?.currencySymbol.isNotEmpty ?? false) ? sale.receipt!.currencySymbol : Money.symbol.trim();

  pw.TextStyle style(double size, {bool bold = false, bool italic = false, double spacing = 0, PdfColor? color}) => pw.TextStyle(
        fontSize: px(size),
        fontWeight: bold ? pw.FontWeight.bold : pw.FontWeight.normal,
        fontStyle: italic ? pw.FontStyle.italic : pw.FontStyle.normal,
        letterSpacing: spacing == 0 ? null : px(spacing),
        color: color,
        lineSpacing: px(size) * 0.2,
      );

  pw.Widget text(String value, double size,
          {bool bold = false, bool italic = false, bool rtl = false, double spacing = 0, pw.TextAlign? align, PdfColor? color}) =>
      pw.Text(value,
          textAlign: align,
          textDirection: rtl ? pw.TextDirection.rtl : null,
          style: style(size, bold: bold, italic: italic, spacing: spacing, color: color));

  pw.Widget centred(String value, double size, {bool bold = false, bool rtl = false, PdfColor? color}) =>
      pw.Center(child: text(value, size, bold: bold, rtl: rtl, align: pw.TextAlign.center, color: color));

  /// "<b>Email:</b> value", centred.
  pw.Widget labelled(String label, String value, double size) => pw.Center(
        child: pw.RichText(
          textAlign: pw.TextAlign.center,
          text: pw.TextSpan(children: [
            pw.TextSpan(text: '$label ', style: style(size, bold: true)),
            pw.TextSpan(text: value, style: style(size)),
          ]),
        ),
      );

  /// The web `h3`: 14px, bold, upper case, centred.
  pw.Widget h3(String value) => pw.Padding(
        padding: pw.EdgeInsets.symmetric(vertical: px(3)),
        child: centred(value.toUpperCase(), 14, bold: true),
      );

  pw.Widget divider() => pw.Padding(
        padding: pw.EdgeInsets.symmetric(vertical: px(5)),
        child: pw.Divider(height: 0.75, thickness: 0.75, color: PdfColors.black, borderStyle: pw.BorderStyle.dashed),
      );

  /// One bordered table cell, bold like the web's `<td><b>…</b></td>`.
  pw.Widget cell(String value, {double size = 11, pw.Alignment align = pw.Alignment.centerLeft, bool rtl = false, bool bold = true}) =>
      pw.Container(
        alignment: align,
        padding: pw.EdgeInsets.all(px(3)),
        child: text(value, size,
            bold: bold,
            rtl: rtl,
            align: align == pw.Alignment.centerRight
                ? pw.TextAlign.right
                : (align == pw.Alignment.center ? pw.TextAlign.center : pw.TextAlign.left)),
      );

  /// "Cash : 1,700.00" in an outlined badge, one per payment.
  pw.Widget badges() => pw.Wrap(
        alignment: pw.WrapAlignment.center,
        spacing: px(2),
        runSpacing: px(2),
        children: [
          for (final p in sale.payments)
            pw.Container(
              padding: pw.EdgeInsets.symmetric(horizontal: px(5), vertical: px(2)),
              decoration: pw.BoxDecoration(border: pw.Border.all(width: 0.75)),
              child: text('${p.alias.isNotEmpty ? p.alias : p.method} : ${Money.plain(p.amount)}', 10),
            ),
        ],
      );

  /// A bordered box like the web `.highlight-box` (dashed for the savings box).
  pw.Widget box(pw.Widget child, {bool dashed = false}) => pw.Container(
        margin: pw.EdgeInsets.symmetric(vertical: px(5)),
        padding: pw.EdgeInsets.all(px(5)),
        decoration: pw.BoxDecoration(
          border: pw.Border.all(width: 0.75, style: dashed ? pw.BorderStyle.dashed : pw.BorderStyle.solid),
        ),
        child: child,
      );
}

/// d-m-Y, as the web's systemDate().
String _webDate(String iso) {
  final d = DateTime.tryParse(iso);
  return d == null ? iso : DateFormat('dd-MM-yyyy').format(d);
}

DateTime? _local(String iso) => DateTime.tryParse(iso)?.toLocal();

// ---- office bill ---------------------------------------------------------

/// The compact English-only "Office bill" ("Stamp" design) — mirrors
/// resources/views/sale/print-office-bill.blade.php: tracked company name, an
/// outlined Bill No badge beside the biller and time, items, totals and a large
/// grand total. A sale rung up offline prints the number the till counted for
/// its biller (StaffBillCounter) — staff tally their day on these slips; only a
/// sale with no number at all falls back to its invoice reference.
Future<Uint8List> _buildOfficeBill(Sale sale, PrintSettings settings, pw.ThemeData theme, PdfPageFormat format,
    double pad, double Function(double) s, String invoiceNo, double grandTotal) {
  final parts = sale.date.split('-');
  final date = parts.length == 3 ? '${parts[2]}-${parts[1]}-${parts[0]}' : sale.date;
  final at = DateTime.tryParse(sale.createdAt)?.toLocal() ?? DateTime.now();
  final time = '${at.hour.toString().padLeft(2, '0')}:${at.minute.toString().padLeft(2, '0')}';
  final billNo = sale.billNo.isEmpty ? invoiceNo : sale.billNo;
  // No company name configured → head with the branch, like the web print.
  final company = settings.companyName.trim().isNotEmpty ? settings.companyName.trim() : sale.branch.trim();
  final body = pw.TextStyle(fontSize: s(9));
  final bold = pw.TextStyle(fontSize: s(9), fontWeight: pw.FontWeight.bold);
  pw.TextStyle caps(double size) =>
      pw.TextStyle(fontSize: s(size), fontWeight: pw.FontWeight.bold, letterSpacing: size * 0.2);

  pw.Widget split(String left, String right, {pw.TextStyle? style}) => pw.Padding(
        padding: const pw.EdgeInsets.symmetric(vertical: 0.6),
        child: pw.Row(children: [
          pw.Expanded(child: pw.Text(left, style: style ?? body)),
          pw.Text(right, style: style ?? body),
        ]),
      );

  final doc = pw.Document(title: 'Bill $billNo');
  doc.addPage(
    pw.Page(
      pageFormat: format,
      theme: theme,
      margin: pw.EdgeInsets.fromLTRB(pad, pad * 1.2, pad, pad * 1.6),
      build: (context) => pw.Column(
        crossAxisAlignment: pw.CrossAxisAlignment.stretch,
        mainAxisSize: pw.MainAxisSize.min,
        children: [
          if (company.isNotEmpty)
            pw.Center(
                child: pw.Text(company.toUpperCase(),
                    textAlign: pw.TextAlign.center,
                    style: pw.TextStyle(fontSize: s(13.5), fontWeight: pw.FontWeight.bold, letterSpacing: s(3)))),
          pw.SizedBox(height: s(9)),
          // ---- bill no badge | biller + date/time ----
          pw.Container(
            decoration: pw.BoxDecoration(
              border: pw.Border.all(width: 1.5),
              borderRadius: const pw.BorderRadius.all(pw.Radius.circular(7)),
            ),
            child: pw.ClipRRect(
              horizontalRadius: 6,
              verticalRadius: 6,
              child: pw.Table(
                columnWidths: {0: pw.FixedColumnWidth(s(72)), 1: const pw.FlexColumnWidth()},
                children: [
                  pw.TableRow(verticalAlignment: pw.TableCellVerticalAlignment.full, children: [
                    // Outlined, not filled: a solid black block starves the
                    // thermal head and prints faint and speckled.
                    pw.Container(
                      decoration: const pw.BoxDecoration(
                        border: pw.Border(right: pw.BorderSide(width: 1.5)),
                      ),
                      padding: pw.EdgeInsets.symmetric(vertical: s(5), horizontal: s(4)),
                      alignment: pw.Alignment.center,
                      child: pw.Column(mainAxisSize: pw.MainAxisSize.min, children: [
                        pw.Text('BILL NO', style: caps(6.5)),
                        pw.Text(billNo,
                            textAlign: pw.TextAlign.center,
                            style: pw.TextStyle(
                                fontSize: s(billNo.length > 7 ? 11 : 16.5), fontWeight: pw.FontWeight.bold)),
                      ]),
                    ),
                    pw.Container(
                      padding: pw.EdgeInsets.symmetric(vertical: s(5), horizontal: s(7)),
                      alignment: pw.Alignment.centerLeft,
                      child: pw.Column(
                        crossAxisAlignment: pw.CrossAxisAlignment.start,
                        mainAxisSize: pw.MainAxisSize.min,
                        children: [
                          pw.Text('Biller : ${sale.createdBy.isEmpty ? '-' : sale.createdBy}',
                              style: pw.TextStyle(fontSize: s(10), fontWeight: pw.FontWeight.bold)),
                          pw.Text('$date · $time', style: pw.TextStyle(fontSize: s(8))),
                        ],
                      ),
                    ),
                  ]),
                ],
              ),
            ),
          ),
          if (sale.billNo.isNotEmpty) ...[
            pw.SizedBox(height: s(4)),
            split('Invoice No', invoiceNo, style: pw.TextStyle(fontSize: s(7))),
          ],
          pw.SizedBox(height: s(11)),
          // ---- items ----
          split('ITEM', 'PRICE', style: caps(6.8)),
          pw.Divider(height: s(4), thickness: 1.1, color: PdfColors.black),
          for (final (i, l) in sale.lines.indexed) ...[
            if (i > 0) pw.Divider(height: 0, thickness: 0.7, color: PdfColors.black, borderStyle: pw.BorderStyle.dashed),
            pw.Padding(
              padding: pw.EdgeInsets.symmetric(vertical: s(5)),
              child: pw.Row(crossAxisAlignment: pw.CrossAxisAlignment.start, children: [
                pw.Expanded(
                    child: pw.Padding(
                  padding: pw.EdgeInsets.only(right: s(8)),
                  child: pw.Column(crossAxisAlignment: pw.CrossAxisAlignment.start, children: [
                    pw.Text(l.quantity == 1 ? l.name : '${l.name} × ${_num(l.quantity)}', style: bold),
                    // Picked options (base, flavour, add-ons…) under the item.
                    for (final o in l.options)
                      pw.Padding(
                        padding: pw.EdgeInsets.only(left: s(o.nested ? 12 : 6), top: s(1)),
                        child: pw.Text(o.label(_amt), style: pw.TextStyle(fontSize: s(7.5))),
                      ),
                  ]),
                )),
                pw.Text(_amt(l.unitPrice * l.quantity), style: bold),
              ]),
            ),
          ],
          pw.Divider(height: s(5), thickness: 1.1, color: PdfColors.black),
          // ---- totals ----
          split('Sub Total', _amt(sale.grossAmount)),
          split('Discount', _amt(sale.discount)),
          if (sale.taxAmount != 0) split('Tax', _amt(sale.taxAmount)),
          if (sale.roundOff != 0) split('Round Off', _amt(sale.roundOff)),
          pw.SizedBox(height: s(6)),
          pw.Divider(height: s(8), thickness: 0.7, color: PdfColors.black, borderStyle: pw.BorderStyle.dashed),
          pw.Center(child: pw.Text('GRAND TOTAL', style: caps(6.8))),
          pw.Center(
              child: pw.Text(_amt(grandTotal),
                  style: pw.TextStyle(fontSize: s(22.5), fontWeight: pw.FontWeight.bold))),
        ],
      ),
    ),
  );

  return doc.save();
}

// ---- logo ----------------------------------------------------------------

/// The cached company logo as a pdf widget — raster (png/jpg) via MemoryImage,
/// svg via SvgImage; anything else (corrupt cache, unsupported format) returns
/// null so the document still prints without it. Shared with the report PDF.
pw.Widget? pdfLogo(Uint8List? bytes, {required double width}) {
  if (bytes == null || bytes.isEmpty) return null;
  try {
    final isPng = bytes.length > 8 && bytes[0] == 0x89 && bytes[1] == 0x50;
    final isJpg = bytes.length > 3 && bytes[0] == 0xFF && bytes[1] == 0xD8;
    if (isPng || isJpg) return pw.Image(pw.MemoryImage(bytes), width: width);
    final text = utf8.decode(bytes, allowMalformed: true);
    if (text.contains('<svg')) return pw.SvgImage(svg: text, width: width);
  } catch (_) {
    // Undecodable image — skip the logo.
  }
  return null;
}

// ---- meta table ----------------------------------------------------------

/// English-only meta: one two-column bordered table.
pw.Widget _metaEnglish(Sale sale, String invoiceNo, _ReceiptText r) {
  final info = sale.receipt;
  final created = _local(sale.createdAt);
  final rows = <(String, pw.Widget)>[
    ('Invoice No', r.cell(invoiceNo)),
    // A sale rung up offline was receipted under its provisional reference;
    // a reprint after it syncs shows both so it matches the customer's copy.
    if (sale.offlineRef.isNotEmpty && sale.offlineRef != invoiceNo) ('Offline Ref', r.cell(sale.offlineRef)),
    ('Date', r.cell(_webDate(sale.date))),
    if (created != null) ('Time', r.cell(DateFormat('hh:mm a').format(created))),
    ('Customer', r.cell(_ucFirst(sale.customerName))),
    if (info != null && info.customerMobile.isNotEmpty) ('Mobile', r.cell(info.customerMobile)),
    if (info != null && info.customerEmail.isNotEmpty) ('Email', r.cell(info.customerEmail)),
    if (info != null && info.customerTaxNo.isNotEmpty) ('GST No', r.cell(info.customerTaxNo)),
    ('Payment Mode', pw.Container(alignment: pw.Alignment.centerLeft, padding: pw.EdgeInsets.all(r.px(3)), child: r.badges())),
  ];
  return pw.Padding(
    padding: pw.EdgeInsets.symmetric(vertical: r.px(8)),
    child: pw.Table(
      border: pw.TableBorder.all(width: 0.75),
      columnWidths: const {0: pw.IntrinsicColumnWidth(), 1: pw.FlexColumnWidth()},
      children: [
        for (final (label, value) in rows)
          pw.TableRow(verticalAlignment: pw.TableCellVerticalAlignment.middle, children: [r.cell(label), value]),
      ],
    ),
  );
}

/// Bilingual meta: each row its own 30% / 40% / 30% table, English label,
/// value, Arabic label — the web prints them as separate tables with a gap.
List<pw.Widget> _metaArabic(Sale sale, String invoiceNo, _ReceiptText r) {
  final info = sale.receipt;
  pw.Widget row(List<pw.Widget> cells, {bool span = false}) => pw.Padding(
        padding: pw.EdgeInsets.symmetric(vertical: r.px(4)),
        child: pw.Table(
          border: pw.TableBorder.all(width: 0.75),
          columnWidths: span
              ? const {0: pw.FlexColumnWidth(3), 1: pw.FlexColumnWidth(7)}
              : const {0: pw.FlexColumnWidth(3), 1: pw.FlexColumnWidth(4), 2: pw.FlexColumnWidth(3)},
          children: [pw.TableRow(verticalAlignment: pw.TableCellVerticalAlignment.middle, children: cells)],
        ),
      );
  pw.Widget three(String en, String value, String arabic) =>
      row([r.cell(en), r.cell(value), r.cell(arabic, align: pw.Alignment.centerRight, rtl: true)]);

  return [
    three('Invoice No', invoiceNo, _ar['invoice_no']!),
    if (sale.offlineRef.isNotEmpty && sale.offlineRef != invoiceNo) three('Offline Ref', sale.offlineRef, _ar['offline_ref']!),
    three('Date', _webDate(sale.date), _ar['date']!),
    three('Customer', _ucFirst(sale.customerName), _ar['customer']!),
    if (info != null && info.customerMobile.isNotEmpty) three('Mobile', info.customerMobile, _ar['mobile']!),
    if (info != null && info.customerEmail.isNotEmpty) row([r.cell('Email'), r.cell(info.customerEmail)], span: true),
    if (info != null && info.customerTaxNo.isNotEmpty) row([r.cell('GST No.'), r.cell(info.customerTaxNo)], span: true),
    pw.Padding(
      padding: pw.EdgeInsets.symmetric(vertical: r.px(4)),
      child: pw.Table(
        border: pw.TableBorder.all(width: 0.75),
        columnWidths: const {0: pw.FlexColumnWidth(3), 1: pw.FlexColumnWidth(7)},
        children: [
          pw.TableRow(children: [r.cell('Payment Mode'), r.cell(_ar['payment_mode']!, align: pw.Alignment.centerRight, rtl: true)]),
        ],
      ),
    ),
    pw.Container(
      padding: pw.EdgeInsets.all(r.px(3)),
      decoration: pw.BoxDecoration(border: pw.Border.all(width: 0.75)),
      child: r.badges(),
    ),
  ];
}

String _ucFirst(String v) => v.isEmpty ? v : v[0].toUpperCase() + v.substring(1);

// ---- items table ---------------------------------------------------------

/// Item lines laid out like the web receipt: the name (with its description
/// and picked options) on a full-width row — a `colspan="4"` there — the
/// Arabic name beneath it, then a `# | Price | Qty | Amount` row.
///
/// The pdf package has no colspan, so the frame is an outer single-column table
/// (it draws the border and the horizontal rules) and every numeric row is a
/// nested table supplying the vertical rules.
pw.Widget _itemsTable(Sale sale, bool ar, _ReceiptText r, PrintSettings settings) {
  pw.Widget head(String en, String? arabic, pw.Alignment align) => pw.Container(
        alignment: align,
        padding: pw.EdgeInsets.all(r.px(3)),
        child: pw.Column(
          crossAxisAlignment: align == pw.Alignment.centerRight ? pw.CrossAxisAlignment.end : pw.CrossAxisAlignment.center,
          children: [
            r.text(en.toUpperCase(), 11, bold: true, spacing: 0.3),
            if (arabic != null) r.text(arabic, 11, bold: true, rtl: true),
          ],
        ),
      );

  final rows = <pw.Widget>[
    _itemGridRow([
      head('#', null, pw.Alignment.center),
      head('Price', ar ? _ar['price'] : null, pw.Alignment.centerRight),
      head(settings.quantityLabel.column, ar ? _ar['quantity'] : null, pw.Alignment.centerRight),
      head('Amount', ar ? _ar['amount'] : null, pw.Alignment.centerRight),
    ]),
  ];

  for (var i = 0; i < sale.lines.length; i++) {
    final line = sale.lines[i];
    // With discounts shown the line prints its list price and stored total;
    // hidden, it prints what it cost after its share of the bill discount.
    final amount = settings.showDiscount ? (line.lineTotal ?? line.total) : (line.effectiveTotal ?? line.total);
    final price = settings.showDiscount || line.quantity == 0 ? line.unitPrice : amount / line.quantity;
    rows.add(pw.Padding(
      padding: pw.EdgeInsets.all(r.px(3)),
      child: pw.Column(crossAxisAlignment: pw.CrossAxisAlignment.start, children: [
        r.text(line.printName.isNotEmpty ? line.printName : line.name, 11, bold: true),
        if (line.description.isNotEmpty)
          pw.Padding(padding: pw.EdgeInsets.only(top: r.px(1)), child: r.text(line.description, 9, italic: true)),
        for (final o in line.options)
          pw.Padding(
            padding: pw.EdgeInsets.only(left: r.px(o.nested ? 16 : 8)),
            child: r.text(o.label(Money.plain), 10),
          ),
      ]),
    ));
    final arabic = line.printName.isNotEmpty ? line.printNameArabic : line.nameArabic;
    if (ar && arabic.isNotEmpty) rows.add(r.cell(arabic, align: pw.Alignment.centerRight, rtl: true));
    rows.add(_itemGridRow([
      r.cell('${i + 1}', align: pw.Alignment.centerRight),
      r.cell(Money.plain(price), align: pw.Alignment.centerRight),
      pw.Container(
        alignment: pw.Alignment.centerRight,
        padding: pw.EdgeInsets.all(r.px(3)),
        child: pw.RichText(
          textAlign: pw.TextAlign.right,
          text: pw.TextSpan(children: [
            pw.TextSpan(text: _num(line.quantity), style: r.style(11, bold: true)),
            if (line.unit.isNotEmpty) pw.TextSpan(text: ' ${line.unit}', style: r.style(9)),
          ]),
        ),
      ),
      r.cell(Money.plain(amount), align: pw.Alignment.centerRight),
    ]));
  }

  return pw.Padding(
    padding: pw.EdgeInsets.symmetric(vertical: r.px(8)),
    child: pw.Table(
      border: pw.TableBorder.all(width: 0.75),
      columnWidths: {0: const pw.FlexColumnWidth(1)},
      children: [for (final row in rows) pw.TableRow(children: [row])],
    ),
  );
}

/// One `# | Price | Qty | Amount` row of the items table.
pw.Widget _itemGridRow(List<pw.Widget> cells) => pw.Table(
      border: const pw.TableBorder(verticalInside: pw.BorderSide(width: 0.75)),
      columnWidths: const {
        0: pw.FlexColumnWidth(0.5),
        1: pw.FlexColumnWidth(1.5),
        2: pw.FlexColumnWidth(1.2),
        3: pw.FlexColumnWidth(1.5),
      },
      children: [pw.TableRow(verticalAlignment: pw.TableCellVerticalAlignment.middle, children: cells)],
    );

// ---- totals table --------------------------------------------------------

/// The web totals table: every label carries the currency code (the Arabic one
/// the symbol). With discounts shown it opens on Net Value and the whole
/// discount; hidden, only the bill discount prints, measured against the
/// already-discounted lines.
pw.Widget _totalsTable(Sale sale, bool ar, _ReceiptText r, PrintSettings settings) {
  final computedTotal = sale.grossAmount - sale.discount + sale.taxAmount + sale.roundOff;
  final grandTotal = sale.grandTotal != 0 ? sale.grandTotal : computedTotal;
  final balance = sale.receipt != null ? sale.balance : grandTotal - sale.paid;
  final totalQty = sale.lines.fold<double>(0, (t, l) => t + l.quantity);
  String pct(double part, double base) {
    final value = base > 0 ? (part / base * 100) : 0.0;
    final rounded = (value * 100).round() / 100;
    return rounded == rounded.roundToDouble() ? rounded.toStringAsFixed(0) : _num(rounded);
  }

  final rows = <(String, String, String?)>[
    if (settings.showTotalQty) (settings.quantityLabel.total, _num(totalQty), _ar['total_quantity']),
    if (settings.showDiscount) ...[
      ('Net Value (${r.code})', Money.plain(sale.grossAmount), '${_ar['net_value']} (${r.symbol})'),
      if (sale.discount != 0)
        ('Discount (${r.code})', '${Money.plain(sale.discount)} (${pct(sale.discount, sale.grossAmount)}%)', '${_ar['discount']} (${r.symbol})'),
    ] else if (sale.otherDiscount != 0)
      ('Discount (${r.code})', '${Money.plain(sale.otherDiscount)} (${pct(sale.otherDiscount, sale.grossAmount - sale.itemDiscount)}%)',
          '${_ar['discount']} (${r.symbol})'),
    if (sale.taxAmount != 0) ('Tax (${r.code})', Money.plain(sale.taxAmount), '${_ar['tax']} (${r.symbol})'),
    if (sale.roundOff != 0) ('Round Off (${r.code})', Money.plain(sale.roundOff), '${_ar['round_off']} (${r.symbol})'),
    ('Total (${r.code})', Money.plain(grandTotal), '${_ar['total']} (${r.symbol})'),
    ('Paid (${r.code})', Money.plain(sale.paid), '${_ar['paid']} (${r.symbol})'),
    if (balance.abs() >= 0.005) ('Balance (${r.code})', Money.plain(balance), '${_ar['balance']} (${r.symbol})'),
    // A student's purchase: what is left on the card.
    if (sale.student != null) ('Card Balance (${r.code})', Money.plain(sale.student!.cardBalance), '${_ar['card_balance']} (${r.symbol})'),
  ];

  return pw.Padding(
    padding: pw.EdgeInsets.symmetric(vertical: r.px(8)),
    child: pw.Table(
      border: pw.TableBorder.all(width: 0.75),
      columnWidths: ar
          ? const {0: pw.FlexColumnWidth(35), 1: pw.FlexColumnWidth(30), 2: pw.FlexColumnWidth(35)}
          : const {0: pw.FlexColumnWidth(39), 1: pw.FlexColumnWidth(61)},
      children: [
        for (final (en, value, arabic) in rows)
          pw.TableRow(verticalAlignment: pw.TableCellVerticalAlignment.middle, children: [
            r.cell(en),
            r.cell(value, align: pw.Alignment.centerRight),
            if (ar) r.cell(arabic ?? '', align: pw.Alignment.centerRight, rtl: true),
          ]),
      ],
    ),
  );
}

/// "You saved QAR 40.00 on this purchase", in a dashed box.
pw.Widget _savedBox(double saved, bool ar, _ReceiptText r) => r.box(
      pw.Column(children: [
        if (ar) r.centred('لقد وفرت ${Money.plain(saved)} ${r.symbol} في هذه الفاتورة', 12, bold: true, rtl: true),
        r.centred('You saved ${r.code} ${Money.plain(saved)} on this purchase', 12, bold: true),
      ]),
      dashed: true,
    );

// ---- barcode + qr --------------------------------------------------------

/// The web's codes row: the total barcode ('229919' + padded total + check
/// digit) boxed on the left, the QR link to the online invoice on the right.
pw.Widget _codes(Sale sale, String invoiceNo, _ReceiptText r) {
  final info = sale.receipt;
  final barcode = (info?.barcode.isNotEmpty ?? false) ? info!.barcode : _totalBarcode(sale.grandTotal);
  final qr = (info?.qr.isNotEmpty ?? false) ? info!.qr : invoiceNo;
  return pw.Padding(
    padding: pw.EdgeInsets.symmetric(vertical: r.px(8)),
    child: pw.Row(
      crossAxisAlignment: pw.CrossAxisAlignment.center,
      children: [
        pw.Expanded(
          child: pw.Container(
            margin: pw.EdgeInsets.symmetric(horizontal: r.px(3)),
            padding: pw.EdgeInsets.all(r.px(5)),
            decoration: pw.BoxDecoration(border: pw.Border.all(width: 0.75)),
            child: pw.Column(children: [
              pw.BarcodeWidget(barcode: pw.Barcode.code128(), data: barcode, height: r.px(40), drawText: false),
              pw.SizedBox(height: r.px(2)),
              r.text(barcode, 10, bold: true, spacing: 0.3),
            ]),
          ),
        ),
        // drawText: false matters even for a QR (which draws no text anyway):
        // BarcodeWidget defaults it to true and then binds its built-in Courier
        // font, spamming "Courier has no Unicode support" in debug runs.
        pw.Padding(
          padding: pw.EdgeInsets.symmetric(horizontal: r.px(3)),
          child: pw.BarcodeWidget(barcode: pw.Barcode.qrCode(), data: qr, width: r.px(80), height: r.px(80), drawText: false),
        ),
      ],
    ),
  );
}

/// SaleHelper::barcodeString for a sale with no server copy yet (rung up offline).
String _totalBarcode(double grandTotal) {
  final padded = '${grandTotal.truncate()}00'.padLeft(6, '0');
  final digits = '229919$padded';
  var sum = 0;
  for (var i = 0; i < digits.length; i++) {
    final d = int.parse(digits[i]);
    sum += i.isOdd ? d * 3 : d;
  }
  final mod = sum % 10;
  return '$digits${mod == 0 ? 0 : 10 - mod}';
}

// ---- served-by box -------------------------------------------------------

/// "Served By :" with the line employees and the sale's last-saved time.
pw.Widget _servedBy(Sale sale, bool ar, _ReceiptText r) {
  final info = sale.receipt;
  final names = info != null
      ? info.servedBy
      : ({for (final l in sale.lines) if (l.employee.trim().isNotEmpty) l.employee.trim()}.toList()..sort()).join(', ');
  final at = _local(info?.updatedAt ?? '') ?? _local(sale.createdAt) ?? DateTime.now();
  return r.box(pw.Column(children: [
    pw.Row(children: [
      pw.Expanded(child: r.text('Served By :', 11, bold: true)),
      if (ar) pw.Expanded(child: r.text('${_ar['served_by']} :', 11, bold: true, rtl: true, align: pw.TextAlign.right)),
    ]),
    if (names.isNotEmpty) pw.Padding(padding: pw.EdgeInsets.only(top: r.px(2)), child: r.centred(names, 11, bold: true)),
    pw.Padding(
      padding: pw.EdgeInsets.only(top: r.px(2)),
      child: r.centred(DateFormat('dd-MMM-yyyy hh:mm a').format(at), 11, bold: true),
    ),
  ]));
}

/// The cashier's checkout note, boxed like the served-by panel.
pw.Widget _notes(String notes, bool ar, _ReceiptText r) => r.box(pw.Column(
      crossAxisAlignment: pw.CrossAxisAlignment.stretch,
      children: [
        pw.Row(children: [
          pw.Expanded(child: r.text('Notes :', 11, bold: true)),
          if (ar) pw.Expanded(child: r.text('${_ar['notes']} :', 11, bold: true, rtl: true, align: pw.TextAlign.right)),
        ]),
        pw.SizedBox(height: r.px(2)),
        r.text(notes, 11, bold: true),
      ],
    ));

// ---- shared cell / divider helpers ---------------------------------------

pw.Widget _dashed() => pw.Padding(
      padding: const pw.EdgeInsets.symmetric(vertical: 5),
      child: pw.Divider(height: 0.5, thickness: 0.7, color: PdfColors.black, borderStyle: pw.BorderStyle.dashed),
    );

/// A short "does this till reach the printer?" slip, on the same roll as a real
/// receipt. Used by Settings → Printer & Receipt → Test print so a cashier can
/// verify the pairing without ringing a live sale.
Future<Uint8List> buildTestReceiptPdf(PrintSettings settings) async {
  await _loadArabicFonts();
  final is58 = settings.width == PaperWidth.mm58;
  final format = is58 ? PdfPageFormat.roll57 : PdfPageFormat.roll80;
  final k = is58 ? 0.84 : 1.0;
  final company = settings.companyName.trim();

  final doc = pw.Document(title: 'Printer test');
  doc.addPage(
    pw.Page(
      pageFormat: format,
      theme: pw.ThemeData.withFont(
        base: pw.Font.helvetica(),
        bold: pw.Font.helveticaBold(),
        fontFallback: [if (_arabicFont != null) _arabicFont!],
      ),
      margin: pw.EdgeInsets.all(is58 ? 6.0 : 10.0),
      build: (context) => pw.Column(
        crossAxisAlignment: pw.CrossAxisAlignment.stretch,
        mainAxisSize: pw.MainAxisSize.min,
        children: [
          if (company.isNotEmpty)
            pw.Center(
                child: pw.Text(company,
                    style: pw.TextStyle(fontSize: 11 * k, fontWeight: pw.FontWeight.bold))),
          pw.SizedBox(height: 4 * k),
          pw.Center(
              child: pw.Text('PRINTER TEST',
                  style: pw.TextStyle(fontSize: 13 * k, fontWeight: pw.FontWeight.bold))),
          _dashed(),
          pw.Center(
              child: pw.Text('Auto-print is working on this device.',
                  textAlign: pw.TextAlign.center, style: pw.TextStyle(fontSize: 8.5 * k))),
          pw.SizedBox(height: 5 * k),
          pw.Center(
              child: pw.Text('Roll: ${settings.width.label}   ·   ${settings.style.label}',
                  style: pw.TextStyle(fontSize: 8 * k))),
          _dashed(),
          // A full-width rule doubles as an alignment check: if it stops short
          // or wraps, the roll width is set wrong for this printer.
          pw.Center(
              child: pw.Text('|| 0123456789 ABCDEFGHIJKLM ||',
                  style: pw.TextStyle(fontSize: 8 * k))),
        ],
      ),
    ),
  );
  return doc.save();
}

String _num(double q) {
  if (q % 1 == 0) return q.toStringAsFixed(0);
  return q.toStringAsFixed(3).replaceFirst(RegExp(r'0+$'), '').replaceFirst(RegExp(r'\.$'), '');
}

String _amt(double v) => v.toStringAsFixed(Money.decimals);
