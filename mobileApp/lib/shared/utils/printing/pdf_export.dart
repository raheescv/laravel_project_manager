import 'package:flutter/foundation.dart';
import 'package:flutter/services.dart';
import 'package:pdf/pdf.dart';
import 'package:printing/printing.dart';

/// Where an exported PDF can go from the app — the print dialog, the share
/// sheet, WhatsApp, or the device's Downloads folder.
///
/// Print and share are `printing`'s. The WhatsApp hand-off and the Downloads
/// save are Android-only native code (FilesPlugin.kt on the `qloud/files`
/// channel); everywhere else they fall back to the share sheet, where both are
/// one tap away.
class PdfExport {
  const PdfExport._();

  static const _channel = MethodChannel('qloud/files');

  static bool get _android => !kIsWeb && defaultTargetPlatform == TargetPlatform.android;

  /// Whether [whatsApp] opens WhatsApp itself. Only Android can; everywhere
  /// else it is the share sheet, so a separate WhatsApp button would just
  /// repeat Share — screens hide it there.
  static bool get opensWhatsAppDirectly => _android;

  /// The platform print dialog, on A4 as the pages were built. False when the
  /// user backs out of it.
  static Future<bool> printDialog(Uint8List bytes, String name) => Printing.layoutPdf(
        onLayout: (_) => bytes,
        name: name,
        format: PdfPageFormat.a4,
        dynamicLayout: false,
      );

  /// The share sheet with the PDF attached.
  static Future<bool> share(Uint8List bytes, String fileName, {String? subject}) =>
      Printing.sharePdf(bytes: bytes, filename: fileName, subject: subject);

  /// Android opens WhatsApp (or WhatsApp Business) on its chat picker with the
  /// PDF attached. iOS won't let an app address WhatsApp with a document, and a
  /// phone without WhatsApp has nothing to address, so both get the share sheet
  /// instead — WhatsApp sits in it. True only when WhatsApp itself opened.
  static Future<bool> whatsApp(Uint8List bytes, String fileName, {String? caption}) async {
    if (_android) {
      try {
        final opened = await _channel.invokeMethod<bool>(
            'shareToWhatsApp', {'bytes': bytes, 'name': fileName, 'text': caption});
        if (opened == true) return true;
      } on Exception {
        // PlatformException / MissingPluginException — the share sheet below
        // still gets the file out.
      }
    }
    await share(bytes, fileName, subject: caption);
    return false;
  }

  /// Saves a copy to the public Downloads folder and returns where it landed
  /// (`Download/…pdf`). Null where the app can't do that itself — iOS, and
  /// Android 9 or older — so the caller offers the share sheet, whose "Save to
  /// Files" / "Save to device" does the same job.
  static Future<String?> saveToDownloads(Uint8List bytes, String fileName) async {
    if (!_android) return null;
    try {
      return await _channel.invokeMethod<String>(
          'saveToDownloads', {'bytes': bytes, 'name': fileName, 'mime': 'application/pdf'});
    } on Exception {
      return null;
    }
  }
}
