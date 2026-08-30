import 'dart:typed_data';

import 'package:flutter_test/flutter_test.dart';
import 'package:invo/shared/domain/models/print_settings.dart';
import 'package:invo/shared/domain/models/printer_target.dart';
import 'package:invo/shared/utils/printing/escpos.dart';

/// Builds an RGBA raster where [black] holds the (x, y) of every burnt dot.
Uint8List _raster(int w, int h, Set<(int, int)> black) {
  final px = Uint8List(w * h * 4);
  for (var y = 0; y < h; y++) {
    for (var x = 0; x < w; x++) {
      final i = (y * w + x) * 4;
      final on = black.contains((x, y));
      px[i] = px[i + 1] = px[i + 2] = on ? 0 : 255;
      px[i + 3] = 255;
    }
  }
  return px;
}

void main() {
  group('rasterToEscPos', () {
    test('emits a GS v 0 header carrying the row width and row count', () {
      final out = rasterToEscPos(_raster(8, 3, {}), 8, 3, 8);
      expect(out.sublist(0, 4), [0x1D, 0x76, 0x30, 0x00]);
      expect(out[4], 1); // bytes per row, low byte  (8 dots → 1 byte)
      expect(out[5], 0); // …high byte
      expect(out[6], 3); // rows, low byte
      expect(out[7], 0); // …high byte
      expect(out.length, 8 + 3);
    });

    test('packs dots MSB-first within each byte', () {
      // Dots 0 and 7 of the first row: 1000_0001.
      final out = rasterToEscPos(_raster(8, 1, {(0, 0), (7, 0)}), 8, 1, 8);
      expect(out[8], 0x81);
    });

    test('leaves white and transparent pixels as paper', () {
      final px = _raster(8, 1, {(1, 0), (2, 0)});
      px[(1 * 4) + 3] = 0; // dot 1 fully transparent — the page has no ground
      final out = rasterToEscPos(px, 8, 1, 8);
      expect(out[8], 0x20); // only dot 2 burns
    });

    test('pads a short raster out to the head width', () {
      // 8 source columns on a 384-dot head still emits 48 bytes per row.
      final out = rasterToEscPos(_raster(8, 1, {(0, 0)}), 8, 1, 384);
      expect(out[4], 48);
      expect(out.length, 8 + 48);
      expect(out[8], 0x80);
      expect(out.sublist(9), everyElement(0));
    });

    test('clips columns the printhead cannot reach', () {
      // A dot at x=8 is off the edge of an 8-dot head and must not wrap onto
      // the next row's first byte.
      final out = rasterToEscPos(_raster(16, 1, {(8, 0)}), 16, 1, 8);
      expect(out.length, 8 + 1);
      expect(out[8], 0);
    });

    test('splits a tall page into 128-row bands', () {
      final out = rasterToEscPos(_raster(8, 200, {}), 8, 200, 8);
      // band 1: header + 128 rows, band 2: header + 72 rows
      expect(out.length, (8 + 128) + (8 + 72));
      expect(out[6], 128);
      expect(out[8 + 128 + 6], 72);
    });
  });

  group('dotsForWidth', () {
    test('matches the printable width of each roll at 203 dpi', () {
      expect(dotsForWidth(PaperWidth.mm80), 576);
      expect(dotsForWidth(PaperWidth.mm58), 384);
    });
  });

  group('PrinterTarget', () {
    test('defaults a network address with no port to 9100', () {
      const t = PrinterTarget(
          transport: PrinterTransport.network, address: '192.168.1.50', name: 'Till');
      expect(t.host, '192.168.1.50');
      expect(t.port, 9100);
    });

    test('honours an explicit port', () {
      const t = PrinterTarget(
          transport: PrinterTransport.network, address: '192.168.1.50:9101', name: '');
      expect(t.port, 9101);
    });

    test('falls back to 9100 when the port is not a number', () {
      const t = PrinterTarget(
          transport: PrinterTransport.network, address: '192.168.1.50:abc', name: '');
      expect(t.port, 9100);
    });

    test('the built-in printer is paired without an address', () {
      const t = PrinterTarget(
          transport: PrinterTransport.builtin, address: '', name: 'Built-in');
      expect(t.isPaired, isTrue);
      expect(t.isDirect, isTrue);
    });

    test('an addressless network target is not paired', () {
      const t =
          PrinterTarget(transport: PrinterTransport.network, address: '', name: 'x');
      expect(t.isPaired, isFalse);
      expect(t.isDirect, isFalse);
    });

    test('a system target never counts as a direct link', () {
      const t = PrinterTarget(
          transport: PrinterTransport.system, address: 'ipp://printer', name: 'Office');
      expect(t.isPaired, isTrue);
      expect(t.isDirect, isFalse);
    });

    test('none is the un-paired state', () {
      expect(PrinterTarget.none.isPaired, isFalse);
      expect(PrinterTarget.none.isDirect, isFalse);
    });

    test('an unknown stored transport key reads back as the dialog', () {
      expect(PrinterTransport.fromKey(null), PrinterTransport.system);
      expect(PrinterTransport.fromKey('bogus'), PrinterTransport.system);
      expect(PrinterTransport.fromKey('bluetooth'), PrinterTransport.bluetooth);
    });
  });
}
