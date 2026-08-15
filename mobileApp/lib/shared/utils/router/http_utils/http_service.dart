import 'dart:convert';
import 'dart:typed_data';

import 'package:dio/dio.dart';

import '../../../domain/constants/app_config.dart';
import '../../local_storage/local_storage_service.dart';
import 'common_exception.dart';
import 'reachability.dart';
// Native-only dev TLS handling; no-op on web.
import 'dev_http_stub.dart' if (dart.library.io) 'dev_http_io.dart';

/// Called when the server rejects the token (401) so the app can force re-login.
typedef OnUnauthorized = void Function();

/// Called with whether a request reached the server, so the app can show an
/// offline banner without any screen having to ask.
typedef OnReachability = void Function(bool reachable);

/// The Dio wrapper. Owns the connection [config], injects the auth token,
/// tenant/host headers and the active `branch_id`, and unwraps the Laravel
/// `{success,data,message}` envelope (throwing [ApiException] on failure).
///
/// Registered as a singleton in the service locator; this is the single entry
/// point for API calls — feature services reach it via
/// `serviceLocator<HttpService>()` and pull their paths from [EndPoints].
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
    // One place to observe reachability for the whole app. Every verb below goes
    // through Dio, so the offline banner is driven by what requests actually did
    // rather than by each call site remembering to report.
    _dio.interceptors.add(InterceptorsWrapper(
      onResponse: (response, handler) {
        // Any response at all — including a 4xx or 5xx — proves the server was
        // reached. `validateStatus` is permissive, so this covers those too.
        onReachability?.call(true);
        handler.next(response);
      },
      onError: (error, handler) {
        if (isServerUnreachable(error)) onReachability?.call(false);
        handler.next(error);
      },
    ));
  }

  final LocalStorageService storage;
  AppConfig config;
  late final Dio _dio;
  OnUnauthorized? onUnauthorized;
  OnReachability? onReachability;

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

  /// Download an asset — a product photo, a staff avatar — from an absolute URL
  /// already resolved by [AppConfig.assetUrl].
  ///
  /// Separate from [getBytes] because none of that method's assumptions hold
  /// here: there is no `/api/v1` prefix, the response is an image rather than a
  /// PDF, and no envelope is ever involved. It goes through the same Dio all the
  /// same, so an asset fetch reports reachability like every other request and
  /// inherits the dev self-signed-certificate handling — a photo that cannot
  /// load on a `.test` host would otherwise look like a missing image.
  ///
  /// The auth token is deliberately not attached: storage paths are served by
  /// the web server, not the API, and sending a bearer token to a plain file
  /// route gains nothing. [headers] carries the `Host` override that lets nginx
  /// route a LAN-IP request to the right vhost.
  Future<Uint8List> getAssetBytes(
    String url, {
    Map<String, String>? headers,
    void Function(int received, int total)? onProgress,
  }) async {
    final res = await _dio.get<List<int>>(
      url,
      options: Options(
        responseType: ResponseType.bytes,
        headers: {'Accept': '*/*', ...?headers},
      ),
      onReceiveProgress: onProgress,
    );
    final status = res.statusCode ?? 0;
    if (status < 200 || status >= 300) {
      throw ApiException('Image request failed ($status)', statusCode: status);
    }
    return Uint8List.fromList(res.data ?? const []);
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

  /// Multipart POST of raw in-memory [bytes] as a single file field — for data
  /// that never touched disk (e.g. a cropped image). Dio sets the multipart
  /// content-type + boundary from the [FormData] automatically.
  Future<dynamic> postFileBytes(
    String path, {
    required String field,
    required Uint8List bytes,
    required String filename,
  }) async {
    final form = FormData();
    form.files.add(MapEntry(field, MultipartFile.fromBytes(bytes, filename: filename)));
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
