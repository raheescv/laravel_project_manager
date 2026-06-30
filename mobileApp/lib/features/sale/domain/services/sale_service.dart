import 'dart:typed_data';

import 'package:invo/shared/api/end_points.dart';
import 'package:invo/shared/domain/constants/global_variables.dart';
import 'package:invo/shared/domain/helpers/formatters.dart';
import 'package:invo/shared/domain/models/index.dart';
import 'package:invo/shared/utils/router/http_utils/http_service.dart';

import '../repository/sale_repository.dart';

class SaleService implements SaleRepository {
  HttpService get _http => serviceLocator<HttpService>();

  @override
  Future<Sale> createSale(Map<String, dynamic> payload) async {
    final data = await _http.post(EndPoints.sale, body: payload);
    return Sale.fromJson(Map<String, dynamic>.from(data));
  }

  @override
  Future<SalesPage> sales({
    String? status,
    String? search,
    int? paymentMethodId,
    String? fromDate,
    String? toDate,
    String sortBy = 'date',
    String sortDirection = 'desc',
    bool mineOnly = false,
    int page = 1,
    int perPage = 30,
  }) async {
    final data = await _http.get(EndPoints.sale, query: {
      if (status != null) 'status': status,
      if (search != null && search.isNotEmpty) 'search': search,
      if (paymentMethodId != null) 'payment_method_id': paymentMethodId,
      if (fromDate != null) 'from_date': fromDate,
      if (toDate != null) 'to_date': toDate,
      'sort_by': sortBy,
      'sort_direction': sortDirection,
      'mine_only': mineOnly,
      'page': page,
      'per_page': perPage,
    });
    final list = (data is Map ? data['data'] : data) as List? ?? const [];
    final summary = (data is Map ? data['summary'] : null) as Map? ?? const {};
    final pag = (data is Map ? data['pagination'] : null) as Map? ?? const {};
    return SalesPage(
      rows: list.map((e) => Map<String, dynamic>.from(e)).toList(),
      total: asNum(summary['invoices'] ?? pag['total'] ?? list.length).toInt(),
      totalPaid: asNum(summary['total_paid']).toDouble(),
      currentPage: asNum(pag['current_page'] ?? 1).toInt(),
      lastPage: asNum(pag['last_page'] ?? 1).toInt(),
    );
  }

  @override
  Future<Sale> saleById(String id) async {
    final data = await _http.get(EndPoints.saleById(id));
    return Sale.fromJson(Map<String, dynamic>.from(data));
  }

  @override
  Future<Uint8List> saleReceiptPdf(String id) =>
      _http.getBytes(EndPoints.saleReceipt(id));

  @override
  Future<Sale> updateSale(String id, Map<String, dynamic> payload) async {
    final data = await _http.put(EndPoints.saleById(id), body: payload);
    return Sale.fromJson(Map<String, dynamic>.from(data));
  }
}
