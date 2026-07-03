import 'dart:convert';
import 'dart:typed_data';

import 'package:dio/dio.dart';

import '../../../domain/constants/app_config.dart';
import '../../local_storage/local_storage_service.dart';
import 'common_exception.dart';
// Native-only dev TLS handling; no-op on web.
import 'dev_http_stub.dart' if (dart.library.io) 'dev_http_io.dart';

/// Called when the server rejects the token (401) so the app can force re-login.
typedef OnUnauthorized = void Function();

/// The Dio wrapper. Owns the connection [config], injects the auth token,
/// tenant/host headers and the active `branch_id`, and unwraps the Laravel
/// `{success,data,message}` envelope (throwing [ApiException] on failure).
///
/// Registered as a singleton in the service locator; feature services reach it
/// through [HttpHelper] or directly via `serviceLocator<HttpService>()`.
class HttpService {
  HttpService({required this.storage, required this.config}) {
    _dio = Dio(BaseOptions(
      connectTimeout: const Duration(seconds: 20),
      receiveTimeout: const Duration(seconds: 30),
      headers: {'Accept': 'application/json'},
      // Don't throw on non-2xx; we unwrap the envelope ourselves.
      validateStatus: (_) => true,
    ));
    // Allow self-signed certs for local .test hosts in dev (native only).
    configureDevHttp(_dio);
  }

  final LocalStorageService storage;
  AppConfig config;
  late final Dio _dio;
  OnUnauthorized? onUnauthorized;

  /// The branch the user is operating as (set by BranchCubit). When set it is
  /// attached as `branch_id` to every request so branch-aware endpoints filter
  /// to this branch.
  int? activeBranchId;

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
    if (config.tenant.isNotEmpty) q['tenant'] = config.tenant;
    if (activeBranchId != null) q['branch_id'] = activeBranchId;
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

  /// Laravel's `boolean` rule rejects "true"/"false" strings; send 1/0 instead.
  Map<String, dynamic> _encodeQuery(Map<String, dynamic> q) =>
      q.map((k, v) => MapEntry(k, v is bool ? (v ? 1 : 0) : v));

  // ---- verbs ----

  Future<dynamic> get(String path,
      {Map<String, dynamic>? query, bool auth = true}) async {
    final res = await _dio.get(
      '${config.apiV1}$path',
      queryParameters: _encodeQuery({..._query(), ...?query}),
      options: auth ? await _authOpts() : _opts(),
    );
    return _unwrap(res);
  }

  /// Download raw bytes (e.g. a server-rendered PDF) from an authenticated
  /// endpoint, bypassing JSON envelope unwrapping.
  Future<Uint8List> getBytes(String path, {Map<String, dynamic>? query}) async {
    final opts = await _authOpts();
    opts.responseType = ResponseType.bytes;
    opts.headers!['Accept'] = 'application/pdf';
    final res = await _dio.get(
      '${config.apiV1}$path',
      queryParameters: _encodeQuery({..._query(), ...?query}),
      options: opts,
    );
    final status = res.statusCode ?? 0;
    if (status == 401) onUnauthorized?.call();
    final bytes = Uint8List.fromList((res.data as List<int>?) ?? const []);
    if (status >= 200 && status < 300) return bytes;

    var message = 'Request failed ($status)';
    try {
      final decoded = jsonDecode(utf8.decode(bytes));
      if (decoded is Map && decoded['message'] != null) {
        message = decoded['message'].toString();
      }
    } catch (_) {
      // Not a JSON error body — keep the generic message.
    }
    throw ApiException(message, statusCode: status);
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

  /// Multipart POST — uploads one or more files (plus optional scalar fields)
  /// to an authenticated endpoint. [files] is a list of (field, path) pairs;
  /// repeated files reuse the same field name (e.g. `attachments[]`).
  Future<dynamic> postFiles(
    String path, {
    Map<String, String> fields = const {},
    required List<({String field, String path})> files,
  }) async {
    final form = FormData();
    fields.forEach((k, v) => form.fields.add(MapEntry(k, v)));
    for (final f in files) {
      form.files.add(MapEntry(
        f.field,
        await MultipartFile.fromFile(f.path, filename: f.path.split('/').last),
      ));
    }
    final res = await _dio.post(
      '${config.apiV1}$path',
      data: form,
      queryParameters: _query(),
      options: await _authOpts(),
    );
    return _unwrap(res);
  }

  Future<dynamic> put(String path, {Object? body, bool auth = true}) async {
    final res = await _dio.put(
      '${config.apiV1}$path',
      data: body,
      queryParameters: _query(),
      options: auth ? await _authOpts() : _opts(),
    );
    return _unwrap(res);
  }

  Future<dynamic> delete(String path, {Object? body, bool auth = true}) async {
    final res = await _dio.delete(
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
