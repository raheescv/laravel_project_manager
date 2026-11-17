/// Plain-language readings of the failures a till can actually hit.
///
/// A raw `PlatformException(... javax.crypto.BadPaddingException: BAD_DECRYPT
/// at com.android.org.conscrypt...)` tells the person holding the device
/// nothing, and it is what the boot-failure screen used to put in front of a
/// cashier mid-shift. Everything here answers two questions instead: what
/// happened, in their words, and what to do about it. The raw text is still
/// kept — folded away on the screen, and uploaded by [CrashReporter] — because
/// support still needs it.
class FriendlyError {
  const FriendlyError({
    required this.title,
    required this.message,
    required this.hint,
  });

  /// One short line: what failed, not which class threw.
  final String title;

  /// Two or three sentences: why it happens and what it means for their data.
  final String message;

  /// The single next thing to try.
  final String hint;

  /// The reading for a failure that stopped the app from starting.
  static FriendlyError forBoot(Object error) {
    if (isSecureStoreUnreadable(error)) {
      return const FriendlyError(
        title: 'Saved sign-in could not be opened',
        message:
            "This device's stored login is locked to a key it no longer has — "
            'usually after the app is reinstalled, restored onto a new device, '
            'or the screen lock is reset. Nothing on the server is affected, '
            'and no sale has been lost.',
        hint: 'Try again, then sign in with your PIN or password.',
      );
    }
    if (_matches(error, const ['no space left', 'enospc', 'disk full', 'out of memory'])) {
      return const FriendlyError(
        title: 'The device is out of storage',
        message:
            'There is no room left to open the offline store the app keeps on '
            'this device, so it cannot start.',
        hint: 'Free up some space on the device, then open the app again.',
      );
    }
    if (_matches(error, const ['sqlite', 'database', 'databaseexception', 'sqflite'])) {
      return const FriendlyError(
        title: 'The offline store could not be opened',
        message:
            "The database this device keeps for selling offline didn't open. "
            'Sales that already reached the server are safe — only what is '
            'still queued on this device is at risk.',
        hint: 'Try again. If it keeps happening, show this screen to support '
            'before reinstalling.',
      );
    }
    if (_matches(error, const [
      'socketexception',
      'handshakeexception',
      'connection refused',
      'failed host lookup',
      'timeoutexception',
    ])) {
      return const FriendlyError(
        title: 'The server could not be reached',
        message:
            "The app couldn't reach the server while starting up. Check that "
            'the device is on the shop network and that the server address in '
            'Settings is right.',
        hint: 'Check the connection, then try again.',
      );
    }
    return const FriendlyError(
      title: 'The app could not finish starting',
      message:
          'Something went wrong before the app was ready. This is almost always '
          'temporary — nothing on the server has been affected.',
      hint: 'Try again. If it keeps happening, show this screen to support.',
    );
  }

  /// Whether the platform's secure store cannot be decrypted any more.
  ///
  /// Android's encrypted preferences file outlives the keystore key that opens
  /// it — a reinstall, a device-to-device restore or a screen-lock reset can
  /// leave the two out of step, and every read then throws `BAD_DECRYPT`. It is
  /// unrecoverable: no retry decrypts it. The only fix is to drop the entries
  /// and sign in again, which is why [LocalStorageService] treats this one
  /// error as permission to wipe its secure keys.
  static bool isSecureStoreUnreadable(Object error) => _matches(error, const [
        'bad_decrypt',
        'badpaddingexception',
        'aeadbadtagexception',
        'invalid keystore format',
        'keystoreexception',
        'generalsecurityexception',
      ]);

  static bool _matches(Object error, List<String> needles) {
    final text = error.toString().toLowerCase();
    return needles.any(text.contains);
  }
}
