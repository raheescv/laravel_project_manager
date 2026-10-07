// Photos are cropped in CropImageScreen before upload.
// - Avatar (circle): pops the square crop; the profile layer uploads those bytes.
// - Checklist (free): the whole photo is pre-selected; dragging a corner crops
//   it, leaving it alone hands back the original (no lossy re-encode).
// Cancel pops nothing in both.
import 'dart:io';
import 'dart:typed_data';
import 'dart:ui' as ui;

import 'package:crop_your_image/crop_your_image.dart';
import 'package:flutter/material.dart';
import 'package:flutter_test/flutter_test.dart';

import 'package:invo/features/checklist/widgets/v3/photo_source_sheet.dart';
import 'package:invo/features/profile/logic/profile_cubit/profile_cubit.dart';
import 'package:invo/shared/domain/constants/global_variables.dart';
import 'package:invo/shared/widgets/crop_image_screen.dart';

import 'support/harness.dart';

/// A real PNG, 120×80 by default (wider than tall, so a square crop has work to do).
Future<Uint8List> _pngBytes({int width = 120, int height = 80}) async {
  final recorder = ui.PictureRecorder();
  ui.Canvas(recorder).drawRect(Rect.fromLTWH(0, 0, width.toDouble(), height.toDouble()), Paint()..color = const Color(0xFF0FA968));
  final image = await recorder.endRecording().toImage(width, height);
  final data = await image.toByteData(format: ui.ImageByteFormat.png);
  return data!.buffer.asUint8List();
}

/// A page that opens the cropper and keeps whatever it pops.
class _Host extends StatefulWidget {
  const _Host(this.bytes, {this.circle = false});
  final Uint8List bytes;
  final bool circle;

  @override
  State<_Host> createState() => _HostState();
}

class _HostState extends State<_Host> {
  Uint8List? result;
  bool returned = false;

  @override
  Widget build(BuildContext context) => Scaffold(
        body: Center(
          child: TextButton(
            onPressed: () async {
              final r = await cropImage(context, widget.bytes, circle: widget.circle);
              setState(() {
                result = r;
                returned = true;
              });
            },
            child: const Text('Open cropper'),
          ),
        ),
      );
}

/// The cropper decodes and encodes on a background isolate — let real time pass.
Future<void> _waitForIsolate(WidgetTester tester) async {
  for (var i = 0; i < 20; i++) {
    await tester.runAsync(() => Future<void>.delayed(const Duration(milliseconds: 100)));
    await tester.pump(const Duration(milliseconds: 50));
  }
}

Future<_HostState> _openCropper(
  WidgetTester tester, {
  bool circle = false,
  Size size = phoneSize,
  Size image = const Size(120, 80),
}) async {
  await setUpEnv();
  final png = (await tester.runAsync(() => _pngBytes(width: image.width.toInt(), height: image.height.toInt())))!;
  await pumpScreen(tester, _Host(png, circle: circle), size: size);
  await tester.tap(find.text('Open cropper'));
  await settle(tester, frames: 6);
  await _waitForIsolate(tester);
  expectNoErrors(tester);
  expect(find.text('Crop Photo'), findsOneWidget);
  return tester.state<_HostState>(find.byType(_Host, skipOffstage: false));
}

Future<ui.Image> _decode(WidgetTester tester, Uint8List bytes) async => (await tester.runAsync(() async {
      final codec = await ui.instantiateImageCodec(bytes);
      return (await codec.getNextFrame()).image;
    }))!;

void main() {
  setUpAll(serveGoogleFontsFromBundle);

  group('avatar (circle)', () {
    for (final (label, size) in [('phone', phoneSize), ('tablet', tabletSize)]) {
      testWidgets('pops square image bytes to the caller ($label)', (tester) async {
        final host = await _openCropper(tester, circle: true, size: size);
        expect(find.text('Pinch to zoom · drag to reposition'), findsOneWidget);

        await tester.tap(find.text('Use Photo'));
        await _waitForIsolate(tester);
        await settle(tester, frames: 6);

        expect(host.returned, isTrue);
        final decoded = await _decode(tester, host.result!);
        expect(decoded.width, decoded.height);
        expect(host.result!.sublist(1, 4), 'PNG'.codeUnits); // PNG in, PNG out
        expectNoErrors(tester);
      });
    }

    testWidgets('Cancel returns nothing', (tester) async {
      final host = await _openCropper(tester, circle: true);
      await tester.tap(find.text('Cancel'));
      await settle(tester, frames: 6);

      expect(host.returned, isTrue);
      expect(host.result, isNull);
      expect(find.text('Crop Photo'), findsNothing);
    });
  });

  group('checklist (free crop)', () {
    for (final (label, size) in [('phone', phoneSize), ('tablet', tabletSize)]) {
      testWidgets('dragging a corner crops the photo ($label)', (tester) async {
        final host = await _openCropper(tester, size: size);
        expect(find.text('Drag the corners to crop'), findsOneWidget);
        expect(find.byType(DotControl), findsNWidgets(4));

        // Pull one corner a good way toward the middle of the photo.
        final crop = tester.getRect(find.byType(Crop));
        final dot = find.byType(DotControl).first;
        final from = tester.getCenter(dot);
        await tester.drag(dot, (crop.center - from) * 0.5);
        await tester.pump();

        await tester.tap(find.text('Use Photo'));
        await _waitForIsolate(tester);
        await settle(tester, frames: 6);

        expect(host.returned, isTrue);
        final decoded = await _decode(tester, host.result!);
        expect(decoded.width, lessThan(120));
        expect(decoded.height, lessThan(80));
        expectNoErrors(tester);
      });
    }

    testWidgets('a crop is cut from the full-resolution photo, not the screen', (tester) async {
      // 1600×1200 shown on a 390pt-wide phone: a screen-resolution crop would be
      // under 390px wide.
      final host = await _openCropper(tester, image: const Size(1600, 1200));
      final crop = tester.getRect(find.byType(Crop));
      final dot = find.byType(DotControl).first;
      await tester.drag(dot, (crop.center - tester.getCenter(dot)) * 0.4);
      await tester.pump();

      await tester.tap(find.text('Use Photo'));
      await _waitForIsolate(tester);
      await settle(tester, frames: 6);

      final decoded = await _decode(tester, host.result!);
      expect(decoded.width, greaterThan(800));
      expect(decoded.width, lessThan(1600));
      expect(decoded.height, greaterThan(600));
    });

    testWidgets('an untouched crop hands back the original photo', (tester) async {
      final host = await _openCropper(tester);
      final original = (tester.widget(find.byType(CropImageScreen)) as CropImageScreen).imageBytes;

      await tester.tap(find.text('Use Photo'));
      await settle(tester, frames: 6);

      expect(host.result, same(original));
    });

    testWidgets('Cancel returns nothing', (tester) async {
      final host = await _openCropper(tester);
      await tester.tap(find.text('Cancel'));
      await settle(tester, frames: 6);

      expect(host.returned, isTrue);
      expect(host.result, isNull);
    });
  });

  group('stripJpegMetadata', () {
    // SOI · APP0 (JFIF) · APP1 (EXIF, orientation) · DQT · SOS + scan data · EOI
    final app0 = [0xFF, 0xE0, 0x00, 0x06, 0x4A, 0x46, 0x49, 0x46];
    final app1 = [0xFF, 0xE1, 0x00, 0x08, 0x45, 0x78, 0x69, 0x66, 0x00, 0x00];
    final dqt = [0xFF, 0xDB, 0x00, 0x04, 0x01, 0x02];
    final scan = [0xFF, 0xDA, 0x00, 0x04, 0x09, 0x09, 0x7A, 0xFF, 0xE1, 0x55, 0xFF, 0xD9];

    test('drops the EXIF block and keeps everything else byte for byte', () {
      final jpeg = Uint8List.fromList([0xFF, 0xD8, ...app0, ...app1, ...dqt, ...scan]);
      expect(stripJpegMetadata(jpeg), [0xFF, 0xD8, ...app0, ...dqt, ...scan]);
    });

    test('leaves a PNG, a JPEG without EXIF and a malformed JPEG alone', () {
      final png = Uint8List.fromList([0x89, 0x50, 0x4E, 0x47, 0x0D, 0x0A]);
      final plain = Uint8List.fromList([0xFF, 0xD8, ...app0, ...dqt, ...scan]);
      final truncated = Uint8List.fromList([0xFF, 0xD8, 0xFF, 0xE1, 0x7F, 0xFF, 0x00]);
      expect(stripJpegMetadata(png), same(png));
      expect(stripJpegMetadata(plain), plain);
      expect(stripJpegMetadata(truncated), same(truncated));
    });
  });

  test('a cropped checklist photo is saved with the extension of its format', () async {
    final jpeg = await writeCroppedPhoto(Uint8List.fromList([0xFF, 0xD8, 0xFF, 0xE0, 1]));
    final png = await writeCroppedPhoto(Uint8List.fromList([0x89, 0x50, 0x4E, 0x47, 1]));
    addTearDown(() {
      for (final f in [jpeg, png]) {
        if (f.existsSync()) f.deleteSync();
      }
    });

    expect(jpeg.path, endsWith('.jpg'));
    expect(png.path, endsWith('.png'));
    expect(File(jpeg.path).readAsBytesSync(), [0xFF, 0xD8, 0xFF, 0xE0, 1]);
  });

  test('the profile cubit uploads the cropped bytes', () async {
    final env = await setUpEnv();
    final cubit = serviceLocator<ProfileCubit>();
    final bytes = Uint8List.fromList([0xFF, 0xD8, 0xFF, 0xE0, 1, 2, 3]);

    final user = await cubit.updatePhoto(bytes);

    expect(user, isNotNull);
    expect(env.profile.calls, ['updatePhoto']);
    expect(env.profile.uploadedPhoto, same(bytes));
    await cubit.close();
  });
}
