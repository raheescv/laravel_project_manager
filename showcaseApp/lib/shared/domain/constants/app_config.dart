/// Connection configuration for the Laravel `api/v1` public catalog.
///
/// The catalog endpoints are open (no login) but a tenant MUST resolve, so the
/// tenant travels on every request twice: as `X-Tenant-Subdomain` and as
/// `?tenant=`. The query param is the fallback for IP/localhost hosts where the
/// server cannot infer the tenant from the host name.
///
/// Values come from `--dart-define-from-file=env.json` at build time.
class AppConfig {
  const AppConfig({
    required this.baseUrl,
    required this.tenant,
    this.hostHeader = '',
  });

  /// e.g. https://project_manager.test — no trailing slash, no `/api`.
  final String baseUrl;

  /// Tenant subdomain, e.g. "project_manager".
  final String tenant;

  /// Optional `Host` header override. When [baseUrl] is a LAN IP, nginx cannot
  /// match the request to a virtual host and falls through to the catch-all;
  /// setting this to the site's `.test` host routes it correctly. Empty = off.
  final String hostHeader;

  static const String envBaseUrl = String.fromEnvironment('API_BASE_URL');
  static const String envTenant = String.fromEnvironment('API_TENANT');
  static const String envHostHeader = String.fromEnvironment('API_HOST');

  static const String fallbackBaseUrl = 'https://project_manager.test';

  /// A build-time env value always wins, so a stale saved override can never
  /// silently shadow `env.json`.
  static AppConfig resolve({String? savedBaseUrl, String? savedTenant}) => AppConfig(
        baseUrl: envBaseUrl.isNotEmpty ? envBaseUrl : (savedBaseUrl ?? fallbackBaseUrl),
        tenant: envTenant.isNotEmpty ? envTenant : (savedTenant ?? ''),
        hostHeader: envHostHeader,
      );

  String get apiV1 => '$baseUrl/api/v1';

  /// Absolute URL for a product photo.
  ///
  /// A relative storage path is resolved against [baseUrl]. An **absolute** URL
  /// is left alone: the catalogue stores fully-qualified image URLs on the
  /// tenant's own asset host, and rewriting those onto a development [baseUrl]
  /// points every photo at a machine that holds none of the files.
  String assetUrl(String raw) {
    if (raw.isEmpty) return raw;
    final uri = Uri.tryParse(raw);
    if (uri != null && uri.hasScheme && uri.host.isNotEmpty) return raw;

    var path = raw;
    if (!path.startsWith('/')) path = '/$path';
    final base =
        baseUrl.endsWith('/') ? baseUrl.substring(0, baseUrl.length - 1) : baseUrl;
    return '$base$path';
  }

  /// Headers for `Image.network`, mirroring the Dio config so an image request
  /// reaches the same vhost the API does.
  Map<String, String>? get assetHeaders =>
      hostHeader.isEmpty ? null : {'Host': hostHeader};

  AppConfig copyWith({String? baseUrl, String? tenant, String? hostHeader}) => AppConfig(
        baseUrl: baseUrl ?? this.baseUrl,
        tenant: tenant ?? this.tenant,
        hostHeader: hostHeader ?? this.hostHeader,
      );
}
