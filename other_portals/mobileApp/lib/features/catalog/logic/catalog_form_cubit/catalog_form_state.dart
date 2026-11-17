part of 'catalog_form_cubit.dart';

/// A photo picked for an item that has no id yet.
class PendingPhoto extends Equatable {
  const PendingPhoto(this.bytes, this.filename);

  final Uint8List bytes;
  final String filename;

  @override
  List<Object?> get props => [bytes, filename];
}

class CatalogFormState extends Equatable {
  const CatalogFormState({
    this.status = DataFetchStatus.idle,
    this.item,
    this.pendingPhotos = const [],
    this.busy = false,
    this.errorMessage,
    this.session = 0,
  });

  /// Loading the item.
  final DataFetchStatus status;
  final CatalogItem? item;
  final List<PendingPhoto> pendingPhotos;

  /// Saving, deleting or uploading — the actions are disabled meanwhile.
  final bool busy;
  final String? errorMessage;

  /// Bumped by every [CatalogFormCubit.load]: the form rebuilds its fields
  /// for a new session, but not when a save or a photo updates the item.
  final int session;

  CatalogFormState copyWith({
    DataFetchStatus? status,
    CatalogItem? item,
    List<PendingPhoto>? pendingPhotos,
    bool? busy,
    String? errorMessage,
    bool clearError = false,
  }) =>
      CatalogFormState(
        status: status ?? this.status,
        item: item ?? this.item,
        pendingPhotos: pendingPhotos ?? this.pendingPhotos,
        busy: busy ?? this.busy,
        errorMessage: clearError ? null : (errorMessage ?? this.errorMessage),
        session: session,
      );

  @override
  List<Object?> get props => [status, item, pendingPhotos, busy, errorMessage, session];
}
