// Runs before every test file. Loads a real proportional font under every
// family the app asks google_fonts for, so layout tests measure text close to
// how a device does. Without it every glyph is the 1em-square test font, text
// is ~1.8x wider than Manrope, and overflow checks report rows that never
// overflow on a device.
//
// The face is the repo's bundled IBM Plex Sans Arabic (its Latin glyphs are IBM
// Plex Sans — close to Manrope in width; regular below w600, bold from w600).
import 'dart:async';
import 'dart:io';

import 'package:flutter/services.dart';
import 'package:google_fonts/google_fonts.dart';

const _families = ['Manrope', 'Marcellus', 'Inter', 'IBM Plex Sans', 'Hanken Grotesk', 'Fraunces', 'Roboto'];
const _weights = ['regular', '100', '200', '300', '500', '600', '700', '800', '900'];

Future<void> testExecutable(FutureOr<void> Function() testMain) async {
  GoogleFonts.config.allowRuntimeFetching = false;
  final regular = File('assets/fonts/IBMPlexSansArabic-Regular.ttf');
  final bold = File('assets/fonts/IBMPlexSansArabic-Bold.ttf');
  if (regular.existsSync() && bold.existsSync()) {
    final r = ByteData.sublistView(await regular.readAsBytes());
    final b = ByteData.sublistView(await bold.readAsBytes());
    for (final family in _families) {
      final names = [family, for (final w in _weights) '${family}_$w', for (final w in _weights) '${family}_${w}italic'];
      for (final name in names) {
        final heavy = RegExp(r'_(600|700|800|900)').hasMatch(name);
        await (FontLoader(name)..addFont(Future.value(heavy ? b : r))).load();
      }
    }
  }
  await testMain();
}
