import 'package:flutter/material.dart';
import 'package:flutter/services.dart';
import 'package:flutter_bloc/flutter_bloc.dart';

import 'package:invo/features/admin/logic/admin_cubit/admin_cubit.dart';
import 'package:invo/shared/logic/branch_cubit/branch_cubit.dart';
import 'package:invo/shared/utils/components/theme/index.dart';

/// How a phone sorts the Breakdown report: a bottom sheet holding the four
/// sorts the API carries, the live one showing its direction. Click-and-go —
/// a tap applies and the list behind rebuilds, so there is no Save button.
///
/// Phone only. A big screen sorts by tapping the table's column heads, keeps
/// the item type on the toolbar and the date range on the command bar, so it
/// has nothing left to put in a panel.
Future<void> showReportSort(BuildContext context) async {
  final admin = context.read<AdminCubit>();
  await showModalBottomSheet<void>(
    context: context,
    backgroundColor: Colors.transparent,
    isScrollControlled: true,
    useSafeArea: true,
    constraints: const BoxConstraints(maxWidth: 520),
    builder: (_) => BlocProvider<AdminCubit>.value(
      value: admin,
      child: const _ReportSortSheet(),
    ),
  );
}

/// The four sorts every breakdown carries, labelled for the report on screen —
/// a staff row's "amount" is their revenue and its "quantity" is lines sold.
typedef _Sort = ({String key, String label, IconData icon});

class _ReportSortSheet extends StatelessWidget {
  const _ReportSortSheet();

  List<_Sort> _sorts(String reportType) => AdminCubit.ranksProducts(reportType)
      ? const [
          (key: 'amount', label: 'Amount', icon: Icons.payments_rounded),
          (key: 'quantity', label: 'Quantity', icon: Icons.numbers_rounded),
          (key: 'bills', label: 'Bills', icon: Icons.receipt_long_rounded),
          (key: 'name', label: 'Name', icon: Icons.sort_by_alpha_rounded),
        ]
      : const [
          (key: 'amount', label: 'Revenue', icon: Icons.payments_rounded),
          (key: 'quantity', label: 'Items sold', icon: Icons.numbers_rounded),
          (key: 'bills', label: 'Bills', icon: Icons.receipt_long_rounded),
          (key: 'name', label: 'Name', icon: Icons.sort_by_alpha_rounded),
        ];

  @override
  Widget build(BuildContext context) {
    final p = context.astra;
    return BlocBuilder<AdminCubit, AdminState>(
      builder: (context, _) {
        final admin = context.read<AdminCubit>();
        return SafeArea(
          top: false,
          child: Container(
            margin: const EdgeInsets.fromLTRB(10, 0, 10, 10),
            decoration: BoxDecoration(
              color: p.cardSolid,
              borderRadius: BorderRadius.circular(26),
              border: Border.all(color: p.hairline),
              boxShadow: [
                BoxShadow(
                    color: Colors.black.withValues(alpha: p.isDark ? 0.55 : 0.2),
                    blurRadius: 40,
                    offset: const Offset(0, 18),
                    spreadRadius: -14),
              ],
            ),
            clipBehavior: Clip.antiAlias,
            child: Column(
              mainAxisSize: MainAxisSize.min,
              crossAxisAlignment: CrossAxisAlignment.stretch,
              children: [
                _header(context, admin),
                Flexible(
                  child: SingleChildScrollView(
                    padding: const EdgeInsets.fromLTRB(16, 14, 16, 16),
                    child: Column(
                      crossAxisAlignment: CrossAxisAlignment.start,
                      children: [
                        _sortList(context, admin),
                        const SizedBox(height: 13),
                        _branchLine(context),
                      ],
                    ),
                  ),
                ),
              ],
            ),
          ),
        );
      },
    );
  }

  Widget _header(BuildContext context, AdminCubit admin) {
    final p = context.astra;
    return Container(
      padding: const EdgeInsets.fromLTRB(16, 14, 12, 13),
      decoration: BoxDecoration(border: Border(bottom: BorderSide(color: p.hairline))),
      child: Row(
        children: [
          Icon(Icons.swap_vert_rounded, size: 17, color: p.primary),
          const SizedBox(width: 9),
          Expanded(child: Text('Sort by', style: ui(size: 15, weight: FontWeight.w800, color: p.ink))),
          GestureDetector(
            onTap: () {
              HapticFeedback.selectionClick();
              admin.setSort('amount', ascending: false);
            },
            behavior: HitTestBehavior.opaque,
            child: Padding(
              padding: const EdgeInsets.symmetric(horizontal: 6, vertical: 4),
              child: Text('Reset', style: ui(size: 12, weight: FontWeight.w800, color: p.primary)),
            ),
          ),
        ],
      ),
    );
  }

  /// The live sort carries a direction pill; tapping it again flips the list
  /// between high → low and low → high.
  Widget _sortList(BuildContext context, AdminCubit admin) {
    final p = context.astra;
    return Container(
      padding: const EdgeInsets.all(4),
      decoration: BoxDecoration(
        color: p.isDark ? Colors.white.withValues(alpha: 0.05) : Colors.black.withValues(alpha: 0.035),
        borderRadius: BorderRadius.circular(15),
        border: Border.all(color: p.hairline),
      ),
      child: Column(
        children: [
          for (final sort in _sorts(admin.reportType))
            _sortRow(context, admin, sort, active: admin.sortKey == sort.key),
        ],
      ),
    );
  }

  Widget _sortRow(BuildContext context, AdminCubit admin, _Sort sort, {required bool active}) {
    final p = context.astra;
    final ascending = admin.sortAscending;
    return GestureDetector(
      onTap: () {
        HapticFeedback.selectionClick();
        admin.setSort(sort.key);
      },
      behavior: HitTestBehavior.opaque,
      child: AnimatedContainer(
        duration: const Duration(milliseconds: 160),
        margin: const EdgeInsets.symmetric(vertical: 1.5),
        padding: const EdgeInsets.symmetric(horizontal: 11, vertical: 10),
        decoration: BoxDecoration(
          color: active ? p.cardSolid : Colors.transparent,
          borderRadius: BorderRadius.circular(11),
          boxShadow: active ? context.astraTheme.softShadow : null,
        ),
        child: Row(
          children: [
            Icon(sort.icon, size: 15, color: active ? p.primary : p.textMuted),
            const SizedBox(width: 10),
            Expanded(
              child: Text(sort.label,
                  style: ui(
                      size: 12.5,
                      weight: active ? FontWeight.w800 : FontWeight.w600,
                      color: active ? p.ink : p.textSecondary)),
            ),
            if (active)
              Container(
                padding: const EdgeInsets.fromLTRB(8, 4, 7, 4),
                decoration: BoxDecoration(color: p.tint, borderRadius: BorderRadius.circular(9)),
                child: Row(
                  mainAxisSize: MainAxisSize.min,
                  children: [
                    Text(sortDirectionLabel(sort.key, ascending),
                        style: ui(size: 10, weight: FontWeight.w800, color: p.primary)),
                    const SizedBox(width: 3),
                    Icon(ascending ? Icons.arrow_upward_rounded : Icons.arrow_downward_rounded,
                        size: 12, color: p.primary),
                  ],
                ),
              ),
          ],
        ),
      ),
    );
  }

  /// Reports follow the branch the app is operating as, not a filter of their
  /// own — said plainly here so nobody hunts for a branch picker.
  Widget _branchLine(BuildContext context) {
    final p = context.astra;
    final branch = context.read<BranchCubit>().selected?.name ?? '';
    if (branch.isEmpty) return const SizedBox.shrink();
    return Row(
      children: [
        Icon(Icons.store_mall_directory_rounded, size: 13, color: p.textMuted),
        const SizedBox(width: 7),
        Expanded(
          child: Text('Reporting on $branch — switch branch from the sidebar',
              maxLines: 2,
              style: ui(size: 10.5, weight: FontWeight.w600, color: p.textMuted)),
        ),
      ],
    );
  }
}

/// How a direction reads for the sort it belongs to: a name sorts A–Z, a
/// figure sorts high → low.
String sortDirectionLabel(String sortKey, bool ascending) => sortKey == 'name'
    ? (ascending ? 'A – Z' : 'Z – A')
    : (ascending ? 'Low → High' : 'High → Low');

/// The sort as the toolbar button and the applied-filter chip show it, e.g.
/// `Amount ↓`. Short on purpose — the full picture is in the sheet.
String sortChipLabel(String sortKey, String reportType) {
  final products = AdminCubit.ranksProducts(reportType);
  return switch (sortKey) {
    'quantity' => products ? 'Quantity' : 'Items sold',
    'bills' => 'Bills',
    'name' => 'Name',
    _ => products ? 'Amount' : 'Revenue',
  };
}
