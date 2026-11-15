/// The normalised result of an API call, mirroring the Laravel
/// `{success, message, data}` envelope. Produced by [HttpHelper].
class ResponseData {
  ResponseData({
    required this.success,
    required this.message,
    required this.responseCode,
    required this.responseBody,
  });

  final bool success;
  final String message;
  final int responseCode;

  /// The unwrapped `data` payload — a Map, List or scalar depending on endpoint.
  final dynamic responseBody;
}
