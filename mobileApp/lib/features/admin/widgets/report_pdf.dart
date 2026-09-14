import 'dart:typed_data';

import 'package:intl/intl.dart';
import 'package:pdf/pdf.dart';
import 'package:pdf/widgets.dart' as pw;

import 'package:invo/shared/domain/helpers/formatters.dart';
import 'package:invo/shared/domain/models/index.dart';
import 'package:invo/shared/widgets/receipt_pdf.dart';

/// Letterhead for the report PDF — whose report it is, and who pulled it.
class ReportPdfBrand {
  const ReportPdfBrand({
    this.companyName = '',
    this.branchName = '',
    this.preparedBy = '',
    this.logo,
    this.accent = const PdfColor.fromInt(0xFF0A62C8),
  });

  /// From the web print settings — '' when the tenant prints no company name.
  final String companyName;
  final String branchName;
  final String preparedBy;

  /// From the web print settings — null when the tenant prints no logo.
  final Uint8List? logo;

  /// The app's theme colour, so the paper matches the till it came from.
  final PdfColor accent;
}

const _ink = PdfColor.fromInt(0xFF1F2430);
const _muted = PdfColor.fromInt(0xFF6B7280);
const _hairline = PdfColor.fromInt(0xFFE3E6EB);
const _zebra = PdfColor.fromInt(0xFFF6F7F9);
const _good = PdfColor.fromInt(0xFF1F9D63);
const _warn = PdfColor.fromInt(0xFFD9890C);
const _bad = PdfColor.fromInt(0xFFD4546A);

/// The accent and the two washes of it the tiles and total rows sit on — pdf
/// fills have no transparency to lean on, so they are mixed over white here.
class _Tone {
  _Tone(this.accent)
      : tint = _over(accent, 0.07),
        tintStrong = _over(accent, 0.15);

  final PdfColor accent;
  final PdfColor tint;
  final PdfColor tintStrong;

  static PdfColor _over(PdfColor c, double alpha) => PdfColor(
        1 - (1 - c.red) * alpha,
        1 - (1 - c.green) * alpha,
        1 - (1 - c.blue) * alpha,
      );
}

/// The Reports screen as an A4 document: letterhead, the figures for the
/// range, and every line of a breakdown across as many pages as it takes.
Future<Uint8List> buildReportPdf(ReportExport data, ReportPdfBrand brand) async {
  // IBM Plex Sans Arabic as the BASE font (it carries Latin too): item and
  // staff names can be Arabic, and only as the base font does Arabic shape —
  // as a fallback it prints reversed and disconnected (see receipt_pdf.dart).
  final (regular, bold) = await loadBundledArabicFonts();
  final theme = regular == null
      ? pw.ThemeData.withFont(base: pw.Font.helvetica(), bold: pw.Font.helveticaBold())
      : pw.ThemeData.withFont(base: regular, bold: bold ?? regular);
  final tone = _Tone(brand.accent);
  final range = Dates.range(data.startDate, data.endDate);

  final doc = pw.Document(
    title: '${data.kind.title} · $range',
    author: brand.companyName.trim().isEmpty ? null : brand.companyName.trim(),
    creator: 'QLOUD POS',
  );
  doc.addPage(
    pw.MultiPage(
      pageTheme: pw.PageTheme(
        pageFormat: PdfPageFormat.a4,
        margin: const pw.EdgeInsets.fromLTRB(36, 32, 36, 28),
        theme: theme,
      ),
      // The masthead opens page one; later pages carry a slim running head so
      // a loose sheet still says which report it belongs to.
      header: (ctx) => ctx.pageNumber == 1 ? pw.SizedBox() : _runningHead(data, brand, range),
      footer: _footer,
      build: (ctx) => [
        _masthead(data, brand, tone, range),
        ...switch (data.kind) {
          ReportExportKind.overview => _overview(data, tone),
          ReportExportKind.items || ReportExportKind.stylists => _breakdown(data, tone),
        },
      ],
    ),
  );
  return doc.save();
}

// ---- letterhead ------------------------------------------------------------

pw.Widget _masthead(ReportExport d, ReportPdfBrand b, _Tone tone, String range) {
  final logo = pdfLogo(b.logo, width: 46);
  final company = b.companyName.trim();
  // With no company name printed, the branch takes the headline rather than
  // sitting under an empty one.
  final headline = company.isNotEmpty ? company : (b.branchName.isNotEmpty ? b.branchName : 'QLOUD POS');
  final subline = company.isNotEmpty ? b.branchName : '';
  final filters = _filterLine(d);
  final at = DateFormat('d MMM yyyy, h:mm a').format(d.generatedAt);
  final by = b.preparedBy.trim();

  return pw.Column(
    crossAxisAlignment: pw.CrossAxisAlignment.stretch,
    children: [
      pw.Row(
        crossAxisAlignment: pw.CrossAxisAlignment.center,
        children: [
          if (logo != null) ...[
            pw.ConstrainedBox(constraints: const pw.BoxConstraints(maxWidth: 70, maxHeight: 46), child: logo),
            pw.SizedBox(width: 12),
          ],
          pw.Expanded(
            child: pw.Column(
              crossAxisAlignment: pw.CrossAxisAlignment.start,
              children: [
                _t(headline, size: 15, bold: true),
                if (subline.isNotEmpty) ...[
                  pw.SizedBox(height: 2),
                  _t(subline, size: 9.5, color: _muted),
                ],
              ],
            ),
          ),
          pw.SizedBox(width: 12),
          pw.Column(
            crossAxisAlignment: pw.CrossAxisAlignment.end,
            children: [
              _t(d.kind.title.toUpperCase(), size: 14, bold: true, color: tone.accent, letterSpacing: 0.8),
              pw.SizedBox(height: 3),
              _t(range, size: 10.5, bold: true),
              pw.SizedBox(height: 2),
              _t(by.isEmpty ? 'Generated $at' : 'Generated $at by $by', size: 7.5, color: _muted),
            ],
          ),
        ],
      ),
      pw.SizedBox(height: 10),
      pw.Container(height: 2, color: tone.accent),
      if (filters.isNotEmpty) ...[
        pw.SizedBox(height: 6),
        _t(filters, size: 8, color: _muted),
      ],
    ],
  );
}

/// The item filters that shaped the list, so the paper says how it was ranked.
String _filterLine(ReportExport d) {
  if (d.kind != ReportExportKind.items) return '';
  final type = switch (d.productType) {
    'product' => 'Products only',
    'service' => 'Services only',
    _ => 'All item types',
  };
  return 'Ranked by ${d.rankByQty ? 'quantity' : 'amount'}  ·  $type';
}

pw.Widget _runningHead(ReportExport d, ReportPdfBrand b, String range) {
  final who = b.companyName.trim().isNotEmpty ? b.companyName.trim() : b.branchName;
  return pw.Container(
    margin: const pw.EdgeInsets.only(bottom: 12),
    padding: const pw.EdgeInsets.only(bottom: 5),
    decoration: const pw.BoxDecoration(
      border: pw.Border(bottom: pw.BorderSide(color: _hairline, width: 0.6)),
    ),
    // Separate Texts, not one joined string: an Arabic company name marks its
    // Text rtl, which would reorder an English title glued onto it.
    child: pw.Row(
      children: [
        if (who.isNotEmpty) ...[
          _t(who, size: 8, bold: true, color: _muted),
          _t('  ·  ', size: 8, color: _muted),
        ],
        _t(d.kind.title, size: 8, bold: true, color: _muted),
        pw.Spacer(),
        _t(range, size: 8, color: _muted),
      ],
    ),
  );
}

pw.Widget _footer(pw.Context ctx) => pw.Container(
      margin: const pw.EdgeInsets.only(top: 12),
      padding: const pw.EdgeInsets.only(top: 5),
      decoration: const pw.BoxDecoration(
        border: pw.Border(top: pw.BorderSide(color: _hairline, width: 0.6)),
      ),
      child: pw.Row(
        children: [
          _t('Powered by QLOUD POS', size: 7, color: _muted),
          pw.Spacer(),
          _t('Page ${ctx.pageNumber} of ${ctx.pagesCount}', size: 7, color: _muted),
        ],
      ),
    );

// ---- Sales Overview --------------------------------------------------------

List<pw.Widget> _overview(ReportExport d, _Tone tone) {
  final ov = d.overview;
  if (ov == null) return [_section('Sales performance', tone), _note('No figures for this period.')];
  final s = ov.summary;
  final pay = ov.payments;
  final methods = pay.methods;
  final avg = s.noOfSales > 0 ? s.netSales / s.noOfSales : 0.0;

  return [
    _section('Sales performance', tone),
    _kpiGrid([
      _Kpi('Gross sales', Money.of(s.grossSales)),
      _Kpi('Discounts', Money.of(s.discount)),
      _Kpi('Net sales', Money.of(s.netSales), color: tone.accent),
      _Kpi('Invoices', '${s.noOfSales}'),
      _Kpi('Average ticket', s.noOfSales > 0 ? Money.of(avg) : '-'),
      _Kpi('Returns', '${s.noOfSalesReturns}'),
      _Kpi('Item total', Money.of(s.totalItem)),
      _Kpi('Products', Money.of(s.productSale)),
      _Kpi('Services', Money.of(s.serviceSale)),
    ], tone),
    pw.SizedBox(height: 12),
    pw.Row(
      children: [
        pw.Expanded(child: _rate('Sales success rate', s.successRate, tone.accent)),
        pw.SizedBox(width: 18),
        pw.Expanded(child: _rate('Collection rate', s.collectionRate, _good)),
      ],
    ),
    _section('Payments', tone),
    _kpiGrid([
      _Kpi('Sales payments', Money.of(pay.salesTotal),
          caption: _count(pay.salesTransactions, 'transaction'), color: _good),
      _Kpi('Returns payments', Money.of(pay.returnsTotal),
          caption: _count(pay.returnsTransactions, 'return'), color: _warn),
      _Kpi('Net payments', Money.of(pay.netPayment),
          caption: _count(pay.totalTransactions, 'transaction'),
          color: pay.netPayment < 0 ? _bad : tone.accent),
    ], tone),
    if (methods.isNotEmpty) ...[
      pw.SizedBox(height: 9),
      _table(
        tone,
        [
          const _Col('Method', 3),
          const _Col('Txns', 1.1, right: true),
          _Col(_withCurrency('Sales'), 2, right: true),
          _Col(_withCurrency('Returns'), 2, right: true),
          _Col(_withCurrency('Net'), 2, right: true),
        ],
        [
          for (final m in methods) [m.method, '${m.transactions}', _amt(m.sales), _amt(m.returns), _amt(m.net)],
        ],
        total: [
          'Total',
          '${methods.fold<int>(0, (a, m) => a + m.transactions)}',
          _amt(methods.fold<double>(0, (a, m) => a + m.sales)),
          _amt(methods.fold<double>(0, (a, m) => a + m.returns)),
          _amt(methods.fold<double>(0, (a, m) => a + m.net)),
        ],
      ),
    ],
    if (d.days.isNotEmpty) ...[
      _section('Sales by day', tone, note: _count(d.days.length, 'day')),
      _table(
        tone,
        [
          const _Col('Date', 3),
          const _Col('Invoices', 1.3, right: true),
          _Col(_withCurrency('Gross'), 2, right: true),
          _Col(_withCurrency('Discount'), 2, right: true),
          _Col(_withCurrency('Paid'), 2, right: true),
        ],
        [
          for (final day in d.days)
            [_day(day.date), '${day.invoices}', _amt(day.gross), _amt(day.discount), _amt(day.paid)],
        ],
        total: [
          'Total',
          '${d.days.fold<int>(0, (a, x) => a + x.invoices)}',
          _amt(d.days.fold<double>(0, (a, x) => a + x.gross)),
          _amt(d.days.fold<double>(0, (a, x) => a + x.discount)),
          _amt(d.days.fold<double>(0, (a, x) => a + x.paid)),
        ],
      ),
    ] else if (!d.daysComplete) ...[
      _section('Sales by day', tone),
      _note('Too many invoices in this range to list day by day — export a shorter range for this table.'),
    ],
    if (ov.employees.isNotEmpty) ...[
      _section('Top staff', tone, note: 'Top ${ov.employees.length}'),
      _table(
        tone,
        [
          const _Col('#', 0.5),
          const _Col('Staff', 4.5),
          const _Col('Qty', 1.3, right: true),
          _Col(_withCurrency('Sales'), 2.2, right: true),
        ],
        [
          for (var i = 0; i < ov.employees.length; i++)
            ['${i + 1}', ov.employees[i].name, qtyLabel(ov.employees[i].quantity), _amt(ov.employees[i].total)],
        ],
      ),
    ],
    if (ov.products.isNotEmpty) ...[
      _section('Top items', tone, note: 'Top ${ov.products.length}'),
      _table(
        tone,
        [
          const _Col('#', 0.5),
          const _Col('Item', 4),
          const _Col('Type', 1.4),
          const _Col('Sold', 1.1, right: true),
          const _Col('Returned', 1.4, right: true),
          _Col(_withCurrency('Net'), 2.2, right: true),
        ],
        [
          for (var i = 0; i < ov.products.length; i++)
            [
              '${i + 1}',
              ov.products[i].name,
              _titleCase(ov.products[i].type),
              qtyLabel(ov.products[i].salesQuantity),
              qtyLabel(ov.products[i].returnQuantity),
              _amt(ov.products[i].netAmount),
            ],
        ],
      ),
    ],
  ];
}

// ---- By Item / By Staff --------------------------------------------------

List<pw.Widget> _breakdown(ReportExport d, _Tone tone) {
  final items = d.kind == ReportExportKind.items;
  final lines = d.lines;
  if (lines.isEmpty) {
    return [_section(items ? 'Item breakdown' : 'Staff breakdown', tone), _note('No sales in this period.')];
  }
  // Share of the metric the list is ranked by, like the bars on screen.
  final byQty = items && d.rankByQty;
  final whole = byQty ? d.totalQuantity : d.totalAmount;
  final itemsSold = lines.fold<int>(0, (a, l) => a + l.items);

  return [
    _section('Summary', tone),
    _kpiGrid(
      items
          ? [
              _Kpi('Items', '${d.lineCount}'),
              _Kpi('Quantity sold', qtyLabel(d.totalQuantity)),
              _Kpi('Net amount', Money.of(d.totalAmount), color: tone.accent),
            ]
          : [
              _Kpi('Staff', '${d.lineCount}'),
              _Kpi('Items sold', '$itemsSold'),
              _Kpi('Net revenue', Money.of(d.totalAmount), color: tone.accent),
            ],
      tone,
    ),
    _section(
      items ? 'Item breakdown' : 'Staff breakdown',
      tone,
      note: d.truncated ? 'Top ${lines.length} of ${d.lineCount}' : items ? _count(lines.length, 'item') : '${lines.length} staff',
    ),
    if (items)
      _table(
        tone,
        [
          const _Col('#', 0.6),
          const _Col('Item', 4),
          const _Col('Code', 1.6),
          const _Col('Qty', 1.1, right: true),
          const _Col('Bills', 1, right: true),
          _Col(_withCurrency('Amount'), 2, right: true),
          const _Col('Share', 1.1, right: true),
        ],
        [
          for (var i = 0; i < lines.length; i++)
            [
              '${i + 1}',
              lines[i].name,
              lines[i].code,
              qtyLabel(lines[i].quantity),
              '${lines[i].bills}',
              _amt(lines[i].amount),
              _share(byQty ? lines[i].quantity : lines[i].amount, whole),
            ],
        ],
        total: ['', 'Total', '', qtyLabel(d.totalQuantity), '', _amt(d.totalAmount), ''],
      )
    else
      _table(
        tone,
        [
          const _Col('#', 0.6),
          const _Col('Staff', 4.4),
          const _Col('Bills', 1.2, right: true),
          const _Col('Items', 1.2, right: true),
          _Col(_withCurrency('Revenue'), 2.2, right: true),
          const _Col('Share', 1.2, right: true),
        ],
        [
          for (var i = 0; i < lines.length; i++)
            [
              '${i + 1}',
              lines[i].name,
              '${lines[i].bills}',
              '${lines[i].items}',
              _amt(lines[i].amount),
              _share(lines[i].amount, whole),
            ],
        ],
        total: ['', 'Total', '', '$itemsSold', _amt(d.totalAmount), ''],
      ),
    if (d.truncated)
      _note('The list stops at the top ${lines.length} of ${d.lineCount}; the totals cover all of them. '
          'Export a shorter range to list every line.'),
  ];
}

// ---- building blocks -------------------------------------------------------

final _arabic = RegExp(r'[؀-ۿݐ-ݿࢠ-ࣿﭐ-﷿ﹰ-﻿]');

/// Text with Arabic runs marked rtl, so they shape and join instead of
/// printing reversed.
pw.Widget _t(
  String text, {
  double size = 9,
  bool bold = false,
  PdfColor color = _ink,
  double letterSpacing = 0,
  pw.TextAlign? align,
}) =>
    pw.Text(
      text,
      textDirection: _arabic.hasMatch(text) ? pw.TextDirection.rtl : null,
      textAlign: align,
      style: pw.TextStyle(
        fontSize: size,
        fontWeight: bold ? pw.FontWeight.bold : pw.FontWeight.normal,
        color: color,
        letterSpacing: letterSpacing,
      ),
    );

pw.Widget _section(String title, _Tone tone, {String? note}) => pw.Padding(
      padding: const pw.EdgeInsets.only(top: 16, bottom: 7),
      child: pw.Row(
        children: [
          pw.Container(width: 3, height: 11, color: tone.accent),
          pw.SizedBox(width: 6),
          _t(title.toUpperCase(), size: 8.5, bold: true, letterSpacing: 0.8),
          pw.Spacer(),
          if (note != null) _t(note, size: 7.5, color: _muted),
        ],
      ),
    );

pw.Widget _note(String text) => pw.Padding(
      padding: const pw.EdgeInsets.only(top: 6),
      child: _t(text, size: 7.8, color: _muted),
    );

class _Kpi {
  const _Kpi(this.label, this.value, {this.caption, this.color});
  final String label;
  final String value;
  final String? caption;
  final PdfColor? color;
}

/// Tiles [columns] to a row. A row's tiles are only as even as their contents,
/// so a grid is given either all captions or none.
pw.Widget _kpiGrid(List<_Kpi> tiles, _Tone tone, {int columns = 3}) => pw.Column(
      children: [
        for (var i = 0; i < tiles.length; i += columns)
          pw.Padding(
            padding: pw.EdgeInsets.only(top: i == 0 ? 0 : 7),
            child: pw.Row(
              crossAxisAlignment: pw.CrossAxisAlignment.start,
              children: [
                for (var j = 0; j < columns; j++) ...[
                  if (j > 0) pw.SizedBox(width: 7),
                  pw.Expanded(child: i + j < tiles.length ? _kpi(tiles[i + j], tone) : pw.SizedBox()),
                ],
              ],
            ),
          ),
      ],
    );

pw.Widget _kpi(_Kpi k, _Tone tone) => pw.Container(
      padding: const pw.EdgeInsets.fromLTRB(10, 8, 10, 8),
      decoration: pw.BoxDecoration(color: tone.tint, borderRadius: pw.BorderRadius.circular(5)),
      child: pw.Column(
        crossAxisAlignment: pw.CrossAxisAlignment.start,
        children: [
          _t(k.label.toUpperCase(), size: 6.8, bold: true, color: _muted, letterSpacing: 0.5),
          pw.SizedBox(height: 3),
          // Long figures shrink to the tile instead of wrapping mid-number.
          pw.FittedBox(
            fit: pw.BoxFit.scaleDown,
            alignment: pw.Alignment.centerLeft,
            child: _t(k.value, size: 13, bold: true, color: k.color ?? _ink),
          ),
          if (k.caption != null) ...[
            pw.SizedBox(height: 2),
            _t(k.caption!, size: 7, color: _muted),
          ],
        ],
      ),
    );

pw.Widget _rate(String label, double pct, PdfColor color) {
  final filled = (pct.clamp(0, 100) * 10).round();
  return pw.Column(
    crossAxisAlignment: pw.CrossAxisAlignment.stretch,
    children: [
      pw.Row(
        children: [
          pw.Expanded(child: _t(label, size: 8.5, bold: true)),
          _t('${pct.toStringAsFixed(1)}%', size: 8.5, bold: true, color: color),
        ],
      ),
      pw.SizedBox(height: 4),
      pw.Container(
        height: 6,
        decoration: pw.BoxDecoration(color: _hairline, borderRadius: pw.BorderRadius.circular(3)),
        child: pw.Row(
          crossAxisAlignment: pw.CrossAxisAlignment.stretch,
          children: [
            if (filled > 0)
              pw.Expanded(
                flex: filled,
                child: pw.Container(
                  decoration: pw.BoxDecoration(color: color, borderRadius: pw.BorderRadius.circular(3)),
                ),
              ),
            if (filled < 1000) pw.Expanded(flex: 1000 - filled, child: pw.SizedBox()),
          ],
        ),
      ),
    ],
  );
}

class _Col {
  const _Col(this.label, this.flex, {this.right = false});
  final String label;
  final double flex;
  final bool right;
}

/// A striped table under an accent header row that repeats on every page the
/// table runs onto, with an optional tinted total row.
pw.Widget _table(_Tone tone, List<_Col> cols, List<List<String>> rows, {List<String>? total}) {
  pw.Widget cell(String text, _Col col, {bool bold = false, PdfColor color = _ink, double size = 8.2}) => pw.Padding(
        padding: const pw.EdgeInsets.symmetric(horizontal: 6, vertical: 4.5),
        child: _t(text,
            size: size, bold: bold, color: color, align: col.right ? pw.TextAlign.right : pw.TextAlign.left),
      );

  return pw.Table(
    columnWidths: {for (var i = 0; i < cols.length; i++) i: pw.FlexColumnWidth(cols[i].flex)},
    border: const pw.TableBorder(horizontalInside: pw.BorderSide(color: _hairline, width: 0.5)),
    defaultVerticalAlignment: pw.TableCellVerticalAlignment.middle,
    children: [
      pw.TableRow(
        repeat: true,
        decoration: pw.BoxDecoration(color: tone.accent),
        children: [
          for (final c in cols) cell(c.label.toUpperCase(), c, bold: true, color: PdfColors.white, size: 7),
        ],
      ),
      for (var r = 0; r < rows.length; r++)
        pw.TableRow(
          decoration: r.isOdd ? const pw.BoxDecoration(color: _zebra) : null,
          children: [for (var i = 0; i < cols.length; i++) cell(rows[r][i], cols[i])],
        ),
      if (total != null)
        pw.TableRow(
          decoration: pw.BoxDecoration(color: tone.tintStrong),
          children: [for (var i = 0; i < cols.length; i++) cell(total[i], cols[i], bold: true)],
        ),
    ],
  );
}

// ---- formatting ------------------------------------------------------------

/// Column label with the currency, e.g. `Sales (QAR)` — cells then print plain
/// grouped numbers, which is what fits a table column.
String _withCurrency(String label) {
  final symbol = Money.symbol.trim();
  return symbol.isEmpty ? label : '$label ($symbol)';
}

String _amt(double v) => Money.plain(v);

String _share(double part, double whole) {
  if (whole == 0) return '-';
  final pct = part / whole * 100;
  return '${pct.toStringAsFixed(pct.abs() >= 10 ? 0 : 1)}%';
}

String _count(int n, String noun) => '$n $noun${n == 1 ? '' : 's'}';

String _day(String iso) {
  final d = DateTime.tryParse(iso);
  return d == null ? iso : DateFormat('EEE, d MMM yyyy').format(d);
}

String _titleCase(String s) => s.isEmpty ? s : s[0].toUpperCase() + s.substring(1);
