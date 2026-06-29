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

  // Reports — server-paginated breakdown table with infinite scroll.
  static const int _reportPageSize = 20;
  bool reportLoading = false; // first-page (full-screen) load
  bool reportLoadingMore = false; // appending the next page
  String? reportError;
  String reportType = 'itemwise'; // itemwise | employeewise
  String itemMetric = 'amount'; // amount | qty — how the item report is ranked
  String? itemProductType; // null (all) | product | service | asset — item report filter
  List<ReportRow> reportRows = [];
  double reportTotal = 0; // full-set grand total (from the summary, not just loaded rows)
  int reportRowCount = 0; // total rows across all pages (for the count badge)
  int _reportPage = 1;
  int _reportLastPage = 1;
  int _reportReq = 0; // guards against out-of-order / superseded responses

  bool get reportHasMore => _reportPage < _reportLastPage;

  // Reports date range (drives the overview KPIs, trend chart and table).
  late DateTime startDate;
  late DateTime endDate;
  String rangePreset = '7d'; // today | 7d | 30d | month | custom
  List<double> reportTrendPoints = [];
  List<String> reportTrendLabels = [];

  // Sales Overview (type=overview) — the premium dashboard at the top of Reports:
  // sales performance + payment overview, range-aware just like the table.
  bool overviewLoading = false;
  String? overviewError;
  SalesOverview? overview;
  int _overviewReq = 0; // guards against out-of-order / superseded responses

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
    loadOverview();
  }

  /// Apply a hand-picked custom range and reload all reports.
  void setCustomRange(DateTime start, DateTime end) {
    startDate = DateTime(start.year, start.month, start.day);
    endDate = DateTime(end.year, end.month, end.day);
    rangePreset = 'custom';
    loadReports();
    loadOverview();
  }

  /// Load the Sales Overview snapshot for the active [startDate]..[endDate].
  /// Independent of the breakdown table so a slow/failed overview never blocks
  /// the rest of the screen; a request guard drops superseded responses.
  Future<void> loadOverview() async {
    final reqId = ++_overviewReq;
    overviewLoading = true;
    overviewError = null;
    notifyListeners();
    try {
      final data = await service.report(
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
        notifyListeners();
      }
    }
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
      final bill = await service.report(type: 'billwise', perPage: 100);
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

  /// (Re)load the active report from page 1 for [startDate]..[endDate].
  /// Bill-wise data always drives the range summary (gross / invoices) and the
  /// per-day trend; the chosen breakdown drives the paginated table.
  Future<void> loadReports({String? type, String? metric}) async {
    if (type != null) reportType = type;
    if (metric != null) itemMetric = metric;
    final req = ++_reportReq; // any in-flight loadMore for an older view is voided
    reportLoading = true;
    reportLoadingMore = false; // clear a stuck flag if a loadMore was superseded
    reportError = null;
    notifyListeners();

    final start = Dates.iso(startDate);
    final end = Dates.iso(endDate);
    try {
      // Bill-wise (capped) always drives the range summary + per-day trend,
      // independent of which breakdown table is on screen.
      final bill = await service.report(type: 'billwise', startDate: start, endDate: end, perPage: 100);
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
      notifyListeners();
    }
  }

  /// Fetch the next page and append it (infinite scroll). No-op while a load is
  /// already running or the last page has been reached.
  Future<void> loadMoreReport() async {
    if (reportLoadingMore || reportLoading || !reportHasMore) return;
    final req = _reportReq; // tie to the current view; bail if it changes
    reportLoadingMore = true;
    notifyListeners();
    try {
      final data = await _fetchReportPage(_reportPage + 1);
      if (req != _reportReq) return; // view changed mid-flight — drop this page
      _applyReportPage(data, append: true);
    } catch (_) {
      // Keep what we have; the next scroll can retry the same page.
    }
    if (req == _reportReq) {
      reportLoadingMore = false;
      notifyListeners();
    }
  }

  Future<Map<String, dynamic>> _fetchReportPage(int page) => service.report(
        type: reportType,
        startDate: Dates.iso(startDate),
        endDate: Dates.iso(endDate),
        page: page,
        perPage: _reportPageSize,
        sort: reportType == 'itemwise' ? (itemMetric == 'qty' ? 'quantity' : 'amount') : null,
        productType: reportType == 'itemwise' ? itemProductType : null,
      );

  /// Merge a fetched page into the table. The grand total + row count come from
  /// the full-set `summary`, so share-of-total bars and the footer stay accurate
  /// no matter how many pages have been loaded.
  void _applyReportPage(Map<String, dynamic> data, {required bool append}) {
    final rows = (data['rows'] as List?) ?? const [];
    final pag = (data['pagination'] as Map?) ?? const {};
    final summary = (data['summary'] as Map?) ?? const {};
    _reportPage = asNum(pag['current_page'] ?? 1).toInt();
    _reportLastPage = asNum(pag['last_page'] ?? 1).toInt();
    reportRowCount = asNum(pag['total'] ?? rows.length).toInt();

    reportTotal = reportType == 'itemwise'
        ? asNum(itemMetric == 'qty' ? summary['total_quantity'] : summary['total_amount']).toDouble()
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
      subtitle: byQty ? '${Money.of(total)} · $billLabel' : '${_qty(qty)} sold · $billLabel',
      value: byQty ? '${_qty(qty)} sold' : Money.of(total),
      amount: byQty ? qty : total,
    );
  }

  ReportRow _employeeRow(dynamic e) {
    final m = Map<String, dynamic>.from(e);
    final rev = asNum(m['revenue']).toDouble();
    return ReportRow(
      title: asStr(m['employee_name']),
      subtitle: '${asNum(m['bills_count']).toInt()} bills · ${asNum(m['items_count']).toInt()} items',
      value: Money.of(rev),
      amount: rev,
    );
  }

  /// Switch the item report between amount- and quantity-ranked views. The
  /// server re-ranks, so this reloads from page 1.
  void setItemMetric(String metric) {
    if (itemMetric == metric) return;
    loadReports(metric: metric);
  }

  /// Filter the item report by product type (null = all). The server re-queries,
  /// so this reloads from page 1.
  void setItemProductType(String? productType) {
    if (itemProductType == productType) return;
    itemProductType = productType;
    loadReports();
  }

  /// Grand-total label for the active table — currency for amounts, a plain
  /// count for the item quantity view.
  String get reportTotalText =>
      (reportType == 'itemwise' && itemMetric == 'qty') ? '${_qty(reportTotal)} sold' : Money.of(reportTotal);

  /// Trim trailing zeros so whole quantities read "12" and fractional ones "1.5".
  String _qty(double q) => q == q.roundToDouble() ? q.toInt().toString() : q.toStringAsFixed(2);

  /// Per-day "gross sales by day" trend from the bill-wise (capped) rows. The
  /// range KPIs now come from the overview snapshot, so only the trend remains.
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
