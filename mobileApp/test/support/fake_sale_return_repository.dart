import 'package:invo/features/sale_return/domain/repository/sale_return_repository.dart';
import 'package:invo/shared/domain/models/index.dart';

/// A minimal [SaleReturnRepository] returning empty/canned data for tests.
class FakeSaleReturnRepository implements SaleReturnRepository {
  /// What saleReturns() lists — empty unless a test seeds it.
  List<Map<String, dynamic>> rows = const [];

  @override
  Future<SaleReturn> createSaleReturn(Map<String, dynamic> payload) async =>
      SaleReturn.fromJson(const {});

  @override
  Future<SaleReturnsPage> saleReturns({
    String? status,
    int? paymentMethodId,
    String? fromDate,
    String? toDate,
    String sortBy = 'date',
    String sortDirection = 'desc',
    bool mineOnly = false,
    int page = 1,
    int perPage = 30,
  }) async =>
      SaleReturnsPage(
        rows: rows,
        total: rows.length,
        totalPaid: rows.fold(0.0, (a, r) => a + ((r['summary'] as Map?)?['paid'] as num? ?? 0)),
      );

  @override
  Future<SaleReturn> saleReturnById(String id) async => SaleReturn.fromJson(const {});

  @override
  Future<SaleReturn> updateSaleReturn(String id, Map<String, dynamic> payload) async =>
      SaleReturn.fromJson(const {});

  @override
  Future<ReturnableSale> returnableSale(String saleId) async =>
      ReturnableSale.fromJson(const {});
}
