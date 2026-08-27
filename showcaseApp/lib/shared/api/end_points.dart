/// Every `api/v1` path this app uses, relative to `AppConfig.apiV1`.
///
/// All of them sit inside the public, tenant-scoped block of `routes/api_v1.php`
/// (`IdentifyTenant:required`) — open, but a tenant must resolve.
class EndPoints {
  const EndPoints._();

  // ---- Catalog funnel ----
  static const String categories = '/categories';
  static const String sizes = '/sizes';
  static const String brands = '/brands';
  static const String colors = '/colors';
  static const String products = '/products';

  /// Full detail: `images`, `images360`, `available_sizes`, `related_sizes`,
  /// per-branch `inventories`. Only the single-product routes emit those.
  static String productById(int id) => '/products/$id';


  // ---- Store ----
  static const String branches = '/branches';
  static const String branding = '/settings/branding';
}
