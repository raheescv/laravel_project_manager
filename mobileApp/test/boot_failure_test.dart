import 'package:flutter/material.dart';
import 'package:flutter/services.dart';
import 'package:flutter_test/flutter_test.dart';
import 'package:invo/shared/domain/constants/app_config.dart';
import 'package:invo/shared/utils/friendly_error.dart';
import 'package:invo/shared/widgets/boot_failure_app.dart';

/// The exact failure a till reported: Android's encrypted preferences file
/// outlived the keystore key that opens it, so the first secure read at boot —
/// the auth token — threw, and the whole Java stack landed on the screen a
/// cashier was looking at.
final _badDecrypt = PlatformException(
  code: 'Exception encountered',
  message: 'read',
  details: 'javax.crypto.BadPaddingException: error:1e000065:Cipher '
      'functions:OPENSSL_internal:BAD_DECRYPT\n'
      '\tat com.android.org.conscrypt.NativeCrypto.EVP_CipherFinal_ex(Native Method)',
);

void main() {
  group('FriendlyError', () {
    test('reads an unreadable keystore as a sign-in problem, not a crash', () {
      final reading = FriendlyError.forBoot(_badDecrypt);
      expect(reading.title, 'Saved sign-in could not be opened');
      expect(reading.hint, contains('sign in'));
      // Nothing about the failure leaks into the words the cashier reads.
      expect(reading.message.toLowerCase(), isNot(contains('exception')));
    });

    test('recognises the keystore fault so storage can wipe itself', () {
      expect(FriendlyError.isSecureStoreUnreadable(_badDecrypt), isTrue);
    });

    test('leaves an ordinary failure alone rather than wiping the roster', () {
      // A wipe costs an offline till every account it can authenticate, so only
      // the unrecoverable decrypt fault may trigger one.
      expect(FriendlyError.isSecureStoreUnreadable(StateError('boom')), isFalse);
      expect(
        FriendlyError.isSecureStoreUnreadable(
            PlatformException(code: 'Exception encountered', message: 'read')),
        isFalse,
      );
    });

    test('still has words for a failure it does not recognise', () {
      final reading = FriendlyError.forBoot(StateError('boom'));
      expect(reading.title, 'The app could not finish starting');
      expect(reading.hint, isNotEmpty);
    });

    test('names the real cause for a full disk and an unreachable server', () {
      expect(FriendlyError.forBoot(const FileSystemExceptionStub()).title,
          'The device is out of storage');
      expect(FriendlyError.forBoot('SocketException: Failed host lookup').title,
          'The server could not be reached');
    });
  });

  group('BootFailureApp', () {
    testWidgets('leads with the plain reading and folds the stack away',
        (tester) async {
      await tester.pumpWidget(BootFailureApp(error: _badDecrypt));

      expect(find.text('Saved sign-in could not be opened'), findsOneWidget);
      expect(find.textContaining('BAD_DECRYPT'), findsNothing);

      await tester.tap(find.text('Technical details'));
      await tester.pumpAndSettle();

      // Still there for support — just not the first thing anyone sees.
      expect(find.textContaining('BAD_DECRYPT'), findsOneWidget);
      expect(find.text('Copy for support'), findsOneWidget);
    });

    testWidgets('offers a retry that re-runs the boot', (tester) async {
      var attempts = 0;
      await tester.pumpWidget(BootFailureApp(
        error: _badDecrypt,
        onRetry: () async => attempts++,
      ));

      await tester.tap(find.text('Try again'));
      await tester.pumpAndSettle();

      expect(attempts, 1);
    });

    testWidgets('drops the retry when the caller offers no way back',
        (tester) async {
      await tester.pumpWidget(BootFailureApp(error: _badDecrypt));
      expect(find.widgetWithText(FilledButton, 'Try again'), findsNothing);
    });
  });

  group('AppConfig', () {
    test('a trailing slash on the host does not double up in the API path', () {
      final config =
          AppConfig(baseUrl: 'https://spa.astraqatar.com/', tenant: 'spa');
      expect(config.baseUrl, 'https://spa.astraqatar.com');
      expect(config.apiV1, 'https://spa.astraqatar.com/api/v1');
      expect(config.assetUrl('/storage/users/1.png'),
          'https://spa.astraqatar.com/storage/users/1.png');
    });

    test('trims whitespace and repeated slashes off a pasted host', () {
      expect(AppConfig.normalizeBaseUrl('  https://shop.example.com//  '),
          'https://shop.example.com');
    });
  });
}

/// Stands in for a disk-full failure without needing `dart:io` here.
class FileSystemExceptionStub {
  const FileSystemExceptionStub();
  @override
  String toString() => 'FileSystemException: No space left on device';
}
