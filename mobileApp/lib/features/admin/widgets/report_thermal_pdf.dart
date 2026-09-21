import 'dart:typed_data';

import 'package:intl/intl.dart';
import 'package:pdf/pdf.dart';
import 'package:pdf/widgets.dart' as pw;

import 'package:invo/features/admin/widgets/report_pdf.dart' show ReportPdfBrand;
import 'package:invo/shared/domain/helpers/formatters.dart';
import 'package:invo/shared/domain/models/index.dart';
import 'package:invo/shared/widgets/receipt_pdf.dart';

/// The Reports screen on the thermal roll — the A4 report's sections folded
/// down to the till's paper so it goes through `printRollPdf`: straight to a
/// paired printer, or the print dialog. [buildOverviewThermalPdf] lays out
/// Sales Overview; [buildBreakdownThermalPdf] lays out Item, Category and
/// Staff Sales.
///
/// Where the A4 report closes on the top items, the roll closes on sales by
/// category ([ReportExport.categories] — export with `withCategories`).
///
/// Laid out for the roll it prints on. Both widths get the same sections;
/// what changes is how much each row carries — 58 mm drops the secondary
/// columns (a method's sales/returns split, a day's discount, a category's
/// share) and keeps the figure each table is read for. Nothing prints just to
/// say zero: a zero figure and an all-zero column are left off. Pure black on white:
/// a thermal head burns 1-bit dots, so tints and greys are left out, the
/// section heads print as black bands, and the signed-in person's row prints
/// white on black. IBM Plex Sans Arabic is the base font — staff and item
/// names can be Arabic, and only as the base font does Arabic shape.
Future<Uint8List> buildOverviewThermalPdf(
  ReportExport data,
  ReportPdfBrand brand,
  PaperWidth width, {
  DateTime? printedAt,
}) async {
  final (regular, bold) = await loadBundledArabicFonts();
  final theme = regular == null
      ? pw.ThemeData.withFont(base: pw.Font.helvetica(), bold: pw.Font.helveticaBold())
      : pw.ThemeData.withFont(base: regular, bold: bold ?? regular);
  final r = _Roll(width);
  final range = Dates.range(data.startDate, data.endDate);
  final printed = DateFormat('dd/MM/yyyy hh:mm a').format(printedAt ?? DateTime.now());

  final doc = pw.Document(
    title: '${data.kind.title} · $range',
    author: brand.companyName.trim().isEmpty ? null : brand.companyName.trim(),
    creator: 'QLOUD POS',
  );
  doc.addPage(
    pw.Page(
      pageFormat: r.format,
      theme: theme,
      margin: pw.EdgeInsets.all(r.margin),
      build: (_) => pw.Column(
        crossAxisAlignment: pw.CrossAxisAlignment.stretch,
        mainAxisSize: pw.MainAxisSize.min,
        children: [
          ..._masthead(data, brand, r, range),
          ..._overview(data, brand, r),
          ..._footer(brand, r, printed),
        ],
      ),
    ),
  );
  return doc.save();
}

/// Item Sales, Category Sales or Staff Sales on the thermal roll — the same
/// ranked list as the A4 breakdown, laid out for the till's paper.
Future<Uint8List> buildBreakdownThermalPdf(
  ReportExport data,
  ReportPdfBrand brand,
  PaperWidth width, {
  DateTime? printedAt,
}) async {
  final (regular, bold) = await loadBundledArabicFonts();
  final theme = regular == null
      ? pw.ThemeData.withFont(base: pw.Font.helvetica(), bold: pw.Font.helveticaBold())
      : pw.ThemeData.withFont(base: regular, bold: bold ?? regular);
  final r = _Roll(width);
  final range = Dates.range(data.startDate, data.endDate);
  final printed = DateFormat('dd/MM/yyyy hh:mm a').format(printedAt ?? DateTime.now());

  final doc = pw.Document(
    title: '${data.kind.title} · $range',
    author: brand.companyName.trim().isEmpty ? null : brand.companyName.trim(),
    creator: 'QLOUD POS',
  );
  doc.addPage(
    pw.Page(
      pageFormat: r.format,
      theme: theme,
      margin: pw.EdgeInsets.all(r.margin),
      build: (_) => pw.Column(
        crossAxisAlignment: pw.CrossAxisAlignment.stretch,
        mainAxisSize: pw.MainAxisSize.min,
        children: [
          ..._masthead(data, brand, r, range),
          ..._breakdown(data, brand, r),
          ..._footer(brand, r, printed),
        ],
      ),
    ),
  );
  return doc.save();
}

/// The roll being printed on: its page, margin and type scale.
class _Roll {
  _Roll(PaperWidth width)
      : wide = width != PaperWidth.mm58,
        format = width == PaperWidth.mm58 ? PdfPageFormat.roll57 : PdfPageFormat.roll80,
        margin = width == PaperWidth.mm58 ? 6 : 10;

  /// 80 mm — room for the secondary columns.
  final bool wide;
  final PdfPageFormat format;
  final double margin;

  /// Type and spacing, scaled down for 58 mm like the receipt.
  double s(double v) => wide ? v : v * 0.84;
}

// ---- masthead / footer --------------------------------------------------------

List<pw.Widget> _masthead(ReportExport d, ReportPdfBrand b, _Roll r, String range) {
  final logo = pdfLogo(b.logo, width: r.s(64));
  final company = b.companyName.trim();
  // With no company name printed, the branch takes the headline rather than
  // sitting under an empty one.
  final headline = company.isNotEmpty ? company : b.branchName;
  final subline = company.isNotEmpty ? b.branchName : '';
  final currency = Money.symbol.trim();

  return [
    if (logo != null) pw.Padding(padding: const pw.EdgeInsets.only(bottom: 4), child: pw.Center(child: logo)),
    if (headline.isNotEmpty) _centred(headline, r.s(12), bold: true),
    if (subline.isNotEmpty) ...[
      pw.SizedBox(height: 1),
      _centred(subline, r.s(9)),
    ],
    _rule(r),
    _centred(d.kind.title.toUpperCase(), r.s(13), bold: true, letterSpacing: 1.2),
    pw.SizedBox(height: 2),
    _centred(range, r.s(9.5), bold: true),
    // Tables print bare numbers to save width; the currency is said once here.
    if (currency.isNotEmpty) ...[
      pw.SizedBox(height: 1),
      _centred('All amounts in $currency', r.s(7.5)),
    ],
    _rule(r, solid: true),
  ];
}

List<pw.Widget> _footer(ReportPdfBrand b, _Roll r, String printed) {
  final by = b.preparedBy.trim();
  return [
    _rule(r),
    _centred('Printed $printed', r.s(8)),
    if (by.isNotEmpty) ...[
      pw.SizedBox(height: 1),
      _centred('by $by', r.s(8), bold: true),
    ],
    pw.SizedBox(height: r.s(6)),
    _centred('*** END OF REPORT ***', r.s(8), bold: true, letterSpacing: 0.8),
    pw.SizedBox(height: 2),
    _centred('Powered by QLOUD POS', r.s(7)),
  ];
}

// ---- Sales Overview --------------------------------------------------------

List<pw.Widget> _overview(ReportExport d, ReportPdfBrand b, _Roll r) {
  final ov = d.overview;
  if (ov == null) return [_band('SALES PERFORMANCE', r), _note('No figures for this period.', r)];
  final s = ov.summary;
  final pay = ov.payments;
  final methods = pay.methods;
  final avg = s.noOfSales > 0 ? s.netSales / s.noOfSales : 0.0;
  final meInStaff = ov.employees.any((e) => _isMe(e.id, b));
  final cats = d.categories;
  final catAmount = cats.fold<double>(0, (a, c) => a + c.amount);
  // Figures that are zero print nothing, and a column that is zero all the way
  // down is left off. With no returns a method's Sales is its Net, so the
  // Sales/Ret split goes too; it needs 80 mm either way.
  final split = r.wide && methods.any((m) => m.returns != 0);
  final discCol = r.wide && d.days.any((x) => x.discount != 0);
  // Nor does a row that only repeats a figure already on the roll: with no
  // discount gross is net, and net is in the box at the top; item total is
  // covered by gross when they agree, and a products (or services) line by
  // item total when every sale was one kind; with no returns the method
  // table's Total is the net payment.
  final grossIsNet = _same(s.grossSales, s.netSales);
  final itemTotal = !_same(s.totalItem, s.grossSales);
  final products = s.productSale != 0 && !_same(s.productSale, s.totalItem);
  final services = s.serviceSale != 0 && !_same(s.serviceSale, s.totalItem);
  final payLines = methods.isEmpty || !_same(pay.salesTotal, pay.netPayment);

  return [
    pw.SizedBox(height: r.s(4)),
    _hero(
      'NET SALES',
      Money.of(s.netSales),
      '${_count(s.noOfSales, 'invoice')}  ·  avg ${s.noOfSales > 0 ? Money.plain(avg) : '-'}',
      r,
    ),

    // ---- sales performance ----
    _band('SALES PERFORMANCE', r),
    if (!grossIsNet) ...[
      _line('Gross sales', _amt(s.grossSales), r),
      if (s.discount != 0) _line('Discounts', _amt(s.discount), r),
      _line('Net sales', _amt(s.netSales), r, strong: true),
      pw.SizedBox(height: r.s(5)),
    ],
    _tiles([
      ('INVOICES', '${s.noOfSales}'),
      ('AVG TICKET', s.noOfSales > 0 ? _amt(avg) : '-'),
      ('RETURNS', '${s.noOfSalesReturns}'),
    ], r),
    if (itemTotal || products || services) pw.SizedBox(height: r.s(5)),
    if (itemTotal) _line('Item total', _amt(s.totalItem), r),
    if (products) _line('Products', _amt(s.productSale), r),
    if (services) _line('Services', _amt(s.serviceSale), r),
    // Two equal bars say nothing the figures above don't (both are 100% on a
    // day with no returns and nothing owed), so they print only when they differ.
    if (s.successRate != s.collectionRate) ...[
      pw.SizedBox(height: r.s(5)),
      _rate('Sales success rate', s.successRate, r),
      pw.SizedBox(height: r.s(4)),
      _rate('Collection rate', s.collectionRate, r),
    ],

    // ---- payments ----
    _band('PAYMENTS', r),
    if (payLines) ...[
      _line('Sales payments', _amt(pay.salesTotal), r, caption: _count(pay.salesTransactions, 'transaction')),
      if (pay.returnsTotal != 0)
        _line('Returns payments', _amt(pay.returnsTotal), r, caption: _count(pay.returnsTransactions, 'return')),
      _line('Net payments', _amt(pay.netPayment), r,
          strong: true, ruled: true, caption: _count(pay.totalTransactions, 'transaction')),
    ],
    if (methods.isNotEmpty) ...[
      if (payLines) pw.SizedBox(height: r.s(6)),
      _table(
        r,
        [
          const _Col('Method', 2.2),
          const _Col('Txn', 0.8, right: true),
          if (split) ...[
            const _Col('Sales', 1.6, right: true),
            const _Col('Ret', 1.4, right: true),
          ],
          const _Col('Net', 1.7, right: true),
        ],
        [
          for (final m in methods)
            [
              m.method,
              '${m.transactions}',
              if (split) ...[_amt(m.sales), _amt(m.returns)],
              _amt(m.net),
            ],
        ],
        total: [
          'Total',
          '${methods.fold<int>(0, (a, m) => a + m.transactions)}',
          if (split) ...[
            _amt(methods.fold<double>(0, (a, m) => a + m.sales)),
            _amt(methods.fold<double>(0, (a, m) => a + m.returns)),
          ],
          _amt(methods.fold<double>(0, (a, m) => a + m.net)),
        ],
      ),
    ],

    // ---- sales by day ----
    if (d.days.isNotEmpty) ...[
      _band('SALES BY DAY', r, note: _count(d.days.length, 'day')),
      _table(
        r,
        [
          const _Col('Date', 1.5),
          const _Col('Inv', 0.8, right: true),
          const _Col('Gross', 1.6, right: true),
          if (discCol) const _Col('Disc', 1.3, right: true),
          const _Col('Paid', 1.6, right: true),
        ],
        [
          for (final day in d.days)
            [
              _day(day.date, d),
              '${day.invoices}',
              _amt(day.gross),
              if (discCol) _amt(day.discount),
              _amt(day.paid),
            ],
        ],
        total: [
          'Total',
          '${d.days.fold<int>(0, (a, x) => a + x.invoices)}',
          _amt(d.days.fold<double>(0, (a, x) => a + x.gross)),
          if (discCol) _amt(d.days.fold<double>(0, (a, x) => a + x.discount)),
          _amt(d.days.fold<double>(0, (a, x) => a + x.paid)),
        ],
      ),
    ] else if (!d.daysComplete) ...[
      _band('SALES BY DAY', r),
      _note('Too many invoices in this range to list day by day — print a shorter range for this table.', r),
    ],

    // ---- top staff ----
    if (ov.employees.isNotEmpty) ...[
      _band('TOP STAFF', r, note: 'Top ${ov.employees.length}'),
      _table(
        r,
        const [
          _Col('#', 0.45),
          _Col('Staff', 3),
          _Col('Qty', 0.9, right: true),
          _Col('Sales', 1.7, right: true),
        ],
        [
          for (var i = 0; i < ov.employees.length; i++)
            ['${i + 1}', ov.employees[i].name, qtyLabel(ov.employees[i].quantity), _amt(ov.employees[i].total)],
        ],
        highlight: {
          for (var i = 0; i < ov.employees.length; i++)
            if (_isMe(ov.employees[i].id, b)) i,
        },
      ),
      // On paper there is no "you" — say whose row is picked out.
      if (meInStaff) _note('Highlighted: ${b.preparedBy}, who printed this report.', r),
    ],

    // ---- sales by category ----
    if (cats.isNotEmpty) ...[
      _band('SALES BY CATEGORY', r,
          note: cats.length < d.categoryCount
              ? 'Top ${cats.length} of ${d.categoryCount}'
              : (cats.length == 1 ? '1 category' : '${cats.length} categories')),
      _table(
        r,
        [
          const _Col('#', 0.45),
          const _Col('Category', 2.6),
          const _Col('Qty', 0.9, right: true),
          const _Col('Amount', 1.7, right: true),
          if (r.wide) const _Col('%', 0.75, right: true),
        ],
        [
          for (var i = 0; i < cats.length; i++)
            [
              '${i + 1}',
              cats[i].name,
              qtyLabel(cats[i].quantity),
              _amt(cats[i].amount),
              if (r.wide) _share(cats[i].amount, catAmount),
            ],
        ],
        total: [
          '',
          'Total',
          qtyLabel(cats.fold<double>(0, (a, c) => a + c.quantity)),
          _amt(catAmount),
          if (r.wide) '',
        ],
      ),
    ],
  ];
}

// ---- Item / Category / Staff Sales -----------------------------------------

List<pw.Widget> _breakdown(ReportExport d, ReportPdfBrand b, _Roll r) {
  final kind = d.kind;
  final heading = switch (kind) {
    ReportExportKind.items => 'ITEM BREAKDOWN',
    ReportExportKind.categories => 'CATEGORY BREAKDOWN',
    _ => 'STAFF BREAKDOWN',
  };
  final lines = d.lines;
  if (lines.isEmpty) return [_band(heading, r), _note('No sales in this period.', r)];
  // Share of the metric the list is ranked by, like the bars on screen.
  final byQty = kind != ReportExportKind.stylists && d.rankByQty;
  final whole = byQty ? d.totalQuantity : d.totalAmount;
  final itemsSold = lines.fold<int>(0, (a, l) => a + l.items);
  final shareOf = [for (final l in lines) byQty ? l.quantity : l.amount];

  return [
    pw.SizedBox(height: r.s(4)),
    _tiles(
      switch (kind) {
        ReportExportKind.items => [
            ('ITEMS', '${d.lineCount}'),
            ('QTY SOLD', qtyLabel(d.totalQuantity)),
            ('NET AMT', _amt(d.totalAmount)),
          ],
        ReportExportKind.categories => [
            ('CATEGORIES', '${d.lineCount}'),
            ('QTY SOLD', qtyLabel(d.totalQuantity)),
            ('NET AMT', _amt(d.totalAmount)),
          ],
        _ => [('STAFF', '${d.lineCount}'), ('ITEMS SOLD', '$itemsSold'), ('NET REV', _amt(d.totalAmount))],
      },
      r,
    ),
    _band(
      heading,
      r,
      note: d.truncated
          ? 'Top ${lines.length} of ${d.lineCount}'
          : switch (kind) {
              ReportExportKind.items => _count(lines.length, 'item'),
              ReportExportKind.categories => lines.length == 1 ? '1 category' : '${lines.length} categories',
              _ => '${lines.length} staff',
            },
    ),
    switch (kind) {
      ReportExportKind.items || ReportExportKind.categories => _table(
          r,
          [
            const _Col('#', 0.45),
            _Col(kind == ReportExportKind.items ? 'Item' : 'Category', kind == ReportExportKind.items ? 3 : 2.6),
            const _Col('Qty', 0.9, right: true),
            const _Col('Amount', 1.7, right: true),
            if (r.wide) const _Col('%', 0.75, right: true),
          ],
          [
            for (var i = 0; i < lines.length; i++)
              [
                '${i + 1}',
                lines[i].name,
                qtyLabel(lines[i].quantity),
                _amt(lines[i].amount),
                if (r.wide) _share(shareOf[i], whole),
              ],
          ],
          total: ['', 'Total', qtyLabel(d.totalQuantity), _amt(d.totalAmount), if (r.wide) ''],
        ),
      _ => _table(
          r,
          const [
            _Col('#', 0.45),
            _Col('Staff', 3),
            _Col('Items', 0.9, right: true),
            _Col('Revenue', 1.7, right: true),
          ],
          [
            for (var i = 0; i < lines.length; i++)
              ['${i + 1}', lines[i].name, '${lines[i].items}', _amt(lines[i].amount)],
          ],
          total: ['', 'Total', '$itemsSold', _amt(d.totalAmount)],
          highlight: {
            for (var i = 0; i < lines.length; i++)
              if (_isMe(lines[i].id, b)) i,
          },
        ),
    },
    if (kind == ReportExportKind.stylists && lines.any((l) => _isMe(l.id, b)))
      _note('Highlighted: ${b.preparedBy}, who printed this report.', r),
    if (d.truncated)
      _note('The list stops at the top ${lines.length} of ${d.lineCount}; the totals cover all of them.', r),
  ];
}

// ---- building blocks -------------------------------------------------------

const _black = PdfColors.black;
const _white = PdfColors.white;

final _arabic = RegExp(r'[؀-ۿݐ-ݿࢠ-ࣿﭐ-﷿ﹰ-﻿]');

/// Text with Arabic runs marked rtl, so they shape and join instead of
/// printing reversed.
pw.Widget _t(
  String text,
  double size, {
  bool bold = false,
  PdfColor color = _black,
  double letterSpacing = 0,
  pw.TextAlign? align,
}) =>
    pw.Text(
      text,
      textAlign: align,
      textDirection: _arabic.hasMatch(text) ? pw.TextDirection.rtl : null,
      style: pw.TextStyle(
        fontSize: size,
        fontWeight: bold ? pw.FontWeight.bold : pw.FontWeight.normal,
        color: color,
        letterSpacing: letterSpacing,
      ),
    );

pw.Widget _centred(String text, double size, {bool bold = false, double letterSpacing = 0}) =>
    pw.Center(child: _t(text, size, bold: bold, letterSpacing: letterSpacing, align: pw.TextAlign.center));

/// A number that shrinks to its cell rather than wrapping mid-figure.
pw.Widget _fit(pw.Widget child, {pw.Alignment alignment = pw.Alignment.centerRight}) =>
    pw.FittedBox(fit: pw.BoxFit.scaleDown, alignment: alignment, child: child);

pw.Widget _rule(_Roll r, {bool solid = false}) => pw.Padding(
      padding: pw.EdgeInsets.symmetric(vertical: r.s(5)),
      child: pw.Divider(
        height: 0.8,
        thickness: solid ? 1.1 : 0.7,
        color: _black,
        borderStyle: solid ? pw.BorderStyle.solid : pw.BorderStyle.dashed,
      ),
    );

/// A section head: white on a black band, with an optional note at the right.
pw.Widget _band(String title, _Roll r, {String? note}) => pw.Container(
      margin: pw.EdgeInsets.only(top: r.s(11), bottom: r.s(5)),
      padding: pw.EdgeInsets.symmetric(horizontal: r.s(5), vertical: r.s(2.5)),
      color: _black,
      child: pw.Row(
        children: [
          pw.Expanded(child: _t(title, r.s(8.4), bold: true, color: _white, letterSpacing: 0.8)),
          // Bold: thin white-on-black type loses its strokes at the head.
          if (note != null) _t(note, r.s(7), bold: true, color: _white),
        ],
      ),
    );

pw.Widget _note(String text, _Roll r) => pw.Padding(
      padding: pw.EdgeInsets.only(top: r.s(4)),
      child: _t(text, r.s(7.2)),
    );

/// The headline figure in a double-ruled box.
pw.Widget _hero(String label, String value, String caption, _Roll r) => pw.Container(
      padding: const pw.EdgeInsets.all(1.6),
      decoration: pw.BoxDecoration(border: pw.Border.all(color: _black, width: 1.3)),
      child: pw.Container(
        padding: pw.EdgeInsets.symmetric(horizontal: r.s(6), vertical: r.s(6)),
        decoration: pw.BoxDecoration(border: pw.Border.all(color: _black, width: 0.5)),
        child: pw.Column(
          children: [
            _t(label, r.s(8), bold: true, letterSpacing: 1.2),
            pw.SizedBox(height: r.s(2)),
            _fit(_t(value, r.s(18), bold: true), alignment: pw.Alignment.center),
            pw.SizedBox(height: r.s(2)),
            _t(caption, r.s(7.5), align: pw.TextAlign.center),
          ],
        ),
      ),
    );

/// Label left, figure right. [caption] prints small under the label; [strong]
/// sets the row bold, and [ruled] draws a line over it, like a total.
pw.Widget _line(String label, String value, _Roll r, {String? caption, bool strong = false, bool ruled = false}) =>
    pw.Container(
      margin: pw.EdgeInsets.only(top: ruled ? r.s(3) : 0),
      padding: pw.EdgeInsets.only(top: ruled ? r.s(3) : r.s(1.5), bottom: r.s(1.5)),
      decoration: ruled
          ? const pw.BoxDecoration(border: pw.Border(top: pw.BorderSide(color: _black, width: 0.8)))
          : null,
      child: pw.Row(
        crossAxisAlignment: pw.CrossAxisAlignment.start,
        children: [
          pw.Expanded(
            flex: 5,
            child: pw.Column(
              crossAxisAlignment: pw.CrossAxisAlignment.start,
              children: [
                _t(label, r.s(strong ? 9 : 8.4), bold: strong),
                if (caption != null) _t(caption, r.s(6.8)),
              ],
            ),
          ),
          pw.SizedBox(width: r.s(4)),
          // Aligned first: a FittedBox handed a tight width stretches tall.
          pw.Expanded(
            flex: 4,
            child: pw.Align(
              alignment: pw.Alignment.centerRight,
              child: _fit(_t(value, r.s(strong ? 9.5 : 8.6), bold: true)),
            ),
          ),
        ],
      ),
    );

/// Counts side by side in outlined cells — three across on either roll.
pw.Widget _tiles(List<(String, String)> tiles, _Roll r) => pw.Row(
      crossAxisAlignment: pw.CrossAxisAlignment.start,
      children: [
        for (var i = 0; i < tiles.length; i++) ...[
          if (i > 0) pw.SizedBox(width: r.s(4)),
          pw.Expanded(
            child: pw.Container(
              padding: pw.EdgeInsets.symmetric(horizontal: r.s(3), vertical: r.s(4)),
              decoration: pw.BoxDecoration(border: pw.Border.all(color: _black, width: 0.8)),
              child: pw.Column(
                children: [
                  _fit(_t(tiles[i].$1, r.s(6.4), bold: true, letterSpacing: 0.4), alignment: pw.Alignment.center),
                  pw.SizedBox(height: r.s(2)),
                  _fit(_t(tiles[i].$2, r.s(11), bold: true), alignment: pw.Alignment.center),
                ],
              ),
            ),
          ),
        ],
      ],
    );

/// A percentage as an outlined bar filled in black.
pw.Widget _rate(String label, double pct, _Roll r) {
  final filled = (pct.clamp(0, 100) * 10).round();
  return pw.Column(
    crossAxisAlignment: pw.CrossAxisAlignment.stretch,
    children: [
      pw.Row(
        children: [
          pw.Expanded(child: _t(label, r.s(8.2))),
          _t('${pct.toStringAsFixed(1)}%', r.s(8.6), bold: true),
        ],
      ),
      pw.SizedBox(height: r.s(2)),
      pw.Container(
        height: r.s(6),
        padding: const pw.EdgeInsets.all(1),
        decoration: pw.BoxDecoration(border: pw.Border.all(color: _black, width: 0.7)),
        child: pw.Row(
          crossAxisAlignment: pw.CrossAxisAlignment.stretch,
          children: [
            if (filled > 0) pw.Expanded(flex: filled, child: pw.Container(color: _black)),
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

/// A roll table: ruled header, dotted row lines, a ruled total row. Text
/// columns wrap; figure columns shrink to fit. [highlight] rows print white on
/// black.
pw.Widget _table(
  _Roll r,
  List<_Col> cols,
  List<List<String>> rows, {
  List<String>? total,
  Set<int> highlight = const {},
}) {
  pw.Widget cell(String text, _Col col, {bool bold = false, bool inverted = false, double? size}) {
    final t = _t(text, size ?? r.s(7.4),
        bold: bold, color: inverted ? _white : _black, align: col.right ? pw.TextAlign.right : null);
    return pw.Container(
      alignment: col.right ? pw.Alignment.centerRight : pw.Alignment.centerLeft,
      padding: pw.EdgeInsets.symmetric(horizontal: r.s(1.8), vertical: r.s(1.8)),
      // An empty cell has no size for a FittedBox to scale — pdf asserts on it.
      child: col.right && text.isNotEmpty ? _fit(t) : t,
    );
  }

  const ruleSide = pw.BorderSide(color: _black, width: 0.8);
  return pw.Table(
    columnWidths: {for (var i = 0; i < cols.length; i++) i: pw.FlexColumnWidth(cols[i].flex)},
    border: const pw.TableBorder(
      horizontalInside: pw.BorderSide(color: _black, width: 0.5, style: pw.BorderStyle.dotted),
    ),
    defaultVerticalAlignment: pw.TableCellVerticalAlignment.middle,
    children: [
      pw.TableRow(
        decoration: const pw.BoxDecoration(border: pw.Border(top: ruleSide, bottom: ruleSide)),
        children: [for (final c in cols) cell(c.label.toUpperCase(), c, bold: true, size: r.s(6.6))],
      ),
      for (var i = 0; i < rows.length; i++)
        pw.TableRow(
          decoration: highlight.contains(i) ? const pw.BoxDecoration(color: _black) : null,
          children: [
            for (var c = 0; c < cols.length; c++)
              cell(rows[i][c], cols[c], bold: highlight.contains(i), inverted: highlight.contains(i)),
          ],
        ),
      if (total != null)
        pw.TableRow(
          decoration: const pw.BoxDecoration(border: pw.Border(top: ruleSide, bottom: ruleSide)),
          children: [for (var c = 0; c < cols.length; c++) cell(total[c], cols[c], bold: true)],
        ),
    ],
  );
}

// ---- formatting ------------------------------------------------------------

bool _isMe(String id, ReportPdfBrand b) => b.preparedById.isNotEmpty && id == b.preparedById;

String _amt(double v) => Money.plain(v);

/// Equal to the cent — the server rounds every amount to two places.
bool _same(double a, double b) => (a - b).abs() < 0.005;

String _count(int n, String noun) => '$n $noun${n == 1 ? '' : 's'}';

/// Share of the whole, e.g. `38%` / `4.5%`; '-' when there is no whole to share.
String _share(double part, double whole) {
  if (whole <= 0) return '-';
  final pct = part / whole * 100;
  return '${pct.toStringAsFixed(pct.abs() >= 10 ? 0 : 1)}%';
}

/// `01 Sep` — the year is in the masthead; a range across years keeps it.
String _day(String iso, ReportExport d) {
  final day = DateTime.tryParse(iso);
  if (day == null) return iso;
  return DateFormat(d.startDate.year == d.endDate.year ? 'dd MMM' : 'dd/MM/yy').format(day);
}
