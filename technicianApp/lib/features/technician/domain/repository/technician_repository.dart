import '../models/technician_models.dart';

/// The technician data contract. Cubits depend on this abstract type; the
/// concrete [TechnicianService] (registered in the service locator) fulfils it
/// against the `/api/v1/technician` endpoints.
abstract class TechnicianRepository {
  Future<TechnicianDashboard> dashboard();

  /// A page of assigned complaints plus pagination metadata (the raw map keeps
  /// the pagination block the cubit needs for infinite scroll).
  Future<Map<String, dynamic>> complaints({
    String? status,
    String? search,
    String? fromDate,
    String? toDate,
    int page,
    int perPage,
  });

  Future<ComplaintDetail> detail(int id);

  Future<ComplaintDetail> saveRemark(int id, String remark);

  Future<ComplaintDetail> complete(int id, String remark);

  /// Add a supply item. Provide [productId] OR [barcode] (barcode resolves the
  /// product server-side, mirroring the web). Omit [unitPrice] to let the server
  /// default it to the product cost.
  Future<ComplaintDetail> addSupplyItem(
    int complaintId, {
    required int branchId,
    int? productId,
    String? barcode,
    String mode,
    double quantity,
    double? unitPrice,
    String remarks,
  });

  Future<ComplaintDetail> updateSupplyItem(
    int itemId, {
    int? branchId,
    String? mode,
    double? quantity,
    double? unitPrice,
    String? remarks,
  });

  Future<ComplaintDetail> deleteSupplyItem(int itemId);

  Future<ComplaintDetail> addNote(int complaintId, String note);

  Future<ComplaintDetail> deleteNote(int noteId);

  /// Upload one or more local files (image/video/pdf/doc) as attachments.
  Future<ComplaintDetail> addAttachments(int complaintId, List<String> paths);

  Future<ComplaintDetail> deleteAttachment(int imageId);

  /// Product picker (search-as-you-type).
  Future<List<ProductOption>> products({String? search, int perPage});

  /// Store / branch picker.
  Future<List<BranchOption>> branches();
}
