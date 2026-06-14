/// Connection configuration for the Laravel `api/v1` backend.
///
/// Tenant resolution on the server is host/subdomain based; for device & emulator
/// development we also send `X-Tenant-Subdomain` so the tenant resolves without a
/// real subdomain host (requires the small IdentifyTenant tweak noted in
/// mobileApp/BUILD_PROMPT.md, §8).
///
/// The defaults are build-time values supplied via `--dart-define-from-file`.
/// Copy `env.example.json` to `env.json`, set your machine's WiFi IP, then run:
///
///   flutter run --dart-define-from-file=env.json
///
/// On a real device the host's `.test` domain is NOT resolvable, so `API_BASE_URL`
/// must point at the LAN IP of the machine running Valet (e.g. http://192.168.x.x).
class AppConfig {
  AppConfig({required this.baseUrl, required this.tenant, this.hostHeader = ''});

  /// e.g. http://192.168.68.106  (no trailing slash, no /api)
  final String baseUrl;

  /// Tenant subdomain, e.g. "project_manager". Sent as `X-Tenant-Subdomain` /
  /// `?tenant=` so the server resolves the tenant when the host is an IP.
  final String tenant;

  /// Optional `Host` header override. When [baseUrl] is a LAN IP, Valet/nginx
  /// can't match the request to the right virtual host (every site is matched by
  /// `server_name`), so it falls through to the catch-all and 404s. Setting this
  /// to the site's `.test` host (e.g. "project_manager.test") makes nginx route
  /// to the correct app. Empty = don't override (normal `.test` host access).
  final String hostHeader;

  /// Build-time values from `--dart-define-from-file=env.json`. Empty when no env
  /// file was supplied (e.g. a plain `flutter run`).
  static const String envBaseUrl = String.fromEnvironment('API_BASE_URL');
  static const String envTenant = String.fromEnvironment('API_TENANT');
  static const String envHostHeader = String.fromEnvironment('API_HOST');

  /// Last-resort host when there is neither an env value nor a saved override.
  static const String fallbackBaseUrl = 'https://project_manager.test';

  /// Resolve the active connection. A build-time env value ALWAYS wins so a stale
  /// value saved via the in-app gear can never silently shadow `env.json`. With no
  /// env file we fall back to the saved override, then the loopback `.test` host.
  static AppConfig resolve({String? savedBaseUrl, String? savedTenant}) =>
      AppConfig(
        baseUrl: envBaseUrl.isNotEmpty
            ? envBaseUrl
            : (savedBaseUrl ?? fallbackBaseUrl),
        tenant: envTenant.isNotEmpty ? envTenant : (savedTenant ?? ''),
        hostHeader: envHostHeader,
      );

  String get apiV1 => '$baseUrl/api/v1';

  AppConfig copyWith({String? baseUrl, String? tenant}) => AppConfig(
        baseUrl: baseUrl ?? this.baseUrl,
        tenant: tenant ?? this.tenant,
      );
}
