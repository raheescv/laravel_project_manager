import 'dart:async';

import 'package:flutter/foundation.dart' show Uint8List, defaultTargetPlatform;
import 'package:flutter/material.dart';
import 'package:flutter_bloc/flutter_bloc.dart';

import 'package:invo/features/admin/logic/admin_cubit/admin_cubit.dart';
import 'package:invo/features/admin/widgets/day_session_report_pdf.dart';
import 'package:invo/features/settings/logic/print_settings_cubit/print_settings_cubit.dart';
import 'package:invo/shared/domain/helpers/formatters.dart';
import 'package:invo/shared/domain/models/index.dart';
import 'package:invo/shared/logic/paginated_list_cubit/paginated_list_cubit.dart';
import 'package:invo/shared/utils/components/theme/index.dart';
import 'package:invo/shared/utils/printing/pdf_export.dart';
import 'package:invo/shared/utils/router/http_utils/common_exception.dart';
import 'package:invo/shared/widgets/astra_snack.dart';
import 'package:invo/shared/widgets/astra_widgets.dart';
import 'package:invo/shared/widgets/pdf_preview_page.dart';
import 'package:invo/shared/widgets/receipt_printer.dart';

/// The day session "Sale Bill Report" from the app — the web's
/// print::sale::day-session-report. Lists the operating branch's sessions;
/// picking one offers the thermal roll (laid out on the device, sent to the
/// paired printer) or the web's own A4 PDF (preview, WhatsApp, share, download).
///
/// The action runs on [context] once both sheets are down, so its confirmation
/// and any preview land on the page rather than behind a closing sheet.
Future<void> showDaySessionReports(BuildContext context) async {
  final pick = await showModalBottomSheet<_Pick>(
    context: context,
    backgroundColor: Colors.transparent,
    isScrollControlled: true,
    useSafeArea: true,
    constraints: const BoxConstraints(maxWidth: 560),
    builder: (_) => const _SessionsSheet(),
  );
  if (pick == null || !context.mounted) return;
  await _run(context, pick);
}

enum _Action { thermal, preview, whatsApp, share, download }

class _Pick {
  const _Pick(this.session, this.action);
  final DaySessionSummary session;
  final _Action action;
}

Future<void> _run(BuildContext context, _Pick pick) async {
  final admin = context.read<AdminCubit>();
  final printer = context.read<PrintSettingsCubit>();
  final settings = printer.snapshot;
  final snack = AstraSnack.capture(context);
  final navigator = Navigator.of(context, rootNavigator: true);

  final session = pick.session;
  final thermal = pick.action == _Action.thermal;
  final title = 'Sale Bill Report #${session.id}';
  final fileName = 'sale-bill-report_session-${session.id}${thermal ? '_roll' : ''}.pdf';
  final caption = [
    'Sale Bill Report #${session.id}',
    Dates.human(session.openedAt),
    if (session.branch.isNotEmpty) session.branch,
  ].join(' · ');

  unawaited(showDialog<void>(
    context: context,
    useRootNavigator: true,
    barrierDismissible: false,
    builder: (_) => PdfProgressCard(
      title: 'Preparing report…',
      message: thermal ? 'Laying out the roll' : 'Rendering the A4 report',
    ),
  ));

  final Uint8List bytes;
  try {
    bytes = thermal
        ? await buildDaySessionThermalPdf(await admin.daySessionReport(session.id), settings)
        : await admin.daySessionReportPdf(session.id);
  } catch (e) {
    navigator.pop();
    snack.error(e is ApiException ? e.message : 'Could not load the report. Check the connection and try again.');
    return;
  }
  navigator.pop();
  if (!context.mounted) return;

  switch (pick.action) {
    case _Action.thermal:
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
    case _Action.preview:
      openPdfPreview(context, title: title, bytes: bytes, fileName: fileName, caption: caption);
    case _Action.whatsApp:
      await PdfExport.whatsApp(bytes, fileName, caption: caption);
    case _Action.share:
      await PdfExport.share(bytes, fileName, subject: caption);
    case _Action.download:
      await downloadPdf(bytes, fileName, caption: caption, snack: snack);
  }
}

// ---- session list -----------------------------------------------------------

class _SessionsSheet extends StatefulWidget {
  const _SessionsSheet();

  @override
  State<_SessionsSheet> createState() => _SessionsSheetState();
}

class _SessionsSheetState extends State<_SessionsSheet> {
  final _scrollCtl = ScrollController();
  late final PaginatedListCubit _list = PaginatedListCubit(
    fetch: context.read<AdminCubit>().daySessionsPage,
    errorMessage: 'Could not load the day sessions.',
  );

  @override
  void initState() {
    super.initState();
    _scrollCtl.addListener(_onScroll);
    WidgetsBinding.instance.addPostFrameCallback((_) => unawaited(_list.load()));
  }

  @override
  void dispose() {
    _scrollCtl.dispose();
    unawaited(_list.close());
    super.dispose();
  }

  void _onScroll() {
    if (!_scrollCtl.hasClients) return;
    final pos = _scrollCtl.position;
    if (pos.pixels >= pos.maxScrollExtent - 300) unawaited(_list.loadMore());
  }

  Future<void> _openActions(DaySessionSummary session) async {
    final printer = context.read<PrintSettingsCubit>();
    final action = await showModalBottomSheet<_Action>(
      context: context,
      backgroundColor: Colors.transparent,
      isScrollControlled: true,
      useSafeArea: true,
      constraints: const BoxConstraints(maxWidth: 560),
      builder: (_) => _ActionsSheet(
        session: session,
        printerName: printer.hasPrinter ? printer.printer.displayName : null,
      ),
    );
    if (action != null && mounted) Navigator.pop(context, _Pick(session, action));
  }

  @override
  Widget build(BuildContext context) {
    final p = context.astra;
    final listHeight = (MediaQuery.sizeOf(context).height * 0.55).clamp(260.0, 560.0);
    return _SheetFrame(
      eyebrow: 'Day sessions',
      title: 'Sale Bill Report',
      subtitle: 'Pick a session to print or send its report',
      child: SizedBox(
        height: listHeight,
        child: BlocBuilder<PaginatedListCubit, PaginatedListState>(
          bloc: _list,
          builder: (context, state) {
            if (state.isLoading && state.items.isEmpty) {
              return const Center(child: CircularProgressIndicator());
            }
            if (state.hasFailed && state.items.isEmpty) {
              return EmptyState(
                icon: Icons.wifi_off,
                title: 'Sessions unavailable',
                message: state.errorMessage,
                action: AstraButton(
                    label: 'Retry', icon: Icons.refresh, expand: false, onTap: () => unawaited(_list.load())),
              );
            }
            if (state.items.isEmpty) {
              return const EmptyState(
                icon: Icons.event_busy_outlined,
                title: 'No day sessions yet',
                message: 'Open a day on this branch to get its report.',
              );
            }
            return ListView.separated(
              controller: _scrollCtl,
              padding: const EdgeInsets.only(bottom: 12),
              itemCount: state.items.length + (state.loadingMore ? 1 : 0),
              separatorBuilder: (_, __) => const SizedBox(height: 9),
              itemBuilder: (_, i) {
                if (i == state.items.length) {
                  return Padding(
                    padding: const EdgeInsets.symmetric(vertical: 10),
                    child: Center(
                      child: SizedBox(
                        width: 20,
                        height: 20,
                        child: CircularProgressIndicator(strokeWidth: 2.2, color: p.primary),
                      ),
                    ),
                  );
                }
                final session = DaySessionSummary.fromJson(state.items[i]);
                return _SessionTile(session: session, onTap: () => unawaited(_openActions(session)));
              },
            );
          },
        ),
      ),
    );
  }
}

class _SessionTile extends StatelessWidget {
  const _SessionTile({required this.session, required this.onTap});

  final DaySessionSummary session;
  final VoidCallback onTap;

  @override
  Widget build(BuildContext context) {
    final p = context.astra;
    final open = session.isOpen;
    final tone = open ? AstraPalette.success : p.primary;
    final staff = [
      if (session.openedBy.isNotEmpty) 'Opened by ${session.openedBy}',
      if (session.closedBy.isNotEmpty) 'Closed by ${session.closedBy}',
    ].join(' · ');

    return GestureDetector(
      onTap: onTap,
      child: Container(
        padding: const EdgeInsets.all(12),
        decoration: BoxDecoration(
          color: p.card,
          borderRadius: BorderRadius.circular(16),
          boxShadow: context.astraTheme.softShadow,
        ),
        child: Row(
          children: [
            Container(
              width: 40,
              height: 40,
              alignment: Alignment.center,
              decoration: BoxDecoration(color: tone.withValues(alpha: 0.12), borderRadius: BorderRadius.circular(12)),
              child: Icon(open ? Icons.wb_sunny_outlined : Icons.bedtime_outlined, size: 19, color: tone),
            ),
            const SizedBox(width: 12),
            Expanded(
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  Row(
                    children: [
                      Flexible(
                        child: Text('Session #${session.id}',
                            maxLines: 1,
                            overflow: TextOverflow.ellipsis,
                            style: ui(size: 13.5, weight: FontWeight.w800, color: p.ink)),
                      ),
                      const SizedBox(width: 8),
                      Container(
                        padding: const EdgeInsets.symmetric(horizontal: 7, vertical: 2),
                        decoration: BoxDecoration(
                          color: (open ? AstraPalette.success : p.textMuted).withValues(alpha: 0.12),
                          borderRadius: BorderRadius.circular(999),
                        ),
                        child: Text(open ? 'OPEN' : 'CLOSED',
                            style: ui(
                                size: 8.5,
                                weight: FontWeight.w900,
                                color: open ? AstraPalette.success : p.textSecondary,
                                letterSpacing: 0.5)),
                      ),
                    ],
                  ),
                  const SizedBox(height: 2),
                  Text(_when(session),
                      maxLines: 1,
                      overflow: TextOverflow.ellipsis,
                      style: ui(size: 11, weight: FontWeight.w600, color: p.textSecondary)),
                  if (staff.isNotEmpty)
                    Text(staff,
                        maxLines: 1,
                        overflow: TextOverflow.ellipsis,
                        style: ui(size: 10.5, weight: FontWeight.w500, color: p.textMuted)),
                ],
              ),
            ),
            Icon(Icons.chevron_right, size: 18, color: p.textMuted),
          ],
        ),
      ),
    );
  }

  /// `14 Sep 2026 · 9:02 AM – 10:15 PM`, with the close's date too when the
  /// session ran past midnight.
  static String _when(DaySessionSummary s) {
    final opened = DateTime.tryParse(s.openedAt);
    if (opened == null) return '';
    final closed = DateTime.tryParse(s.closedAt);
    final start = '${Dates.human(s.openedAt)} · ${Dates.time(opened)}';
    if (closed == null) return '$start – open';
    final sameDay = opened.year == closed.year && opened.month == closed.month && opened.day == closed.day;
    return sameDay ? '$start – ${Dates.time(closed)}' : '$start – ${Dates.human(s.closedAt)} ${Dates.time(closed)}';
  }
}

// ---- actions for one session --------------------------------------------------

class _ActionsSheet extends StatelessWidget {
  const _ActionsSheet({required this.session, required this.printerName});

  final DaySessionSummary session;

  /// The paired printer, or null when the thermal roll goes via a preview.
  final String? printerName;

  @override
  Widget build(BuildContext context) {
    final p = context.astra;
    Widget row(IconData icon, Color tint, String label, String hint, _Action action) =>
        _ActionRow(icon: icon, tint: tint, label: label, hint: hint, onTap: () => Navigator.pop(context, action));

    return _SheetFrame(
      eyebrow: 'Session #${session.id}',
      title: 'Sale Bill Report',
      subtitle: _SessionTile._when(session),
      child: Flexible(
        child: SingleChildScrollView(
          child: Column(
            mainAxisSize: MainAxisSize.min,
            children: [
              row(Icons.receipt_long_outlined, p.primary, 'Thermal print',
                  printerName == null ? 'Preview the roll, then print' : 'Send to $printerName', _Action.thermal),
              row(Icons.picture_as_pdf_outlined, p.ink, 'A4 PDF', 'See the pages, then print or send', _Action.preview),
              row(Icons.chat_rounded, const Color(0xFF25D366), 'WhatsApp', 'Send the A4 PDF to a chat',
                  _Action.whatsApp),
              row(Icons.ios_share, p.ink, 'Share', 'Email, Drive or any other app', _Action.share),
              row(
                  Icons.download_rounded,
                  p.goldText,
                  'Download',
                  defaultTargetPlatform == TargetPlatform.android
                      ? 'Save the A4 PDF to Downloads'
                      : 'Save to Files from the share sheet',
                  _Action.download),
            ],
          ),
        ),
      ),
    );
  }
}

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
  final VoidCallback onTap;

  @override
  Widget build(BuildContext context) {
    final p = context.astra;
    return Padding(
      padding: const EdgeInsets.only(bottom: 10),
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
                decoration: BoxDecoration(color: tint.withValues(alpha: 0.12), borderRadius: BorderRadius.circular(11)),
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
    );
  }
}

/// The app's sheet chrome — grip, eyebrow, serif title, hint — around [child].
class _SheetFrame extends StatelessWidget {
  const _SheetFrame({required this.eyebrow, required this.title, required this.subtitle, required this.child});

  final String eyebrow;
  final String title;
  final String subtitle;
  final Widget child;

  @override
  Widget build(BuildContext context) {
    final p = context.astra;
    return Container(
      decoration: BoxDecoration(
        color: p.sheet,
        borderRadius: const BorderRadius.vertical(top: Radius.circular(30)),
      ),
      padding: const EdgeInsets.fromLTRB(16, 12, 16, 8),
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
            if (subtitle.isNotEmpty) ...[
              const SizedBox(height: 2),
              Text(subtitle, style: ui(size: 11.5, weight: FontWeight.w600, color: p.textMuted)),
            ],
            const SizedBox(height: 14),
            child,
          ],
        ),
      ),
    );
  }
}
