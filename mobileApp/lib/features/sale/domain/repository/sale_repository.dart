import 'dart:typed_data';

import 'package:invo/shared/domain/models/index.dart';

abstract class SaleRepository {
  /// Post a completed (or draft) sale.
  ///
  /// [offlineSale] is the same ticket in `SaleResource` shape. Supplying it is
  /// how a caller opts into offline capture: if the server cannot be reached,
  /// the sale is queued and this snapshot is what the invoice screen and the
  /// receipt render from. Omit it and an unreachable server is an error, which
  /// is what drafts and edits want — neither can be replayed safely.
  Future<Sale> createSale(Map<String, dynamic> payload, {Map<String, dynamic>? offlineSale});

  Future<SalesPage> sales({
    String? status,
    String? search,
    int? paymentMethodId,
    String? fromDate,
    String? toDate,
    String sortBy,
    String sortDirection,
    bool mineOnly,
    int? createdById,
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
