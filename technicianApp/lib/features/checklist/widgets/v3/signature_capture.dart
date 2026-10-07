import 'dart:convert';
import 'dart:ui' as dui;

import 'package:flutter/material.dart';

import 'package:invo/shared/domain/helpers/responsive.dart';
import 'package:invo/shared/utils/components/theme/index.dart';

import 'atelier_parts.dart';

/// What signature capture hands back: the typed name and a
/// `data:image/png;base64,…` PNG of the ink (dark on white — printed on PDFs).
typedef SignatureResult = ({String name, String dataUrl});

/// Opens full-screen signature capture: compact name field on top, the pad
/// filling the rest of the screen, Clear / Save docked at the bottom.
/// [requireName] keeps Save disabled until a name is typed (owner acceptance).
Future<SignatureResult?> showSignatureCapture(
  BuildContext context, {
  required String title,
  String? kicker,
  String nameLabel = 'Signer name',
  String initialName = '',
  bool requireName = false,
}) =>
    Navigator.of(context, rootNavigator: true).push<SignatureResult>(MaterialPageRoute<SignatureResult>(
      // fullscreenDialog also disables the iOS edge swipe, which would
      // otherwise steal strokes that start near the left edge.
      fullscreenDialog: true,
      builder: (_) => SignatureCaptureScreen(
        title: title,
        kicker: kicker,
        nameLabel: nameLabel,
        initialName: initialName,
        requireName: requireName,
      ),
    ));

class SignatureCaptureScreen extends StatefulWidget {
  const SignatureCaptureScreen({
    super.key,
    required this.title,
    this.kicker,
    this.nameLabel = 'Signer name',
    this.initialName = '',
    this.requireName = false,
  });
  final String title;
  final String? kicker;
  final String nameLabel;
  final String initialName;
  final bool requireName;

  @override
  State<SignatureCaptureScreen> createState() => _SignatureCaptureScreenState();
}

class _SignatureCaptureScreenState extends State<SignatureCaptureScreen> {
  late final _nameCtl = TextEditingController(text: widget.initialName);
  final List<List<Offset>> _strokes = [];
  Size _padSize = Size.zero;
  bool _exporting = false;

  @override
  void initState() {
    super.initState();
    _nameCtl.addListener(_onName);
  }

  @override
  void dispose() {
    _nameCtl.removeListener(_onName);
    _nameCtl.dispose();
    super.dispose();
  }

  void _onName() => setState(() {});

  bool get _hasInk => _strokes.any((s) => s.isNotEmpty);
  bool get _canSave => _hasInk && (!widget.requireName || _nameCtl.text.trim().isNotEmpty) && !_exporting;

  Offset _clamp(Offset o) =>
      Offset(o.dx.clamp(0, _padSize.width).toDouble(), o.dy.clamp(0, _padSize.height).toDouble());

  Future<void> _save() async {
    if (!_canSave) return;
    setState(() => _exporting = true);
    final dataUrl = await _exportPng();
    if (!mounted) return;
    Navigator.of(context).pop<SignatureResult>((name: _nameCtl.text.trim(), dataUrl: dataUrl));
  }

  /// Renders the strokes onto a white canvas at 2× and encodes a PNG data URL.
  Future<String> _exportPng() async {
    const scale = 2.0;
    final w = _padSize.width <= 0 ? 320.0 : _padSize.width;
    final h = _padSize.height <= 0 ? 160.0 : _padSize.height;
    final recorder = dui.PictureRecorder();
    final canvas = Canvas(recorder, Rect.fromLTWH(0, 0, w * scale, h * scale));
    canvas.scale(scale);
    canvas.drawRect(Rect.fromLTWH(0, 0, w, h), Paint()..color = ColorManager.white);
    paintSignatureStrokes(canvas, _strokes);
    final picture = recorder.endRecording();
    final image = await picture.toImage((w * scale).round(), (h * scale).round());
    final bytes = await image.toByteData(format: dui.ImageByteFormat.png);
    picture.dispose();
    image.dispose();
    final b64 = base64Encode(bytes?.buffer.asUint8List() ?? const <int>[]);
    return 'data:image/png;base64,$b64';
  }

  @override
  Widget build(BuildContext context) {
    final p = context.astra;
    final tablet = context.isTablet;
    return Scaffold(
      backgroundColor: p.canvas,
      body: SafeArea(
        child: MaxWidthBox(
          maxWidth: tablet ? 860 : 640,
          child: Padding(
            padding: EdgeInsets.fromLTRB(16, 8, 16, tablet ? 20 : 12),
            child: Column(crossAxisAlignment: CrossAxisAlignment.stretch, children: [
              Row(children: [
                AtelierRoundButton(icon: Icons.close, onTap: () => Navigator.of(context).maybePop()),
                const SizedBox(width: 10),
                Expanded(
                  child: Column(crossAxisAlignment: CrossAxisAlignment.start, children: [
                    if (widget.kicker != null) AtelierKicker(widget.kicker!),
                    const SizedBox(height: 2),
                    Text(widget.title,
                        maxLines: 1, overflow: TextOverflow.ellipsis, style: serif(size: tablet ? 24 : 20, color: p.ink)),
                  ]),
                ),
              ]),
              const SizedBox(height: 10),
              Container(
                padding: const EdgeInsets.symmetric(horizontal: 12),
                height: 46,
                decoration: BoxDecoration(
                  color: p.cardSolid,
                  borderRadius: BorderRadius.circular(14),
                  border: Border.all(color: p.hairline),
                ),
                child: Row(children: [
                  Text(widget.nameLabel.toUpperCase(),
                      style: ui(size: 9.5, weight: FontWeight.w800, letterSpacing: 0.8, color: p.textMuted)),
                  const SizedBox(width: 10),
                  Expanded(
                    child: TextField(
                      controller: _nameCtl,
                      textCapitalization: TextCapitalization.words,
                      style: ui(size: 14, weight: FontWeight.w700, color: p.ink),
                      decoration: InputDecoration(
                        isDense: true,
                        border: InputBorder.none,
                        hintText: widget.requireName ? 'Required' : 'Optional',
                        hintStyle: ui(size: 13, weight: FontWeight.w500, color: p.textMuted),
                      ),
                    ),
                  ),
                ]),
              ),
              const SizedBox(height: 10),
              Expanded(
                child: Container(
                  decoration: BoxDecoration(
                    color: ColorManager.white,
                    borderRadius: BorderRadius.circular(20),
                    border: Border.all(color: p.accent.withValues(alpha: 0.6), width: 1.2),
                    boxShadow: context.astraTheme.softShadow,
                  ),
                  child: ClipRRect(
                    borderRadius: BorderRadius.circular(19),
                    child: LayoutBuilder(builder: (context, c) {
                      _padSize = Size(c.maxWidth, c.maxHeight);
                      return Stack(children: [
                        Positioned(
                          left: 24,
                          right: 24,
                          bottom: c.maxHeight * 0.26,
                          child: Container(height: 1, color: p.accent.withValues(alpha: 0.45)),
                        ),
                        Positioned(
                          left: 24,
                          bottom: c.maxHeight * 0.26 - 22,
                          child: Text(_hasInk ? '' : 'Sign above the line',
                              style: ui(size: 12, weight: FontWeight.w700, color: p.textMuted)),
                        ),
                        Positioned(
                          left: 18,
                          bottom: c.maxHeight * 0.26 + 6,
                          child: Text('✕', style: ui(size: 18, weight: FontWeight.w700, color: p.accent.withValues(alpha: 0.6))),
                        ),
                        Positioned.fill(
                          child: GestureDetector(
                            behavior: HitTestBehavior.opaque,
                            onPanStart: (d) => setState(() => _strokes.add([_clamp(d.localPosition)])),
                            onPanUpdate: (d) => setState(() => _strokes.last.add(_clamp(d.localPosition))),
                            child: CustomPaint(
                              painter: _SignaturePainter(List.of(_strokes), _strokes.fold(0, (n, s) => n + s.length)),
                            ),
                          ),
                        ),
                      ]);
                    }),
                  ),
                ),
              ),
              const SizedBox(height: 12),
              Row(children: [
                Expanded(
                  child: AtelierButton(
                    label: 'Clear',
                    kind: AtelierButtonKind.ghost,
                    leadingIcon: Icons.refresh,
                    onTap: _hasInk ? () => setState(_strokes.clear) : null,
                  ),
                ),
                const SizedBox(width: 8),
                Expanded(
                  flex: 2,
                  child: AtelierButton(
                    label: 'Save signature',
                    leadingIcon: Icons.check,
                    busy: _exporting,
                    onTap: _canSave ? _save : null,
                  ),
                ),
              ]),
            ]),
          ),
        ),
      ),
    );
  }
}

/// Draws signature strokes in dark ink (shared by the live pad and the export).
void paintSignatureStrokes(Canvas canvas, List<List<Offset>> strokes) {
  final paint = Paint()
    ..color = ColorManager.black
    ..strokeWidth = 2.6
    ..strokeCap = StrokeCap.round
    ..strokeJoin = StrokeJoin.round
    ..style = PaintingStyle.stroke;
  for (final s in strokes) {
    if (s.isEmpty) continue;
    if (s.length == 1) {
      canvas.drawCircle(s.first, 1.5, Paint()..color = ColorManager.black);
      continue;
    }
    final path = Path()..moveTo(s.first.dx, s.first.dy);
    for (var i = 1; i < s.length; i++) {
      path.lineTo(s[i].dx, s[i].dy);
    }
    canvas.drawPath(path, paint);
  }
}

class _SignaturePainter extends CustomPainter {
  _SignaturePainter(this.strokes, this.pointCount);
  final List<List<Offset>> strokes;
  final int pointCount;

  @override
  void paint(Canvas canvas, Size size) => paintSignatureStrokes(canvas, strokes);

  @override
  bool shouldRepaint(_SignaturePainter old) => old.pointCount != pointCount || old.strokes.length != strokes.length;
}
