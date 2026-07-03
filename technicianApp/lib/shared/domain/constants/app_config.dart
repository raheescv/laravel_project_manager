import 'package:flutter_dotenv/flutter_dotenv.dart';

/// Connection configuration for the Laravel `api/v1` backend.
///
/// Tenant resolution on the server is host/subdomain based; for device &
/// emulator development we also send `X-Tenant-Subdomain` so the tenant resolves
/// without a real subdomain host.
///
/// Defaults are build-time values supplied via `--dart-define-from-file=env.json`.
/// A `.env` file (loaded in `main.dart`) is checked next — the quickest way to
/// point a local dev build at a different URL: edit `.env`, hot-restart, no
/// rebuild needed. A saved in-app override (settings screen) comes after that.
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

  /// Whether `.env` was loaded (via `dotenv.load()` in `main.dart`). Guarded so
  /// a missing/failed `.env` file never crashes boot — `dotenv.env` throws if
  /// read before `load()` succeeds.
  static bool get _hasDotenv => dotenv.isInitialized;

  static bool get _dotenvUseLan =>
      (dotenv.env['USE_LAN_API'] ?? 'false').toLowerCase() == 'true';

  static String get _dotenvBaseUrl {
    if (!_hasDotenv) return '';
    return _dotenvUseLan
        ? (dotenv.env['API_BASE_URL_LAN'] ?? '')
        : (dotenv.env['API_BASE_URL'] ?? '');
  }

  static String get _dotenvTenant {
    if (!_hasDotenv) return '';
    return _dotenvUseLan
        ? (dotenv.env['API_TENANT_LAN'] ?? '')
        : (dotenv.env['API_TENANT'] ?? '');
  }

  /// Resolve the active connection. Priority: build-time `--dart-define`
  /// (always wins so a stale saved/`.env` value can never silently shadow a
  /// release build) > `.env` file > saved in-app override > last-resort fallback.
  static AppConfig resolve({String? savedBaseUrl, String? savedTenant}) =>
      AppConfig(
        baseUrl: envBaseUrl.isNotEmpty
            ? envBaseUrl
            : (_dotenvBaseUrl.isNotEmpty
                ? _dotenvBaseUrl
                : (savedBaseUrl ?? fallbackBaseUrl)),
        tenant: envTenant.isNotEmpty
            ? envTenant
            : (_dotenvTenant.isNotEmpty ? _dotenvTenant : (savedTenant ?? '')),
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
