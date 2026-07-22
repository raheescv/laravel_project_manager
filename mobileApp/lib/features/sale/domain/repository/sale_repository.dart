import 'dart:typed_data';

import 'package:invo/shared/domain/models/index.dart';

abstract class SaleRepository {
  Future<Sale> createSale(Map<String, dynamic> payload);

  Future<SalesPage> sales({
    String? status,
    String? search,
    int? paymentMethodId,
    String? fromDate,
    String? toDate,
    String sortBy,
    String sortDirection,
    bool mineOnly,
    int page,
    int perPage,
  });

  Future<Sale> saleById(String id);

  Future<Uint8List> saleReceiptPdf(String id);

  Future<Sale> updateSale(String id, Map<String, dynamic> payload);

  /// Permanently delete a sale (with its items and payments). Throws an
  /// [ApiException] the caller can surface when the server refuses — e.g. a
  /// completed sale, which can't be deleted.
  Future<void> deleteSale(String id);
}
