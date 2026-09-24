import 'dart:typed_data';

import 'package:intl/intl.dart';
import 'package:pdf/pdf.dart';
import 'package:pdf/widgets.dart' as pw;

import 'package:invo/shared/domain/helpers/formatters.dart';
import 'package:invo/shared/domain/models/index.dart';
import 'package:invo/shared/widgets/receipt_pdf.dart';

/// A day session's "Sale Bill Report" on the thermal roll — section for section
/// the web's `sale.day-session-print`, laid out here like the app's receipts so
/// it goes through `printRollPdf`: straight to a paired printer, or the print
/// dialog. IBM Plex Sans Arabic is the base font — branch and staff names can
/// be Arabic, and only as the base font does Arabic shape (see receipt_pdf.dart).
///
/// When [highlightUserId] opened or closed the session, that name prints white
/// on black — the one highlight a thermal roll can carry.
Future<Uint8List> buildDaySessionThermalPdf(
  DaySessionReport report,
  PrintSettings settings, {
  DateTime? printedAt,
  String highlightUserId = '',
  TransactionsLayout layout = TransactionsLayout.combined,
}) async {
  final (regular, bold) = await loadBundledArabicFonts();
  final theme = regular == null
      ? pw.ThemeData.withFont(base: pw.Font.helvetica(), bold: pw.Font.helveticaBold())
      : pw.ThemeData.withFont(base: regular, bold: bold ?? regular);

  final is58 = settings.width == PaperWidth.mm58;
  final k = is58 ? 0.84 : 1.0;
  double s(double v) => v * k;

  final at = printedAt ?? DateTime.now();
  final printed = DateFormat('dd/MM/yyyy hh:mm a').format(at);
  final session = report.session;
  final t = report.totals;
  final logo = pdfLogo(settings.logo, width: s(70));
  final company = settings.companyName.trim();
  bool isMe(String id) => highlightUserId.isNotEmpty && id == highlightUserId;

  final doc = pw.Document(title: 'Sale Bill Report #${session.id}');
  doc.addPage(
    pw.Page(
      pageFormat: is58 ? PdfPageFormat.roll57 : PdfPageFormat.roll80,
      theme: theme,
      margin: pw.EdgeInsets.all(is58 ? 6 : 10),
      build: (_) => pw.Column(
        crossAxisAlignment: pw.CrossAxisAlignment.stretch,
        mainAxisSize: pw.MainAxisSize.min,
        children: [
          // ---- header ----
          if (logo != null)
            pw.Padding(padding: const pw.EdgeInsets.only(bottom: 3), child: pw.Center(child: logo)),
          if (company.isNotEmpty) _centred(company, s(12)),
          if (session.branch.isNotEmpty) _centred(session.branch, s(10)),
          if (session.branchLocation.isNotEmpty) _centred(session.branchLocation, s(8.5)),
          if (session.branchMobile.isNotEmpty) _centred('Mobile : ${session.branchMobile}', s(8.5)),
          pw.SizedBox(height: s(6)),
          _centred('SALE BILL REPORT', s(12.5)),
          pw.SizedBox(height: 2),
          // A session still open prints today as its end, as the web does.
          _centred('${_day(session.openedAt, at)} TO ${_day(session.closedAt, at)}', s(8.5)),
          _rule(),
          // ---- session details ----
          _heading('SESSION DETAILS', s),
          _pairs([
            ('Session No', '#${session.id}'),
            ('Branch', _orNa(session.branch)),
            ('Status', session.status.toUpperCase()),
            ('Opened By', _orNa(session.openedBy)),
            if (session.closedBy.isNotEmpty) ('Closed By', session.closedBy),
            ('Printed Time', printed),
          ], s, bordered: false, labelFlex: 0.8, highlight: {
            if (isMe(session.openedById)) 3,
            if (session.closedBy.isNotEmpty && isMe(session.closedById)) 4,
          }),
          _rule(),
          // ---- transactions ----
          _heading('SALE TRANSACTIONS', s),
          layout == TransactionsLayout.combined
              ? _transactionsGrid(s, report.transactions)
              : _transactionsGridDetailed(s, report.transactions),
          // Nothing outstanding and nothing collected against an older bill
          // leaves these two off the roll entirely — an empty table is paper
          // spent on saying nothing.
          if (report.dues.isNotEmpty) ...[
            pw.SizedBox(height: s(6)),
            _heading('DUE AMOUNT DETAILS', s),
            _grid(
              s,
              head: const ['Type', 'Reference', 'Due Amount'],
              flex: const [1.2, 1.6, 1.4],
              right: const {2},
              rows: [
                for (final due in report.dues) _GridRow([due.source, _orNa(due.referenceNo), _amt(due.dueAmount)]),
              ],
              empty: 'No due amounts.',
            ),
          ],
          if (report.duePayments.isNotEmpty) ...[
            pw.SizedBox(height: s(6)),
            _heading('DUE PAYMENT RECEIVED', s),
            _grid(
              s,
              head: const ['Type', 'Reference', 'Payment Method', 'Amount'],
              flex: const [1.1, 1.4, 1.3, 1.2],
              right: const {3},
              rows: [
                for (final pay in report.duePayments)
                  _GridRow([_orNa(pay.source), _orNa(pay.referenceNo), pay.paymentMethod, _amt(pay.amount)]),
              ],
              empty: 'No due payment receipts.',
            ),
          ],
          _rule(),
          // ---- totals ----
          _heading('TOTAL SUMMARY', s),
          _pairs([
            ('TOTAL CREDIT (UNPAID)', _amt(t.credit)),
            ('TOTAL CASH (INVOICE)', _amt(t.cash)),
            ('TOTAL CARD (INVOICE)', _amt(t.card)),
            ('TOTAL SALE AMOUNT', _amt(t.saleAmount)),
            ('TOTAL PAYMENT (INVOICE)', _amt(t.paymentTotal)),
            ('TOTAL DUE PAYMENT CASH', _amt(t.dueCash)),
            ('TOTAL DUE PAYMENT CARD', _amt(t.dueCard)),
            ('TOTAL DUE PAYMENT', _amt(t.dueTotal)),
            ('TOTAL CARD (INVOICE + DUE)', _amt(t.cardWithDue)),
            ('TOTAL CASH (INVOICE + DUE)', _amt(t.cashWithDue)),
            ('GRAND TOTAL PAYMENT', _amt(t.grandTotalPayment)),
          ], s),
          _rule(),
          // ---- footer ----
          _centred('Printed: $printed', s(8.5)),
          if (settings.footerEnglish.trim().isNotEmpty) ...[
            pw.SizedBox(height: 3),
            _centred(settings.footerEnglish.trim(), s(8)),
          ],
          if (settings.style.isArabic && settings.footerArabic.trim().isNotEmpty) ...[
            pw.SizedBox(height: 3),
            _centred(settings.footerArabic.trim(), s(8)),
          ],
        ],
      ),
    ),
  );
  return doc.save();
}

/// How SALE TRANSACTIONS lays out a bill's payment method(s) against its
/// invoice row, on the thermal roll — [combined] folds them into the same
/// row, [detailed] gives each one a row of its own under the invoice, as the
/// web print does.
enum TransactionsLayout { combined, detailed }

final _arabic = RegExp(r'[؀-ۿݐ-ݿࢠ-ࣿﭐ-﷿ﹰ-﻿]');

/// Bold like the web slip, where every cell sits in a `<strong>`. Arabic runs
/// are marked rtl so they shape and join.
pw.Widget _text(String text, double size, {pw.TextAlign? align, PdfColor color = PdfColors.black}) => pw.Text(
      text,
      textAlign: align,
      textDirection: _arabic.hasMatch(text) ? pw.TextDirection.rtl : null,
      style: pw.TextStyle(fontSize: size, fontWeight: pw.FontWeight.bold, color: color),
    );

pw.Widget _centred(String text, double size) =>
    pw.Center(child: _text(text, size, align: pw.TextAlign.center));

pw.Widget _heading(String text, double Function(double) s) =>
    pw.Padding(padding: const pw.EdgeInsets.only(bottom: 3), child: _centred(text, s(9.5)));

pw.Widget _rule() => pw.Padding(
      padding: const pw.EdgeInsets.symmetric(vertical: 5),
      child: pw.Divider(height: 0.5, thickness: 0.7, color: PdfColors.black, borderStyle: pw.BorderStyle.dashed),
    );

/// [inverted] prints the text white on a black block — the signed-in person's
/// name.
pw.Widget _cell(String text, double size, {bool right = false, bool inverted = false}) {
  final align = right ? pw.TextAlign.right : pw.TextAlign.left;
  return pw.Container(
    alignment: right ? pw.Alignment.centerRight : pw.Alignment.centerLeft,
    padding: const pw.EdgeInsets.symmetric(horizontal: 3, vertical: 2),
    child: inverted
        ? pw.Container(
            color: PdfColors.black,
            padding: const pw.EdgeInsets.symmetric(horizontal: 4, vertical: 1),
            child: _text(text, size, align: align, color: PdfColors.white),
          )
        : _text(text, size, align: align),
  );
}

/// Label left, value right — the web's two-column tables.
pw.Widget _pairs(
  List<(String, String)> rows,
  double Function(double) s, {
  bool bordered = true,
  double labelFlex = 1.7,
  Set<int> highlight = const {},
}) =>
    pw.Table(
      border: bordered ? pw.TableBorder.all(width: 0.5, color: PdfColors.black) : null,
      columnWidths: {0: pw.FlexColumnWidth(labelFlex), 1: const pw.FlexColumnWidth(1)},
      children: [
        for (var i = 0; i < rows.length; i++)
          pw.TableRow(children: [
            _cell(rows[i].$1, s(8)),
            _cell(rows[i].$2, s(8), right: true, inverted: highlight.contains(i)),
          ]),
      ],
    );

class _GridRow {
  const _GridRow(this.cells, {this.firstRight = false});
  final List<String> cells;

  /// A payment line under its invoice — the web right-aligns the method name.
  final bool firstRight;
}

pw.Widget _grid(
  double Function(double) s, {
  required List<String> head,
  required List<double> flex,
  required Set<int> right,
  required List<_GridRow> rows,
  required String empty,
}) {
  final table = pw.Table(
    border: pw.TableBorder.all(width: 0.5, color: PdfColors.black),
    columnWidths: {for (var i = 0; i < flex.length; i++) i: pw.FlexColumnWidth(flex[i])},
    children: [
      pw.TableRow(children: [for (var i = 0; i < head.length; i++) _cell(head[i], s(7.5), right: right.contains(i))]),
      for (final row in rows)
        pw.TableRow(children: [
          for (var i = 0; i < row.cells.length; i++)
            _cell(row.cells[i], s(7.5), right: right.contains(i) || (i == 0 && row.firstRight)),
        ]),
    ],
  );
  if (rows.isNotEmpty) return table;
  // pdf tables have no colspan: the web's full-width "No …" row becomes a box
  // hung under the header, sharing its bottom border.
  return pw.Column(
    crossAxisAlignment: pw.CrossAxisAlignment.stretch,
    children: [
      table,
      pw.Container(
        padding: const pw.EdgeInsets.symmetric(vertical: 3),
        decoration: const pw.BoxDecoration(
          border: pw.Border(
            left: pw.BorderSide(width: 0.5),
            right: pw.BorderSide(width: 0.5),
            bottom: pw.BorderSide(width: 0.5),
          ),
        ),
        child: _centred(empty, s(7.5)),
      ),
    ],
  );
}

/// SALE TRANSACTIONS, one row per invoice — its payment method(s) sit in the
/// same row's Payment cell rather than a row of their own, so a split payment
/// costs a couple of extra lines in one cell instead of a whole extra row.
pw.Widget _transactionsGrid(double Function(double) s, List<DaySessionTransaction> transactions) {
  pw.Widget paymentCell(DaySessionTransaction tx) {
    if (tx.payments.isEmpty) return _cell('_', s(7.5), right: true);
    if (tx.payments.length == 1) {
      final p = tx.payments.first;
      return _cell('${p.method}  ${_amt(p.amount)}', s(7.5), right: true);
    }
    return pw.Container(
      alignment: pw.Alignment.centerRight,
      padding: const pw.EdgeInsets.symmetric(horizontal: 3, vertical: 2),
      child: pw.Column(
        crossAxisAlignment: pw.CrossAxisAlignment.end,
        children: [
          for (final p in tx.payments) _text('${p.method}  ${_amt(p.amount)}', s(7), align: pw.TextAlign.right),
        ],
      ),
    );
  }

  final table = pw.Table(
    border: pw.TableBorder.all(width: 0.5, color: PdfColors.black),
    columnWidths: const {0: pw.FlexColumnWidth(1.5), 1: pw.FlexColumnWidth(1.2), 2: pw.FlexColumnWidth(1.7)},
    defaultVerticalAlignment: pw.TableCellVerticalAlignment.middle,
    children: [
      pw.TableRow(children: [
        _cell('Reference', s(7.5)),
        _cell('Amount', s(7.5), right: true),
        _cell('Payment', s(7.5), right: true),
      ]),
      for (final tx in transactions)
        pw.TableRow(children: [_cell(_orNa(tx.referenceNo), s(7.5)), _cell(_amt(tx.amount), s(7.5), right: true), paymentCell(tx)]),
    ],
  );
  if (transactions.isNotEmpty) return table;
  // pdf tables have no colspan: the "No …" row becomes a box hung under the
  // header, sharing its bottom border — same trick as _grid's empty state.
  return pw.Column(
    crossAxisAlignment: pw.CrossAxisAlignment.stretch,
    children: [
      table,
      pw.Container(
        padding: const pw.EdgeInsets.symmetric(vertical: 3),
        decoration: const pw.BoxDecoration(
          border: pw.Border(
            left: pw.BorderSide(width: 0.5),
            right: pw.BorderSide(width: 0.5),
            bottom: pw.BorderSide(width: 0.5),
          ),
        ),
        child: _centred('No transactions.', s(7.5)),
      ),
    ],
  );
}

/// SALE TRANSACTIONS, the web print's layout: an invoice row, then one row
/// per payment method underneath it, repeating the reference.
pw.Widget _transactionsGridDetailed(double Function(double) s, List<DaySessionTransaction> transactions) => _grid(
      s,
      head: const ['Type', 'Reference', 'Amount', 'Payment'],
      flex: const [1.2, 1.5, 1.2, 1.2],
      right: const {2, 3},
      rows: [
        for (final tx in transactions) ...[
          _GridRow(['Invoice', _orNa(tx.referenceNo), _amt(tx.amount), '_']),
          for (final pay in tx.payments)
            _GridRow([pay.method, _orNa(tx.referenceNo), '_', _amt(pay.amount)], firstRight: true),
        ],
      ],
      empty: 'No transactions.',
    );

String _day(String wallClock, DateTime fallback) =>
    DateFormat('dd-MM-yyyy').format(DateTime.tryParse(wallClock) ?? fallback);

String _orNa(String v) => v.isEmpty ? 'N/A' : v;

/// Grouped, no symbol — the web slip's `currency()` is a bare number_format.
String _amt(double v) => Money.plain(v);
