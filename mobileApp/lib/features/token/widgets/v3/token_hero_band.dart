import 'package:flutter/material.dart';

import 'package:invo/shared/utils/components/theme/index.dart';
import 'package:invo/shared/widgets/astra_widgets.dart';

/// The floating hero band of the tablet Status Board, the same frame Day
/// Session uses: icon tile, accent overline, serif title with an optional
/// pill, a subtitle, then key figures behind a hairline on the right.
class TokenHeroBand extends StatelessWidget {
  const TokenHeroBand({
    super.key,
    required this.icon,
    required this.overline,
    required this.title,
    required this.subtitle,
    this.pill,
    this.meta = const [],
    this.onBack,
    this.trailing,
    this.compact = false,
  });

  /// Drops the key figures on a narrow (small portrait) tablet, where they
  /// can't sit beside the title; the page below repeats them. The caller
  /// measures: the band lives inside a SliverFillRemaining, which asks for
  /// intrinsic sizes a LayoutBuilder here could not answer.
  final bool compact;

  final IconData icon;
  final String overline;
  final String title;
  final String subtitle;
  final Widget? pill;
  final List<({String label, String value, bool gold})> meta;

  /// Only for a screen pushed as a route. As a shell destination the rail is
  /// the way out, as on Day Session.
  final VoidCallback? onBack;
  final Widget? trailing;

  @override
  Widget build(BuildContext context) {
    final p = context.astra;
    final showMeta = meta.isNotEmpty && !compact;
    return Container(
      clipBehavior: Clip.antiAlias,
      decoration: BoxDecoration(
        gradient: p.heroGradient,
        borderRadius: BorderRadius.circular(22),
        boxShadow: context.astraTheme.floatShadow(p.primary),
      ),
      child: Stack(
        children: [
          Positioned(
            right: -60,
            top: -70,
            child: Container(
              width: 240,
              height: 240,
              decoration: BoxDecoration(
                shape: BoxShape.circle,
                gradient: RadialGradient(colors: [p.accent.withValues(alpha: 0.22), Colors.transparent]),
              ),
            ),
          ),
          Padding(
            padding: const EdgeInsets.fromLTRB(20, 18, 20, 18),
            child: Row(
              children: [
                if (onBack != null) ...[
                  HeaderIconButton(icon: Icons.chevron_left, onTap: onBack),
                  const SizedBox(width: 14),
                ],
                Container(
                  width: 60,
                  height: 60,
                  decoration: BoxDecoration(
                    color: Colors.white.withValues(alpha: 0.14),
                    borderRadius: BorderRadius.circular(20),
                    border: Border.all(color: Colors.white.withValues(alpha: 0.22)),
                  ),
                  child: Icon(icon, size: 28, color: Colors.white),
                ),
                const SizedBox(width: 18),
                Expanded(
                  child: Column(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: [
                      Text(overline,
                          maxLines: 1,
                          overflow: TextOverflow.ellipsis,
                          style: ui(size: 9.5, weight: FontWeight.w800, color: p.accent, letterSpacing: 2)),
                      const SizedBox(height: 4),
                      Row(
                        children: [
                          Flexible(
                            child: Text(title,
                                maxLines: 1, overflow: TextOverflow.ellipsis, style: serif(size: 28, color: Colors.white)),
                          ),
                          if (pill != null) ...[const SizedBox(width: 10), pill!],
                        ],
                      ),
                      const SizedBox(height: 2),
                      Text(subtitle,
                          maxLines: 1,
                          overflow: TextOverflow.ellipsis,
                          style: ui(size: 12.5, weight: FontWeight.w600, color: Colors.white.withValues(alpha: 0.82))),
                    ],
                  ),
                ),
                if (showMeta) ...[
                  const SizedBox(width: 18),
                  Container(
                    padding: const EdgeInsets.only(left: 24),
                    decoration: BoxDecoration(
                      border: Border(left: BorderSide(color: Colors.white.withValues(alpha: 0.18))),
                    ),
                    child: Row(children: [
                      for (var i = 0; i < meta.length; i++) ...[
                        if (i > 0) const SizedBox(width: 30),
                        _BandMeta(label: meta[i].label, value: meta[i].value, gold: meta[i].gold),
                      ],
                    ]),
                  ),
                ],
                if (trailing != null) ...[const SizedBox(width: 22), trailing!],
              ],
            ),
          ),
        ],
      ),
    );
  }
}

/// A small white-on-glass status pill for the band's title row.
class TokenHeroPill extends StatelessWidget {
  const TokenHeroPill({super.key, required this.icon, required this.label});

  final IconData icon;
  final String label;

  @override
  Widget build(BuildContext context) => Container(
        padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 4),
        decoration: BoxDecoration(
          color: Colors.white.withValues(alpha: 0.16),
          borderRadius: BorderRadius.circular(20),
        ),
        child: Row(mainAxisSize: MainAxisSize.min, children: [
          Icon(icon, size: 12, color: Colors.white),
          const SizedBox(width: 4),
          Text(label, style: ui(size: 10.5, weight: FontWeight.w800, color: Colors.white)),
        ]),
      );
}

class _BandMeta extends StatelessWidget {
  const _BandMeta({required this.label, required this.value, this.gold = false});

  final String label;
  final String value;
  final bool gold;

  @override
  Widget build(BuildContext context) {
    final p = context.astra;
    return Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      mainAxisSize: MainAxisSize.min,
      children: [
        Text(label.toUpperCase(),
            style: ui(size: 9, weight: FontWeight.w800, color: Colors.white.withValues(alpha: 0.6), letterSpacing: 1.4)),
        const SizedBox(height: 4),
        Text(value, style: ui(size: 17, weight: FontWeight.w800, color: gold ? p.accent : Colors.white)),
      ],
    );
  }
}
