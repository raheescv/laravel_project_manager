import 'dart:typed_data';

import '../../../domain/constants/global_variables.dart';
import 'common_exception.dart';
import 'http_service.dart';
import 'model/response_data.dart';

/// HTTP verbs supported by [HttpHelper.getDataFromServer].
enum RequestType { post, get, delete, put, patch }

/// The single, unified call site for API requests. Delegates to the registered
/// [HttpService] singleton (which injects auth/tenant/branch and unwraps the
/// Laravel envelope) and normalises the outcome into a [ResponseData].
///
/// Feature services either use this (skeleton convention) or, for raw payloads
/// (typed lists, bytes), reach the [HttpService] directly via [service].
class HttpHelper {
  HttpHelper._();

  static HttpService get service => serviceLocator<HttpService>();

  /// Run a request and return a normalised [ResponseData]. Never throws for an
  /// API-level error — inspect [ResponseData.success]; transport errors still
  /// throw so callers can surface "could not reach server".
  static Future<ResponseData> getDataFromServer(
    String path, {
    RequestType requestType = RequestType.post,
    Object? data,
    Map<String, dynamic>? query,
    bool authenticationRequired = true,
  }) async {
    final http = service;
    try {
      final body = switch (requestType) {
        RequestType.get =>
          await http.get(path, query: query, auth: authenticationRequired),
        RequestType.post =>
          await http.post(path, body: data, auth: authenticationRequired),
        RequestType.put =>
          await http.put(path, body: data, auth: authenticationRequired),
        RequestType.delete =>
          await http.delete(path, body: data, auth: authenticationRequired),
        RequestType.patch =>
          await http.put(path, body: data, auth: authenticationRequired),
      };
      return ResponseData(
        success: true,
        message: '',
        responseCode: 200,
        responseBody: body,
      );
    } on ApiException catch (e) {
      return ResponseData(
        success: false,
        message: e.message,
        responseCode: e.statusCode ?? 0,
        responseBody: e.fieldErrors,
      );
    }
  }

  /// Download raw bytes (e.g. a server-rendered receipt PDF).
  static Future<Uint8List> getBytes(String path,
          {Map<String, dynamic>? query}) =>
      service.getBytes(path, query: query);
}
