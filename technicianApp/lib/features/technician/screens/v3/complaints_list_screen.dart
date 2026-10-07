import 'dart:async';

import 'package:flutter/material.dart';
import 'package:flutter_bloc/flutter_bloc.dart';
import 'package:go_router/go_router.dart';

import 'package:invo/shared/domain/helpers/formatters.dart';
import 'package:invo/shared/domain/helpers/responsive.dart';
import 'package:invo/shared/utils/components/theme/index.dart';
import 'package:invo/shared/utils/router/routes.dart';
import 'package:invo/shared/widgets/astra_range_picker.dart';
import 'package:invo/shared/widgets/astra_widgets.dart';
import 'package:invo/shared/widgets/skeleton.dart';
import 'package:invo/shared/widgets/tablet_widgets.dart';

import '../../domain/models/technician_models.dart';
import '../../logic/complaint_detail_cubit/complaint_detail_cubit.dart';
import 'complaint_detail_screen.dart';

import '../../logic/complaints_cubit/complaints_cubit.dart';
import '../../widgets/v3/complaint_card.dart';
import '../../widgets/v3/status_style.dart';

class _StatusTab {
  const _StatusTab(this.label, this.value);
  final String label;
  final String? value;
}

// The technician's pending work is the "assigned" bucket; pending/outstanding/
// cancelled complaints aren't surfaced in the app, so only these tabs show.
const _statusTabs = <_StatusTab>[
  _StatusTab('Assigned', 'assigned'),
  _StatusTab('Completed', 'completed'),
  _StatusTab('All', null),
];

/// Priority filter options — (value, label, backend colour name). A null value
/// clears the filter ("All"); the rest map to MaintenancePriority cases.
const _priorityOptions = <(String?, String, String)>[
  (null, 'All', ''),
  ('critical', 'Critical', 'danger'),
  ('high', 'High', 'warning'),
  ('medium', 'Medium', 'info'),
  ('low', 'Low', 'secondary'),
];

const _datePresets = <(String, String)>[
  ('today', 'Today'),
  ('7d', '7 days'),
  ('30d', '30 days'),
  ('month', 'This month'),
  ('all', 'All time'),
];

class ComplaintsListScreen extends StatefulWidget {
  const ComplaintsListScreen({super.key});

  @override
  State<ComplaintsListScreen> createState() => _ComplaintsListScreenState();
}

class _ComplaintsListScreenState extends State<ComplaintsListScreen> {
  final _scrollCtl = ScrollController();
  final _searchCtl = TextEditingController();
  final _searchFocus = FocusNode();
  Timer? _searchDebounce;

  /// Tablet master–detail: the job open in the right pane. Null = the first
  /// row (auto-selected) unless the user closed the pane.
  int? _selectedId;
  bool _paneClosed = false;

  /// Rotating a tablet moves the list between the split and single-pane
  /// layouts. The keys keep both panes' state through that, and once the
  /// detail pane has been shown it stays mounted (offstage) while the window
  /// is too narrow for it — a remark being typed, or a sheet open on its cubit,
  /// survives the rotation instead of being torn down.
  final _listPaneKey = GlobalKey();
  final _detailPaneKey = GlobalKey();
  bool _detailMounted = false;

  @override
  void initState() {
    super.initState();
    _scrollCtl.addListener(_onScroll);
    _searchFocus.addListener(() => setState(() {}));
    // Seed the field from the cubit (it outlives this screen) before the
    // listener goes on, so seeding never fires a search.
    _searchCtl.text = context.read<ComplaintsCubit>().search;
    _searchCtl.addListener(_onSearchChanged);
    // Always refresh on open — the cubit may hold a previous session's rows.
    WidgetsBinding.instance.addPostFrameCallback((_) {
      if (mounted) context.read<ComplaintsCubit>().load();
    });
  }

  @override
  void dispose() {
    _searchDebounce?.cancel();
    _scrollCtl.dispose();
    _searchCtl.dispose();
    _searchFocus.dispose();
    super.dispose();
  }

  /// Live search — refresh the clear button and query the API a beat after the
  /// user stops typing (Enter still searches immediately via onSubmitted).
  void _onSearchChanged() {
    setState(() {});
    _searchDebounce?.cancel();
    _searchDebounce = Timer(const Duration(milliseconds: 400), () {
      if (!mounted) return;
      final cubit = context.read<ComplaintsCubit>();
      if (cubit.search != _searchCtl.text) cubit.setSearch(_searchCtl.text);
    });
  }

  void _onScroll() {
    if (_scrollCtl.position.pixels >= _scrollCtl.position.maxScrollExtent - 240) {
      context.read<ComplaintsCubit>().loadMore();
    }
  }

  /// Set while a complaint is open — a double tap must not push it twice.
  bool _opening = false;

  Future<void> _open(ComplaintListItem item) async {
    if (_opening) return;
    _opening = true;
    try {
      await context.push(Routes.complaintDetail(item.id));
    } finally {
      _opening = false;
    }
  }

  @override
  Widget build(BuildContext context) {
    final cubit = context.watch<ComplaintsCubit>();
    if (context.isTablet) {
      return Scaffold(
        backgroundColor: Colors.transparent,
        body: AstraBackground(child: SafeArea(bottom: false, child: _tablet(context, cubit))),
      );
    }
    return Scaffold(
      backgroundColor: Colors.transparent,
      body: AstraBackground(
        child: Column(
          children: [
            EmeraldHeader(
              title: 'My Jobs',
              subtitle: '${cubit.total} total',
            ),
            Expanded(
              child: MaxWidthBox(
                maxWidth: 620,
                child: Column(
                  children: [
                    _controlCard(context, cubit),
                    Expanded(child: _list(context, cubit)),
                  ],
                ),
              ),
            ),
          ],
        ),
      ),
    );
  }

  Widget _controlCard(BuildContext context, ComplaintsCubit cubit) {
    return Padding(
      padding: const EdgeInsets.fromLTRB(16, 14, 16, 8),
      child: AstraCard(
        radius: 18,
        padding: const EdgeInsets.fromLTRB(14, 14, 14, 13),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            _searchField(context, cubit),
            const SizedBox(height: 12),
            _statusSegments(context, cubit),
            const SizedBox(height: 12),
            _priorityRow(context, cubit),
            const SizedBox(height: 10),
            _dateRow(context, cubit),
          ],
        ),
      ),
    );
  }

  /// Premium search field — the leading icon lights up with the brand gradient
  /// and the field grows a glow ring while focused; the clear button pops in.
  Widget _searchField(BuildContext context, ComplaintsCubit cubit) {
    final p = context.astra;
    final t = context.astraTheme;
    final focused = _searchFocus.hasFocus;
    final hasText = _searchCtl.text.isNotEmpty;

    return AnimatedContainer(
      duration: const Duration(milliseconds: 200),
      curve: Curves.easeOut,
      padding: const EdgeInsets.fromLTRB(7, 7, 10, 7),
      decoration: BoxDecoration(
        color: p.tint.withValues(alpha: focused ? 0.35 : 0.5),
        borderRadius: BorderRadius.circular(t.rField),
        border: Border.all(
          width: 1.3,
          color: focused ? p.primary.withValues(alpha: 0.60) : Colors.transparent,
        ),
        boxShadow: focused ? t.floatShadow(p.primary.withValues(alpha: 0.45)) : null,
      ),
      child: Row(
        children: [
          AnimatedContainer(
            duration: const Duration(milliseconds: 200),
            curve: Curves.easeOut,
            width: 30,
            height: 30,
            decoration: BoxDecoration(
              gradient: focused ? p.primaryGradient : null,
              borderRadius: BorderRadius.circular(9),
            ),
            child: Icon(Icons.search_rounded,
                size: 17, color: focused ? Colors.white : p.textMuted),
          ),
          const SizedBox(width: 9),
          Expanded(
            child: TextField(
              controller: _searchCtl,
              focusNode: _searchFocus,
              onSubmitted: (v) {
                _searchDebounce?.cancel();
                cubit.setSearch(v);
              },
              textInputAction: TextInputAction.search,
              style: ui(size: 13, weight: FontWeight.w600, color: p.ink),
              decoration: InputDecoration(
                isDense: true,
                border: InputBorder.none,
                hintText: 'Search job, unit, customer…',
                hintStyle: ui(size: 12.5, weight: FontWeight.w500, color: p.textMuted),
              ),
            ),
          ),
          AnimatedScale(
            scale: hasText ? 1 : 0,
            duration: const Duration(milliseconds: 180),
            curve: Curves.easeOutBack,
            child: GestureDetector(
              behavior: HitTestBehavior.opaque,
              onTap: () {
                _searchDebounce?.cancel();
                _searchCtl.clear();
                cubit.setSearch('');
              },
              child: Container(
                width: 22,
                height: 22,
                decoration: BoxDecoration(color: p.tint, shape: BoxShape.circle),
                child: Icon(Icons.close_rounded, size: 13, color: p.textSecondary),
              ),
            ),
          ),
        ],
      ),
    );
  }

  /// Segmented status control with a gradient thumb that slides between tabs.
  Widget _statusSegments(BuildContext context, ComplaintsCubit cubit) {
    final p = context.astra;
    final t = context.astraTheme;
    final idx = _statusTabs.indexWhere((s) => s.value == cubit.status);

    return SizedBox(
      height: 38,
      child: LayoutBuilder(
        builder: (context, constraints) {
          final tabWidth = (constraints.maxWidth - 6) / _statusTabs.length;
          return Container(
            decoration: BoxDecoration(
              color: p.tint.withValues(alpha: 0.5),
              borderRadius: BorderRadius.circular(12),
            ),
            child: Stack(
              children: [
                AnimatedPositioned(
                  duration: const Duration(milliseconds: 240),
                  curve: Curves.easeOutCubic,
                  left: 3 + idx * tabWidth,
                  top: 3,
                  bottom: 3,
                  width: tabWidth,
                  child: Container(
                    decoration: BoxDecoration(
                      gradient: p.primaryGradient,
                      borderRadius: BorderRadius.circular(9),
                      boxShadow: t.floatShadow(p.primary.withValues(alpha: 0.55)),
                    ),
                  ),
                ),
                Row(
                  children: [
                    for (final tab in _statusTabs)
                      Expanded(
                        child: GestureDetector(
                          onTap: () => cubit.setStatus(tab.value),
                          behavior: HitTestBehavior.opaque,
                          child: Center(
                            child: AnimatedDefaultTextStyle(
                              duration: const Duration(milliseconds: 200),
                              style: ui(
                                size: 11.5,
                                weight: FontWeight.w800,
                                color: cubit.status == tab.value
                                    ? Colors.white
                                    : p.textSecondary,
                              ),
                              child: Text(tab.label),
                            ),
                          ),
                        ),
                      ),
                  ],
                ),
              ],
            ),
          );
        },
      ),
    );
  }

  /// Horizontal priority filter — colour-coded chips that light up in the
  /// priority's own tint when active, so urgency reads at a glance.
  Widget _priorityRow(BuildContext context, ComplaintsCubit cubit) {
    return SingleChildScrollView(
      scrollDirection: Axis.horizontal,
      child: Row(
        children: [
          for (final opt in _priorityOptions) ...[
            _priorityChip(context, cubit, opt.$1, opt.$2, opt.$3),
            const SizedBox(width: 8),
          ],
        ],
      ),
    );
  }

  Widget _priorityChip(
      BuildContext context, ComplaintsCubit cubit, String? value, String label, String colorName) {
    final p = context.astra;
    final t = context.astraTheme;
    final active = cubit.priority == value;
    final tint = colorName.isEmpty ? null : astraTint(context, colorName);
    final accent = tint?.fg ?? p.primary;

    return GestureDetector(
      onTap: () => cubit.setPriority(value),
      behavior: HitTestBehavior.opaque,
      child: AnimatedContainer(
        duration: const Duration(milliseconds: 180),
        padding: const EdgeInsets.symmetric(horizontal: 14, vertical: 9),
        decoration: BoxDecoration(
          color: active ? (tint?.bg ?? p.tint) : p.card,
          borderRadius: BorderRadius.circular(t.rChip),
          border: Border.all(
            width: 1.2,
            color: active ? accent.withValues(alpha: 0.55) : Colors.transparent,
          ),
          boxShadow: active ? null : t.softShadow,
        ),
        child: Row(
          mainAxisSize: MainAxisSize.min,
          children: [
            if (tint != null) ...[
              Container(
                width: 8,
                height: 8,
                decoration: BoxDecoration(color: accent, shape: BoxShape.circle),
              ),
              const SizedBox(width: 7),
            ],
            Text(label,
                style: ui(
                    size: 12.5,
                    weight: FontWeight.w700,
                    color: active ? accent : p.textSecondary)),
          ],
        ),
      ),
    );
  }

  Widget _dateRow(BuildContext context, ComplaintsCubit cubit) {
    return SingleChildScrollView(
      scrollDirection: Axis.horizontal,
      child: Row(
        children: [
          for (final preset in _datePresets) ...[
            AstraChip(
              label: preset.$2,
              active: cubit.datePreset == preset.$1,
              onTap: () => cubit.setPreset(preset.$1),
            ),
            const SizedBox(width: 8),
          ],
          AstraChip(
            label: cubit.datePreset == 'custom'
                ? Dates.range(cubit.startDate, cubit.endDate)
                : 'Custom',
            active: cubit.datePreset == 'custom',
            icon: Icons.tune,
            onTap: () => _pickCustomRange(context, cubit),
          ),
        ],
      ),
    );
  }

  Future<void> _pickCustomRange(BuildContext context, ComplaintsCubit cubit) async {
    final now = DateTime.now();
    final range = await showAstraDateRangePicker(
      context,
      title: 'Complaint dates',
      firstDate: DateTime(now.year - 3),
      lastDate: DateTime(now.year + 1),
      initialDateRange: DateTimeRange(start: cubit.startDate, end: cubit.endDate),
    );
    if (range != null) cubit.setCustomRange(range.start, range.end);
  }

  // ---- Tablet: master–detail ---------------------------------------------------

  Widget _tablet(BuildContext context, ComplaintsCubit cubit) {
    return LayoutBuilder(builder: (context, box) {
      final m = TabletMetrics.forWidth(box.maxWidth);
      // Narrow tablets keep a single pane; a tap pushes the detail.
      final split = box.maxWidth >= 760;
      final listPane = TabletPane(
        width: split ? m.listColumn : null,
        edge: split ? PaneEdge.right : PaneEdge.none,
        child: Column(children: [
          TabletPaneHead(
            title: 'My Jobs',
            subtitle: '${cubit.total} ${cubit.total == 1 ? 'complaint' : 'complaints'}',
            children: [
              const SizedBox(height: 12),
              _searchField(context, cubit),
              const SizedBox(height: 10),
              _statusSegments(context, cubit),
              const SizedBox(height: 10),
              _priorityRow(context, cubit),
              const SizedBox(height: 8),
              _dateRow(context, cubit),
            ],
          ),
          Expanded(child: _tabletList(context, cubit, split)),
        ]),
      );
      if (split) _detailMounted = true;
      final list = KeyedSubtree(key: _listPaneKey, child: listPane);
      final detail = KeyedSubtree(key: _detailPaneKey, child: _detailPane(context, cubit));
      return Row(crossAxisAlignment: CrossAxisAlignment.stretch, children: [
        if (split) list else Expanded(child: list),
        if (split)
          Expanded(child: detail)
        else if (_detailMounted)
          // Laid out at a usable width but never painted or hit-tested.
          SizedBox(
            width: 0,
            child: OverflowBox(
              alignment: Alignment.topLeft,
              minWidth: 600,
              maxWidth: 600,
              child: Offstage(child: detail),
            ),
          ),
      ]);
    });
  }

  int? _effectiveSelection(ComplaintsCubit cubit) {
    if (_paneClosed) return null;
    if (_selectedId != null && cubit.rows.any((r) => r.id == _selectedId)) return _selectedId;
    return cubit.rows.isEmpty ? null : cubit.rows.first.id;
  }

  Widget _detailPane(BuildContext context, ComplaintsCubit cubit) {
    final sel = _effectiveSelection(cubit);
    final Widget child;
    if (sel == null) {
      child = cubit.loading && cubit.rows.isEmpty
          ? const SkeletonList(key: ValueKey('loading'), count: 4, padding: EdgeInsets.all(28), itemHeight: 110)
          : EmptyState(
              key: const ValueKey('none'),
              icon: Icons.assignment_outlined,
              title: 'Select a job',
              message: cubit.rows.isEmpty ? 'No complaints match these filters.' : 'Pick a complaint on the left to open it here.',
            );
    } else {
      child = BlocProvider(
        key: ValueKey(sel),
        create: (_) => ComplaintDetailCubit(sel)..load(),
        child: ComplaintDetailScreen(onClose: () => setState(() => _paneClosed = true)),
      );
    }
    return astraPaneSwitcher(child: child);
  }

  Widget _tabletList(BuildContext context, ComplaintsCubit cubit, bool split) {
    final p = context.astra;
    if (cubit.loading && cubit.rows.isEmpty) {
      return const SingleChildScrollView(
        physics: NeverScrollableScrollPhysics(),
        child: SkeletonList(count: 7, padding: EdgeInsets.all(14), itemHeight: 78),
      );
    }
    if (cubit.error != null && cubit.rows.isEmpty) {
      return EmptyState(
        icon: Icons.wifi_off_rounded,
        title: 'Could not load',
        message: cubit.error,
        action: AstraButton(label: 'Retry', expand: false, onTap: () => cubit.load()),
      );
    }
    if (cubit.rows.isEmpty) {
      return const EmptyState(
          icon: Icons.inbox_outlined, title: 'No complaints found', message: 'Try a different status or date range.');
    }
    final sel = _effectiveSelection(cubit);
    return RefreshIndicator(
      onRefresh: () => cubit.load(),
      child: ListView.builder(
        controller: _scrollCtl,
        padding: const EdgeInsets.only(bottom: 24),
        itemCount: cubit.rows.length + (cubit.hasMore ? 1 : 0),
        itemBuilder: (context, i) {
          if (i >= cubit.rows.length) {
            return Padding(
              padding: const EdgeInsets.symmetric(vertical: 16),
              child: Center(
                child: SizedBox(width: 22, height: 22, child: CircularProgressIndicator(strokeWidth: 2.2, color: p.primary)),
              ),
            );
          }
          final item = cubit.rows[i];
          return _ComplaintRow(
            key: ValueKey(item.id),
            item: item,
            selected: split && item.id == sel,
            onTap: split
                ? () => setState(() {
                      _selectedId = item.id;
                      _paneClosed = false;
                    })
                : () => _open(item),
          );
        },
      ),
    );
  }

  Widget _list(BuildContext context, ComplaintsCubit cubit) {
    final p = context.astra;
    if (cubit.loading && cubit.rows.isEmpty) {
      return const SingleChildScrollView(
        physics: NeverScrollableScrollPhysics(),
        child: SkeletonList(count: 6, itemHeight: 104),
      );
    }
    if (cubit.error != null && cubit.rows.isEmpty) {
      return EmptyState(
        icon: Icons.wifi_off_rounded,
        title: 'Could not load',
        message: cubit.error,
        action: AstraButton(label: 'Retry', expand: false, onTap: () => cubit.load()),
      );
    }
    if (cubit.rows.isEmpty) {
      return EmptyState(
        icon: Icons.inbox_outlined,
        title: 'No complaints found',
        message: 'Try a different status or date range.',
      );
    }
    return RefreshIndicator(
      onRefresh: () => cubit.load(),
      child: ListView.separated(
        controller: _scrollCtl,
        padding: const EdgeInsets.fromLTRB(16, 8, 16, 120),
        itemCount: cubit.rows.length + (cubit.hasMore ? 1 : 0),
        separatorBuilder: (_, __) => const SizedBox(height: 10),
        itemBuilder: (context, i) {
          if (i >= cubit.rows.length) {
            return Padding(
              padding: const EdgeInsets.symmetric(vertical: 16),
              child: Center(
                child: SizedBox(
                  width: 22,
                  height: 22,
                  child: CircularProgressIndicator(strokeWidth: 2.2, color: p.primary),
                ),
              ),
            );
          }
          final item = cubit.rows[i];
          return ComplaintCard(key: ValueKey(item.id), item: item, onTap: () => _open(item));
        },
      ),
    );
  }
}

/// A flat master-pane row (tablet) — title, category, status, location, date.
class _ComplaintRow extends StatelessWidget {
  const _ComplaintRow({super.key, required this.item, required this.selected, required this.onTap});
  final ComplaintListItem item;
  final bool selected;
  final VoidCallback onTap;

  @override
  Widget build(BuildContext context) {
    final p = context.astra;
    final tint = astraTint(context, item.priorityColor);
    final where = [
      if (item.propertyNumber.isNotEmpty) 'Unit ${item.propertyNumber}',
      if (item.building.isNotEmpty) item.building,
    ].join(' · ');
    return TabletListRow(
      selected: selected,
      onTap: onTap,
      child: Row(children: [
        IconChip(icon: priorityIcon(item.priority), size: 34, radius: 10, bg: tint.bg, fg: tint.fg),
        const SizedBox(width: 11),
        Expanded(
          child: Column(crossAxisAlignment: CrossAxisAlignment.start, children: [
            Text(item.complaintName.isEmpty ? 'Complaint #${item.id}' : item.complaintName,
                maxLines: 1, overflow: TextOverflow.ellipsis, style: ui(size: 13, weight: FontWeight.w800, color: p.ink)),
            const SizedBox(height: 2),
            Text(
              [if (where.isNotEmpty) where, if (item.date.isNotEmpty) Dates.human(item.date)].join(' · '),
              maxLines: 1,
              overflow: TextOverflow.ellipsis,
              style: ui(size: 11, weight: FontWeight.w600, color: p.textMuted),
            ),
          ]),
        ),
        const SizedBox(width: 8),
        AstraStatusPill(label: item.statusLabel, colorName: item.statusColor),
      ]),
    );
  }
}
