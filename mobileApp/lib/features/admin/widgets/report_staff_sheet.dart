import 'package:flutter/material.dart';
import 'package:flutter/services.dart';
import 'package:flutter_bloc/flutter_bloc.dart';

import 'package:invo/features/admin/logic/admin_cubit/admin_cubit.dart';
import 'package:invo/features/sale/logic/stylist_cubit/stylist_cubit.dart';
import 'package:invo/shared/domain/models/index.dart';
import 'package:invo/shared/utils/components/theme/index.dart';

/// Who the commission report is for: everyone, or one staff member. A bottom
/// sheet of the branch's staff, click-and-go — a tap applies and closes.
///
/// Only an account that sees every record is offered it; a rank-and-file
/// employee's report is theirs alone, which the server enforces anyway.
Future<void> showReportStaffPicker(BuildContext context) async {
  final admin = context.read<AdminCubit>();
  final staff = context.read<StylistCubit>();
  // The same list the POS staff picker and the Sales filter use, served from
  // the offline snapshot when there is no network.
  await staff.loadIfNeeded();
  if (!context.mounted) return;
  await showModalBottomSheet<void>(
    context: context,
    backgroundColor: Colors.transparent,
    isScrollControlled: true,
    useSafeArea: true,
    constraints: const BoxConstraints(maxWidth: 520),
    builder: (_) => MultiBlocProvider(
      providers: [
        BlocProvider<AdminCubit>.value(value: admin),
        BlocProvider<StylistCubit>.value(value: staff),
      ],
      child: const _ReportStaffSheet(),
    ),
  );
}

class _ReportStaffSheet extends StatefulWidget {
  const _ReportStaffSheet();

  @override
  State<_ReportStaffSheet> createState() => _ReportStaffSheetState();
}

class _ReportStaffSheetState extends State<_ReportStaffSheet> {
  final _searchCtl = TextEditingController();
  String _term = '';

  @override
  void dispose() {
    _searchCtl.dispose();
    super.dispose();
  }

  void _pick(Employee? e) {
    HapticFeedback.selectionClick();
    context.read<AdminCubit>().setCommissionStaff(e?.id, name: e?.name ?? '');
    Navigator.pop(context);
  }

  @override
  Widget build(BuildContext context) {
    final p = context.astra;
    final current = context.select<AdminCubit, int?>((c) => c.state.commissionStaffId);
    final state = context.watch<StylistCubit>().state;
    final q = _term.trim().toLowerCase();
    // The whole staff list, not the assignable one ([StylistCubit.all]) —
    // reading someone's commission is a view, not an assignment.
    final staff = [
      for (final e in state.items)
        if (q.isEmpty || e.name.toLowerCase().contains(q) || e.code.toLowerCase().contains(q)) e,
    ];

    return SafeArea(
      top: false,
      child: Container(
        margin: const EdgeInsets.fromLTRB(10, 0, 10, 10),
        constraints: BoxConstraints(maxHeight: MediaQuery.sizeOf(context).height * 0.72),
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
            Container(
              padding: const EdgeInsets.fromLTRB(16, 14, 16, 13),
              decoration: BoxDecoration(border: Border(bottom: BorderSide(color: p.hairline))),
              child: Row(
                children: [
                  Icon(Icons.badge_outlined, size: 17, color: p.primary),
                  const SizedBox(width: 9),
                  Expanded(child: Text('Commission for', style: ui(size: 15, weight: FontWeight.w800, color: p.ink))),
                ],
              ),
            ),
            if (state.items.length > 8)
              Padding(
                padding: const EdgeInsets.fromLTRB(16, 12, 16, 0),
                child: TextField(
                  controller: _searchCtl,
                  onChanged: (v) => setState(() => _term = v),
                  style: ui(size: 13, weight: FontWeight.w600, color: p.ink),
                  decoration: InputDecoration(
                    isDense: true,
                    hintText: 'Search staff',
                    prefixIcon: Icon(Icons.search_rounded, size: 18, color: p.textMuted),
                  ),
                ),
              ),
            Flexible(
              child: ListView(
                shrinkWrap: true,
                padding: const EdgeInsets.fromLTRB(12, 10, 12, 14),
                children: [
                  if (q.isEmpty) _row(context, 'All staff', Icons.groups_outlined, active: current == null, onTap: () => _pick(null)),
                  for (final e in staff)
                    _row(context, e.name, Icons.person_outline, active: current == e.id, onTap: () => _pick(e)),
                  if (state.loading && state.items.isEmpty)
                    const Padding(padding: EdgeInsets.all(18), child: Center(child: CircularProgressIndicator())),
                  if (state.errorMessage != null && state.items.isEmpty)
                    Padding(
                      padding: const EdgeInsets.all(14),
                      child: Text(state.errorMessage!,
                          textAlign: TextAlign.center,
                          style: ui(size: 12, weight: FontWeight.w600, color: p.textMuted)),
                    ),
                ],
              ),
            ),
          ],
        ),
      ),
    );
  }

  Widget _row(BuildContext context, String label, IconData icon, {required bool active, required VoidCallback onTap}) {
    final p = context.astra;
    return GestureDetector(
      onTap: onTap,
      behavior: HitTestBehavior.opaque,
      child: Container(
        margin: const EdgeInsets.symmetric(vertical: 1.5),
        padding: const EdgeInsets.symmetric(horizontal: 11, vertical: 11),
        decoration: BoxDecoration(
          color: active ? p.tint : Colors.transparent,
          borderRadius: BorderRadius.circular(11),
        ),
        child: Row(
          children: [
            Icon(icon, size: 16, color: active ? p.primary : p.textMuted),
            const SizedBox(width: 10),
            Expanded(
              child: Text(label,
                  maxLines: 1,
                  overflow: TextOverflow.ellipsis,
                  style: ui(
                      size: 12.5,
                      weight: active ? FontWeight.w800 : FontWeight.w600,
                      color: active ? p.ink : p.textSecondary)),
            ),
            if (active) Icon(Icons.check_rounded, size: 16, color: p.primary),
          ],
        ),
      ),
    );
  }
}
