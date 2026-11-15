import 'dart:io';

import 'package:flutter_test/flutter_test.dart';

void main() {
  test('release builds declare internet permission in the main manifest', () {
    final manifest = File('android/app/src/main/AndroidManifest.xml')
        .readAsStringSync()
        .replaceAll(RegExp(r'<!--[\s\S]*?-->'), '');

    expect(
      RegExp(r'<uses-permission\s+android:name="android.permission.INTERNET"\s*/>')
          .hasMatch(manifest),
      isTrue,
      reason: 'The kiosk release does not include the debug manifest.',
    );
  });
}
