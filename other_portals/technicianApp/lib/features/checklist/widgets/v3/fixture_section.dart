import 'package:flutter/material.dart';
import 'package:flutter_bloc/flutter_bloc.dart';

import 'package:invo/shared/domain/helpers/formatters.dart';
import 'package:invo/shared/utils/components/app_strings.dart';
import 'package:invo/shared/utils/components/theme/index.dart';

import '../../domain/models/checklist_models.dart';
import '../../logic/checklist_detail_cubit/checklist_detail_cubit.dart';
import 'atelier_parts.dart';
import 'fixture_entry_sheet.dart';
import 'photo_viewer.dart';
import 'signature_capture.dart';

/// Fixture comments for one room (category): entry cards, "Add fixture", and
/// the owner's acceptance signature once every entry is Completed.
class FixtureSection extends StatelessWidget {
  const FixtureSection({super.key, required this.detail, required this.category});
  final ChecklistDetail detail;
  final String category;

  Future<void> _ownerSign(BuildContext context, FixtureArea area) async {
    final areaId = area.id;
    if (areaId == null) return;
    // Captured before the awaits: this section is rebuilt (and its context
    // replaced) when the window crosses a breakpoint, but the cubit and the
    // messenger outlive that — so the signature is saved regardless.
    final cubit = context.read<ChecklistDetailCubit>();
    final messenger = ScaffoldMessenger.of(context);
    final result = await showSignatureCapture(
      context,
      title: 'Owner acceptance',
      kicker: '$category · fixture comments',
      nameLabel: 'Owner name',
      initialName: area.ownerName ?? '',
      requireName: true,
    );
    if (result == null || cubit.isClosed) return;
    final ok = await cubit.signFixtureArea(areaId, ownerName: result.name, signature: result.dataUrl);
    messenger
      ..hideCurrentSnackBar()
      ..showSnackBar(SnackBar(
        behavior: SnackBarBehavior.floating,
        margin: const EdgeInsets.fromLTRB(14, 0, 14, 84),
        content: Text(ok ? 'Owner acceptance saved' : (cubit.state.actionError ?? AppStrings.somethingWentWrong)),
      ));
  }

  @override
  Widget build(BuildContext context) {
    final p = context.astra;
    final area = detail.fixtureFor(category);
    final entries = area?.entries ?? const <FixtureEntry>[];
    final locked = detail.sealed;
    return Column(crossAxisAlignment: CrossAxisAlignment.stretch, children: [
      const SizedBox(height: 6),
      AtelierSectionTitle(
        title: 'Fixture comments',
        action: locked
            ? null
            : GestureDetector(
                onTap: () => showFixtureEntrySheet(context, category: category),
                behavior: HitTestBehavior.opaque,
                child: Padding(
                  padding: const EdgeInsets.symmetric(vertical: 4),
                  child: Row(mainAxisSize: MainAxisSize.min, children: [
                    Icon(Icons.add_circle_outline, size: 15, color: p.goldText),
                    const SizedBox(width: 4),
                    Text('Add fixture', style: ui(size: 12, weight: FontWeight.w800, color: p.goldText)),
                  ]),
                ),
              ),
      ),
      if (entries.isEmpty)
        Padding(
          padding: const EdgeInsets.fromLTRB(18, 0, 18, 8),
          child: Text('No fixture notes for this room.', style: ui(size: 12, weight: FontWeight.w600, color: p.textMuted)),
        )
      else
        for (final e in entries)
          Padding(
            padding: const EdgeInsets.fromLTRB(14, 0, 14, 8),
            child: FixtureEntryCard(
              entry: e,
              category: category,
              onTap: locked ? null : () => showFixtureEntrySheet(context, category: category, entry: e),
            ),
          ),
      if (area != null && area.isSigned)
        Padding(
          padding: const EdgeInsets.fromLTRB(14, 4, 14, 8),
          child: AtelierCard(
            child: Column(crossAxisAlignment: CrossAxisAlignment.start, children: [
              Row(children: [
                Icon(Icons.verified_outlined, size: 14, color: p.goldText),
                const SizedBox(width: 6),
                const AtelierKicker('Owner acceptance'),
              ]),
              if (area.ownerSignature != null) ...[
                const SizedBox(height: 8),
                SignatureImage(path: area.ownerSignature!, caption: 'Owner acceptance · $category'),
              ],
              const SizedBox(height: 6),
              Text(
                [area.ownerName ?? 'Owner', Dates.humanDateTime(area.ownerSignedAt)].where((s) => s.isNotEmpty).join(' · '),
                style: ui(size: 11.5, weight: FontWeight.w700, color: p.textSecondary),
              ),
            ]),
          ),
        )
      else if (area != null && area.readyForAcceptance && area.id != null && !locked)
        Padding(
          padding: const EdgeInsets.fromLTRB(14, 4, 14, 8),
          child: AtelierButton(
            label: 'Owner acceptance',
            leadingIcon: Icons.draw_outlined,
            kind: AtelierButtonKind.gold,
            height: 46,
            onTap: () => _ownerSign(context, area),
          ),
        ),
    ]);
  }
}

/// Fixture entry card: before / after thumbs (tap → full screen), comments,
/// status chip. Tapping elsewhere edits the entry.
class FixtureEntryCard extends StatelessWidget {
  const FixtureEntryCard({super.key, required this.entry, required this.category, this.onTap});
  final FixtureEntry entry;
  final String category;
  final VoidCallback? onTap;

  void _view(BuildContext context, String which) {
    final photos = [
      if (entry.beforeImage != null) ChecklistViewerPhoto(path: entry.beforeImage!, caption: 'Before · $category'),
      if (entry.afterImage != null) ChecklistViewerPhoto(path: entry.afterImage!, caption: 'After · $category'),
    ];
    final start = which == 'after' && entry.beforeImage != null ? 1 : 0;
    openChecklistPhotoViewer(context, photos, initialIndex: start);
  }

  @override
  Widget build(BuildContext context) {
    final p = context.astra;
    return AtelierCard(
      onTap: onTap,
      padding: const EdgeInsets.all(10),
      child: Row(crossAxisAlignment: CrossAxisAlignment.start, children: [
        _Thumb(label: 'Before', path: entry.beforeImage, onView: () => _view(context, 'before')),
        const SizedBox(width: 6),
        _Thumb(label: 'After', path: entry.afterImage, onView: () => _view(context, 'after')),
        const SizedBox(width: 10),
        Expanded(
          child: Column(crossAxisAlignment: CrossAxisAlignment.start, children: [
            Text(
              entry.comments.isEmpty ? 'No comment' : entry.comments,
              maxLines: 2,
              overflow: TextOverflow.ellipsis,
              style: ui(size: 12.5, weight: FontWeight.w700, color: entry.comments.isEmpty ? p.textMuted : p.ink),
            ),
            const SizedBox(height: 6),
            Row(children: [
              FixtureStatusChip(status: entry.status, label: entry.label),
              if (entry.completedDate != null) ...[
                const SizedBox(width: 6),
                Flexible(
                  child: Text(Dates.human(entry.completedDate),
                      maxLines: 1,
                      overflow: TextOverflow.ellipsis,
                      style: ui(size: 10.5, weight: FontWeight.w700, color: p.textMuted)),
                ),
              ],
            ]),
          ]),
        ),
        if (onTap != null) Icon(Icons.chevron_right, size: 18, color: p.textMuted),
      ]),
    );
  }
}

class _Thumb extends StatelessWidget {
  const _Thumb({required this.label, required this.path, required this.onView});
  final String label;
  final String? path;
  final VoidCallback onView;

  @override
  Widget build(BuildContext context) {
    final p = context.astra;
    final thumb = ClipRRect(
      borderRadius: BorderRadius.circular(10),
      child: SizedBox(
        width: 54,
        height: 54,
        child: Stack(fit: StackFit.expand, children: [
          path != null
              ? Hero(tag: checklistPhotoHeroTag(path!), child: ChecklistPhoto(path: path!))
              : HatchedBox(child: Icon(Icons.photo_camera_outlined, size: 14, color: p.textMuted)),
          Positioned(
            left: 0,
            right: 0,
            bottom: 0,
            child: Container(
              color: ColorManager.black.withValues(alpha: 0.45),
              padding: const EdgeInsets.symmetric(vertical: 1),
              child: Text(label.toUpperCase(),
                  textAlign: TextAlign.center,
                  style: ui(size: 7.5, weight: FontWeight.w800, letterSpacing: 0.5, color: ColorManager.white)),
            ),
          ),
        ]),
      ),
    );
    if (path == null) return thumb;
    return GestureDetector(onTap: onView, behavior: HitTestBehavior.opaque, child: thumb);
  }
}

/// Pending (amber) / In Progress (brand) / Completed (green) chip.
class FixtureStatusChip extends StatelessWidget {
  const FixtureStatusChip({super.key, required this.status, required this.label});
  final String status;
  final String label;

  @override
  Widget build(BuildContext context) {
    final p = context.astra;
    final (Color bg, Color fg) = switch (status) {
      FixtureStatus.completed => (p.successTint, ColorManager.success),
      FixtureStatus.inProgress => (p.tint, p.primary),
      _ => (p.warnTint, p.warnText),
    };
    return Container(
      padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 3),
      decoration: BoxDecoration(color: bg, borderRadius: BorderRadius.circular(20)),
      child: Text(label, style: ui(size: 10, weight: FontWeight.w800, color: fg)),
    );
  }
}
