import 'package:invo/features/auth/logic/auth_cubit/auth_cubit.dart';
import 'package:invo/shared/domain/constants/global_variables.dart';
import 'package:invo/shared/domain/helpers/formatters.dart';
import 'package:invo/shared/domain/models/index.dart';
import 'package:invo/shared/logic/base/holder_cubit.dart';
import 'package:invo/shared/utils/router/http_utils/common_exception.dart';

import '../../domain/repository/admin_repository.dart';

/// Backs the Day Session screen: the open/closed state, the selected open/close
/// date-&-time, and the toggle. Seeded from the signed-in user and mirrored back
/// into [AuthCubit] so the profile row and dashboard pill stay in sync.
class DaySessionCubit extends HolderCubit {
  DaySessionCubit() {
    seedFromUser(_auth.user);
  }

  AdminRepository get _repo => serviceLocator<AdminRepository>();
  AuthCubit get _auth => serviceLocator<AuthCubit>();

  String status = 'closed';
  DateTime selected = _nowToMinute();
  DaySession? session;

  bool busy = false;
  String? error;

  bool get isOpen => status == 'open';

  void seedFromUser(ApiUser? user) {
    status = user?.daySessionStatus == 'open' ? 'open' : 'closed';
    selected = _nowToMinute();
    error = null;
  }

  void setDate(DateTime date) {
    selected = DateTime(
        date.year, date.month, date.day, selected.hour, selected.minute);
    refresh();
  }

  void setTime(int hour, int minute) {
    selected =
        DateTime(selected.year, selected.month, selected.day, hour, minute);
    refresh();
  }

  void setNow() {
    selected = _nowToMinute();
    refresh();
  }

  Future<DaySessionToggleResult?> toggle() async {
    busy = true;
    error = null;
    refresh();
    try {
      final res = await _repo.toggleDay(Dates.isoDateTime(selected));
      status = res.isOpen ? 'open' : 'closed';
      session = res.session;
      await _auth.syncDaySession(
        status: status,
        openedAt: res.session?.openedAt,
        date: res.session?.openedAt.isNotEmpty == true
            ? res.session!.openedAt.split(' ').first
            : Dates.iso(selected),
        lastClosedAt: status == 'closed'
            ? (res.session?.closedAt ?? Dates.isoDateTime(selected))
            : null,
      );
      busy = false;
      refresh();
      return res;
    } on ApiException catch (e) {
      error = e.message;
    } catch (_) {
      error =
          'Could not update the day session. Check your connection and try again.';
    }
    busy = false;
    refresh();
    return null;
  }

  static DateTime _nowToMinute() {
    final n = DateTime.now();
    return DateTime(n.year, n.month, n.day, n.hour, n.minute);
  }
}
