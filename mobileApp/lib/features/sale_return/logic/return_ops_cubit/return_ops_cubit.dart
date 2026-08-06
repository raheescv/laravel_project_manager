import 'package:invo/features/sale/domain/repository/sale_repository.dart';
import 'package:invo/features/sale_return/domain/repository/sale_return_repository.dart';
import 'package:invo/shared/domain/constants/global_variables.dart';
import 'package:invo/shared/domain/models/index.dart';
import 'package:invo/shared/domain/repository/lookup_repository.dart';
import 'package:invo/shared/logic/paginated_list_cubit/paginated_list_cubit.dart';

/// Repository access for the sale-return flow's screens (pick invoice, review,
/// receipt). [ReturnDraftCubit] owns the draft; this owns the calls (§10).
class ReturnOpsCubit {
  ReturnOpsCubit({
    SaleReturnRepository? returns,
    SaleRepository? sales,
    LookupRepository? lookup,
  })  : _returnsOverride = returns,
        _salesOverride = sales,
        _lookupOverride = lookup;

  final SaleReturnRepository? _returnsOverride;
  final SaleRepository? _salesOverride;
  final LookupRepository? _lookupOverride;

  // Resolved on first use — see the note on SaleOpsCubit.
  SaleReturnRepository get _returns =>
      _returnsOverride ?? serviceLocator<SaleReturnRepository>();
  SaleRepository get _sales => _salesOverride ?? serviceLocator<SaleRepository>();
  LookupRepository get _lookup => _lookupOverride ?? serviceLocator<LookupRepository>();

  Future<List<PaymentMethod>> paymentMethods() => _lookup.paymentMethods();

  Future<ReturnableSale> returnableSale(String saleId) => _returns.returnableSale(saleId);

  Future<SaleReturn> createSaleReturn(Map<String, dynamic> payload) =>
      _returns.createSaleReturn(payload);

  Future<SaleReturn> updateSaleReturn(String id, Map<String, dynamic> payload) =>
      _returns.updateSaleReturn(id, payload);

  /// Paid invoices to return against — step 1 of the wizard.
  Future<PageResult> fetchPaidInvoices({
    required int page,
    String? search,
    String? fromDate,
    String? toDate,
  }) async {
    final res = await _sales.sales(
      status: 'completed',
      search: search,
      fromDate: fromDate,
      toDate: toDate,
      page: page,
    );
    return PageResult(
      rows: res.rows,
      currentPage: res.currentPage,
      lastPage: res.lastPage,
      total: res.total,
    );
  }
}
