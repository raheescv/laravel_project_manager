import 'package:flutter/material.dart';
import 'package:flutter_bloc/flutter_bloc.dart';
import 'package:go_router/go_router.dart';
import 'package:intl/intl.dart';

import 'package:invo/features/sale/logic/offline_sync_cubit/offline_sync_cubit.dart';
import 'package:invo/features/settings/logic/offline_data_cubit/offline_data_cubit.dart';
import 'package:invo/shared/domain/constants/global_variables.dart';
import 'package:invo/shared/domain/helpers/responsive.dart';
import 'package:invo/shared/domain/repository/catalog_snapshot_repository.dart';
import 'package:invo/shared/logic/connectivity_cubit/connectivity_cubit.dart';
import 'package:invo/shared/utils/components/theme/index.dart';
import 'package:invo/shared/utils/local_storage/image_store.dart';
import 'package:invo/shared/utils/local_storage/local_storage_service.dart';
import 'package:invo/shared/widgets/astra_widgets.dart';

/// What this till can sell without a network, and the controls to change it.
///
/// The offline layer was entirely implicit before this screen existed: it
/// refreshed on boot, on a branch switch and every six hours, and reported
/// itself only through a banner that appears when something has already gone
/// wrong. There was no way to check the till was ready *before* taking it
/// somewhere with no signal, and no way to make it ready on demand — which is
/// precisely the moment anyone would think to ask.
class OfflineDataScreen extends StatelessWidget {
  const OfflineDataScreen({super.key, this.embedded = false});

  /// Rendered inside the tablet Settings detail pane rather than pushed as its
  /// own page: body only, no scaffold, no header, no back arrow. The cubit and
  /// the sync listener still come with it — this is the screen, hosted.
  final bool embedded;

  @override
  Widget build(BuildContext context) => BlocProvider(
        create: (_) => OfflineDataCubit()..load(),
        child: _OfflineDataView(embedded: embedded),
      );
}

class _OfflineDataView extends StatelessWidget {
  const _OfflineDataView({this.embedded = false});

  final bool embedded;

  static final NumberFormat _count = NumberFormat.decimalPattern();

  OfflineSyncCubit get _sync => serviceLocator<OfflineSyncCubit>();

  @override
  Widget build(BuildContext context) {
    return BlocListener<OfflineSyncCubit, OfflineSyncState>(
      bloc: _sync,
      // Re-read the device whenever a sync lands. The counts come off the
      // snapshot, not out of the sync's own report, so nothing else would tell
      // this screen that the numbers underneath it just changed.
      listenWhen: (a, b) =>
          a.catalogSyncedAt != b.catalogSyncedAt ||
          a.photosCached != b.photosCached ||
          (a.catalogRefreshing && !b.catalogRefreshing),
      listener: (context, _) => context.read<OfflineDataCubit>().load(),
      child: embedded
          ? BlocBuilder<OfflineDataCubit, OfflineDataState>(
              builder: (context, data) => BlocBuilder<OfflineSyncCubit, OfflineSyncState>(
                bloc: _sync,
                builder: (context, sync) => _body(context, data, sync),
              ),
            )
          : Scaffold(
        backgroundColor: Colors.transparent,
        body: AstraBackground(
          child: Column(
            children: [
              EmeraldHeader(
                title: 'Offline Data',
                subtitle: 'What this till can sell without a network',
                leading: HeaderIconButton(icon: Icons.arrow_back, onTap: () => context.pop()),
              ),
              Expanded(
                child: MaxWidthBox(
                  maxWidth: 560,
                  child: BlocBuilder<OfflineDataCubit, OfflineDataState>(
                    builder: (context, data) => BlocBuilder<OfflineSyncCubit, OfflineSyncState>(
                      bloc: _sync,
                      builder: (context, sync) => _body(context, data, sync),
                    ),
                  ),
                ),
              ),
            ],
          ),
        ),
      ),
    );
  }

  Widget _body(BuildContext context, OfflineDataState data, OfflineSyncState sync) {
    final storage = serviceLocator<LocalStorageService>();
    return ListView(
      shrinkWrap: embedded,
      physics: embedded ? const NeverScrollableScrollPhysics() : null,
      padding: embedded ? EdgeInsets.zero : const EdgeInsets.fromLTRB(16, 16, 16, 40),
      children: [
        _statusCard(context, data, sync),
        const SizedBox(height: 18),
        ..._warnings(context, sync),
        const SectionLabel('Stored on this device'),
        const SizedBox(height: 8),
        _countRow(context, Icons.inventory_2_outlined, 'Products', data.products,
            'Everything sellable, with prices and stock figures'),
        const SizedBox(height: 9),
        _countRow(context, Icons.category_outlined, 'Categories', data.categories,
            'The filters across the top of the catalog'),
        const SizedBox(height: 9),
        _countRow(context, Icons.payments_outlined, 'Payment methods', data.paymentMethods,
            'Without these a split payment can only be cash or card'),
        const SizedBox(height: 9),
        _countRow(context, Icons.badge_outlined, 'Staff', data.employees,
            'For assigning a ticket or a line to someone'),
        const SizedBox(height: 9),
        _countRow(context, Icons.people_outline, 'Customers', data.customers,
            'So a returning client is found rather than created twice'),
        const SizedBox(height: 9),
        _photoRow(context, data, sync),
        const SizedBox(height: 20),
        const SectionLabel('Product photos'),
        const SizedBox(height: 8),
        _photoToggle(context, storage, data),
        const SizedBox(height: 20),
        const SectionLabel('Actions'),
        const SizedBox(height: 10),
        _syncButton(context, sync),
        const SizedBox(height: 10),
        _clearButton(context, data),
        const SizedBox(height: 14),
        _footnote(context),
      ],
    );
  }

  // ---- status ----

  Widget _statusCard(BuildContext context, OfflineDataState data, OfflineSyncState sync) {
    final p = context.astra;
    final offline = serviceLocator.isRegistered<ConnectivityCubit>() &&
        serviceLocator<ConnectivityCubit>().state.isOffline;
    final (colour, icon, title, detail) = _status(data, sync, offline: offline);

    return AstraCard(
      radius: 16,
      padding: const EdgeInsets.all(16),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Row(
            children: [
              IconChip(icon: icon, size: 44, radius: 14, bg: colour.withValues(alpha: 0.14), fg: colour),
              const SizedBox(width: 12),
              Expanded(
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    Text(title, style: ui(size: 14.5, weight: FontWeight.w800, color: p.ink)),
                    const SizedBox(height: 2),
                    Text(detail, style: ui(size: 11, weight: FontWeight.w600, color: p.textMuted)),
                  ],
                ),
              ),
            ],
          ),
          if (sync.catalogRefreshing) ...[
            const SizedBox(height: 14),
            ClipRRect(
              borderRadius: BorderRadius.circular(3),
              child: LinearProgressIndicator(
                value: sync.provisionTotal == 0 ? null : sync.provisionProgress,
                minHeight: 4,
                backgroundColor: p.hairline,
                valueColor: AlwaysStoppedAnimation(p.primary),
              ),
            ),
            const SizedBox(height: 7),
            Text(
              sync.provisionStep == null
                  ? 'Downloading…'
                  : '${sync.provisionStep} · step ${sync.provisionDone} of ${sync.provisionTotal}',
              style: ui(size: 10.5, weight: FontWeight.w700, color: p.textMuted),
            ),
          ],
        ],
      ),
    );
  }

  /// The one-line verdict. Ordered by what would stop the till trading first:
  /// a wipe in progress, then nothing downloaded at all, then a refresh running,
  /// then how old what it has is.
  (Color, IconData, String, String) _status(
    OfflineDataState data,
    OfflineSyncState sync, {
    required bool offline,
  }) {
    if (data.clearing) {
      return (AstraPalette.danger, Icons.delete_sweep_outlined, 'Clearing…',
          'Removing everything cached on this device');
    }
    if (data.isEmpty) {
      return (
        AstraPalette.danger,
        Icons.cloud_off_rounded,
        'Not prepared',
        offline
            ? 'Nothing is stored, and there is no connection to fetch it. This till cannot sell offline.'
            : 'Nothing is stored yet. Sync now to make this till usable without a network.',
      );
    }
    if (sync.catalogRefreshing) {
      return (const Color(0xFF1F5C7A), Icons.cloud_download_outlined, 'Preparing…',
          'The catalog on this device is still usable while this runs');
    }
    final synced = 'Last synced ${catalogAgeLabel(data.syncedAt)}';
    return switch (CatalogFreshness.of(
        data.syncedAt == null ? null : DateTime.now().difference(data.syncedAt!))) {
      CatalogFreshness.stale => (
          const Color(0xFF8A3A1F),
          Icons.sync_problem_rounded,
          'Out of date',
          '$synced — prices and stock may have moved. Sync before selling.',
        ),
      CatalogFreshness.aging => (
          const Color(0xFF8A6A1F),
          Icons.schedule_rounded,
          'Ready, but ageing',
          '$synced — worth a refresh before going offline.',
        ),
      CatalogFreshness.fresh => (
          const Color(0xFF1E7A5F),
          Icons.check_circle_outline_rounded,
          'Ready to sell offline',
          synced,
        ),
    };
  }

  List<Widget> _warnings(BuildContext context, OfflineSyncState sync) {
    final items = <Widget>[
      if (sync.provisionIncomplete.isNotEmpty)
        _warning(context, Icons.warning_amber_rounded,
            '${sync.provisionIncomplete.join(", ")} didn’t download. Reconnect and sync to finish.'),
      if (sync.catalogTruncated)
        _warning(context, Icons.filter_alt_outlined,
            'The catalog was too large to store in full. Products beyond the limit can’t be sold offline.'),
      if (sync.photosBudgetHit)
        _warning(context, Icons.sd_storage_outlined,
            'Photo storage is full, so the rest of the catalog has no pictures. Everything is still sellable.'),
    ];
    return items.isEmpty ? const [] : [...items, const SizedBox(height: 8)];
  }

  Widget _warning(BuildContext context, IconData icon, String text) {
    final p = context.astra;
    const amber = Color(0xFF8A6A1F);
    return Padding(
      padding: const EdgeInsets.only(bottom: 9),
      child: Container(
        padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 11),
        decoration: BoxDecoration(
          color: amber.withValues(alpha: p.isDark ? 0.18 : 0.10),
          borderRadius: BorderRadius.circular(13),
          border: Border.all(color: amber.withValues(alpha: 0.3)),
        ),
        child: Row(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Icon(icon, size: 15, color: amber),
            const SizedBox(width: 9),
            Expanded(
              child: Text(text, style: ui(size: 11, weight: FontWeight.w600, color: p.ink)),
            ),
          ],
        ),
      ),
    );
  }

  // ---- rows ----

  Widget _countRow(BuildContext context, IconData icon, String title, int value, String subtitle) {
    final p = context.astra;
    // Zero is the one figure worth colouring: every other number on this screen
    // is reassurance, and a zero is the thing that will fail mid-shift.
    final missing = value == 0;
    return AstraCard(
      radius: 14,
      child: Row(
        children: [
          IconChip(icon: icon, size: 32, radius: 9, bg: p.tint),
          const SizedBox(width: 11),
          Expanded(
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Text(title, style: ui(size: 12.5, weight: FontWeight.w700, color: p.ink)),
                Text(subtitle,
                    maxLines: 2,
                    overflow: TextOverflow.ellipsis,
                    style: ui(size: 10, weight: FontWeight.w600, color: p.textMuted)),
              ],
            ),
          ),
          const SizedBox(width: 8),
          Text(
            missing ? 'None' : _count.format(value),
            style: ui(
              size: missing ? 11.5 : 14,
              weight: FontWeight.w800,
              color: missing ? AstraPalette.danger : p.ink,
            ),
          ),
        ],
      ),
    );
  }

  Widget _photoRow(BuildContext context, OfflineDataState data, OfflineSyncState sync) {
    final subtitle = data.photoFiles == 0
        ? 'Without these the offline grid is a wall of blank tiles'
        : '${data.photoSizeLabel} of ${ImageStore.maxBytes ~/ (1024 * 1024)} MB used'
            '${sync.photosTotal > 0 ? ' · ${_count.format(sync.photosTotal)} in the catalog' : ''}';
    return _countRow(context, Icons.image_outlined, 'Product photos', data.photoFiles, subtitle);
  }

  Widget _photoToggle(BuildContext context, LocalStorageService storage, OfflineDataState data) {
    final p = context.astra;
    final on = storage.offlineCachePhotos;
    return AstraCard(
      radius: 14,
      onTap: () async {
        await storage.setOfflineCachePhotos(!on);
        if (!context.mounted) return;
        // Rebuilt through the cubit rather than a local setState: the pref is
        // read back out of storage on the next build, so the screen has to be
        // told to look again.
        await context.read<OfflineDataCubit>().load();
        // Turning it on with a catalog already stored would otherwise do nothing
        // until the next six-hourly refresh — which is not what tapping a switch
        // labelled "cache photos" is asking for.
        if (!on && context.mounted) unawaitedWarm(context);
      },
      child: Row(
        children: [
          IconChip(icon: Icons.photo_library_outlined, size: 32, radius: 9, bg: p.tint),
          const SizedBox(width: 11),
          Expanded(
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Text('Cache product photos',
                    style: ui(size: 12.5, weight: FontWeight.w700, color: p.ink)),
                Text(
                  on
                      ? 'Downloaded with the catalog so the grid still looks right offline'
                      : 'Off — photos already stored are kept; clear offline data to free the space',
                  maxLines: 2,
                  overflow: TextOverflow.ellipsis,
                  style: ui(size: 10, weight: FontWeight.w600, color: p.textMuted),
                ),
              ],
            ),
          ),
          _switch(context, on),
        ],
      ),
    );
  }

  void unawaitedWarm(BuildContext context) {
    _sync.warmPhotos().then((_) {
      if (context.mounted) context.read<OfflineDataCubit>().load();
    });
  }

  Widget _switch(BuildContext context, bool value) {
    final p = context.astra;
    return AnimatedContainer(
      duration: const Duration(milliseconds: 160),
      width: 44,
      height: 26,
      padding: const EdgeInsets.all(3),
      alignment: value ? Alignment.centerRight : Alignment.centerLeft,
      decoration: BoxDecoration(
        gradient: value ? p.primaryGradient : null,
        color: value ? null : p.hairline,
        borderRadius: BorderRadius.circular(20),
      ),
      child: Container(
        width: 20,
        height: 20,
        decoration: const BoxDecoration(color: Colors.white, shape: BoxShape.circle),
      ),
    );
  }

  // ---- actions ----

  Widget _syncButton(BuildContext context, OfflineSyncState sync) => AstraButton(
        label: sync.catalogRefreshing ? 'Syncing…' : 'Sync now',
        icon: Icons.cloud_download_outlined,
        busy: sync.catalogRefreshing,
        // `force` skips the six-hour freshness check. Someone who opened this
        // screen and pressed this button is not asking whether a refresh is due.
        onTap: sync.catalogRefreshing ? null : () => _sync.refreshCatalog(force: true),
      );

  Widget _clearButton(BuildContext context, OfflineDataState data) {
    final p = context.astra;
    final busy = data.clearing;
    return GestureDetector(
      onTap: busy ? null : () => _confirmClear(context),
      child: AstraCard(
        radius: 14,
        child: Center(
          child: Row(
            mainAxisSize: MainAxisSize.min,
            children: [
              Icon(busy ? Icons.hourglass_empty : Icons.delete_outline,
                  size: 16, color: busy ? p.textMuted : AstraPalette.danger),
              const SizedBox(width: 8),
              Text(
                busy ? 'Clearing…' : 'Clear offline data',
                style: ui(
                  size: 12.5,
                  weight: FontWeight.w700,
                  color: busy ? p.textMuted : AstraPalette.danger,
                ),
              ),
            ],
          ),
        ),
      ),
    );
  }

  Future<void> _confirmClear(BuildContext context) async {
    final cubit = context.read<OfflineDataCubit>();
    final ok = await showDialog<bool>(
      context: context,
      builder: (ctx) => AlertDialog(
        title: const Text('Clear offline data?'),
        content: const Text(
          'The stored catalog, reference lists and product photos are removed from '
          'this device. Until the next sync it won’t be able to sell without a '
          'network.\n\nSales waiting to be synced are not affected.',
        ),
        actions: [
          TextButton(onPressed: () => Navigator.pop(ctx, false), child: const Text('Cancel')),
          TextButton(onPressed: () => Navigator.pop(ctx, true), child: const Text('Clear')),
        ],
      ),
    );
    if (ok == true) await cubit.clear();
  }

  Widget _footnote(BuildContext context) {
    final p = context.astra;
    return Padding(
      padding: const EdgeInsets.symmetric(horizontal: 4),
      child: Text(
        'Offline data refreshes on its own when the app opens, when the branch '
        'changes, and every few hours while connected.',
        style: ui(size: 10.5, weight: FontWeight.w600, color: p.textMuted),
      ),
    );
  }
}
