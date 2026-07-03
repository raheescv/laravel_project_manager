import 'package:flutter/material.dart';

import 'package:invo/shared/utils/components/theme/index.dart';
import 'package:invo/shared/widgets/astra_widgets.dart';

/// Maps the backend's Bootstrap-flavoured status / priority colour names to the
/// Astra tint pair (bg, fg) so chips read consistently with the rest of the app
/// (build_prompt §2.9): completed→success, pending/high→warn, assigned/medium→
/// primary tint, critical/danger→danger, outstanding/cancelled→muted.
({Color bg, Color fg}) astraTint(BuildContext context, String colorName) {
  final p = context.astra;
  switch (colorName) {
    case 'success':
      return (bg: p.successTint, fg: AstraPalette.success);
    case 'warning':
      return (bg: p.warnTint, fg: p.warnText);
    case 'danger':
      return (bg: p.dangerTint, fg: AstraPalette.danger);
    case 'info':
      return (bg: p.tint, fg: p.primary);
    case 'dark':
    case 'secondary':
    default:
      return (bg: p.hairline.withValues(alpha: 0.6), fg: p.textSecondary);
  }
}

/// A ready-made status pill from a backend colour name + label.
class AstraStatusPill extends StatelessWidget {
  const AstraStatusPill(
      {super.key, required this.label, required this.colorName, this.icon});
  final String label;
  final String colorName;
  final IconData? icon;

  @override
  Widget build(BuildContext context) {
    final t = astraTint(context, colorName);
    return StatusPill(label: label, bg: t.bg, fg: t.fg, icon: icon);
  }
}

/// The dot/icon for a priority (used in the dashboard breakdown & detail bar).
IconData priorityIcon(String priority) {
  switch (priority) {
    case 'critical':
      return Icons.priority_high;
    case 'high':
      return Icons.keyboard_double_arrow_up;
    case 'medium':
      return Icons.drag_handle;
    case 'low':
    default:
      return Icons.keyboard_arrow_down;
  }
}
