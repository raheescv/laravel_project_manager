part of 'reports_screen.dart';

// The Reports screen's PDF export: the Export sheet (pick a report, then
// Preview / Print / WhatsApp / Share / Download), building the document, and
// the full-screen preview. A `part` like reports_overview_sections.dart, so it
// stays library-private.

enum _ExportAction { preview, print, whatsApp, share, download }

const _whatsAppGreen = Color(0xFF25D366);

extension _ReportExportActions on _ReportsScreenState {
  /// The report the sheet opens on — whatever is on screen right now.
  ReportExportKind get _kindOnScreen {
    if (_tab == 0) return ReportExportKind.overview;
    return context.read<AdminCubit>().reportType == 'itemwise'
        ? ReportExportKind.items
        : ReportExportKind.stylists;
  }

  /// Toolbar button on tablets; phones use the header's download button.
  Widget _exportButton({double size = 40, double radius = 12, double iconSize = 18}) {
    final p = context.astra;
    return GestureDetector(
      onTap: () => unawaited(_openExportSheet()),
      child: Container(
        width: size,
        height: size,
        alignment: Alignment.center,
        decoration: BoxDecoration(color: p.tint, borderRadius: BorderRadius.circular(radius)),
        child: Icon(Icons.download_rounded, size: iconSize, color: p.primary),
      ),
    );
  }

  Future<void> _openExportSheet() async {
    var kind = _kindOnScreen;
    await showModalBottomSheet<void>(
      context: context,
      backgroundColor: Colors.transparent,
      isScrollControlled: true,
      useSafeArea: true,
      constraints: const BoxConstraints(maxWidth: 560),
      builder: (_) => StatefulBuilder(
        builder: (sheetCtx, setSheet) {
          final p = sheetCtx.astra;
          Widget action(IconData icon, Color tint, String label, String hint, _ExportAction what) =>
              _exportRow(sheetCtx, icon, tint, label, hint, () => unawaited(_export(kind, what)));
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
                  const SectionLabel('Export PDF'),
                  const SizedBox(height: 4),
                  Text(kind.title, style: serif(size: 22, color: p.ink)),
                  const SizedBox(height: 2),
                  Text(_exportScope(kind),
                      style: ui(size: 11.5, weight: FontWeight.w600, color: p.textMuted)),
                  const SizedBox(height: 14),
                  _kindPicker(sheetCtx, kind, (k) => setSheet(() => kind = k)),
                  const SizedBox(height: 14),
                  // Scrollable so the five actions never overflow a landscape
                  // phone or a short viewport.
                  Flexible(
                    child: SingleChildScrollView(
                      child: Column(
                        mainAxisSize: MainAxisSize.min,
                        children: [
                          action(Icons.visibility_outlined, p.primary, 'Preview',
                              'See the pages, then print or send', _ExportAction.preview),
                          action(Icons.print_outlined, p.ink, 'Print', 'Send it to a printer', _ExportAction.print),
                          action(Icons.chat_rounded, _whatsAppGreen, 'WhatsApp', 'Send the PDF to a chat',
                              _ExportAction.whatsApp),
                          action(Icons.ios_share, p.ink, 'Share', 'Email, Drive or any other app',
                              _ExportAction.share),
                          action(
                              Icons.download_rounded,
                              p.goldText,
                              'Download',
                              defaultTargetPlatform == TargetPlatform.android
                                  ? 'Save a copy to Downloads'
                                  : 'Save to Files from the share sheet',
                              _ExportAction.download),
                        ],
                      ),
                    ),
                  ),
                ],
              ),
            ),
          );
        },
      ),
    );
  }

  /// Range plus the filters that shape the chosen report, under the sheet title.
  String _exportScope(ReportExportKind kind) {
    final admin = context.read<AdminCubit>();
    final range = Dates.range(admin.startDate, admin.endDate);
    if (kind != ReportExportKind.items) return range;
    final type = switch (admin.itemProductType) {
      'product' => 'Products',
      'service' => 'Services',
      _ => 'All types',
    };
    return '$range · By ${admin.itemMetric == 'qty' ? 'qty' : 'amount'} · $type';
  }

  /// Overview / By Item / By Staff — the same segmented look as the
  /// breakdown's own toggle. Opens on the report that is on screen.
  Widget _kindPicker(BuildContext ctx, ReportExportKind selected, ValueChanged<ReportExportKind> onPick) {
    final p = ctx.astra;
    Widget seg(String label, ReportExportKind kind, IconData icon) {
      final active = selected == kind;
      return Expanded(
        child: GestureDetector(
          onTap: () => onPick(kind),
          child: Container(
            padding: const EdgeInsets.symmetric(vertical: 9),
            alignment: Alignment.center,
            decoration: BoxDecoration(
              gradient: active ? p.primaryGradient : null,
              borderRadius: BorderRadius.circular(10),
              boxShadow: active ? ctx.astraTheme.floatShadow(p.primary) : null,
            ),
            child: Row(
              mainAxisSize: MainAxisSize.min,
              children: [
                Icon(icon, size: 14, color: active ? Colors.white : p.textSecondary),
                const SizedBox(width: 6),
                Flexible(
                  child: Text(label,
                      maxLines: 1,
                      overflow: TextOverflow.ellipsis,
                      style: ui(size: 11.5, weight: FontWeight.w800, color: active ? Colors.white : p.textSecondary)),
                ),
              ],
            ),
          ),
        ),
      );
    }

    return Container(
      padding: const EdgeInsets.all(4),
      decoration: BoxDecoration(color: p.tint, borderRadius: BorderRadius.circular(13)),
      child: Row(children: [
        seg('Overview', ReportExportKind.overview, Icons.insights_rounded),
        seg('By Item', ReportExportKind.items, Icons.inventory_2_rounded),
        seg('By Staff', ReportExportKind.stylists, Icons.people_alt_rounded),
      ]),
    );
  }

  /// One action row in the sheet: tinted icon tile, title + hint, chevron. A
  /// tap closes the sheet, then runs [onTap] on the page.
  Widget _exportRow(BuildContext ctx, IconData icon, Color tint, String label, String hint, VoidCallback onTap) {
    final p = ctx.astra;
    return Padding(
      padding: const EdgeInsets.only(bottom: 10),
      child: GestureDetector(
        onTap: () {
          Navigator.pop(ctx);
          onTap();
        },
        child: Container(
          padding: const EdgeInsets.symmetric(horizontal: 14, vertical: 12),
          decoration: BoxDecoration(
            color: p.card,
            borderRadius: BorderRadius.circular(16),
            boxShadow: ctx.astraTheme.softShadow,
          ),
          child: Row(
            children: [
              Container(
                width: 38,
                height: 38,
                alignment: Alignment.center,
                decoration: BoxDecoration(
                  color: tint.withValues(alpha: 0.12),
                  borderRadius: BorderRadius.circular(11),
                ),
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

  /// Pulls the report, builds the PDF, then does [action] with it. A long
  /// breakdown means walking every page of it, so a progress card holds the
  /// screen until the document is ready.
  Future<void> _export(ReportExportKind kind, _ExportAction action) async {
    final admin = context.read<AdminCubit>();
    final snack = AstraSnack.capture(context);
    final brand = _pdfBrand();
    final navigator = Navigator.of(context, rootNavigator: true);
    unawaited(showDialog<void>(
      context: context,
      useRootNavigator: true,
      barrierDismissible: false,
      builder: (_) => const _ExportProgress(),
    ));

    final ReportExport data;
    final Uint8List bytes;
    try {
      data = await admin.exportReport(kind);
      bytes = await buildReportPdf(data, brand);
    } catch (e) {
      navigator.pop();
      snack.error(e is ApiException ? e.message : 'Could not prepare the PDF. Check the connection and try again.');
      return;
    }
    navigator.pop();

    final caption = [
      data.kind.title,
      Dates.range(data.startDate, data.endDate),
      if (brand.branchName.isNotEmpty) brand.branchName,
    ].join(' · ');
    switch (action) {
      case _ExportAction.preview:
        if (context.mounted) _openPreview(data, bytes, caption);
      case _ExportAction.print:
        await PdfExport.printDialog(bytes, data.fileName);
      case _ExportAction.whatsApp:
        await PdfExport.whatsApp(bytes, data.fileName, caption: caption);
      case _ExportAction.share:
        await PdfExport.share(bytes, data.fileName, subject: caption);
      case _ExportAction.download:
        await _download(bytes, data.fileName, caption, snack);
    }
  }

  /// Saves to Downloads, then offers WhatsApp straight from the confirmation.
  /// Where the app can't write a Downloads copy itself (iOS, Android 9 and
  /// older) the share sheet is the download — its Save to Files / Save to
  /// device does the job.
  Future<void> _download(Uint8List bytes, String fileName, String caption, AstraSnackHandle snack) async {
    final where = await PdfExport.saveToDownloads(bytes, fileName);
    if (where == null) {
      await PdfExport.share(bytes, fileName, subject: caption);
      return;
    }
    snack.success(
      'Saved to $where',
      duration: const Duration(seconds: 6),
      action: SnackBarAction(
        label: 'WhatsApp',
        onPressed: () => unawaited(PdfExport.whatsApp(bytes, fileName, caption: caption)),
      ),
    );
  }

  /// Letterhead from the web print settings (company name and logo, each only
  /// when the tenant prints it), the branch the app is operating as, and who
  /// is signed in. The accent follows the selected theme preset.
  ReportPdfBrand _pdfBrand() {
    final print = context.read<PrintSettingsCubit>().snapshot;
    return ReportPdfBrand(
      companyName: print.companyName,
      branchName: context.read<BranchCubit>().selected?.name ?? '',
      preparedBy: context.read<AuthCubit>().user?.name ?? '',
      logo: print.logo,
      accent: PdfColor.fromInt(context.astra.primary.toARGB32()),
    );
  }

  /// Full-screen page preview. PdfPreview brings Print and Share; WhatsApp and
  /// Download are added beside them so every route out is one tap from here.
  void _openPreview(ReportExport data, Uint8List bytes, String caption) {
    final p = context.astra;
    final paper = p.isDark ? const Color(0xFF26282D) : const Color(0xFFE8EBF0);
    Navigator.of(context, rootNavigator: true).push(MaterialPageRoute<void>(
      fullscreenDialog: true,
      builder: (_) => Scaffold(
        backgroundColor: paper,
        appBar: AppBar(
          title: Text(data.kind.title),
          backgroundColor: p.primary,
          foregroundColor: Colors.white,
        ),
        body: PdfPreview(
          build: (_) => bytes,
          useActions: true,
          canChangePageFormat: false,
          canChangeOrientation: false,
          canDebug: false,
          pdfFileName: data.fileName,
          // A4 blown up to a landscape tablet's full width is soft and absurd.
          maxPageWidth: 820,
          scrollViewDecoration: BoxDecoration(color: paper),
          actions: [
            PdfPreviewAction(
              icon: const Icon(Icons.chat_rounded),
              onPressed: (_, build, format) async =>
                  PdfExport.whatsApp(await build(format), data.fileName, caption: caption),
            ),
            PdfPreviewAction(
              icon: const Icon(Icons.download_rounded),
              onPressed: (ctx, build, format) async {
                final snack = AstraSnack.capture(ctx);
                await _download(await build(format), data.fileName, caption, snack);
              },
            ),
          ],
          actionBarTheme: PdfActionBarTheme(backgroundColor: p.primary, iconColor: Colors.white),
        ),
      ),
    ));
  }
}

/// Holds the screen while the report is pulled and laid out.
class _ExportProgress extends StatelessWidget {
  const _ExportProgress();

  @override
  Widget build(BuildContext context) {
    final p = context.astra;
    return PopScope(
      canPop: false,
      child: Center(
        child: Material(
          color: Colors.transparent,
          child: Container(
            width: 230,
            padding: const EdgeInsets.fromLTRB(20, 22, 20, 18),
            decoration: BoxDecoration(
              color: p.card,
              borderRadius: BorderRadius.circular(20),
              boxShadow: context.astraTheme.softShadow,
            ),
            child: Column(
              mainAxisSize: MainAxisSize.min,
              children: [
                SizedBox(
                  width: 26,
                  height: 26,
                  child: CircularProgressIndicator(strokeWidth: 2.6, color: p.primary),
                ),
                const SizedBox(height: 14),
                Text('Preparing PDF…', style: ui(size: 14, weight: FontWeight.w800, color: p.ink)),
                const SizedBox(height: 3),
                Text('Pulling every line for this range',
                    textAlign: TextAlign.center,
                    style: ui(size: 11, weight: FontWeight.w500, color: p.textMuted)),
              ],
            ),
          ),
        ),
      ),
    );
  }
}
