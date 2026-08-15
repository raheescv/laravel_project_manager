import 'dart:typed_data';

import 'package:invo/features/auth/logic/auth_cubit/auth_cubit.dart';
import 'package:invo/shared/domain/constants/global_variables.dart';
import 'package:invo/shared/domain/models/index.dart';
import 'package:invo/shared/domain/repository/catalog_snapshot_repository.dart';
import 'package:invo/shared/logic/branch_cubit/branch_cubit.dart';
import 'package:invo/shared/logic/connectivity_cubit/connectivity_cubit.dart';
import 'package:invo/shared/utils/router/http_utils/reachability.dart';

import '../repository/outbox_repository.dart';
import '../repository/sale_repository.dart';

/// Wraps the live [SaleRepository] so a sale survives a dead network.
///
/// Only [createSale] changes behaviour. Everything else — listing, viewing,
/// editing, deleting — needs a server id and is passed straight through, which
/// is also why editing a queued sale is not offered: there is nothing to edit
/// against until it has synced.
///
/// The rule for queueing is deliberately narrow. A sale is queued only when the
/// request never reached the server ([_isUnreachable]); anything the server
/// answered — a validation failure, a rejected payment method, an expired token
/// — is a real answer and is surfaced to the cashier as it always was. Queuing
/// on every error would silently swallow refusals and replay them forever.
class OfflineFirstSaleService implements SaleRepository {
  OfflineFirstSaleService(this._online);

  final SaleRepository _online;

  OutboxRepository get _outbox => serviceLocator<OutboxRepository>();
  CatalogSnapshotRepository get _snapshot => serviceLocator<CatalogSnapshotRepository>();

  @override
  Future<Sale> createSale(Map<String, dynamic> payload, {Map<String, dynamic>? offlineSale}) async {
    final clientUuid = payload['clientUuid'] as String?;

    // Don't make the cashier wait out a timeout the app already knows the answer
    // to. `connectTimeout` is 20s, and a charge that has to expire it before the
    // ticket is captured feels like the till has hung at the worst possible moment —
    // with the customer standing there and the cash already counted.
    //
    // Only taken when offline capture is actually possible, so a draft or an edit
    // still gets the real attempt and the real error.
    if (offlineSale != null && clientUuid != null && _knownUnreachable()) {
      return _capture(clientUuid: clientUuid, payload: payload, offlineSale: offlineSale);
    }

    try {
      return await _online.createSale(payload);
    } catch (e) {
      // No key or no receipt-ready snapshot means the caller never opted into
      // offline capture (a draft, or an edit). Those stay online-only.
      if (offlineSale == null || clientUuid == null || !isServerUnreachable(e)) rethrow;

      return _capture(clientUuid: clientUuid, payload: payload, offlineSale: offlineSale);
    }
  }

  /// Whether the app already knows a request cannot land.
  ///
  /// Two cases, both self-correcting: the OS reporting no network interface is
  /// conclusive — a request has nowhere to go — and an `offline` status means the
  /// last request that got a definite answer failed to reach the server. The 60s
  /// drain keeps probing, so a status at most a minute stale flips back to online
  /// the moment anything gets through, and the next charge goes over the network
  /// again.
  ///
  /// The trade-off is deliberate: within that window a sale that *could* have gone
  /// online is captured instead, so the customer gets a provisional reference and the
  /// sale syncs seconds later. That is a far better outcome than making every cashier
  /// on a downed network wait out a 20-second timeout at the till.
  bool _knownUnreachable() {
    final network = _resolve<ConnectivityCubit>();
    if (network == null) return false;
    return !network.state.hasInterface || network.state.isOffline;
  }

  /// Durably capture the sale on this device and hand back a receipt-ready [Sale].
  ///
  /// Reached either because the POST failed as unreachable, or because the app
  /// already knew it would — the capture itself is identical, and must be, or the two
  /// paths would queue subtly different rows.
  Future<Sale> _capture({
    required String clientUuid,
    required Map<String, dynamic> payload,
    required Map<String, dynamic> offlineSale,
  }) async {
    // Resolved leniently on purpose. These two only stamp attribution onto the
    // queued row; letting a missing registration throw would abort the capture
    // and report a sale the till has taken money for as failed.
    final branchId = _resolve<BranchCubit>()?.selectedId;
    final queued = await _outbox.enqueue(
      clientUuid: clientUuid,
      payload: payload,
      saleJson: offlineSale,
      userId: _resolve<AuthCubit>()?.user?.id ?? '',
      branchId: branchId,
    );

    // Past this line the sale is durably captured, so nothing may throw its
    // way back out to the caller — a throw here would surface as "could not
    // save the sale" over a sale that IS saved, and the cashier would ring it
    // up a second time.
    try {
      // Take the goods off the cached shelf so the next ticket sees a
      // realistic figure. Best-effort by design.
      //
      // Not for a draft: nothing has left the shop, and decrementing here would
      // hide stock that is still on the shelf and sellable to the next customer.
      if (branchId != null && !queued.isDraft) {
        await _snapshot.reduceStock(branchId: branchId, soldByProductId: queued.soldQuantities);
      }
      // The provisional reference is only known once the row is written, so
      // the returned sale carries it in place of the invoice number. It is also
      // set as the offline reference, so this sale reads the same way before and
      // after syncing: the number on the customer's receipt is always there.
      return Sale.fromJson({
        ...offlineSale,
        'invoice_no': queued.provisionalRef,
        'reference_no': queued.provisionalRef,
      });
    } catch (_) {
      return Sale.fromJson(offlineSale);
    }
  }

  T? _resolve<T extends Object>() =>
      serviceLocator.isRegistered<T>() ? serviceLocator<T>() : null;

  // ---- pass-through: every one of these needs the server ----

  @override
  Future<SalesPage> sales({
    String? status,
    String? search,
    int? paymentMethodId,
    String? fromDate,
    String? toDate,
    String sortBy = 'date',
    String sortDirection = 'desc',
    bool mineOnly = false,
    int? createdById,
    int page = 1,
    int perPage = 30,
  }) =>
      _online.sales(
        status: status,
        search: search,
        paymentMethodId: paymentMethodId,
        fromDate: fromDate,
        toDate: toDate,
        sortBy: sortBy,
        sortDirection: sortDirection,
        mineOnly: mineOnly,
        createdById: createdById,
        page: page,
        perPage: perPage,
      );

  @override
  Future<Sale> saleById(String id) => _online.saleById(id);

  @override
  Future<Uint8List> saleReceiptPdf(String id) => _online.saleReceiptPdf(id);

  @override
  Future<Sale> updateSale(String id, Map<String, dynamic> payload) => _online.updateSale(id, payload);

  @override
  Future<void> deleteSale(String id) => _online.deleteSale(id);
}
