import 'package:invo/shared/domain/constants/global_variables.dart';
import 'package:invo/shared/domain/helpers/formatters.dart';
import 'package:invo/shared/domain/models/index.dart';
import 'package:invo/shared/logic/base/holder_cubit.dart';
import 'package:invo/shared/utils/router/http_utils/common_exception.dart';

import '../../domain/repository/admin_repository.dart';

class ReportRow {
  ReportRow(
      {required this.title,
      required this.subtitle,
      required this.value,
      this.amount = 0});
  final String title;
  final String subtitle;
  final String value;
  final double amount;
}

/// Backs the Dashboard and the Reports suite (bill-wise / employee-wise).
class AdminCubit extends HolderCubit {
  AdminCubit() {
    final now = DateTime.now();
    final today = DateTime(now.year, now.month, now.day);
    startDate = today.subtract(const Duration(days: 6));
    endDate = today;
  }

  AdminRepository get _repo => serviceLocator<AdminRepository>();

  bool loading = false;
  String? error;
  DashboardData? dashboard;
  List<ReportRow> topStylists = [];
  List<double> trendPoints = [];
  List<String> trendLabels = [];

  static const int _reportPageSize = 20;
  bool reportLoading = false;
  bool reportLoadingMore = false;
  String? reportError;
  String reportType = 'itemwise';
  String itemMetric = 'amount';
  String? itemProductType;
  List<ReportRow> reportRows = [];
  double reportTotal = 0;
  int reportRowCount = 0;
  int _reportPage = 1;
  int _reportLastPage = 1;
  int _reportReq = 0;

  bool get reportHasMore => _reportPage < _reportLastPage;

  late DateTime startDate;
  late DateTime endDate;
  String rangePreset = '7d';
  List<double> reportTrendPoints = [];
  List<String> reportTrendLabels = [];

  bool overviewLoading = false;
  String? overviewError;
  SalesOverview? overview;
  int _overviewReq = 0;

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
    loadOverview();
  }

  void setCustomRange(DateTime start, DateTime end) {
    startDate = DateTime(start.year, start.month, start.day);
    endDate = DateTime(end.year, end.month, end.day);
    rangePreset = 'custom';
    loadReports();
    loadOverview();
  }

  Future<void> loadOverview() async {
    final reqId = ++_overviewReq;
    overviewLoading = true;
    overviewError = null;
    refresh();
    try {
      final data = await _repo.report(
        type: 'overview',
        startDate: Dates.iso(startDate),
        endDate: Dates.iso(endDate),
      );
      if (reqId != _overviewReq) return;
      overview = SalesOverview.fromJson(data);
    } on ApiException catch (e) {
      if (reqId == _overviewReq) overviewError = e.message;
    } catch (e) {
      if (reqId == _overviewReq) overviewError = 'Could not load the overview.';
    } finally {
      if (reqId == _overviewReq) {
        overviewLoading = false;
        refresh();
      }
    }
  }

  Future<void> loadDashboard() async {
    loading = true;
    error = null;
    refresh();
    try {
      dashboard = await _repo.dashboard();
    } on ApiException catch (e) {
      error = e.message;
    } catch (e) {
      error = 'Could not load the dashboard.';
    }
    try {
      final emp = await _repo.report(type: 'employeewise');
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
      final bill = await _repo.report(type: 'billwise', perPage: 100);
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
    refresh();
  }

  Future<void> loadReports({String? type, String? metric}) async {
    if (type != null) reportType = type;
    if (metric != null) itemMetric = metric;
    final req = ++_reportReq;
    reportLoading = true;
    reportLoadingMore = false;
    reportError = null;
    refresh();

    final start = Dates.iso(startDate);
    final end = Dates.iso(endDate);
    try {
      final bill = await _repo.report(
          type: 'billwise', startDate: start, endDate: end, perPage: 100);
      if (req != _reportReq) return;
      _applyRangeSummary(bill);

      final data = await _fetchReportPage(1);
      if (req != _reportReq) return;
      _applyReportPage(data, append: false);
      reportError = null;
    } on ApiException catch (e) {
      if (req == _reportReq) reportError = e.message;
    } catch (e) {
      if (req == _reportReq) reportError = 'Could not load the report.';
    }
    if (req == _reportReq) {
      reportLoading = false;
      refresh();
    }
  }

  Future<void> loadMoreReport() async {
    if (reportLoadingMore || reportLoading || !reportHasMore) return;
    final req = _reportReq;
    reportLoadingMore = true;
    refresh();
    try {
      final data = await _fetchReportPage(_reportPage + 1);
      if (req != _reportReq) return;
      _applyReportPage(data, append: true);
    } catch (_) {
      // Keep what we have; the next scroll can retry the same page.
    }
    if (req == _reportReq) {
      reportLoadingMore = false;
      refresh();
    }
  }

  Future<Map<String, dynamic>> _fetchReportPage(int page) => _repo.report(
        type: reportType,
        startDate: Dates.iso(startDate),
        endDate: Dates.iso(endDate),
        page: page,
        perPage: _reportPageSize,
        sort: reportType == 'itemwise'
            ? (itemMetric == 'qty' ? 'quantity' : 'amount')
            : null,
        productType: reportType == 'itemwise' ? itemProductType : null,
      );

  void _applyReportPage(Map<String, dynamic> data, {required bool append}) {
    final rows = (data['rows'] as List?) ?? const [];
    final pag = (data['pagination'] as Map?) ?? const {};
    final summary = (data['summary'] as Map?) ?? const {};
    _reportPage = asNum(pag['current_page'] ?? 1).toInt();
    _reportLastPage = asNum(pag['last_page'] ?? 1).toInt();
    reportRowCount = asNum(pag['total'] ?? rows.length).toInt();

    reportTotal = reportType == 'itemwise'
        ? asNum(itemMetric == 'qty'
                ? summary['total_quantity']
                : summary['total_amount'])
            .toDouble()
        : asNum(summary['total_revenue']).toDouble();

    final mapped = reportType == 'itemwise'
        ? rows.map(_itemRow).toList()
        : rows.map(_employeeRow).toList();
    reportRows = append ? [...reportRows, ...mapped] : mapped;
  }

  ReportRow _itemRow(dynamic e) {
    final m = Map<String, dynamic>.from(e);
    final total = asNum(m['total']).toDouble();
    final qty = asNum(m['quantity']).toDouble();
    final bills = asNum(m['bills_count']).toInt();
    final byQty = itemMetric == 'qty';
    final billLabel = '$bills bill${bills == 1 ? '' : 's'}';
    return ReportRow(
      title: asStr(m['item_name']),
      subtitle: byQty
          ? '${Money.of(total)} · $billLabel'
          : '${_qty(qty)} sold · $billLabel',
      value: byQty ? '${_qty(qty)} sold' : Money.of(total),
      amount: byQty ? qty : total,
    );
  }

  ReportRow _employeeRow(dynamic e) {
    final m = Map<String, dynamic>.from(e);
    final rev = asNum(m['revenue']).toDouble();
    return ReportRow(
      title: asStr(m['employee_name']),
      subtitle:
          '${asNum(m['bills_count']).toInt()} bills · ${asNum(m['items_count']).toInt()} items',
      value: Money.of(rev),
      amount: rev,
    );
  }

  void setItemMetric(String metric) {
    if (itemMetric == metric) return;
    loadReports(metric: metric);
  }

  void setItemProductType(String? productType) {
    if (itemProductType == productType) return;
    itemProductType = productType;
    loadReports();
  }

  String get reportTotalText =>
      (reportType == 'itemwise' && itemMetric == 'qty')
          ? '${_qty(reportTotal)} sold'
          : Money.of(reportTotal);

  String _qty(double q) =>
      q == q.roundToDouble() ? q.toInt().toString() : q.toStringAsFixed(2);

  void _applyRangeSummary(Map<String, dynamic> bill) {
    final rows = (bill['rows'] as List?) ?? const [];
    final byDate = <String, double>{};
    for (final e in rows) {
      final m = Map<String, dynamic>.from(e);
      final d = asStr(m['date']);
      byDate[d] = (byDate[d] ?? 0) + asNum(m['paid']).toDouble();
    }
    final keys = byDate.keys.toList()..sort();
    final last = keys.length > 14 ? keys.sublist(keys.length - 14) : keys;
    reportTrendPoints = last.map((k) => byDate[k]!).toList();
    reportTrendLabels = last;
  }
}
