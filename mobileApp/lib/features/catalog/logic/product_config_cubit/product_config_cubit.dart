import 'package:equatable/equatable.dart';
import 'package:flutter_bloc/flutter_bloc.dart';

import 'package:invo/shared/domain/constants/data_fetching_status.dart';
import 'package:invo/shared/domain/constants/global_variables.dart';
import 'package:invo/shared/utils/components/app_strings.dart';
import 'package:invo/shared/utils/router/http_utils/common_exception.dart';
import 'package:invo/shared/utils/router/http_utils/reachability.dart';

import '../../domain/models/catalog_models.dart';
import '../../domain/repository/catalog_repository.dart';

part 'product_config_state.dart';

/// Settings → Product Configuration. Click-and-go: each change applies at once
/// (optimistically) and rolls back if the server refuses it.
class ProductConfigCubit extends Cubit<ProductConfigState> {
  ProductConfigCubit({CatalogRepository? repository})
      : _repo = repository ?? serviceLocator<CatalogRepository>(),
        super(const ProductConfigState());

  final CatalogRepository _repo;

  Future<void> load() async {
    emit(state.copyWith(status: DataFetchStatus.waiting, clearError: true));
    try {
      emit(state.copyWith(status: DataFetchStatus.success, config: await _repo.productConfig()));
    } on ApiException catch (e) {
      emit(state.copyWith(status: DataFetchStatus.failed, errorMessage: e.message));
    } catch (e) {
      emit(state.copyWith(status: DataFetchStatus.failed, errorMessage: networkErrorMessage(e, AppStrings.somethingWentWrong)));
    }
  }

  /// Returns whether the change stuck.
  Future<bool> update(ProductConfig next) async {
    final previous = state.config;
    if (next == previous) return true;
    emit(state.copyWith(config: next, saving: true, clearError: true));
    try {
      emit(state.copyWith(config: await _repo.updateProductConfig(next), saving: false));
      return true;
    } on ApiException catch (e) {
      emit(state.copyWith(config: previous, saving: false, errorMessage: e.message));
    } catch (e) {
      emit(state.copyWith(config: previous, saving: false, errorMessage: networkErrorMessage(e, AppStrings.somethingWentWrong)));
    }
    return false;
  }
}
