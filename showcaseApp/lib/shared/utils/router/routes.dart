/// Every route path, in one place, so a rename is a single edit the compiler
/// checks rather than a silent runtime no-match.
class Routes {
  const Routes._();

  static const String browse = '/';
  static const String size = '/size';
  static const String brand = '/brand';
  static const String results = '/results';

  static const String product = '/product/:id';
  static String productById(int id) => '/product/$id';

  /// Nested under [product] so closing the viewer returns to the page it was
  /// opened from, with its scroll position intact.
  static const String spin = 'spin';
  static String spinFor(int id) => '/product/$id/spin';

  static const String search = '/search';
  static const String scan = '/scan';
}
