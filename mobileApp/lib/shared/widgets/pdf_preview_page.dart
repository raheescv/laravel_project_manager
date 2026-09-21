import 'dart:async';

import 'package:flutter/foundation.dart' show Uint8List;
import 'package:flutter/material.dart';

import 'package:invo/shared/utils/components/theme/index.dart';
import 'package:invo/shared/utils/printing/pdf_export.dart';
import 'package:invo/shared/widgets/astra_snack.dart';

// Shared by the report preview (ReportPreviewScreen): saving a document it has
// laid out, and the card shown while one is fetched and laid out.

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

/// Holds the screen while a document is fetched and laid out. Show it with
/// `showDialog(barrierDismissible: false)` and pop it when the bytes are in —
/// or inline in a page with [blocking] off.
class PdfProgressCard extends StatelessWidget {
  const PdfProgressCard({super.key, this.title = 'Preparing PDF…', this.message = '', this.blocking = true});

  final String title;
  final String message;

  /// Holds back navigation — right in a dialog, wrong inside a page, where the
  /// user may leave while the document is still being made.
  final bool blocking;

  @override
  Widget build(BuildContext context) {
    final p = context.astra;
    return PopScope(
      canPop: !blocking,
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
