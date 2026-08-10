import 'package:flutter/material.dart';
import 'package:flutter_bloc/flutter_bloc.dart';
import 'package:invo/features/sale/logic/offline_sync_cubit/offline_sync_cubit.dart';
import 'package:invo/shared/domain/constants/global_variables.dart';
import 'package:invo/shared/domain/repository/catalog_snapshot_repository.dart';
import 'package:invo/shared/logic/connectivity_cubit/connectivity_cubit.dart';
import 'package:invo/shared/utils/components/theme/index.dart';

/// App-wide strip that says the till is running offline.
///
/// Sits above every screen rather than on the POS alone: a cashier who cannot
/// see it has no way to know a sale was held on the device instead of banked,
/// and the first they'd learn of it is a missing invoice.
///
/// Only ever shown once something is actually known — see [NetworkStatus.unknown].
/// Wraps the whole app so the strip sits above every screen.
///
/// It takes the [child] rather than being placed beside it because only this
/// widget knows whether a strip is currently visible — and when one is, it has
/// already consumed the status-bar inset, so the page beneath must not inset
/// again or the entire UI shifts down twice.
class OfflineBanner extends StatelessWidget {
  const OfflineBanner({super.key, required this.child, this.onTapPending});

  final Widget child;

  /// Opens the pending-sales screen. Null on screens with no router above them
  /// (the strip is then plain text).
  final VoidCallback? onTapPending;

  @override
  Widget build(BuildContext context) {
    if (!serviceLocator.isRegistered<ConnectivityCubit>()) return child;

    return BlocBuilder<ConnectivityCubit, ConnectivityState>(
      bloc: serviceLocator<ConnectivityCubit>(),
      builder: (context, network) {
        if (network.isOffline) {
          return _WithStrip(
            strip: _Strip(hasInterface: network.hasInterface, onTapPending: onTapPending),
            child: child,
          );
        }
        return _ProvisioningStrip(child: child);
      },
    );
  }
}

/// Lays a visible strip above [child] and stops the page double-insetting.
class _WithStrip extends StatelessWidget {
  const _WithStrip({required this.strip, required this.child});

  final Widget? strip;
  final Widget child;

  @override
  Widget build(BuildContext context) {
    final visible = strip != null;
    return Column(
      children: [
        // Animated so the strip slides the page down rather than snapping it,
        // which on a POS reads as a layout glitch mid-tap.
        AnimatedSize(
          duration: const Duration(milliseconds: 220),
          curve: Curves.easeOut,
          alignment: Alignment.topCenter,
          child: strip ?? const SizedBox(width: double.infinity),
        ),
        Expanded(
          child: MediaQuery.removePadding(
            context: context,
            removeTop: visible,
            child: child,
          ),
        ),
      ],
    );
  }
}

/// Shown while the device is stocking itself for the first time, and afterwards
/// if anything failed to arrive.
///
/// Both matter because the screen underneath looks ready either way. A cashier
/// who starts selling against a half-built snapshot has no way to tell it is
/// half-built — the grid simply has fewer products in it.
class _ProvisioningStrip extends StatelessWidget {
  const _ProvisioningStrip({required this.child});

  final Widget child;

  @override
  Widget build(BuildContext context) {
    if (!serviceLocator.isRegistered<OfflineSyncCubit>()) return child;
    return BlocBuilder<OfflineSyncCubit, OfflineSyncState>(
      bloc: serviceLocator<OfflineSyncCubit>(),
      buildWhen: (a, b) =>
          a.provisionStep != b.provisionStep ||
          a.provisionDone != b.provisionDone ||
          a.provisionIncomplete != b.provisionIncomplete ||
          a.catalogSyncedAt != b.catalogSyncedAt ||
          a.hasCatalog != b.hasCatalog,
      builder: (context, sync) {
        // Only the FIRST run gets the progress strip. A routine refresh happens
        // behind a catalog that already works, and announcing it would train
        // people to ignore the strip.
        if (sync.provisioning && !sync.hasCatalog) {
          return _WithStrip(
            strip: _Banner(
              colour: const Color(0xFF1F5C7A),
              icon: Icons.cloud_download_outlined,
              text: 'Preparing offline data — ${sync.provisionStep} '
                  '(${sync.provisionDone} of ${sync.provisionTotal})',
              progress: sync.provisionProgress,
            ),
            child: child,
          );
        }
        if (sync.provisionIncomplete.isNotEmpty) {
          return _WithStrip(
            strip: _Banner(
              colour: const Color(0xFF8A6A1F),
              icon: Icons.warning_amber_rounded,
              text: 'Offline data incomplete: ${sync.provisionIncomplete.join(", ")} '
                  'didn’t download. Reconnect to finish.',
            ),
            child: child,
          );
        }
        // Reachable but the snapshot is old anyway, which means the refresh itself
        // keeps failing — the till looks fine and is quietly selling from figures
        // nobody has checked in a day. Worth saying even while online, because
        // being online is precisely why nobody would think to look.
        if (sync.catalogFreshness == CatalogFreshness.stale) {
          return _WithStrip(
            strip: _Banner(
              colour: const Color(0xFF8A3A1F),
              icon: Icons.sync_problem_rounded,
              text: 'Catalog last updated ${catalogAgeLabel(sync.catalogSyncedAt)}. '
                  'Prices and stock may be out of date.',
            ),
            child: child,
          );
        }
        return _WithStrip(strip: null, child: child);
      },
    );
  }
}

/// The shared strip chrome — a coloured full-width bar with an optional
/// progress line under it.
class _Banner extends StatelessWidget {
  const _Banner({
    required this.colour,
    required this.icon,
    required this.text,
    this.progress,
  });

  final Color colour;
  final IconData icon;
  final String text;
  final double? progress;

  @override
  Widget build(BuildContext context) {
    return Material(
      color: colour,
      child: SafeArea(
        bottom: false,
        child: Column(
          mainAxisSize: MainAxisSize.min,
          children: [
            Padding(
              padding: const EdgeInsets.symmetric(horizontal: 14, vertical: 7),
              child: Row(
                mainAxisAlignment: MainAxisAlignment.center,
                children: [
                  Icon(icon, size: 14, color: Colors.white),
                  const SizedBox(width: 8),
                  Flexible(
                    child: Text(
                      text,
                      textAlign: TextAlign.center,
                      overflow: TextOverflow.ellipsis,
                      style: ui(size: 11, weight: FontWeight.w700, color: Colors.white),
                    ),
                  ),
                ],
              ),
            ),
            if (progress != null)
              LinearProgressIndicator(
                value: progress,
                minHeight: 2,
                backgroundColor: Colors.white.withValues(alpha: 0.2),
                valueColor: const AlwaysStoppedAnimation(Colors.white),
              ),
          ],
        ),
      ),
    );
  }
}

class _Strip extends StatelessWidget {
  const _Strip({required this.hasInterface, this.onTapPending});

  final bool hasInterface;
  final VoidCallback? onTapPending;

  @override
  Widget build(BuildContext context) {
    if (!serviceLocator.isRegistered<OfflineSyncCubit>()) {
      return _bar(context, CatalogFreshness.fresh, null);
    }
    // The snapshot's age is folded into this one strip rather than stacked as a
    // second bar: two strips on a phone POS push the ticket off the screen, and
    // the cashier only needs one line to know both facts.
    return BlocBuilder<OfflineSyncCubit, OfflineSyncState>(
      bloc: serviceLocator<OfflineSyncCubit>(),
      buildWhen: (a, b) => a.catalogSyncedAt != b.catalogSyncedAt,
      builder: (context, sync) => _bar(context, sync.catalogFreshness, sync.catalogSyncedAt),
    );
  }

  Widget _bar(BuildContext context, CatalogFreshness freshness, DateTime? syncedAt) {
    // Amber rather than red: offline is a state to be aware of, not a failure.
    // Sales still complete — red would say something is broken. Fixed rather
    // than preset-derived so it reads the same on all five themes, light or
    // dark; white-on-amber holds contrast either way.
    //
    // A stale snapshot does earn the redder shade, because by then the figures
    // on screen are the thing to be careful about, not the connection. Selling
    // is still never blocked — see [CatalogFreshness].
    final background = freshness == CatalogFreshness.stale
        ? const Color(0xFF8A3A1F)
        : const Color(0xFF8A6A1F);

    return Material(
      color: background,
      child: SafeArea(
        bottom: false,
        child: InkWell(
          onTap: onTapPending,
          child: Padding(
            padding: const EdgeInsets.symmetric(horizontal: 14, vertical: 7),
            child: Row(
              mainAxisAlignment: MainAxisAlignment.center,
              children: [
                Icon(
                  freshness == CatalogFreshness.stale
                      ? Icons.warning_amber_rounded
                      : Icons.cloud_off_rounded,
                  size: 14,
                  color: Colors.white,
                ),
                const SizedBox(width: 8),
                Flexible(
                  child: Text(
                    _text(freshness, syncedAt),
                    textAlign: TextAlign.center,
                    overflow: TextOverflow.ellipsis,
                    style: ui(size: 11, weight: FontWeight.w700, color: Colors.white),
                  ),
                ),
                const _PendingCount(),
              ],
            ),
          ),
        ),
      ),
    );
  }

  String _text(CatalogFreshness freshness, DateTime? syncedAt) {
    // "No network" and "can't reach the server" are different problems with
    // different fixes — turn wifi on, versus go find out why the server is down.
    final connection = hasInterface
        ? 'Offline — can’t reach the server.'
        : 'No network.';

    // Once the snapshot is old enough to matter, its age displaces the reassuring
    // half of the message. "Sales are saved on this device" is the right thing to
    // say at hour one and the wrong thing to lead with at hour thirty, when what
    // the cashier needs to know is that the prices on screen are yesterday's.
    return switch (freshness) {
      CatalogFreshness.fresh => '$connection Sales are saved on this device.',
      CatalogFreshness.aging =>
        '$connection Catalog from ${catalogAgeLabel(syncedAt)} — check prices.',
      CatalogFreshness.stale =>
        '$connection Catalog is ${catalogAgeLabel(syncedAt)} — verify prices before selling.',
    };
  }
}

/// "· 3 waiting", appended only when there is actually a queue.
class _PendingCount extends StatelessWidget {
  const _PendingCount();

  @override
  Widget build(BuildContext context) {
    if (!serviceLocator.isRegistered<OfflineSyncCubit>()) return const SizedBox.shrink();
    return BlocBuilder<OfflineSyncCubit, OfflineSyncState>(
      bloc: serviceLocator<OfflineSyncCubit>(),
      buildWhen: (a, b) => a.pendingCount != b.pendingCount,
      builder: (context, sync) {
        if (!sync.hasPending) return const SizedBox.shrink();
        return Padding(
          padding: const EdgeInsets.only(left: 8),
          child: Container(
            padding: const EdgeInsets.symmetric(horizontal: 7, vertical: 2),
            decoration: BoxDecoration(
              color: Colors.white.withValues(alpha: 0.22),
              borderRadius: BorderRadius.circular(20),
            ),
            child: Text('${sync.pendingCount} waiting',
                style: ui(size: 10, weight: FontWeight.w800, color: Colors.white)),
          ),
        );
      },
    );
  }
}
