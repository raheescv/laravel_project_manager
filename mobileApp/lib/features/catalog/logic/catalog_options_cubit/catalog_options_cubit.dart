import 'package:equatable/equatable.dart';
import 'package:flutter_bloc/flutter_bloc.dart';

import 'package:invo/shared/domain/constants/data_fetching_status.dart';
import 'package:invo/shared/utils/components/app_strings.dart';
import 'package:invo/shared/utils/router/http_utils/common_exception.dart';
import 'package:invo/shared/utils/router/http_utils/reachability.dart';

import '../../domain/models/catalog_models.dart';
import '../../domain/repository/catalog_repository.dart';

part 'catalog_options_state.dart';

/// Departments, categories, units and brands — fetched once per session and
/// shared by the list filters and the Add / Edit pickers of both types.
class CatalogOptionsCubit extends Cubit<CatalogOptionsState> {
  CatalogOptionsCubit(this._repo) : super(const CatalogOptionsState());

  final CatalogRepository _repo;

  /// Loads unless already loaded (or loading); [force] refetches — after a
  /// save that quick-created a department, say.
  Future<void> ensureLoaded({bool force = false}) async {
    if (state.isLoading || (state.isLoaded && !force)) return;
    emit(state.copyWith(status: DataFetchStatus.waiting));
    try {
      emit(CatalogOptionsState(status: DataFetchStatus.success, options: await _repo.options()));
    } on ApiException catch (e) {
      emit(state.copyWith(status: DataFetchStatus.failed, errorMessage: e.message));
    } catch (e) {
      emit(state.copyWith(status: DataFetchStatus.failed, errorMessage: networkErrorMessage(e, AppStrings.somethingWentWrong)));
    }
  }
}
