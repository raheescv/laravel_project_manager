part of 'product_cubit.dart';

class ProductState extends Equatable {
  const ProductState({
    this.status = DataFetchStatus.idle,
    this.relatedStatus = DataFetchStatus.idle,
    this.product,
    this.related = const [],
    this.galleryIndex = 0,
    this.selectedSize,
    this.errorMessage,
  });

  final DataFetchStatus status;
  final DataFetchStatus relatedStatus;
  final Product? product;
  final List<Product> related;
  final int galleryIndex;

  /// Defaults to the product's own size; changing it navigates to that variant
  /// rather than mutating this one.
  final String? selectedSize;

  final String? errorMessage;

  ProductState copyWith({
    DataFetchStatus? status,
    DataFetchStatus? relatedStatus,
    Product? product,
    List<Product>? related,
    int? galleryIndex,
    String? selectedSize,
    String? errorMessage,
    bool clearError = false,
  }) =>
      ProductState(
        status: status ?? this.status,
        relatedStatus: relatedStatus ?? this.relatedStatus,
        product: product ?? this.product,
        related: related ?? this.related,
        galleryIndex: galleryIndex ?? this.galleryIndex,
        selectedSize: selectedSize ?? this.selectedSize,
        errorMessage: clearError ? null : (errorMessage ?? this.errorMessage),
      );

  @override
  List<Object?> get props =>
      [status, relatedStatus, product, related, galleryIndex, selectedSize, errorMessage];
}
