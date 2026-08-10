import 'package:invo/features/auth/logic/auth_cubit/auth_cubit.dart';
import 'package:invo/shared/domain/constants/global_variables.dart';
import 'package:invo/shared/domain/helpers/formatters.dart';
import 'package:invo/shared/domain/models/index.dart';
import 'package:equatable/equatable.dart';
import 'package:flutter_bloc/flutter_bloc.dart';
import 'package:invo/shared/utils/router/http_utils/common_exception.dart';

import '../../domain/repository/admin_repository.dart';

part 'day_session_state.dart';

/// Backs the Day Session screen: the open/closed state, the selected open/close
/// date-&-time, and the toggle. Seeded from the signed-in user and mirrored back
/// into [AuthCubit] so the profile row and dashboard pill stay in sync.
class DaySessionCubit extends Cubit<DaySessionState> {
  DaySessionCubit() : super(DaySessionState(selected: _nowToMinute())) {
    seedFromUser(serviceLocator<AuthCubit>().user);
  }

  AdminRepository get _repo => serviceLocator<AdminRepository>();
  AuthCubit get _auth => serviceLocator<AuthCubit>();

  // Read facade over `state`.
  String get status => state.status;
  DateTime get selected => state.selected;
  DaySession? get session => state.session;
  bool get busy => state.busy;
  String? get error => state.errorMessage;
  bool get isOpen => state.isOpen;

  void seedFromUser(ApiUser? user) => emit(DaySessionState(
        status: user?.daySessionStatus == 'open' ? 'open' : 'closed',
        selected: _nowToMinute(),
      ));

  void setDate(DateTime date) => emit(state.copyWith(
        selected: DateTime(date.year, date.month, date.day,
            state.selected.hour, state.selected.minute),
      ));

  void setTime(int hour, int minute) => emit(state.copyWith(
        selected: DateTime(state.selected.year, state.selected.month,
            state.selected.day, hour, minute),
      ));

  void setNow() => emit(state.copyWith(selected: _nowToMinute()));

  Future<DaySessionToggleResult?> toggle() async {
    final at = state.selected;
    emit(state.copyWith(busy: true, clearError: true));
    try {
      // Absolute instant, not a wall clock — the server validates this against
      // its own `now`, so it must not have to guess the device's timezone.
      final res = await _repo.toggleDay(Dates.instant(at));
      final next = res.isOpen ? 'open' : 'closed';
      await _auth.syncDaySession(
        status: next,
        openedAt: res.session?.openedAt,
        date: res.session?.openedAt.isNotEmpty == true
            ? res.session!.openedAt.split(' ').first
            : Dates.iso(at),
        lastClosedAt:
            next == 'closed' ? (res.session?.closedAt ?? Dates.isoDateTime(at)) : null,
      );
      emit(state.copyWith(status: next, session: res.session, busy: false));
      return res;
    } on ApiException catch (e) {
      emit(state.copyWith(busy: false, errorMessage: e.message));
    } catch (_) {
      emit(state.copyWith(
          busy: false,
          errorMessage:
              'Could not update the day session. Check your connection and try again.'));
    }
    return null;
  }

  static DateTime _nowToMinute() {
    final n = DateTime.now();
    return DateTime(n.year, n.month, n.day, n.hour, n.minute);
  }
}
