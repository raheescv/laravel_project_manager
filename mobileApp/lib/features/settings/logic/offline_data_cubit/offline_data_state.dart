part of 'offline_data_cubit.dart';

/// What this branch actually holds on the device, read straight off the
/// snapshot rather than inferred from what the last sync reported.
///
/// The distinction matters: [OfflineSyncState] describes the last *run*, and is
/// empty after a restart even when the device is fully stocked. This describes
/// the stock itself, which is the question the screen is answering.
class OfflineDataState extends Equatable {
  const OfflineDataState({
    this.status = DataFetchStatus.idle,
    this.products = 0,
    this.categories = 0,
    this.paymentMethods = 0,
    this.employees = 0,
    this.customers = 0,
    this.photoFiles = 0,
    this.photoBytes = 0,
    this.syncedAt,
    this.clearing = false,
  });

  final DataFetchStatus status;

  final int products;
  final int categories;
  final int paymentMethods;
  final int employees;
  final int customers;

  /// Product photographs held on disk, and what they occupy.
  final int photoFiles;
  final int photoBytes;

  final DateTime? syncedAt;

  /// A wipe is running. Held here rather than as a screen-local flag so the
  /// buttons that must not be pressed twice are disabled by state, not by a
  /// `setState` that a rebuild could lose.
  final bool clearing;

  bool get loading => status == DataFetchStatus.waiting;

  /// Nothing has ever been downloaded for this branch. The screen leads with
  /// this, because every count below it being zero is a symptom, not the fact.
  bool get isEmpty => syncedAt == null && products == 0;

  String get photoSizeLabel => ImageCacheStats(files: photoFiles, bytes: photoBytes).sizeLabel;

  OfflineDataState copyWith({
    DataFetchStatus? status,
    int? products,
    int? categories,
    int? paymentMethods,
    int? employees,
    int? customers,
    int? photoFiles,
    int? photoBytes,
    DateTime? syncedAt,
    bool clearSyncedAt = false,
    bool? clearing,
  }) =>
      OfflineDataState(
        status: status ?? this.status,
        products: products ?? this.products,
        categories: categories ?? this.categories,
        paymentMethods: paymentMethods ?? this.paymentMethods,
        employees: employees ?? this.employees,
        customers: customers ?? this.customers,
        photoFiles: photoFiles ?? this.photoFiles,
        photoBytes: photoBytes ?? this.photoBytes,
        syncedAt: clearSyncedAt ? null : (syncedAt ?? this.syncedAt),
        clearing: clearing ?? this.clearing,
      );

  @override
  List<Object?> get props => [
        status,
        products,
        categories,
        paymentMethods,
        employees,
        customers,
        photoFiles,
        photoBytes,
        syncedAt,
        clearing,
      ];
}
