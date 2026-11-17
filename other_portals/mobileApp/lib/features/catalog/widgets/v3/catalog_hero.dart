import 'package:flutter/material.dart';

import 'package:invo/shared/utils/components/theme/index.dart';

/// One KPI under the title.
typedef CatalogKpi = ({String label, String value, Color? tone});

/// The "Hero Console" band shared by the catalog pages, kept compact: back,
/// title with its breadcrumb and the actions share one row, and the KPIs sit
/// in a slim strip beneath — the list keeps the height for rows.
class CatalogHero extends StatelessWidget {
  const CatalogHero({
    super.key,
    required this.title,
    required this.subtitle,
    required this.onBack,
    this.actions = const [],
    this.kpis = const [],
  });

  final String title;

  /// The breadcrumb under the title.
  final String subtitle;
  final VoidCallback onBack;
  final List<Widget> actions;
  final List<CatalogKpi> kpis;

  @override
  Widget build(BuildContext context) {
    final p = context.astra;
    return Container(
      width: double.infinity,
      padding: EdgeInsets.fromLTRB(10, MediaQuery.paddingOf(context).top + 8, 14, 12),
      decoration: BoxDecoration(gradient: p.heroGradient),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.stretch,
        children: [
          Row(
            children: [
              HeroIconButton(icon: Icons.arrow_back, onTap: onBack),
              const SizedBox(width: 10),
              Expanded(
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    Text(title, maxLines: 1, overflow: TextOverflow.ellipsis, style: serif(size: 21, color: Colors.white)),
                    const SizedBox(height: 2),
                    Text(subtitle,
                        maxLines: 1,
                        overflow: TextOverflow.ellipsis,
                        style: ui(size: 11, weight: FontWeight.w600, color: Colors.white.withValues(alpha: 0.75))),
                  ],
                ),
              ),
              ...actions,
            ],
          ),
          if (kpis.isNotEmpty) ...[
            const SizedBox(height: 10),
            Row(
              children: [
                for (var i = 0; i < kpis.length; i++) ...[
                  if (i > 0) const SizedBox(width: 7),
                  Expanded(child: _KpiChip(kpi: kpis[i])),
                ],
              ],
            ),
          ],
        ],
      ),
    );
  }
}

class _KpiChip extends StatelessWidget {
  const _KpiChip({required this.kpi});

  final CatalogKpi kpi;

  @override
  Widget build(BuildContext context) {
    return Container(
      height: 34,
      padding: const EdgeInsets.symmetric(horizontal: 10),
      decoration: BoxDecoration(
        color: Colors.white.withValues(alpha: 0.14),
        borderRadius: BorderRadius.circular(11),
        border: Border.all(color: Colors.white.withValues(alpha: 0.16)),
      ),
      alignment: Alignment.centerLeft,
      child: FittedBox(
        fit: BoxFit.scaleDown,
        alignment: Alignment.centerLeft,
        child: Row(
          children: [
            Text(kpi.value, style: serif(size: 16, color: kpi.tone ?? Colors.white)),
            const SizedBox(width: 6),
            Text(kpi.label.toUpperCase(),
                style: ui(size: 9.5, weight: FontWeight.w800, color: Colors.white.withValues(alpha: 0.72), letterSpacing: 0.6)),
          ],
        ),
      ),
    );
  }
}

/// A frosted square button on the hero.
class HeroIconButton extends StatelessWidget {
  const HeroIconButton({super.key, required this.icon, required this.onTap, this.label});

  final IconData icon;
  final VoidCallback onTap;

  /// When set, a white pill with the label instead of a frosted square.
  final String? label;

  @override
  Widget build(BuildContext context) {
    final p = context.astra;
    final solid = label != null;
    return GestureDetector(
      onTap: onTap,
      child: Container(
        height: 38,
        constraints: const BoxConstraints(minWidth: 38),
        margin: EdgeInsets.only(left: solid ? 8 : 0),
        padding: EdgeInsets.symmetric(horizontal: solid ? 14 : 0),
        decoration: BoxDecoration(
          color: solid ? Colors.white : Colors.white.withValues(alpha: 0.16),
          borderRadius: BorderRadius.circular(12),
        ),
        child: Row(
          mainAxisSize: MainAxisSize.min,
          mainAxisAlignment: MainAxisAlignment.center,
          children: [
            Icon(icon, size: 19, color: solid ? p.primary : Colors.white),
            if (solid) ...[const SizedBox(width: 5), Text(label!, style: ui(size: 13, weight: FontWeight.w800, color: p.primary))],
          ],
        ),
      ),
    );
  }
}
