import 'package:flutter/material.dart';
import 'package:flutter_bloc/flutter_bloc.dart';

import '../../domain/helpers/responsive.dart';
import '../../logic/connectivity_cubit/connectivity_cubit.dart';
import '../../utils/components/theme/pearl_theme.dart';
import 'app_rail.dart';
import '../../../l10n/app_localizations.dart';

/// The frame every screen sits in.
///
/// Tablet: persistent rail, a top bar that stays put, and up to three columns —
/// choices on the left, content in the middle, live context on the right.
/// Phone: the same top bar, one column, and no rail. The columns are dropped
/// rather than squeezed, because a 250px sidebar on a phone is neither.
class ShowcaseScaffold extends StatelessWidget {
  const ShowcaseScaffold({
    super.key,
    required this.body,
    this.topBar,
    this.leftColumn,
    this.rightColumn,
    this.bottomBar,
    this.railIndex = 0,
    this.showRail = true,
  });

  final Widget body;
  final Widget? topBar;

  /// Funnel steps / filters. Tablet only.
  final Widget? leftColumn;

  /// Live preview, top brands, running counts. Tablet, and only when wide.
  final Widget? rightColumn;

  final Widget? bottomBar;
  final int railIndex;
  final bool showRail;

  @override
  Widget build(BuildContext context) {
    final p = context.pearl;
    final tablet = context.isTablet;

    final content = Column(
      children: [
        const _OfflineBanner(),
        if (topBar != null) topBar!,
        Expanded(
          child: tablet
              ? Row(
                  crossAxisAlignment: CrossAxisAlignment.stretch,
                  children: [
                    if (leftColumn != null)
                      Container(
                        width: PearlMetrics.funnelColumn,
                        decoration: BoxDecoration(
                          border: Border(right: BorderSide(color: p.line)),
                        ),
                        child: leftColumn,
                      ),
                    Expanded(child: body),
                    if (rightColumn != null && context.isWide)
                      Container(
                        width: PearlMetrics.aside,
                        decoration: BoxDecoration(
                          border: Border(left: BorderSide(color: p.line)),
                        ),
                        child: rightColumn,
                      ),
                  ],
                )
              : body,
        ),
        if (bottomBar != null) bottomBar!,
      ],
    );

    return Scaffold(
      backgroundColor: p.bg,
      body: SafeArea(
        child: tablet && showRail
            ? Row(
                crossAxisAlignment: CrossAxisAlignment.stretch,
                children: [AppRail(active: railIndex), Expanded(child: content)],
              )
            : content,
      ),
    );
  }
}

/// Shown only while requests are failing to reach the server. Stock is the one
/// thing that must be labelled stale — a customer sent to a shop for a shoe
/// that sold an hour ago is a worse outcome than a visible warning.
class _OfflineBanner extends StatelessWidget {
  const _OfflineBanner();

  @override
  Widget build(BuildContext context) {
    final online = context.watch<ConnectivityCubit>().state;
    if (online) return const SizedBox.shrink();
    final p = context.pearl;
    return Container(
      width: double.infinity,
      padding: const EdgeInsets.symmetric(horizontal: PearlMetrics.pad, vertical: 9),
      color: p.ink,
      child: Text(
        L.of(context).offline.toUpperCase(),
        textAlign: TextAlign.center,
        style: PearlText.micro.copyWith(color: p.bg),
      ),
    );
  }
}

/// A quiet action bar pinned to the bottom of a screen. On the product page the
/// call to action lives here so it never scrolls away.
class PinnedBar extends StatelessWidget {
  const PinnedBar({super.key, required this.child});

  final Widget child;

  @override
  Widget build(BuildContext context) {
    final p = context.pearl;
    return Container(
      padding: const EdgeInsets.fromLTRB(
          PearlMetrics.pad, 12, PearlMetrics.pad, 14),
      decoration: BoxDecoration(
        color: p.bg,
        border: Border(top: BorderSide(color: p.line)),
      ),
      child: child,
    );
  }
}
