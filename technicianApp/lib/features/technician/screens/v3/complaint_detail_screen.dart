import 'package:flutter/material.dart';
import 'package:flutter/services.dart' show HapticFeedback;
import 'package:go_router/go_router.dart';
import 'package:image_picker/image_picker.dart';
import 'package:file_picker/file_picker.dart';
import 'package:provider/provider.dart';
import 'package:url_launcher/url_launcher.dart';

import 'package:invo/shared/domain/helpers/formatters.dart';
import 'package:invo/shared/domain/helpers/responsive.dart';
import 'package:invo/shared/utils/components/theme/index.dart';
import 'package:invo/shared/widgets/astra_widgets.dart';

import '../../domain/models/technician_models.dart';
import '../../logic/complaint_detail_cubit/complaint_detail_cubit.dart';
import '../../widgets/v3/status_style.dart';
import '../../widgets/v3/supply_item_sheet.dart';

class ComplaintDetailScreen extends StatefulWidget {
  const ComplaintDetailScreen({super.key});

  @override
  State<ComplaintDetailScreen> createState() => _ComplaintDetailScreenState();
}

class _ComplaintDetailScreenState extends State<ComplaintDetailScreen> {
  final _remarkCtl = TextEditingController();
  final _noteCtl = TextEditingController();
  int? _seededFor; // complaint id whose remark is currently in the controller

  @override
  void dispose() {
    _remarkCtl.dispose();
    _noteCtl.dispose();
    super.dispose();
  }

  @override
  Widget build(BuildContext context) {
    final cubit = context.watch<ComplaintDetailCubit>();
    final detail = cubit.detail;

    // Seed the remark field once we have data (don't clobber ongoing typing).
    if (detail != null && _seededFor != detail.id) {
      _remarkCtl.text = detail.technicianRemark;
      _seededFor = detail.id;
    }

    final current = detail == null ? null : _currentComplaint(detail);

    // The header identity (REG # + status pill) and the property card hero
    // (category + complaint) don't repeat each other; the subtitle stays a
    // quiet locator so nothing is duplicated. Building/group carries it best.
    final headerSubtitle = detail == null
        ? null
        : (detail.propertyInfo.building.isNotEmpty
            ? detail.propertyInfo.building
            : (detail.propertyInfo.group.isNotEmpty ? detail.propertyInfo.group : null));

    return Scaffold(
      backgroundColor: Colors.transparent,
      body: AstraBackground(
        child: Column(
          children: [
            EmeraldHeader(
              leading: HeaderIconButton(icon: Icons.arrow_back, onTap: () => context.pop()),
              title: detail != null ? 'REG #${detail.propertyInfo.registrationId}' : 'Complaint',
              subtitle: headerSubtitle,
              trailing: detail != null
                  ? AstraStatusPill(label: detail.statusLabel, colorName: detail.statusColor)
                  : null,
            ),
            Expanded(
              child: MaxWidthBox(maxWidth: 640, child: _body(context, cubit, detail, current)),
            ),
          ],
        ),
      ),
    );
  }

  Widget _body(BuildContext context, ComplaintDetailCubit cubit, ComplaintDetail? detail, SiblingComplaint? current) {
    final p = context.astra;
    if (cubit.loading && detail == null) {
      return Center(child: CircularProgressIndicator(color: p.primary));
    }
    if (cubit.error != null && detail == null) {
      return EmptyState(
        icon: Icons.wifi_off_rounded,
        title: 'Could not load',
        message: cubit.error,
        action: AstraButton(label: 'Retry', expand: false, onTap: () => cubit.load()),
      );
    }
    if (detail == null) return const SizedBox.shrink();

    final locked = detail.isLocked;
    return RefreshIndicator(
      onRefresh: () => cubit.load(),
      child: ListView(
        padding: const EdgeInsets.fromLTRB(16, 14, 16, 40),
        children: [
          _propertyBar(context, detail, current),
          const SizedBox(height: 14),
          _detailsCard(context, detail.customerInfo),
          const SizedBox(height: 14),
          _remarkCard(context, cubit, detail, locked),
          const SizedBox(height: 14),
          _activityCard(context, detail.activityLog),
          if (detail.allComplaints.length > 1) ...[
            const SizedBox(height: 14),
            _siblingsCard(context, detail),
          ],
          const SizedBox(height: 14),
          _supplyCard(context, cubit, detail, locked),
          const SizedBox(height: 14),
          _notesCard(context, cubit, detail, locked),
          const SizedBox(height: 14),
          _attachmentsCard(context, cubit, detail, locked),
          const SizedBox(height: 20),
          if (!locked) _actions(context, cubit, detail),
        ],
      ),
    );
  }

  /// The complaint this technician is currently viewing within the maintenance
  /// registration — the source of the category + complaint name shown up top.
  SiblingComplaint? _currentComplaint(ComplaintDetail detail) {
    for (final s in detail.allComplaints) {
      if (s.isCurrent) return s;
    }
    return null;
  }

  // ── Property + complaint bar — gradient masthead leads with the job
  // (category + complaint), then a de-duplicated property fact grid. ──
  Widget _propertyBar(BuildContext context, ComplaintDetail detail, SiblingComplaint? current) {
    final p = context.astra;
    final info = detail.propertyInfo;

    // What am I fixing: the current complaint carries category + name.
    final category = current?.categoryName ?? '';
    final complaint = current?.complaintName ?? '';

    // Group and Building often name the same tower — collapse to one "Property"
    // fact when they match so the grid never repeats itself. Location facts run
    // full width (long names); Type + Unit are short, so they share a row.
    final sameProperty = info.group.trim().toLowerCase() == info.building.trim().toLowerCase();
    final wideFacts = <(IconData, String, String)>[
      if (info.group.isNotEmpty && (sameProperty || info.building.isEmpty))
        (Icons.location_city_outlined, 'Property', info.group)
      else ...[
        if (info.group.isNotEmpty) (Icons.location_city_outlined, 'Group', info.group),
        if (info.building.isNotEmpty) (Icons.business_outlined, 'Building', info.building),
      ],
    ];
    final compactFacts = <(IconData, String, String)>[
      if (info.type.isNotEmpty) (Icons.category_outlined, 'Type', info.type),
      if (info.propertyNumber.isNotEmpty) (Icons.door_front_door_outlined, 'Unit', info.propertyNumber),
    ];
    final hasFacts = wideFacts.isNotEmpty || compactFacts.isNotEmpty;

    return AstraCard(
      radius: 18,
      padding: EdgeInsets.zero,
      child: ClipRRect(
        borderRadius: BorderRadius.circular(context.astraTheme.rCard),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.stretch,
          children: [
            // Masthead — hero gradient carrying the job: category eyebrow,
            // complaint name headline, priority pill. The REG # already lives
            // in the page header, so it isn't repeated here.
            Container(
              decoration: BoxDecoration(gradient: p.heroGradient),
              padding: const EdgeInsets.fromLTRB(16, 14, 14, 15),
              child: Stack(
                children: [
                  // Oversized watermark icon anchoring the right edge — kept
                  // clear of content so it reads as a design motif.
                  Positioned(
                    right: -12,
                    top: -20,
                    child: Icon(Icons.handyman_rounded,
                        size: 96, color: Colors.white.withValues(alpha: 0.12)),
                  ),
                  Row(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: [
                      Container(
                        width: 44,
                        height: 44,
                        decoration: BoxDecoration(
                          color: Colors.white.withValues(alpha: 0.16),
                          borderRadius: BorderRadius.circular(13),
                          border: Border.all(color: Colors.white.withValues(alpha: 0.22)),
                        ),
                        child: Icon(Icons.build_outlined, size: 20, color: p.accent),
                      ),
                      const SizedBox(width: 12),
                      Expanded(
                        child: Column(
                          crossAxisAlignment: CrossAxisAlignment.start,
                          children: [
                            Text(category.isEmpty ? 'MAINTENANCE REQUEST' : category.toUpperCase(),
                                maxLines: 1,
                                overflow: TextOverflow.ellipsis,
                                style: ui(
                                    size: 9.5,
                                    weight: FontWeight.w800,
                                    letterSpacing: 1.8,
                                    color: Colors.white.withValues(alpha: 0.72))),
                            const SizedBox(height: 3),
                            Text(complaint.isEmpty ? 'Registration #${info.registrationId}' : complaint,
                                maxLines: 2,
                                overflow: TextOverflow.ellipsis,
                                style: serif(size: 20, color: Colors.white)),
                          ],
                        ),
                      ),
                      if (info.priority.isNotEmpty) ...[
                        const SizedBox(width: 10),
                        _priorityPill(context, info),
                      ],
                    ],
                  ),
                ],
              ),
            ),
            // Fact grid — location fact(s) full width, then Type + Unit paired.
            if (hasFacts)
              Padding(
                padding: const EdgeInsets.fromLTRB(16, 12, 16, 14),
                child: Column(
                  children: [
                    // Location — each on its own full-width row.
                    for (var i = 0; i < wideFacts.length; i++) ...[
                      if (i > 0) _factDivider(p),
                      _factTile(context, wideFacts[i]),
                    ],
                    // Compact pair — Type on the left, Unit on the right.
                    if (compactFacts.isNotEmpty) ...[
                      if (wideFacts.isNotEmpty) _factDivider(p),
                      Row(
                        crossAxisAlignment: CrossAxisAlignment.start,
                        children: [
                          Expanded(child: _factTile(context, compactFacts[0])),
                          if (compactFacts.length > 1) ...[
                            Container(
                              width: 1,
                              height: 30,
                              margin: const EdgeInsets.symmetric(horizontal: 12),
                              color: p.hairline.withValues(alpha: 0.6),
                            ),
                            Expanded(child: _factTile(context, compactFacts[1])),
                          ] else
                            const Spacer(),
                        ],
                      ),
                    ],
                  ],
                ),
              ),
          ],
        ),
      ),
    );
  }

  /// Priority pill for the masthead — glassy white so it reads on any gradient,
  /// with a colour dot carrying the priority tint.
  Widget _priorityPill(BuildContext context, PropertyInfo info) {
    final tint = astraTint(context, info.priorityColor);
    return Container(
      padding: const EdgeInsets.symmetric(horizontal: 11, vertical: 7),
      decoration: BoxDecoration(
        color: Colors.white.withValues(alpha: 0.16),
        borderRadius: BorderRadius.circular(20),
        border: Border.all(color: Colors.white.withValues(alpha: 0.22)),
      ),
      child: Row(
        mainAxisSize: MainAxisSize.min,
        children: [
          Container(
            width: 7,
            height: 7,
            decoration: BoxDecoration(
              color: tint.fg,
              shape: BoxShape.circle,
              boxShadow: [BoxShadow(color: tint.fg.withValues(alpha: 0.7), blurRadius: 6)],
            ),
          ),
          const SizedBox(width: 6),
          Text(info.priority,
              style: ui(size: 10.5, weight: FontWeight.w800, color: Colors.white)),
        ],
      ),
    );
  }

  /// Hairline row separator inside the fact grid.
  Widget _factDivider(AstraPalette p) => Padding(
        padding: const EdgeInsets.symmetric(vertical: 10),
        child: Container(height: 1, color: p.hairline.withValues(alpha: 0.6)),
      );

  /// One masthead fact: icon + tiny caps label over a bold, legible value.
  Widget _factTile(BuildContext context, (IconData, String, String) fact) {
    final p = context.astra;
    return Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        Row(
          children: [
            Icon(fact.$1, size: 11, color: p.textMuted),
            const SizedBox(width: 4),
            Text(fact.$2.toUpperCase(),
                style: ui(size: 9, weight: FontWeight.w800, letterSpacing: 1.1, color: p.textMuted)),
          ],
        ),
        const SizedBox(height: 3),
        Text(fact.$3,
            maxLines: 2,
            overflow: TextOverflow.ellipsis,
            style: ui(size: 12.5, weight: FontWeight.w800, color: p.ink, height: 1.25)),
      ],
    );
  }

  // ── Details ──
  Widget _detailsCard(BuildContext context, CustomerInfo info) {
    return AstraCard(
      radius: 16,
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          SectionLabel('Details'),
          const SizedBox(height: 12),
          if (info.customerName.isNotEmpty) _infoRow(context, Icons.person_outline, 'Customer', info.customerName),
          if (info.customerMobile.isNotEmpty)
            _infoRow(context, Icons.phone_outlined, 'Mobile', info.customerMobile, onTap: () => _call(info.customerMobile)),
          if (info.rentoutId.isNotEmpty) _infoRow(context, Icons.description_outlined, 'RentOut', '#${info.rentoutId}${info.rentoutStatus.isNotEmpty ? ' · ${info.rentoutStatus}' : ''}'),
          if (info.agreementStartDate.isNotEmpty) _infoRow(context, Icons.calendar_today_outlined, 'Agreement start', info.agreementStartDate),
          _infoRow(context, Icons.confirmation_number_outlined, 'Work order', info.workOrderNo),
        ],
      ),
    );
  }

  Widget _infoRow(BuildContext context, IconData icon, String label, String value, {VoidCallback? onTap}) {
    final p = context.astra;
    return Padding(
      padding: const EdgeInsets.only(bottom: 12),
      child: GestureDetector(
        onTap: onTap,
        behavior: HitTestBehavior.opaque,
        child: Row(
          children: [
            Icon(icon, size: 16, color: p.textMuted),
            const SizedBox(width: 10),
            Text(label, style: ui(size: 12, weight: FontWeight.w600, color: p.textMuted)),
            const Spacer(),
            Flexible(
              child: Text(value,
                  maxLines: 1,
                  overflow: TextOverflow.ellipsis,
                  textAlign: TextAlign.right,
                  style: ui(size: 12.5, weight: FontWeight.w700, color: onTap != null ? p.primary : p.ink)),
            ),
            if (onTap != null) ...[const SizedBox(width: 5), Icon(Icons.call, size: 14, color: p.primary)],
          ],
        ),
      ),
    );
  }

  // ── Technician remarks ──
  Widget _remarkCard(BuildContext context, ComplaintDetailCubit cubit, ComplaintDetail detail, bool locked) {
    final p = context.astra;
    return AstraCard(
      radius: 16,
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          SectionLabel('Technician remarks'),
          const SizedBox(height: 10),
          if (locked)
            Text(detail.technicianRemark.isEmpty ? '—' : detail.technicianRemark,
                style: ui(size: 13, weight: FontWeight.w600, color: p.ink, height: 1.4))
          else
            Container(
              padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 4),
              decoration: BoxDecoration(color: p.card, borderRadius: BorderRadius.circular(12), border: Border.all(color: p.cardBorder)),
              child: TextField(
                controller: _remarkCtl,
                maxLines: 4,
                minLines: 3,
                style: ui(size: 13.5, weight: FontWeight.w600, color: p.ink, height: 1.4),
                decoration: InputDecoration(
                  border: InputBorder.none,
                  hintText: 'Describe the work carried out…',
                  hintStyle: ui(size: 13, weight: FontWeight.w500, color: p.textMuted),
                ),
              ),
            ),
        ],
      ),
    );
  }

  // ── Activity log ──
  Widget _activityCard(BuildContext context, ActivityLog log) {
    final steps = <(String, String, String, bool)>[
      ('Created', log.createdBy, log.createdAt, log.createdAt.isNotEmpty),
      ('Assigned', log.assignedBy, log.assignedAt, log.assignedAt.isNotEmpty),
      ('Completed', log.completedBy, log.completedAt, log.completedAt.isNotEmpty),
    ];
    return AstraCard(
      radius: 16,
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          SectionLabel('Activity log'),
          const SizedBox(height: 12),
          for (var i = 0; i < steps.length; i++)
            _timelineRow(context, steps[i].$1, steps[i].$2, steps[i].$3, steps[i].$4, isLast: i == steps.length - 1),
        ],
      ),
    );
  }

  Widget _timelineRow(BuildContext context, String title, String by, String at, bool done, {required bool isLast}) {
    final p = context.astra;
    final color = done ? p.primary : p.hairline;
    return IntrinsicHeight(
      child: Row(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Column(
            children: [
              Container(
                width: 12,
                height: 12,
                decoration: BoxDecoration(
                  color: done ? color : Colors.transparent,
                  border: Border.all(color: done ? color : p.textMuted, width: 2),
                  shape: BoxShape.circle,
                ),
              ),
              if (!isLast) Expanded(child: Container(width: 2, color: p.hairline)),
            ],
          ),
          const SizedBox(width: 12),
          Expanded(
            child: Padding(
              padding: EdgeInsets.only(bottom: isLast ? 0 : 14),
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  Row(
                    children: [
                      Text(title, style: ui(size: 12.5, weight: FontWeight.w800, color: done ? p.ink : p.textMuted)),
                      if (by.isNotEmpty) ...[
                        Text('  ·  ', style: ui(size: 12, weight: FontWeight.w600, color: p.textMuted)),
                        Flexible(child: Text(by, maxLines: 1, overflow: TextOverflow.ellipsis, style: ui(size: 12, weight: FontWeight.w600, color: p.textSecondary))),
                      ],
                    ],
                  ),
                  if (at.isNotEmpty) Text(at, style: ui(size: 10.5, weight: FontWeight.w600, color: p.textMuted)),
                  if (at.isEmpty && !done) Text('Pending', style: ui(size: 10.5, weight: FontWeight.w600, color: p.textMuted)),
                ],
              ),
            ),
          ),
        ],
      ),
    );
  }

  // ── Sibling complaints ──
  Widget _siblingsCard(BuildContext context, ComplaintDetail detail) {
    return AstraCard(
      radius: 16,
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          SectionLabel('Maintenance requests'),
          const SizedBox(height: 10),
          for (var i = 0; i < detail.allComplaints.length; i++)
            Padding(
              padding: EdgeInsets.only(bottom: i == detail.allComplaints.length - 1 ? 0 : 10),
              child: _siblingRow(context, detail.allComplaints[i]),
            ),
        ],
      ),
    );
  }

  /// One "Maintenance requests" row — complaint name + category badge, with a
  /// gradient-lit tint and "YOU" marker on the request currently open.
  Widget _siblingRow(BuildContext context, SiblingComplaint s) {
    final p = context.astra;
    final tint = astraTint(context, s.statusColor);
    return GestureDetector(
      onTap: s.isCurrent ? null : () => context.pushReplacement('/complaints/${s.id}'),
      behavior: HitTestBehavior.opaque,
      child: Container(
        padding: const EdgeInsets.all(12),
        decoration: BoxDecoration(
          gradient: s.isCurrent
              ? LinearGradient(colors: [p.tint, p.tint.withValues(alpha: 0.3)], begin: Alignment.centerLeft, end: Alignment.centerRight)
              : null,
          color: s.isCurrent ? null : p.card,
          borderRadius: BorderRadius.circular(13),
          border: Border.all(
              color: s.isCurrent ? p.primary.withValues(alpha: 0.45) : p.cardBorder, width: s.isCurrent ? 1.3 : 1),
          boxShadow: s.isCurrent ? [BoxShadow(color: p.primary.withValues(alpha: 0.14), blurRadius: 14, offset: const Offset(0, 4))] : null,
        ),
        child: Row(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            IconChip(icon: Icons.build_outlined, size: 38, radius: 11, bg: tint.bg, fg: tint.fg),
            const SizedBox(width: 11),
            Expanded(
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  Row(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: [
                      Expanded(
                        child: Text(s.complaintName.isEmpty ? 'Complaint #${s.id}' : s.complaintName,
                            maxLines: 1,
                            overflow: TextOverflow.ellipsis,
                            style: ui(size: 13, weight: FontWeight.w800, color: p.ink)),
                      ),
                      if (s.isCurrent) ...[
                        const SizedBox(width: 6),
                        Container(
                          padding: const EdgeInsets.symmetric(horizontal: 7, vertical: 2.5),
                          decoration: BoxDecoration(gradient: p.primaryGradient, borderRadius: BorderRadius.circular(20)),
                          child: Text('YOU',
                              style: ui(size: 8.5, weight: FontWeight.w800, color: Colors.white, letterSpacing: 0.6)),
                        ),
                      ],
                    ],
                  ),
                  if (s.categoryName.isNotEmpty) ...[
                    const SizedBox(height: 5),
                    Container(
                      padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 3),
                      decoration: BoxDecoration(
                        color: p.hairline.withValues(alpha: 0.55),
                        borderRadius: BorderRadius.circular(8),
                      ),
                      child: Row(
                        mainAxisSize: MainAxisSize.min,
                        children: [
                          Icon(Icons.category_outlined, size: 10, color: p.textSecondary),
                          const SizedBox(width: 4),
                          Text(s.categoryName, style: ui(size: 10, weight: FontWeight.w700, color: p.textSecondary)),
                        ],
                      ),
                    ),
                  ],
                  if (s.technicianName.isNotEmpty) ...[
                    const SizedBox(height: 6),
                    Row(
                      children: [
                        Icon(Icons.person_outline, size: 11, color: p.textMuted),
                        const SizedBox(width: 3),
                        Flexible(
                          child: Text(s.technicianName,
                              maxLines: 1,
                              overflow: TextOverflow.ellipsis,
                              style: ui(size: 10.5, weight: FontWeight.w600, color: p.textMuted)),
                        ),
                      ],
                    ),
                  ],
                ],
              ),
            ),
            const SizedBox(width: 8),
            Column(
              crossAxisAlignment: CrossAxisAlignment.end,
              children: [
                AstraStatusPill(label: s.statusLabel, colorName: s.statusColor),
                if (!s.isCurrent) ...[const SizedBox(height: 10), Icon(Icons.chevron_right, size: 16, color: p.textMuted)],
              ],
            ),
          ],
        ),
      ),
    );
  }

  // ── Supply items ──
  Widget _supplyCard(BuildContext context, ComplaintDetailCubit cubit, ComplaintDetail detail, bool locked) {
    final p = context.astra;
    final sr = detail.supplyRequest;
    return AstraCard(
      radius: 16,
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          SectionLabel('Supply items',
              trailing: locked
                  ? null
                  : GestureDetector(
                      onTap: () => showAddSupplyItemSheet(context, cubit),
                      child: Row(mainAxisSize: MainAxisSize.min, children: [
                        Icon(Icons.add, size: 15, color: p.primary),
                        const SizedBox(width: 3),
                        Text('Add', style: ui(size: 11.5, weight: FontWeight.w700, color: p.primary)),
                      ]),
                    )),
          const SizedBox(height: 10),
          if (sr.items.isEmpty)
            Text('No items added yet.', style: ui(size: 12.5, weight: FontWeight.w500, color: p.textMuted))
          else
            for (final item in sr.items) _supplyRow(context, cubit, item, locked),
          if (sr.items.isNotEmpty) ...[
            const Divider(height: 22),
            _totalRow(context, 'Subtotal', sr.total),
            if (sr.otherCharges != 0) _totalRow(context, 'Other charges', sr.otherCharges),
            const SizedBox(height: 4),
            _totalRow(context, 'Grand total', sr.grandTotal, emphasise: true),
          ],
        ],
      ),
    );
  }

  Widget _supplyRow(BuildContext context, ComplaintDetailCubit cubit, SupplyItem item, bool locked) {
    final p = context.astra;
    return Padding(
      padding: const EdgeInsets.only(bottom: 10),
      child: Container(
        padding: const EdgeInsets.all(11),
        decoration: BoxDecoration(color: p.card, borderRadius: BorderRadius.circular(12), border: Border.all(color: p.cardBorder)),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Row(
              children: [
                Expanded(
                  child: Text(item.productName.isEmpty ? 'Item #${item.id}' : item.productName,
                      maxLines: 1, overflow: TextOverflow.ellipsis, style: ui(size: 13, weight: FontWeight.w700, color: p.ink)),
                ),
                StatusPill(
                  label: item.mode,
                  bg: item.mode == 'Damaged' ? p.dangerTint : p.tint,
                  fg: item.mode == 'Damaged' ? AstraPalette.danger : p.primary,
                ),
              ],
            ),
            const SizedBox(height: 6),
            Row(
              children: [
                Icon(Icons.store_outlined, size: 12, color: p.textMuted),
                const SizedBox(width: 4),
                Text(item.branchName, style: ui(size: 10.5, weight: FontWeight.w600, color: p.textMuted)),
                const Spacer(),
                Text('${qtyLabel(item.quantity)} × ${Money.plain(item.unitPrice)}',
                    style: ui(size: 11.5, weight: FontWeight.w600, color: p.textSecondary)),
                const SizedBox(width: 8),
                Text(Money.of(item.total), style: ui(size: 12.5, weight: FontWeight.w800, color: p.ink)),
              ],
            ),
            if (item.remarks.isNotEmpty)
              Padding(
                padding: const EdgeInsets.only(top: 5),
                child: Text(item.remarks, style: ui(size: 11, weight: FontWeight.w500, color: p.textMuted)),
              ),
            if (!locked)
              Padding(
                padding: const EdgeInsets.only(top: 8),
                child: Row(
                  children: [
                    _tinyBtn(context, Icons.edit_outlined, 'Edit', p.primary, () => _editItem(context, cubit, item)),
                    const SizedBox(width: 8),
                    _tinyBtn(context, Icons.delete_outline, 'Delete', AstraPalette.danger,
                        () => _confirmDeleteItem(context, cubit, item)),
                  ],
                ),
              ),
          ],
        ),
      ),
    );
  }

  Widget _tinyBtn(BuildContext context, IconData icon, String label, Color color, VoidCallback onTap) {
    return GestureDetector(
      onTap: onTap,
      child: Container(
        padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 6),
        decoration: BoxDecoration(color: color.withValues(alpha: 0.12), borderRadius: BorderRadius.circular(9)),
        child: Row(mainAxisSize: MainAxisSize.min, children: [
          Icon(icon, size: 13, color: color),
          const SizedBox(width: 5),
          Text(label, style: ui(size: 11, weight: FontWeight.w700, color: color)),
        ]),
      ),
    );
  }

  Widget _totalRow(BuildContext context, String label, double value, {bool emphasise = false}) {
    final p = context.astra;
    return Padding(
      padding: const EdgeInsets.symmetric(vertical: 2),
      child: Row(
        children: [
          Text(label, style: ui(size: emphasise ? 13 : 12, weight: emphasise ? FontWeight.w800 : FontWeight.w600, color: emphasise ? p.ink : p.textSecondary)),
          const Spacer(),
          Text(Money.of(value), style: emphasise ? serif(size: 17, color: p.primary) : ui(size: 12.5, weight: FontWeight.w700, color: p.ink)),
        ],
      ),
    );
  }

  // ── Notes ──
  Widget _notesCard(BuildContext context, ComplaintDetailCubit cubit, ComplaintDetail detail, bool locked) {
    final p = context.astra;
    final notes = detail.supplyRequest.notes;
    return AstraCard(
      radius: 16,
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          SectionLabel('Notes'),
          const SizedBox(height: 10),
          if (!locked)
            Row(
              children: [
                Expanded(
                  child: Container(
                    padding: const EdgeInsets.symmetric(horizontal: 12),
                    decoration: BoxDecoration(color: p.card, borderRadius: BorderRadius.circular(12), border: Border.all(color: p.cardBorder)),
                    child: TextField(
                      controller: _noteCtl,
                      style: ui(size: 13, weight: FontWeight.w600, color: p.ink),
                      decoration: InputDecoration(
                        isDense: true,
                        contentPadding: const EdgeInsets.symmetric(vertical: 12),
                        border: InputBorder.none,
                        hintText: 'Add a note…',
                        hintStyle: ui(size: 12.5, weight: FontWeight.w500, color: p.textMuted),
                      ),
                    ),
                  ),
                ),
                const SizedBox(width: 8),
                GestureDetector(
                  onTap: cubit.busy ? null : () => _addNote(cubit),
                  child: Container(
                    width: 44,
                    height: 44,
                    decoration: BoxDecoration(gradient: p.primaryGradient, borderRadius: BorderRadius.circular(12)),
                    child: const Icon(Icons.send, color: Colors.white, size: 18),
                  ),
                ),
              ],
            ),
          if (notes.isNotEmpty) const SizedBox(height: 12),
          for (final note in notes)
            Padding(
              padding: const EdgeInsets.only(bottom: 8),
              child: Row(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  Icon(Icons.sticky_note_2_outlined, size: 15, color: p.textMuted),
                  const SizedBox(width: 8),
                  Expanded(
                    child: Column(
                      crossAxisAlignment: CrossAxisAlignment.start,
                      children: [
                        Text(note.note, style: ui(size: 12.5, weight: FontWeight.w600, color: p.ink, height: 1.3)),
                        Text('${note.creator}${note.createdAt.isNotEmpty ? ' · ${note.createdAt}' : ''}',
                            style: ui(size: 10, weight: FontWeight.w600, color: p.textMuted)),
                      ],
                    ),
                  ),
                  if (!locked)
                    GestureDetector(
                      onTap: () => cubit.deleteNote(note.id),
                      child: Icon(Icons.close, size: 15, color: p.textMuted),
                    ),
                ],
              ),
            ),
          if (notes.isEmpty && locked)
            Text('No notes.', style: ui(size: 12.5, weight: FontWeight.w500, color: p.textMuted)),
        ],
      ),
    );
  }

  // ── Attachments ──
  Widget _attachmentsCard(BuildContext context, ComplaintDetailCubit cubit, ComplaintDetail detail, bool locked) {
    final p = context.astra;
    final images = detail.supplyRequest.images;
    return AstraCard(
      radius: 16,
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          SectionLabel('Attachments',
              trailing: locked
                  ? null
                  : GestureDetector(
                      onTap: () => _pickAttachment(context, cubit),
                      child: Row(mainAxisSize: MainAxisSize.min, children: [
                        Icon(Icons.add, size: 15, color: p.primary),
                        const SizedBox(width: 3),
                        Text('Add', style: ui(size: 11.5, weight: FontWeight.w700, color: p.primary)),
                      ]),
                    )),
          const SizedBox(height: 10),
          if (images.isEmpty)
            Text('No files uploaded.', style: ui(size: 12.5, weight: FontWeight.w500, color: p.textMuted))
          else
            Wrap(
              spacing: 10,
              runSpacing: 10,
              children: [for (final a in images) _thumb(context, cubit, a, locked)],
            ),
        ],
      ),
    );
  }

  Widget _thumb(BuildContext context, ComplaintDetailCubit cubit, Attachment a, bool locked) {
    final p = context.astra;
    Widget preview;
    if (a.isImage) {
      preview = ClipRRect(
        borderRadius: BorderRadius.circular(12),
        child: Image.network(a.path, width: 82, height: 82, fit: BoxFit.cover,
            errorBuilder: (_, __, ___) => _fileIcon(p, Icons.broken_image_outlined)),
      );
    } else {
      preview = _fileIcon(p, a.isVideo ? Icons.play_circle_outline : Icons.picture_as_pdf_outlined);
    }
    return GestureDetector(
      onTap: () => _openAttachment(context, a),
      child: Stack(
        children: [
          preview,
          if (!locked)
            Positioned(
              top: -2,
              right: -2,
              child: GestureDetector(
                onTap: () => _confirmDeleteAttachment(context, cubit, a),
                child: Container(
                  margin: const EdgeInsets.all(4),
                  padding: const EdgeInsets.all(2),
                  decoration: BoxDecoration(color: Colors.black.withValues(alpha: 0.55), shape: BoxShape.circle),
                  child: const Icon(Icons.close, size: 13, color: Colors.white),
                ),
              ),
            ),
        ],
      ),
    );
  }

  Widget _fileIcon(AstraPalette p, IconData icon) => Container(
        width: 82,
        height: 82,
        decoration: BoxDecoration(color: p.tint, borderRadius: BorderRadius.circular(12)),
        child: Icon(icon, size: 30, color: p.primary),
      );

  // ── Actions ──
  Widget _actions(BuildContext context, ComplaintDetailCubit cubit, ComplaintDetail detail) {
    return Row(
      children: [
        Expanded(
          child: AstraButton(
            label: 'Save',
            icon: Icons.save_outlined,
            busy: cubit.busy,
            onTap: () => _save(cubit),
          ),
        ),
        const SizedBox(width: 12),
        Expanded(
          child: AstraButton(
            label: 'Complete',
            icon: Icons.check_circle_outline,
            gold: true,
            busy: cubit.busy,
            onTap: () => _complete(context, cubit),
          ),
        ),
      ],
    );
  }

  // ── Actions logic ──
  Future<void> _save(ComplaintDetailCubit cubit) async {
    FocusManager.instance.primaryFocus?.unfocus();
    final ok = await cubit.saveRemark(_remarkCtl.text.trim());
    _toast(ok ? 'Saved' : (cubit.actionError ?? 'Could not save'));
  }

  Future<void> _complete(BuildContext context, ComplaintDetailCubit cubit) async {
    if (_remarkCtl.text.trim().isEmpty) {
      _toast('Add a technician remark before completing.');
      return;
    }
    final confirmed = await _confirm(context,
        title: 'Complete complaint?',
        message: 'This marks the complaint completed. If every sibling is done, the maintenance is auto-completed. This cannot be undone from the app.',
        confirmLabel: 'Complete');
    if (confirmed != true) return;
    HapticFeedback.mediumImpact();
    final ok = await cubit.complete(_remarkCtl.text.trim());
    _toast(ok ? 'Complaint completed' : (cubit.actionError ?? 'Could not complete'));
  }

  Future<void> _addNote(ComplaintDetailCubit cubit) async {
    final text = _noteCtl.text.trim();
    if (text.isEmpty) return;
    FocusManager.instance.primaryFocus?.unfocus();
    final ok = await cubit.addNote(text);
    if (ok) _noteCtl.clear();
    if (!ok) _toast(cubit.actionError ?? 'Could not add note');
  }

  Future<void> _confirmDeleteItem(BuildContext context, ComplaintDetailCubit cubit, SupplyItem item) async {
    final ok = await _confirm(context, title: 'Delete item?', message: item.productName, confirmLabel: 'Delete', danger: true);
    if (ok == true) {
      final done = await cubit.deleteSupplyItem(item.id);
      if (!done) _toast(cubit.actionError ?? 'Could not delete item');
    }
  }

  Future<void> _confirmDeleteAttachment(BuildContext context, ComplaintDetailCubit cubit, Attachment a) async {
    final ok = await _confirm(context, title: 'Delete attachment?', message: a.name, confirmLabel: 'Delete', danger: true);
    if (ok == true) {
      final done = await cubit.deleteAttachment(a.id);
      if (!done) _toast(cubit.actionError ?? 'Could not delete attachment');
    }
  }

  Future<void> _editItem(BuildContext context, ComplaintDetailCubit cubit, SupplyItem item) async {
    final qtyCtl = TextEditingController(text: qtyLabel(item.quantity));
    final priceCtl = TextEditingController(text: item.unitPrice.toStringAsFixed(2));
    final remarksCtl = TextEditingController(text: item.remarks);
    var mode = item.mode;
    final p = context.astra;

    await showModalBottomSheet<void>(
      context: context,
      isScrollControlled: true,
      backgroundColor: Colors.transparent,
      builder: (sheetCtx) => StatefulBuilder(
        builder: (sheetCtx, setSheet) => Padding(
          padding: EdgeInsets.only(bottom: MediaQuery.of(sheetCtx).viewInsets.bottom),
          child: Container(
            decoration: BoxDecoration(color: p.canvas, borderRadius: const BorderRadius.vertical(top: Radius.circular(26))),
            child: SafeArea(
              top: false,
              child: Padding(
                padding: const EdgeInsets.fromLTRB(18, 14, 18, 18),
                child: Column(
                  mainAxisSize: MainAxisSize.min,
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    Text('Edit item', style: serif(size: 20, color: p.ink)),
                    const SizedBox(height: 4),
                    Text(item.productName, style: ui(size: 12, weight: FontWeight.w600, color: p.textMuted)),
                    const SizedBox(height: 16),
                    Row(children: [
                      for (final m in const ['New', 'Damaged'])
                        Padding(
                          padding: const EdgeInsets.only(right: 8),
                          child: AstraChip(label: m, active: mode == m, onTap: () => setSheet(() => mode = m)),
                        ),
                    ]),
                    const SizedBox(height: 14),
                    Row(children: [
                      Expanded(child: _sheetField(context, qtyCtl, 'Quantity', keyboardType: const TextInputType.numberWithOptions(decimal: true))),
                      const SizedBox(width: 10),
                      Expanded(child: _sheetField(context, priceCtl, 'Unit price', keyboardType: const TextInputType.numberWithOptions(decimal: true))),
                    ]),
                    const SizedBox(height: 12),
                    _sheetField(context, remarksCtl, 'Remarks'),
                    const SizedBox(height: 18),
                    AstraButton(
                      label: 'Save changes',
                      onTap: () async {
                        Navigator.of(sheetCtx).pop();
                        final done = await cubit.updateSupplyItem(
                          item.id,
                          mode: mode,
                          quantity: double.tryParse(qtyCtl.text.trim()),
                          unitPrice: double.tryParse(priceCtl.text.trim()),
                          remarks: remarksCtl.text.trim(),
                        );
                        if (!done) _toast(cubit.actionError ?? 'Could not update item');
                      },
                    ),
                  ],
                ),
              ),
            ),
          ),
        ),
      ),
    );
  }

  Widget _sheetField(BuildContext context, TextEditingController ctl, String label, {TextInputType? keyboardType}) {
    final p = context.astra;
    return Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        Text(label.toUpperCase(), style: ui(size: 10, weight: FontWeight.w800, color: p.textMuted, letterSpacing: 1)),
        const SizedBox(height: 6),
        Container(
          padding: const EdgeInsets.symmetric(horizontal: 12),
          decoration: BoxDecoration(color: p.card, borderRadius: BorderRadius.circular(12), border: Border.all(color: p.cardBorder)),
          child: TextField(
            controller: ctl,
            keyboardType: keyboardType,
            style: ui(size: 13.5, weight: FontWeight.w600, color: p.ink),
            decoration: const InputDecoration(isDense: true, contentPadding: EdgeInsets.symmetric(vertical: 13), border: InputBorder.none),
          ),
        ),
      ],
    );
  }

  Future<void> _pickAttachment(BuildContext context, ComplaintDetailCubit cubit) async {
    final choice = await showModalBottomSheet<String>(
      context: context,
      backgroundColor: Colors.transparent,
      builder: (ctx) {
        final p = ctx.astra;
        Widget tile(IconData icon, String label, String value) => AstraCard(
              radius: 12,
              onTap: () => Navigator.of(ctx).pop(value),
              child: Row(children: [
                IconChip(icon: icon, size: 34, radius: 10, bg: p.tint),
                const SizedBox(width: 12),
                Text(label, style: ui(size: 13.5, weight: FontWeight.w700, color: p.ink)),
              ]),
            );
        return Container(
          decoration: BoxDecoration(color: p.canvas, borderRadius: const BorderRadius.vertical(top: Radius.circular(26))),
          child: SafeArea(
            top: false,
            child: Padding(
              padding: const EdgeInsets.fromLTRB(16, 16, 16, 16),
              child: Column(mainAxisSize: MainAxisSize.min, children: [
                tile(Icons.camera_alt_outlined, 'Take a photo', 'camera'),
                const SizedBox(height: 10),
                tile(Icons.photo_library_outlined, 'Choose from gallery', 'gallery'),
                const SizedBox(height: 10),
                tile(Icons.insert_drive_file_outlined, 'Pick a file (PDF / doc)', 'file'),
              ]),
            ),
          ),
        );
      },
    );
    if (choice == null) return;

    final paths = <String>[];
    try {
      if (choice == 'camera') {
        final x = await ImagePicker().pickImage(source: ImageSource.camera, imageQuality: 82);
        if (x != null) paths.add(x.path);
      } else if (choice == 'gallery') {
        final xs = await ImagePicker().pickMultiImage(imageQuality: 82);
        paths.addAll(xs.map((e) => e.path));
      } else {
        final res = await FilePicker.platform.pickFiles(
          allowMultiple: true,
          type: FileType.custom,
          allowedExtensions: const ['pdf', 'doc', 'docx', 'jpg', 'jpeg', 'png', 'mp4', 'mov'],
        );
        if (res != null) paths.addAll(res.paths.whereType<String>());
      }
    } catch (e) {
      _toast('Could not pick a file');
      return;
    }
    if (paths.isEmpty) return;
    final ok = await cubit.addAttachments(paths);
    _toast(ok ? 'Uploaded' : (cubit.actionError ?? 'Upload failed'));
  }

  void _openAttachment(BuildContext context, Attachment a) {
    if (a.isImage) {
      Navigator.of(context).push(MaterialPageRoute(
        builder: (_) => _Lightbox(url: a.path, name: a.name),
        fullscreenDialog: true,
      ));
    } else {
      _launch(a.path);
    }
  }

  Future<void> _call(String mobile) => _launch('tel:$mobile');

  Future<void> _launch(String url) async {
    final uri = Uri.parse(url);
    if (await canLaunchUrl(uri)) {
      await launchUrl(uri, mode: LaunchMode.externalApplication);
    } else {
      _toast('Could not open');
    }
  }

  Future<bool?> _confirm(BuildContext context,
          {required String title, required String message, required String confirmLabel, bool danger = false}) =>
      showDialog<bool>(
        context: context,
        builder: (ctx) => AlertDialog(
          title: Text(title),
          content: Text(message),
          actions: [
            TextButton(onPressed: () => Navigator.pop(ctx, false), child: const Text('Cancel')),
            TextButton(
              onPressed: () => Navigator.pop(ctx, true),
              child: Text(confirmLabel, style: TextStyle(color: danger ? AstraPalette.danger : null)),
            ),
          ],
        ),
      );

  void _toast(String msg) {
    if (!mounted) return;
    ScaffoldMessenger.of(context)
      ..hideCurrentSnackBar()
      ..showSnackBar(SnackBar(content: Text(msg)));
  }
}

/// Full-screen pinch-to-zoom image viewer.
class _Lightbox extends StatelessWidget {
  const _Lightbox({required this.url, required this.name});
  final String url;
  final String name;

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      backgroundColor: Colors.black,
      appBar: AppBar(
        backgroundColor: Colors.black,
        foregroundColor: Colors.white,
        title: Text(name, style: const TextStyle(fontSize: 14)),
      ),
      body: Center(
        child: InteractiveViewer(
          minScale: 0.8,
          maxScale: 4,
          child: Image.network(url, fit: BoxFit.contain,
              errorBuilder: (_, __, ___) => const Icon(Icons.broken_image_outlined, color: Colors.white38, size: 60)),
        ),
      ),
    );
  }
}
