import 'package:invo/features/auth/logic/auth_cubit/auth_cubit.dart';
import 'package:invo/features/sale/domain/models/pending_sale.dart';
import 'package:invo/features/sale/domain/repository/outbox_repository.dart';
import 'package:invo/features/sale/domain/repository/sale_repository.dart';
import 'package:invo/shared/domain/constants/global_variables.dart';
import 'package:invo/shared/domain/helpers/formatters.dart';
import 'package:invo/shared/domain/models/index.dart';
import 'package:invo/shared/domain/repository/lookup_repository.dart';
import 'package:invo/shared/logic/paginated_list_cubit/paginated_list_cubit.dart';
import 'package:invo/shared/utils/router/http_utils/reachability.dart';

/// Owns the repositories the Sales list needs, so the screen never resolves one
/// itself (§10: Widget → Cubit → Repository).
///
/// Pagination state lives in the shared [PaginatedListCubit]; this holds the
/// data access — the page fetcher it is driven by, plus the detail load, the
/// payment-method lookup and the delete the list offers.
class SalesCubit {
  SalesCubit({SaleRepository? sales, LookupRepository? lookup})
      : _salesOverride = sales,
        _lookupOverride = lookup;

  final SaleRepository? _salesOverride;
  final LookupRepository? _lookupOverride;

  // Resolved on first use, not in the constructor, so a screen only needs the
  // repositories it actually calls to be registered.
  SaleRepository get _sales => _salesOverride ?? serviceLocator<SaleRepository>();
  LookupRepository get _lookup => _lookupOverride ?? serviceLocator<LookupRepository>();

  /// One page of the filtered list, adapted to the shared cubit's shape.
  ///
  /// Sales this device is still holding are shown at the top of the first page, and
  /// are the ONLY thing shown when the server cannot be reached. That is the point:
  /// a cashier who has just rung something up offline looks for it in Sales, and a
  /// list that omits it reads as a lost sale. From there the invoice screen opens it
  /// and Edit corrects the queued row — see `OfflineSyncCubit.editPending`.
  Future<PageResult> fetchPage({
    required int page,
    String? status,
    String? search,
    int? paymentMethodId,
    String? fromDate,
    String? toDate,
    String sortBy = 'date',
    String sortDirection = 'desc',
    int? staffId,
  }) async {
    // Only the first page carries them: they are a short, bounded set, and repeating
    // them on page two would show the same sale twice.
    final held = page == 1
        ? await _heldSales(
            status: status,
            search: search,
            paymentMethodId: paymentMethodId,
            fromDate: fromDate,
            toDate: toDate,
            staffId: staffId,
          )
        : const <Map<String, dynamic>>[];

    try {
      final res = await _sales.sales(
        status: status,
        search: search,
        paymentMethodId: paymentMethodId,
        fromDate: fromDate,
        toDate: toDate,
        sortBy: sortBy,
        sortDirection: sortDirection,
        createdById: staffId,
        page: page,
      );
      return PageResult(
        rows: [...held, ...res.rows],
        currentPage: res.currentPage,
        lastPage: res.lastPage,
        total: res.total + held.length,
        totalPaid: res.totalPaid,
      );
    } catch (e) {
      // Only an unreachable server falls back to the held rows alone. A server that
      // answered gave a real verdict, and replacing it with a two-row list would
      // hide it — and would read as "these are all your sales", which is a lie.
      if (!isServerUnreachable(e)) rethrow;
      if (page > 1) {
        return const PageResult(rows: [], currentPage: 1, lastPage: 1, total: 0);
      }
      return PageResult(rows: held, currentPage: 1, lastPage: 1, total: held.length);
    }
  }

  /// The outbox rows, in the shape the list renders, newest first.
  ///
  /// Each carries `pending: true` and its provisional reference in place of an
  /// invoice number, which is what makes the invoice screen treat it as held rather
  /// than committed — hiding Return and Delete, and pointing Edit at the queue.
  Future<List<Map<String, dynamic>>> _heldSales({
    String? status,
    String? search,
    int? paymentMethodId,
    String? fromDate,
    String? toDate,
    int? staffId,
  }) async {
    if (!serviceLocator.isRegistered<OutboxRepository>()) return const [];
    // A held sale has no payment-method id yet — the server assigns those — so any
    // payment filter necessarily excludes it rather than silently ignoring the
    // filter.
    if (paymentMethodId != null) return const [];

    try {
      final rows = await serviceLocator<OutboxRepository>().all();
      return [
        for (final row in rows)
          if (row.status != PendingSaleStatus.synced &&
              _visibleTo(row, staffId) &&
              _matchesStatus(row, status) &&
              _matchesSearch(row, search) &&
              _withinRange(row, fromDate: fromDate, toDate: toDate))
            // `reference_no` carries the provisional reference for the same reason
            // the server stores it there once the sale syncs, so a held row and the
            // committed row it becomes describe themselves identically.
            {
              ...row.saleJson,
              'invoice_no': row.displayRef,
              'reference_no': row.provisionalRef,
              'pending': true,
            },
      ];
    } catch (_) {
      // The list is still perfectly usable without them.
      return const [];
    }
  }

  /// Whether the signed-in user may see this held row, and whether it survives
  /// the staff filter.
  ///
  /// A shared till queues every cashier's sales into one outbox, and offline the
  /// server's own scoping (`App\Actions\V1\Sale\ListAction`) cannot run — so the
  /// same rule is applied here: a non-admin employee sees only what they rang up.
  /// Without this, going offline would show a cashier their colleagues' takings,
  /// which is exactly what the online list refuses them.
  ///
  /// A row with no attribution stays visible to everyone. That only happens when
  /// the capture could not resolve the user, and a sale the till has taken money
  /// for must not vanish from the one list that can show it.
  bool _visibleTo(PendingSale row, int? staffId) {
    if (row.userId.isEmpty) return true;
    if (staffId != null && row.userId != '$staffId') return false;
    final user = _user;
    if (user == null || !user.isNonAdminEmployee) return true;
    return row.userId == user.id;
  }

  /// Resolved leniently: a screen that never registers [AuthCubit] (a test, a
  /// preview) still gets its list rather than an exception.
  ApiUser? get _user =>
      serviceLocator.isRegistered<AuthCubit>() ? serviceLocator<AuthCubit>().user : null;

  bool _matchesStatus(PendingSale row, String? status) {
    if (status == null || status.isEmpty) return true;
    // A payload with no status is a completed sale — that is what the server
    // defaults it to, and the filter has to agree with the server.
    final rowStatus = asStr(row.payload['status']);
    return (rowStatus.isEmpty ? 'completed' : rowStatus.toLowerCase()) == status.toLowerCase();
  }

  /// Search a held row the way the server searches a committed one: its reference
  /// and its customer.
  ///
  /// Matched locally because there is nothing to ask — the row has never left the
  /// device. The provisional reference is included deliberately: `OFF-7K2-0042` is
  /// what the cashier is holding a printout of, so it is what they will type.
  bool _matchesSearch(PendingSale row, String? search) {
    final term = (search ?? '').trim().toLowerCase();
    if (term.isEmpty) return true;
    final customer = (row.saleJson['customer'] as Map?) ?? const {};
    return [
      row.displayRef,
      row.provisionalRef,
      asStr(customer['name']),
      asStr(customer['mobile']),
    ].any((field) => field.toLowerCase().contains(term));
  }

  /// A held sale is dated by the till clock that captured it, so a date filter is
  /// applied against that rather than against a server column it does not have yet.
  bool _withinRange(PendingSale row, {String? fromDate, String? toDate}) {
    final date = asStr(row.saleJson['date']);
    if (date.isEmpty) return true;
    if (fromDate != null && fromDate.isNotEmpty && date.compareTo(fromDate) < 0) return false;
    if (toDate != null && toDate.isNotEmpty && date.compareTo(toDate) > 0) return false;
    return true;
  }

  /// Full invoice for the tablet detail pane.
  Future<Sale> saleById(String id) => _sales.saleById(id);

  Future<void> deleteSale(String id) => _sales.deleteSale(id);

  /// Powers the in-card payment filter; a failure just leaves it on "All".
  Future<List<PaymentMethod>> paymentMethods() => _lookup.paymentMethods();
}
