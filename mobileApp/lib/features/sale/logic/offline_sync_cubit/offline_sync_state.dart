part of 'offline_sync_cubit.dart';

/// What the sync engine is doing right now. Drives the badge, the pending-sales
/// screen and the "catalog from …" chip.
class OfflineSyncState extends Equatable {
  const OfflineSyncState({
    this.status = DataFetchStatus.idle,
    this.rows = const [],
    this.catalogSyncedAt,
    this.catalogRefreshing = false,
    this.lastSyncedCount = 0,
    this.lastSyncedRefs = const [],
    this.errorMessage,
    this.provisionStep,
    this.provisionDone = 0,
    this.provisionTotal = 0,
    this.provisionIncomplete = const [],
    this.catalogTruncated = false,
  });

  final DataFetchStatus status;

  /// Every outbox row, newest first — pending, failed and recently synced.
  final List<PendingSale> rows;

  /// When this branch's catalog snapshot was last refreshed; null when there
  /// has never been one.
  final DateTime? catalogSyncedAt;

  final bool catalogRefreshing;

  /// How many sales the most recent drain got through — used for the
  /// "3 sales synced" confirmation.
  final int lastSyncedCount;

  /// The real invoice numbers those sales became.
  ///
  /// An acknowledged row is deleted from the device the moment the server has it,
  /// so this is the one chance to tell a cashier holding an `OFF-7K2-0042` receipt
  /// which invoice it turned into.
  final List<String> lastSyncedRefs;

  final String? errorMessage;

  /// What the first-run provisioning is fetching right now, e.g. "Products".
  /// Null when nothing is being provisioned.
  final String? provisionStep;

  final int provisionDone;
  final int provisionTotal;

  /// Names of the things provisioning could not fetch. A till that starts
  /// selling against a half-built snapshot believing it is whole is exactly the
  /// failure this list exists to prevent.
  final List<String> provisionIncomplete;

  /// True when the catalog was too large to snapshot in full, so some products
  /// cannot be sold offline. Reported rather than swallowed: a partial snapshot that
  /// looks complete is worse than one that says so.
  final bool catalogTruncated;

  bool get provisioning => provisionStep != null;

  double get provisionProgress =>
      provisionTotal == 0 ? 0 : (provisionDone / provisionTotal).clamp(0, 1);

  bool get draining => status == DataFetchStatus.waiting;

  List<PendingSale> get waiting =>
      rows.where((r) => r.status != PendingSaleStatus.synced).toList();

  List<PendingSale> get failed =>
      rows.where((r) => r.status == PendingSaleStatus.failed).toList();

  /// Sales the server still owes an acknowledgement for. This is the number on
  /// the badge and in the offline strip.
  int get pendingCount => waiting.length;

  bool get hasPending => pendingCount > 0;

  /// Money taken on this device that the server does not have yet.
  ///
  /// What the day-close guard refuses to close over — deliberately narrower than
  /// [pendingCount], which includes parked drafts. A draft is not unbanked
  /// takings, and blocking a cashier from closing up because someone left a quote
  /// on a screen would teach them to force past the guard that protects the money.
  List<PendingSale> get unbankedTakings => waiting.where((r) => !r.isDraft).toList();

  /// How many of them — what the day-close refusal counts.
  int get unbankedCount => unbankedTakings.length;

  bool get hasUnbankedTakings => unbankedCount > 0;

  bool get hasCatalog => catalogSyncedAt != null;

  /// How old this branch's snapshot is, or null when there has never been one.
  Duration? get catalogAge =>
      catalogSyncedAt == null ? null : DateTime.now().difference(catalogSyncedAt!);

  /// Drives the escalating staleness warning. Selling is never blocked on it —
  /// see [CatalogFreshness].
  CatalogFreshness get catalogFreshness => CatalogFreshness.of(catalogAge);

  OfflineSyncState copyWith({
    DataFetchStatus? status,
    List<PendingSale>? rows,
    DateTime? catalogSyncedAt,
    bool? catalogRefreshing,
    int? lastSyncedCount,
    List<String>? lastSyncedRefs,
    String? errorMessage,
    bool clearError = false,
    String? provisionStep,
    bool clearProvisionStep = false,
    int? provisionDone,
    int? provisionTotal,
    List<String>? provisionIncomplete,
    bool? catalogTruncated,
  }) =>
      OfflineSyncState(
        status: status ?? this.status,
        rows: rows ?? this.rows,
        catalogSyncedAt: catalogSyncedAt ?? this.catalogSyncedAt,
        catalogRefreshing: catalogRefreshing ?? this.catalogRefreshing,
        lastSyncedCount: lastSyncedCount ?? this.lastSyncedCount,
        lastSyncedRefs: lastSyncedRefs ?? this.lastSyncedRefs,
        errorMessage: clearError ? null : (errorMessage ?? this.errorMessage),
        provisionStep: clearProvisionStep ? null : (provisionStep ?? this.provisionStep),
        provisionDone: provisionDone ?? this.provisionDone,
        provisionTotal: provisionTotal ?? this.provisionTotal,
        provisionIncomplete: provisionIncomplete ?? this.provisionIncomplete,
        catalogTruncated: catalogTruncated ?? this.catalogTruncated,
      );

  @override
  List<Object?> get props => [
        status,
        rows,
        catalogSyncedAt,
        catalogRefreshing,
        lastSyncedCount,
        lastSyncedRefs,
        errorMessage,
        provisionStep,
        provisionDone,
        provisionTotal,
        provisionIncomplete,
        catalogTruncated,
      ];
}
