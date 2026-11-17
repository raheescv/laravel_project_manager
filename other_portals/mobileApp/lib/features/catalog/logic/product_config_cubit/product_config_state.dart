part of 'product_config_cubit.dart';

class ProductConfigState extends Equatable {
  const ProductConfigState({
    this.status = DataFetchStatus.idle,
    this.config = const ProductConfig(),
    this.saving = false,
    this.errorMessage,
  });

  final DataFetchStatus status;
  final ProductConfig config;
  final bool saving;
  final String? errorMessage;

  bool get isLoading => status == DataFetchStatus.waiting;

  ProductConfigState copyWith({
    DataFetchStatus? status,
    ProductConfig? config,
    bool? saving,
    String? errorMessage,
    bool clearError = false,
  }) =>
      ProductConfigState(
        status: status ?? this.status,
        config: config ?? this.config,
        saving: saving ?? this.saving,
        errorMessage: clearError ? null : (errorMessage ?? this.errorMessage),
      );

  @override
  List<Object?> get props => [status, config, saving, errorMessage];
}
