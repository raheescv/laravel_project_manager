/// User-facing copy. New code should pull strings from here rather than
/// hardcoding English literals, so the app stays translation-ready.
class AppStrings {
  AppStrings._();

  static const String appName = 'QLOUD POS';

  // Generic / errors.
  static const String somethingWentWrong =
      'Something went wrong. Please try again.';
  static const String somethingWentWrongPleaseTryAgainLater =
      'Something went wrong. Please try again later.';
  static const String couldNotReachServer =
      'Could not reach the server. Check your connection and try again.';

  // Auth.
  static const String signIn = 'Sign in';
  static const String enterPin = 'Enter PIN';
  static const String username = 'Username';
  static const String password = 'Password';
  static const String biometricReason = 'Authenticate to sign in to QLOUD POS';

  // Catalog / sale.
  /// Shown (and stored) as the customer name when a ticket has no named client.
  /// Both the display label and the "is this an anonymous sale" sentinel, so it
  /// must stay a single shared constant.
  static const String walkInCustomer = 'Walk-in';
  static const String couldNotLoadCatalog = 'Could not load the catalog.';

  /// Offline with nothing cached to fall back on — a till that has never
  /// completed a sync, or one whose offline data has been cleared. Says what to
  /// do about it, because unlike every other offline state this one does not
  /// resolve itself by waiting.
  static const String noOfflineCatalog =
      'You’re offline and this device has no saved catalog yet. '
      'Reconnect once to prepare offline data.';
  static const String couldNotLoadStylists = 'Could not load stylists.';
  static const String couldNotLoadBranches = 'Could not load branches.';

  // Day session.
  static const String couldNotUpdateDaySession =
      'Could not update the day session. Check your connection and try again.';
}
