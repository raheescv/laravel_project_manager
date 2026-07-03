/// A typed error surfaced from the API. Mirrors the Laravel `{success,message,data}`
/// envelope and the 401/403/422 handling.
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
