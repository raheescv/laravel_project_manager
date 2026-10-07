import 'dart:typed_data';

import 'package:invo/shared/api/end_points.dart';
import 'package:invo/shared/domain/constants/global_variables.dart';
import 'package:invo/shared/utils/router/http_utils/http_service.dart';

import '../models/checklist_models.dart';
import '../repository/checklist_repository.dart';

/// Concrete [ChecklistRepository] over the shared [HttpService] (auth token,
/// tenant header and envelope unwrapping applied there).
class ChecklistService implements ChecklistRepository {
  HttpService get _http => serviceLocator<HttpService>();

  ChecklistDetail _detail(dynamic data) =>
      ChecklistDetail.fromJson(Map<String, dynamic>.from(data as Map? ?? const {}));

  @override
  Future<List<ChecklistJob>> jobs({
    String? search,
    String? phase,
    String? status,
    String? dateBasis,
    String? fromDate,
    String? toDate,
  }) async {
    final data = await _http.get(EndPoints.checklists, query: {
      if (search != null && search.isNotEmpty) 'search': search,
      if (phase != null && phase.isNotEmpty) 'phase': phase,
      if (status != null && status.isNotEmpty) 'status': status,
      if (dateBasis != null && dateBasis.isNotEmpty) 'date_basis': dateBasis,
      if (fromDate != null && fromDate.isNotEmpty) 'from_date': fromDate,
      if (toDate != null && toDate.isNotEmpty) 'to_date': toDate,
    });
    return (data as List<dynamic>? ?? const [])
        .whereType<Map>()
        .map((e) => ChecklistJob.fromJson(Map<String, dynamic>.from(e)))
        .toList();
  }

  @override
  Future<ChecklistDetail> detail(int id, {String? phase}) async {
    final data = await _http.get(EndPoints.checklist(id), query: {
      if (phase != null && phase.isNotEmpty) 'phase': phase,
    });
    return _detail(data);
  }

  @override
  Future<ChecklistDetail> updateLine(int id, int lineId,
      {required String phase, required Map<String, dynamic> changes}) async {
    final data = await _http.patch(EndPoints.checklistLine(id, lineId), body: {'phase': phase, ...changes});
    return _detail(data);
  }

  @override
  Future<ChecklistDetail> uploadLinePhoto(int id, int lineId,
      {required String phase, required String filePath}) async {
    final data = await _http.postFiles(
      EndPoints.checklistLinePhoto(id, lineId),
      fields: {'phase': phase},
      files: [(field: 'photo', path: filePath)],
    );
    return _detail(data);
  }

  @override
  Future<ChecklistDetail> markOk(int id, {required String phase, required List<int> lineIds}) async {
    final data = await _http.post(EndPoints.checklistMarkOk(id), body: {'phase': phase, 'line_ids': lineIds});
    return _detail(data);
  }

  @override
  Future<ChecklistDetail> sign(int id,
      {required String phase, required String role, String? signerName, required String signature}) async {
    final data = await _http.post(EndPoints.checklistSignatures(id), body: {
      'phase': phase,
      'role': role,
      if (signerName != null && signerName.isNotEmpty) 'signer_name': signerName,
      'signature': signature,
    });
    return _detail(data);
  }

  @override
  Future<ChecklistDetail> seal(int id, {required String phase, String? remarks, String? actualDate}) async {
    final data = await _http.post(EndPoints.checklistSeal(id), body: {
      'phase': phase,
      // Always sent — an empty value clears earlier remarks (SealRequest:
      // sometimes|nullable; the action nulls a blank one).
      'remarks': remarks ?? '',
      if (actualDate != null && actualDate.isNotEmpty) 'actual_date': actualDate,
    });
    return _detail(data);
  }

  @override
  Future<ChecklistDetail> addFixture(int id,
      {required String phase, required String category, String? comments, String? status}) async {
    final data = await _http.post(EndPoints.checklistFixtures(id), body: {
      'phase': phase,
      'category': category,
      if (comments != null) 'comments': comments,
      if (status != null) 'status': status,
    });
    return _detail(data);
  }

  @override
  Future<ChecklistDetail> updateFixtureEntry(int entryId,
      {required String phase, String? comments, String? status, String? completedDate}) async {
    final data = await _http.patch(EndPoints.fixtureEntry(entryId), body: {
      'phase': phase,
      if (comments != null) 'comments': comments,
      if (status != null) 'status': status,
      if (completedDate != null) 'completed_date': completedDate,
    });
    return _detail(data);
  }

  @override
  Future<ChecklistDetail> uploadFixturePhoto(int entryId,
      {required String phase, required String which, required String filePath}) async {
    final data = await _http.postFiles(
      EndPoints.fixtureEntryPhoto(entryId),
      fields: {'phase': phase, 'which': which},
      files: [(field: 'photo', path: filePath)],
    );
    return _detail(data);
  }

  @override
  Future<ChecklistDetail> deleteFixtureEntry(int entryId, {required String phase}) async {
    final data = await _http.delete(EndPoints.fixtureEntry(entryId), query: {'phase': phase});
    return _detail(data);
  }

  @override
  Future<Uint8List> pdf(int id) => _http.getBytes(EndPoints.checklistPdf(id));

  @override
  Future<ChecklistDetail> signFixtureArea(int id, int areaId,
      {required String phase, required String ownerName, required String signature}) async {
    final data = await _http.post(EndPoints.checklistFixtureSign(id, areaId), body: {
      'phase': phase,
      'owner_name': ownerName,
      'signature': signature,
    });
    return _detail(data);
  }
}
