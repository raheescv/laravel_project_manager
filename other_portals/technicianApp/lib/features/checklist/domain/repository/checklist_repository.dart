import 'dart:typed_data';

import '../models/checklist_models.dart';

/// Contract for the technician hand-over checklist API. Every write returns
/// the full [ChecklistDetail] for the phase sent, which replaces the cubit's
/// copy. Failures throw `ApiException` for the cubit to surface.
abstract class ChecklistRepository {
  /// Hand-over jobs (rent-out × phase) the user coordinates, sorted by
  /// scheduled date. [status] is `open` (default) | `completed` | `all`.
  /// [dateBasis] (`move_in` | `move_out`) picks which date [fromDate] /
  /// [toDate] (`Y-m-d`, inclusive) apply to and also restricts the phase.
  Future<List<ChecklistJob>> jobs({
    String? search,
    String? phase,
    String? status,
    String? dateBasis,
    String? fromDate,
    String? toDate,
  });

  /// Full hand-over; [phase] defaults server-side to the current one.
  Future<ChecklistDetail> detail(int id, {String? phase});

  /// PATCH a line — only the keys present in [changes] (`status`, `comment`,
  /// `qty`, `damage_cost`) are sent. A present `status: null` clears it.
  Future<ChecklistDetail> updateLine(int id, int lineId,
      {required String phase, required Map<String, dynamic> changes});

  /// Replaces this phase's photo on a line.
  Future<ChecklistDetail> uploadLinePhoto(int id, int lineId,
      {required String phase, required String filePath});

  /// Sets `ok` on the given lines that are still unchecked.
  Future<ChecklistDetail> markOk(int id,
      {required String phase, required List<int> lineIds});

  /// Signs [role] with a `data:image/png;base64,…` [signature].
  Future<ChecklistDetail> sign(int id,
      {required String phase, required String role, String? signerName, required String signature});

  /// Seals the hand-over (422 unless all three signatures are present).
  Future<ChecklistDetail> seal(int id,
      {required String phase, String? remarks, String? actualDate});

  /// Adds a fixture-comment entry (creating the area if needed).
  Future<ChecklistDetail> addFixture(int id,
      {required String phase, required String category, String? comments, String? status});

  Future<ChecklistDetail> updateFixtureEntry(int entryId,
      {required String phase, String? comments, String? status, String? completedDate});

  /// [which] is `before` or `after`.
  Future<ChecklistDetail> uploadFixturePhoto(int entryId,
      {required String phase, required String which, required String filePath});

  Future<ChecklistDetail> deleteFixtureEntry(int entryId, {required String phase});

  /// The "Unit Handover & Snagging" form as PDF bytes (same document as the
  /// web tab's Download PDF).
  Future<Uint8List> pdf(int id);

  /// Owner acceptance of a room's fixture area.
  Future<ChecklistDetail> signFixtureArea(int id, int areaId,
      {required String phase, required String ownerName, required String signature});
}
