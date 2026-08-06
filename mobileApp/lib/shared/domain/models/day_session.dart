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
