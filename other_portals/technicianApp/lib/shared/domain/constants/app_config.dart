import 'package:flutter_dotenv/flutter_dotenv.dart';

/// Connection configuration for the Laravel `api/v1` backend.
///
/// Tenant resolution on the server is host/subdomain based; for device &
/// emulator development we also send `X-Tenant-Subdomain` so the tenant resolves
/// without a real subdomain host.
///
/// Precedence (same as the POS app): a connection saved from the Connection
/// sheet WINS; the build's own address — `--dart-define-from-file=env.json`,
/// then the dev `.env` — is only the default for a device that has never saved
/// one. It used to be the other way round, which silently repointed a device
/// back at the build address on every cold start (any sign-out the OS follows
/// by killing the app). The sheet offers the build default back instead.
class AppConfig {
  AppConfig({required String baseUrl, required this.tenant, this.hostHeader = ''})
      : baseUrl = normalizeBaseUrl(baseUrl);

  /// e.g. http://192.168.68.106  (no trailing slash, no /api) — guaranteed, the
  /// constructor runs [normalizeBaseUrl] over whatever it is handed.
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

  /// The build's own address: env.json, then the dev `.env`.
  static String get buildBaseUrl => envBaseUrl.isNotEmpty ? envBaseUrl : _dotenvBaseUrl;
  static String get buildTenant => envTenant.isNotEmpty ? envTenant : _dotenvTenant;

  /// What this build points at until someone saves a connection.
  static String get defaultBaseUrl => buildBaseUrl.isNotEmpty ? buildBaseUrl : fallbackBaseUrl;
  static String get defaultTenant => buildTenant;

  /// Resolve the active connection — saved first, then the build default.
  /// The build values are parameters so the precedence is testable.
  static AppConfig resolve({
    String? savedBaseUrl,
    String? savedTenant,
    String? buildBaseUrl,
    String? buildTenant,
    String buildHostHeader = envHostHeader,
  }) {
    final build = buildBaseUrl ?? AppConfig.buildBaseUrl;
    final saved = normalizeBaseUrl(savedBaseUrl ?? '');
    final baseUrl = saved.isNotEmpty ? saved : (build.isNotEmpty ? build : fallbackBaseUrl);
    return AppConfig(
      baseUrl: baseUrl,
      // A saved tenant wins even when blank: the sheet writes both fields
      // together, so a blank one was cleared on purpose.
      tenant: (savedTenant ?? buildTenant ?? AppConfig.buildTenant).trim(),
      hostHeader: hostHeaderFor(baseUrl, buildBaseUrl: build, buildHostHeader: buildHostHeader),
    );
  }

  /// The `Host` override to send to [baseUrl]. It belongs to the build's own
  /// host (it routes a LAN-IP request to the right Valet/nginx site), so a
  /// device pointed at any other server sends none.
  static String hostHeaderFor(
    String baseUrl, {
    String? buildBaseUrl,
    String buildHostHeader = envHostHeader,
  }) =>
      normalizeBaseUrl(baseUrl) == normalizeBaseUrl(buildBaseUrl ?? AppConfig.buildBaseUrl)
          ? buildHostHeader
          : '';

  String get apiV1 => '$baseUrl/api/v1';

  /// Strips the whitespace and trailing slashes a hand-typed host arrives with
  /// (`https://x.com/` would otherwise build `https://x.com//api/v1`).
  static String normalizeBaseUrl(String raw) {
    var value = raw.trim();
    while (value.endsWith('/')) {
      value = value.substring(0, value.length - 1);
    }
    return value;
  }

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
