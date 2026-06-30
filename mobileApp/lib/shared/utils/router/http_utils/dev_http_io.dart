import 'dart:io';

import 'package:dio/dio.dart';
import 'package:dio/io.dart';

import '../../../../flavors.dart';

/// On native platforms in **dev** builds only, accept self-signed / untrusted
/// TLS certificates. This is what makes local `.test` HTTPS hosts (Herd/Valet)
/// reachable from the app during development.
///
/// Prod builds keep full certificate validation — the bypass never ships.
void configureDevHttp(Dio dio) {
  if (!F.isDev) return;
  dio.httpClientAdapter = IOHttpClientAdapter(
    createHttpClient: () {
      final client = HttpClient();
      client.badCertificateCallback = (cert, host, port) => true;
      return client;
    },
  );
}
