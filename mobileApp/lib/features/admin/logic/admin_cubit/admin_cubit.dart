import 'dart:async';
import 'dart:typed_data';

import 'package:invo/features/auth/logic/auth_cubit/auth_cubit.dart';
import 'package:invo/shared/domain/constants/global_variables.dart';
import 'package:invo/shared/domain/helpers/formatters.dart';
import 'package:invo/shared/domain/models/index.dart';
import 'package:equatable/equatable.dart';
import 'package:flutter_bloc/flutter_bloc.dart';
import 'package:invo/shared/utils/router/http_utils/common_exception.dart';
import 'package:invo/shared/utils/router/http_utils/reachability.dart';

import '../../domain/repository/admin_repository.dart';

part 'admin_state.dart';

class ReportRow extends Equatable {
  const ReportRow(
      {this.id = '',
      required this.title,
      required this.subtitle,
      required this.value,
      this.amount = 0,
      this.quantity = 0,
      this.bills = 0});

  /// The staff member's user id on a staff row, so a screen can pick out the
  /// signed-in person; '' on item and category rows.
  final String id;
  final String title;
  final String subtitle;
  final String value;
  final double amount;

  /// Units sold on an item or category row, lines sold on a staff row — the
  /// ledger table's Qty column.
  final double quantity;

  /// Distinct bills the row appears on — the ledger table's Bills column.
  final int bills;

  @override
  List<Object?> get props => [id, title, subtitle, value, amount, quantity, bills];
}

/// The loaded breakdown for one report type, held so the By Item / By Category /
/// By Staff toggle can restore it without another round-trip. Carries the
/// pagination cursor too, so a list you had already scrolled comes back as you
/// left it.
class _ReportCache {
  const _ReportCache({
    required this.rows,
    required this.total,
    required this.rowCount,
    required this.page,
    required this.lastPage,
  });
  final List<ReportRow> rows;
  final double total;
  final int rowCount;
  final int page;
  final int lastPage;
}

/// One report type walked page by page for the PDF export.
typedef _ExportRows = ({
  List<Map<String, dynamic>> rows,
  Map<String, dynamic> summary,
  int total,
  bool truncated,
});

/// Backs the Dashboard and the Reports suite (bill-wise / employee-wise).
class AdminCubit extends Cubit<AdminState> {
  AdminCubit() : super(_initialState()) {
    // Provided once for the life of the app (InvoApp), so the cubit outlives
    // the session and a sign-out has to empty it by hand — or the next
    // cashier's dashboard would open on the last one's figures while its own
    // request was still in flight. A lock keeps everything warm on purpose.
    _authSub = serviceLocator<AuthCubit>().stream.listen(_onAuth);
  }

  StreamSubscription<AuthState>? _authSub;

  void _onAuth(AuthState s) {
    if (s.status == AuthStatus.signedOut) reset();
  }

  /// Back to the blank slate: every cached figure dropped and every request
  /// still in flight disowned, so nothing from the old session lands on the
  /// new one.
  void reset() {
    ++_dashboardReq;
    ++_reportReq;
    ++_overviewReq;
    _reportCache.clear();
    _trendKey = null;
    emit(_initialState());
  }

  @override
  Future<void> close() {
    _authSub?.cancel();
    return super.close();
  }

  static AdminState _initialState() {
    final now = DateTime.now();
    final today = DateTime(now.year, now.month, now.day);
    // Reports open on today; the range then stays wherever it is left for the
    // rest of the session, and a sign-out brings it back here.
    return AdminState(
      startDate: today,
      endDate: today,
      rangePreset: 'today',
    );
  }

  AdminRepository get _repo => serviceLocator<AdminRepository>();

  static const int _reportPageSize = 20;
  int _reportReq = 0;
  int _overviewReq = 0;
  int _dashboardReq = 0;

  /// Whether the dashboard request stamped [req] is still the one to honour.
  /// Every visit to the dashboard reloads it, and so does a branch switch, so
  /// a slow answer for the previous branch — or the previous cashier — can
  /// overlap a newer one and must not land on top of it.
  bool _live(int req) => req == _dashboardReq && !isClosed;

  /// Last loaded breakdown per report type, for the filters in force when it
  /// was fetched. Only the By Item / By Category / By Staff toggle reads it;
  /// each input invalidates exactly what it can have moved on the way past, so
  /// an entry can never outlive the filters behind it:
  ///
  ///  * date range, branch — every entry (every figure is range-scoped)
  ///  * Rank By, Type      — the `itemwise` and `categorywise` entries; neither
  ///                         is sent with `employeewise`, so By Staff stands.
  final Map<String, _ReportCache> _reportCache = {};

  /// Date range the per-day trend was fetched for. The trend depends on the
  /// range alone, so a report-type or Rank By change reuses it instead of
  /// pulling 100 bill-wise rows again for a chart that cannot have moved.
  String? _trendKey;

  // Read facade over `state`.
  bool get loading => state.loading;
  String? get error => state.errorMessage;
  DashboardData? get dashboard => state.dashboard;
  List<ReportRow> get topStylists => state.topStylists;
  String get topStylistsDate => state.topStylistsDate;
  List<double> get trendPoints => state.trendPoints;
  List<String> get trendLabels => state.trendLabels;
  bool get reportLoading => state.reportLoading;
  bool get reportLoadingMore => state.reportLoadingMore;
  String? get reportError => state.reportError;
  String get reportType => state.reportType;
  String get sortKey => state.sortKey;
  bool get sortAscending => state.sortAscending;

  /// The measure the amount column and the grand total are in. Sorting by
  /// quantity switches the report to units; the other three sorts leave it in
  /// money. Kept for the export screen, which labels the PDF with it.
  String get itemMetric => state.sortKey == 'quantity' ? 'qty' : 'amount';
  String? get itemProductType => state.itemProductType;
  List<ReportRow> get reportRows => state.reportRows;
  double get reportTotal => state.reportTotal;
  double get reportQuantityTotal => state.reportQuantityTotal;
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
      case 'yesterday':
        from = today.subtract(const Duration(days: 1));
        to = from;
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
    // The range is the one input both breakdowns are built on, so it is the
    // one that empties the cache for both. Re-tapping the active preset is
    // deliberately not short-circuited — it doubles as the manual refresh.
    _reportCache.clear();
    unawaited(loadReports());
    unawaited(loadOverview());
  }

  void setCustomRange(DateTime start, DateTime end) {
    emit(state.copyWith(
      startDate: DateTime(start.year, start.month, start.day),
      endDate: DateTime(end.year, end.month, end.day),
      rangePreset: 'custom',
    ));
    _reportCache.clear();
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
    } catch (e) {
      if (reqId == _overviewReq) {
        emit(state.copyWith(
            overviewError: networkErrorMessage(e, 'Could not load the overview.')));
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
    final req = ++_dashboardReq;
    emit(state.copyWith(loading: true, clearError: true));

    // The leaderboard needs the business date up front to stay parallel with
    // the cards, so it starts from the session date the signed-in user carries.
    // That copy can be stale — on a shared till another device can open or
    // close the day — so the server's answer is reconciled below.
    final assumed = _sessionDate;
    final cards = _loadCards(req);
    final decoration = Future.wait(
        [_loadTopStylists(assumed, req), _loadTrend(req), _syncDayStatus(req)]);

    await cards;
    if (_live(req)) emit(state.copyWith(loading: false));

    // Still awaited so pull-to-refresh doesn't end while these are in flight.
    await decoration;

    final actual = state.dashboard?.date ?? '';
    if (actual.isNotEmpty && actual != assumed && _live(req)) {
      await _loadTopStylists(actual, req);
    }
  }

  /// Re-reads the branch's day-session state from the server and writes it back
  /// into the cached user, so a refresh shows the day as the *database* has it.
  /// The user's copy is only as fresh as the last sign-in or toggle on this
  /// device: on a shared till another device can open or close the day, and an
  /// app left running overnight still carries yesterday's session date. The
  /// server answers for the branch the app is operating as (`branch_id` rides
  /// on every request), so the pill follows a branch switch like the cards do.
  Future<void> _syncDayStatus(int req) async {
    try {
      final live = await _repo.dayStatus();
      if (!_live(req)) return;
      await serviceLocator<AuthCubit>().applyDayStatus(live);
    } catch (_) {
      // Offline or the endpoint said no — keep the cached day state rather than
      // blanking the pill; the KPI cards above it still render.
    }
  }

  /// A rank-and-file employee sees only their own figures — the server hard-
  /// scopes every dashboard and report query to them (User::seesOnlyOwnRecords)
  /// — so the leaderboard would be a board of one, restating the revenue the
  /// hero already shows. Skipped rather than drawn as a rank-1 medal.
  bool get _selfScoped =>
      serviceLocator<AuthCubit>().user?.isNonAdminEmployee ?? false;

  /// The business day the dashboard reports on: the branch's open day-session
  /// date, or the calendar date when no session is open — the same anchor the
  /// server uses for the KPI cards (App\Actions\V1\Dashboard\GetAction).
  String get _sessionDate {
    final user = serviceLocator<AuthCubit>().user;
    final date = (user?.daySessionDate ?? '').split(' ').first;
    return (user?.dayOpen ?? false) && date.isNotEmpty ? date : Dates.today();
  }

  Future<void> _loadCards(int req) async {
    try {
      final data = await _repo.dashboard();
      if (_live(req)) emit(state.copyWith(dashboard: data));
    } on ApiException catch (e) {
      if (_live(req)) emit(state.copyWith(errorMessage: e.message));
    } catch (e) {
      if (_live(req)) {
        emit(state.copyWith(
            errorMessage: networkErrorMessage(e, 'Could not load the dashboard.')));
      }
    }
  }

  /// Ranks the stylists for a single business day — [date], the branch's open
  /// day-session date. Sending no range would rank them on every sale ever
  /// recorded, which puts the same names on the board whatever happened today.
  Future<void> _loadTopStylists(String date, int req) async {
    if (_selfScoped) return;
    try {
      final emp = await _repo.report(type: 'employeewise', startDate: date, endDate: date);
      final rows = (emp['rows'] as List?) ?? const [];
      final stylists = rows.map((e) {
        final m = Map<String, dynamic>.from(e);
        final rev = asNum(m['revenue']).toDouble();
        return ReportRow(
          id: asStr(m['employee_id']),
          title: asStr(m['employee_name']),
          subtitle: '${asNum(m['bills_count']).toInt()} bills',
          value: Money.of(rev),
          amount: rev,
        );
      }).toList()
        ..sort((a, b) => b.amount.compareTo(a.amount));
      if (_live(req)) {
        emit(state.copyWith(
            topStylists: stylists.take(4).toList(), topStylistsDate: date));
      }
    } catch (_) {/* keep whatever we had */}
  }

  Future<void> _loadTrend(int req) async {
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
      if (_live(req)) {
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

  /// Switch the breakdown between By Item, By Category and By Staff. Each side
  /// is held in [_reportCache] for as long as the filters behind it hold, so the
  /// toggle is instant — the API is hit again only for a side that hasn't been
  /// loaded under the current Rank By / Type / date / branch selection.
  void setReportType(String type) {
    // Re-tapping the active segment is a no-op, unless its load failed — then
    // it's the only retry the user has.
    if (state.reportType == type && state.reportError == null) return;
    final cached = _reportCache[type];
    if (cached == null) {
      unawaited(loadReports(type: type));
      return;
    }
    // Discard anything still in flight for the type we're leaving, or its page
    // would land on top of the restored rows.
    ++_reportReq;
    emit(state.copyWith(
      reportType: type,
      reportLoading: false,
      reportLoadingMore: false,
      clearReportError: true,
      reportRows: cached.rows,
      reportTotal: cached.total,
      reportRowCount: cached.rowCount,
      reportPage: cached.page,
      reportLastPage: cached.lastPage,
    ));
  }

  /// [force] throws away everything cached — for a branch switch, where the
  /// range is unchanged but every figure behind it belongs to someone else.
  /// Every other caller invalidates only what its own input can have moved.
  Future<void> loadReports({String? type, bool force = false}) async {
    if (force) {
      _reportCache.clear();
      _trendKey = null;
    }

    final req = ++_reportReq;
    emit(state.copyWith(
      reportType: type,
      reportLoading: true,
      reportLoadingMore: false,
      clearReportError: true,
    ));

    final start = Dates.iso(state.startDate);
    final end = Dates.iso(state.endDate);
    final rangeKey = '$start|$end';
    try {
      if (_trendKey != rangeKey) {
        final bill = await _repo.report(
            type: 'billwise', startDate: start, endDate: end, perPage: 100);
        if (req != _reportReq) return;
        _applyRangeSummary(bill);
        _trendKey = rangeKey;
      }

      final data = await _fetchReportPage(1);
      if (req != _reportReq) return;
      _applyReportPage(data, append: false);
    } on ApiException catch (e) {
      if (req == _reportReq) emit(state.copyWith(reportError: e.message));
    } catch (e) {
      if (req == _reportReq) {
        emit(state.copyWith(
            reportError: networkErrorMessage(e, 'Could not load the report.')));
      }
    }
    if (req == _reportReq && !isClosed) {
      emit(state.copyWith(reportLoading: false));
    }
  }

  /// The Reports refresh button: re-pull everything for the range on screen
  /// straight from the API. `force` drops both cached breakdown sides and the
  /// trend key, so neither the By Item / By Staff toggle nor the per-day
  /// chart can hand back figures from before the tap. Unlike re-tapping a
  /// preset it keeps the range as-is, so a custom range refreshes too.
  Future<void> refreshReports() async {
    await Future.wait([loadReports(force: true), loadOverview()]);
  }

  // ---- PDF export -------------------------------------------------------------

  /// The API's per_page ceiling (GetAction clamps to 100).
  static const int _exportPerPage = 100;

  /// How far an export walks: 50 × 100 = 5,000 lines or bills. Past that a
  /// printed table is not something anyone reads, and the requests pile up.
  static const int _exportPageCap = 50;

  /// Everything the PDF for [kind] prints, fetched fresh for the range, branch
  /// and item filters on screen. The screen pages its tables 20 rows at a time
  /// for scrolling; a printed report has to carry every line, so this walks the
  /// pages instead of reusing [reportRows]. Throws what the repository throws —
  /// the caller reports it.
  ///
  /// [withCategories] also pulls the overview's sales by category — the
  /// thermal summary prints it in place of the top items.
  Future<ReportExport> exportReport(ReportExportKind kind, {bool withCategories = false}) async {
    final from = state.startDate;
    final to = state.endDate;
    final start = Dates.iso(from);
    final end = Dates.iso(to);
    final now = DateTime.now();

    switch (kind) {
      case ReportExportKind.overview:
        // A one-day range's "by day" table is a single row repeating the
        // totals, so it is left out — and its bills are never walked.
        final byDay = start != end;
        final results = await Future.wait<Object>([
          _repo.report(type: 'overview', startDate: start, endDate: end),
          if (byDay) _allReportRows('billwise', start, end),
          // Every item type, ranked by amount: the summary covers the whole
          // range, not whatever the By Category screen is filtered to.
          if (withCategories) _allReportRows('categorywise', start, end, sort: 'amount'),
        ]);
        final bills = byDay ? results[1] as _ExportRows : null;
        final categories = withCategories ? results.last as _ExportRows : null;
        final byDate = <String, ReportExportDay>{};
        for (final bill in bills?.rows ?? const <Map<String, dynamic>>[]) {
          final date = asStr(bill['date']).split(' ').first;
          byDate[date] = (byDate[date] ?? ReportExportDay(date: date)).add(bill);
        }
        return ReportExport(
          kind: kind,
          startDate: from,
          endDate: to,
          generatedAt: now,
          overview: SalesOverview.fromJson(results[0] as Map<String, dynamic>),
          days: bills == null || bills.truncated
              ? const []
              : (byDate.values.toList()..sort((a, b) => a.date.compareTo(b.date))),
          daysComplete: !(bills?.truncated ?? false),
          categories: categories?.rows.map(ReportExportLine.category).toList() ?? const [],
          categoryCount: categories?.total ?? 0,
        );
      case ReportExportKind.items:
        final byQty = state.sortKey == 'quantity';
        final productType = state.itemProductType;
        final data = await _allReportRows('itemwise', start, end,
            sort: state.sortKey,
            direction: state.sortAscending ? 'asc' : 'desc',
            productType: productType);
        return ReportExport(
          kind: kind,
          startDate: from,
          endDate: to,
          generatedAt: now,
          lines: data.rows.map(ReportExportLine.item).toList(),
          lineCount: data.total,
          totalAmount: asNum(data.summary['total_amount']).toDouble(),
          totalQuantity: asNum(data.summary['total_quantity']).toDouble(),
          rankByQty: byQty,
          productType: productType,
        );
      case ReportExportKind.categories:
        final byQty = state.sortKey == 'quantity';
        final productType = state.itemProductType;
        final data = await _allReportRows('categorywise', start, end,
            sort: state.sortKey,
            direction: state.sortAscending ? 'asc' : 'desc',
            productType: productType);
        return ReportExport(
          kind: kind,
          startDate: from,
          endDate: to,
          generatedAt: now,
          lines: data.rows.map(ReportExportLine.category).toList(),
          lineCount: data.total,
          totalAmount: asNum(data.summary['total_amount']).toDouble(),
          totalQuantity: asNum(data.summary['total_quantity']).toDouble(),
          rankByQty: byQty,
          productType: productType,
        );
      case ReportExportKind.stylists:
        final data = await _allReportRows('employeewise', start, end);
        return ReportExport(
          kind: kind,
          startDate: from,
          endDate: to,
          generatedAt: now,
          lines: data.rows.map(ReportExportLine.stylist).toList(),
          lineCount: data.total,
          totalAmount: asNum(data.summary['total_revenue']).toDouble(),
        );
    }
  }

  /// Every row of one report type for the range, [_exportPerPage] a request and
  /// four requests at a time — all at once would queue up to fifty on the
  /// server behind a single tap.
  Future<_ExportRows> _allReportRows(String type, String start, String end,
      {String? sort, String? direction, String? productType}) async {
    Future<Map<String, dynamic>> page(int n) => _repo.report(
          type: type,
          startDate: start,
          endDate: end,
          page: n,
          perPage: _exportPerPage,
          sort: sort,
          direction: direction,
          productType: productType,
        );

    final first = await page(1);
    final pag = (first['pagination'] as Map?) ?? const {};
    final lastPage = asNum(pag['last_page'] ?? 1).toInt();
    final until = lastPage < _exportPageCap ? lastPage : _exportPageCap;
    final rows = _rowsOf(first);
    for (var n = 2; n <= until; n += 4) {
      final batch = await Future.wait([for (var i = n; i < n + 4 && i <= until; i++) page(i)]);
      for (final data in batch) {
        rows.addAll(_rowsOf(data));
      }
    }
    return (
      rows: rows,
      summary: Map<String, dynamic>.from((first['summary'] as Map?) ?? const {}),
      total: asNum(pag['total'] ?? rows.length).toInt(),
      truncated: lastPage > _exportPageCap,
    );
  }

  static List<Map<String, dynamic>> _rowsOf(Map<String, dynamic> data) =>
      [for (final e in (data['rows'] as List?) ?? const []) Map<String, dynamic>.from(e as Map)];

  // ---- Day session reports ----------------------------------------------------

  /// The session the Sale Bill Report is for: the branch's open session, or the
  /// one opened last once the day is shut. Null before any day was opened.
  Future<DaySessionSummary?> currentDaySession() => _repo.currentDaySession();

  /// A day session's Sale Bill Report figures, for the thermal roll.
  Future<DaySessionReport> daySessionReport(String id) => _repo.daySessionReport(id);

  /// The web A4 Sale Bill Report for a day session, as PDF bytes.
  Future<Uint8List> daySessionReportPdf(String id) => _repo.daySessionReportPdf(id);

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

  /// The breakdowns built from product lines — By Item and By Category. Only
  /// these take the Rank By and Type filters; By Staff ranks by revenue alone.
  static bool ranksProducts(String type) => type == 'itemwise' || type == 'categorywise';

  Future<Map<String, dynamic>> _fetchReportPage(int page) => _repo.report(
        type: state.reportType,
        startDate: Dates.iso(state.startDate),
        endDate: Dates.iso(state.endDate),
        page: page,
        perPage: _reportPageSize,
        sort: state.sortKey,
        direction: state.sortAscending ? 'asc' : 'desc',
        productType: ranksProducts(state.reportType) ? state.itemProductType : null,
      );

  void _applyReportPage(Map<String, dynamic> data, {required bool append}) {
    final rows = (data['rows'] as List?) ?? const [];
    final pag = (data['pagination'] as Map?) ?? const {};
    final summary = (data['summary'] as Map?) ?? const {};
    // Always the money total: the ledger has a column each for quantity and
    // amount, so ranking by quantity changes the order, never the measure.
    final total = ranksProducts(state.reportType)
        ? asNum(summary['total_amount']).toDouble()
        : asNum(summary['total_revenue']).toDouble();

    final mapped = switch (state.reportType) {
      'itemwise' => rows.map(_itemRow).toList(),
      'categorywise' => rows.map(_categoryRow).toList(),
      _ => rows.map(_employeeRow).toList(),
    };

    emit(state.copyWith(
      reportPage: asNum(pag['current_page'] ?? 1).toInt(),
      reportLastPage: asNum(pag['last_page'] ?? 1).toInt(),
      reportRowCount: asNum(pag['total'] ?? rows.length).toInt(),
      reportTotal: total,
      reportQuantityTotal: asNum(summary['total_quantity']).toDouble(),
      reportRows: append ? [...state.reportRows, ...mapped] : mapped,
    ));

    // Keep the toggle's copy in step, including pages added by infinite scroll.
    _reportCache[state.reportType] = _ReportCache(
      rows: state.reportRows,
      total: state.reportTotal,
      rowCount: state.reportRowCount,
      page: state.reportPage,
      lastPage: state.reportLastPage,
    );
  }

  ReportRow _itemRow(dynamic e) {
    final m = Map<String, dynamic>.from(e);
    final total = asNum(m['total']).toDouble();
    final qty = asNum(m['quantity']).toDouble();
    final bills = asNum(m['bills_count']).toInt();
    return ReportRow(
      title: asStr(m['item_name']),
      subtitle: '${_qty(qty)} sold · $bills bill${bills == 1 ? '' : 's'}',
      value: Money.of(total),
      amount: total,
      quantity: qty,
      bills: bills,
    );
  }

  ReportRow _categoryRow(dynamic e) {
    final m = Map<String, dynamic>.from(e);
    final total = asNum(m['total']).toDouble();
    final qty = asNum(m['quantity']).toDouble();
    final products = asNum(m['products_count']).toInt();
    final bills = asNum(m['bills_count']).toInt();
    return ReportRow(
      title: asStr(m['category_name']),
      subtitle: '${_qty(qty)} sold · $products item${products == 1 ? '' : 's'}',
      value: Money.of(total),
      amount: total,
      quantity: qty,
      bills: bills,
    );
  }

  ReportRow _employeeRow(dynamic e) {
    final m = Map<String, dynamic>.from(e);
    final rev = asNum(m['revenue']).toDouble();
    final bills = asNum(m['bills_count']).toInt();
    final items = asNum(m['items_count']).toInt();
    return ReportRow(
      id: asStr(m['employee_id']),
      title: asStr(m['employee_name']),
      subtitle: '$bills bills · $items items',
      value: Money.of(rev),
      amount: rev,
      quantity: items.toDouble(),
      bills: bills,
    );
  }

  /// The sort rides on every breakdown request, so changing it empties the
  /// cache for all three sides rather than the two product ones. Re-picking
  /// the live sort flips its direction, which is what tapping a column header
  /// (or the live row in the filter sheet) means.
  void setSort(String key, {bool? ascending}) {
    final flip = ascending ?? (state.sortKey == key ? !state.sortAscending : false);
    if (state.sortKey == key && state.sortAscending == flip) return;
    _reportCache.clear();
    emit(state.copyWith(sortKey: key, sortAscending: flip));
    unawaited(loadReports());
  }

  /// Type rides on the item and category requests alone, so it leaves By
  /// Staff's cached rows standing.
  /// Back to the report the screen opens on: today, ranked by amount, every
  /// item type. The breakdown side (By Item / By Category / By Staff) is the
  /// user's place in the report, not a filter, so it stays where it is.
  void resetReportFilters() {
    final cleared = state.sortKey != 'amount' ||
        state.sortAscending ||
        state.itemProductType != null ||
        state.rangePreset != 'today';
    if (!cleared) return;
    _reportCache.clear();
    _trendKey = null;
    final now = DateTime.now();
    final today = DateTime(now.year, now.month, now.day);
    emit(state.copyWith(
      startDate: today,
      endDate: today,
      rangePreset: 'today',
      sortKey: 'amount',
      sortAscending: false,
      clearItemProductType: true,
    ));
    unawaited(loadReports());
    unawaited(loadOverview());
  }

  void setItemProductType(String? productType) {
    if (state.itemProductType == productType) return;
    emit(state.copyWith(
        itemProductType: productType, clearItemProductType: productType == null));
    _dropProductCaches();
    unawaited(loadReports());
  }

  void _dropProductCaches() => _reportCache
    ..remove('itemwise')
    ..remove('categorywise');

  /// The list's grand total — money, whichever column it is ranked by.
  String get reportTotalText => Money.of(state.reportTotal);

  /// The Qty column's total, empty when the report has no quantity to sum.
  String get reportQuantityText =>
      state.reportQuantityTotal == 0 ? '' : qtyLabel(state.reportQuantityTotal);

  /// The same quantity formatting the ledger's Qty column uses, so a row's
  /// caption and its column can never disagree.
  String _qty(double q) => qtyLabel(q);

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
