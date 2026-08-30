import 'dart:typed_data';

import 'package:printing/printing.dart';

import 'package:invo/shared/domain/models/print_settings.dart';

/// Turns the receipt PDF the app already builds into raw ESC/POS bytes, so it
/// can be pushed straight down a socket / Bluetooth link / USB pipe instead of
/// through the OS print dialog.
///
/// Rasterising rather than re-typesetting the receipt as ESC/POS text is
/// deliberate: `buildReceiptPdf` is the one place the layout lives, and Arabic
/// only shapes correctly through it (a thermal printer's own Arabic code page
/// disconnects the letters — the same trap documented on `buildReceiptPdf`).
/// Whatever prints via the dialog and whatever prints direct are then the
/// identical slip.

/// Printable dot columns per roll. Thermal heads are 8 dots/mm (203 dpi):
/// an 80mm roll prints 72mm of that (576 dots) and a 58mm roll 48mm (384) —
/// the remainder is margin the head cannot reach.
int dotsForWidth(PaperWidth w) => w == PaperWidth.mm58 ? 384 : 576;

/// Physical roll width in mm — matches the `PdfPageFormat.roll57 / roll80`
/// page `buildReceiptPdf` lays the receipt out on.
double _pageMm(PaperWidth w) => w == PaperWidth.mm58 ? 57.0 : 80.0;

/// Luminance at or below which a pixel becomes a burnt dot. Sits above the
/// midpoint on purpose: at 8 dots/mm the small type is heavily anti-aliased,
/// and a 128 threshold thins it to the point of being hard to read.
const int _blackAt = 168;

/// Rows per `GS v 0` block. Cheap printers have small input buffers and drop
/// an oversized image silently, so the page goes down in bands.
const int _bandRows = 128;

/// Renders [pdf] to ESC/POS for a [width] roll: reset, the page as raster
/// bitmap bands, a short feed so the last line clears the tear bar, then a
/// partial cut.
///
/// [cut] is off for printers with no cutter (the feed still clears the head).
Future<Uint8List> escPosFromPdf(
  Uint8List pdf,
  PaperWidth width, {
  bool cut = true,
  int feedLines = 4,
}) async {
  final dots = dotsForWidth(width);
  // Pick the dpi that maps the roll's full page width onto exactly the head's
  // printable dots, so the receipt fills the paper and never clips.
  final dpi = dots / (_pageMm(width) / 25.4);

  final out = BytesBuilder();
  out.add(const [0x1B, 0x40]); // ESC @   — initialise, clear any leftover style
  out.add(const [0x1B, 0x61, 0x00]); // ESC a 0 — left align

  await for (final page in Printing.raster(pdf, dpi: dpi)) {
    out.add(rasterToEscPos(page.pixels, page.width, page.height, dots));
  }

  if (feedLines > 0) out.add([0x1B, 0x64, feedLines & 0xFF]); // ESC d n
  if (cut) out.add(const [0x1D, 0x56, 0x42, 0x00]); // GS V 66 0 — partial cut
  return out.toBytes();
}

/// Packs an RGBA raster into `GS v 0` raster-bitmap bands, thresholding to
/// 1 bit per dot. Rows wider than [dots] are clipped rather than scaled — the
/// dpi above is chosen so that never happens in practice.
Uint8List rasterToEscPos(Uint8List rgba, int srcW, int srcH, int dots) {
  final bytesPerRow = (dots + 7) >> 3;
  final limit = srcW < dots ? srcW : dots;
  final out = BytesBuilder();

  for (var y0 = 0; y0 < srcH; y0 += _bandRows) {
    final rows = (srcH - y0) < _bandRows ? srcH - y0 : _bandRows;
    final data = Uint8List(bytesPerRow * rows);
    for (var y = 0; y < rows; y++) {
      final srcRow = (y0 + y) * srcW;
      final dstRow = y * bytesPerRow;
      for (var x = 0; x < limit; x++) {
        final i = (srcRow + x) * 4;
        // Anything see-through is paper — the PDF page has no background.
        if (rgba[i + 3] < 128) continue;
        final lum = (rgba[i] * 299 + rgba[i + 1] * 587 + rgba[i + 2] * 114) ~/ 1000;
        if (lum <= _blackAt) data[dstRow + (x >> 3)] |= 0x80 >> (x & 7);
      }
    }
    out.add([
      0x1D, 0x76, 0x30, 0x00, // GS v 0, mode 0 (normal density)
      bytesPerRow & 0xFF, (bytesPerRow >> 8) & 0xFF,
      rows & 0xFF, (rows >> 8) & 0xFF,
    ]);
    out.add(data);
  }
  return out.toBytes();
}
