import 'package:equatable/equatable.dart';

import 'package:invo/shared/domain/helpers/formatters.dart';

/// A nullable string from the API — empty / missing collapses to null so
/// "has a photo / a comment" checks stay simple.
String? _optStr(dynamic v) {
  final s = asStr(v).trim();
  return s.isEmpty ? null : s;
}

bool _asBool(dynamic v) => v == true || v == 1 || v == '1' || v == 'true';

List<Map<String, dynamic>> _maps(dynamic v) => (v as List<dynamic>? ?? const [])
    .whereType<Map>()
    .map((e) => Map<String, dynamic>.from(e))
    .toList();

/// The two hand-over phases (`phase` on every checklist request).
class ChecklistPhase {
  ChecklistPhase._();

  static const String moveIn = 'move_in';
  static const String moveOut = 'move_out';
}

/// Line status values. Move-in is binary ([ok] = Present, null = not present);
/// move-out is [ok] (Good) / [notOk] (Damaged) / null.
class ChecklistLineStatus {
  ChecklistLineStatus._();

  static const String ok = 'ok';
  static const String notOk = 'not_ok';
}

/// Fixture-comment entry status values.
class FixtureStatus {
  FixtureStatus._();

  static const String pending = 'pending';
  static const String inProgress = 'in_progress';
  static const String completed = 'completed';

  static const List<(String, String)> options = [
    (pending, 'Pending'),
    (inProgress, 'In Progress'),
    (completed, 'Completed'),
  ];

  static String labelFor(String status) {
    for (final o in options) {
      if (o.$1 == status) return o.$2;
    }
    return 'Pending';
  }
}

/// Signatory role sentinels the client needs to recognise.
class ChecklistRole {
  ChecklistRole._();

  static const String lessee = 'lessee';
}

/// One of the signed-in user's coordinator roles on a rent-out.
class MyRole extends Equatable {
  const MyRole({required this.role, required this.label});

  factory MyRole.fromJson(Map<String, dynamic> j) =>
      MyRole(role: asStr(j['role']), label: asStr(j['label']));

  final String role;
  final String label;

  @override
  List<Object?> get props => [role, label];
}

/// An inbox row — one open hand-over (rent-out × phase) the user coordinates.
class ChecklistJob extends Equatable {
  const ChecklistJob({
    required this.id,
    required this.unit,
    this.building = '',
    this.group = '',
    this.agreementType = '',
    this.agreementLabel = '',
    this.referenceNo = '',
    this.lesseeName = '',
    this.lesseeMobile = '',
    required this.phase,
    this.phaseLabel = '',
    this.scheduledDate,
    this.myRoles = const [],
    this.linesTotal = 0,
    this.linesChecked = 0,
    this.linesDamaged = 0,
    this.signaturesDone = 0,
    this.signaturesRequired = 3,
    this.readyToSeal = false,
    this.sealed = false,
    this.phases = const [ChecklistPhase.moveIn, ChecklistPhase.moveOut],
  });

  factory ChecklistJob.fromJson(Map<String, dynamic> j) => ChecklistJob(
        id: asNum(j['id']).toInt(),
        unit: asStr(j['unit']),
        building: asStr(j['building']),
        group: asStr(j['group']),
        agreementType: asStr(j['agreement_type']),
        agreementLabel: asStr(j['agreement_label']),
        referenceNo: asStr(j['reference_no']),
        lesseeName: asStr(j['lessee_name']),
        lesseeMobile: asStr(j['lessee_mobile']),
        phase: asStr(j['phase']).isEmpty ? ChecklistPhase.moveOut : asStr(j['phase']),
        phaseLabel: asStr(j['phase_label']),
        scheduledDate: _optStr(j['scheduled_date']),
        myRoles: _maps(j['my_roles']).map(MyRole.fromJson).toList(),
        linesTotal: asNum(j['lines_total']).toInt(),
        linesChecked: asNum(j['lines_checked']).toInt(),
        linesDamaged: asNum(j['lines_damaged']).toInt(),
        signaturesDone: asNum(j['signatures_done']).toInt(),
        signaturesRequired: j['signatures_required'] == null ? 3 : asNum(j['signatures_required']).toInt(),
        readyToSeal: _asBool(j['ready_to_seal']),
        sealed: _asBool(j['sealed']),
        phases: j['phases'] is List
            ? (j['phases'] as List).map(asStr).where((s) => s.isNotEmpty).toList()
            : const [ChecklistPhase.moveIn, ChecklistPhase.moveOut],
      );

  final int id;
  final String unit;
  final String building;
  final String group;
  final String agreementType;
  final String agreementLabel;
  final String referenceNo;
  final String lesseeName;
  final String lesseeMobile;
  final String phase;
  final String phaseLabel;
  final String? scheduledDate; // yyyy-MM-dd
  final List<MyRole> myRoles;
  final int linesTotal;
  final int linesChecked;
  final int linesDamaged;
  final int signaturesDone;
  final int signaturesRequired;
  final bool readyToSeal;

  /// Whether this hand-over (rent-out × phase) has been sealed.
  final bool sealed;

  /// Phases this rent-out has: both on a rental, only `move_in` (the single
  /// "Handover") on a lease / sale.
  final List<String> phases;

  bool get isMoveOut => phase == ChecklistPhase.moveOut;

  /// Still collecting signatures: not sealed, and fewer than required signed.
  /// (Once all have signed it is "ready to seal", not "to sign".)
  bool get needsSignatures => !sealed && signaturesDone < signaturesRequired;

  /// A lease / sale has one hand-over (no move-out phase).
  bool get isSingleHandover => !phases.contains(ChecklistPhase.moveOut);

  /// Server-named phase ("Move-In" / "Move-Out" / "Handover") for UI copy.
  String get phaseName => phaseLabel.isNotEmpty ? phaseLabel : (isMoveOut ? 'Move-Out' : 'Move-In');

  /// Stable list key — the same rent-out can appear once per phase.
  String get rowKey => '$id-$phase';

  /// Checked share of the lines, 0..1.
  double get progress => linesTotal == 0 ? 0 : (linesChecked / linesTotal).clamp(0, 1).toDouble();

  /// `Unit 305 · Porto Arabia Tower 11` (building dropped when blank).
  String get title => building.isEmpty ? 'Unit $unit' : 'Unit $unit · $building';

  DateTime? get scheduled {
    final d = scheduledDate == null ? null : DateTime.tryParse(scheduledDate!);
    return d == null ? null : DateTime(d.year, d.month, d.day);
  }

  @override
  List<Object?> get props => [
        id, unit, building, group, agreementType, agreementLabel, referenceNo, lesseeName, lesseeMobile,
        phase, phaseLabel, scheduledDate, myRoles, linesTotal, linesChecked, linesDamaged,
        signaturesDone, signaturesRequired, readyToSeal, sealed, phases,
      ];
}

/// One checklist item (a fixture / appliance in a room).
class ChecklistLine extends Equatable {
  const ChecklistLine({
    required this.id,
    this.checklistId = 0,
    required this.name,
    this.category = 'Others',
    this.qty = 1,
    this.sortOrder = 0,
    this.referenceImage,
    this.moveInImage,
    this.moveOutImage,
    this.moveInStatus,
    this.moveInComment,
    this.moveOutStatus,
    this.moveOutComment,
    this.damageCost = 0,
  });

  factory ChecklistLine.fromJson(Map<String, dynamic> j) => ChecklistLine(
        id: asNum(j['id']).toInt(),
        checklistId: asNum(j['checklist_id']).toInt(),
        name: asStr(j['name']),
        category: _optStr(j['category']) ?? 'Others',
        qty: j['qty'] == null ? 1 : asNum(j['qty']),
        sortOrder: asNum(j['sort_order']).toInt(),
        referenceImage: _optStr(j['reference_image']),
        moveInImage: _optStr(j['move_in_image']),
        moveOutImage: _optStr(j['move_out_image']),
        moveInStatus: _optStr(j['move_in_status']),
        moveInComment: _optStr(j['move_in_comment']),
        moveOutStatus: _optStr(j['move_out_status']),
        moveOutComment: _optStr(j['move_out_comment']),
        damageCost: asNum(j['damage_cost']).toDouble(),
      );

  final int id;
  final int checklistId;
  final String name;
  final String category;
  final num qty;
  final int sortOrder;
  final String? referenceImage;
  final String? moveInImage;
  final String? moveOutImage;
  final String? moveInStatus;
  final String? moveInComment;
  final String? moveOutStatus;
  final String? moveOutComment;
  final double damageCost;

  String? statusFor(String phase) => phase == ChecklistPhase.moveOut ? moveOutStatus : moveInStatus;

  String? commentFor(String phase) => phase == ChecklistPhase.moveOut ? moveOutComment : moveInComment;

  String? imageFor(String phase) => phase == ChecklistPhase.moveOut ? moveOutImage : moveInImage;

  bool isCheckedFor(String phase) => statusFor(phase) != null;

  bool isDamagedFor(String phase) =>
      phase == ChecklistPhase.moveOut && moveOutStatus == ChecklistLineStatus.notOk;

  @override
  List<Object?> get props => [
        id, checklistId, name, category, qty, sortOrder, referenceImage, moveInImage, moveOutImage,
        moveInStatus, moveInComment, moveOutStatus, moveOutComment, damageCost,
      ];
}

/// A fixture-comment note (snag) inside a room's [FixtureArea].
class FixtureEntry extends Equatable {
  const FixtureEntry({
    required this.id,
    this.comments = '',
    this.status = FixtureStatus.pending,
    this.statusLabel = '',
    this.completedDate,
    this.beforeImage,
    this.afterImage,
  });

  factory FixtureEntry.fromJson(Map<String, dynamic> j) => FixtureEntry(
        id: asNum(j['id']).toInt(),
        comments: asStr(j['comments']),
        status: _optStr(j['status']) ?? FixtureStatus.pending,
        statusLabel: asStr(j['status_label']),
        completedDate: _optStr(j['completed_date']),
        beforeImage: _optStr(j['before_image']),
        afterImage: _optStr(j['after_image']),
      );

  final int id;
  final String comments;
  final String status;
  final String statusLabel;
  final String? completedDate;
  final String? beforeImage;
  final String? afterImage;

  String get label => statusLabel.isNotEmpty ? statusLabel : FixtureStatus.labelFor(status);

  @override
  List<Object?> get props => [id, comments, status, statusLabel, completedDate, beforeImage, afterImage];
}

/// The fixture-comment area for one room category, with the owner's sign-off.
class FixtureArea extends Equatable {
  const FixtureArea({
    this.id,
    required this.category,
    this.ownerName,
    this.ownerSignedAt,
    this.ownerSignature,
    this.readyForAcceptance = false,
    this.entries = const [],
  });

  factory FixtureArea.fromJson(Map<String, dynamic> j) => FixtureArea(
        id: j['id'] == null ? null : asNum(j['id']).toInt(),
        category: _optStr(j['category']) ?? 'Others',
        ownerName: _optStr(j['owner_name']),
        ownerSignedAt: _optStr(j['owner_signed_at']),
        ownerSignature: _optStr(j['owner_signature']),
        readyForAcceptance: _asBool(j['ready_for_acceptance']),
        entries: _maps(j['entries']).map(FixtureEntry.fromJson).toList(),
      );

  final int? id;
  final String category;
  final String? ownerName;
  final String? ownerSignedAt;
  final String? ownerSignature;
  final bool readyForAcceptance;
  final List<FixtureEntry> entries;

  bool get isSigned => ownerSignature != null || ownerSignedAt != null;

  @override
  List<Object?> get props => [id, category, ownerName, ownerSignedAt, ownerSignature, readyForAcceptance, entries];
}

/// One of the three hand-over signatures for the current phase.
class ChecklistSignature extends Equatable {
  const ChecklistSignature({
    required this.role,
    this.label = '',
    this.assigneeName = '',
    this.canSign = false,
    this.isMe = false,
    this.signed = false,
    this.signerName,
    this.signedAt,
    this.signature,
  });

  factory ChecklistSignature.fromJson(Map<String, dynamic> j) => ChecklistSignature(
        role: asStr(j['role']),
        label: asStr(j['label']),
        assigneeName: asStr(j['assignee_name']),
        canSign: _asBool(j['can_sign']),
        isMe: _asBool(j['is_me']),
        signed: _asBool(j['signed']),
        signerName: _optStr(j['signer_name']),
        signedAt: _optStr(j['signed_at']),
        signature: _optStr(j['signature']),
      );

  final String role;
  final String label;
  final String assigneeName;
  final bool canSign;
  final bool isMe;
  final bool signed;
  final String? signerName;
  final String? signedAt; // "2026-10-07 16:41"
  final String? signature;

  bool get isLessee => role == ChecklistRole.lessee;

  @override
  List<Object?> get props => [role, label, assigneeName, canSign, isMe, signed, signerName, signedAt, signature];
}

/// A room on the Rooms screen — a line category, in line order.
class ChecklistRoom extends Equatable {
  const ChecklistRoom({required this.name, required this.lines});

  final String name;
  final List<ChecklistLine> lines;

  int checkedFor(String phase) => lines.where((l) => l.isCheckedFor(phase)).length;

  int okFor(String phase) => lines.where((l) => l.statusFor(phase) == ChecklistLineStatus.ok).length;

  int damagedFor(String phase) => lines.where((l) => l.isDamagedFor(phase)).length;

  List<ChecklistLine> uncheckedFor(String phase) => lines.where((l) => !l.isCheckedFor(phase)).toList();

  bool isDoneFor(String phase) => lines.isNotEmpty && checkedFor(phase) == lines.length;

  @override
  List<Object?> get props => [name, lines];
}

/// Full hand-over for one rent-out × phase: the inbox fields plus lines,
/// fixture areas and signatures. Every write endpoint returns a fresh one.
class ChecklistDetail extends Equatable {
  const ChecklistDetail({
    required this.job,
    this.actualDate,
    this.remarks,
    this.damageTotal = 0,
    this.lines = const [],
    this.fixtures = const [],
    this.signatures = const [],
  });

  factory ChecklistDetail.fromJson(Map<String, dynamic> j) => ChecklistDetail(
        job: ChecklistJob.fromJson(j),
        actualDate: _optStr(j['actual_date']),
        remarks: _optStr(j['remarks']),
        damageTotal: asNum(j['damage_total']).toDouble(),
        lines: _maps(j['lines']).map(ChecklistLine.fromJson).toList(),
        fixtures: _maps(j['fixtures']).map(FixtureArea.fromJson).toList(),
        signatures: _maps(j['signatures']).map(ChecklistSignature.fromJson).toList(),
      );

  final ChecklistJob job;
  final String? actualDate;
  final String? remarks;
  final double damageTotal;
  final List<ChecklistLine> lines;
  final List<FixtureArea> fixtures;
  final List<ChecklistSignature> signatures;

  bool get sealed => job.sealed;
  String get phase => job.phase;
  String get phaseName => job.phaseName;
  bool get isMoveOut => job.isMoveOut;

  int get checkedCount => lines.where((l) => l.isCheckedFor(phase)).length;
  int get uncheckedCount => lines.length - checkedCount;
  int get okCount => lines.where((l) => l.statusFor(phase) == ChecklistLineStatus.ok).length;
  int get damagedCount => lines.where((l) => l.isDamagedFor(phase)).length;
  int get photoCount => lines.where((l) => l.imageFor(phase) != null).length;
  double get progress => lines.isEmpty ? 0 : checkedCount / lines.length;

  bool get readyToSeal => job.readyToSeal;
  int get signaturesDone => signatures.where((s) => s.signed).length;

  /// Line categories as rooms, in the order they first appear.
  List<ChecklistRoom> get rooms {
    final order = <String>[];
    final byName = <String, List<ChecklistLine>>{};
    for (final l in lines) {
      final bucket = byName.putIfAbsent(l.category, () {
        order.add(l.category);
        return <ChecklistLine>[];
      });
      bucket.add(l);
    }
    return [for (final n in order) ChecklistRoom(name: n, lines: byName[n]!)];
  }

  ChecklistLine? lineById(int id) {
    for (final l in lines) {
      if (l.id == id) return l;
    }
    return null;
  }

  FixtureArea? fixtureFor(String category) {
    for (final a in fixtures) {
      if (a.category.toLowerCase() == category.toLowerCase()) return a;
    }
    return null;
  }

  /// Signing timeline order: my own role(s) first, then the lessee, then the rest.
  List<ChecklistSignature> get signingOrder {
    int rank(ChecklistSignature s) => s.isMe ? 0 : (s.isLessee ? 1 : 2);
    final indexed = signatures.asMap().entries.toList()
      ..sort((a, b) {
        final r = rank(a.value).compareTo(rank(b.value));
        return r != 0 ? r : a.key.compareTo(b.key);
      });
    return [for (final e in indexed) e.value];
  }

  /// The labels the user signs as, e.g. "Facility Coordinator".
  String get mySigningLabel {
    final mine = signatures.where((s) => s.isMe).map((s) => s.label).where((l) => l.isNotEmpty).toList();
    if (mine.isNotEmpty) return mine.join(' & ');
    return job.myRoles.isEmpty ? '—' : job.myRoles.first.label;
  }

  @override
  List<Object?> get props => [job, actualDate, remarks, damageTotal, lines, fixtures, signatures];
}
