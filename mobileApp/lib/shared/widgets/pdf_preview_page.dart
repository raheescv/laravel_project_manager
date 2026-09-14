import 'dart:async';

import 'package:flutter/foundation.dart' show Uint8List;
import 'package:flutter/material.dart';
import 'package:printing/printing.dart';

import 'package:invo/shared/utils/components/theme/index.dart';
import 'package:invo/shared/utils/printing/pdf_export.dart';
import 'package:invo/shared/widgets/astra_snack.dart';

// What the app does with a PDF it has exported — the Reports screen's A4 report
// and the day session Sale Bill Report share these, so every document offers
// the same routes out: preview, print, WhatsApp, share, download.

/// Saves [bytes] to Downloads, then offers WhatsApp straight from the
/// confirmation. Where the app can't write a Downloads copy itself (iOS,
/// Android 9 and older) the share sheet is the download — its Save to Files /
/// Save to device does the job.
Future<void> downloadPdf(
  Uint8List bytes,
  String fileName, {
  required String caption,
  required AstraSnackHandle snack,
}) async {
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

/// Full-screen preview. PdfPreview brings Print and Share; WhatsApp and
/// Download sit beside them so every route out is one tap from here.
/// [maxPageWidth] keeps an A4 page from blowing up across a landscape tablet —
/// pass a narrow one for a thermal roll.
void openPdfPreview(
  BuildContext context, {
  required String title,
  required Uint8List bytes,
  required String fileName,
  required String caption,
  double maxPageWidth = 820,
}) {
  final p = context.astra;
  final paper = p.isDark ? const Color(0xFF26282D) : const Color(0xFFE8EBF0);
  Navigator.of(context, rootNavigator: true).push(MaterialPageRoute<void>(
    fullscreenDialog: true,
    builder: (_) => Scaffold(
      backgroundColor: paper,
      appBar: AppBar(
        title: Text(title),
        backgroundColor: p.primary,
        foregroundColor: Colors.white,
      ),
      body: PdfPreview(
        build: (_) => bytes,
        useActions: true,
        canChangePageFormat: false,
        canChangeOrientation: false,
        canDebug: false,
        pdfFileName: fileName,
        maxPageWidth: maxPageWidth,
        scrollViewDecoration: BoxDecoration(color: paper),
        actions: [
          // Only where it differs from the preview's own Share button.
          if (PdfExport.opensWhatsAppDirectly)
            PdfPreviewAction(
              icon: const Icon(Icons.chat_rounded),
              onPressed: (_, build, format) async =>
                  PdfExport.whatsApp(await build(format), fileName, caption: caption),
            ),
          PdfPreviewAction(
            icon: const Icon(Icons.download_rounded),
            onPressed: (ctx, build, format) async {
              final snack = AstraSnack.capture(ctx);
              await downloadPdf(await build(format), fileName, caption: caption, snack: snack);
            },
          ),
        ],
        actionBarTheme: PdfActionBarTheme(backgroundColor: p.primary, iconColor: Colors.white),
      ),
    ),
  ));
}

/// Holds the screen while a document is fetched and laid out. Show it with
/// `showDialog(barrierDismissible: false)` and pop it when the bytes are in.
class PdfProgressCard extends StatelessWidget {
  const PdfProgressCard({super.key, this.title = 'Preparing PDF…', this.message = ''});

  final String title;
  final String message;

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
                Text(title, style: ui(size: 14, weight: FontWeight.w800, color: p.ink)),
                if (message.isNotEmpty) ...[
                  const SizedBox(height: 3),
                  Text(message,
                      textAlign: TextAlign.center,
                      style: ui(size: 11, weight: FontWeight.w500, color: p.textMuted)),
                ],
              ],
            ),
          ),
        ),
      ),
    );
  }
}
