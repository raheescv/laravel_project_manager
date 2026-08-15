part of 'admin_cubit.dart';

/// State for [AdminCubit] — the §5 shape.
///
/// Covers three independently-loaded areas: the dashboard KPIs, the paginated
/// report (item-wise / employee-wise) and the sales overview. They share the
/// date range but nothing else, so each carries its own loading/error pair.
class AdminState extends Equatable {
  const AdminState({
    required this.startDate,
    required this.endDate,
    this.rangePreset = '7d',
    // dashboard
    this.loading = false,
    this.errorMessage,
    this.dashboard,
    this.topStylists = const [],
    this.topStylistsDate = '',
    this.trendPoints = const [],
    this.trendLabels = const [],
    // report
    this.reportLoading = false,
    this.reportLoadingMore = false,
    this.reportError,
    this.reportType = 'itemwise',
    this.itemMetric = 'amount',
    this.itemProductType,
    this.reportRows = const [],
    this.reportTotal = 0,
    this.reportRowCount = 0,
    this.reportPage = 1,
    this.reportLastPage = 1,
    this.reportTrendPoints = const [],
    this.reportTrendLabels = const [],
    // overview
    this.overviewLoading = false,
    this.overviewError,
    this.overview,
  });

  final DateTime startDate;
  final DateTime endDate;
  final String rangePreset;

  final bool loading;
  final String? errorMessage;
  final DashboardData? dashboard;
  final List<ReportRow> topStylists;

  /// `yyyy-MM-dd` the leaderboard was fetched for — the branch's open
  /// day-session date, which is what the card labels itself with.
  final String topStylistsDate;
  final List<double> trendPoints;
  final List<String> trendLabels;

  final bool reportLoading;
  final bool reportLoadingMore;
  final String? reportError;
  final String reportType;
  final String itemMetric;
  final String? itemProductType;
  final List<ReportRow> reportRows;
  final double reportTotal;
  final int reportRowCount;
  final int reportPage;
  final int reportLastPage;
  final List<double> reportTrendPoints;
  final List<String> reportTrendLabels;

  final bool overviewLoading;
  final String? overviewError;
  final SalesOverview? overview;

  bool get reportHasMore => reportPage < reportLastPage;

  AdminState copyWith({
    DateTime? startDate,
    DateTime? endDate,
    String? rangePreset,
    bool? loading,
    String? errorMessage,
    DashboardData? dashboard,
    List<ReportRow>? topStylists,
    String? topStylistsDate,
    List<double>? trendPoints,
    List<String>? trendLabels,
    bool? reportLoading,
    bool? reportLoadingMore,
    String? reportError,
    String? reportType,
    String? itemMetric,
    String? itemProductType,
    List<ReportRow>? reportRows,
    double? reportTotal,
    int? reportRowCount,
    int? reportPage,
    int? reportLastPage,
    List<double>? reportTrendPoints,
    List<String>? reportTrendLabels,
    bool? overviewLoading,
    String? overviewError,
    SalesOverview? overview,
    bool clearError = false,
    bool clearReportError = false,
    bool clearOverviewError = false,
    bool clearItemProductType = false,
  }) =>
      AdminState(
        startDate: startDate ?? this.startDate,
        endDate: endDate ?? this.endDate,
        rangePreset: rangePreset ?? this.rangePreset,
        loading: loading ?? this.loading,
        errorMessage: clearError ? null : (errorMessage ?? this.errorMessage),
        dashboard: dashboard ?? this.dashboard,
        topStylists: topStylists ?? this.topStylists,
        topStylistsDate: topStylistsDate ?? this.topStylistsDate,
        trendPoints: trendPoints ?? this.trendPoints,
        trendLabels: trendLabels ?? this.trendLabels,
        reportLoading: reportLoading ?? this.reportLoading,
        reportLoadingMore: reportLoadingMore ?? this.reportLoadingMore,
        reportError: clearReportError ? null : (reportError ?? this.reportError),
        reportType: reportType ?? this.reportType,
        itemMetric: itemMetric ?? this.itemMetric,
        itemProductType:
            clearItemProductType ? null : (itemProductType ?? this.itemProductType),
        reportRows: reportRows ?? this.reportRows,
        reportTotal: reportTotal ?? this.reportTotal,
        reportRowCount: reportRowCount ?? this.reportRowCount,
        reportPage: reportPage ?? this.reportPage,
        reportLastPage: reportLastPage ?? this.reportLastPage,
        reportTrendPoints: reportTrendPoints ?? this.reportTrendPoints,
        reportTrendLabels: reportTrendLabels ?? this.reportTrendLabels,
        overviewLoading: overviewLoading ?? this.overviewLoading,
        overviewError:
            clearOverviewError ? null : (overviewError ?? this.overviewError),
        overview: overview ?? this.overview,
      );

  @override
  List<Object?> get props => [
        startDate, endDate, rangePreset,
        loading, errorMessage, dashboard, topStylists, topStylistsDate,
        trendPoints, trendLabels,
        reportLoading, reportLoadingMore, reportError, reportType, itemMetric,
        itemProductType, reportRows, reportTotal, reportRowCount, reportPage,
        reportLastPage, reportTrendPoints, reportTrendLabels,
        overviewLoading, overviewError, overview,
      ];
}
