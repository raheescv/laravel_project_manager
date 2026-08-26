/// A typed error surfaced from the API. Mirrors the Laravel
/// `{success, message, data}` envelope.
class ApiException implements Exception {
  ApiException(this.message, {this.statusCode});

  final String message;
  final int? statusCode;

  /// The public catalog aborts with 404 and a tenant-flavoured message when no
  /// tenant resolves. That is a configuration problem, not a missing record, and
  /// the UI says so instead of showing "not found".
  bool get isTenantUnresolved =>
      statusCode == 404 && message.toLowerCase().contains('tenant');

  bool get isNotFound => statusCode == 404;

  @override
  String toString() => message;
}

/// Raised when no request reached the server at all.
class OfflineException extends ApiException {
  OfflineException()
      : super('Cannot reach the store. Check the connection and try again.');
}
