import 'dart:async';

import 'package:flutter/material.dart';
import 'package:go_router/go_router.dart';
import 'package:provider/provider.dart';

import 'package:invo/shared/domain/helpers/formatters.dart';
import 'package:invo/shared/domain/helpers/responsive.dart';
import 'package:invo/shared/utils/components/theme/index.dart';
import 'package:invo/shared/widgets/astra_widgets.dart';

import '../../logic/complaints_cubit/complaints_cubit.dart';
import '../../widgets/v3/complaint_card.dart';

class _StatusTab {
  const _StatusTab(this.label, this.value);
  final String label;
  final String? value;
}

const _statusTabs = <_StatusTab>[
  _StatusTab('Pending', 'pending'),
  _StatusTab('Assigned', 'assigned'),
  _StatusTab('Completed', 'completed'),
  _StatusTab('All', null),
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

  @override
  void initState() {
    super.initState();
    _scrollCtl.addListener(_onScroll);
    _searchFocus.addListener(() => setState(() {}));
    _searchCtl.addListener(_onSearchChanged);
    WidgetsBinding.instance.addPostFrameCallback((_) {
      final cubit = context.read<ComplaintsCubit>();
      if (cubit.rows.isEmpty) cubit.load();
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

  @override
  Widget build(BuildContext context) {
    final cubit = context.watch<ComplaintsCubit>();
    return Scaffold(
      backgroundColor: Colors.transparent,
      body: AstraBackground(
        child: Column(
          children: [
            EmeraldHeader(
              title: 'My Complaints',
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
    final range = await showDateRangePicker(
      context: context,
      firstDate: DateTime(now.year - 3),
      lastDate: DateTime(now.year + 1),
      initialDateRange: DateTimeRange(start: cubit.startDate, end: cubit.endDate),
    );
    if (range != null) cubit.setCustomRange(range.start, range.end);
  }

  Widget _list(BuildContext context, ComplaintsCubit cubit) {
    final p = context.astra;
    if (cubit.loading && cubit.rows.isEmpty) {
      return Center(child: CircularProgressIndicator(color: p.primary));
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
          return ComplaintCard(item: item, onTap: () => context.push('/complaints/${item.id}'));
        },
      ),
    );
  }
}
