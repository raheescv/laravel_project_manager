import 'dart:async';

import 'package:flutter/material.dart';
import 'package:flutter_bloc/flutter_bloc.dart';

import 'package:invo/features/admin/logic/admin_cubit/admin_cubit.dart';
import 'package:invo/features/admin/screens/v3/report_preview_screen.dart';
import 'package:invo/shared/domain/helpers/formatters.dart';
import 'package:invo/shared/logic/branch_cubit/branch_cubit.dart';
import 'package:invo/shared/utils/components/theme/index.dart';

/// The first step of printing a report: a floating card listing every report
/// the user may print, [current] (the one on screen) ticked. A tap opens that
/// report's preview over the card, and closing the preview lands back here to
/// pick another.
Future<void> showReportPicker(BuildContext context, {ExportReport current = ExportReport.overview}) async {
  final reports = printableReports(context);
  if (reports.isEmpty) return;
  await showModalBottomSheet<void>(
    context: context,
    backgroundColor: Colors.transparent,
    isScrollControlled: true,
    useSafeArea: true,
    constraints: const BoxConstraints(maxWidth: 520),
    builder: (_) => _ReportPicker(reports: reports, current: reports.contains(current) ? current : reports.first),
  );
}

class _ReportPicker extends StatefulWidget {
  const _ReportPicker({required this.reports, required this.current});

  final List<ExportReport> reports;
  final ExportReport current;

  @override
  State<_ReportPicker> createState() => _ReportPickerState();
}

class _ReportPickerState extends State<_ReportPicker> {
  /// The tick follows the report opened last.
  late ExportReport _current = widget.current;

  void _open(ExportReport report) {
    setState(() => _current = report);
    unawaited(openReportPreview(context, report));
  }

  @override
  Widget build(BuildContext context) {
    final p = context.astra;
    final admin = context.read<AdminCubit>();
    final branch = context.read<BranchCubit>().selected?.name ?? '';
    return SafeArea(
      top: false,
      child: Container(
        margin: const EdgeInsets.fromLTRB(12, 0, 12, 12),
        padding: const EdgeInsets.fromLTRB(8, 10, 8, 8),
        decoration: BoxDecoration(
          color: p.cardSolid,
          borderRadius: BorderRadius.circular(28),
          border: Border.all(color: p.hairline),
          boxShadow: [
            BoxShadow(
                color: Colors.black.withValues(alpha: p.isDark ? 0.55 : 0.2),
                blurRadius: 40,
                offset: const Offset(0, 18),
                spreadRadius: -14),
          ],
        ),
        child: Column(
          mainAxisSize: MainAxisSize.min,
          crossAxisAlignment: CrossAxisAlignment.stretch,
          children: [
            Center(
              child: Container(
                width: 38,
                height: 4,
                decoration:
                    BoxDecoration(color: p.textMuted.withValues(alpha: 0.3), borderRadius: BorderRadius.circular(2)),
              ),
            ),
            Padding(
              padding: const EdgeInsets.fromLTRB(12, 14, 12, 8),
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  Text('Print a report', style: ui(size: 18, weight: FontWeight.w800, color: p.ink)),
                  const SizedBox(height: 2),
                  Text(
                    [Dates.range(admin.startDate, admin.endDate), if (branch.isNotEmpty) branch].join(' · '),
                    maxLines: 1,
                    overflow: TextOverflow.ellipsis,
                    style: ui(size: 12, weight: FontWeight.w600, color: p.textMuted),
                  ),
                ],
              ),
            ),
            // Scrolls rather than overflowing a landscape phone.
            Flexible(
              child: SingleChildScrollView(
                child: Column(
                  mainAxisSize: MainAxisSize.min,
                  children: [for (final r in widget.reports) _row(r)],
                ),
              ),
            ),
          ],
        ),
      ),
    );
  }

  /// Icon tile, title over hint, and a tick on the current report.
  Widget _row(ExportReport r) {
    final p = context.astra;
    final active = r == _current;
    return Material(
      color: Colors.transparent,
      child: InkWell(
        onTap: () => _open(r),
        borderRadius: BorderRadius.circular(18),
        child: Padding(
          padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 9),
          child: Row(
            children: [
              Container(
                width: 46,
                height: 46,
                alignment: Alignment.center,
                decoration: BoxDecoration(
                  color: active ? null : p.tint,
                  gradient: active ? p.primaryGradient : null,
                  borderRadius: BorderRadius.circular(14),
                  boxShadow: active ? context.astraTheme.floatShadow(p.primary) : null,
                ),
                child: Icon(r.icon, size: 22, color: active ? Colors.white : p.primary),
              ),
              const SizedBox(width: 14),
              Expanded(
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    Text(r.title, style: ui(size: 15.5, weight: FontWeight.w800, color: p.ink)),
                    const SizedBox(height: 2),
                    Text(r.hint,
                        maxLines: 1,
                        overflow: TextOverflow.ellipsis,
                        style: ui(size: 12.5, weight: FontWeight.w500, color: p.textMuted)),
                  ],
                ),
              ),
              const SizedBox(width: 8),
              if (active) Icon(Icons.check_rounded, size: 22, color: p.primary),
            ],
          ),
        ),
      ),
    );
  }
}
