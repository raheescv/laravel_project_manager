import 'package:equatable/equatable.dart';
import 'package:flutter_bloc/flutter_bloc.dart';

import 'package:invo/shared/domain/constants/data_fetching_status.dart';
import 'package:invo/shared/domain/helpers/formatters.dart';
import 'package:invo/shared/utils/components/app_strings.dart';
import 'package:invo/shared/utils/router/http_utils/common_exception.dart';

import '../../domain/models/checklist_models.dart';
import '../../domain/repository/checklist_repository.dart';

part 'checklist_inbox_state.dart';

/// Backs the Checklists tab: hand-over jobs (rent-out × phase) with server-side
/// search, status, date-basis and date range, plus the client-side To sign tab.
class ChecklistInboxCubit extends Cubit<ChecklistInboxState> {
  ChecklistInboxCubit(this._repo) : super(const ChecklistInboxState());

  final ChecklistRepository _repo;
  int _reqId = 0;

  /// [silent] keeps the current rows on screen while refreshing (pull-to-
  /// refresh, returning from a hand-over).
  Future<void> load({bool silent = false}) async {
    final req = ++_reqId;
    if (!silent || state.jobs.isEmpty) {
      emit(state.copyWith(status: DataFetchStatus.waiting, clearError: true));
    }
    final from = state.fromDate;
    final to = state.toDate;
    try {
      final jobs = await _repo.jobs(
        search: state.search,
        status: state.statusFilter.param,
        dateBasis: state.filter.dateBasis,
        fromDate: from == null ? null : Dates.iso(from),
        toDate: to == null ? null : Dates.iso(to),
      );
      if (isClosed || req != _reqId) return;
      emit(state.copyWith(status: DataFetchStatus.success, jobs: jobs, clearError: true));
    } on ApiException catch (e) {
      if (isClosed || req != _reqId) return;
      emit(state.copyWith(status: DataFetchStatus.failed, errorMessage: e.message));
    } catch (_) {
      if (isClosed || req != _reqId) return;
      emit(state.copyWith(status: DataFetchStatus.failed, errorMessage: AppStrings.somethingWentWrong));
    }
  }

  /// Drops rows, search and filters — the cubit outlives a sign-out, so the
  /// tab resets when the shell is rebuilt for a (possibly different) user.
  void reset() {
    _reqId++;
    emit(const ChecklistInboxState());
  }

  void setSearch(String value) {
    final q = value.trim();
    if (q == state.search) return;
    emit(state.copyWith(search: q));
    load();
  }

  /// Tabs: Move-in / Move-out change the server `date_basis`, so they reload.
  void setFilter(ChecklistInboxFilter filter) {
    if (filter == state.filter) return;
    final reload = filter.dateBasis != state.filter.dateBasis;
    emit(state.copyWith(filter: filter));
    if (reload) load();
  }

  void setStatusFilter(ChecklistStatusFilter value) {
    if (value == state.statusFilter) return;
    emit(state.copyWith(statusFilter: value));
    load();
  }

  /// Applies a preset; [custom] supplies the range for [ChecklistDatePreset.custom].
  void setDatePreset(ChecklistDatePreset preset, {(DateTime, DateTime)? custom}) {
    final range = preset == ChecklistDatePreset.custom ? custom : preset.rangeFor(DateTime.now());
    if (preset == ChecklistDatePreset.custom && range == null) return;
    emit(range == null
        ? state.copyWith(datePreset: preset, clearRange: true)
        : state.copyWith(datePreset: preset, fromDate: range.$1, toDate: range.$2));
    load();
  }

  /// Back to the default view (open, all dates, all phases); keeps the search.
  void clearFilters() {
    emit(state.copyWith(
      filter: ChecklistInboxFilter.all,
      statusFilter: ChecklistStatusFilter.open,
      datePreset: ChecklistDatePreset.all,
      clearRange: true,
    ));
    load();
  }
}
