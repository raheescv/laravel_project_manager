import 'package:invo/shared/domain/models/index.dart';

abstract class SaleReturnRepository {
  Future<SaleReturn> createSaleReturn(Map<String, dynamic> payload);

  Future<SaleReturnsPage> saleReturns({
    String? status,
    int? paymentMethodId,
    String? fromDate,
    String? toDate,
    String sortBy,
    String sortDirection,
    bool mineOnly,
    int page,
    int perPage,
  });

  Future<SaleReturn> saleReturnById(String id);

  Future<SaleReturn> updateSaleReturn(String id, Map<String, dynamic> payload);

  Future<ReturnableSale> returnableSale(String saleId);
}
