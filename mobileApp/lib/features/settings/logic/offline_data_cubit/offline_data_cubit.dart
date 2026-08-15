import 'package:equatable/equatable.dart';
import 'package:flutter_bloc/flutter_bloc.dart';

import 'package:invo/shared/domain/constants/data_fetching_status.dart';
import 'package:invo/shared/domain/constants/global_variables.dart';
import 'package:invo/shared/domain/repository/catalog_snapshot_repository.dart';
import 'package:invo/shared/logic/branch_cubit/branch_cubit.dart';
import 'package:invo/shared/utils/local_storage/image_store.dart';

part 'offline_data_state.dart';

/// Reads the device's offline stock for the settings screen that reports it.
///
/// Everything here is local. The point of the screen is to answer "can this till
/// sell if the network goes now" *while* the network is still up, and a readout
/// that had to reach the server to say so would be worthless for exactly the
/// state it exists to describe.
class OfflineDataCubit extends Cubit<OfflineDataState> {
  OfflineDataCubit() : super(const OfflineDataState());

  CatalogSnapshotRepository get _snapshot => serviceLocator<CatalogSnapshotRepository>();

  int? get _branchId => serviceLocator<BranchCubit>().selectedId;

  Future<void> load() async {
    final branchId = _branchId;
    if (branchId == null) {
      emit(state.copyWith(status: DataFetchStatus.success));
      return;
    }
    emit(state.copyWith(status: DataFetchStatus.waiting));
    try {
      final meta = await _snapshot.meta(branchId);
      final categories = await _snapshot.categoryCount(branchId);
      final paymentMethods =
          await _snapshot.lookupCount(branchId: branchId, kind: SnapshotLookup.paymentMethod);
      final employees =
          await _snapshot.lookupCount(branchId: branchId, kind: SnapshotLookup.employee);
      final customers =
          await _snapshot.lookupCount(branchId: branchId, kind: SnapshotLookup.customer);
      final photos = await ImageStore.instance.stats();
      if (isClosed) return;
      emit(state.copyWith(
        status: DataFetchStatus.success,
        products: meta?.productCount ?? 0,
        categories: categories,
        paymentMethods: paymentMethods,
        employees: employees,
        customers: customers,
        photoFiles: photos.files,
        photoBytes: photos.bytes,
        syncedAt: meta?.syncedAt,
        clearSyncedAt: meta == null,
      ));
    } catch (_) {
      // An unreadable database, or one written by a build that has since been
      // rolled back. Reporting zero is the honest answer and matches what the
      // till would actually be able to sell.
      if (!isClosed) emit(const OfflineDataState(status: DataFetchStatus.success));
    }
  }

  /// Drop everything cached for every branch, then re-read so the screen shows
  /// the emptied state rather than the figures it had a moment ago.
  ///
  /// The outbox is untouched — [CatalogSnapshotRepository.clear] does not go
  /// near it. A sale queued on this device is money that was taken, and it has
  /// to survive a cache wipe the same way it survives a sign-out.
  Future<void> clear() async {
    emit(state.copyWith(clearing: true));
    try {
      await _snapshot.clear();
    } finally {
      if (!isClosed) emit(state.copyWith(clearing: false));
      await load();
    }
  }
}
