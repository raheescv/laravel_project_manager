import 'package:equatable/equatable.dart';

import '../helpers/formatters.dart';

/// A day session as `GET /admin/day-sessions/current` names it — the head of
/// its Sale Bill Report. Times are the branch's wall clock (`Y-m-d H:i:s`).
class DaySessionSummary extends Equatable {
  const DaySessionSummary({
    required this.id,
    required this.branch,
    required this.status,
    required this.openedAt,
    required this.closedAt,
    required this.openedBy,
    required this.closedBy,
    this.branchLocation = '',
    this.branchMobile = '',
  });

  final String id;
  final String branch;
  final String status; // open | closed
  final String openedAt;
  final String closedAt; // '' while open
  final String openedBy;
  final String closedBy;
  final String branchLocation;
  final String branchMobile;

  bool get isOpen => status == 'open';

  factory DaySessionSummary.fromJson(Map<String, dynamic> j) => DaySessionSummary(
        id: asStr(j['id']),
        branch: asStr(j['branch']),
        status: asStr(j['status']),
        openedAt: asStr(j['opened_at']),
        closedAt: asStr(j['closed_at']),
        openedBy: asStr(j['opened_by']),
        closedBy: asStr(j['closed_by']),
        branchLocation: asStr(j['branch_location']),
        branchMobile: asStr(j['branch_mobile']),
      );

  @override
  List<Object?> get props =>
      [id, branch, status, openedAt, closedAt, openedBy, closedBy, branchLocation, branchMobile];
}

/// A day session's "Sale Bill Report" (`GET /admin/day-sessions/{id}/report`) —
/// the figures the web thermal print (`sale.day-session-print`) shows.
class DaySessionReport extends Equatable {
  const DaySessionReport({
    required this.session,
    required this.transactions,
    required this.dues,
    required this.duePayments,
    required this.totals,
  });

  final DaySessionSummary session;
  final List<DaySessionTransaction> transactions;
  final List<DaySessionDue> dues;
  final List<DaySessionDuePayment> duePayments;
  final DaySessionTotals totals;

  factory DaySessionReport.fromJson(Map<String, dynamic> j) => DaySessionReport(
        session: DaySessionSummary.fromJson(Map<String, dynamic>.from((j['session'] as Map?) ?? const {})),
        transactions: _list(j['transactions'], DaySessionTransaction.fromJson),
        dues: _list(j['due_transactions'], DaySessionDue.fromJson),
        duePayments: _list(j['due_payments'], DaySessionDuePayment.fromJson),
        totals: DaySessionTotals.fromJson(Map<String, dynamic>.from((j['totals'] as Map?) ?? const {})),
      );

  @override
  List<Object?> get props => [session, transactions, dues, duePayments, totals];
}

List<T> _list<T>(dynamic raw, T Function(Map<String, dynamic>) fromJson) =>
    [for (final e in (raw as List?) ?? const []) fromJson(Map<String, dynamic>.from(e as Map))];

/// An invoice or tailoring order taken in the session, with its payments.
class DaySessionTransaction extends Equatable {
  const DaySessionTransaction({
    required this.source,
    required this.referenceNo,
    required this.amount,
    required this.paidAmount,
    required this.dueAmount,
    required this.payments,
  });

  final String source; // Sale | Tailoring
  final String referenceNo;
  final double amount;
  final double paidAmount;
  final double dueAmount;
  final List<DaySessionPayment> payments;

  factory DaySessionTransaction.fromJson(Map<String, dynamic> j) => DaySessionTransaction(
        source: asStr(j['source']),
        referenceNo: asStr(j['reference_no']),
        amount: asNum(j['amount']).toDouble(),
        paidAmount: asNum(j['paid_amount']).toDouble(),
        dueAmount: asNum(j['due_amount']).toDouble(),
        payments: _list(j['payments'], DaySessionPayment.fromJson),
      );

  @override
  List<Object?> get props => [source, referenceNo, amount, paidAmount, dueAmount, payments];
}

/// One payment method's share of a transaction.
class DaySessionPayment extends Equatable {
  const DaySessionPayment({required this.method, required this.amount});
  final String method;
  final double amount;

  factory DaySessionPayment.fromJson(Map<String, dynamic> j) =>
      DaySessionPayment(method: asStr(j['method']), amount: asNum(j['amount']).toDouble());

  @override
  List<Object?> get props => [method, amount];
}

/// A transaction left with something still owing.
class DaySessionDue extends Equatable {
  const DaySessionDue({required this.source, required this.referenceNo, required this.dueAmount});
  final String source;
  final String referenceNo;
  final double dueAmount;

  factory DaySessionDue.fromJson(Map<String, dynamic> j) => DaySessionDue(
        source: asStr(j['source']),
        referenceNo: asStr(j['reference_no']),
        dueAmount: asNum(j['due_amount']).toDouble(),
      );

  @override
  List<Object?> get props => [source, referenceNo, dueAmount];
}

/// A payment taken this session against an earlier session's invoice.
class DaySessionDuePayment extends Equatable {
  const DaySessionDuePayment({
    required this.source,
    required this.referenceNo,
    required this.paymentMethod,
    required this.amount,
  });

  final String source;
  final String referenceNo;
  final String paymentMethod;
  final double amount;

  factory DaySessionDuePayment.fromJson(Map<String, dynamic> j) => DaySessionDuePayment(
        source: asStr(j['source']),
        referenceNo: asStr(j['reference_no']),
        paymentMethod: asStr(j['payment_method']),
        amount: asNum(j['amount']).toDouble(),
      );

  @override
  List<Object?> get props => [source, referenceNo, paymentMethod, amount];
}

/// The report's total summary, including the three sums the web views work
/// out inline (card / cash with dues, grand total payment).
class DaySessionTotals extends Equatable {
  const DaySessionTotals({
    this.credit = 0,
    this.cash = 0,
    this.card = 0,
    this.saleAmount = 0,
    this.paymentTotal = 0,
    this.dueCash = 0,
    this.dueCard = 0,
    this.dueTotal = 0,
    this.cardWithDue = 0,
    this.cashWithDue = 0,
    this.grandTotalPayment = 0,
  });

  final double credit;
  final double cash;
  final double card;
  final double saleAmount;
  final double paymentTotal;
  final double dueCash;
  final double dueCard;
  final double dueTotal;
  final double cardWithDue;
  final double cashWithDue;
  final double grandTotalPayment;

  factory DaySessionTotals.fromJson(Map<String, dynamic> j) => DaySessionTotals(
        credit: asNum(j['credit']).toDouble(),
        cash: asNum(j['cash']).toDouble(),
        card: asNum(j['card']).toDouble(),
        saleAmount: asNum(j['sale_tailoring_amount']).toDouble(),
        paymentTotal: asNum(j['payment_total']).toDouble(),
        dueCash: asNum(j['due_total_cash']).toDouble(),
        dueCard: asNum(j['due_total_card']).toDouble(),
        dueTotal: asNum(j['due_total']).toDouble(),
        cardWithDue: asNum(j['card_with_due']).toDouble(),
        cashWithDue: asNum(j['cash_with_due']).toDouble(),
        grandTotalPayment: asNum(j['grand_total_payment']).toDouble(),
      );

  @override
  List<Object?> get props => [
        credit,
        cash,
        card,
        saleAmount,
        paymentTotal,
        dueCash,
        dueCard,
        dueTotal,
        cardWithDue,
        cashWithDue,
        grandTotalPayment,
      ];
}
