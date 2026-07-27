import 'dart:typed_data';

import 'package:pdf/pdf.dart';
import 'package:printing/printing.dart';

import 'package:invo/shared/domain/models/index.dart';
import 'package:invo/shared/widgets/receipt_pdf.dart';

/// How a print attempt ended. The caller decides what to say — the sale is
/// already saved by the time we get here, so nothing in this file may throw
/// into the sale flow.
enum ReceiptPrintResult {
  /// Went straight to the paired printer — the cashier tapped nothing.
  printed,

  /// No pairing was possible on this platform, so the OS print dialog was
  /// used instead and the job was sent from there.
  viaDialog,

  /// The dialog opened and the user backed out of it. Not an error.
  cancelled,

  /// Couldn't print at all (printer unreachable, PDF build failed, …).
  failed;

  bool get ok => this == printed || this == viaDialog;
}

/// True when this platform can print to a chosen printer without showing a
/// dialog. iOS/macOS/Windows can; Android's print framework always puts its
/// own sheet in front, so an Android till falls back to [ReceiptPrintResult.viaDialog].
Future<bool> canDirectPrint() async {
  try {
    final info = await Printing.info();
    return info.directPrint && info.canListPrinters;
  } catch (_) {
    return false;
  }
}

PdfPageFormat _rollFormat(PrintSettings settings) =>
    settings.width == PaperWidth.mm58 ? PdfPageFormat.roll57 : PdfPageFormat.roll80;

/// Print [sale]'s receipt. When [printerUrl] names a paired printer we drive it
/// directly (no dialog, no taps); otherwise we fall back to the platform print
/// dialog so the receipt still reaches paper.
///
/// Never throws: a print failure must not look like a sale failure.
Future<ReceiptPrintResult> printReceipt(
  Sale sale,
  PrintSettings settings, {
  String? printerUrl,
  String? printerName,
}) async {
  Uint8List bytes;
  try {
    bytes = await buildReceiptPdf(sale, settings);
  } catch (_) {
    return ReceiptPrintResult.failed;
  }
  final title = 'Invoice ${sale.invoiceNo.isEmpty ? sale.id : sale.invoiceNo}';
  return _send(bytes, settings, title, printerUrl, printerName);
}

/// Same path as [printReceipt] but with the Settings test slip — lets a cashier
/// confirm the pairing without ringing a live sale.
Future<ReceiptPrintResult> printTestReceipt(
  PrintSettings settings, {
  String? printerUrl,
  String? printerName,
}) async {
  Uint8List bytes;
  try {
    bytes = await buildTestReceiptPdf(settings);
  } catch (_) {
    return ReceiptPrintResult.failed;
  }
  return _send(bytes, settings, 'Printer test', printerUrl, printerName);
}

Future<ReceiptPrintResult> _send(
  Uint8List bytes,
  PrintSettings settings,
  String title,
  String? printerUrl,
  String? printerName,
) async {
  final format = _rollFormat(settings);
  // `dynamicLayout: false` keeps the already-rendered roll exactly as built —
  // the same reason buildReceiptPdf pins its own page format rather than
  // following the OS-reported paper.
  final url = (printerUrl ?? '').trim();
  if (url.isNotEmpty) {
    try {
      final ok = await Printing.directPrintPdf(
        printer: Printer(url: url, name: printerName),
        onLayout: (_) => bytes,
        name: title,
        format: format,
        dynamicLayout: false,
      );
      if (ok) return ReceiptPrintResult.printed;
      // `false` here means the platform refused the job (printer gone, paper
      // out of the queue) — fall through to the dialog so the cashier can pick
      // another printer rather than losing the receipt entirely.
    } catch (_) {
      // Same treatment: try the dialog before giving up.
    }
  }
  try {
    final ok = await Printing.layoutPdf(
      onLayout: (_) => bytes,
      name: title,
      format: format,
      dynamicLayout: false,
    );
    return ok ? ReceiptPrintResult.viaDialog : ReceiptPrintResult.cancelled;
  } catch (_) {
    return ReceiptPrintResult.failed;
  }
}
