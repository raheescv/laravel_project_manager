import 'package:flutter/foundation.dart';

import '../core/api_client.dart';
import '../core/api_service.dart';
import '../core/formatters.dart';
import '../models/models.dart';
import 'auth_controller.dart';

/// Backs the Day Session screen: the open/closed state, the selected
/// open/close date-&-time, and the open/close toggle. State is seeded from the
/// signed-in user (`sale_day_session_*`) and refreshed from each toggle
/// response; a successful toggle is mirrored back into [AuthController] so the
/// profile row and dashboard pill stay in sync without a re-login.
class DaySessionController extends ChangeNotifier {
  DaySessionController({required this.service, required this.auth}) {
    seedFromUser(auth.user);
  }

  final ApiService service;
  final AuthController auth;

  /// 'open' | 'closed' — the branch's current day-session state.
  String status = 'closed';

  /// The chosen open/close moment (defaults to "now"). Sent to the API as a
  /// full `yyyy-MM-dd HH:mm:ss` datetime.
  DateTime selected = _nowToMinute();

  /// The session returned by the last toggle (for richer detail after acting).
  DaySession? session;

  bool busy = false;
  String? error;

  bool get isOpen => status == 'open';

  /// Seed the status (and reset the picker to now) from a freshly-loaded user.
  void seedFromUser(ApiUser? user) {
    status = user?.daySessionStatus == 'open' ? 'open' : 'closed';
    selected = _nowToMinute();
    error = null;
  }

  /// Replace the date part, keeping the chosen time.
  void setDate(DateTime date) {
    selected = DateTime(date.year, date.month, date.day, selected.hour, selected.minute);
    notifyListeners();
  }

  /// Replace the time part, keeping the chosen date.
  void setTime(int hour, int minute) {
    selected = DateTime(selected.year, selected.month, selected.day, hour, minute);
    notifyListeners();
  }

  /// Snap the selection back to the current moment.
  void setNow() {
    selected = _nowToMinute();
    notifyListeners();
  }

  /// Open the day (when closed) or close it (when open) at [selected]. Returns
  /// the result on success, or null on failure (with [error] set).
  Future<DaySessionToggleResult?> toggle() async {
    busy = true;
    error = null;
    notifyListeners();
    try {
      final res = await service.toggleDay(Dates.isoDateTime(selected));
      status = res.isOpen ? 'open' : 'closed';
      session = res.session;
      // Mirror into the auth user so other screens reflect the new state.
      await auth.syncDaySession(
        status: status,
        openedAt: res.session?.openedAt,
        date: res.session?.openedAt.isNotEmpty == true
            ? res.session!.openedAt.split(' ').first
            : Dates.iso(selected),
        lastClosedAt: status == 'closed' ? (res.session?.closedAt ?? Dates.isoDateTime(selected)) : null,
      );
      busy = false;
      notifyListeners();
      return res;
    } on ApiException catch (e) {
      error = e.message;
    } catch (_) {
      error = 'Could not update the day session. Check your connection and try again.';
    }
    busy = false;
    notifyListeners();
    return null;
  }

  static DateTime _nowToMinute() {
    final n = DateTime.now();
    return DateTime(n.year, n.month, n.day, n.hour, n.minute);
  }
}
