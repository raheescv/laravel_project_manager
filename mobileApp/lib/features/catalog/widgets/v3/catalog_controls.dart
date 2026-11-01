import 'package:flutter/material.dart';

import 'package:invo/shared/utils/components/theme/index.dart';
import 'package:invo/shared/widgets/astra_widgets.dart';

/// The app's gradient pill switch (same look as Settings).
class PillSwitch extends StatelessWidget {
  const PillSwitch({super.key, required this.value});

  final bool value;

  @override
  Widget build(BuildContext context) {
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
      child: Container(width: 20, height: 20, decoration: const BoxDecoration(color: Colors.white, shape: BoxShape.circle)),
    );
  }
}

/// A row that flips a switch on tap — the whole row is the target.
class ToggleRow extends StatelessWidget {
  const ToggleRow({super.key, required this.title, required this.value, required this.onChanged, this.subtitle, this.icon, this.enabled = true});

  final String title;
  final String? subtitle;
  final IconData? icon;
  final bool value;
  final bool enabled;
  final ValueChanged<bool> onChanged;

  @override
  Widget build(BuildContext context) {
    final p = context.astra;
    return GestureDetector(
      behavior: HitTestBehavior.opaque,
      onTap: enabled ? () => onChanged(!value) : null,
      child: Opacity(
        opacity: enabled ? 1 : 0.5,
        child: Padding(
          padding: const EdgeInsets.symmetric(vertical: 4),
          child: Row(
            children: [
              if (icon != null) ...[IconChip(icon: icon!, size: 34, radius: 10, bg: p.tint), const SizedBox(width: 11)],
              Expanded(
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    Text(title, style: ui(size: 13.5, weight: FontWeight.w700, color: p.ink)),
                    if (subtitle != null)
                      Padding(
                        padding: const EdgeInsets.only(top: 2),
                        child: Text(subtitle!, style: ui(size: 11, weight: FontWeight.w500, color: p.textMuted, height: 1.35)),
                      ),
                  ],
                ),
              ),
              const SizedBox(width: 10),
              PillSwitch(value: value),
            ],
          ),
        ),
      ),
    );
  }
}

/// A segmented control: every option visible, the chosen one raised.
class SegmentedChoice<T> extends StatelessWidget {
  const SegmentedChoice({super.key, required this.options, required this.selected, required this.onChanged, this.height = 40});

  final List<(T value, String label, IconData? icon)> options;
  final T selected;
  final ValueChanged<T> onChanged;
  final double height;

  @override
  Widget build(BuildContext context) {
    final p = context.astra;
    return Container(
      height: height,
      padding: const EdgeInsets.all(3),
      decoration: BoxDecoration(color: p.tint, borderRadius: BorderRadius.circular(12)),
      child: Row(
        children: [
          for (final (value, label, icon) in options)
            Expanded(
              child: GestureDetector(
                behavior: HitTestBehavior.opaque,
                onTap: () => onChanged(value),
                child: AnimatedContainer(
                  duration: const Duration(milliseconds: 160),
                  alignment: Alignment.center,
                  decoration: BoxDecoration(
                    color: value == selected ? p.cardSolid : Colors.transparent,
                    borderRadius: BorderRadius.circular(9),
                    boxShadow: value == selected ? context.astraTheme.softShadow : null,
                  ),
                  child: Row(
                    mainAxisSize: MainAxisSize.min,
                    children: [
                      if (icon != null) ...[
                        Icon(icon, size: 16, color: value == selected ? p.primary : p.textMuted),
                        const SizedBox(width: 5),
                      ],
                      Flexible(
                        child: Text(label,
                            maxLines: 1,
                            overflow: TextOverflow.ellipsis,
                            style: ui(size: 12.5, weight: FontWeight.w700, color: value == selected ? p.ink : p.textSecondary)),
                      ),
                    ],
                  ),
                ),
              ),
            ),
        ],
      ),
    );
  }
}

/// A rounded chip used for filters and toolbar actions.
class ActionChipButton extends StatelessWidget {
  const ActionChipButton({super.key, required this.label, this.icon, this.trailingIcon, this.badge, this.active = false, required this.onTap});

  final String label;
  final IconData? icon;
  final IconData? trailingIcon;
  final int? badge;
  final bool active;
  final VoidCallback onTap;

  @override
  Widget build(BuildContext context) {
    final p = context.astra;
    final fg = active ? Colors.white : p.textSecondary;
    return GestureDetector(
      onTap: onTap,
      child: Container(
        height: 36,
        padding: const EdgeInsets.symmetric(horizontal: 12),
        decoration: BoxDecoration(
          gradient: active ? p.primaryGradient : null,
          color: active ? null : p.cardSolid,
          borderRadius: BorderRadius.circular(20),
          border: active ? null : Border.all(color: p.hairline),
        ),
        child: Row(
          mainAxisSize: MainAxisSize.min,
          children: [
            if (icon != null) ...[Icon(icon, size: 16, color: fg), const SizedBox(width: 5)],
            ConstrainedBox(
              constraints: const BoxConstraints(maxWidth: 160),
              child: Text(label, maxLines: 1, overflow: TextOverflow.ellipsis, style: ui(size: 12.5, weight: FontWeight.w700, color: fg)),
            ),
            if (badge != null) ...[
              const SizedBox(width: 6),
              Container(
                padding: const EdgeInsets.symmetric(horizontal: 6, vertical: 1),
                decoration: BoxDecoration(color: active ? Colors.white.withValues(alpha: 0.25) : p.primary, borderRadius: BorderRadius.circular(10)),
                child: Text('$badge', style: ui(size: 10.5, weight: FontWeight.w800, color: Colors.white)),
              ),
            ],
            if (trailingIcon != null) ...[const SizedBox(width: 3), Icon(trailingIcon, size: 16, color: fg)],
          ],
        ),
      ),
    );
  }
}
