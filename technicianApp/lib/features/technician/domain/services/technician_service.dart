import 'package:invo/shared/api/end_points.dart';
import 'package:invo/shared/domain/constants/global_variables.dart';
import 'package:invo/shared/utils/router/http_utils/http_service.dart';

import '../models/technician_models.dart';
import '../repository/technician_repository.dart';

/// Concrete [TechnicianRepository] — every call goes through the shared
/// [HttpService] (auth token + tenant header applied automatically) and unwraps
/// the `{success,data,message}` envelope. Writes return the re-fetched detail
/// payload (the server reconciles like the web loadData()).
class TechnicianService implements TechnicianRepository {
  HttpService get _http => serviceLocator<HttpService>();

  ComplaintDetail _detail(dynamic data) =>
      ComplaintDetail.fromJson(Map<String, dynamic>.from(data));

  @override
  Future<TechnicianDashboard> dashboard() async {
    final data = await _http.get(EndPoints.technicianDashboard);
    return TechnicianDashboard.fromJson(Map<String, dynamic>.from(data));
  }

  @override
  Future<Map<String, dynamic>> complaints({
    String? status,
    String? search,
    String? fromDate,
    String? toDate,
    int page = 1,
    int perPage = 15,
  }) async {
    final data = await _http.get(EndPoints.technicianComplaints, query: {
      if (status != null && status.isNotEmpty) 'status': status,
      if (search != null && search.isNotEmpty) 'search': search,
      if (fromDate != null) 'from_date': fromDate,
      if (toDate != null) 'to_date': toDate,
      'page': page,
      'per_page': perPage,
    });
    return Map<String, dynamic>.from(data);
  }

  @override
  Future<ComplaintDetail> detail(int id) async {
    final data = await _http.get(EndPoints.technicianComplaint(id));
    return _detail(data);
  }

  @override
  Future<ComplaintDetail> saveRemark(int id, String remark) async {
    final data = await _http.put(EndPoints.technicianComplaint(id),
        body: {'technician_remark': remark});
    return _detail(data);
  }

  @override
  Future<ComplaintDetail> complete(int id, String remark) async {
    final data = await _http.post(EndPoints.technicianComplete(id),
        body: {'technician_remark': remark});
    return _detail(data);
  }

  @override
  Future<ComplaintDetail> addSupplyItem(
    int complaintId, {
    required int branchId,
    int? productId,
    String? barcode,
    String mode = 'New',
    double quantity = 1,
    double? unitPrice,
    String remarks = '',
  }) async {
    final data =
        await _http.post(EndPoints.technicianSupplyItems(complaintId), body: {
      'branch_id': branchId,
      if (productId != null) 'product_id': productId,
      if (barcode != null && barcode.isNotEmpty) 'barcode': barcode,
      'mode': mode,
      'quantity': quantity,
      if (unitPrice != null) 'unit_price': unitPrice,
      'remarks': remarks,
    });
    return _detail(data);
  }

  @override
  Future<ComplaintDetail> updateSupplyItem(
    int itemId, {
    int? branchId,
    String? mode,
    double? quantity,
    double? unitPrice,
    String? remarks,
  }) async {
    final data = await _http.put(EndPoints.technicianSupplyItem(itemId), body: {
      if (branchId != null) 'branch_id': branchId,
      if (mode != null) 'mode': mode,
      if (quantity != null) 'quantity': quantity,
      if (unitPrice != null) 'unit_price': unitPrice,
      if (remarks != null) 'remarks': remarks,
    });
    return _detail(data);
  }

  @override
  Future<ComplaintDetail> deleteSupplyItem(int itemId) async {
    final data = await _http.delete(EndPoints.technicianSupplyItem(itemId));
    return _detail(data);
  }

  @override
  Future<ComplaintDetail> addNote(int complaintId, String note) async {
    final data = await _http
        .post(EndPoints.technicianNotes(complaintId), body: {'note': note});
    return _detail(data);
  }

  @override
  Future<ComplaintDetail> deleteNote(int noteId) async {
    final data = await _http.delete(EndPoints.technicianNote(noteId));
    return _detail(data);
  }

  @override
  Future<ComplaintDetail> addAttachments(
      int complaintId, List<String> paths) async {
    final data = await _http.postFiles(
      EndPoints.technicianAttachments(complaintId),
      files: [for (final p in paths) (field: 'attachments[]', path: p)],
    );
    return _detail(data);
  }

  @override
  Future<ComplaintDetail> deleteAttachment(int imageId) async {
    final data = await _http.delete(EndPoints.technicianAttachment(imageId));
    return _detail(data);
  }

  @override
  Future<List<ProductOption>> products({String? search, int perPage = 30}) async {
    final data = await _http.get(EndPoints.products, query: {
      if (search != null && search.isNotEmpty) 'search': search,
      'per_page': perPage,
    });
    // /products is paginated → { data: [...], pagination: {...} }.
    final rows = data is Map ? (data['data'] as List? ?? const []) : (data as List);
    return rows
        .map((e) => ProductOption.fromJson(Map<String, dynamic>.from(e)))
        .toList();
  }

  @override
  Future<List<BranchOption>> branches() async {
    final data = await _http.get(EndPoints.branches);
    final rows = data is List ? data : (data['data'] as List? ?? const []);
    return rows
        .map((e) => BranchOption.fromJson(Map<String, dynamic>.from(e)))
        .toList();
  }
}
