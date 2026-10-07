import 'dart:async';

import 'package:flutter/material.dart';
import 'package:flutter_bloc/flutter_bloc.dart';
import 'package:go_router/go_router.dart';
import 'package:intl/intl.dart';

import 'package:invo/features/auth/logic/auth_cubit/auth_cubit.dart';
import 'package:invo/shared/domain/constants/data_fetching_status.dart';
import 'package:invo/shared/domain/helpers/responsive.dart';
import 'package:invo/shared/utils/components/theme/index.dart';
import 'package:invo/shared/utils/router/routes.dart';
import 'package:invo/shared/widgets/astra_range_picker.dart';
import 'package:invo/shared/widgets/astra_widgets.dart';
import 'package:invo/shared/widgets/skeleton.dart';
import 'package:invo/shared/widgets/tablet_widgets.dart';

import '../../domain/models/checklist_models.dart';
import '../../logic/checklist_inbox_cubit/checklist_inbox_cubit.dart';
import '../../widgets/v3/atelier_parts.dart';
import '../../widgets/v3/checklist_job_card.dart';

int _dayDiff(DateTime d, DateTime today) =>
    DateTime.utc(d.year, d.month, d.day).difference(DateTime.utc(today.year, today.month, today.day)).inDays;

/// A date group in the inbox. Open rows group forward (Overdue → Later);
/// sealed rows group backward (Today → Earlier), newest first.
class _Group {
  const _Group(this.order, this.label, {this.alert = false});
  final int order;
  final String label;
  final bool alert;

  static _Group of(ChecklistJob j, DateTime today, {required bool prefixSealed}) {
    final d = j.scheduled;
    if (!j.sealed) {
      if (d == null) return const _Group(4, 'No date');
      final diff = _dayDiff(d, today);
      if (diff < 0) return const _Group(0, 'Overdue', alert: true);
      if (diff == 0) return const _Group(1, 'Today');
      if (diff < 7) return const _Group(2, 'This week');
      return const _Group(3, 'Later');
    }
    final pre = prefixSealed ? 'Completed · ' : '';
    if (d == null) return _Group(15, '${pre}No date');
    final diff = _dayDiff(d, today);
    if (diff > 0) return _Group(10, '${pre}Upcoming');
    if (diff == 0) return _Group(11, '${pre}Today');
    if (diff > -7) return _Group(12, '${pre}Past 7 days');
    if (d.year == today.year && d.month == today.month) return _Group(13, '${pre}This month');
    return _Group(14, '${pre}Earlier');
  }
}

/// Checklists tab (C1): hand-over jobs (rent-out × phase) the user
/// coordinates, grouped by date, with search, status / date filters and the
/// phase / to-sign tabs.
class ChecklistInboxScreen extends StatefulWidget {
  const ChecklistInboxScreen({super.key});

  @override
  State<ChecklistInboxScreen> createState() => _ChecklistInboxScreenState();
}

class _ChecklistInboxScreenState extends State<ChecklistInboxScreen> {
  final _searchCtl = TextEditingController();
  Timer? _debounce;

  @override
  void initState() {
    super.initState();
    _searchCtl.addListener(_onSearchChanged);
    WidgetsBinding.instance.addPostFrameCallback((_) {
      if (!mounted) return;
      context.read<ChecklistInboxCubit>()
        ..reset()
        ..load();
    });
  }

  @override
  void dispose() {
    _debounce?.cancel();
    _searchCtl.removeListener(_onSearchChanged);
    _searchCtl.dispose();
    super.dispose();
  }

  void _onSearchChanged() {
    setState(() {});
    _debounce?.cancel();
    _debounce = Timer(const Duration(milliseconds: 400), () {
      if (!mounted) return;
      context.read<ChecklistInboxCubit>().setSearch(_searchCtl.text);
    });
  }

  /// Set while a hand-over is open — a double tap must not push it twice.
  bool _opening = false;

  Future<void> _open(ChecklistJob job) async {
    if (_opening) return;
    _opening = true;
    try {
      await context.push(Routes.checklistDetail(job.id, phase: job.phase));
    } finally {
      _opening = false;
    }
    if (!mounted) return;
    context.read<ChecklistInboxCubit>().load(silent: true);
  }

  Future<void> _pickDates(ChecklistInboxCubit cubit) async {
    final preset = await showModalBottomSheet<ChecklistDatePreset>(
      context: context,
      isScrollControlled: true,
      backgroundColor: Colors.transparent,
      builder: (_) => _DatePresetSheet(current: cubit.state.datePreset),
    );
    if (preset == null || !mounted) return;
    if (preset != ChecklistDatePreset.custom) {
      cubit.setDatePreset(preset);
      return;
    }
    final now = DateTime.now();
    final s = cubit.state;
    final range = await showAstraDateRangePicker(
      context,
      title: 'Hand-over dates',
      firstDate: DateTime(now.year - 3),
      lastDate: DateTime(now.year + 2, 12, 31),
      initialDateRange: s.fromDate != null && s.toDate != null ? DateTimeRange(start: s.fromDate!, end: s.toDate!) : null,
    );
    if (range == null || !mounted) return;
    cubit.setDatePreset(ChecklistDatePreset.custom, custom: (range.start, range.end));
  }

  @override
  Widget build(BuildContext context) {
    final p = context.astra;
    final tablet = context.isTablet;
    final list = MaxWidthBox(
          maxWidth: checklistContentWidth(context),
          child: BlocBuilder<ChecklistInboxCubit, ChecklistInboxState>(
            builder: (context, state) {
              final cubit = context.read<ChecklistInboxCubit>();
              return RefreshIndicator(
                color: p.primary,
                onRefresh: () => cubit.load(silent: true),
                child: CustomScrollView(
                  physics: const AlwaysScrollableScrollPhysics(),
                  slivers: [
                    if (tablet) const SliverToBoxAdapter(child: SizedBox(height: 16)) else const SliverToBoxAdapter(child: _InboxHeader()),
                    SliverToBoxAdapter(child: _SummaryStrip(jobs: state.jobs)),
                    SliverToBoxAdapter(
                      child: _SearchField(controller: _searchCtl, onClear: () {
                        _searchCtl.clear();
                        cubit.setSearch('');
                      }),
                    ),
                    SliverToBoxAdapter(
                      child: _FilterBar(
                        state: state,
                        onDates: () => _pickDates(cubit),
                        onStatus: cubit.setStatusFilter,
                      ),
                    ),
                    if (state.hasActiveFilters)
                      SliverToBoxAdapter(child: _ActiveFilters(state: state, onClear: cubit.clearFilters)),
                    SliverToBoxAdapter(child: _FilterTabs(state: state, onPick: cubit.setFilter)),
                    ..._body(context, state, cubit),
                    SliverToBoxAdapter(child: SizedBox(height: tablet ? 40 : 120)),
                  ],
                ),
              );
            },
          ),
        );
    return Scaffold(
      backgroundColor: Colors.transparent,
      body: AstraBackground(
        child: tablet
            // Tablet: the flat page head over a hairline (no header band, and
            // no avatar — the rail carries it).
            ? SafeArea(
                bottom: false,
                child: Column(children: [
                  TabletPageHead(
                    title: 'Hand-overs',
                    subtitle: DateFormat('EEEE · d MMMM').format(DateTime.now()),
                  ),
                  Expanded(child: list),
                ]),
              )
            : list,
      ),
    );
  }

  List<Widget> _body(BuildContext context, ChecklistInboxState state, ChecklistInboxCubit cubit) {
    if (state.jobs.isEmpty && (state.status == DataFetchStatus.waiting || state.status == DataFetchStatus.idle)) {
      return const [SliverToBoxAdapter(child: SkeletonList(count: 6, padding: EdgeInsets.fromLTRB(14, 8, 14, 16), itemHeight: 76))];
    }
    if (state.jobs.isEmpty && state.status == DataFetchStatus.failed) {
      return [
        SliverFillRemaining(
          hasScrollBody: false,
          child: EmptyState(
            icon: Icons.wifi_off_rounded,
            title: 'Could not load',
            message: state.errorMessage,
            action: AstraButton(label: 'Retry', expand: false, onTap: () => cubit.load()),
          ),
        ),
      ];
    }
    final visible = state.visibleJobs;
    if (visible.isEmpty) {
      final String message;
      if (state.search.isNotEmpty) {
        message = 'Nothing matches "${state.search}".';
      } else if (state.hasActiveFilters) {
        message = 'No hand-overs match these filters.';
      } else {
        message = 'Move-ins and move-outs you coordinate will appear here.';
      }
      return [
        SliverFillRemaining(
          hasScrollBody: false,
          child: EmptyState(
            icon: Icons.fact_check_outlined,
            title: 'No hand-overs',
            message: message,
            action: state.hasActiveFilters
                ? AstraButton(label: 'Clear filters', expand: false, onTap: cubit.clearFilters)
                : null,
          ),
        ),
      ];
    }

    final today = DateTime.now();
    final prefixSealed = state.statusFilter == ChecklistStatusFilter.all;
    final groups = <int, (_Group, List<ChecklistJob>)>{};
    for (final j in visible) {
      final g = _Group.of(j, today, prefixSealed: prefixSealed);
      groups.putIfAbsent(g.order, () => (g, <ChecklistJob>[])).$2.add(j);
    }
    final orderedKeys = groups.keys.toList()..sort();
    for (final k in orderedKeys) {
      if (k >= 10) {
        groups[k]!.$2.sort((a, b) => (b.scheduledDate ?? '').compareTo(a.scheduledDate ?? ''));
      }
    }

    // Phone: one lazy list (headers + rows). Tablet: a header + card grid per group.
    if (!context.isTablet) {
      final rows = <Object>[];
      for (final k in orderedKeys) {
        rows
          ..add(groups[k]!.$1)
          ..addAll(groups[k]!.$2);
      }
      return [
        SliverList.builder(
          itemCount: rows.length,
          itemBuilder: (_, i) {
            final row = rows[i];
            if (row is _Group) return _GroupHeader(label: row.label, alert: row.alert);
            final job = row as ChecklistJob;
            return ChecklistJobCard(key: ValueKey(job.rowKey), job: job, onTap: () => _open(job));
          },
        ),
      ];
    }
    final cols = checklistGridColumns(context, tablet: 2, wide: 3);
    return [
      for (final k in orderedKeys) ...[
        SliverToBoxAdapter(child: _GroupHeader(label: groups[k]!.$1.label, alert: groups[k]!.$1.alert)),
        SliverPadding(
          padding: const EdgeInsets.fromLTRB(14, 0, 14, 4),
          sliver: SliverGrid.builder(
            gridDelegate: SliverGridDelegateWithFixedCrossAxisCount(
              crossAxisCount: cols,
              mainAxisSpacing: 10,
              crossAxisSpacing: 10,
              mainAxisExtent: 92,
            ),
            itemCount: groups[k]!.$2.length,
            itemBuilder: (_, i) {
              final job = groups[k]!.$2[i];
              return ChecklistJobCard(key: ValueKey(job.rowKey), job: job, margin: EdgeInsets.zero, onTap: () => _open(job));
            },
          ),
        ),
      ],
    ];
  }
}

class _InboxHeader extends StatelessWidget {
  const _InboxHeader();

  @override
  Widget build(BuildContext context) {
    final p = context.astra;
    final name = context.read<AuthCubit>().user?.name ?? '';
    final initials = name
        .trim()
        .split(RegExp(r'\s+'))
        .where((s) => s.isNotEmpty)
        .take(2)
        .map((s) => s[0].toUpperCase())
        .join();
    return SafeArea(
      bottom: false,
      child: Padding(
        padding: const EdgeInsets.fromLTRB(18, 10, 18, 12),
        child: Row(crossAxisAlignment: CrossAxisAlignment.end, children: [
          Expanded(
            child: Column(crossAxisAlignment: CrossAxisAlignment.start, children: [
              AtelierKicker(DateFormat('EEEE · d MMMM').format(DateTime.now())),
              const SizedBox(height: 4),
              Text('Hand-overs', style: serif(size: 31, color: p.ink, height: 1.05)),
            ]),
          ),
          Container(
            width: 40,
            height: 40,
            alignment: Alignment.center,
            decoration: BoxDecoration(
              shape: BoxShape.circle,
              gradient: p.heroGradient,
              boxShadow: [
                BoxShadow(color: p.accent, spreadRadius: 3.5),
                BoxShadow(color: p.canvas, spreadRadius: 2),
              ],
            ),
            child: Text(initials.isEmpty ? '·' : initials, style: ui(size: 13, weight: FontWeight.w800, color: p.heroLabel)),
          ),
        ]),
      ),
    );
  }
}

class _SummaryStrip extends StatelessWidget {
  const _SummaryStrip({required this.jobs});
  final List<ChecklistJob> jobs;

  @override
  Widget build(BuildContext context) {
    final p = context.astra;
    final today = DateTime.now();
    final open = jobs.where((j) => !j.sealed).toList();
    int count(String label) => open.where((j) => _Group.of(j, today, prefixSealed: false).label == label).length;
    final cells = [
      ('Overdue', count('Overdue'), ColorManager.danger),
      ('Today', count('Today'), p.ink),
      ('This week', count('This week'), p.ink),
      ('To sign', open.where((j) => j.needsSignatures).length, p.goldText),
    ];
    return Container(
      margin: const EdgeInsets.fromLTRB(14, 0, 14, 12),
      padding: const EdgeInsets.symmetric(vertical: 10, horizontal: 4),
      decoration: BoxDecoration(
        color: p.cardSolid,
        borderRadius: BorderRadius.circular(18),
        border: Border.all(color: p.hairline),
      ),
      child: IntrinsicHeight(
        child: Row(children: [
          for (var i = 0; i < cells.length; i++) ...[
            if (i > 0) VerticalDivider(width: 1, thickness: 1, color: p.hairline),
            Expanded(
              child: Column(children: [
                Text('${cells[i].$2}',
                    style: ui(
                        size: 19,
                        weight: FontWeight.w800,
                        letterSpacing: -0.4,
                        color: cells[i].$2 == 0 ? p.textMuted : cells[i].$3)),
                const SizedBox(height: 2),
                Text(cells[i].$1.toUpperCase(),
                    maxLines: 1,
                    overflow: TextOverflow.ellipsis,
                    style: ui(size: 9, weight: FontWeight.w800, letterSpacing: 0.6, color: p.textMuted)),
              ]),
            ),
          ],
        ]),
      ),
    );
  }
}

class _SearchField extends StatelessWidget {
  const _SearchField({required this.controller, required this.onClear});
  final TextEditingController controller;
  final VoidCallback onClear;

  @override
  Widget build(BuildContext context) {
    final p = context.astra;
    return Container(
      margin: const EdgeInsets.fromLTRB(14, 0, 14, 8),
      padding: const EdgeInsets.symmetric(horizontal: 12),
      height: 42,
      decoration: BoxDecoration(
        color: p.cardSolid,
        borderRadius: BorderRadius.circular(14),
        border: Border.all(color: p.hairline),
      ),
      child: Row(children: [
        Icon(Icons.search, size: 18, color: p.textMuted),
        const SizedBox(width: 9),
        Expanded(
          child: TextField(
            controller: controller,
            textInputAction: TextInputAction.search,
            style: ui(size: 13, weight: FontWeight.w600, color: p.ink),
            decoration: InputDecoration(
              isDense: true,
              border: InputBorder.none,
              hintText: 'Unit, building or lessee',
              hintStyle: ui(size: 13, weight: FontWeight.w500, color: p.textMuted),
            ),
          ),
        ),
        if (controller.text.isNotEmpty)
          GestureDetector(onTap: onClear, child: Icon(Icons.close, size: 17, color: p.textMuted)),
      ]),
    );
  }
}

/// Compact row under the search: date-range chip + Open / Completed / All.
class _FilterBar extends StatelessWidget {
  const _FilterBar({required this.state, required this.onDates, required this.onStatus});
  final ChecklistInboxState state;
  final VoidCallback onDates;
  final ValueChanged<ChecklistStatusFilter> onStatus;

  @override
  Widget build(BuildContext context) {
    final p = context.astra;
    final rangeOn = state.datePreset != ChecklistDatePreset.all;
    return Padding(
      padding: const EdgeInsets.fromLTRB(14, 0, 14, 10),
      child: Row(children: [
        Expanded(
          child: GestureDetector(
            onTap: onDates,
            behavior: HitTestBehavior.opaque,
            child: Container(
              height: 36,
              padding: const EdgeInsets.symmetric(horizontal: 11),
              decoration: BoxDecoration(
                color: rangeOn ? p.goldTint : p.cardSolid,
                borderRadius: BorderRadius.circular(12),
                border: Border.all(color: rangeOn ? p.accent : p.hairline),
              ),
              child: Row(children: [
                Icon(Icons.calendar_month_outlined, size: 15, color: rangeOn ? p.goldText : p.textSecondary),
                const SizedBox(width: 6),
                Expanded(
                  child: Text(state.rangeLabel,
                      maxLines: 1,
                      overflow: TextOverflow.ellipsis,
                      style: ui(size: 12, weight: FontWeight.w800, color: rangeOn ? p.goldText : p.ink)),
                ),
                Icon(Icons.expand_more, size: 16, color: p.textMuted),
              ]),
            ),
          ),
        ),
        const SizedBox(width: 8),
        Container(
          height: 36,
          padding: const EdgeInsets.all(3),
          decoration: BoxDecoration(
            color: p.cardSolid,
            borderRadius: BorderRadius.circular(12),
            border: Border.all(color: p.hairline),
          ),
          child: Row(mainAxisSize: MainAxisSize.min, children: [
            for (final s in ChecklistStatusFilter.values)
              GestureDetector(
                onTap: () => onStatus(s),
                child: AnimatedContainer(
                  duration: const Duration(milliseconds: 150),
                  padding: const EdgeInsets.symmetric(horizontal: 10),
                  alignment: Alignment.center,
                  decoration: BoxDecoration(
                    color: s == state.statusFilter ? p.ink : ColorManager.transparent,
                    borderRadius: BorderRadius.circular(9),
                  ),
                  child: Text(s.label,
                      style: ui(size: 11.5, weight: FontWeight.w800, color: s == state.statusFilter ? p.canvas : p.textSecondary)),
                ),
              ),
          ]),
        ),
      ]),
    );
  }
}

/// "Completed · This week · by move-in date ×" — one tap back to defaults.
class _ActiveFilters extends StatelessWidget {
  const _ActiveFilters({required this.state, required this.onClear});
  final ChecklistInboxState state;
  final VoidCallback onClear;

  @override
  Widget build(BuildContext context) {
    final p = context.astra;
    final rangeOn = state.datePreset != ChecklistDatePreset.all;
    final basis = state.filter.dateBasis;
    final basisName = basis == ChecklistPhase.moveOut ? 'move-out' : 'move-in';
    final parts = [
      if (state.statusFilter == ChecklistStatusFilter.completed) 'Completed',
      if (state.statusFilter == ChecklistStatusFilter.all) 'Open & completed',
      if (rangeOn) state.rangeLabel,
      if (basis != null) rangeOn ? 'by $basisName date' : '${basisName[0].toUpperCase()}${basisName.substring(1)} only',
    ];
    return Padding(
      padding: const EdgeInsets.fromLTRB(14, 0, 14, 10),
      child: Align(
        alignment: Alignment.centerLeft,
        child: Container(
          padding: const EdgeInsets.fromLTRB(10, 5, 4, 5),
          decoration: BoxDecoration(color: p.goldTint, borderRadius: BorderRadius.circular(20)),
          child: Row(mainAxisSize: MainAxisSize.min, children: [
            Icon(Icons.tune, size: 13, color: p.goldText),
            const SizedBox(width: 6),
            Flexible(
              child: Text(parts.join(' · '),
                  maxLines: 1,
                  overflow: TextOverflow.ellipsis,
                  style: ui(size: 11.5, weight: FontWeight.w800, color: p.goldText)),
            ),
            const SizedBox(width: 2),
            GestureDetector(
              onTap: onClear,
              behavior: HitTestBehavior.opaque,
              child: Padding(
                padding: const EdgeInsets.symmetric(horizontal: 6),
                child: Icon(Icons.close, size: 15, color: p.goldText),
              ),
            ),
          ]),
        ),
      ),
    );
  }
}

class _FilterTabs extends StatelessWidget {
  const _FilterTabs({required this.state, required this.onPick});
  final ChecklistInboxState state;
  final ValueChanged<ChecklistInboxFilter> onPick;

  @override
  Widget build(BuildContext context) {
    final p = context.astra;
    return Container(
      margin: const EdgeInsets.fromLTRB(18, 0, 18, 4),
      decoration: BoxDecoration(border: Border(bottom: BorderSide(color: p.hairline))),
      child: SingleChildScrollView(
        scrollDirection: Axis.horizontal,
        child: Row(children: [
          for (final f in ChecklistInboxFilter.values) ...[
            _FilterTab(
              label: f.label,
              // Move-in / Move-out are server-side, so only the active tab's
              // count is known — show it there alone.
              count: f == state.filter ? state.visibleJobs.length : null,
              active: f == state.filter,
              onTap: () => onPick(f),
            ),
            const SizedBox(width: 16),
          ],
        ]),
      ),
    );
  }
}

class _FilterTab extends StatelessWidget {
  const _FilterTab({required this.label, required this.count, required this.active, required this.onTap});
  final String label;
  final int? count;
  final bool active;
  final VoidCallback onTap;

  @override
  Widget build(BuildContext context) {
    final p = context.astra;
    return GestureDetector(
      onTap: onTap,
      behavior: HitTestBehavior.opaque,
      child: Container(
        padding: const EdgeInsets.fromLTRB(0, 8, 0, 9),
        decoration: BoxDecoration(
          border: Border(bottom: BorderSide(color: active ? p.accent : ColorManager.transparent, width: 2.5)),
        ),
        child: Row(mainAxisSize: MainAxisSize.min, crossAxisAlignment: CrossAxisAlignment.end, children: [
          Text(label, style: ui(size: 12.5, weight: FontWeight.w800, color: active ? p.ink : p.textMuted)),
          if (count != null) ...[
            const SizedBox(width: 3),
            Text('$count', style: ui(size: 10, weight: FontWeight.w800, color: p.textMuted)),
          ],
        ]),
      ),
    );
  }
}

class _GroupHeader extends StatelessWidget {
  const _GroupHeader({required this.label, this.alert = false});
  final String label;
  final bool alert;

  @override
  Widget build(BuildContext context) {
    final p = context.astra;
    return Padding(
      padding: const EdgeInsets.fromLTRB(18, 12, 18, 7),
      child: Row(children: [
        Text(label.toUpperCase(),
            style: ui(size: 10, weight: FontWeight.w800, letterSpacing: 1.5, color: alert ? ColorManager.danger : p.textMuted)),
        const SizedBox(width: 8),
        Expanded(child: Container(height: 1, color: p.hairline)),
      ]),
    );
  }
}

/// Click-and-go date preset picker (Custom opens the range picker next).
class _DatePresetSheet extends StatelessWidget {
  const _DatePresetSheet({required this.current});
  final ChecklistDatePreset current;

  @override
  Widget build(BuildContext context) {
    final p = context.astra;
    return Container(
      decoration: BoxDecoration(color: p.canvas, borderRadius: const BorderRadius.vertical(top: Radius.circular(26))),
      child: SafeArea(
        top: false,
        child: Padding(
          padding: const EdgeInsets.fromLTRB(16, 8, 16, 12),
          child: Column(mainAxisSize: MainAxisSize.min, crossAxisAlignment: CrossAxisAlignment.stretch, children: [
            const SheetGrabber(),
            const AtelierKicker('Scheduled date'),
            const SizedBox(height: 3),
            Text('Show hand-overs for', style: serif(size: 20, color: p.ink)),
            const SizedBox(height: 10),
            for (final preset in ChecklistDatePreset.values)
              GestureDetector(
                onTap: () => Navigator.of(context).pop(preset),
                behavior: HitTestBehavior.opaque,
                child: Container(
                  margin: const EdgeInsets.only(bottom: 6),
                  padding: const EdgeInsets.symmetric(horizontal: 14, vertical: 12),
                  decoration: BoxDecoration(
                    color: preset == current ? p.goldTint : p.cardSolid,
                    borderRadius: BorderRadius.circular(14),
                    border: Border.all(color: preset == current ? p.accent : p.hairline),
                  ),
                  child: Row(children: [
                    Icon(preset == ChecklistDatePreset.custom ? Icons.date_range_outlined : Icons.event_outlined,
                        size: 17, color: preset == current ? p.goldText : p.textSecondary),
                    const SizedBox(width: 10),
                    Expanded(
                      child: Text(preset.label,
                          style: ui(size: 13.5, weight: FontWeight.w700, color: p.ink)),
                    ),
                    if (preset == current) Icon(Icons.check, size: 17, color: p.goldText),
                  ]),
                ),
              ),
          ]),
        ),
      ),
    );
  }
}
