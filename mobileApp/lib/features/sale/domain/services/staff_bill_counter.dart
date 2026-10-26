import 'dart:math';

import 'package:invo/shared/domain/constants/global_variables.dart';
import 'package:invo/shared/domain/helpers/formatters.dart';
import 'package:invo/shared/domain/models/index.dart';
import 'package:invo/shared/utils/local_storage/local_storage_service.dart';

/// A biller's Office Bill number the till counted for a sale: `{code}/{sequence}`
/// on business day [date].
typedef StaffBillClaim = ({int sequence, String date});

/// Counts each biller's Office Bill numbers on this till, so a sale rung up
/// offline prints the `{code}/{n}` staff tally their day by — not a provisional
/// reference that leaves a hole in their pile of slips.
///
/// The server owns the count (`AssignStaffBillNoAction`); the till only follows
/// it. It learns the figure from sign-in and day status ([ApiUser.billSequence]),
/// from every sale the server confirms, and from each number it hands out
/// offline. Every completed sale claims the next number, online too: the server
/// keeps a claim that is still free, and a sale it numbered itself would take
/// max + 1 and land on a number an offline sale here has already printed.
///
/// A number is recorded only once its sale is captured or confirmed, so a charge
/// that fails or is abandoned never leaves a gap in the count.
class StaffBillCounter {
  LocalStorageService get _storage => serviceLocator<LocalStorageService>();

  /// The number the next completed sale by [user] claims; null without a user.
  StaffBillClaim? nextFor(ApiUser? user) {
    if (user == null || user.id.isEmpty) return null;
    final date = businessDate(user);
    final known = user.daySessionDate == date ? user.billSequence : 0;
    return (sequence: max(known, _recorded(user.id, date)) + 1, date: date);
  }

  /// Remember that [userId] has reached [sequence] on [date]. Only ever moves
  /// forward: a sale from an earlier day syncing late leaves today's count alone.
  ///
  /// Never throws — a lost note only means the next claim starts from the
  /// server's figure, which the server corrects if it has to.
  Future<void> record(String userId, String date, int sequence) async {
    final day = date.length >= 10 ? date.substring(0, 10) : date;
    if (userId.isEmpty || day.isEmpty || sequence <= 0) return;
    final current = _read(userId);
    if (current != null) {
      final order = current.date.compareTo(day);
      if (order > 0 || (order == 0 && current.sequence >= sequence)) return;
    }
    try {
      await _storage.setStaffBillFor(userId, '$day:$sequence');
    } on Exception catch (_) {
      // Storage refused the write; the server's figure covers the next claim.
    }
  }

  /// The day the server will file a sale rung up now under: the open day
  /// session's date, else today — the V1 `CreateAction` + `Sale` model rule.
  static String businessDate(ApiUser user) =>
      user.dayOpen && user.daySessionDate.isNotEmpty ? user.daySessionDate : Dates.today();

  /// `{code}/{n}` as the server writes it — the user id when there is no code.
  static String billNo(ApiUser user, int sequence) =>
      '${user.code.isNotEmpty ? user.code : user.id}/$sequence';

  int _recorded(String userId, String date) {
    final current = _read(userId);
    return current != null && current.date == date ? current.sequence : 0;
  }

  StaffBillClaim? _read(String userId) {
    final raw = _storage.staffBillFor(userId) ?? '';
    final at = raw.lastIndexOf(':');
    if (at <= 0) return null;
    return (date: raw.substring(0, at), sequence: asNum(raw.substring(at + 1)).toInt());
  }
}
