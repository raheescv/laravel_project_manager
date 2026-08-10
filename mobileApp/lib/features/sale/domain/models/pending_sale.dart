import 'package:equatable/equatable.dart';
import 'package:invo/shared/domain/helpers/formatters.dart';
import 'package:invo/shared/domain/models/index.dart';

/// Where a queued sale is in its journey to the server.
enum PendingSaleStatus {
  /// Waiting for the next drain.
  pending,

  /// Being posted right now.
  syncing,

  /// The server refused it for a reason retrying cannot fix (a 4xx: stock gone,
  /// a product deleted, validation). A person has to look at it.
  failed,

  /// Committed on the server. Kept briefly so the cashier can reprint it with
  /// the real invoice number, then purged.
  synced,
}

extension PendingSaleStatusX on PendingSaleStatus {
  String get storageValue => name;

  static PendingSaleStatus parse(String? raw) => PendingSaleStatus.values.firstWhere(
        (s) => s.name == raw,
        orElse: () => PendingSaleStatus.pending,
      );

  String get label => switch (this) {
        PendingSaleStatus.pending => 'Waiting to sync',
        PendingSaleStatus.syncing => 'Syncing…',
        PendingSaleStatus.failed => 'Needs attention',
        PendingSaleStatus.synced => 'Synced',
      };
}

/// One sale rung up on this device that the server has not acknowledged yet.
///
/// [payload] is the POST body verbatim, so replaying is a dumb re-send with no
/// re-derivation — the figures that sync are the figures the customer paid.
/// [saleJson] is the same ticket in `SaleResource` shape, which is what lets the
/// invoice screen and the receipt render a queued sale with no network.
class PendingSale extends Equatable {
  const PendingSale({
    required this.clientUuid,
    required this.payload,
    required this.saleJson,
    required this.provisionalRef,
    required this.userId,
    required this.createdAt,
    required this.status,
    this.branchId,
    this.attempts = 0,
    this.lastAttemptAt,
    this.lastError,
    this.serverSaleId,
    this.invoiceNo,
  });

  final String clientUuid;
  final Map<String, dynamic> payload;
  final Map<String, dynamic> saleJson;

  /// Device-local reference printed in place of an invoice number until the
  /// server assigns the real one (e.g. `OFF-7K2-0042`).
  final String provisionalRef;

  final String userId;
  final int? branchId;
  final DateTime createdAt;
  final PendingSaleStatus status;
  final int attempts;
  final DateTime? lastAttemptAt;
  final String? lastError;
  final String? serverSaleId;
  final String? invoiceNo;

  /// The queued ticket as the rest of the app's screens expect it.
  Sale get sale => Sale.fromJson(saleJson);

  /// What to show where an invoice number would go.
  String get displayRef => invoiceNo?.isNotEmpty == true ? invoiceNo! : provisionalRef;

  /// A parked ticket rather than a completed sale.
  ///
  /// The distinction matters in two places, and only those two. A draft has taken
  /// no money and moved no goods, so it does not decrement the cached stock, and
  /// the day-close guard does not refuse to close the day over one — a parked
  /// ticket is not unbanked takings, and treating it as such would leave a cashier
  /// unable to close up because somebody left a quote on the screen.
  ///
  /// Read from the payload, which is the copy that actually posts.
  bool get isDraft => asStr(payload['status']).toLowerCase() == 'draft';

  double get total => asNum((saleJson['summary'] as Map?)?['grand_total']).toDouble();

  String get customerName => asStr((saleJson['customer'] as Map?)?['name']);

  /// Quantity sold per product — what the cached stock figures owe to this sale.
  Map<int, double> get soldQuantities {
    final sold = <int, double>{};
    for (final raw in (payload['items'] as List?) ?? const []) {
      final item = Map<String, dynamic>.from(raw as Map);
      final id = asNum(item['productId']).toInt();
      sold[id] = (sold[id] ?? 0) + asNum(item['quantity']).toDouble();
    }
    return sold;
  }

  /// The same captured sale, corrected.
  ///
  /// Identity is deliberately preserved: [clientUuid] so the server still sees
  /// one sale rather than two, [provisionalRef] so the reference already printed
  /// for the customer still resolves, and [createdAt] so the queue keeps posting
  /// in the order the customers were actually served.
  ///
  /// The attempt counter and last error reset — a corrected sale should be tried
  /// immediately, not made to sit out the backoff its rejected version earned.
  PendingSale replacing({
    required Map<String, dynamic> payload,
    required Map<String, dynamic> saleJson,
  }) =>
      PendingSale(
        clientUuid: clientUuid,
        payload: payload,
        saleJson: saleJson,
        provisionalRef: provisionalRef,
        userId: userId,
        branchId: branchId,
        createdAt: createdAt,
        status: PendingSaleStatus.pending,
        attempts: 0,
      );

  PendingSale copyWith({
    PendingSaleStatus? status,
    int? attempts,
    DateTime? lastAttemptAt,
    String? lastError,
    bool clearError = false,
    String? serverSaleId,
    String? invoiceNo,
  }) =>
      PendingSale(
        clientUuid: clientUuid,
        payload: payload,
        saleJson: saleJson,
        provisionalRef: provisionalRef,
        userId: userId,
        branchId: branchId,
        createdAt: createdAt,
        status: status ?? this.status,
        attempts: attempts ?? this.attempts,
        lastAttemptAt: lastAttemptAt ?? this.lastAttemptAt,
        lastError: clearError ? null : (lastError ?? this.lastError),
        serverSaleId: serverSaleId ?? this.serverSaleId,
        invoiceNo: invoiceNo ?? this.invoiceNo,
      );

  @override
  List<Object?> get props => [
        clientUuid,
        provisionalRef,
        userId,
        branchId,
        createdAt,
        status,
        attempts,
        lastAttemptAt,
        lastError,
        serverSaleId,
        invoiceNo,
      ];
}
