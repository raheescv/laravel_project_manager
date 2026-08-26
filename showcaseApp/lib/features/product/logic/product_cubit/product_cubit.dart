import 'package:equatable/equatable.dart';
import 'package:flutter_bloc/flutter_bloc.dart';

import '../../../../shared/domain/constants/data_fetching_status.dart';
import '../../../../shared/domain/constants/global_variables.dart';
import '../../../../shared/domain/models/index.dart';
import '../../../../shared/domain/repository/catalog_repository.dart';
import '../../../../shared/utils/router/http_utils/common_exception.dart';

part 'product_state.dart';

/// One product page. Detail first, related after — the rail sits below the fold
/// and must never hold up the photo, the price or the stock.
class ProductCubit extends Cubit<ProductState> {
  ProductCubit({required this.productId}) : super(const ProductState()) {
    load();
  }

  final int productId;

  CatalogRepository get _repo => serviceLocator<CatalogRepository>();

  Future<void> load() async {
    emit(state.copyWith(status: DataFetchStatus.waiting, clearError: true));
    try {
      final product = await _repo.product(productId);
      emit(state.copyWith(
        status: DataFetchStatus.success,
        product: product,
        selectedSize: product.size.isEmpty ? null : product.size,
        galleryIndex: 0,
      ));
      await _loadRelated(product);
    } on ApiException catch (e) {
      emit(state.copyWith(status: DataFetchStatus.failed, errorMessage: e.message));
    }
  }

  Future<void> _loadRelated(Product product) async {
    emit(state.copyWith(relatedStatus: DataFetchStatus.waiting));
    try {
      final rows = await _repo.related(product);
      emit(state.copyWith(relatedStatus: DataFetchStatus.success, related: rows));
    } on ApiException {
      // A missing rail is a quiet degradation, not a failed page.
      emit(state.copyWith(relatedStatus: DataFetchStatus.failed, related: const []));
    }
  }

  void showImage(int index) => emit(state.copyWith(galleryIndex: index));

  void selectSize(String size) => emit(state.copyWith(selectedSize: size));
}
