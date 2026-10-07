part of 'checklist_inbox_cubit.dart';

/// The inbox's underline tabs. Move-in / Move-out are sent to the server as
/// `date_basis` (which restricts the phase and decides which date a range
/// applies to); To sign is applied client-side.
enum ChecklistInboxFilter {
  all('All'),
  moveIn('Move-in'),
  moveOut('Move-out'),
  toSign('To sign');

  const ChecklistInboxFilter(this.label);
  final String label;

  String? get dateBasis => switch (this) {
        ChecklistInboxFilter.moveIn => ChecklistPhase.moveIn,
        ChecklistInboxFilter.moveOut => ChecklistPhase.moveOut,
        _ => null,
      };

  bool matches(ChecklistJob j) => switch (this) {
        ChecklistInboxFilter.all => true,
        ChecklistInboxFilter.moveIn => !j.isMoveOut,
        ChecklistInboxFilter.moveOut => j.isMoveOut,
        ChecklistInboxFilter.toSign => j.needsSignatures,
      };
}

/// Open / Completed / All — the server `status` param.
enum ChecklistStatusFilter {
  open('Open'),
  completed('Completed'),
  all('All');

  const ChecklistStatusFilter(this.label);
  final String label;

  String get param => name;
}

/// Date presets for the inbox range (applied to each row's scheduled_date).
enum ChecklistDatePreset {
  all('All dates'),
  today('Today'),
  week('This week'),
  month('This month'),
  next30('Next 30 days'),
  custom('Custom range');

  const ChecklistDatePreset(this.label);
  final String label;

  /// The inclusive range for [now]; null for [all] and [custom].
  (DateTime, DateTime)? rangeFor(DateTime now) {
    final today = DateTime(now.year, now.month, now.day);
    return switch (this) {
      ChecklistDatePreset.today => (today, today),
      ChecklistDatePreset.week => (
          DateTime(today.year, today.month, today.day - (today.weekday - 1)),
          DateTime(today.year, today.month, today.day + (7 - today.weekday)),
        ),
      ChecklistDatePreset.month => (DateTime(today.year, today.month, 1), DateTime(today.year, today.month + 1, 0)),
      ChecklistDatePreset.next30 => (today, DateTime(today.year, today.month, today.day + 29)),
      _ => null,
    };
  }
}

class ChecklistInboxState extends Equatable {
  const ChecklistInboxState({
    this.status = DataFetchStatus.idle,
    this.jobs = const [],
    this.search = '',
    this.filter = ChecklistInboxFilter.all,
    this.statusFilter = ChecklistStatusFilter.open,
    this.datePreset = ChecklistDatePreset.all,
    this.fromDate,
    this.toDate,
    this.errorMessage,
  });

  final DataFetchStatus status;
  final List<ChecklistJob> jobs;
  final String search;
  final ChecklistInboxFilter filter;
  final ChecklistStatusFilter statusFilter;
  final ChecklistDatePreset datePreset;
  final DateTime? fromDate;
  final DateTime? toDate;
  final String? errorMessage;

  List<ChecklistJob> get visibleJobs => jobs.where(filter.matches).toList();

  /// Anything beyond the default "open, all dates, all phases" view.
  bool get hasActiveFilters =>
      statusFilter != ChecklistStatusFilter.open || datePreset != ChecklistDatePreset.all || filter.dateBasis != null;

  /// "This week" / "3 – 9 Oct 2026" / "All dates".
  String get rangeLabel {
    if (datePreset == ChecklistDatePreset.custom && fromDate != null && toDate != null) {
      return Dates.range(fromDate!, toDate!);
    }
    return datePreset.label;
  }

  ChecklistInboxState copyWith({
    DataFetchStatus? status,
    List<ChecklistJob>? jobs,
    String? search,
    ChecklistInboxFilter? filter,
    ChecklistStatusFilter? statusFilter,
    ChecklistDatePreset? datePreset,
    DateTime? fromDate,
    DateTime? toDate,
    String? errorMessage,
    bool clearRange = false,
    bool clearError = false,
  }) =>
      ChecklistInboxState(
        status: status ?? this.status,
        jobs: jobs ?? this.jobs,
        search: search ?? this.search,
        filter: filter ?? this.filter,
        statusFilter: statusFilter ?? this.statusFilter,
        datePreset: datePreset ?? this.datePreset,
        fromDate: clearRange ? null : (fromDate ?? this.fromDate),
        toDate: clearRange ? null : (toDate ?? this.toDate),
        errorMessage: clearError ? null : (errorMessage ?? this.errorMessage),
      );

  @override
  List<Object?> get props => [status, jobs, search, filter, statusFilter, datePreset, fromDate, toDate, errorMessage];
}
