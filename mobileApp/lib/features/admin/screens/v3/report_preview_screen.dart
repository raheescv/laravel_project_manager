import 'dart:async';
import 'dart:convert' show latin1;
import 'dart:math' as math;
import 'dart:ui' as dui;

import 'package:flutter/foundation.dart' show Uint8List;
import 'package:flutter/material.dart';
import 'package:flutter_bloc/flutter_bloc.dart';
import 'package:pdf/pdf.dart' show PdfColor, PdfPageFormat;
import 'package:printing/printing.dart' show PdfPreviewCustom, Printing;

import 'package:invo/features/admin/logic/admin_cubit/admin_cubit.dart';
import 'package:invo/features/admin/widgets/report_sort_sheet.dart';
import 'package:invo/features/admin/widgets/day_session_report_pdf.dart';
import 'package:invo/features/admin/widgets/report_pdf.dart';
import 'package:invo/features/admin/widgets/report_thermal_pdf.dart';
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
import 'package:invo/shared/widgets/pdf_preview_page.dart';
import 'package:invo/shared/widgets/receipt_printer.dart';

/// Every report the app prints.
enum ExportReport {
  overview('Sales Overview', 'Performance, payments, staff', Icons.insights_rounded),
  items('Item Sales', 'Every item, ranked', Icons.inventory_2_outlined),
  categories('Category Sales', 'Sales mix by category', Icons.category_outlined),
  staff('Staff Sales', 'Revenue per person', Icons.people_alt_outlined),
  daySession('Sale Bill Report', 'The current day session', Icons.receipt_long_outlined);

  const ExportReport(this.title, this.hint, this.icon);
  final String title;
  final String hint;
  final IconData icon;

  /// The Reports-screen report behind this choice; null for the day session,
  /// whose documents come from its own endpoints.
  ReportExportKind? get kind => switch (this) {
        ExportReport.overview => ReportExportKind.overview,
        ExportReport.items => ReportExportKind.items,
        ExportReport.categories => ReportExportKind.categories,
        ExportReport.staff => ReportExportKind.stylists,
        ExportReport.daySession => null,
      };

  /// Every report is also laid out on the thermal roll, so the preview offers
  /// A4 or the roll width.
  bool get hasRoll => true;
}

/// The paper a report is laid out on.
enum ReportPaper { a4, roll }

/// The paper picked last — the preview opens on it again for this app run, so
/// a till that prints its reports on the roll isn't switched back every time.
ReportPaper _lastPaper = ReportPaper.a4;

/// The Sale Bill Report's thermal layout picked last — remembered the same way.
TransactionsLayout _lastLayout = TransactionsLayout.combined;

/// [reports] less the ones the signed-in user may not print.
List<ExportReport> printableReports(BuildContext context, [List<ExportReport> reports = ExportReport.values]) {
  final auth = context.read<AuthCubit>();
  return [
    for (final r in reports)
      if (auth.hasPermission(r == ExportReport.daySession ? PermissionSlug.daySessionPrint : PermissionSlug.report)) r,
  ];
}

/// The report's preview, full screen: the page itself, with the paper and
/// Print in one pill under it and Close, Share and Save over it. The day
/// session is [sessionId], or the *current* one: the branch's open session, or
/// the one opened last once the day is shut.
Future<void> openReportPreview(
  BuildContext context,
  ExportReport report, {
  ReportPaper? paper,
  String? sessionId,
}) async {
  if (printableReports(context, [report]).isEmpty) return;
  await Navigator.of(context, rootNavigator: true).push(MaterialPageRoute<void>(
    fullscreenDialog: true,
    builder: (_) => ReportPreviewScreen(report: report, paper: paper, sessionId: sessionId),
  ));
}

/// A day session's Sale Bill Report on the thermal roll, in one tap: laid out
/// on the device and sent straight to the paired printer. With no printer
/// paired it opens the roll's preview instead — the only way to pick a printer
/// on this device. The Day Session screen's report card and print-on-close
/// both come through here. Never throws.
Future<void> printDaySessionRoll(BuildContext context, String sessionId) async {
  final printer = context.read<PrintSettingsCubit>();
  if (!printer.hasPrinter) {
    return openReportPreview(context, ExportReport.daySession, paper: ReportPaper.roll, sessionId: sessionId);
  }

  final admin = context.read<AdminCubit>();
  final settings = printer.snapshot;
  final snack = AstraSnack.capture(context);
  final navigator = Navigator.of(context, rootNavigator: true);
  final me = context.read<AuthCubit>().user?.id ?? '';

  unawaited(showDialog<void>(
    context: context,
    useRootNavigator: true,
    barrierDismissible: false,
    builder: (_) => PdfProgressCard(
      title: _preparingRoll,
      message: 'Sale Bill Report #$sessionId · ${settings.width.label} roll',
    ),
  ));

  final Uint8List bytes;
  try {
    final report = await admin.daySessionReport(sessionId);
    bytes = await buildDaySessionThermalPdf(report, settings, highlightUserId: me, layout: _lastLayout);
  } catch (e) {
    navigator.pop();
    snack.error(e is ApiException ? e.message : _couldNotPrepare);
    return;
  }
  navigator.pop();

  final result = await printRollPdf(bytes, settings, title: 'Sale Bill Report #$sessionId', target: printer.printer);
  if (result == ReceiptPrintResult.printed) snack.success('Sent to ${printer.printer.displayName}');
  if (result == ReceiptPrintResult.failed) snack.error(_unreachable);
}

const _preparingRoll = 'Preparing thermal print…';
const _unreachable = 'Couldn\'t reach the printer — check it\'s on and paired.';
const _couldNotPrepare = 'Could not prepare the report. Check the connection and try again.';

/// One laid-out document and how it is named when it leaves the app.
class _Doc {
  _Doc(this.bytes, {required this.title, required this.fileName, required this.caption});

  final Uint8List bytes;
  final String title;
  final String fileName;
  final String caption;

  /// The preview's build callback. Tear-offs of it compare equal, so the
  /// preview rasterises this document once, however often the screen rebuilds.
  Future<Uint8List> layout(PdfPageFormat _) async => bytes;
}

class ReportPreviewScreen extends StatefulWidget {
  const ReportPreviewScreen({super.key, required this.report, this.paper, this.sessionId});

  final ExportReport report;
  final ReportPaper? paper;
  final String? sessionId;

  @override
  State<ReportPreviewScreen> createState() => _ReportPreviewScreenState();
}

class _ReportPreviewScreenState extends State<ReportPreviewScreen> {
  ExportReport get _report => widget.report;
  late ReportPaper _paper = widget.paper ?? _lastPaper;
  late TransactionsLayout _txLayout = _lastLayout;

  /// Built documents by paper, so flipping back is instant.
  final _docs = <ReportPaper, Future<_Doc>>{};
  final _ready = <ReportPaper, _Doc>{};

  /// The figures behind them — one fetch serves both papers.
  Future<ReportExport>? _data;
  Future<DaySessionSummary?>? _session;
  Future<DaySessionReport>? _sessionReport;

  ReportPaper get _shown => _report.hasRoll ? _paper : ReportPaper.a4;
  bool get _roll => _shown == ReportPaper.roll;

  /// The document on [_shown] paper, built once. Its arrival rebuilds the
  /// whole screen, not just the pages — the bar's Share and Save and the
  /// pill's Print wake up with it.
  Future<_Doc> _doc() {
    final paper = _shown;
    return _docs.putIfAbsent(
      paper,
      () => _build(paper).then((d) {
        if (mounted) setState(() => _ready[paper] = d);
        return d;
      }),
    );
  }

  /// Drops what failed so the next look fetches it again.
  void _retry() => setState(() {
        _docs.remove(_shown);
        _data = null;
        _session = null;
        _sessionReport = null;
      });

  /// Applies on tap, like the paper switch — no Save. Only the roll changes
  /// shape with it, so just that cached document is dropped.
  void _setLayout(TransactionsLayout layout) {
    if (layout == _txLayout) return;
    setState(() {
      _txLayout = _lastLayout = layout;
      _docs.remove(ReportPaper.roll);
      _ready.remove(ReportPaper.roll);
    });
  }

  // ---- building the documents ------------------------------------------------

  Future<_Doc> _build(ReportPaper paper) async {
    final admin = context.read<AdminCubit>();
    final settings = context.read<PrintSettingsCubit>().snapshot;
    final brand = _brand();

    if (_report == ExportReport.daySession) {
      final s = await _currentSession();
      if (s == null) throw const _Failure('No day session on this branch yet.');
      final title = 'Sale Bill Report #${s.id}';
      final caption = [title, Dates.human(s.openedAt), if (s.branch.isNotEmpty) s.branch].join(' · ');
      if (paper == ReportPaper.roll) {
        final data = await _dayReport(s.id);
        return _Doc(
            await buildDaySessionThermalPdf(data, settings, highlightUserId: brand.preparedById, layout: _txLayout),
            title: title, fileName: 'sale-bill-report_session-${s.id}_roll.pdf', caption: caption);
      }
      return _Doc(await admin.daySessionReportPdf(s.id),
          title: title, fileName: 'sale-bill-report_session-${s.id}.pdf', caption: caption);
    }

    // The overview is fetched with its categories either way — the roll prints
    // them — so flipping the paper never goes back to the server.
    final data = await (_data ??=
        admin.exportReport(_report.kind!, withCategories: _report == ExportReport.overview));
    final roll = paper == ReportPaper.roll;
    final overview = data.kind == ReportExportKind.overview;
    return _Doc(
      roll
          ? (overview
              ? await buildOverviewThermalPdf(data, brand, settings.width)
              : await buildBreakdownThermalPdf(data, brand, settings.width))
          : await buildReportPdf(data, brand),
      title: data.kind.title,
      fileName: roll ? data.fileName.replaceFirst(RegExp(r'\.pdf$'), '_roll.pdf') : data.fileName,
      caption: [
        data.kind.title,
        Dates.range(data.startDate, data.endDate),
        if (brand.branchName.isNotEmpty) brand.branchName,
      ].join(' · '),
    );
  }

  Future<DaySessionSummary?> _currentSession() {
    final id = widget.sessionId;
    return _session ??= id == null
        ? context.read<AdminCubit>().currentDaySession()
        : _dayReport(id).then((r) => r.session);
  }

  Future<DaySessionReport> _dayReport(String id) =>
      _sessionReport ??= context.read<AdminCubit>().daySessionReport(id);

  /// Letterhead: company name and logo from the web print settings (each only
  /// when the tenant prints it), the branch the app is operating as, and who
  /// is signed in. The accent follows the theme preset.
  ReportPdfBrand _brand() {
    final print = context.read<PrintSettingsCubit>().snapshot;
    final user = context.read<AuthCubit>().user;
    return ReportPdfBrand(
      companyName: print.companyName,
      branchName: context.read<BranchCubit>().selected?.name ?? '',
      preparedBy: user?.name ?? '',
      preparedById: user?.id ?? '',
      logo: print.logo,
      accent: PdfColor.fromInt(context.astra.primary.toARGB32()),
    );
  }

  // ---- actions --------------------------------------------------------------------

  /// The roll goes straight to the paired printer (or the print dialog with
  /// none); A4 always goes through the dialog, where the printer is picked.
  Future<void> _print(_Doc doc) async {
    if (!_roll) {
      await PdfExport.printDialog(doc.bytes, doc.fileName);
      return;
    }
    final printer = context.read<PrintSettingsCubit>();
    final snack = AstraSnack.capture(context);
    final result = await printRollPdf(doc.bytes, printer.snapshot, title: doc.title, target: printer.printer);
    if (result == ReceiptPrintResult.printed) snack.success('Sent to ${printer.printer.displayName}');
    if (result == ReceiptPrintResult.failed) snack.error(_unreachable);
  }

  /// The share sheet carries WhatsApp, email, Drive and the rest.
  Future<void> _share(_Doc doc) => PdfExport.share(doc.bytes, doc.fileName, subject: doc.caption);

  Future<void> _save(_Doc doc) =>
      downloadPdf(doc.bytes, doc.fileName, caption: doc.caption, snack: AstraSnack.capture(context));

  // ---- build -----------------------------------------------------------------------

  @override
  Widget build(BuildContext context) {
    final p = context.astra;
    final desk = p.isDark ? const Color(0xFF10141F) : const Color(0xFFE9ECF1);
    final doc = _ready[_shown];
    return Scaffold(
      backgroundColor: desk,
      body: Stack(
        children: [
          Positioned.fill(child: doc != null ? _pages(doc, desk) : _pending(desk)),
          Positioned(top: 0, left: 0, right: 0, child: _topBar(doc, desk)),
          Positioned(left: 0, right: 0, bottom: 0, child: _pill(doc)),
        ],
      ),
    );
  }

  /// The document while it is fetched and laid out — or why it couldn't be.
  Widget _pending(Color desk) => FutureBuilder<_Doc>(
        future: _doc(),
        builder: (context, snap) {
          if (snap.connectionState != ConnectionState.done) {
            return PdfProgressCard(blocking: false, title: _roll ? _preparingRoll : 'Preparing PDF…', message: _making());
          }
          if (snap.hasError) return _failed(snap.error!);
          return _pages(snap.data!, desk);
        },
      );

  /// What the progress card says is being made: which report, on which paper.
  String _making() {
    final what = _report == ExportReport.daySession && widget.sessionId != null
        ? 'Sale Bill Report #${widget.sessionId}'
        : _report.title;
    if (_roll) return '$what · ${context.read<PrintSettingsCubit>().width.label} roll';
    return _report == ExportReport.daySession ? '$what · A4' : '$what · A4, every line in the range';
  }

  /// The pages on the desk, clear of the bar above and the pill below. A roll
  /// gets its own view — see [_RollView].
  Widget _pages(_Doc doc, Color desk) {
    final padding = EdgeInsets.only(top: MediaQuery.paddingOf(context).top + 64, bottom: 104);
    if (_roll) {
      return _RollView(
        key: ObjectKey(doc),
        bytes: doc.bytes,
        padding: padding + const EdgeInsets.symmetric(vertical: 8),
        onError: _failed,
      );
    }
    return PdfPreviewCustom(
      key: ObjectKey(doc),
      build: doc.layout,
      maxPageWidth: 640,
      scrollViewDecoration: BoxDecoration(color: desk),
      pdfPreviewPageDecoration: BoxDecoration(
        color: Colors.white,
        borderRadius: BorderRadius.circular(3),
        boxShadow: _paperShadow,
      ),
      padding: padding,
      previewPageMargin: const EdgeInsets.symmetric(horizontal: 18, vertical: 8),
      loadingWidget: const SizedBox.shrink(),
      onError: (_, e) => _failed(e),
    );
  }

  Widget _failed(Object e) {
    final p = context.astra;
    final message = switch (e) {
      ApiException(:final message) => message,
      _Failure(:final message) => message,
      _ => _couldNotPrepare,
    };
    return Center(
      child: Padding(
        padding: const EdgeInsets.fromLTRB(32, 0, 32, 40),
        child: Column(
          mainAxisSize: MainAxisSize.min,
          children: [
            Icon(Icons.cloud_off_rounded, size: 34, color: p.textMuted),
            const SizedBox(height: 10),
            Text(message,
                textAlign: TextAlign.center,
                style: ui(size: 13, weight: FontWeight.w600, color: p.textSecondary, height: 1.4)),
            const SizedBox(height: 12),
            TextButton.icon(
              onPressed: _retry,
              icon: const Icon(Icons.refresh_rounded, size: 18),
              label: const Text('Try again'),
            ),
          ],
        ),
      ),
    );
  }

  // ---- top bar -------------------------------------------------------------------

  /// Close, the report and its range, Share and Save — over a fade of the desk
  /// so the page scrolls away under it.
  Widget _topBar(_Doc? doc, Color desk) {
    final p = context.astra;
    final session = _report == ExportReport.daySession;
    Widget send(IconData icon, String tip, Future<void> Function(_Doc) run) => _RoundButton(
          icon: icon,
          tooltip: tip,
          onTap: doc == null ? null : () => unawaited(run(doc)),
        );
    return Container(
      decoration: BoxDecoration(
        gradient: LinearGradient(
          begin: Alignment.topCenter,
          end: Alignment.bottomCenter,
          colors: [desk, desk, desk.withValues(alpha: 0)],
          stops: const [0, 0.62, 1],
        ),
      ),
      child: SafeArea(
        bottom: false,
        child: Padding(
          padding: const EdgeInsets.fromLTRB(14, 6, 14, 16),
          child: Row(
            children: [
              _RoundButton(icon: Icons.close_rounded, tooltip: 'Close', onTap: () => Navigator.of(context).maybePop()),
              const SizedBox(width: 10),
              Expanded(
                child: Column(
                  children: [
                    Text(session && doc != null ? doc.title : _report.title,
                        maxLines: 1,
                        overflow: TextOverflow.ellipsis,
                        style: ui(size: 15, weight: FontWeight.w800, color: p.ink)),
                    const SizedBox(height: 2),
                    session ? _sessionLine() : _line(_scope()),
                  ],
                ),
              ),
              const SizedBox(width: 10),
              if (session && _roll) ...[
                _RoundButton(icon: Icons.view_agenda_outlined, tooltip: 'Transactions layout', onTap: _pickLayout),
                const SizedBox(width: 8),
              ],
              send(Icons.ios_share_rounded, 'Share', _share),
              const SizedBox(width: 8),
              send(Icons.download_rounded, 'Save', _save),
            ],
          ),
        ),
      ),
    );
  }

  Widget _line(String text) => Text(text,
      maxLines: 1,
      overflow: TextOverflow.ellipsis,
      style: ui(size: 11, weight: FontWeight.w600, color: context.astra.textMuted));

  /// Combined or detailed SALE TRANSACTIONS, applied on tap — no Save.
  Future<void> _pickLayout() async {
    final chosen = await showModalBottomSheet<TransactionsLayout>(
      context: context,
      backgroundColor: Colors.transparent,
      isScrollControlled: true,
      useSafeArea: true,
      constraints: const BoxConstraints(maxWidth: 480),
      builder: (_) => _LayoutPicker(current: _txLayout),
    );
    if (chosen != null) _setLayout(chosen);
  }

  /// Range, the filters that shape the report, and the branch.
  String _scope() {
    final admin = context.read<AdminCubit>();
    final branch = context.read<BranchCubit>().selected?.name ?? '';
    final parts = [Dates.range(admin.startDate, admin.endDate)];
    if (_report == ExportReport.items || _report == ExportReport.categories) {
      parts.add('By ${sortChipLabel(admin.sortKey, admin.reportType).toLowerCase()}'
          '${admin.sortAscending ? ' ↑' : ''}');
      parts.add(switch (admin.itemProductType) {
        'product' => 'Products',
        'service' => 'Services',
        _ => 'All types',
      });
    }
    if (branch.isNotEmpty) parts.add(branch);
    return parts.join(' · ');
  }

  /// When the session opened or closed, once it is known.
  Widget _sessionLine() => FutureBuilder<DaySessionSummary?>(
        future: _currentSession(),
        builder: (context, snap) {
          final s = snap.data;
          if (snap.connectionState != ConnectionState.done) return _line('Finding the session…');
          if (s == null) return _line(snap.hasError ? 'Session not loaded' : 'No day session yet');
          return _line(s.isOpen
              ? 'Open since ${Dates.humanDateTime(s.openedAt)}'
              : 'Closed ${Dates.humanDateTime(s.closedAt)}');
        },
      );

  // ---- the pill ---------------------------------------------------------------------

  /// The paper (when the report has a roll) and Print, in one floating pill.
  Widget _pill(_Doc? doc) {
    final p = context.astra;
    return SafeArea(
      top: false,
      child: Padding(
        padding: const EdgeInsets.only(bottom: 16),
        child: Center(
          child: Container(
            padding: const EdgeInsets.all(5),
            decoration: BoxDecoration(
              color: p.cardSolid,
              borderRadius: BorderRadius.circular(999),
              border: Border.all(color: p.hairline),
              boxShadow: [
                BoxShadow(
                    color: Colors.black.withValues(alpha: p.isDark ? 0.5 : 0.22),
                    blurRadius: 34,
                    offset: const Offset(0, 16),
                    spreadRadius: -12),
              ],
            ),
            child: Row(
              mainAxisSize: MainAxisSize.min,
              children: [
                if (_report.hasRoll) ...[_paperSwitch(), const SizedBox(width: 6)],
                _printButton(doc),
              ],
            ),
          ),
        ),
      ),
    );
  }

  /// A4 or the roll. Applies on tap and is remembered for the next preview.
  Widget _paperSwitch() {
    final p = context.astra;
    final width = context.read<PrintSettingsCubit>().width;
    Widget seg(ReportPaper paper, String label) {
      final active = _paper == paper;
      return GestureDetector(
        onTap: active ? null : () => setState(() => _paper = _lastPaper = paper),
        child: AnimatedContainer(
          duration: const Duration(milliseconds: 160),
          padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 8),
          decoration: BoxDecoration(
            color: active ? p.cardSolid : Colors.transparent,
            borderRadius: BorderRadius.circular(999),
            boxShadow: active ? context.astraTheme.softShadow : null,
          ),
          child: Text(label, style: ui(size: 12, weight: FontWeight.w800, color: active ? p.ink : p.textSecondary)),
        ),
      );
    }

    return Container(
      padding: const EdgeInsets.all(3),
      decoration: BoxDecoration(color: p.tint, borderRadius: BorderRadius.circular(999)),
      child: Row(
        mainAxisSize: MainAxisSize.min,
        children: [seg(ReportPaper.a4, 'A4'), seg(ReportPaper.roll, width.label)],
      ),
    );
  }

  Widget _printButton(_Doc? doc) {
    final p = context.astra;
    return Opacity(
      opacity: doc == null ? 0.5 : 1,
      child: GestureDetector(
        onTap: doc == null ? null : () => unawaited(_print(doc)),
        child: Container(
          padding: EdgeInsets.symmetric(horizontal: _report.hasRoll ? 18 : 30, vertical: 11),
          decoration: BoxDecoration(
            gradient: p.primaryGradient,
            borderRadius: BorderRadius.circular(999),
            boxShadow: context.astraTheme.floatShadow(p.primary),
          ),
          child: Row(
            mainAxisSize: MainAxisSize.min,
            children: [
              const Icon(Icons.print_rounded, size: 19, color: Colors.white),
              const SizedBox(width: 7),
              Text('Print', style: ui(size: 14, weight: FontWeight.w800, color: Colors.white)),
            ],
          ),
        ),
      ),
    );
  }
}

final _paperShadow = [
  BoxShadow(color: Colors.black.withValues(alpha: 0.24), blurRadius: 30, offset: const Offset(0, 16), spreadRadius: -14),
  BoxShadow(color: Colors.black.withValues(alpha: 0.06), blurRadius: 4, offset: const Offset(0, 1)),
];

/// A roll as it prints: one continuous page, however long. The stock preview
/// draws every page as a single bitmap, and a busy day's Sale Bill Report —
/// a row per transaction — runs past the GPU's texture limit (8–16k px) and is
/// cut off. So the roll is rasterised here, once, at the screen's sharpness,
/// and shown as a stack of strips no taller than [_band].
class _RollView extends StatefulWidget {
  const _RollView({super.key, required this.bytes, required this.padding, required this.onError});

  final Uint8List bytes;
  final EdgeInsets padding;
  final Widget Function(Object error) onError;

  @override
  State<_RollView> createState() => _RollViewState();
}

class _RollViewState extends State<_RollView> {
  /// Widest the slip shows — past this it only gets softer, not more legible.
  static const _maxWidth = 360.0;

  /// Rows per strip, well inside every GPU's texture limit.
  static const _band = 2048;

  /// Ceiling on the whole bitmap (~64 MB of RGBA): an extreme roll is drawn a
  /// little softer rather than running the device out of memory.
  static const _maxPixels = 16e6;

  List<dui.Image>? _strips;
  Object? _error;
  bool _started = false;

  @override
  void didChangeDependencies() {
    super.didChangeDependencies();
    if (_started) return;
    _started = true;
    unawaited(_raster(MediaQuery.of(context)));
  }

  Future<void> _raster(MediaQueryData mq) async {
    final strips = <dui.Image>[];
    try {
      final (width, height) = _pageSize(widget.bytes);
      var dpi = math.min(mq.size.width - 36, _maxWidth) * mq.devicePixelRatio / width * 72;
      final pixels = (width * dpi / 72) * (height * dpi / 72);
      if (pixels > _maxPixels) dpi *= math.sqrt(_maxPixels / pixels);

      final page = await Printing.raster(widget.bytes, pages: const [0], dpi: dpi).first;
      final rowBytes = page.width * 4;
      for (var y = 0; y < page.height; y += _band) {
        final rows = math.min(_band, page.height - y);
        final slice = Uint8List.sublistView(page.pixels, y * rowBytes, (y + rows) * rowBytes);
        strips.add(await _decode(slice, page.width, rows));
      }
    } catch (e) {
      for (final s in strips) {
        s.dispose();
      }
      if (mounted) setState(() => _error = e);
      return;
    }
    if (!mounted) {
      for (final s in strips) {
        s.dispose();
      }
      return;
    }
    setState(() => _strips = strips);
  }

  static Future<dui.Image> _decode(Uint8List rgba, int width, int height) {
    final done = Completer<dui.Image>();
    dui.decodeImageFromPixels(rgba, width, height, dui.PixelFormat.rgba8888, done.complete);
    return done.future;
  }

  /// The first page's size in points, read from its /MediaBox — the pdf
  /// package writes it in plain text. Height 0 (no memory cap) if it can't be
  /// found.
  static (double, double) _pageSize(Uint8List pdf) {
    final box = RegExp(r'/MediaBox\s*\[\s*[-\d.]+\s+[-\d.]+\s+([\d.]+)\s+([\d.]+)').firstMatch(latin1.decode(pdf));
    if (box == null) return (PdfPageFormat.roll80.width, 0);
    return (double.parse(box.group(1)!), double.parse(box.group(2)!));
  }

  @override
  void dispose() {
    for (final s in _strips ?? const <dui.Image>[]) {
      s.dispose();
    }
    super.dispose();
  }

  @override
  Widget build(BuildContext context) {
    if (_error != null) return widget.onError(_error!);
    final strips = _strips;
    if (strips == null) {
      return Center(
        child: SizedBox(
          width: 24,
          height: 24,
          child: CircularProgressIndicator(strokeWidth: 2.4, color: context.astra.primary),
        ),
      );
    }
    final shown = math.min(MediaQuery.sizeOf(context).width - 36, _maxWidth);
    final scale = shown / strips.first.width;
    return ListView(
      padding: widget.padding,
      children: [
        Center(
          child: DecoratedBox(
            decoration: BoxDecoration(color: Colors.white, boxShadow: _paperShadow),
            child: Column(
              children: [
                for (final s in strips)
                  RawImage(
                    image: s,
                    width: shown,
                    height: s.height * scale,
                    fit: BoxFit.fill,
                    filterQuality: FilterQuality.medium,
                  ),
              ],
            ),
          ),
        ),
      ],
    );
  }
}

/// A round card-coloured button over the page — Close, Share, Save. Dimmed
/// and inert while [onTap] is null.
class _RoundButton extends StatelessWidget {
  const _RoundButton({required this.icon, required this.tooltip, required this.onTap});

  final IconData icon;
  final String tooltip;
  final VoidCallback? onTap;

  @override
  Widget build(BuildContext context) {
    final p = context.astra;
    return Tooltip(
      message: tooltip,
      child: Opacity(
        opacity: onTap == null ? 0.45 : 1,
        child: GestureDetector(
          onTap: onTap,
          child: Container(
            width: 38,
            height: 38,
            alignment: Alignment.center,
            decoration: BoxDecoration(
              color: p.cardSolid,
              shape: BoxShape.circle,
              boxShadow: context.astraTheme.softShadow,
            ),
            child: Icon(icon, size: 19, color: p.ink),
          ),
        ),
      ),
    );
  }
}

/// The Sale Bill Report's roll layout, chosen with a tap — a floating card
/// like [showReportPicker]'s, with the picked layout ticked.
class _LayoutPicker extends StatelessWidget {
  const _LayoutPicker({required this.current});

  final TransactionsLayout current;

  @override
  Widget build(BuildContext context) {
    final p = context.astra;
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
              padding: const EdgeInsets.fromLTRB(12, 14, 12, 4),
              child: Text('Transactions layout', style: ui(size: 18, weight: FontWeight.w800, color: p.ink)),
            ),
            _row(context, TransactionsLayout.combined, Icons.call_merge_rounded, 'Combined',
                'One row per invoice, its payment methods together'),
            _row(context, TransactionsLayout.detailed, Icons.table_rows_rounded, 'Detailed',
                'Every payment method its own row, like the web print'),
          ],
        ),
      ),
    );
  }

  /// Icon tile, title over hint, and a tick on the picked layout.
  Widget _row(BuildContext context, TransactionsLayout layout, IconData icon, String title, String hint) {
    final p = context.astra;
    final active = layout == current;
    return Material(
      color: Colors.transparent,
      child: InkWell(
        onTap: () => Navigator.of(context).pop(layout),
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
                child: Icon(icon, size: 22, color: active ? Colors.white : p.primary),
              ),
              const SizedBox(width: 14),
              Expanded(
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    Text(title, style: ui(size: 15.5, weight: FontWeight.w800, color: p.ink)),
                    const SizedBox(height: 2),
                    Text(hint,
                        maxLines: 2,
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

/// A reason the report can't be shown that isn't a server error.
class _Failure implements Exception {
  const _Failure(this.message);
  final String message;
}
