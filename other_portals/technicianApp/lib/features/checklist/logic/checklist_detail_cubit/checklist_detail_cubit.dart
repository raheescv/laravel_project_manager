import 'dart:typed_data';

import 'package:equatable/equatable.dart';
import 'package:flutter_bloc/flutter_bloc.dart';

import 'package:invo/shared/domain/constants/data_fetching_status.dart';
import 'package:invo/shared/utils/components/app_strings.dart';
import 'package:invo/shared/utils/router/http_utils/common_exception.dart';

import '../../domain/models/checklist_models.dart';
import '../../domain/repository/checklist_repository.dart';

part 'checklist_detail_state.dart';

/// One hand-over (rent-out × phase), shared by the Rooms, Room, item sheet and
/// Hand-over screens. Every write replaces [ChecklistDetailState.detail] with
/// the server's response and returns whether it succeeded.
class ChecklistDetailCubit extends Cubit<ChecklistDetailState> {
  ChecklistDetailCubit(this._repo) : super(const ChecklistDetailState());

  final ChecklistRepository _repo;
  int _id = 0;

  int get checklistId => _id;

  Future<void> load(int id, [String? phase]) async {
    if (isClosed) return;
    _id = id;
    emit(state.copyWith(status: DataFetchStatus.waiting, phase: phase, clearError: true));
    try {
      final detail = await _repo.detail(id, phase: phase);
      if (isClosed) return;
      emit(state.copyWith(status: DataFetchStatus.success, detail: detail, phase: detail.phase));
    } on ApiException catch (e) {
      if (isClosed) return;
      emit(state.copyWith(status: DataFetchStatus.failed, errorMessage: e.message));
    } catch (_) {
      if (isClosed) return;
      emit(state.copyWith(status: DataFetchStatus.failed, errorMessage: AppStrings.somethingWentWrong));
    }
  }

  Future<void> reload() => load(_id, state.phase);

  /// Downloads the hand-over PDF. Returns the bytes, or null with
  /// [ChecklistDetailState.actionError] set. A second tap while one is in
  /// flight is ignored.
  Future<Uint8List?> fetchPdf() async {
    if (state.pdfLoading || isClosed) return null;
    emit(state.copyWith(pdfStatus: DataFetchStatus.waiting, clearActionError: true));
    try {
      final bytes = await _repo.pdf(_id);
      if (!isClosed) emit(state.copyWith(pdfStatus: DataFetchStatus.success));
      return bytes;
    } on ApiException catch (e) {
      if (!isClosed) emit(state.copyWith(pdfStatus: DataFetchStatus.failed, actionError: e.message));
    } catch (_) {
      if (!isClosed) {
        emit(state.copyWith(pdfStatus: DataFetchStatus.failed, actionError: 'Could not download the PDF.'));
      }
    }
    return null;
  }

  Future<bool> _mutate(Future<ChecklistDetail> Function(String phase) op) async {
    final current = state.detail;
    // A sheet can outlive the screen that owns this cubit (it is closed with its
    // route); a late tap must fail quietly rather than emit after close.
    if (current == null || isClosed) return false;
    emit(state.copyWith(busy: true, clearActionError: true));
    try {
      final detail = await op(current.phase);
      if (!isClosed) emit(state.copyWith(detail: detail, phase: detail.phase, busy: false));
      return true;
    } on ApiException catch (e) {
      if (!isClosed) emit(state.copyWith(busy: false, actionError: e.message));
    } catch (_) {
      if (!isClosed) emit(state.copyWith(busy: false, actionError: AppStrings.somethingWentWrong));
    }
    return false;
  }

  /// [changes] holds only the keys that changed (`status`, `comment`, `qty`,
  /// `damage_cost`).
  Future<bool> updateLine(int lineId, Map<String, dynamic> changes) =>
      _mutate((phase) => _repo.updateLine(_id, lineId, phase: phase, changes: changes));

  Future<bool> uploadLinePhoto(int lineId, String filePath) =>
      _mutate((phase) => _repo.uploadLinePhoto(_id, lineId, phase: phase, filePath: filePath));

  Future<bool> markOk(List<int> lineIds) =>
      _mutate((phase) => _repo.markOk(_id, phase: phase, lineIds: lineIds));

  Future<bool> sign({required String role, String? signerName, required String signature}) =>
      _mutate((phase) => _repo.sign(_id, phase: phase, role: role, signerName: signerName, signature: signature));

  Future<bool> seal({String? remarks, String? actualDate}) =>
      _mutate((phase) => _repo.seal(_id, phase: phase, remarks: remarks, actualDate: actualDate));

  Future<bool> addFixture({required String category, String? comments, String? status}) =>
      _mutate((phase) => _repo.addFixture(_id, phase: phase, category: category, comments: comments, status: status));

  Future<bool> updateFixtureEntry(int entryId, {String? comments, String? status, String? completedDate}) =>
      _mutate((phase) => _repo.updateFixtureEntry(entryId,
          phase: phase, comments: comments, status: status, completedDate: completedDate));

  Future<bool> uploadFixturePhoto(int entryId, {required String which, required String filePath}) =>
      _mutate((phase) => _repo.uploadFixturePhoto(entryId, phase: phase, which: which, filePath: filePath));

  Future<bool> deleteFixtureEntry(int entryId) =>
      _mutate((phase) => _repo.deleteFixtureEntry(entryId, phase: phase));

  Future<bool> signFixtureArea(int areaId, {required String ownerName, required String signature}) =>
      _mutate((phase) => _repo.signFixtureArea(_id, areaId, phase: phase, ownerName: ownerName, signature: signature));
}
