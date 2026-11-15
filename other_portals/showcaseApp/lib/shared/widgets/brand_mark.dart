import 'package:flutter/material.dart';

import '../utils/components/theme/pearl_theme.dart';

/// The Sizerun lockup, from the artwork the business already uses.
///
/// The file is black type inside a blue frame on transparency, so it is given
/// its own light ground rather than the page's: on the dark theme the type
/// would otherwise vanish and the frame would float on its own. A logo with a
/// fixed safe ground is what the brand asset expects, and it means the mark
/// reads identically whichever theme the tablet is in.
class BrandMark extends StatelessWidget {
  const BrandMark({super.key, this.height = 40, this.padding = 3});

  /// Height of the plate, not of the artwork — the logo is inset by [padding].
  final double height;
  final double padding;

  /// The cropped mark's own proportions, so nothing is ever squashed.
  static const double _ratio = 297 / 277;

  /// The framed SIZE RUN mark, cropped out of the full lockup.
  ///
  /// The supplied artwork is 44% transparent margin with the "EST. 2022 QATAR"
  /// tagline underneath, so fitting the whole file to a bar-height box left the
  /// mark itself about a third of the height it had to work with — and the
  /// tagline illegible at any size the chrome can give it. The launcher icon
  /// still uses the full lockup, where there is room for both.
  static const String asset = 'assets/brand/sizerun_mark.png';

  @override
  Widget build(BuildContext context) {
    final art = height - padding * 2;
    return Container(
      height: height,
      width: art * _ratio + padding * 2,
      alignment: Alignment.center,
      color: Colors.white,
      padding: EdgeInsets.all(padding),
      child: Image.asset(
        asset,
        height: art,
        fit: BoxFit.contain,
        filterQuality: FilterQuality.medium,
        // A missing asset must not take the whole chrome down with it.
        errorBuilder: (context, _, __) => _Fallback(height: art),
      ),
    );
  }
}

class _Fallback extends StatelessWidget {
  const _Fallback({required this.height});

  final double height;

  @override
  Widget build(BuildContext context) => Text(
        'SIZERUN',
        style: PearlText.section.copyWith(
          fontSize: height * .42,
          color: const Color(0xFF191A1E),
        ),
      );
}
