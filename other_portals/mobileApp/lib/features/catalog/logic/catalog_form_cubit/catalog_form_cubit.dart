import 'dart:typed_data';

import 'package:equatable/equatable.dart';
import 'package:flutter_bloc/flutter_bloc.dart';

import 'package:invo/shared/domain/constants/data_fetching_status.dart';
import 'package:invo/shared/domain/constants/global_variables.dart';
import 'package:invo/shared/utils/components/app_strings.dart';
import 'package:invo/shared/utils/router/http_utils/common_exception.dart';
import 'package:invo/shared/utils/router/http_utils/reachability.dart';

import '../../domain/models/catalog_models.dart';
import '../../domain/repository/catalog_repository.dart';

part 'catalog_form_state.dart';

/// The Add / Edit page: loads the item, saves it, deletes it and manages its
/// photos. A new item's photos are held until the first save gives it an id.
class CatalogFormCubit extends Cubit<CatalogFormState> {
  CatalogFormCubit({required this.type, CatalogRepository? repository})
      : _repo = repository ?? serviceLocator<CatalogRepository>(),
        super(const CatalogFormState());

  final CatalogRepository _repo;
  final CatalogType type;

  /// Something was saved on this page — a "Save & New" run included — so the
  /// list behind it has to re-read when the page closes.
  bool get savedAny => _savedAny;
  bool _savedAny = false;

  /// A new item ([id] null) starts from [blank]; an existing one is fetched.
  Future<void> load({int? id, required CatalogItem blank}) async {
    final session = state.session + 1;
    if (id == null) {
      emit(CatalogFormState(status: DataFetchStatus.success, item: blank, session: session));
      return;
    }
    emit(CatalogFormState(status: DataFetchStatus.waiting, session: session));
    final item = await _guard(() => _repo.item(type, id));
    emit(item == null
        ? state.copyWith(status: DataFetchStatus.failed)
        : state.copyWith(status: DataFetchStatus.success, item: item));
  }

  /// Creates or updates from the form's [draft]; returns the saved item or
  /// null with the reason in [CatalogFormState.errorMessage].
  Future<CatalogItem?> save(CatalogItem draft) async {
    if (state.busy) return null;
    emit(state.copyWith(busy: true, clearError: true));
    var saved = await _guard(() => _repo.save(type, draft.toPayload(type), id: draft.id));
    if (saved != null && draft.isNew) {
      for (final photo in state.pendingPhotos) {
        saved = await _guard(() => _repo.addImage(type, saved!.id!, photo.bytes, photo.filename)) ?? saved;
      }
    }
    if (saved != null) _savedAny = true;
    emit(saved == null ? state.copyWith(busy: false) : state.copyWith(busy: false, item: saved, pendingPhotos: const []));
    return saved;
  }

  Future<bool> delete() async {
    final id = state.item?.id;
    if (id == null || state.busy) return false;
    emit(state.copyWith(busy: true, clearError: true));
    final ok = await _guard(() async {
      await _repo.delete(type, id);
      return true;
    });
    emit(state.copyWith(busy: false));
    return ok ?? false;
  }

  Future<void> addPhoto(Uint8List bytes, String filename) async {
    final id = state.item?.id;
    if (id == null) {
      emit(state.copyWith(pendingPhotos: [...state.pendingPhotos, PendingPhoto(bytes, filename)]));
      return;
    }
    emit(state.copyWith(busy: true, clearError: true));
    final item = await _guard(() => _repo.addImage(type, id, bytes, filename));
    emit(state.copyWith(busy: false, item: item));
  }

  Future<void> removePhoto(CatalogImage image) async {
    final id = state.item?.id;
    if (id == null) return;
    emit(state.copyWith(busy: true, clearError: true));
    final item = await _guard(() => _repo.removeImage(type, id, image.id));
    emit(state.copyWith(busy: false, item: item));
  }

  void removePendingPhoto(int index) =>
      emit(state.copyWith(pendingPhotos: [...state.pendingPhotos]..removeAt(index)));

  /// Runs [action]; on failure records why and returns null.
  Future<T?> _guard<T>(Future<T> Function() action) async {
    try {
      return await action();
    } on ApiException catch (e) {
      emit(state.copyWith(errorMessage: e.message));
    } catch (e) {
      emit(state.copyWith(errorMessage: networkErrorMessage(e, AppStrings.somethingWentWrong)));
    }
    return null;
  }
}
