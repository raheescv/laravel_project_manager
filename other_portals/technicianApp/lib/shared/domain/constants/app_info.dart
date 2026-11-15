/// App identity shown in Settings → About.
///
/// The version mirrors pubspec's `version:`. The POS app reads it live through
/// `package_info_plus`; this app doesn't carry that dependency, so keep this in
/// step when bumping pubspec.
class AppInfo {
  AppInfo._();

  static const String name = 'FixMate';
  static const String tagline = 'Field maintenance';
  static const String version = 'v1.0.0';
}
