import 'package:invo/features/sale/domain/repository/sale_repository.dart';
import 'package:invo/shared/domain/constants/global_variables.dart';
import 'package:invo/shared/domain/models/index.dart';
import 'package:invo/shared/domain/repository/lookup_repository.dart';
import 'package:invo/shared/logic/paginated_list_cubit/paginated_list_cubit.dart';

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
  Future<PageResult> fetchPage({
    required int page,
    String? status,
    int? paymentMethodId,
    String? fromDate,
    String? toDate,
    String sortBy = 'date',
    String sortDirection = 'desc',
  }) async {
    final res = await _sales.sales(
      status: status,
      paymentMethodId: paymentMethodId,
      fromDate: fromDate,
      toDate: toDate,
      sortBy: sortBy,
      sortDirection: sortDirection,
      page: page,
    );
    return PageResult(
      rows: res.rows,
      currentPage: res.currentPage,
      lastPage: res.lastPage,
      total: res.total,
      totalPaid: res.totalPaid,
    );
  }

  /// Full invoice for the tablet detail pane.
  Future<Sale> saleById(String id) => _sales.saleById(id);

  Future<void> deleteSale(String id) => _sales.deleteSale(id);

  /// Powers the in-card payment filter; a failure just leaves it on "All".
  Future<List<PaymentMethod>> paymentMethods() => _lookup.paymentMethods();
}
