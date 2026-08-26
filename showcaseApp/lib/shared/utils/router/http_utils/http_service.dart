import 'package:dio/dio.dart';

import '../../../domain/constants/app_config.dart';
import 'common_exception.dart';
// Native-only dev TLS handling; a no-op on web.
import 'dev_http_stub.dart' if (dart.library.io) 'dev_http_io.dart';

/// Called with whether a request reached the server, so the app can show an
/// offline banner without any screen having to ask.
typedef OnReachability = void Function(bool reachable);

/// The Dio wrapper and the single entry point for API calls. Owns the
/// connection [config], attaches the tenant header and query param to every
/// request, and unwraps the Laravel `{success, data, message}` envelope.
///
/// There is no auth token here on purpose: every endpoint this app touches is
/// part of the public catalog, and the showcase never signs anyone in.
class HttpService {
  HttpService({required this.config}) {
    _dio = Dio(BaseOptions(
      connectTimeout: const Duration(seconds: 20),
      receiveTimeout: const Duration(seconds: 30),
      headers: {'Accept': 'application/json'},
      // Don't throw on non-2xx; the envelope is unwrapped below.
      validateStatus: (_) => true,
    ));
    // Local `.test` hosts serve a self-signed certificate; debug builds accept it.
    configureDevHttp(_dio);
    _dio.interceptors.add(InterceptorsWrapper(
      onResponse: (response, handler) {
        // Any response at all — including 4xx/5xx — proves the server was
        // reached, so the offline banner is driven by what requests actually
        // did rather than by each call site remembering to report.
        onReachability?.call(true);
        handler.next(response);
      },
      onError: (error, handler) {
        if (_unreachable(error)) onReachability?.call(false);
        handler.next(error);
      },
    ));
  }

  AppConfig config;
  late final Dio _dio;
  OnReachability? onReachability;

  /// The branch every catalog query is scoped to. Set by `BranchCubit`; sent as
  /// `branch_id` so stock counts describe the shop the customer is standing in.
  int? activeBranchId;

  Future<dynamic> get(String path, {Map<String, dynamic>? query}) async {
    try {
      final res = await _dio.get(
        '${config.apiV1}$path',
        queryParameters: _encode({..._baseQuery(), ...?query}),
        options: Options(headers: _headers()),
      );
      return _unwrap(res);
    } on DioException catch (e) {
      if (_unreachable(e)) throw OfflineException();
      rethrow;
    }
  }

  Map<String, dynamic> _headers() {
    final headers = <String, dynamic>{};
    if (config.tenant.isNotEmpty) headers['X-Tenant-Subdomain'] = config.tenant;
    // Hitting a LAN IP: override Host so nginx routes to the right vhost.
    if (config.hostHeader.isNotEmpty) headers['Host'] = config.hostHeader;
    return headers;
  }

  Map<String, dynamic> _baseQuery() => {
        if (config.tenant.isNotEmpty) 'tenant': config.tenant,
        if (activeBranchId != null) 'branch_id': activeBranchId,
      };

  /// Laravel's `boolean` rule rejects "true"/"false" strings; send 1/0.
  Map<String, dynamic> _encode(Map<String, dynamic> q) =>
      q.map((k, v) => MapEntry(k, v is bool ? (v ? 1 : 0) : v));

  bool _unreachable(DioException e) =>
      e.type == DioExceptionType.connectionError ||
      e.type == DioExceptionType.connectionTimeout ||
      e.type == DioExceptionType.receiveTimeout ||
      e.type == DioExceptionType.unknown;

  dynamic _unwrap(Response<dynamic> res) {
    final status = res.statusCode ?? 0;
    final data = res.data;

    if (data is Map) {
      final success = data['success'] == true;
      final message = (data['message'] ?? '').toString();
      if (success && status >= 200 && status < 300) return data['data'];
      throw ApiException(
        message.isEmpty ? 'Request failed ($status)' : message,
        statusCode: status,
      );
    }

    if (status >= 200 && status < 300) return data;
    throw ApiException('Unexpected response ($status)', statusCode: status);
  }
}
