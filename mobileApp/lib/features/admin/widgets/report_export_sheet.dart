import 'dart:async';

import 'package:flutter/foundation.dart' show Uint8List, defaultTargetPlatform;
import 'package:flutter/material.dart';
import 'package:flutter_bloc/flutter_bloc.dart';
import 'package:pdf/pdf.dart' show PdfColor;

import 'package:invo/features/admin/logic/admin_cubit/admin_cubit.dart';
import 'package:invo/features/admin/widgets/day_session_report_pdf.dart';
import 'package:invo/features/admin/widgets/report_pdf.dart';
import 'package:invo/features/auth/logic/auth_cubit/auth_cubit.dart';
import 'package:invo/features/settings/logic/print_settings_cubit/print_settings_cubit.dart';
import 'package:invo/shared/domain/constants/mobile_permissions.dart';
import 'package:invo/shared/domain/helpers/formatters.dart';
import 'package:invo/shared/domain/models/index.dart';
import 'package:invo/shared/logic/branch_cubit/branch_cubit.dart';
import 'package:invo/shared/utils/components/theme/index.dart';
import 'package:invo/shared/utils/printing/pdf_export.dart';
import 'package:invo/shared/utils/router/http_utils/common_exception.dart';
import 'package:invo/shared/widgets/astra_snack.dart';
import 'package:invo/shared/widgets/astra_widgets.dart';
import 'package:invo/shared/widgets/pdf_preview_page.dart';
import 'package:invo/shared/widgets/receipt_printer.dart';

/// Every report the app hands over as a PDF.
enum ExportReport {
  overview('Overview', Icons.insights_rounded),
  items('By Item', Icons.inventory_2_rounded),
  staff('By Staff', Icons.people_alt_rounded),
  daySession('Session', Icons.receipt_long_rounded);

  const ExportReport(this.label, this.icon);
  final String label;
  final IconData icon;

  /// The Reports-screen report behind this choice; null for the day session,
  /// whose documents come from its own endpoints.
  ReportExportKind? get kind => switch (this) {
        ExportReport.overview => ReportExportKind.overview,
        ExportReport.items => ReportExportKind.items,
        ExportReport.staff => ReportExportKind.stylists,
        ExportReport.daySession => null,
      };
}

enum _Action { preview, print, whatsApp, share, download }

class _Pick {
  const _Pick(this.report, this.action, this.session);
  final ExportReport report;
  final _Action action;

  /// The day session the report is for; null for the Reports-screen reports.
  final DaySessionSummary? session;
}

const _whatsAppGreen = Color(0xFF25D366);

/// The one Export sheet: pick a report (when more than one is on offer), then
/// Preview, Print, WhatsApp, Share or Download — the same five routes out for
/// every report. The day session is always the *current* one: the branch's
/// open session, or the one opened last once the day is shut. Its Print is the
/// thermal roll; its Preview and the rest carry the web's A4 report.
///
/// [reports] narrows the choice (the Day Session screen offers only
/// [ExportReport.daySession]), and each is shown only to users holding its
/// permission. The action runs on [context] once the sheet is down, so its
/// confirmation and any preview land on the page rather than behind the sheet.
Future<void> showReportExport(
  BuildContext context, {
  ExportReport initial = ExportReport.overview,
  List<ExportReport> reports = ExportReport.values,
}) async {
  final auth = context.read<AuthCubit>();
  final offered = [
    for (final r in reports)
      if (auth.hasPermission(r == ExportReport.daySession ? PermissionSlug.daySessionPrint : PermissionSlug.report)) r,
  ];
  if (offered.isEmpty) return;

  final pick = await showModalBottomSheet<_Pick>(
    context: context,
    backgroundColor: Colors.transparent,
    isScrollControlled: true,
    useSafeArea: true,
    constraints: const BoxConstraints(maxWidth: 560),
    builder: (_) => _ExportSheet(reports: offered, initial: offered.contains(initial) ? initial : offered.first),
  );
  if (pick == null || !context.mounted) return;
  await _run(context, pick);
}

// ---- running an action --------------------------------------------------------

Future<void> _run(BuildContext context, _Pick pick) async {
  final admin = context.read<AdminCubit>();
  final printer = context.read<PrintSettingsCubit>();
  final settings = printer.snapshot;
  final snack = AstraSnack.capture(context);
  final navigator = Navigator.of(context, rootNavigator: true);
  final brand = _brand(context);
  final session = pick.session;
  final thermal = session != null && pick.action == _Action.print;

  unawaited(showDialog<void>(
    context: context,
    useRootNavigator: true,
    barrierDismissible: false,
    builder: (_) => PdfProgressCard(
      message: session == null
          ? 'Pulling every line for this range'
          : (thermal ? 'Laying out the roll' : 'Rendering the A4 report'),
    ),
  ));

  final Uint8List bytes;
  final String title;
  final String fileName;
  final String caption;
  try {
    if (session == null) {
      final data = await admin.exportReport(pick.report.kind!);
      bytes = await buildReportPdf(data, brand);
      title = data.kind.title;
      fileName = data.fileName;
      caption = [
        data.kind.title,
        Dates.range(data.startDate, data.endDate),
        if (brand.branchName.isNotEmpty) brand.branchName,
      ].join(' · ');
    } else {
      bytes = thermal
          ? await buildDaySessionThermalPdf(await admin.daySessionReport(session.id), settings,
              highlightUserId: brand.preparedById)
          : await admin.daySessionReportPdf(session.id);
      title = 'Sale Bill Report #${session.id}';
      fileName = 'sale-bill-report_session-${session.id}${thermal ? '_roll' : ''}.pdf';
      caption = [
        title,
        Dates.human(session.openedAt),
        if (session.branch.isNotEmpty) session.branch,
      ].join(' · ');
    }
  } catch (e) {
    navigator.pop();
    snack.error(e is ApiException ? e.message : 'Could not prepare the PDF. Check the connection and try again.');
    return;
  }
  navigator.pop();
  if (!context.mounted) return;

  switch (pick.action) {
    case _Action.preview:
      openPdfPreview(context, title: title, bytes: bytes, fileName: fileName, caption: caption);
    case _Action.print:
      if (!thermal) {
        await PdfExport.printDialog(bytes, fileName);
        return;
      }
      // Unpaired, the preview is the only way to pick a printer on this device
      // — the same rule as a receipt's Print button.
      if (!printer.hasPrinter) {
        openPdfPreview(context, title: title, bytes: bytes, fileName: fileName, caption: caption, maxPageWidth: 420);
        return;
      }
      final result = await printRollPdf(bytes, settings, title: title, target: printer.printer);
      if (result == ReceiptPrintResult.cancelled) return;
      if (result.ok) {
        snack.success('Sent to ${printer.printer.displayName}');
      } else {
        snack.error('Couldn\'t reach the printer — check it\'s on and paired.');
      }
    case _Action.whatsApp:
      await PdfExport.whatsApp(bytes, fileName, caption: caption);
    case _Action.share:
      await PdfExport.share(bytes, fileName, subject: caption);
    case _Action.download:
      await downloadPdf(bytes, fileName, caption: caption, snack: snack);
  }
}

/// Letterhead for the Reports-screen PDFs: company name and logo from the web
/// print settings (each only when the tenant prints it), the branch the app is
/// operating as, and who is signed in. The accent follows the theme preset.
ReportPdfBrand _brand(BuildContext context) {
  final print = context.read<PrintSettingsCubit>().snapshot;
  return ReportPdfBrand(
    companyName: print.companyName,
    branchName: context.read<BranchCubit>().selected?.name ?? '',
    preparedBy: context.read<AuthCubit>().user?.name ?? '',
    preparedById: context.read<AuthCubit>().user?.id ?? '',
    logo: print.logo,
    accent: PdfColor.fromInt(context.astra.primary.toARGB32()),
  );
}

// ---- the sheet --------------------------------------------------------------------

class _ExportSheet extends StatefulWidget {
  const _ExportSheet({required this.reports, required this.initial});

  final List<ExportReport> reports;
  final ExportReport initial;

  @override
  State<_ExportSheet> createState() => _ExportSheetState();
}

class _ExportSheetState extends State<_ExportSheet> {
  late ExportReport _report = widget.initial;

  /// Which session is current — looked up the first time the day session is
  /// picked, and again only on a retry.
  Future<DaySessionSummary?>? _session;

  @override
  void initState() {
    super.initState();
    if (_report == ExportReport.daySession) _findSession();
  }

  void _findSession({bool retry = false}) {
    if (_session != null && !retry) return;
    _session = context.read<AdminCubit>().currentDaySession();
  }

  void _select(ExportReport report) => setState(() {
        _report = report;
        if (report == ExportReport.daySession) _findSession();
      });

  @override
  Widget build(BuildContext context) {
    final isSession = _report == ExportReport.daySession;
    return FutureBuilder<DaySessionSummary?>(
      future: isSession ? _session : null,
      builder: (context, snap) {
        final p = context.astra;
        final session = isSession ? snap.data : null;
        // Report actions are always ready; the session's wait for its lookup.
        final ready = !isSession || session != null;

        Widget action(IconData icon, Color tint, String label, String hint, _Action what) => _ActionRow(
              icon: icon,
              tint: tint,
              label: label,
              hint: hint,
              onTap: ready ? () => Navigator.pop(context, _Pick(_report, what, session)) : null,
            );

        return _SheetFrame(
          eyebrow: 'Export PDF',
          title: isSession ? 'Sale Bill Report' : _report.kind!.title,
          subtitle: isSession ? _sessionLine(snap) : _plainLine(_scope()),
          children: [
            if (widget.reports.length > 1) ...[
              _picker(),
              const SizedBox(height: 14),
            ],
            // Scrollable so the five actions never overflow a landscape phone.
            Flexible(
              child: SingleChildScrollView(
                child: Column(
                  mainAxisSize: MainAxisSize.min,
                  children: [
                    action(Icons.visibility_outlined, p.primary, 'Preview',
                        isSession ? 'The A4 report — print or send from there' : 'See the pages, then print or send',
                        _Action.preview),
                    action(Icons.print_outlined, p.ink, 'Print', isSession ? _thermalHint() : 'Send it to a printer',
                        _Action.print),
                    // Straight to WhatsApp on Android; on iPhone it could only
                    // open the share sheet, which is what Share already does.
                    if (PdfExport.opensWhatsAppDirectly)
                      action(Icons.chat_rounded, _whatsAppGreen, 'WhatsApp', 'Send the PDF to a chat', _Action.whatsApp),
                    action(Icons.ios_share, p.ink, 'Share', 'Email, Drive or any other app', _Action.share),
                    action(
                        Icons.download_rounded,
                        p.goldText,
                        'Download',
                        defaultTargetPlatform == TargetPlatform.android
                            ? 'Save a copy to Downloads'
                            : 'Save to Files from the share sheet',
                        _Action.download),
                  ],
                ),
              ),
            ),
          ],
        );
      },
    );
  }

  Widget _plainLine(String text) =>
      Text(text, style: ui(size: 11.5, weight: FontWeight.w600, color: context.astra.textMuted));

  /// Range plus the filters that shape the chosen report.
  String _scope() {
    final admin = context.read<AdminCubit>();
    final range = Dates.range(admin.startDate, admin.endDate);
    if (_report != ExportReport.items) return range;
    final type = switch (admin.itemProductType) {
      'product' => 'Products',
      'service' => 'Services',
      _ => 'All types',
    };
    return '$range · By ${admin.itemMetric == 'qty' ? 'qty' : 'amount'} · $type';
  }

  /// Which session the report is for — or why it can't be printed yet.
  Widget _sessionLine(AsyncSnapshot<DaySessionSummary?> snap) {
    if (snap.connectionState != ConnectionState.done) return _plainLine('Finding the current session…');
    if (snap.hasError) {
      return GestureDetector(
        onTap: () => setState(() => _findSession(retry: true)),
        child: Text('Couldn\'t load the session · tap to retry',
            style: ui(size: 11.5, weight: FontWeight.w700, color: AstraPalette.danger)),
      );
    }
    final s = snap.data;
    if (s == null) return _plainLine('No day session on this branch yet');
    return _plainLine(s.isOpen
        ? 'Session #${s.id} · open since ${Dates.humanDateTime(s.openedAt)}'
        : 'Session #${s.id} · closed ${Dates.humanDateTime(s.closedAt)}');
  }

  String _thermalHint() {
    final printer = context.read<PrintSettingsCubit>();
    return printer.hasPrinter
        ? 'Thermal roll, straight to ${printer.printer.displayName}'
        : 'Thermal roll — preview, then print';
  }

  /// The report choice, in the same segmented look as the Reports screen's own
  /// toggles; icon over label so four fit a phone.
  Widget _picker() {
    final p = context.astra;
    Widget seg(ExportReport r) {
      final active = _report == r;
      final fg = active ? Colors.white : p.textSecondary;
      return Expanded(
        child: GestureDetector(
          onTap: () => _select(r),
          child: Container(
            padding: const EdgeInsets.symmetric(vertical: 8),
            decoration: BoxDecoration(
              gradient: active ? p.primaryGradient : null,
              borderRadius: BorderRadius.circular(10),
              boxShadow: active ? context.astraTheme.floatShadow(p.primary) : null,
            ),
            child: Column(
              mainAxisSize: MainAxisSize.min,
              children: [
                Icon(r.icon, size: 16, color: fg),
                const SizedBox(height: 3),
                Text(r.label,
                    maxLines: 1,
                    overflow: TextOverflow.ellipsis,
                    style: ui(size: 10.5, weight: FontWeight.w800, color: fg)),
              ],
            ),
          ),
        ),
      );
    }

    return Container(
      padding: const EdgeInsets.all(4),
      decoration: BoxDecoration(color: p.tint, borderRadius: BorderRadius.circular(13)),
      child: Row(children: [for (final r in widget.reports) seg(r)]),
    );
  }
}

/// One action row: tinted icon tile, title + hint, chevron. Dimmed and inert
/// while [onTap] is null.
class _ActionRow extends StatelessWidget {
  const _ActionRow({
    required this.icon,
    required this.tint,
    required this.label,
    required this.hint,
    required this.onTap,
  });

  final IconData icon;
  final Color tint;
  final String label;
  final String hint;
  final VoidCallback? onTap;

  @override
  Widget build(BuildContext context) {
    final p = context.astra;
    return Padding(
      padding: const EdgeInsets.only(bottom: 10),
      child: Opacity(
        opacity: onTap == null ? 0.45 : 1,
        child: GestureDetector(
          onTap: onTap,
          child: Container(
            padding: const EdgeInsets.symmetric(horizontal: 14, vertical: 12),
            decoration: BoxDecoration(
              color: p.card,
              borderRadius: BorderRadius.circular(16),
              boxShadow: context.astraTheme.softShadow,
            ),
            child: Row(
              children: [
                Container(
                  width: 38,
                  height: 38,
                  alignment: Alignment.center,
                  decoration:
                      BoxDecoration(color: tint.withValues(alpha: 0.12), borderRadius: BorderRadius.circular(11)),
                  child: Icon(icon, size: 18, color: tint),
                ),
                const SizedBox(width: 12),
                Expanded(
                  child: Column(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: [
                      Text(label, style: ui(size: 14, weight: FontWeight.w800, color: p.ink)),
                      const SizedBox(height: 1),
                      Text(hint,
                          maxLines: 1,
                          overflow: TextOverflow.ellipsis,
                          style: ui(size: 11, weight: FontWeight.w500, color: p.textMuted)),
                    ],
                  ),
                ),
                Icon(Icons.chevron_right, size: 18, color: p.textMuted),
              ],
            ),
          ),
        ),
      ),
    );
  }
}

/// The app's sheet chrome — grip, eyebrow, serif title, subtitle — over [children].
class _SheetFrame extends StatelessWidget {
  const _SheetFrame({
    required this.eyebrow,
    required this.title,
    required this.subtitle,
    required this.children,
  });

  final String eyebrow;
  final String title;
  final Widget subtitle;
  final List<Widget> children;

  @override
  Widget build(BuildContext context) {
    final p = context.astra;
    return Container(
      decoration: BoxDecoration(
        color: p.sheet,
        borderRadius: const BorderRadius.vertical(top: Radius.circular(30)),
      ),
      padding: const EdgeInsets.fromLTRB(16, 12, 16, 16),
      child: SafeArea(
        top: false,
        child: Column(
          mainAxisSize: MainAxisSize.min,
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Center(
              child: Container(
                width: 40,
                height: 4,
                decoration: BoxDecoration(
                  color: p.textMuted.withValues(alpha: 0.30),
                  borderRadius: BorderRadius.circular(2),
                ),
              ),
            ),
            const SizedBox(height: 16),
            SectionLabel(eyebrow),
            const SizedBox(height: 4),
            Text(title, style: serif(size: 22, color: p.ink)),
            const SizedBox(height: 2),
            subtitle,
            const SizedBox(height: 14),
            ...children,
          ],
        ),
      ),
    );
  }
}
