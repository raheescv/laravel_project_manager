part of 'catalog_options_cubit.dart';

class CatalogOptionsState extends Equatable {
  const CatalogOptionsState({
    this.status = DataFetchStatus.idle,
    this.options = const CatalogOptions(),
    this.errorMessage,
  });

  final DataFetchStatus status;
  final CatalogOptions options;
  final String? errorMessage;

  bool get isLoading => status == DataFetchStatus.waiting;
  bool get isLoaded => status == DataFetchStatus.success;

  CatalogOptionsState copyWith({DataFetchStatus? status, CatalogOptions? options, String? errorMessage}) =>
      CatalogOptionsState(
        status: status ?? this.status,
        options: options ?? this.options,
        errorMessage: errorMessage ?? this.errorMessage,
      );

  @override
  List<Object?> get props => [status, options, errorMessage];
}
