import 'dart:io';

import 'package:dio/dio.dart';
import 'package:dio/io.dart';
import 'package:flutter/foundation.dart';

/// On native platforms in **debug builds only**, accept self-signed TLS
/// certificates. This is what makes a local `.test` HTTPS host (Herd/Valet)
/// reachable from a simulator or a device during development.
///
/// Release builds keep full certificate validation — the bypass never ships.
void configureDevHttp(Dio dio) {
  if (!kDebugMode) return;
  dio.httpClientAdapter = IOHttpClientAdapter(
    createHttpClient: () => HttpClient()
      ..badCertificateCallback = (cert, host, port) => true,
  );
}

/// Route Flutter's **default** `HttpClient` — which `Image.network` uses — through
/// the same bypass. Without it every product photo fails on a local `.test` host
/// even though the API calls succeed, because only Dio was configured.
void configureDevHttpOverrides() {
  if (!kDebugMode) return;
  HttpOverrides.global = _DevHttpOverrides();
}

class _DevHttpOverrides extends HttpOverrides {
  @override
  HttpClient createHttpClient(SecurityContext? context) =>
      super.createHttpClient(context)
        ..badCertificateCallback = (cert, host, port) => true;
}
