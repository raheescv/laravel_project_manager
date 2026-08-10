import 'package:flutter/material.dart';
import 'package:flutter_bloc/flutter_bloc.dart';
import 'package:go_router/go_router.dart';
import 'package:invo/shared/domain/constants/global_variables.dart';
import 'package:invo/shared/domain/helpers/formatters.dart';
import 'package:invo/shared/utils/components/theme/index.dart';
import 'package:invo/shared/utils/router/routes.dart';
import 'package:invo/shared/widgets/astra_widgets.dart';

import '../../domain/models/pending_sale.dart';
import '../../logic/offline_sync_cubit/offline_sync_cubit.dart';

/// Sales taken on this till that the server has not acknowledged yet.
///
/// Every row here is money already collected, so the screen never hides one: a
/// sale leaves this list by syncing, or by a person deliberately discarding a
/// failure they have settled another way.
class PendingSalesScreen extends StatelessWidget {
  const PendingSalesScreen({super.key});

  @override
  Widget build(BuildContext context) {
    // The sync engine is an app-wide singleton, not a screen-scoped cubit, so
    // every builder here targets it explicitly rather than through the tree.
    final sync = serviceLocator<OfflineSyncCubit>();
    return Scaffold(
      body: AstraBackground(
        child: Column(
          children: [
            BlocBuilder<OfflineSyncCubit, OfflineSyncState>(
              bloc: sync,
              buildWhen: (a, b) => a.pendingCount != b.pendingCount || a.draining != b.draining,
              builder: (_, state) => EmeraldHeader(
                title: 'Offline sales',
                subtitle: state.hasPending
                    ? '${state.pendingCount} waiting to reach the server'
                    : 'Everything has synced',
                leading: context.canPop()
                    ? HeaderIconButton(icon: Icons.chevron_left, onTap: context.pop)
                    : null,
                trailing: HeaderIconButton(
                  icon: Icons.sync_rounded,
                  gold: true,
                  onTap: state.draining ? null : sync.drain,
                ),
              ),
            ),
            Expanded(
              child: BlocBuilder<OfflineSyncCubit, OfflineSyncState>(
                bloc: sync,
                builder: (_, state) => _body(context, sync, state),
              ),
            ),
          ],
        ),
      ),
    );
  }

  Widget _body(BuildContext context, OfflineSyncCubit sync, OfflineSyncState state) {
    if (state.rows.isEmpty) {
      return const EmptyState(
        icon: Icons.cloud_done_outlined,
        title: 'Nothing waiting',
        message: 'Sales rung up without a connection appear here until they reach the server.',
      );
    }
    return RefreshIndicator(
      onRefresh: sync.drain,
      child: ListView.separated(
        padding: const EdgeInsets.fromLTRB(16, 16, 16, 28),
        itemCount: state.rows.length,
        separatorBuilder: (_, __) => const SizedBox(height: 10),
        itemBuilder: (_, i) => _PendingRow(row: state.rows[i], sync: sync),
      ),
    );
  }
}

class _PendingRow extends StatelessWidget {
  const _PendingRow({required this.row, required this.sync});

  final PendingSale row;
  final OfflineSyncCubit sync;

  @override
  Widget build(BuildContext context) {
    final p = context.astra;
    final (bg, fg) = _pillColours(p, row.status);
    return AstraCard(
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Row(
            children: [
              Expanded(
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    Text(row.displayRef, style: ui(size: 14, weight: FontWeight.w800, color: p.ink)),
                    const SizedBox(height: 2),
                    Text('${row.customerName} · ${_when(row.createdAt)}',
                        style: ui(size: 11, color: p.textSecondary)),
                  ],
                ),
              ),
              const SizedBox(width: 10),
              Column(
                crossAxisAlignment: CrossAxisAlignment.end,
                children: [
                  Text(Money.of(row.total), style: serif(size: 16, color: p.ink)),
                  const SizedBox(height: 4),
                  StatusPill(label: row.status.label, bg: bg, fg: fg),
                ],
              ),
            ],
          ),
          if (row.status == PendingSaleStatus.failed && row.lastError != null) ...[
            const SizedBox(height: 10),
            Container(
              width: double.infinity,
              padding: const EdgeInsets.all(9),
              decoration: BoxDecoration(
                color: p.dangerTint,
                borderRadius: BorderRadius.circular(9),
              ),
              // The server's own wording. It names the actual obstacle — stock
              // gone, a deleted product — which is what the person fixing it
              // needs, not a generic "sync failed".
              child: Text(row.lastError!, style: ui(size: 11, color: p.ink)),
            ),
          ],
          // Whose sale this is. It syncs from any session — the payload carries
          // the originating cashier — but saying so stops a manager wondering
          // why a stranger's sale is sitting on their till.
          if (!sync.ownedByCurrentSession(row)) ...[
            const SizedBox(height: 8),
            Row(
              children: [
                Icon(Icons.person_outline, size: 12, color: p.textMuted),
                const SizedBox(width: 5),
                Text('Taken by another cashier · syncs from here',
                    style: ui(size: 10, color: p.textMuted)),
              ],
            ),
          ],
          // A row mid-post offers nothing to do: retrying would issue a second
          // concurrent POST, and discarding would delete a sale whose request
          // may already have committed.
          if (row.status != PendingSaleStatus.synced &&
              row.status != PendingSaleStatus.syncing) ...[
            const SizedBox(height: 10),
            Row(
              children: [
                Expanded(
                  child: AstraButton(
                    label: 'Retry now',
                    icon: Icons.refresh,
                    onTap: () => sync.retry(row.clientUuid),
                  ),
                ),
                if (row.status == PendingSaleStatus.failed) ...[
                  const SizedBox(width: 8),
                  Expanded(
                    child: AstraButton(
                      label: 'Discard',
                      icon: Icons.delete_outline,
                      onTap: () => _confirmDiscard(context),
                    ),
                  ),
                ],
              ],
            ),
          ],
          if (row.attempts > 0) ...[
            const SizedBox(height: 6),
            Text('${row.attempts} ${row.attempts == 1 ? 'attempt' : 'attempts'}',
                style: ui(size: 10, color: p.textMuted)),
          ],
        ],
      ),
    );
  }

  /// Discarding is the one action here that can lose takings, so it asks first
  /// and says plainly what will be lost.
  Future<void> _confirmDiscard(BuildContext context) async {
    final ok = await showDialog<bool>(
      context: context,
      builder: (ctx) => AlertDialog(
        title: const Text('Discard this sale?'),
        content: Text(
          '${row.displayRef} (${Money.of(row.total)}) will be removed from this device '
          'and will never reach the server. Only do this if it has already been '
          'entered another way.',
        ),
        actions: [
          TextButton(onPressed: () => Navigator.pop(ctx, false), child: const Text('Keep')),
          TextButton(
            onPressed: () => Navigator.pop(ctx, true),
            child: Text('Discard',
                style: ui(size: 14, weight: FontWeight.w700, color: AstraPalette.danger)),
          ),
        ],
      ),
    );
    if (ok == true) await sync.discard(row.clientUuid);
  }

  (Color, Color) _pillColours(AstraPalette p, PendingSaleStatus status) => switch (status) {
        PendingSaleStatus.synced => (p.successTint, AstraPalette.success),
        PendingSaleStatus.failed => (p.dangerTint, AstraPalette.danger),
        _ => (p.tint, p.goldText),
      };

  String _when(DateTime at) {
    final diff = DateTime.now().difference(at);
    if (diff.inMinutes < 1) return 'just now';
    if (diff.inMinutes < 60) return '${diff.inMinutes}m ago';
    if (diff.inHours < 24) return '${diff.inHours}h ago';
    return '${diff.inDays}d ago';
  }
}

/// Badge that opens [PendingSalesScreen], shown only while sales are waiting.
/// Sits in the New Sale header so the cashier sees the queue on the screen they
/// spend the day on.
class PendingSalesBadge extends StatelessWidget {
  const PendingSalesBadge({super.key});

  @override
  Widget build(BuildContext context) {
    // The badge is an optional ornament on someone else's screen, so a missing
    // sync engine renders nothing rather than taking New Sale down with it.
    if (!serviceLocator.isRegistered<OfflineSyncCubit>()) return const SizedBox.shrink();
    final sync = serviceLocator<OfflineSyncCubit>();
    return BlocBuilder<OfflineSyncCubit, OfflineSyncState>(
      bloc: sync,
      buildWhen: (a, b) => a.pendingCount != b.pendingCount || a.failed.length != b.failed.length,
      builder: (context, state) {
        if (!state.hasPending) return const SizedBox.shrink();
        final p = context.astra;
        // A sale the server refused needs a person; one merely waiting does not.
        // Only the first earns the alarm colour, so the badge keeps meaning
        // something when it turns red.
        final needsAttention = state.failed.isNotEmpty;
        final fg = needsAttention ? AstraPalette.danger : p.goldText;
        return GestureDetector(
          behavior: HitTestBehavior.opaque,
          onTap: () => context.push(Routes.pendingSales),
          child: Container(
            padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 6),
            decoration: BoxDecoration(
              color: needsAttention ? p.dangerTint : p.tint,
              borderRadius: BorderRadius.circular(20),
            ),
            child: Row(
              mainAxisSize: MainAxisSize.min,
              children: [
                Icon(needsAttention ? Icons.error_outline : Icons.cloud_upload_outlined,
                    size: 13, color: fg),
                const SizedBox(width: 5),
                Text('${state.pendingCount}',
                    style: ui(size: 11, weight: FontWeight.w800, color: fg)),
              ],
            ),
          ),
        );
      },
    );
  }
}
