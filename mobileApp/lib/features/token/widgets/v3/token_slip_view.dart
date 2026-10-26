import 'dart:typed_data';

import 'package:flutter/material.dart';
import 'package:intl/intl.dart' show DateFormat;

import 'package:invo/features/token/domain/models/token_models.dart';
import 'package:invo/shared/domain/models/print_settings.dart';
import 'package:invo/shared/utils/components/theme/index.dart';

/// Labels printed on every slip. Kept beside the layout so the screen and
/// [buildTokenPdf] can't drift apart.
const String kTokenLabelEn = 'TOKEN';
const String kTokenLabelAr = 'رقم الدور';

/// The token slip drawn on screen ("Paper First"): black on white at the
/// paired roll's proportions, laid out exactly like `buildTokenPdf`, so the
/// cashier sees what prints. [ghost] fades it for the "next number" preview
/// shown before the first token of the day.
class TokenSlipView extends StatelessWidget {
  const TokenSlipView({
    super.key,
    required this.displayNo,
    required this.settings,
    required this.paper,
    this.counterName = '',
    this.issuedAt,
    this.logo,
    this.companyName = '',
    this.ghost = false,
  });

  final String displayNo;
  final TokenSettings settings;
  final PaperWidth paper;
  final String counterName;
  final DateTime? issuedAt;
  final Uint8List? logo;
  final String companyName;
  final bool ghost;

  static const Color _ink = Colors.black;

  @override
  Widget build(BuildContext context) {
    final narrow = paper == PaperWidth.mm58;
    final width = narrow ? 210.0 : 280.0;
    final k = narrow ? 0.84 : 1.0;
    final at = issuedAt ?? DateTime.now();
    final brand = settings.showLogo ? _brand(k) : null;
    final footerEn = settings.footerEnglish.trim();
    final footerAr = settings.footerArabic.trim();
    final header = settings.header.trim();

    final paperBody = Container(
      width: width,
      color: Colors.white,
      padding: EdgeInsets.fromLTRB(14 * k, 16 * k, 14 * k, 12 * k),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.stretch,
        mainAxisSize: MainAxisSize.min,
        children: [
          if (brand != null || settings.showDateTime) ...[
            Row(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Expanded(child: Align(alignment: Alignment.centerLeft, child: brand ?? const SizedBox())),
                if (settings.showDateTime)
                  Text('${DateFormat('dd MMM yyyy').format(at)}\n${DateFormat('hh:mm a').format(at)}',
                      textAlign: TextAlign.right,
                      style: ui(size: 9.5 * k, weight: FontWeight.w500, color: _ink, height: 1.45)),
              ],
            ),
            SizedBox(height: 8 * k),
          ],
          Container(height: 2, color: _ink),
          if (header.isNotEmpty)
            Padding(
              padding: EdgeInsets.only(top: 8 * k),
              child: Text(header,
                  textAlign: TextAlign.center,
                  style: ui(size: 10 * k, weight: FontWeight.w500, color: _ink, height: 1.35)),
            ),
          SizedBox(height: 9 * k),
          Row(
            children: [
              Text(kTokenLabelEn,
                  style: ui(size: 10.5 * k, weight: FontWeight.w800, color: _ink, letterSpacing: 2.6 * k)),
              const Spacer(),
              Text(kTokenLabelAr,
                  textDirection: TextDirection.rtl,
                  style: ui(size: 11 * k, weight: FontWeight.w700, color: _ink)),
            ],
          ),
          SizedBox(height: 4 * k),
          FittedBox(
            fit: BoxFit.scaleDown,
            child: Text(
              displayNo,
              maxLines: 1,
              style: ui(
                size: (narrow ? 54 : 68) * settings.numberSize.scale,
                weight: FontWeight.w800,
                color: _ink,
                height: 1.08,
                letterSpacing: -1,
              ).copyWith(fontFeatures: const [FontFeature.tabularFigures()]),
            ),
          ),
          if (counterName.trim().isNotEmpty)
            Text(counterName.trim().toUpperCase(),
                textAlign: TextAlign.center,
                style: ui(size: 11.5 * k, weight: FontWeight.w800, color: _ink, letterSpacing: 0.8)),
          if (footerEn.isNotEmpty || footerAr.isNotEmpty) ...[
            SizedBox(height: 10 * k),
            const _DashedRule(),
            SizedBox(height: 7 * k),
            if (footerEn.isNotEmpty)
              Text(footerEn,
                  textAlign: TextAlign.center,
                  style: ui(size: 9.5 * k, weight: FontWeight.w500, color: _ink, height: 1.4)),
            if (footerAr.isNotEmpty)
              Text(footerAr,
                  textAlign: TextAlign.center,
                  textDirection: TextDirection.rtl,
                  style: ui(size: 10 * k, weight: FontWeight.w500, color: _ink, height: 1.4)),
          ],
        ],
      ),
    );

    return Opacity(
      opacity: ghost ? 0.42 : 1,
      child: Column(
        mainAxisSize: MainAxisSize.min,
        children: [
          paperBody,
          CustomPaint(size: Size(width, 8), painter: const _TornEdgePainter()),
        ],
      ),
    );
  }

  /// The logo, or the company name when there is no logo (or it won't decode).
  Widget? _brand(double k) {
    final name = companyName.trim();
    final nameText = name.isEmpty
        ? null
        : Text(name,
            maxLines: 2,
            overflow: TextOverflow.ellipsis,
            style: ui(size: 14 * k, weight: FontWeight.w800, color: _ink, letterSpacing: 1.1));
    if (logo != null && logo!.isNotEmpty) {
      return Image.memory(logo!,
          height: 26 * k,
          fit: BoxFit.contain,
          alignment: Alignment.centerLeft,
          errorBuilder: (_, __, ___) => nameText ?? const SizedBox());
    }
    return nameText;
  }
}

class _DashedRule extends StatelessWidget {
  const _DashedRule();

  @override
  Widget build(BuildContext context) =>
      const CustomPaint(size: Size(double.infinity, 1.5), painter: _DashPainter());
}

class _DashPainter extends CustomPainter {
  const _DashPainter();

  @override
  void paint(Canvas canvas, Size size) {
    final paint = Paint()
      ..color = Colors.black
      ..strokeWidth = size.height;
    for (double x = 0; x < size.width; x += 7) {
      canvas.drawLine(Offset(x, 0), Offset((x + 4).clamp(0, size.width), 0), paint);
    }
  }

  @override
  bool shouldRepaint(covariant CustomPainter oldDelegate) => false;
}

/// The serrated tear-off edge under the paper.
class _TornEdgePainter extends CustomPainter {
  const _TornEdgePainter();

  @override
  void paint(Canvas canvas, Size size) {
    const tooth = 10.0;
    final path = Path()..moveTo(0, 0);
    for (double x = 0; x < size.width; x += tooth) {
      path
        ..lineTo(x + tooth / 2, size.height)
        ..lineTo(x + tooth, 0);
    }
    path.close();
    canvas.drawPath(path, Paint()..color = Colors.white);
  }

  @override
  bool shouldRepaint(covariant CustomPainter oldDelegate) => false;
}
