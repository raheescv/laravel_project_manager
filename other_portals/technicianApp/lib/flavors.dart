/// Build flavor (dev / prod). Set from the flavor entry points
/// (`main_dev.dart` / `main_prod.dart`) before `runApp`.
enum Flavor { dev, prod }

/// Flavor helper. `F.appFlavor` is assigned once at boot; everything else reads
/// from it. Use `F.isDev` (never `kDebugMode`) for environment-specific logic.
class F {
  F._();

  static Flavor? appFlavor;

  static bool get isDev => appFlavor == Flavor.dev;

  static String get name => appFlavor?.name ?? '';

  static String get title => appFlavor == Flavor.dev ? 'FixMate (Dev)' : 'FixMate';
}
