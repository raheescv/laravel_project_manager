import 'dart:async';

import 'package:connectivity_plus/connectivity_plus.dart';
import 'package:equatable/equatable.dart';
import 'package:flutter_bloc/flutter_bloc.dart';
import 'package:invo/features/auth/logic/auth_cubit/auth_cubit.dart';
import 'package:invo/shared/domain/constants/data_fetching_status.dart';
import 'package:invo/shared/domain/constants/global_variables.dart';
import 'package:invo/shared/domain/models/index.dart';
import 'package:invo/shared/domain/repository/catalog_snapshot_repository.dart';
import 'package:invo/shared/domain/repository/lookup_repository.dart';
import 'package:invo/shared/domain/services/sale_settings_sync.dart';
import 'package:invo/shared/logic/branch_cubit/branch_cubit.dart';
import 'package:invo/shared/logic/connectivity_cubit/connectivity_cubit.dart';
import 'package:invo/shared/logic/currency_cubit/currency_cubit.dart';
import 'package:invo/shared/utils/components/app_strings.dart';
import 'package:invo/shared/utils/local_storage/image_store.dart';
import 'package:invo/shared/utils/local_storage/local_storage_service.dart';
import 'package:invo/shared/utils/router/http_utils/common_exception.dart';

import '../../domain/models/pending_sale.dart';
import '../../domain/repository/outbox_repository.dart';
import '../../domain/repository/sale_repository.dart';

part 'offline_sync_state.dart';

/// Reconciles this device with the server in both directions: it pushes sales
/// taken while offline, and pulls the catalog snapshot that let them be taken.
///
/// One cubit owns both because they are the same job — keeping the till able to
/// sell without a network — and because they must not run at once: a snapshot
/// pull is a large download that would starve the drain of the connection the
/// queued sales are waiting for.
class OfflineSyncCubit extends Cubit<OfflineSyncState> {
  /// [_online] is the plain [SaleService], not the registered [SaleRepository]:
  /// that one is the offline-first decorator, and posting through it would
  /// re-queue a sale that is already queued.
  OfflineSyncCubit(this._online) : super(const OfflineSyncState()) {
    // A regained connection is a hint worth acting on, never the definition of
    // "online" — a till on shop wifi with a dead uplink reports connected. The
    // timer below is what actually guarantees progress.
    _connectivitySub = Connectivity().onConnectivityChanged.listen((results) {
      // A regained connection invalidates any backoff earned while offline.
      if (results.any((r) => r != ConnectivityResult.none)) {
        unawaited(drain(ignoreBackoff: true));
      }
    });
    // The strongest "we are back" signal in the app, and the reason the OS event
    // above is only a hint. `ConnectivityCubit` flips to online when a request
    // actually got an answer, which is proof of reach — where the OS event fires
    // on an interface appearing and never fires at all for the case that bites
    // hardest: a till that sat on shop wifi the whole time while the uplink was
    // down. Without this, recovery there waits for the timer, and a row that has
    // already failed once waits out its backoff on top of that.
    if (serviceLocator.isRegistered<ConnectivityCubit>()) {
      final connectivity = serviceLocator<ConnectivityCubit>();
      var wasOnline = connectivity.state.status == NetworkStatus.online;
      _connectivityCubitSub = connectivity.stream.listen((network) {
        final isOnline = network.status == NetworkStatus.online;
        // Only the transition. The cubit emits on `hasInterface` changes too, and
        // every successful request keeps it online — draining on each of those
        // would post the queue from inside its own drain.
        if (isOnline && !wasOnline) {
          unawaited(drain(ignoreBackoff: true));
          // A snapshot that went stale during the outage is worth re-taking now
          // rather than at the next six-hour check. No-ops when it is still fresh.
          unawaited(refreshCatalog());
        }
        wasOnline = isOnline;
      });
    }

    _timer = Timer.periodic(_interval, (_) => unawaited(drain()));
    _branchSub = serviceLocator<BranchCubit>().onBranchChanged.listen((_) {
      unawaited(refreshCatalog(force: true));
    });
  }

  static const Duration _interval = Duration(seconds: 60);

  /// How long a snapshot is considered current enough to skip a refresh.
  static const Duration _catalogMaxAge = Duration(hours: 6);

  /// Products fetched per request while snapshotting. This is the server's
  /// hard ceiling — `GetProductsRequest` validates `per_page` at `max:100` and
  /// 422s above it, which would leave the till with no snapshot at all.
  static const int _snapshotPageSize = 100;

  /// A backstop against a pathological catalog, NOT a page budget: the snapshot is
  /// meant to hold the whole catalog, because a product missing from it cannot be
  /// sold offline at all. At the server's 100-per-page ceiling this allows 50,000
  /// products, and hitting it is reported rather than passed off as a full snapshot
  /// (see [OfflineSyncState.catalogTruncated]).
  static const int _snapshotPageLimit = 500;

  /// Products, categories, payment methods, staff, customers, settings, photos.
  static const int _provisionSteps = 7;

  final SaleRepository _online;

  OutboxRepository get _outbox => serviceLocator<OutboxRepository>();
  LookupRepository get _lookup => serviceLocator<LookupRepository>();
  CatalogSnapshotRepository get _snapshot => serviceLocator<CatalogSnapshotRepository>();

  StreamSubscription<List<ConnectivityResult>>? _connectivitySub;
  StreamSubscription<ConnectivityState>? _connectivityCubitSub;
  StreamSubscription<int>? _branchSub;
  Timer? _timer;

  /// Guards against two drains overlapping — the periodic timer and a regained
  /// connection routinely fire together, and posting the same row twice would
  /// lean on the server's idempotency key for something this can just prevent.
  bool _draining = false;

  /// The same idea for the catalog pull, held outside `state` so it is set
  /// synchronously — a flag read from `state` across an await is not a mutex.
  bool _refreshingCatalog = false;

  /// And again for the photo pass, which the offline-data screen can start by
  /// hand while a refresh is already running one.
  bool _warmingPhotos = false;

  /// The real invoice numbers the current drain has collected.
  ///
  /// Worth keeping now that an acknowledged row is deleted immediately: the
  /// cashier's receipt says `OFF-7K2-0042`, and this is the only moment the app
  /// can tell them which invoice that turned into.
  final List<String> _syncedRefs = [];

  /// Recover rows abandoned mid-post by a previous run, then take stock. Called
  /// once from the app shell after sign-in.
  Future<void> bootstrap() async {
    await _outbox.resetStuckSyncing();
    // An acknowledged row is now deleted the moment the server confirms it, so
    // this only sweeps up rows left `synced` by a build that kept them for a
    // week. Zero rather than a window: there is nothing left for them to do.
    await _outbox.purgeSynced(keepFor: Duration.zero);
    await refresh();
    unawaited(refreshCatalog());
    unawaited(drain());
  }

  /// Re-read the outbox and the snapshot age without touching the network.
  Future<void> refresh() async {
    final rows = await _outbox.all();
    final branchId = serviceLocator<BranchCubit>().selectedId;
    final meta = branchId == null ? null : await _snapshot.meta(branchId);
    if (isClosed) return;
    emit(state.copyWith(rows: rows, catalogSyncedAt: meta?.syncedAt));
  }

  // ---- push: the outbox ----

  /// Post every unsynced sale, oldest first, one at a time.
  ///
  /// Sequential on purpose. These are the same customer's takings in the order
  /// they were rung up, and a serial drain means a failure stops at the sale it
  /// belongs to instead of scattering across a batch.
  /// [ignoreBackoff] retries every owed row immediately, whatever its backoff.
  /// Used when something changed for the better — a regained connection, the app
  /// coming back to the foreground — where waiting out a backoff earned while
  /// offline would strand the oldest sale for another fifteen minutes.
  Future<void> drain({bool ignoreBackoff = false}) async {
    if (_draining) return;
    _draining = true;
    _syncedRefs.clear();
    try {
      final queue = await _outbox.unsynced();
      if (queue.isEmpty) {
        await refresh();
        return;
      }
      emit(state.copyWith(status: DataFetchStatus.waiting, clearError: true));

      for (final row in queue) {
        if (isClosed) return;
        if (!ignoreBackoff && !_isDue(row)) continue;
        if (await _push(row)) continue;
        // Unreachable: every remaining row would fail the same way, so stop and
        // let the next tick try again rather than burning through the queue.
        if (state.errorMessage != null) break;
      }

      await refresh();
      if (isClosed) return;
      emit(state.copyWith(
        status: DataFetchStatus.success,
        lastSyncedCount: _syncedRefs.length,
        lastSyncedRefs: List.of(_syncedRefs),
      ));
    } catch (_) {
      // The outbox itself failed to read or write. Without this the emitted
      // `waiting` status would stick, and `draining` is what disables the Sync
      // button — the queue would look permanently busy and never recover.
      if (!isClosed) {
        emit(state.copyWith(
          status: DataFetchStatus.failed,
          errorMessage: AppStrings.somethingWentWrong,
        ));
      }
    } finally {
      _draining = false;
    }
  }

  /// Retry one row now, ignoring its backoff — the manual "Retry" button.
  ///
  /// Takes the same mutex as [drain]: a row that is already mid-post must not be
  /// posted a second time, and the two writes would otherwise clobber each other
  /// (both save a whole row built from their own stale snapshot).
  Future<void> retry(String clientUuid) async {
    if (_draining) return;
    _draining = true;
    _syncedRefs.clear();
    try {
      final row = await _outbox.byUuid(clientUuid);
      if (row == null ||
          row.status == PendingSaleStatus.synced ||
          row.status == PendingSaleStatus.syncing) {
        return;
      }
      await _push(row.copyWith(status: PendingSaleStatus.pending, clearError: true));
    } finally {
      _draining = false;
    }
    await refresh();
    if (isClosed) return;
    emit(state.copyWith(
      lastSyncedCount: _syncedRefs.length,
      lastSyncedRefs: List.of(_syncedRefs),
    ));
  }

  /// Correct a sale that is still sitting in the queue.
  ///
  /// This is the only way to edit an offline sale, and it edits the OUTBOX ROW —
  /// there is no server record to patch, and minting a second row would post both
  /// the wrong version and the right one. The key, the provisional reference and
  /// the capture time are all preserved by [PendingSale.replacing].
  ///
  /// Takes the drain mutex for the same reason [retry] does: a row mid-POST may be
  /// committing the version being replaced, and the edit would then describe a
  /// sale the server has already recorded differently. Returns false when there
  /// was nothing editable under [clientUuid], which is what the caller shows as
  /// "this sale has already synced".
  ///
  /// [soldBefore] is the quantities the queued version had taken off the cached
  /// shelf; they are handed back before the new ones are taken, or every edit
  /// would walk the cached figure further down.
  Future<bool> editPending(
    String clientUuid, {
    required Map<String, dynamic> payload,
    required Map<String, dynamic> saleJson,
    required Map<int, double> soldBefore,
  }) async {
    if (_draining) return false;
    _draining = true;
    PendingSale? updated;
    try {
      updated = await _outbox.replaceContent(
        clientUuid: clientUuid,
        payload: payload,
        saleJson: saleJson,
      );
      if (updated == null) return false;

      // Best-effort, and deliberately after the durable write: the corrected sale
      // is what must survive, and a cached stock figure that drifts is a cosmetic
      // problem next to that.
      final branchId = updated.branchId;
      if (branchId != null) {
        await _snapshot.restoreStock(branchId: branchId, byProductId: soldBefore);
        await _snapshot.reduceStock(
          branchId: branchId,
          soldByProductId: updated.soldQuantities,
        );
      }
    } catch (_) {
      // The correction may or may not have landed; `refresh` below re-reads the
      // row so the screen shows whichever version is actually stored.
      return updated != null;
    } finally {
      _draining = false;
    }

    await refresh();
    // The device may well be online again by now — a cashier correcting a queued
    // sale is often doing it precisely because they noticed the queue.
    unawaited(drain(ignoreBackoff: true));
    return true;
  }

  /// Forget a row a person has resolved another way. Only ever offered for a
  /// failed sale, and only behind a confirmation — this is the one operation
  /// here that can lose money that was taken.
  ///
  /// Only a settled row can be discarded. A `pending` row is still owed to the
  /// server and a `syncing` row has a request in flight that may already have
  /// committed — deleting either loses the takings.
  Future<void> discard(String clientUuid) async {
    final row = await _outbox.byUuid(clientUuid);
    if (row == null ||
        (row.status != PendingSaleStatus.failed && row.status != PendingSaleStatus.synced)) {
      return;
    }
    await _outbox.discard(clientUuid);
    await refresh();
  }

  /// Whether this row was taken by whoever is signed in now.
  ///
  /// No longer gates the drain — the payload carries the originating cashier and
  /// branch, and the server honours them after checking the claim, so any signed-in
  /// user can sync anyone's queue without misfiling it. Refusing to drain would
  /// strand real takings on the device until that cashier came back, which is the
  /// worse failure by a distance.
  ///
  /// Kept because the pending-sales screen still says whose sale a row is.
  bool ownedByCurrentSession(PendingSale row) {
    final userId = serviceLocator<AuthCubit>().user?.id;
    if (userId == null) return false;
    return row.userId.isEmpty || row.userId == userId;
  }

  /// Post [row], recording the outcome on it either way.
  /// Returns true when the server now holds the sale.
  Future<bool> _push(PendingSale row) async {
    try {
      await _outbox.save(row.copyWith(
        status: PendingSaleStatus.syncing,
        lastAttemptAt: DateTime.now(),
        attempts: row.attempts + 1,
      ));

      // The originating cashier rides along at push time rather than being baked
      // into the payload at capture: an online sale needs it (the token already
      // says who), and a queued one may well be drained by a different cashier
      // on a shared till.
      //
      // Only the cashier. The branch follows their own assignment server-side —
      // the row's `branchId` stays local, where it scopes the cached stock.
      // The provisional reference goes with it for the same reason, and it is
      // read off the row rather than the payload so a corrected sale still
      // carries the number already printed for the customer.
      //
      // This is the last moment it can be saved anywhere permanent: the row is
      // dropped a few lines below, and until the server has it, the number on the
      // customer's receipt belongs to nothing.
      final saved = await _online.createSale({
        ...row.payload,
        if (row.userId.isNotEmpty) 'clientUserId': int.tryParse(row.userId),
        if (row.provisionalRef.isNotEmpty) 'offlineRef': row.provisionalRef,
      });

      // Acknowledged, so the device's copy has done its job and is dropped.
      // The outbox exists to hold sales the server does not have yet; keeping
      // them afterwards means a second, permanently-staling record of a sale the
      // Sales list now serves properly — and the device is by definition online
      // at this moment, so nothing is lost by looking it up there instead.
      await _outbox.discard(row.clientUuid);
      _syncedRefs.add(saved.invoiceNo.isEmpty ? row.provisionalRef : saved.invoiceNo);
      return true;
    } on ApiException catch (e) {
      // Only a verdict on THIS sale is terminal. "Any 4xx" was too broad: 408,
      // 425 and 429 are explicitly retryable, 401 clears on re-login, and this
      // app's own LAN/Host-header setup makes a proxy 404 a real possibility.
      // Marking those failed is not merely a slow retry — `failed` is the only
      // state that offers the Discard button, so it invites someone to delete a
      // sale that would have synced fine.
      //
      // Note `SaleController::store()` answers 422 for every domain refusal, so
      // a genuine server-side transient can arrive here as a 422. That is the
      // known cost of leaving 422 terminal: a stock refusal must reach a person
      // rather than retry forever.
      const verdictCodes = {400, 403, 404, 409, 422};
      final terminal = verdictCodes.contains(e.statusCode);
      await _outbox.save(row.copyWith(
        status: terminal ? PendingSaleStatus.failed : PendingSaleStatus.pending,
        attempts: row.attempts + 1,
        lastAttemptAt: DateTime.now(),
        lastError: e.message,
      ));
      if (!terminal && !isClosed) emit(state.copyWith(errorMessage: e.message));
      return false;
    } catch (e) {
      // Never reached the server. Leave the row pending and stop the drain.
      await _outbox.save(row.copyWith(
        status: PendingSaleStatus.pending,
        attempts: row.attempts + 1,
        lastAttemptAt: DateTime.now(),
        lastError: AppStrings.somethingWentWrong,
      ));
      if (!isClosed) emit(state.copyWith(errorMessage: AppStrings.somethingWentWrong));
      return false;
    }
  }

  /// Exponential backoff on a row the server has already refused, capped so a
  /// long-failing sale is still retried a few times an hour rather than never.
  bool _isDue(PendingSale row) {
    if (row.status == PendingSaleStatus.pending && row.attempts == 0) return true;
    final last = row.lastAttemptAt;
    if (last == null) return true;
    final backoff = Duration(seconds: (30 * (1 << row.attempts.clamp(0, 6))).clamp(30, 900));
    return DateTime.now().difference(last) >= backoff;
  }

  // ---- pull: the catalog snapshot ----

  /// Re-take this branch's catalog snapshot when it is missing or stale.
  /// [force] re-takes it regardless — a branch switch, or a manual refresh.
  Future<void> refreshCatalog({bool force = false}) async {
    final branchId = serviceLocator<BranchCubit>().selectedId;
    // Set synchronously. Reading the flag off `state` left a window across the
    // `meta` await below in which boot and app-resume both started a full
    // download of the same catalog.
    if (branchId == null || _refreshingCatalog) return;
    _refreshingCatalog = true;
    try {
      if (!force) {
        final previous = await _snapshot.meta(branchId);
        if (previous != null && previous.age < _catalogMaxAge) {
          if (!isClosed) emit(state.copyWith(catalogSyncedAt: previous.syncedAt));
          return;
        }
      }

      emit(state.copyWith(catalogRefreshing: true));

      final products = <Map<String, dynamic>>[];
      var page = 1;
      var truncated = false;
      while (true) {
        final result = await _lookup.productsRaw(page: page, perPage: _snapshotPageSize);
        products.addAll(result.rows);
        if (result.currentPage >= result.lastPage || result.rows.isEmpty) break;
        if (page >= _snapshotPageLimit) {
          // Whatever was fetched is still written — a partial catalog beats none —
          // but the caller is told, because "some products cannot be sold offline"
          // must not look identical to a complete snapshot.
          truncated = true;
          break;
        }
        page++;
        _step('Products (${products.length})', 1, _provisionSteps);
      }

      _step('Products', 1, _provisionSteps);

      // Category lists are per type filter, and the grid offers all three, so
      // all three are cached. They are small — a few dozen rows each.
      final categories = <String, List<Category>>{};
      for (final type in <String?>[null, 'product', 'service']) {
        categories[type ?? ''] = await _lookup.categories(type: type);
      }
      _step('Categories', 2, _provisionSteps);

      // The branch is stamped on each request by HttpService at send time, not
      // captured up front, so a switch mid-download means the later pages are
      // the NEW branch's products — which would be written under the old
      // branch's key and sold from there. Abandon the run instead; the branch
      // listener has already queued a fresh one.
      if (serviceLocator<BranchCubit>().selectedId != branchId) return;

      // An empty result is far more likely to be a filtered/failed page than a
      // shop with nothing to sell, and `replace` would delete the working
      // catalog and stamp it fresh for six hours.
      final previous = await _snapshot.meta(branchId);
      if (products.isEmpty && (previous?.productCount ?? 0) > 0) return;

      await _snapshot.replace(
        branchId: branchId,
        products: products,
        categoriesByType: categories,
      );

      final missing = await _provisionLookups(branchId);

      // Last, and deliberately so: the photos are by far the largest download
      // and the only one the till can sell without. Everything above has already
      // landed by the time this starts, so a refresh cut short here leaves a
      // working catalog that merely looks plainer.
      await warmPhotos(branchId: branchId);

      final meta = await _snapshot.meta(branchId);
      if (isClosed) return;
      emit(state.copyWith(
        catalogRefreshing: false,
        catalogSyncedAt: meta?.syncedAt,
        provisionIncomplete: missing,
        catalogTruncated: truncated,
        clearProvisionStep: true,
      ));
    } on ApiException catch (e) {
      // The server answered and refused. Unlike being offline, this will not fix
      // itself, and a snapshot that never lands means the till silently has no
      // offline mode at all — so it is recorded rather than swallowed.
      if (!isClosed) {
        emit(state.copyWith(catalogRefreshing: false, errorMessage: e.message));
      }
    } catch (_) {
      // Offline. The previous snapshot is untouched and still sellable, and the
      // "catalog from …" chip already says how old it is.
      if (!isClosed) emit(state.copyWith(catalogRefreshing: false));
    } finally {
      _refreshingCatalog = false;
      // The early returns above (fresh enough, branch switched, empty page) skip
      // the success emit, so the flag is cleared here too or the UI would sit on
      // a spinner that never stops.
      if (!isClosed && state.catalogRefreshing) {
        emit(state.copyWith(catalogRefreshing: false));
      }
    }
  }

  /// Pre-download the catalog's product photos into [ImageStore].
  ///
  /// The rest of the snapshot makes a product *sellable* offline; this is what
  /// makes it *findable*. Flutter's image cache is memory-only and empty on
  /// every cold start, so without this pass an offline grid is a wall of tinted
  /// placeholders — the catalog is all there, and none of it is recognisable at
  /// a glance, which on a busy till means reading every label.
  ///
  /// Fetching a photo the first time it is shown cannot work for this: by then
  /// the network is already gone. It has to happen while there is still one.
  ///
  /// Never throws. A photo is the one part of the snapshot the till can trade
  /// without, so nothing here is allowed to fail a refresh that has already put
  /// a working catalog on the device.
  Future<void> warmPhotos({int? branchId}) async {
    final id = branchId ?? serviceLocator<BranchCubit>().selectedId;
    if (id == null) return;
    // The till's own choice, checked here rather than at the call sites so both
    // the automatic refresh and the settings screen honour it the same way.
    if (!serviceLocator<LocalStorageService>().offlineCachePhotos) return;
    if (_warmingPhotos) return;
    _warmingPhotos = true;
    try {
      final paths = await _snapshot.thumbnails(id);
      if (paths.isEmpty) {
        if (!isClosed) emit(state.copyWith(photosCached: 0, photosTotal: 0, photosBudgetHit: false));
        return;
      }
      final cfg = serviceLocator<AuthCubit>().config;
      _step('Product photos', 7, _provisionSteps);
      final result = await ImageStore.instance.warm(
        [for (final path in paths) cfg.assetUrl(path)],
        headers: cfg.assetHeaders,
        // A branch switch mid-download would spend the remaining budget on the
        // branch the till just left; the branch listener has already queued a
        // fresh refresh for the new one.
        shouldContinue: () =>
            !isClosed && serviceLocator<BranchCubit>().selectedId == id,
      );
      if (isClosed) return;
      emit(state.copyWith(
        photosCached: result.cached,
        photosTotal: paths.length,
        photosBudgetHit: result.stoppedOnBudget,
      ));
    } catch (_) {
      // Offline again, or no writable cache directory. The catalog itself is
      // already stored and sellable; only the pictures are missing.
    } finally {
      _warmingPhotos = false;
      // Cleared here rather than by the caller: `refreshCatalog` clears it on
      // its way out, but the offline-data screen calls this on its own, and a
      // step label left set would leave the first-run strip reading
      // "Preparing offline data" with nothing running behind it.
      if (!isClosed && state.provisionStep != null) {
        emit(state.copyWith(clearProvisionStep: true));
      }
    }
  }

  /// Everything besides the catalog that the New Sale flow cannot work without.
  ///
  /// Each is fetched independently and a failure is collected rather than
  /// thrown: losing the staff list should not also cost the payment methods, and
  /// the caller needs to be able to say precisely what is missing.
  ///
  /// Returns the human names of whatever could not be fetched.
  Future<List<String>> _provisionLookups(int branchId) async {
    final missing = <String>[];

    _step('Payment methods', 3, _provisionSteps);
    await _provision(
      label: 'Payment methods',
      missing: missing,
      fetch: () async {
        final methods = await _lookup.paymentMethods();
        await _snapshot.replaceLookups(
          branchId: branchId,
          kind: SnapshotLookup.paymentMethod,
          rows: [for (final m in methods) {'id': m.id, 'name': m.name}],
        );
      },
    );

    _step('Staff', 4, _provisionSteps);
    await _provision(
      label: 'Staff',
      missing: missing,
      fetch: () async {
        final staff = await _fetchAll(
          (page) => _lookup.employeesPage(page: page, branchId: branchId),
        );
        await _snapshot.replaceLookups(
          branchId: branchId,
          kind: SnapshotLookup.employee,
          rows: [
            for (final e in staff)
              {
                'id': e.id,
                'name': e.name,
                'code': e.code,
                'mobile': e.mobile,
                'designation': e.designation,
                // `Employee.fromJson` reads `photo` — under any other key the
                // cached staff come back offline with no avatar.
                'photo': e.photoUrl,
              },
          ],
        );
      },
    );

    _step('Customers', 5, _provisionSteps);
    await _provision(
      label: 'Customers',
      missing: missing,
      fetch: () async {
        final customers = await _fetchAll((page) => _lookup.customersPage(page: page));
        await _snapshot.replaceLookups(
          branchId: branchId,
          kind: SnapshotLookup.customer,
          rows: [for (final c in customers) {'id': c.id, 'name': c.name, 'mobile': c.mobile}],
        );
      },
    );

    // Settings, print options and currencies cache into prefs through their own
    // cubits. They are pulled here too because otherwise they arrive only when
    // some screen happens to open — a device that goes straight offline after
    // first sign-in would have none of them.
    _step('Settings', 6, _provisionSteps);
    await _provision(
      label: 'Sale settings',
      missing: missing,
      fetch: pullAndCacheSaleSettings,
    );
    await _provision(
      label: 'Currencies',
      missing: missing,
      fetch: () => serviceLocator<CurrencyCubit>().refreshCurrencies(),
    );

    return missing;
  }

  /// Every page of a paginated list, not just the first.
  ///
  /// This is the difference between a usable offline snapshot and a misleading one.
  /// The interactive lookups deliberately fetch a small page — 20 customers is the
  /// right answer for a search-as-you-type sheet — but caching 20 of a shop's 2,000
  /// customers means an offline till creates a duplicate account for almost every
  /// returning client it serves.
  ///
  /// Bounded by the same backstop the catalog uses, for the same reason.
  Future<List<T>> _fetchAll<T>(Future<Paginated<T>> Function(int page) fetchPage) async {
    final rows = <T>[];
    var page = 1;
    while (page <= _snapshotPageLimit) {
      final result = await fetchPage(page);
      rows.addAll(result.items);
      if (result.currentPage >= result.lastPage || result.items.isEmpty) break;
      page++;
    }
    return rows;
  }

  /// Run one provisioning step, recording [label] when it fails rather than
  /// letting one missing list abort the rest.
  Future<void> _provision({
    required String label,
    required List<String> missing,
    required Future<void> Function() fetch,
  }) async {
    try {
      await fetch();
    } catch (_) {
      missing.add(label);
    }
  }

  void _step(String label, int done, int total) {
    if (isClosed) return;
    emit(state.copyWith(provisionStep: label, provisionDone: done, provisionTotal: total));
  }

  @override
  Future<void> close() {
    _timer?.cancel();
    _connectivitySub?.cancel();
    _connectivityCubitSub?.cancel();
    _branchSub?.cancel();
    return super.close();
  }
}
