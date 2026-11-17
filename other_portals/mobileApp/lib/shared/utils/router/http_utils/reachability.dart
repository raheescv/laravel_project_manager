import 'package:dio/dio.dart';

import '../../components/app_strings.dart';
import 'common_exception.dart';

/// The message to show a user for [error].
///
/// A screen's own wording ("Could not load sales.") is right when the server
/// answered and something went wrong, and useless when the real problem is that
/// there is no connection — one is a bug to report, the other is something the
/// person holding the device can actually fix. [fallback] is used for
/// everything that is not a network failure.
String networkErrorMessage(Object error, String fallback) =>
    isServerUnreachable(error) ? AppStrings.couldNotReachServer : fallback;

/// Whether [error] means the request never got an answer — no route to the host,
/// a timeout, a dropped socket.
///
/// This is the app's single definition of "the server could not be reached", and
/// it is deliberately narrow. A `5xx` is excluded: the server WAS reached, so it
/// may well have acted on the request, and treating that as offline would let
/// the POS queue a sale it might already have committed.
///
/// An [ApiException] is never a connection failure — by the time one is thrown
/// the envelope has been read, which means the server answered.
bool isServerUnreachable(Object error) {
  if (error is ApiException) return false;
  if (error is! DioException) return false;
  return switch (error.type) {
    DioExceptionType.connectionError ||
    DioExceptionType.connectionTimeout ||
    DioExceptionType.sendTimeout ||
    DioExceptionType.receiveTimeout =>
      true,
    // `unknown` wraps whatever the transport threw. A SocketException lands here;
    // a FormatException means bytes arrived and could not be parsed, which is a
    // reachable server sending something unexpected.
    DioExceptionType.unknown => error.error is! FormatException,
    _ => false,
  };
}
