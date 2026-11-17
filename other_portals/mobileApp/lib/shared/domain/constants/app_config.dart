/// Connection configuration for the Laravel `api/v1` backend.
///
/// Tenant resolution on the server is host/subdomain based; for device &
/// emulator development we also send `X-Tenant-Subdomain` so the tenant resolves
/// without a real subdomain host.
///
/// Defaults are build-time values supplied via `--dart-define-from-file=env.json`.
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

  /// What this build points at until someone saves a connection.
  static String get defaultBaseUrl =>
      envBaseUrl.isNotEmpty ? envBaseUrl : fallbackBaseUrl;
  static String get defaultTenant => envTenant;

  /// Resolve the active connection.
  ///
  /// A connection saved from the Connection sheet wins. The build-time value is
  /// only the default for a device that has never saved one. It used to win
  /// outright — "so a stale saved value can never shadow env.json" — which meant
  /// every cold start silently repointed a till that had been moved to another
  /// server back at whatever `env.json` was compiled with. On a phone that is
  /// any sign-out the OS follows by killing the backgrounded process, so it
  /// looked like signing in and out reset the address. The sheet offers the
  /// build default back, which is the one thing the old rule was good for.
  ///
  /// [buildBaseUrl] / [buildTenant] / [buildHostHeader] exist so the precedence
  /// is testable; under `flutter test` the real `--dart-define` values are empty.
  static AppConfig resolve({
    String? savedBaseUrl,
    String? savedTenant,
    String buildBaseUrl = envBaseUrl,
    String buildTenant = envTenant,
    String buildHostHeader = envHostHeader,
  }) {
    final saved = normalizeBaseUrl(savedBaseUrl ?? '');
    final baseUrl = saved.isNotEmpty
        ? saved
        : (buildBaseUrl.isNotEmpty ? buildBaseUrl : fallbackBaseUrl);
    return AppConfig(
      baseUrl: baseUrl,
      // A saved tenant wins even when it is blank: the sheet writes both fields
      // together, so a blank one was cleared on purpose, not never set.
      tenant: (savedTenant ?? buildTenant).trim(),
      hostHeader: hostHeaderFor(baseUrl,
          buildBaseUrl: buildBaseUrl, buildHostHeader: buildHostHeader),
    );
  }

  /// The `Host` override to send to [baseUrl].
  ///
  /// It belongs to the build's own host — it is what routes a LAN-IP request
  /// to the right Valet/nginx site — so a device pointed at any other server
  /// sends none. `Host: project_manager.test` against a live domain is a request
  /// nginx routes to the wrong site, or nowhere.
  static String hostHeaderFor(
    String baseUrl, {
    String buildBaseUrl = envBaseUrl,
    String buildHostHeader = envHostHeader,
  }) =>
      normalizeBaseUrl(baseUrl) == normalizeBaseUrl(buildBaseUrl)
          ? buildHostHeader
          : '';

  String get apiV1 => '$baseUrl/api/v1';

  /// Strips the whitespace and trailing slashes a hand-typed (or pasted) host
  /// arrives with. `https://shop.example.com/` would otherwise build
  /// `https://shop.example.com//api/v1` — one slash the server never routed, on
  /// every request the app makes.
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
    return '$baseUrl$path';
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
