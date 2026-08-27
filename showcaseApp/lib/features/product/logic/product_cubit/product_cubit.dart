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

  /// The page can be popped while its request is in the air, and emitting into
  /// a closed cubit throws — including from the catch block, which turned an
  /// abandoned screen into an unhandled error.
  void _set(ProductState next) {
    if (!isClosed) emit(next);
  }

  Future<void> load() async {
    _set(state.copyWith(status: DataFetchStatus.waiting, clearError: true));
    try {
      final product = await _repo.product(productId);
      _set(state.copyWith(
        status: DataFetchStatus.success,
        product: product,
        selectedSize: product.size.isEmpty ? null : product.size,
        galleryIndex: 0,
      ));
      await _loadRelated(product);
    } on ApiException catch (e) {
      _set(state.copyWith(status: DataFetchStatus.failed, errorMessage: e.message));
    } catch (_) {
      // Belt and braces behind HttpService, which types everything it throws.
      // A screen that fails says so and offers a retry; a screen that throws
      // past its handler sits on a spinner forever, which is the one outcome
      // with no way out of it.
      _set(state.copyWith(status: DataFetchStatus.failed));
    }
  }

  Future<void> _loadRelated(Product product) async {
    _set(state.copyWith(relatedStatus: DataFetchStatus.waiting));
    try {
      final rows = await _repo.related(product, inStockOnly: inStockOnly);
      _set(state.copyWith(relatedStatus: DataFetchStatus.success, related: rows));
    } on ApiException {
      // A missing rail is a quiet degradation, not a failed page.
      _set(state.copyWith(relatedStatus: DataFetchStatus.failed, related: const []));
    }
  }

  void showImage(int index) => _set(state.copyWith(galleryIndex: index));

  /// Tapping the chosen size again clears it, which is what puts the
  /// availability strip back to every shop that carries the style. There is no
  /// other way out of a selection on a panel with no keyboard and no back
  /// gesture, and a customer who has narrowed to a size they cannot get needs
  /// one.
  void selectSize(String size) => _set(state.selectedSize == size
      ? state.copyWith(clearSize: true)
      : state.copyWith(selectedSize: size));
}
