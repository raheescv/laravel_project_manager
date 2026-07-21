/// Connection configuration for the Laravel `api/v1` backend.
///
/// Tenant resolution on the server is host/subdomain based; for device &
/// emulator development we also send `X-Tenant-Subdomain` so the tenant resolves
/// without a real subdomain host.
///
/// Defaults are build-time values supplied via `--dart-define-from-file=env.json`.
class AppConfig {
  AppConfig({required this.baseUrl, required this.tenant, this.hostHeader = ''});

  /// e.g. http://192.168.68.106  (no trailing slash, no /api)
  final String baseUrl;

  /// Tenant subdomain, e.g. "project_manager". Sent as `X-Tenant-Subdomain` /
  /// `?tenant=` so the server resolves the tenant when the host is an IP.
  final String tenant;

  /// Optional `Host` header override. When [baseUrl] is a LAN IP, Valet/nginx
  /// can't match the request to the right virtual host, so it falls through to
  /// the catch-all and 404s. Setting this to the site's `.test` host makes nginx
  /// route to the correct app. Empty = don't override.
  final String hostHeader;

  /// Build-time values from `--dart-define-from-file=env.json`. Empty when no env
  /// file was supplied.
  static const String envBaseUrl = String.fromEnvironment('API_BASE_URL');
  static const String envTenant = String.fromEnvironment('API_TENANT');
  static const String envHostHeader = String.fromEnvironment('API_HOST');

  /// Last-resort host when there is neither an env value nor a saved override.
  static const String fallbackBaseUrl = 'https://project_manager.test';

  /// Resolve the active connection. A build-time env value ALWAYS wins so a stale
  /// saved value can never silently shadow `env.json`.
  static AppConfig resolve({String? savedBaseUrl, String? savedTenant}) =>
      AppConfig(
        baseUrl:
            envBaseUrl.isNotEmpty ? envBaseUrl : (savedBaseUrl ?? fallbackBaseUrl),
        tenant: envTenant.isNotEmpty ? envTenant : (savedTenant ?? ''),
        hostHeader: envHostHeader,
      );

  String get apiV1 => '$baseUrl/api/v1';

  /// Absolute URL for a server asset/attachment. The API returns storage paths
  /// relative to the site root (e.g. `/storage/…`); we point them at the
  /// reachable [baseUrl] instead of whatever host the server would bake in, so
  /// images load on a real device (LAN IP / correct host) exactly like the API.
  /// If the server ever returns an absolute URL we rewrite it onto [baseUrl] too.
  String assetUrl(String raw) {
    if (raw.isEmpty) return raw;
    var path = raw;
    final uri = Uri.tryParse(raw);
    if (uri != null && uri.hasScheme) {
      path = uri.path;
      if (uri.hasQuery) path = '$path?${uri.query}';
    }
    if (!path.startsWith('/')) path = '/$path';
    final base =
        baseUrl.endsWith('/') ? baseUrl.substring(0, baseUrl.length - 1) : baseUrl;
    return '$base$path';
  }

  /// Headers to attach when loading an asset over HTTP — mirrors the Dio config
  /// so `Image.network` reaches the server the same way. The `Host` override lets
  /// nginx route a LAN-IP request to the right virtual host.
  Map<String, String>? get assetHeaders =>
      hostHeader.isEmpty ? null : {'Host': hostHeader};

  AppConfig copyWith({String? baseUrl, String? tenant, String? hostHeader}) =>
      AppConfig(
        baseUrl: baseUrl ?? this.baseUrl,
        tenant: tenant ?? this.tenant,
        hostHeader: hostHeader ?? this.hostHeader,
      );
}
