/// User-facing copy. New code should pull strings from here rather than
/// hardcoding English literals, so the app stays translation-ready.
class AppStrings {
  AppStrings._();

  static const String appName = 'Invo';

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
  static const String biometricReason = 'Authenticate to sign in to Invo';

  // Catalog / sale.
  static const String couldNotLoadCatalog = 'Could not load the catalog.';
  static const String couldNotLoadStylists = 'Could not load stylists.';
  static const String couldNotLoadBranches = 'Could not load branches.';

  // Day session.
  static const String couldNotUpdateDaySession =
      'Could not update the day session. Check your connection and try again.';
}
