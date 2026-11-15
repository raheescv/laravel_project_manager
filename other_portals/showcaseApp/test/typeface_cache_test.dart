import 'dart:async';

import 'package:flutter/material.dart';
import 'package:flutter_test/flutter_test.dart';
import 'package:google_fonts/google_fonts.dart';
import 'package:showcase/shared/utils/components/theme/type_presets.dart';

/// Every label in the app is set through [TypeFace.style], and it no longer
/// reaches `GoogleFonts` to answer — it asks once per face and weight and builds
/// the style from what came back. That is worth doing because reaching
/// GoogleFonts rebuilds the family's whole variant table on every call, and this
/// is called once per `Text` per build.
///
/// It is only worth doing if the style is the same one. A face that quietly
/// resolves to a different variant, or drops its fallback, is a panel set in the
/// wrong typeface with nothing on screen to say so.
void main() {
  /// google_fonts cannot fetch in a test: the load it starts rejects and would
  /// fail whatever test is running when it lands. What it *returns* is
  /// synchronous and is the whole of what is under test here.
  T withoutFetching<T>(T Function() body) {
    late T out;
    runZonedGuarded(() => out = body(), (_, __) {});
    return out;
  }

  /// The same style, asked for the way the rest of the app asks for it.
  TextStyle cached(TypeFace face, FontWeight weight) => withoutFetching(
        () => face.style(
            size: 12, weight: weight, letterSpacing: 1.5, height: 1.3),
      );

  /// And asked for the way it used to be.
  TextStyle direct(TypeFace face, FontWeight weight) =>
      withoutFetching(() => switch (face) {
            TypeFace.jost => GoogleFonts.jost(
                fontSize: 12,
                fontWeight: weight,
                letterSpacing: 1.5,
                height: 1.3),
            TypeFace.fraunces => GoogleFonts.fraunces(
                fontSize: 12,
                fontWeight: weight,
                letterSpacing: 1.5,
                height: 1.3),
            TypeFace.hanken => GoogleFonts.hankenGrotesk(
                fontSize: 12,
                fontWeight: weight,
                letterSpacing: 1.5,
                height: 1.3),
            // One weight, deliberately not asked for — see [TypeFace].
            TypeFace.instrument => GoogleFonts.instrumentSerif(
                fontSize: 12, letterSpacing: 1.5, height: 1.3),
            TypeFace.manrope => GoogleFonts.manrope(
                fontSize: 12,
                fontWeight: weight,
                letterSpacing: 1.5,
                height: 1.3),
            TypeFace.inter => GoogleFonts.interTight(
                fontSize: 12,
                fontWeight: weight,
                letterSpacing: 1.5,
                height: 1.3),
            TypeFace.plexArabic => GoogleFonts.ibmPlexSansArabic(
                fontSize: 12,
                fontWeight: weight,
                letterSpacing: 1.5,
                height: 1.3),
          });

  for (final face in TypeFace.values) {
    test('$face is set in the same face GoogleFonts would have set it in', () {
      for (final weight in FontWeight.values) {
        final was = direct(face, weight);
        final now = cached(face, weight);
        expect(now.fontFamily, was.fontFamily, reason: 'variant at $weight');
        expect(now.fontFamilyFallback, was.fontFamilyFallback,
            reason: 'fallback at $weight');
        expect(now.fontWeight, was.fontWeight, reason: 'weight at $weight');
        expect(now.fontSize, was.fontSize);
        expect(now.letterSpacing, was.letterSpacing);
        expect(now.height, was.height);
      }
    });
  }

  test('a weight is resolved once and the answer is reused', () {
    final first = cached(TypeFace.jost, FontWeight.w500);
    final second = cached(TypeFace.jost, FontWeight.w500);
    // Identical, not merely equal: the fallback list is the one object the
    // resolution kept, handed to every style built from it.
    expect(identical(first.fontFamilyFallback, second.fontFamilyFallback), isTrue);
  });

  test('forgetting the answers asks for them again', () {
    // Which is what re-arms the download for a panel that was switched on
    // before its network came up. Nothing else would ever ask a second time.
    final before = cached(TypeFace.jost, FontWeight.w500);
    TypeFace.forgetResolved();
    final after = cached(TypeFace.jost, FontWeight.w500);
    expect(after.fontFamily, before.fontFamily, reason: 'same face either way');
    expect(identical(after.fontFamilyFallback, before.fontFamilyFallback), isFalse,
        reason: 'resolved afresh, so the fetch was started again');
  });
}
