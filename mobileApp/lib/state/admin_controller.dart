import 'package:flutter/foundation.dart';

import '../core/api_client.dart';
import '../core/api_service.dart';
import '../core/formatters.dart';
import '../models/models.dart';

class ReportRow {
  ReportRow({required this.title, required this.subtitle, required this.value, this.amount = 0});
  final String title;
  final String subtitle;
  final String value;
  final double amount;
}

/// Backs the Dashboard and the Reports suite (bill-wise / employee-wise).
class AdminController extends ChangeNotifier {
  AdminController(this.service) {
    final now = DateTime.now();
    final today = DateTime(now.year, now.month, now.day);
    startDate = today.subtract(const Duration(days: 6));
    endDate = today;
  }
  final ApiService service;

  bool loading = false;
  String? error;
  DashboardData? dashboard;
  List<ReportRow> topStylists = [];
  List<double> trendPoints = [];
  List<String> trendLabels = [];

  // Reports
  bool reportLoading = false;
  String? reportError;
  String reportType = 'employeewise'; // employeewise | billwise
  List<ReportRow> reportRows = [];
  double reportTotal = 0;

  // Reports date range (drives the overview KPIs, trend chart and table).
  late DateTime startDate;
  late DateTime endDate;
  String rangePreset = '7d'; // today | 7d | 30d | month | custom
  double rangeGross = 0;
  int rangeInvoices = 0;
  List<double> reportTrendPoints = [];
  List<String> reportTrendLabels = [];

  /// Apply a quick-range preset and reload all reports.
  void setPreset(String id) {
    final now = DateTime.now();
    final today = DateTime(now.year, now.month, now.day);
    switch (id) {
      case 'today':
        startDate = today;
        endDate = today;
      case '7d':
        startDate = today.subtract(const Duration(days: 6));
        endDate = today;
      case '30d':
        startDate = today.subtract(const Duration(days: 29));
        endDate = today;
      case 'month':
        startDate = DateTime(now.year, now.month, 1);
        endDate = today;
    }
    rangePreset = id;
    loadReports();
  }

  /// Apply a hand-picked custom range and reload all reports.
  void setCustomRange(DateTime start, DateTime end) {
    startDate = DateTime(start.year, start.month, start.day);
    endDate = DateTime(end.year, end.month, end.day);
    rangePreset = 'custom';
    loadReports();
  }

  Future<void> loadDashboard() async {
    loading = true;
    error = null;
    notifyListeners();
    try {
      dashboard = await service.dashboard();
    } on ApiException catch (e) {
      error = e.message;
    } catch (e) {
      error = 'Could not load the dashboard.';
    }
    // Derive top stylists + a 7-day revenue trend from the report endpoints.
    // Each is best-effort so a single failure never blanks the dashboard.
    try {
      final emp = await service.report(type: 'employeewise');
      final rows = (emp['rows'] as List?) ?? const [];
      topStylists = rows.map((e) {
        final m = Map<String, dynamic>.from(e);
        final rev = asNum(m['revenue']).toDouble();
        return ReportRow(
          title: asStr(m['employee_name']),
          subtitle: '${asNum(m['bills_count']).toInt()} bills',
          value: Money.of(rev),
          amount: rev,
        );
      }).toList()
        ..sort((a, b) => b.amount.compareTo(a.amount));
      topStylists = topStylists.take(4).toList();
    } catch (_) {/* keep whatever we had */}

    try {
      final bill = await service.report(type: 'billwise');
      final rows = (bill['rows'] as List?) ?? const [];
      final byDate = <String, double>{};
      for (final e in rows) {
        final m = Map<String, dynamic>.from(e);
        final d = asStr(m['date']);
        byDate[d] = (byDate[d] ?? 0) + asNum(m['paid']).toDouble();
      }
      final keys = byDate.keys.toList()..sort();
      final last = keys.length > 7 ? keys.sublist(keys.length - 7) : keys;
      trendPoints = last.map((k) => byDate[k]!).toList();
      trendLabels = last;
    } catch (_) {}
    loading = false;
    notifyListeners();
  }

  /// Load the reports for the active type within [startDate]..[endDate].
  /// Bill-wise data always drives the range summary (gross / invoices) and the
  /// per-day trend, so those stay consistent across both table views.
  Future<void> loadReports({String? type}) async {
    if (type != null) reportType = type;
    reportLoading = true;
    reportError = null;
    notifyListeners();

    final start = Dates.iso(startDate);
    final end = Dates.iso(endDate);
    try {
      final bill = await service.report(type: 'billwise', startDate: start, endDate: end);
      final billRows = (bill['rows'] as List?) ?? const [];
      _applyRangeSummary(billRows);

      if (reportType == 'billwise') {
        _applyBillwiseTable(billRows);
      } else {
        final data = await service.report(type: reportType, startDate: start, endDate: end);
        _applyEmployeeTable((data['rows'] as List?) ?? const []);
      }
      reportError = null;
    } on ApiException catch (e) {
      reportError = e.message;
    } catch (e) {
      reportError = 'Could not load the report.';
    }
    reportLoading = false;
    notifyListeners();
  }

  /// Range KPIs (gross, invoices) + per-day trend, derived from bill-wise rows.
  void _applyRangeSummary(List billRows) {
    final byDate = <String, double>{};
    var gross = 0.0;
    for (final e in billRows) {
      final m = Map<String, dynamic>.from(e);
      final paid = asNum(m['paid']).toDouble();
      gross += paid;
      final d = asStr(m['date']);
      byDate[d] = (byDate[d] ?? 0) + paid;
    }
    rangeGross = gross;
    rangeInvoices = billRows.length;
    final keys = byDate.keys.toList()..sort();
    final last = keys.length > 14 ? keys.sublist(keys.length - 14) : keys;
    reportTrendPoints = last.map((k) => byDate[k]!).toList();
    reportTrendLabels = last;
  }

  void _applyBillwiseTable(List rows) {
    reportTotal = 0;
    reportRows = rows.map((e) {
      final m = Map<String, dynamic>.from(e);
      final paid = asNum(m['paid']).toDouble();
      reportTotal += paid;
      return ReportRow(
        title: asStr(m['invoice_no']),
        subtitle: '${asStr(m['customer'])} · ${Dates.human(asStr(m['date']))}',
        value: Money.of(paid),
        amount: paid,
      );
    }).toList();
  }

  void _applyEmployeeTable(List rows) {
    reportTotal = 0;
    reportRows = rows.map((e) {
      final m = Map<String, dynamic>.from(e);
      final rev = asNum(m['revenue']).toDouble();
      reportTotal += rev;
      return ReportRow(
        title: asStr(m['employee_name']),
        subtitle: '${asNum(m['bills_count']).toInt()} bills · ${asNum(m['items_count']).toInt()} items',
        value: Money.of(rev),
        amount: rev,
      );
    }).toList();
  }
}
