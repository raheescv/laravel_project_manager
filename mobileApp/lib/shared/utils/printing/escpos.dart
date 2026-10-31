import 'dart:async';
import 'dart:typed_data';
import 'dart:ui' as ui;

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
  final dpi = _dpiFor(width);

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

/// The dpi that maps the roll's full page width onto exactly the head's
/// printable dots, so the receipt fills the paper and never clips.
double _dpiFor(PaperWidth width) => dotsForWidth(width) / (_pageMm(width) / 25.4);

/// True when the RGBA pixel at byte offset [i] should burn a dot. Anything
/// see-through is paper — the PDF page has no background.
bool _isInk(Uint8List rgba, int i) {
  if (rgba[i + 3] < 128) return false;
  final lum = (rgba[i] * 299 + rgba[i + 1] * 587 + rgba[i + 2] * 114) ~/ 1000;
  return lum <= _blackAt;
}

/// Renders [pdf] to a pure black-and-white PNG at the head's dot width, for
/// terminals whose printer service takes an image instead of ESC/POS (iMin).
/// Thresholded here, with the same cut-off as the ESC/POS path, so the
/// service never has to grey-scale or dither the slip itself.
Future<Uint8List> pngFromPdf(Uint8List pdf, PaperWidth width) async {
  final dots = dotsForWidth(width);
  final pages = <({Uint8List pixels, int width, int height})>[];
  await for (final page in Printing.raster(pdf, dpi: _dpiFor(width))) {
    pages.add((pixels: page.pixels, width: page.width, height: page.height));
  }
  final height = pages.fold<int>(0, (h, p) => h + p.height);
  final mono = thresholdRgba(pages, dots, height);
  final completer = Completer<ui.Image>();
  ui.decodeImageFromPixels(mono, dots, height, ui.PixelFormat.rgba8888, completer.complete);
  final image = await completer.future;
  final png = await image.toByteData(format: ui.ImageByteFormat.png);
  image.dispose();
  return png!.buffer.asUint8List();
}

/// Stacks [pages] into one opaque [dots]-wide RGBA buffer of pure black and
/// white. Rows wider than [dots] are clipped, narrower ones padded with paper.
Uint8List thresholdRgba(
    List<({Uint8List pixels, int width, int height})> pages, int dots, int height) {
  final out = Uint8List(dots * height * 4)..fillRange(0, dots * height * 4, 0xFF);
  var row = 0;
  for (final page in pages) {
    final limit = page.width < dots ? page.width : dots;
    for (var y = 0; y < page.height; y++, row++) {
      for (var x = 0; x < limit; x++) {
        if (!_isInk(page.pixels, (y * page.width + x) * 4)) continue;
        final o = (row * dots + x) * 4;
        out[o] = 0;
        out[o + 1] = 0;
        out[o + 2] = 0;
      }
    }
  }
  return out;
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
        if (_isInk(rgba, (srcRow + x) * 4)) data[dstRow + (x >> 3)] |= 0x80 >> (x & 7);
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
