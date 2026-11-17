import 'dart:async';

import 'package:invo/features/auth/logic/auth_cubit/auth_cubit.dart';
import 'package:invo/features/student_card/domain/models/student_card.dart';
import 'package:invo/features/student_card/domain/repository/student_card_repository.dart';
import 'package:invo/features/student_card/domain/services/nfc_card_reader.dart';
import 'package:invo/shared/domain/constants/global_variables.dart';
import 'package:invo/shared/domain/repository/catalog_snapshot_repository.dart';
import 'package:invo/shared/logic/branch_cubit/branch_cubit.dart';
import 'package:invo/shared/utils/router/http_utils/common_exception.dart';

/// What the server said about a card that was answered from the device.
class StudentCardConfirmation {
  StudentCardConfirmation.fresh(StudentCard this.card)
      : accountId = card.accountId,
        error = null;

  const StudentCardConfirmation.refused({required this.accountId, required String this.error}) : card = null;

  final int accountId;

  /// The live card — newer balance — when the server still takes it.
  final StudentCard? card;

  /// Why the server refused it (blocked, inactive, unlinked since the list).
  final String? error;
}

/// The student cards this till can take, kept on the device so a tap is
/// answered from memory instead of a server round trip per child.
///
/// The list is the server's `/students/cards`, stored in the offline snapshot
/// (so it survives a restart and is wiped with it at sign-out) and held in a
/// map for an O(1) tap. It is refreshed when the POS opens and is older than
/// [maxAge]; between refreshes every live lookup writes its answer back.
///
/// A tap answered from here is still confirmed with the server in the
/// background ([confirm]): the newer balance, or the reason the card can no
/// longer pay, goes out on [confirmations] for the ticket to pick up. The sale
/// itself is guarded on the server, so a stale balance can never overspend.
class StudentCardCache {
  StudentCardCache() {
    if (serviceLocator.isRegistered<AuthCubit>()) {
      // Another cashier's tenant must never answer from this one's list.
      _authSub = serviceLocator<AuthCubit>().stream.listen((s) {
        if (s.status == AuthStatus.signedOut) clear();
      });
    }
  }

  static const Duration maxAge = Duration(minutes: 5);

  StudentCardRepository get _repo => serviceLocator<StudentCardRepository>();
  CatalogSnapshotRepository get _snapshot => serviceLocator<CatalogSnapshotRepository>();

  int? get _branchId =>
      serviceLocator.isRegistered<BranchCubit>() ? serviceLocator<BranchCubit>().selectedId : null;

  StreamSubscription<AuthState>? _authSub;
  final _confirmations = StreamController<StudentCardConfirmation>.broadcast();

  /// Keyed by normalised card UID, and separately by admission number (what a
  /// cashier types when a card is forgotten) — the server tries them in that order.
  Map<String, StudentCard> _byUid = const {};
  Map<String, StudentCard> _byAdmission = const {};
  int? _heldFor;
  DateTime? _fetchedAt;
  Future<void>? _refreshing;

  Stream<StudentCardConfirmation> get confirmations => _confirmations.stream;

  int get length => _byUid.length;

  /// The card for a tapped UID or a typed admission number, or null when this
  /// device does not hold it (the caller then asks the server).
  StudentCard? find(String raw) {
    if (_heldFor == null || _heldFor != _branchId) return null;
    final uid = NfcCardReader.normalize(raw);
    return (uid.isEmpty ? null : _byUid[uid]) ?? _byAdmission[raw.trim().toUpperCase()];
  }

  /// Load the stored list into memory, then fetch a new one if it is older
  /// than [maxAge] (or [force]). Concurrent calls share one fetch; failures are
  /// silent — the stored list, or the server per tap, still answers.
  Future<void> refresh({bool force = false}) {
    return _refreshing ??= _refresh(force).whenComplete(() => _refreshing = null);
  }

  Future<void> _refresh(bool force) async {
    final branchId = _branchId;
    if (branchId == null) return;
    try {
      if (_heldFor != branchId) {
        _hold(branchId, await _snapshot.lookupRows(branchId: branchId, kind: SnapshotLookup.studentCard));
        _fetchedAt = null;
      }
      final fetchedAt = _fetchedAt;
      if (!force && fetchedAt != null && DateTime.now().difference(fetchedAt) < maxAge) return;
      final rows = await _repo.cardsRaw();
      if (_branchId != branchId) return;
      _hold(branchId, rows);
      _fetchedAt = DateTime.now();
      await _snapshot.replaceLookups(
        branchId: branchId,
        kind: SnapshotLookup.studentCard,
        rows: [for (final r in rows) {...r, 'id': r['account_id']}],
      );
    } catch (_) {
      // Offline or refused: whatever is held keeps answering.
    }
  }

  void _hold(int branchId, List<Map<String, dynamic>> rows) {
    final byUid = <String, StudentCard>{};
    final byAdmission = <String, StudentCard>{};
    for (final row in rows) {
      final card = StudentCard.fromJson(row);
      final uid = NfcCardReader.normalize(card.cardUid);
      if (uid.isEmpty) continue;
      byUid[uid] = card;
      if (card.admissionNo.isNotEmpty) byAdmission[card.admissionNo.toUpperCase()] = card;
    }
    _byUid = byUid;
    _byAdmission = byAdmission;
    _heldFor = branchId;
  }

  /// A live lookup's answer, written back so the next tap of it is local.
  void put(StudentCard card) {
    final uid = NfcCardReader.normalize(card.cardUid);
    if (uid.isEmpty || _heldFor == null || _heldFor != _branchId) return;
    _byUid = {..._byUid, uid: card};
    if (card.admissionNo.isNotEmpty) _byAdmission = {..._byAdmission, card.admissionNo.toUpperCase(): card};
  }

  void _drop(int accountId) {
    _byUid = {
      for (final e in _byUid.entries)
        if (e.value.accountId != accountId) e.key: e.value,
    };
    _byAdmission = {
      for (final e in _byAdmission.entries)
        if (e.value.accountId != accountId) e.key: e.value,
    };
  }

  /// Ask the server about a card that was just answered from here, and
  /// announce the outcome on [confirmations]. Silent when the server cannot be
  /// reached — the cached answer stands, and the sale is guarded server-side.
  Future<void> confirm(StudentCard card) async {
    try {
      final live = await _repo.lookup(card.cardUid);
      put(live);
      if (!_confirmations.isClosed) _confirmations.add(StudentCardConfirmation.fresh(live));
    } on ApiException catch (e) {
      // Only a refusal about this card — not an expired session or a server
      // fault — takes it off the ticket.
      final status = e.statusCode ?? 0;
      if (status != 404 && status != 422) return;
      _drop(card.accountId);
      if (!_confirmations.isClosed) {
        _confirmations.add(StudentCardConfirmation.refused(accountId: card.accountId, error: e.message));
      }
    } catch (_) {
      // Unreachable: nothing to correct.
    }
  }

  void clear() {
    _byUid = const {};
    _byAdmission = const {};
    _heldFor = null;
    _fetchedAt = null;
  }

  Future<void> dispose() async {
    await _authSub?.cancel();
    await _confirmations.close();
  }
}
