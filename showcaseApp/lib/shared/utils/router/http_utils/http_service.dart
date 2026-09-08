import 'dart:async';

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
  HttpService({required this.config, HttpClientAdapter? adapter}) {
    _dio = Dio(BaseOptions(
      connectTimeout: const Duration(seconds: 20),
      receiveTimeout: const Duration(seconds: 30),
      headers: {'Accept': 'application/json'},
      // Don't throw on non-2xx; the envelope is unwrapped below.
      validateStatus: (_) => true,
    ));
    // Local `.test` hosts serve a self-signed certificate; debug builds accept it.
    configureDevHttp(_dio);
    if (adapter != null) _dio.httpClientAdapter = adapter;
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

  /// A hard ceiling on one request.
  ///
  /// Dio's receive timeout only fires between chunks, so a server that trickles
  /// a response forever never trips it. Every screen in this app puts up a
  /// spinner and waits for this future, so anything that can hang has to be
  /// bounded here rather than left to the caller.
  static const Duration _deadline = Duration(seconds: 25);

  Future<dynamic> get(String path, {Map<String, dynamic>? query}) async {
    final cancelToken = CancelToken();
    try {
      final res = await _getWithRetry(
        '${config.apiV1}$path',
        _encode({..._baseQuery(), ...?query}),
        _headers(),
        cancelToken,
      ).timeout(_deadline, onTimeout: () {
        cancelToken.cancel('Request deadline exceeded');
        throw TimeoutException('Request deadline exceeded');
      });
      return _unwrap(res);
    } on ApiException {
      rethrow;
    } on DioException catch (e) {
      if (_unreachable(e)) throw OfflineException();
      throw ApiException(e.message ?? 'Request failed');
    } on TimeoutException {
      onReachability?.call(false);
      throw OfflineException();
    } catch (e) {
      // Anything else — a malformed body, a cast that did not hold, a bug in
      // here — becomes a typed failure too. Every caller catches ApiException
      // and nothing else, so an escaping exception does not surface as an
      // error: it strands the screen on its spinner with no way back.
      throw ApiException('Something went wrong loading this.');
    }
  }

  /// Retry a transient transport failure once, within the original deadline.
  /// The URL, tenant and branch stay fixed even if the store changes while
  /// waiting. Cancellation stops both an active request and a pending retry.
  Future<Response<dynamic>> _getWithRetry(
    String url,
    Map<String, dynamic> query,
    Map<String, dynamic> headers,
    CancelToken cancelToken,
  ) async {
    for (var attempt = 0; ; attempt++) {
      if (cancelToken.isCancelled) throw cancelToken.cancelError!;
      try {
        return await _dio.get(
          url,
          queryParameters: query,
          options: Options(headers: headers),
          cancelToken: cancelToken,
        );
      } on DioException catch (e) {
        final transient = e.type == DioExceptionType.connectionError ||
            e.type == DioExceptionType.connectionTimeout ||
            e.type == DioExceptionType.receiveTimeout;
        if (attempt > 0 || !transient || cancelToken.isCancelled) rethrow;
        await Future<void>.delayed(const Duration(milliseconds: 300));
      }
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
