import 'package:dio/dio.dart';

import 'config.dart';
import 'storage.dart';
// Native-only dev TLS handling; no-op on web.
import 'dev_http_stub.dart' if (dart.library.io) 'dev_http_io.dart';

/// A typed error surfaced from the API. Mirrors the Laravel `{success,message,data}`
/// envelope and the 401/403/422 handling described in the build prompt.
class ApiException implements Exception {
  ApiException(this.message, {this.statusCode, this.fieldErrors});

  final String message;
  final int? statusCode;

  /// 422 validation errors: { field: [messages] }.
  final Map<String, List<String>>? fieldErrors;

  bool get isUnauthorized => statusCode == 401;
  bool get isForbidden => statusCode == 403;

  @override
  String toString() => message;
}

/// Called when the server rejects the token (401) so the app can force re-login.
typedef OnUnauthorized = void Function();

class ApiClient {
  ApiClient({required this.storage, required this.config}) {
    _dio = Dio(BaseOptions(
      connectTimeout: const Duration(seconds: 20),
      receiveTimeout: const Duration(seconds: 30),
      headers: {'Accept': 'application/json'},
      // Don't throw on non-2xx; we unwrap the envelope ourselves.
      validateStatus: (_) => true,
    ));
    // Allow self-signed certs for local .test hosts in debug (native only).
    configureDevHttp(_dio);
  }

  final Storage storage;
  AppConfig config;
  late final Dio _dio;
  OnUnauthorized? onUnauthorized;

  Options _opts() {
    final headers = <String, dynamic>{};
    if (config.tenant.isNotEmpty) {
      headers['X-Tenant-Subdomain'] = config.tenant;
    }
    // When hitting a LAN IP, override Host so nginx routes to the right vhost.
    if (config.hostHeader.isNotEmpty) {
      headers['Host'] = config.hostHeader;
    }
    return Options(headers: headers);
  }

  Map<String, dynamic> _query() {
    final q = <String, dynamic>{};
    // Localhost tenant fallback also accepts ?tenant=
    if (config.tenant.isNotEmpty) q['tenant'] = config.tenant;
    return q;
  }

  Future<Options> _authOpts() async {
    final base = _opts();
    final token = await storage.readToken();
    if (token != null) {
      base.headers!['Authorization'] = 'Bearer $token';
    }
    return base;
  }

  /// Laravel's `boolean` validation rule rejects the strings "true"/"false"
  /// (which is how Dio serialises Dart bools in a query string). Send 1/0 instead.
  Map<String, dynamic> _encodeQuery(Map<String, dynamic> q) =>
      q.map((k, v) => MapEntry(k, v is bool ? (v ? 1 : 0) : v));

  // ---- verbs ----

  Future<dynamic> get(String path, {Map<String, dynamic>? query, bool auth = true}) async {
    final res = await _dio.get(
      '${config.apiV1}$path',
      queryParameters: _encodeQuery({..._query(), ...?query}),
      options: auth ? await _authOpts() : _opts(),
    );
    return _unwrap(res);
  }

  Future<dynamic> post(String path, {Object? body, bool auth = true}) async {
    final res = await _dio.post(
      '${config.apiV1}$path',
      data: body,
      queryParameters: _query(),
      options: auth ? await _authOpts() : _opts(),
    );
    return _unwrap(res);
  }

  /// Unwrap the `{success,data,message}` envelope or throw [ApiException].
  dynamic _unwrap(Response res) {
    final status = res.statusCode ?? 0;
    final data = res.data;

    if (status == 401) {
      onUnauthorized?.call();
    }

    if (data is Map) {
      final success = data['success'] == true;
      final message = (data['message'] ?? '').toString();
      if (success && status >= 200 && status < 300) {
        return data['data'];
      }
      // Error path.
      Map<String, List<String>>? fields;
      final errs = data['data'];
      if (status == 422 && errs is Map) {
        fields = errs.map((k, v) => MapEntry(
              k.toString(),
              (v is List) ? v.map((e) => e.toString()).toList() : [v.toString()],
            ));
      }
      throw ApiException(
        message.isEmpty ? 'Request failed ($status)' : message,
        statusCode: status,
        fieldErrors: fields,
      );
    }

    if (status >= 200 && status < 300) return data;
    throw ApiException('Unexpected response ($status)', statusCode: status);
  }
}
