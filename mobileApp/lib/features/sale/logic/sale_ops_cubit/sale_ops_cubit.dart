import 'package:invo/features/sale/domain/repository/sale_repository.dart';
import 'package:invo/features/sale_return/domain/repository/sale_return_repository.dart';
import 'package:invo/shared/domain/constants/global_variables.dart';
import 'package:invo/shared/domain/models/index.dart';
import 'package:invo/shared/domain/repository/lookup_repository.dart';

/// Repository access for the sale flow's screens (Review & Pay, Invoice).
///
/// [CartCubit] owns the *ticket*; this owns the *calls* the screens around it
/// make, so no screen resolves a repository itself (§10). Errors are left to
/// propagate: these screens already surface `ApiException.message` inline, and
/// a decline on Charge must not be swallowed.
class SaleOpsCubit {
  SaleOpsCubit({
    SaleRepository? sales,
    SaleReturnRepository? returns,
    LookupRepository? lookup,
  })  : _salesOverride = sales,
        _returnsOverride = returns,
        _lookupOverride = lookup;

  final SaleRepository? _salesOverride;
  final SaleReturnRepository? _returnsOverride;
  final LookupRepository? _lookupOverride;

  // Resolved on first use, not in the constructor: a screen that only charges
  // a sale should not require the return repository to be registered.
  SaleRepository get _sales => _salesOverride ?? serviceLocator<SaleRepository>();
  SaleReturnRepository get _returns =>
      _returnsOverride ?? serviceLocator<SaleReturnRepository>();
  LookupRepository get _lookup => _lookupOverride ?? serviceLocator<LookupRepository>();

  /// Configured split-payment methods for the Custom payment sheet.
  Future<List<PaymentMethod>> paymentMethods() => _lookup.paymentMethods();

  Future<Sale> createSale(Map<String, dynamic> payload) => _sales.createSale(payload);

  Future<Sale> updateSale(String id, Map<String, dynamic> payload) =>
      _sales.updateSale(id, payload);

  Future<void> deleteSale(String id) => _sales.deleteSale(id);

  /// Returnable lines for this sale — drives "Return" on the invoice.
  Future<ReturnableSale> returnableSale(String saleId) => _returns.returnableSale(saleId);
}
