import 'package:flutter/material.dart';

import 'package:invo/shared/domain/helpers/formatters.dart';
import 'package:invo/shared/utils/components/theme/index.dart';
import 'package:invo/shared/widgets/astra_widgets.dart';

import '../../domain/models/technician_models.dart';
import 'status_style.dart';

/// A single assigned-complaint card — the shared row for the dashboard "recent"
/// list and the "My Complaints" list.
class ComplaintCard extends StatelessWidget {
  const ComplaintCard({super.key, required this.item, this.onTap});

  final ComplaintListItem item;
  final VoidCallback? onTap;

  @override
  Widget build(BuildContext context) {
    final p = context.astra;
    final location = [
      if (item.propertyNumber.isNotEmpty) 'Unit ${item.propertyNumber}',
      if (item.building.isNotEmpty) item.building,
    ].join(' · ');

    return AstraCard(
      radius: 16,
      onTap: onTap,
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Row(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              IconChip(
                icon: Icons.build_outlined,
                size: 40,
                radius: 12,
                bg: astraTint(context, item.priorityColor).bg,
                fg: astraTint(context, item.priorityColor).fg,
              ),
              const SizedBox(width: 11),
              Expanded(
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    Text(
                      item.complaintName.isEmpty ? 'Complaint #${item.id}' : item.complaintName,
                      maxLines: 1,
                      overflow: TextOverflow.ellipsis,
                      style: ui(size: 14, weight: FontWeight.w800, color: p.ink),
                    ),
                    if (item.categoryName.isNotEmpty)
                      Padding(
                        padding: const EdgeInsets.only(top: 2),
                        child: Text(item.categoryName,
                            maxLines: 1,
                            overflow: TextOverflow.ellipsis,
                            style: ui(size: 11, weight: FontWeight.w600, color: p.textMuted)),
                      ),
                  ],
                ),
              ),
              const SizedBox(width: 8),
              AstraStatusPill(label: item.statusLabel, colorName: item.statusColor),
            ],
          ),
          const SizedBox(height: 12),
          Row(
            children: [
              if (item.priorityLabel.isNotEmpty) ...[
                Icon(priorityIcon(item.priority), size: 13, color: astraTint(context, item.priorityColor).fg),
                const SizedBox(width: 3),
                Text(item.priorityLabel,
                    style: ui(size: 11, weight: FontWeight.w700, color: astraTint(context, item.priorityColor).fg)),
                const SizedBox(width: 12),
              ],
              if (location.isNotEmpty) ...[
                Icon(Icons.place_outlined, size: 13, color: p.textMuted),
                const SizedBox(width: 3),
                Flexible(
                  child: Text(location,
                      maxLines: 1,
                      overflow: TextOverflow.ellipsis,
                      style: ui(size: 11, weight: FontWeight.w600, color: p.textSecondary)),
                ),
              ],
            ],
          ),
          if (item.date.isNotEmpty || item.customerName.isNotEmpty) ...[
            const SizedBox(height: 6),
            Row(
              children: [
                if (item.date.isNotEmpty) ...[
                  Icon(Icons.event_outlined, size: 13, color: p.textMuted),
                  const SizedBox(width: 3),
                  Text('${Dates.human(item.date)}${item.time.isNotEmpty ? ' · ${item.time}' : ''}',
                      style: ui(size: 11, weight: FontWeight.w600, color: p.textSecondary)),
                  const SizedBox(width: 12),
                ],
                if (item.customerName.isNotEmpty) ...[
                  Icon(Icons.person_outline, size: 13, color: p.textMuted),
                  const SizedBox(width: 3),
                  Flexible(
                    child: Text(item.customerName,
                        maxLines: 1,
                        overflow: TextOverflow.ellipsis,
                        style: ui(size: 11, weight: FontWeight.w600, color: p.textSecondary)),
                  ),
                ],
              ],
            ),
          ],
        ],
      ),
    );
  }
}
