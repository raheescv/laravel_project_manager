import 'dart:convert';

import 'package:flutter_test/flutter_test.dart';
import 'package:invo/features/admin/widgets/report_pdf.dart';
import 'package:invo/features/admin/widgets/report_thermal_pdf.dart';
import 'package:invo/shared/domain/helpers/formatters.dart';
import 'package:invo/shared/domain/models/index.dart';

/// The Staff Commission report on paper — A4 and both roll widths — for a
/// whole branch (with the printer's own lines highlighted) and for one person.
void main() {
  TestWidgetsFlutterBinding.ensureInitialized();

  ReportExport export({String staffName = '', int staffCount = 2}) => ReportExport(
        kind: ReportExportKind.commission,
        startDate: DateTime(2026, 10, 1),
        endDate: DateTime(2026, 10, 19),
        generatedAt: DateTime(2026, 10, 19, 9),
        lines: [
          ReportExportLine.commission(const {
            'employee_id': '14', 'employee_name': 'Maya Chen', 'product_name': 'Balayage',
            'quantity': 6, 'net_amount': 2400, 'commission_percentage': 10, 'commission': 240,
          }),
          // A product with no rate set, and an Arabic name.
          ReportExportLine.commission(const {
            'employee_id': '15', 'employee_name': 'ليام', 'product_name': 'شمع التصفيف',
            'quantity': 9, 'net_amount': 405, 'commission_percentage': 0, 'commission': 0,
          }),
        ],
        lineCount: 2,
        totalAmount: 240,
        totalQuantity: 15,
        totalNet: 2805,
        staffCount: staffCount,
        staffName: staffName,
      );

  const brand = ReportPdfBrand(companyName: 'Astra Salon', branchName: 'Downtown', preparedBy: 'Maya Chen', preparedById: '14');

  bool isPdf(List<int> bytes) => bytes.length > 1000 && ascii.decode(bytes.sublist(0, 5)) == '%PDF-';

  test('the commission line reads the API row', () {
    final line = export().lines.first;
    expect(line.name, 'Balayage');
    expect(line.staff, 'Maya Chen');
    expect(line.net, 2400);
    expect(line.rate, 10);
    expect(line.amount, 240);
    expect(pctLabel(7.5), '7.5%');
    expect(pctLabel(10), '10%');
    expect(pctLabel(0.25), '0.25%');
  });

  test('builds the A4 commission report for the branch and for one person', () async {
    expect(isPdf(await buildReportPdf(export(), brand)), isTrue);
    expect(isPdf(await buildReportPdf(export(staffName: 'Maya Chen', staffCount: 1), brand)), isTrue);
  });

  test('builds the commission report on both roll widths', () async {
    for (final width in PaperWidth.values) {
      expect(isPdf(await buildBreakdownThermalPdf(export(), brand, width)), isTrue, reason: width.label);
    }
    expect(isPdf(await buildBreakdownThermalPdf(export(staffName: 'Maya Chen', staffCount: 1), brand, PaperWidth.mm58)),
        isTrue);
  });

  test('an empty period still prints', () async {
    final empty = ReportExport(
      kind: ReportExportKind.commission,
      startDate: DateTime(2026, 10, 1),
      endDate: DateTime(2026, 10, 1),
      generatedAt: DateTime(2026, 10, 1),
    );
    expect(isPdf(await buildReportPdf(empty, brand)), isTrue);
    expect(isPdf(await buildBreakdownThermalPdf(empty, brand, PaperWidth.mm80)), isTrue);
  });
}
