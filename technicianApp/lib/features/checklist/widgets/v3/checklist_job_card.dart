import 'package:flutter/material.dart';
import 'package:intl/intl.dart';

import 'package:invo/shared/utils/components/theme/index.dart';

import '../../domain/models/checklist_models.dart';
import 'atelier_parts.dart';

/// Inbox row: date block · unit + lessee · phase / role pills · progress ring.
/// A sealed (completed) job is muted and carries a "Sealed" pill.
class ChecklistJobCard extends StatelessWidget {
  const ChecklistJobCard({super.key, required this.job, this.onTap, this.margin = const EdgeInsets.fromLTRB(14, 0, 14, 8)});
  final ChecklistJob job;
  final VoidCallback? onTap;
  final EdgeInsets margin;

  @override
  Widget build(BuildContext context) {
    final p = context.astra;
    final date = job.scheduled;
    final sub = [job.lesseeName, job.agreementLabel].where((s) => s.isNotEmpty).join(' · ');
    final role = job.myRoles.isEmpty ? '' : job.myRoles.first.label;
    final card = Padding(
      padding: margin,
      child: AtelierCard(
        onTap: onTap,
        padding: const EdgeInsets.fromLTRB(12, 11, 12, 11),
        child: Row(children: [
          Container(
            width: 44,
            padding: const EdgeInsets.fromLTRB(0, 6, 0, 5),
            decoration: BoxDecoration(color: p.goldTint, borderRadius: BorderRadius.circular(12)),
            child: Column(children: [
              Text(date == null ? '—' : '${date.day}',
                  style: ui(size: 18, weight: FontWeight.w800, color: p.ink, height: 1.05, letterSpacing: -0.4)),
              const SizedBox(height: 2),
              Text(date == null ? 'TBD' : DateFormat('MMM').format(date).toUpperCase(),
                  style: ui(size: 9, weight: FontWeight.w800, letterSpacing: 1, color: p.goldText)),
            ]),
          ),
          const SizedBox(width: 12),
          Expanded(
            child: Column(crossAxisAlignment: CrossAxisAlignment.start, children: [
              Text(job.title,
                  maxLines: 1, overflow: TextOverflow.ellipsis, style: ui(size: 13.5, weight: FontWeight.w800, color: p.ink)),
              if (sub.isNotEmpty) ...[
                const SizedBox(height: 1),
                Text(sub,
                    maxLines: 1,
                    overflow: TextOverflow.ellipsis,
                    style: ui(size: 11.5, weight: FontWeight.w500, color: p.textSecondary)),
              ],
              const SizedBox(height: 6),
              Row(children: [
                AtelierPill.phase(context, job),
                const SizedBox(width: 6),
                if (job.sealed)
                  AtelierPill(label: 'Sealed', bg: p.goldTint, fg: p.goldText, icon: Icons.verified_outlined)
                else if (job.readyToSeal)
                  AtelierPill(label: 'Ready to seal', bg: p.goldTint, fg: p.goldText, icon: Icons.edit_outlined)
                else if (role.isNotEmpty)
                  Flexible(
                    child: Row(mainAxisSize: MainAxisSize.min, children: [
                      Icon(Icons.edit_outlined, size: 11, color: p.textSecondary),
                      const SizedBox(width: 3),
                      Flexible(
                        child: Text(role,
                            maxLines: 1,
                            overflow: TextOverflow.ellipsis,
                            style: ui(size: 10.5, weight: FontWeight.w700, color: p.textSecondary)),
                      ),
                    ]),
                  ),
              ]),
            ]),
          ),
          const SizedBox(width: 10),
          ProgressRing(value: job.progress, complete: job.readyToSeal, sealed: job.sealed),
        ]),
      ),
    );
    return job.sealed ? Opacity(opacity: 0.78, child: card) : card;
  }
}
