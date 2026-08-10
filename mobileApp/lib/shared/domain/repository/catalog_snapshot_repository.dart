import '../models/index.dart';

/// The reference lists cached alongside the catalog. Each is small, and each is
/// something a cashier cannot ring a sale without.
enum SnapshotLookup {
  /// Configured split-payment accounts — without them the Custom sheet is empty
  /// and only Cash/Card/Credit remain.
  paymentMethod,

  /// Staff, for assigning a stylist to the ticket or a line.
  employee,

  /// Known customers, so a returning client can be found by phone rather than
  /// retyped as a new one (which would create a duplicate account on sync).
  customer,
}

/// When a branch's catalog snapshot was taken, and how much of it there is.
class CatalogSnapshotMeta {
  const CatalogSnapshotMeta({required this.syncedAt, required this.productCount});

  final DateTime syncedAt;
  final int productCount;

  Duration get age => DateTime.now().difference(syncedAt);

  CatalogFreshness get freshness => CatalogFreshness.of(age);
}

/// How far to trust a cached catalog, by age.
///
/// Selling from a stale snapshot is deliberately **never blocked**. Refusing to
/// trade spends certain revenue to avoid a possible price error, and a till that
/// cannot sell during an outage is the exact failure this whole feature exists to
/// prevent. What the age changes is how loudly the screen says so — and past the
/// second threshold it says it in a way that is hard to keep working through.
enum CatalogFreshness {
  /// The routine case: recent enough that no extra warning is earned. Saying
  /// "offline" is already on screen and enough.
  fresh,

  /// Old enough that a price or a stock figure could plausibly have moved since.
  aging,

  /// Old enough that someone should go and get the till reconnected.
  stale;

  /// Past half a day the figures may well have moved on the web.
  static const Duration agingAfter = Duration(hours: 12);

  /// Past a full day, say so plainly and keep saying it.
  static const Duration staleAfter = Duration(hours: 24);

  /// A null age means nothing has ever been snapshotted, which is not staleness —
  /// the provisioning strip owns that case and would otherwise say it twice.
  static CatalogFreshness of(Duration? age) {
    if (age == null) return CatalogFreshness.fresh;
    if (age >= staleAfter) return CatalogFreshness.stale;
    if (age >= agingAfter) return CatalogFreshness.aging;
    return CatalogFreshness.fresh;
  }

  bool get needsSaying => this != CatalogFreshness.fresh;
}

/// "12 min ago" / "6h ago" / "3d ago" — the one place snapshot age is worded, so
/// the offline strip and the catalog chip never disagree about how old it is.
String catalogAgeLabel(DateTime? syncedAt) {
  if (syncedAt == null) return 'an earlier session';
  final age = DateTime.now().difference(syncedAt);
  if (age.inMinutes < 60) return '${age.inMinutes} min ago';
  if (age.inHours < 24) return '${age.inHours}h ago';
  return '${age.inDays}d ago';
}

/// The locally cached, branch-scoped catalog that keeps the New Sale screen
/// usable with no network.
///
/// It answers the same three questions [LookupRepository] answers online —
/// paged products, categories, barcode lookup — so `CatalogCubit` can fall back
/// to it without changing how it renders anything.
abstract class CatalogSnapshotRepository {
  /// Replace this branch's entire snapshot in one transaction. Partial writes
  /// are never visible: either the whole new catalog lands or the previous one
  /// stays intact, so a refresh interrupted halfway can't leave a half-catalog
  /// that looks complete.
  ///
  /// [products] are the raw product JSON maps exactly as the server sent them.
  /// [categoriesByType] is keyed by the type filter the list belongs to — `''`
  /// for "All Types", otherwise `product` / `service`.
  Future<void> replace({
    required int branchId,
    required List<Map<String, dynamic>> products,
    required Map<String, List<Category>> categoriesByType,
  });

  Future<CatalogSnapshotMeta?> meta(int branchId);

  Future<Paginated<Product>> products({
    required int branchId,
    String? search,
    int? mainCategoryId,
    String? type,
    int page = 1,
    int perPage = 20,
  });

  Future<Product?> productByBarcode({required int branchId, required String barcode});

  Future<List<Category>> categories({required int branchId, String? type});

  /// Replace one branch's copy of a reference list. [rows] are the server's own
  /// JSON maps; [kind] is one of [SnapshotLookup] values.
  ///
  /// Skipped entirely when [rows] is empty and something was already stored:
  /// an empty page is far more likely to be a failed fetch than a business with
  /// no staff, and wiping the list would leave the till unable to assign one.
  Future<void> replaceLookups({
    required int branchId,
    required SnapshotLookup kind,
    required List<Map<String, dynamic>> rows,
  });

  Future<List<PaymentMethod>> paymentMethods({required int branchId});

  Future<List<Employee>> employees({required int branchId, String? search});

  Future<List<Customer>> customers({required int branchId, String? mobile, String? search});

  /// How many rows are held for [kind] — drives the provisioning readout.
  Future<int> lookupCount({required int branchId, required SnapshotLookup kind});

  /// Reduce the cached stock figures by what pending offline sales have already
  /// taken off the shelf, so a second ticket sees a realistic number instead of
  /// the figure from the last successful refresh.
  Future<void> reduceStock({required int branchId, required Map<int, double> soldByProductId});

  /// Put cached stock back. The mirror of [reduceStock], for the one thing that
  /// un-sells goods while still offline: correcting a queued sale, whose original
  /// quantities have to be given back before the edited ones are taken, or the
  /// cached figure drifts down on every edit.
  Future<void> restoreStock({required int branchId, required Map<int, double> byProductId});

  /// Drop every cached catalog row (sign-out).
  Future<void> clear();
}
