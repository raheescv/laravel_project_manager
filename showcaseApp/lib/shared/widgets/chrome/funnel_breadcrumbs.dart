import 'package:flutter/material.dart';

import '../../logic/funnel_cubit/funnel_cubit.dart';
import '../../utils/components/theme/pearl_theme.dart';
import '../../../l10n/app_localizations.dart';

/// The choices made so far, as a tappable strip under the top bar.
///
/// This is what stops the funnel being a corridor: a completed step shows its
/// answer and reopens on tap. Nothing here is destructive — reopening a step
/// keeps everything before it.
///
/// Every crumb names the question as well as the answer. "49 › HOKA" asks the
/// customer to know that the first one is a size and the second a brand, and a
/// number on its own is the crumb most likely to be misread — 49 is a shoe
/// size here and a price everywhere else. The label is set small and quiet
/// ahead of the answer, and the answer carries the weight — that is the part
/// somebody scans for.
class FunnelBreadcrumbs extends StatelessWidget {
  const FunnelBreadcrumbs({
    super.key,
    required this.state,
    required this.current,
    required this.onReopen,
    this.trailing,
  });

  final FunnelState state;
  final FunnelStep current;
  final void Function(FunnelStep) onReopen;

  /// An unlabelled last crumb that reopens nothing — for a step whose answer
  /// is the screen itself.
  final String? trailing;

  @override
  Widget build(BuildContext context) {
    final p = context.pearl;
    final t = L.of(context);
    final crumbs = <(FunnelStep, String, String)>[
      if (state.size != null) (FunnelStep.size, t.stepSize, state.size!),
      if (state.brand != null)
        (FunnelStep.brand, t.stepBrand, state.brand!.name),
    ];
    final count = crumbs.length + (trailing != null ? 1 : 0);

    return SizedBox(
      height: 34,
      child: ListView.builder(
        scrollDirection: Axis.horizontal,
        // No padding of its own: this only ever renders inside AppTopBar, which
        // has already inset it.
        padding: EdgeInsets.zero,
        itemCount: count,
        itemBuilder: (context, i) {
          final crumb = i < crumbs.length ? crumbs[i] : null;
          return Row(
            children: [
              if (crumb == null)
                _Crumb(value: trailing!)
              else
                InkWell(
                  onTap: () => onReopen(crumb.$1),
                  // Fills the strip's height so the tap target is the band, not
                  // the nine-point type inside it.
                  child: Center(child: _Crumb(label: crumb.$2, value: crumb.$3)),
                ),
              // A chevron reads as "and then", so it needs something after it.
              // The strip drew one past the last crumb as well, leaving an
              // arrow aimed at the empty half of the bar.
              if (i < count - 1)
                Padding(
                  padding: const EdgeInsets.symmetric(horizontal: 10),
                  child: Icon(
                    // Points the way the strip runs. In Arabic the crumbs lay
                    // out right to left and a fixed chevron aimed back at the
                    // step already taken.
                    Directionality.of(context) == TextDirection.rtl
                        ? Icons.chevron_left
                        : Icons.chevron_right,
                    size: 13,
                    color: p.faint,
                  ),
                ),
            ],
          );
        },
      ),
    );
  }
}

/// One answer, under the name of the question it answered.
class _Crumb extends StatelessWidget {
  const _Crumb({this.label, required this.value});

  final String? label;
  final String value;

  @override
  Widget build(BuildContext context) {
    final p = context.pearl;
    return Row(
      mainAxisSize: MainAxisSize.min,
      // Baselines rather than centres: the two are set at different sizes, and
      // centred they sit as two unrelated marks.
      crossAxisAlignment: CrossAxisAlignment.baseline,
      textBaseline: TextBaseline.alphabetic,
      children: [
        if (label != null) ...[
          Text(
            label!.toUpperCase(),
            style: PearlText.micro.copyWith(fontSize: 8, color: p.faint),
          ),
          const SizedBox(width: 4),
        ],
        Text(
          value.toUpperCase(),
          style: PearlText.micro.copyWith(
            fontWeight: FontWeight.w700,
            color: p.ink,
          ),
        ),
      ],
    );
  }
}
