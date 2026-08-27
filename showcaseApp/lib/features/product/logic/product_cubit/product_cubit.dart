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
  ProductCubit({required this.productId, this.inStockOnly = true})
      : super(const ProductState()) {
    load();
  }

  final int productId;

  /// The funnel's global "in stock at this store". A customer who has asked to
  /// be shown only what they can walk out with means it for the related rail
  /// too — passed in rather than read from the cubit so the product page stays
  /// openable from a deep link with no funnel behind it.
  final bool inStockOnly;

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
      final rows = await _repo.related(product, inStockOnly: inStockOnly);
      emit(state.copyWith(relatedStatus: DataFetchStatus.success, related: rows));
    } on ApiException {
      // A missing rail is a quiet degradation, not a failed page.
      emit(state.copyWith(relatedStatus: DataFetchStatus.failed, related: const []));
    }
  }

  void showImage(int index) => emit(state.copyWith(galleryIndex: index));

  void selectSize(String size) => emit(state.copyWith(selectedSize: size));
}
