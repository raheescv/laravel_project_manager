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

/// Install a process-wide [HttpOverrides] (dev only) so EVERY dart:io
/// `HttpClient` — including the one `Image.network` creates — accepts
/// self-signed / untrusted certs. [configureDevHttp] only covers Dio, which is
/// why API calls succeed against a local `.test` HTTPS host while attachment
/// images silently fail TLS. Prod builds skip this entirely.
void installDevHttpOverrides() {
  if (!F.isDev) return;
  HttpOverrides.global = _DevHttpOverrides();
}

class _DevHttpOverrides extends HttpOverrides {
  @override
  HttpClient createHttpClient(SecurityContext? context) =>
      super.createHttpClient(context)
        ..badCertificateCallback = (cert, host, port) => true;
}
