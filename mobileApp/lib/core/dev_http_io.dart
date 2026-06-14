import 'dart:io';

import 'package:dio/dio.dart';
import 'package:dio/io.dart';
import 'package:flutter/foundation.dart';

/// On native platforms in **debug builds only**, accept self-signed / untrusted
/// TLS certificates. This is what makes local `.test` HTTPS hosts (Herd/Valet,
/// which use a locally-generated cert) reachable from the app during dev.
///
/// Release builds keep full certificate validation — the bypass never ships.
void configureDevHttp(Dio dio) {
  if (!kDebugMode) return;
  dio.httpClientAdapter = IOHttpClientAdapter(
    createHttpClient: () {
      final client = HttpClient();
      client.badCertificateCallback = (cert, host, port) => true;
      return client;
    },
  );
}
