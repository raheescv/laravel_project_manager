import 'dart:typed_data';

import 'package:pdf/pdf.dart';
import 'package:printing/printing.dart';

import 'package:invo/shared/domain/models/index.dart';
import 'package:invo/shared/domain/models/printer_target.dart';
import 'package:invo/shared/utils/printing/escpos.dart';
import 'package:invo/shared/utils/printing/printer_link.dart';
import 'package:invo/shared/widgets/receipt_pdf.dart';

/// How a print attempt ended. The caller decides what to say — the sale is
/// already saved by the time we get here, so nothing in this file may throw
/// into the sale flow.
enum ReceiptPrintResult {
  /// Went straight to the paired printer — the cashier tapped nothing.
  printed,

  /// No direct link was available, so the OS print dialog was used instead
  /// and the job was sent from there.
  viaDialog,

  /// The dialog opened and the user backed out of it. Not an error.
  cancelled,

  /// Couldn't print at all (printer unreachable, PDF build failed, …).
  failed;

  bool get ok => this == printed || this == viaDialog;
}

/// True when this platform can drive a printer picked from the OS print
/// subsystem without showing a dialog. iOS/macOS/Windows can; Android's print
/// framework always puts its own sheet in front — which is exactly why the
/// direct ESC/POS transports in [PrinterLink] exist.
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

/// Print [sale]'s receipt at [target].
///
/// A direct target (network / Bluetooth / USB / built-in) gets raw ESC/POS and
/// prints with no UI at all. A system target on a platform that supports it is
/// driven through the OS print subsystem. Everything else falls back to the
/// print dialog so the receipt still reaches paper.
///
/// Never throws: a print failure must not look like a sale failure.
Future<ReceiptPrintResult> printReceipt(
  Sale sale,
  PrintSettings settings, {
  required PrinterTarget target,
}) async {
  Uint8List bytes;
  try {
    bytes = await buildReceiptPdf(sale, settings);
  } catch (_) {
    return ReceiptPrintResult.failed;
  }
  final title = 'Invoice ${sale.invoiceNo.isEmpty ? sale.id : sale.invoiceNo}';
  return _send(bytes, settings, title, target);
}

/// Same path as [printReceipt] but with the Settings test slip — lets a cashier
/// confirm the pairing without ringing a live sale.
Future<ReceiptPrintResult> printTestReceipt(
  PrintSettings settings, {
  required PrinterTarget target,
}) async {
  Uint8List bytes;
  try {
    bytes = await buildTestReceiptPdf(settings);
  } catch (_) {
    return ReceiptPrintResult.failed;
  }
  return _send(bytes, settings, 'Printer test', target);
}

Future<ReceiptPrintResult> _send(
  Uint8List bytes,
  PrintSettings settings,
  String title,
  PrinterTarget target,
) async {
  // 1. Direct ESC/POS — the only genuinely zero-tap path, and the only one
  //    that works silently on Android.
  if (target.isDirect) {
    try {
      final escpos = await escPosFromPdf(bytes, settings.width);
      if (await PrinterLink.send(target, escpos)) return ReceiptPrintResult.printed;
      // Printer off, cable out, out of range — fall through to the dialog so
      // the cashier can still get the slip rather than losing it.
    } catch (_) {
      // Rasterising failed on this device; the dialog can still print the PDF.
    }
  }

  final format = _rollFormat(settings);
  // `dynamicLayout: false` keeps the already-rendered roll exactly as built —
  // the same reason buildReceiptPdf pins its own page format rather than
  // following the OS-reported paper.

  // 2. A printer picked from the OS print subsystem (iOS/macOS/Windows).
  if (target.transport == PrinterTransport.system && target.address.trim().isNotEmpty) {
    try {
      final ok = await Printing.directPrintPdf(
        printer: Printer(url: target.address.trim(), name: target.name),
        onLayout: (_) => bytes,
        name: title,
        format: format,
        dynamicLayout: false,
      );
      if (ok) return ReceiptPrintResult.printed;
    } catch (_) {
      // Same treatment: try the dialog before giving up.
    }
  }

  // 3. Last resort — the platform print sheet.
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
