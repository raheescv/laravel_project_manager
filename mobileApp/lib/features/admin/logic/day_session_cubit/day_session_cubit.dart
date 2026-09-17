import 'dart:async';

import 'package:invo/features/auth/logic/auth_cubit/auth_cubit.dart';
import 'package:invo/shared/domain/constants/global_variables.dart';
import 'package:invo/shared/domain/constants/mobile_permissions.dart';
import 'package:invo/shared/domain/helpers/formatters.dart';
import 'package:invo/shared/domain/models/index.dart';
import 'package:equatable/equatable.dart';
import 'package:flutter_bloc/flutter_bloc.dart';
import 'package:invo/shared/utils/local_storage/local_storage_service.dart';
import 'package:invo/shared/utils/router/http_utils/common_exception.dart';

import '../../domain/repository/admin_repository.dart';

part 'day_session_state.dart';

/// Backs the Day Session screen: the open/closed state, the selected open/close
/// date-&-time, and the toggle. Seeded from the signed-in user and mirrored back
/// into [AuthCubit] so the profile row and dashboard pill stay in sync.
class DaySessionCubit extends Cubit<DaySessionState> {
  DaySessionCubit() : super(DaySessionState(selected: _nowToMinute())) {
    seedFromUser(_auth.user);
    // Provided once for the life of the app, so the session's comings and
    // goings are followed by hand: a sign-out drops the last cashier's day,
    // and a day moved elsewhere — the dashboard's own re-read, a toggle on
    // another screen — is mirrored so the hero here never contradicts the
    // pill on the dashboard.
    _authSub = _auth.stream.listen(_onAuth);
  }

  AdminRepository get _repo => serviceLocator<AdminRepository>();
  AuthCubit get _auth => serviceLocator<AuthCubit>();
  LocalStorageService get _storage => serviceLocator<LocalStorageService>();

  StreamSubscription<AuthState>? _authSub;
  int _refreshReq = 0;
  int _reportReq = 0;

  // Read facade over `state`.
  String get status => state.status;
  DateTime get selected => state.selected;
  DaySession? get session => state.session;
  bool get busy => state.busy;
  bool get syncing => state.syncing;
  String? get error => state.errorMessage;
  bool get isOpen => state.isOpen;
  DaySessionSummary? get report => state.report;

  /// Whether closing the day also prints its Sale Bill Report — this till's
  /// last answer on the close sheet, printing until it is first answered.
  bool get printOnClose => _storage.daySessionPrintOnClose ?? true;
  Future<void> setPrintOnClose(bool v) => _storage.setDaySessionPrintOnClose(v);

  // The report's session is kept: [refresh] replaces it straight after, and
  // dropping it here would blink the report card off and on.
  void seedFromUser(ApiUser? user) => emit(DaySessionState(
        status: user?.daySessionStatus == 'open' ? 'open' : 'closed',
        selected: _nowToMinute(),
        report: state.report,
      ));

  void _onAuth(AuthState s) {
    if (s.status == AuthStatus.signedOut) {
      ++_refreshReq;
      ++_reportReq;
      emit(DaySessionState(selected: _nowToMinute()));
      return;
    }
    final user = s.user;
    // A toggle syncs the user before it emits its own result; leave it to.
    if (user == null || state.busy) return;
    final next = user.dayOpen ? 'open' : 'closed';
    if (next == state.status) return;
    // The session details belong to the status they came with; the next
    // [refresh] brings the new ones.
    emit(state.copyWith(status: next, clearSession: true));
  }

  /// Re-reads the branch's day-session state from the server and syncs it
  /// into the cached user. That copy is only as fresh as the last sign-in or
  /// toggle on *this* device — on a shared till another device (or the web)
  /// can open or close the day underneath it — and the server answers for the
  /// branch the app is operating as, so a branch switch is covered too.
  ///
  /// Keeps the dialled-in moment. Offline, or refused, the cached status
  /// stands: it is still the best answer there is, and the toggle keeps
  /// working on it.
  ///
  /// True when the server answered — the closed-day gate says "still closed"
  /// only then, and "couldn't reach the server" otherwise. [statusOnly] skips
  /// the report lookup, for the sale flow's checks (New Sale re-reads the day
  /// on every visit, and the report card isn't on show there).
  Future<bool> refresh({bool statusOnly = false}) async {
    if (state.busy) return false;
    final req = ++_refreshReq;
    emit(state.copyWith(syncing: true, clearError: true));
    if (!statusOnly) unawaited(_loadReport());
    try {
      final live = await _repo.dayStatus();
      if (req != _refreshReq || isClosed) return false;
      await _auth.applyDayStatus(live);
      if (req != _refreshReq || isClosed) return false;
      emit(state.copyWith(
        status: live.status,
        session: live.session,
        clearSession: live.session == null,
        syncing: false,
      ));
      return true;
    } catch (_) {
      if (req == _refreshReq && !isClosed) emit(state.copyWith(syncing: false));
      return false;
    }
  }

  /// Looks up the session the Sale Bill Report is for. Only for those who may
  /// print it; a failure keeps the last answer, as printing re-reads the
  /// figures anyway.
  Future<void> _loadReport() async {
    if (!_auth.hasPermission(PermissionSlug.daySessionPrint)) return;
    final req = ++_reportReq;
    try {
      final found = await _repo.currentDaySession();
      if (req != _reportReq || isClosed) return;
      emit(state.copyWith(report: found, clearReport: found == null));
    } catch (_) {}
  }

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
      // A close turns the open report into the final one; an open starts a new one.
      unawaited(_loadReport());
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

  @override
  Future<void> close() {
    _authSub?.cancel();
    return super.close();
  }

  static DateTime _nowToMinute() {
    final n = DateTime.now();
    return DateTime(n.year, n.month, n.day, n.hour, n.minute);
  }
}
