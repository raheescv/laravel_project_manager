import 'package:flutter/material.dart';
import 'package:flutter_bloc/flutter_bloc.dart';

import '../../logic/connectivity_cubit/connectivity_cubit.dart';
import '../../utils/components/theme/pearl_theme.dart';
import '../../../l10n/app_localizations.dart';

/// The frame every screen sits in: the offline banner, the top bar, one column
/// of content, and an optional bar pinned to the bottom.
///
/// One column, and only one. This runs on a kiosk — a single screen, a single
/// customer standing in front of it, one question at a time. The rail, the
/// funnel column and the right-hand aside it used to carry were there to use up
/// a tablet's width, and width is not what this app is short of.
class ShowcaseScaffold extends StatelessWidget {
  const ShowcaseScaffold({
    super.key,
    required this.body,
    this.topBar,
    this.bottomBar,
  });

  final Widget body;
  final Widget? topBar;
  final Widget? bottomBar;

  @override
  Widget build(BuildContext context) {
    final p = context.pearl;

    return Scaffold(
      backgroundColor: p.bg,
      // Edge to edge. The kiosk is a panel the size of a door and the app is
      // the only thing on it, so a column floating in the middle of it reads as
      // a phone screenshot someone hung on a wall. How dense the size run gets
      // at this width is Appearance's "sizes per row", not a cap here.
      body: SafeArea(
        child: Column(
          children: [
            const _OfflineBanner(),
            if (topBar != null) topBar!,
            Expanded(child: body),
            if (bottomBar != null) bottomBar!,
          ],
        ),
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
