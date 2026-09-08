import 'package:equatable/equatable.dart';

import '../helpers/formatters.dart';

/// One branch sale-day session (Laravel `DaySessionResource`). Returned by the
/// open/close toggle; amounts are nullable until a closing/expected value is set.
class DaySession extends Equatable {
  const DaySession({
    required this.id,
    required this.branch,
    required this.status,
    required this.openedAt,
    required this.closedAt,
    required this.openedBy,
    required this.closedBy,
    required this.openingAmount,
    required this.closingAmount,
    required this.expectedAmount,
  });

  final String id;
  final String branch;
  final String status; // open | closed
  final String openedAt; // ISO datetime
  final String closedAt; // ISO datetime ('' while open)
  final String openedBy;
  final String closedBy;
  final double openingAmount;
  final double? closingAmount;
  final double? expectedAmount;

  bool get isOpen => status == 'open';

  factory DaySession.fromJson(Map<String, dynamic> j) => DaySession(
        id: asStr(j['id']),
        branch: asStr(j['branch']),
        status: asStr(j['status']),
        openedAt: asStr(j['opened_at']),
        closedAt: asStr(j['closed_at']),
        openedBy: asStr(j['opened_by']),
        closedBy: asStr(j['closed_by']),
        openingAmount: asNum(j['opening_amount']).toDouble(),
        closingAmount: j['closing_amount'] == null ? null : asNum(j['closing_amount']).toDouble(),
        expectedAmount: j['expected_amount'] == null ? null : asNum(j['expected_amount']).toDouble(),
      );


  @override
  List<Object?> get props => [
        id,
        branch,
        status,
        openedAt,
        closedAt,
        openedBy,
        closedBy,
        openingAmount,
        closingAmount,
        expectedAmount,
      ];
}

/// The envelope returned by `POST /admin/day-status` — a message, the resulting
/// `status`, and the affected [session].
class DaySessionToggleResult extends Equatable {
  const DaySessionToggleResult({required this.message, required this.status, required this.session});
  final String message;
  final String status; // open | closed (the new state)
  final DaySession? session;

  bool get isOpen => status == 'open';

  factory DaySessionToggleResult.fromJson(Map<String, dynamic> j) => DaySessionToggleResult(
        message: asStr(j['message']),
        status: asStr(j['status']),
        session: j['session'] is Map
            ? DaySession.fromJson(Map<String, dynamic>.from(j['session']))
            : null,
      );


  @override
  List<Object?> get props => [
        message,
        status,
        session,
      ];
}

/// The live day-session state of the user's branch (`GET /admin/day-status`).
///
/// The signed-in [ApiUser] carries a copy of this, but it is only as fresh as
/// the last sign-in or toggle *on this device* — on a shared till another
/// device (or the web) can open or close the day underneath it. The dashboard
/// re-reads this on every refresh and syncs it back into the cached user, so
/// the day pill's status and date come from the database rather than whatever
/// this device last saw.
class DayStatus extends Equatable {
  const DayStatus({
    required this.status,
    required this.date,
    required this.openedAt,
    required this.lastClosedAt,
    this.session,
  });

  final String status; // open | closed
  final String date; // 'yyyy-MM-dd' business day
  final String openedAt; // 'Y-m-d H:i:s' while open, else ''
  final String lastClosedAt; // 'Y-m-d H:i:s' of the most recent close, else ''
  final DaySession? session;

  bool get isOpen => status == 'open';

  factory DayStatus.fromJson(Map<String, dynamic> j) => DayStatus(
        status: asStr(j['status']).isNotEmpty
            ? asStr(j['status'])
            : (j['is_open'] == true ? 'open' : 'closed'),
        date: asStr(j['date']),
        openedAt: asStr(j['opened_at']),
        lastClosedAt: asStr(j['last_closed_at']),
        session: j['session'] is Map
            ? DaySession.fromJson(Map<String, dynamic>.from(j['session']))
            : null,
      );

  @override
  List<Object?> get props => [status, date, openedAt, lastClosedAt, session];
}
