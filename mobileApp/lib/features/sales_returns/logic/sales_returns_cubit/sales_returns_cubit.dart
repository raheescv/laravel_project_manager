import 'package:invo/features/sale_return/domain/repository/sale_return_repository.dart';
import 'package:invo/shared/domain/constants/global_variables.dart';
import 'package:invo/shared/domain/models/index.dart';
import 'package:invo/shared/domain/repository/lookup_repository.dart';
import 'package:invo/shared/logic/paginated_list_cubit/paginated_list_cubit.dart';

/// Owns the repositories the Sales Returns list needs, so the screen never
/// resolves one itself (§10). Mirrors `SalesCubit`.
class SalesReturnsCubit {
  SalesReturnsCubit({SaleReturnRepository? returns, LookupRepository? lookup})
      : _returnsOverride = returns,
        _lookupOverride = lookup;

  final SaleReturnRepository? _returnsOverride;
  final LookupRepository? _lookupOverride;

  // Resolved on first use — see the note on SalesCubit.
  SaleReturnRepository get _returns =>
      _returnsOverride ?? serviceLocator<SaleReturnRepository>();
  LookupRepository get _lookup => _lookupOverride ?? serviceLocator<LookupRepository>();

  Future<PageResult> fetchPage({
    required int page,
    String? status,
    int? paymentMethodId,
    String? fromDate,
    String? toDate,
    String sortBy = 'date',
    String sortDirection = 'desc',
  }) async {
    final res = await _returns.saleReturns(
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

  Future<SaleReturn> saleReturnById(String id) => _returns.saleReturnById(id);

  Future<List<PaymentMethod>> paymentMethods() => _lookup.paymentMethods();
}
