import 'dart:async';

import 'package:invo/shared/domain/constants/global_variables.dart';
import 'package:invo/shared/domain/helpers/formatters.dart';
import 'package:invo/shared/domain/models/index.dart';
import 'package:equatable/equatable.dart';
import 'package:flutter_bloc/flutter_bloc.dart';
import 'package:invo/shared/utils/router/http_utils/common_exception.dart';

import '../../domain/repository/admin_repository.dart';

part 'admin_state.dart';

class ReportRow extends Equatable {
  const ReportRow(
      {required this.title,
      required this.subtitle,
      required this.value,
      this.amount = 0});
  final String title;
  final String subtitle;
  final String value;
  final double amount;

  @override
  List<Object?> get props => [title, subtitle, value, amount];
}

/// Backs the Dashboard and the Reports suite (bill-wise / employee-wise).
class AdminCubit extends Cubit<AdminState> {
  AdminCubit() : super(_initialState());

  static AdminState _initialState() {
    final now = DateTime.now();
    final today = DateTime(now.year, now.month, now.day);
    return AdminState(
      startDate: today.subtract(const Duration(days: 6)),
      endDate: today,
    );
  }

  AdminRepository get _repo => serviceLocator<AdminRepository>();

  static const int _reportPageSize = 20;
  int _reportReq = 0;
  int _overviewReq = 0;

  // Read facade over `state`.
  bool get loading => state.loading;
  String? get error => state.errorMessage;
  DashboardData? get dashboard => state.dashboard;
  List<ReportRow> get topStylists => state.topStylists;
  List<double> get trendPoints => state.trendPoints;
  List<String> get trendLabels => state.trendLabels;
  bool get reportLoading => state.reportLoading;
  bool get reportLoadingMore => state.reportLoadingMore;
  String? get reportError => state.reportError;
  String get reportType => state.reportType;
  String get itemMetric => state.itemMetric;
  String? get itemProductType => state.itemProductType;
  List<ReportRow> get reportRows => state.reportRows;
  double get reportTotal => state.reportTotal;
  int get reportRowCount => state.reportRowCount;
  bool get reportHasMore => state.reportHasMore;
  DateTime get startDate => state.startDate;
  DateTime get endDate => state.endDate;
  String get rangePreset => state.rangePreset;
  List<double> get reportTrendPoints => state.reportTrendPoints;
  List<String> get reportTrendLabels => state.reportTrendLabels;
  bool get overviewLoading => state.overviewLoading;
  String? get overviewError => state.overviewError;
  SalesOverview? get overview => state.overview;

  void setPreset(String id) {
    final now = DateTime.now();
    final today = DateTime(now.year, now.month, now.day);
    var from = state.startDate, to = state.endDate;
    switch (id) {
      case 'today':
        from = today;
        to = today;
      case '7d':
        from = today.subtract(const Duration(days: 6));
        to = today;
      case '30d':
        from = today.subtract(const Duration(days: 29));
        to = today;
      case 'month':
        from = DateTime(now.year, now.month, 1);
        to = today;
    }
    emit(state.copyWith(startDate: from, endDate: to, rangePreset: id));
    unawaited(loadReports());
    unawaited(loadOverview());
  }

  void setCustomRange(DateTime start, DateTime end) {
    emit(state.copyWith(
      startDate: DateTime(start.year, start.month, start.day),
      endDate: DateTime(end.year, end.month, end.day),
      rangePreset: 'custom',
    ));
    unawaited(loadReports());
    unawaited(loadOverview());
  }

  Future<void> loadOverview() async {
    final reqId = ++_overviewReq;
    emit(state.copyWith(overviewLoading: true, clearOverviewError: true));
    try {
      final data = await _repo.report(
        type: 'overview',
        startDate: Dates.iso(state.startDate),
        endDate: Dates.iso(state.endDate),
      );
      if (reqId != _overviewReq) return;
      emit(state.copyWith(overview: SalesOverview.fromJson(data)));
    } on ApiException catch (e) {
      if (reqId == _overviewReq) emit(state.copyWith(overviewError: e.message));
    } catch (_) {
      if (reqId == _overviewReq) {
        emit(state.copyWith(overviewError: 'Could not load the overview.'));
      }
    } finally {
      if (reqId == _overviewReq && !isClosed) {
        emit(state.copyWith(overviewLoading: false));
      }
    }
  }

  /// The three calls behind the dashboard are independent, so they are issued
  /// together: awaited one after another the screen cost the *sum* of three
  /// round-trips, which is most of the wait after signing in.
  ///
  /// Only [_loadCards] gates `loading`. The other two feed decoration — the top
  /// stylists list and the sparkline — and used to hold the KPI cards back for
  /// two extra round-trips they don't depend on.
  Future<void> loadDashboard() async {
    emit(state.copyWith(loading: true, clearError: true));

    final cards = _loadCards();
    final decoration = Future.wait([_loadTopStylists(), _loadTrend()]);

    await cards;
    if (!isClosed) emit(state.copyWith(loading: false));

    // Still awaited so pull-to-refresh doesn't end while these are in flight.
    await decoration;
  }

  Future<void> _loadCards() async {
    try {
      final data = await _repo.dashboard();
      if (!isClosed) emit(state.copyWith(dashboard: data));
    } on ApiException catch (e) {
      if (!isClosed) emit(state.copyWith(errorMessage: e.message));
    } catch (_) {
      if (!isClosed) emit(state.copyWith(errorMessage: 'Could not load the dashboard.'));
    }
  }

  Future<void> _loadTopStylists() async {
    try {
      final emp = await _repo.report(type: 'employeewise');
      final rows = (emp['rows'] as List?) ?? const [];
      final stylists = rows.map((e) {
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
      if (!isClosed) emit(state.copyWith(topStylists: stylists.take(4).toList()));
    } catch (_) {/* keep whatever we had */}
  }

  Future<void> _loadTrend() async {
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
      if (!isClosed) {
        emit(state.copyWith(
          trendPoints: last.map((k) => byDate[k]!).toList(),
          trendLabels: last,
        ));
      }
    } on ApiException catch (_) {
      // Dashboard trend is decoration — the KPI cards above it still render.
    } catch (_) {
      // Unexpected trend payload shape; leave the sparkline empty rather than
      // failing the whole dashboard load.
    }
  }

  Future<void> loadReports({String? type, String? metric}) async {
    final req = ++_reportReq;
    emit(state.copyWith(
      reportType: type,
      itemMetric: metric,
      reportLoading: true,
      reportLoadingMore: false,
      clearReportError: true,
    ));

    final start = Dates.iso(state.startDate);
    final end = Dates.iso(state.endDate);
    try {
      final bill = await _repo.report(
          type: 'billwise', startDate: start, endDate: end, perPage: 100);
      if (req != _reportReq) return;
      _applyRangeSummary(bill);

      final data = await _fetchReportPage(1);
      if (req != _reportReq) return;
      _applyReportPage(data, append: false);
    } on ApiException catch (e) {
      if (req == _reportReq) emit(state.copyWith(reportError: e.message));
    } catch (_) {
      if (req == _reportReq) {
        emit(state.copyWith(reportError: 'Could not load the report.'));
      }
    }
    if (req == _reportReq && !isClosed) {
      emit(state.copyWith(reportLoading: false));
    }
  }

  Future<void> loadMoreReport() async {
    if (state.reportLoadingMore || state.reportLoading || !state.reportHasMore) {
      return;
    }
    final req = _reportReq;
    emit(state.copyWith(reportLoadingMore: true));
    try {
      final data = await _fetchReportPage(state.reportPage + 1);
      if (req != _reportReq) return;
      _applyReportPage(data, append: true);
    } catch (_) {
      // Keep what we have; the next scroll can retry the same page.
    } finally {
      // Always clear, even for a discarded page, or the guard on entry blocks
      // every later page for the life of the screen.
      if (!isClosed) emit(state.copyWith(reportLoadingMore: false));
    }
  }

  Future<Map<String, dynamic>> _fetchReportPage(int page) => _repo.report(
        type: state.reportType,
        startDate: Dates.iso(state.startDate),
        endDate: Dates.iso(state.endDate),
        page: page,
        perPage: _reportPageSize,
        sort: state.reportType == 'itemwise'
            ? (state.itemMetric == 'qty' ? 'quantity' : 'amount')
            : null,
        productType: state.reportType == 'itemwise' ? state.itemProductType : null,
      );

  void _applyReportPage(Map<String, dynamic> data, {required bool append}) {
    final rows = (data['rows'] as List?) ?? const [];
    final pag = (data['pagination'] as Map?) ?? const {};
    final summary = (data['summary'] as Map?) ?? const {};
    final total = state.reportType == 'itemwise'
        ? asNum(state.itemMetric == 'qty'
                ? summary['total_quantity']
                : summary['total_amount'])
            .toDouble()
        : asNum(summary['total_revenue']).toDouble();

    final mapped = state.reportType == 'itemwise'
        ? rows.map(_itemRow).toList()
        : rows.map(_employeeRow).toList();

    emit(state.copyWith(
      reportPage: asNum(pag['current_page'] ?? 1).toInt(),
      reportLastPage: asNum(pag['last_page'] ?? 1).toInt(),
      reportRowCount: asNum(pag['total'] ?? rows.length).toInt(),
      reportTotal: total,
      reportRows: append ? [...state.reportRows, ...mapped] : mapped,
    ));
  }

  ReportRow _itemRow(dynamic e) {
    final m = Map<String, dynamic>.from(e);
    final total = asNum(m['total']).toDouble();
    final qty = asNum(m['quantity']).toDouble();
    final bills = asNum(m['bills_count']).toInt();
    final byQty = state.itemMetric == 'qty';
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
    if (state.itemMetric == metric) return;
    unawaited(loadReports(metric: metric));
  }

  void setItemProductType(String? productType) {
    if (state.itemProductType == productType) return;
    emit(state.copyWith(
        itemProductType: productType, clearItemProductType: productType == null));
    unawaited(loadReports());
  }

  String get reportTotalText =>
      (state.reportType == 'itemwise' && state.itemMetric == 'qty')
          ? '${_qty(state.reportTotal)} sold'
          : Money.of(state.reportTotal);

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
    emit(state.copyWith(
      reportTrendPoints: last.map((k) => byDate[k]!).toList(),
      reportTrendLabels: last,
    ));
  }
}
