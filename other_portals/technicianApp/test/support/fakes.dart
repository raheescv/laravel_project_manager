// In-memory repositories for widget tests. Each records what it was asked so
// tests can assert on server parameters, and can be switched to fail.
import 'dart:typed_data';

import 'package:invo/features/auth/domain/repository/auth_repository.dart';
import 'package:invo/features/checklist/domain/models/checklist_models.dart';
import 'package:invo/features/checklist/domain/repository/checklist_repository.dart';
import 'package:invo/features/profile/domain/repository/profile_repository.dart';
import 'package:invo/features/technician/domain/models/technician_models.dart';
import 'package:invo/features/technician/domain/repository/technician_repository.dart';
import 'package:invo/shared/domain/models/index.dart';
import 'package:invo/shared/utils/router/http_utils/common_exception.dart';

import 'sample_data.dart';

// --------------------------------------------------------------- auth ----

class FakeAuthRepository implements AuthRepository {
  ApiUser user = sampleUser();
  final calls = <String>[];

  @override
  Future<({String token, ApiUser user})> login(String pin) async {
    calls.add('login:$pin');
    if (pin == '0000') throw ApiException('Invalid PIN', statusCode: 422);
    return (token: 'token-123', user: user);
  }

  @override
  Future<({String token, ApiUser user})> loginCredential(String username, String password) async {
    calls.add('loginCredential:$username');
    return (token: 'token-123', user: user);
  }

  @override
  Future<void> logout() async => calls.add('logout');

  @override
  Future<void> changePin(String current, String next) async {
    calls.add('changePin:$next');
    if (current == '0000') throw ApiException('Current PIN is wrong', statusCode: 422);
  }

  @override
  Future<void> changePassword(String current, String next) async => calls.add('changePassword');
}

// ------------------------------------------------------------ profile ----

class FakeProfileRepository implements ProfileRepository {
  final calls = <String>[];
  Uint8List? uploadedPhoto;

  @override
  Future<ApiUser> updateProfile({required String name, required String email, required String mobile}) async {
    calls.add('updateProfile:$name');
    return sampleUser(name: name, mobile: mobile);
  }

  @override
  Future<ApiUser> updatePhoto(Uint8List bytes) async {
    calls.add('updatePhoto');
    uploadedPhoto = bytes;
    return sampleUser(photo: '/storage/users/new.webp');
  }
}

// --------------------------------------------------------- technician ----

class FakeTechnicianRepository implements TechnicianRepository {
  FakeTechnicianRepository({this.fail = false, List<ComplaintListItem>? rows}) : rows = rows ?? sampleComplaints();

  bool fail;
  List<ComplaintListItem> rows;

  /// Holds `detail()` open this long — lets a test act while a load is in flight.
  Duration detailDelay = Duration.zero;
  final complaintQueries = <Map<String, Object?>>[];
  final calls = <String>[];

  void _maybeFail() {
    if (fail) throw ApiException('Server unreachable', statusCode: 500);
  }

  /// Holds the next `dashboard()` open this long (for stale-response tests).
  final dashboardDelays = <Duration>[];
  int dashboardCalls = 0;

  @override
  Future<TechnicianDashboard> dashboard() async {
    dashboardCalls++;
    if (dashboardDelays.isNotEmpty) await Future<void>.delayed(dashboardDelays.removeAt(0));
    _maybeFail();
    return sampleDashboard();
  }

  @override
  Future<Map<String, dynamic>> complaints({
    String? status,
    String? priority,
    String? search,
    String? fromDate,
    String? toDate,
    int page = 1,
    int perPage = 15,
  }) async {
    complaintQueries.add({'status': status, 'priority': priority, 'search': search, 'from': fromDate, 'to': toDate, 'page': page});
    _maybeFail();
    return {
      'data': [
        for (final r in rows)
          complaintRowJson(r.id, status: r.status, priority: r.priority, name: r.complaintName),
      ],
      'pagination': {'current_page': 1, 'last_page': 1, 'total': rows.length},
    };
  }

  @override
  Future<ComplaintDetail> detail(int id) async {
    calls.add('detail:$id');
    if (detailDelay > Duration.zero) await Future<void>.delayed(detailDelay);
    _maybeFail();
    return sampleComplaintDetail(id);
  }

  @override
  Future<ComplaintDetail> saveRemark(int id, String remark) async {
    calls.add('saveRemark:$id');
    return sampleComplaintDetail(id);
  }

  @override
  Future<ComplaintDetail> complete(int id, String remark) async {
    calls.add('complete:$id');
    return sampleComplaintDetail(id, completed: true);
  }

  @override
  Future<ComplaintDetail> addNote(int complaintId, String note) async {
    calls.add('addNote:$note');
    return sampleComplaintDetail(complaintId);
  }

  final productSearches = <String?>[];

  /// Per-search latency, e.g. {'slow': 600ms}.
  final productDelays = <String, Duration>{};

  @override
  Future<List<ProductOption>> products({String? search, int perPage = 20}) async {
    productSearches.add(search);
    final delay = productDelays[search ?? ''];
    if (delay != null) await Future<void>.delayed(delay);
    return [
      ProductOption.fromJson({'id': 9, 'name': 'Result for ${search ?? ''}', 'barcode': '8901', 'cost': 120}),
    ];
  }

  Duration branchesDelay = Duration.zero;

  @override
  Future<List<BranchOption>> branches() async {
    if (branchesDelay > Duration.zero) await Future<void>.delayed(branchesDelay);
    return [BranchOption(id: 1, name: 'Main Store')];
  }

  @override
  Future<ComplaintDetail> updateSupplyItem(int itemId,
      {int? branchId, String? mode, double? quantity, double? unitPrice, String? remarks}) async {
    calls.add('updateSupplyItem:$itemId:$mode:$quantity:$unitPrice:$remarks');
    return sampleComplaintDetail(3);
  }

  @override
  Future<ComplaintDetail> addSupplyItem(int complaintId,
      {required int branchId,
      int? productId,
      String? barcode,
      String mode = 'New',
      double quantity = 1,
      double? unitPrice,
      String remarks = ''}) async {
    calls.add('addSupplyItem:$branchId:$productId');
    return sampleComplaintDetail(complaintId);
  }

  @override
  dynamic noSuchMethod(Invocation invocation) => super.noSuchMethod(invocation);
}

// ---------------------------------------------------------- checklist ----

class FakeChecklistRepository implements ChecklistRepository {
  FakeChecklistRepository({List<ChecklistJob>? jobs, Map<int, ChecklistDetail>? details, this.failJobs = false})
      : jobsList = jobs ?? sampleJobs(),
        details = details ?? {12: rentalMoveOut(), 13: rentalMoveIn(), 20: leaseHandover()};

  List<ChecklistJob> jobsList;
  final Map<int, ChecklistDetail> details;
  bool failJobs;

  /// Fails every write (for error-path tests).
  bool failWrites = false;

  final jobQueries = <Map<String, String?>>[];
  final calls = <String>[];
  final lineChanges = <Map<String, dynamic>>[];

  @override
  Future<List<ChecklistJob>> jobs({
    String? search,
    String? phase,
    String? status,
    String? dateBasis,
    String? fromDate,
    String? toDate,
  }) async {
    jobQueries.add({
      'search': search,
      'phase': phase,
      'status': status,
      'date_basis': dateBasis,
      'from_date': fromDate,
      'to_date': toDate,
    });
    if (failJobs) throw ApiException('Server unreachable', statusCode: 500);
    return jobsList;
  }

  ChecklistDetail _get(int id) {
    final d = details[id];
    if (d == null) throw ApiException('Not found', statusCode: 404);
    return d;
  }

  Future<ChecklistDetail> _write(int id, String call) async {
    calls.add(call);
    if (failWrites) throw ApiException('Could not save', statusCode: 422);
    return _get(id);
  }

  int _owner(int entryOrLine) {
    for (final e in details.entries) {
      if (e.value.lines.any((l) => l.id == entryOrLine)) return e.key;
      if (e.value.fixtures.any((a) => a.entries.any((x) => x.id == entryOrLine))) return e.key;
    }
    return details.keys.first;
  }

  @override
  Future<ChecklistDetail> detail(int id, {String? phase}) async {
    calls.add('detail:$id');
    return _get(id);
  }

  @override
  Future<ChecklistDetail> updateLine(int id, int lineId, {required String phase, required Map<String, dynamic> changes}) async {
    lineChanges.add({'line': lineId, ...changes});
    calls.add('updateLine:$lineId');
    if (failWrites) throw ApiException('Could not save', statusCode: 422);
    final d = _get(id);
    if (!changes.containsKey('status')) return d;
    final status = changes['status'] as String?;
    final updated = ChecklistDetail(
      job: d.job,
      actualDate: d.actualDate,
      remarks: d.remarks,
      damageTotal: d.damageTotal,
      fixtures: d.fixtures,
      signatures: d.signatures,
      lines: [
        for (final l in d.lines)
          l.id != lineId
              ? l
              : ChecklistLine(
                  id: l.id,
                  checklistId: l.checklistId,
                  name: l.name,
                  category: l.category,
                  qty: l.qty,
                  sortOrder: l.sortOrder,
                  referenceImage: l.referenceImage,
                  moveInImage: l.moveInImage,
                  moveOutImage: l.moveOutImage,
                  moveInStatus: phase == ChecklistPhase.moveIn ? status : l.moveInStatus,
                  moveInComment: l.moveInComment,
                  moveOutStatus: phase == ChecklistPhase.moveOut ? status : l.moveOutStatus,
                  moveOutComment: l.moveOutComment,
                  damageCost: l.damageCost,
                ),
      ],
    );
    details[id] = updated;
    return updated;
  }

  @override
  Future<ChecklistDetail> uploadLinePhoto(int id, int lineId, {required String phase, required String filePath}) =>
      _write(id, 'uploadLinePhoto:$lineId');

  @override
  Future<ChecklistDetail> markOk(int id, {required String phase, required List<int> lineIds}) =>
      _write(id, 'markOk:${lineIds.join(',')}');

  @override
  Future<ChecklistDetail> sign(int id, {required String phase, required String role, String? signerName, required String signature}) =>
      _write(id, 'sign:$role');

  @override
  Future<ChecklistDetail> seal(int id, {required String phase, String? remarks, String? actualDate}) =>
      _write(id, 'seal:$actualDate:$remarks');

  @override
  Future<ChecklistDetail> addFixture(int id, {required String phase, required String category, String? comments, String? status}) async {
    calls.add('addFixture:$category:$comments:$status');
    if (failWrites) throw ApiException('Could not save', statusCode: 422);
    final d = _get(id);
    final updated = ChecklistDetail(
      job: d.job,
      lines: d.lines,
      signatures: d.signatures,
      damageTotal: d.damageTotal,
      fixtures: [
        for (final a in d.fixtures)
          a.category != category
              ? a
              : FixtureArea(
                  id: a.id ?? 77,
                  category: a.category,
                  entries: [...a.entries, FixtureEntry(id: 900 + a.entries.length, comments: comments ?? '', status: status ?? 'pending')],
                ),
      ],
    );
    details[id] = updated;
    return updated;
  }

  @override
  Future<ChecklistDetail> updateFixtureEntry(int entryId,
          {required String phase, String? comments, String? status, String? completedDate}) =>
      _write(_owner(entryId), 'updateFixtureEntry:$entryId:$comments:$status');

  @override
  Future<ChecklistDetail> uploadFixturePhoto(int entryId, {required String phase, required String which, required String filePath}) =>
      _write(_owner(entryId), 'uploadFixturePhoto:$entryId:$which');

  @override
  Future<ChecklistDetail> deleteFixtureEntry(int entryId, {required String phase}) =>
      _write(_owner(entryId), 'deleteFixtureEntry:$entryId');

  @override
  Future<ChecklistDetail> signFixtureArea(int id, int areaId,
          {required String phase, required String ownerName, required String signature}) =>
      _write(id, 'signFixtureArea:$areaId');

  /// A tiny but valid single-page PDF.
  @override
  Future<Uint8List> pdf(int id) async {
    calls.add('pdf:$id');
    if (failWrites) throw ApiException('Not found', statusCode: 404);
    return Uint8List.fromList('%PDF-1.4\n%%EOF'.codeUnits);
  }
}
