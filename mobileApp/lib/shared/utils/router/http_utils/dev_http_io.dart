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

/// Route Flutter's **default** `HttpClient` — used by `Image.network` /
/// `NetworkImage` and any non-Dio networking — through the same self-signed-cert
/// bypass in dev builds. Without this, product/asset images fail to load from a
/// local `.test` HTTPS host on a physical device: the Valet/Herd dev CA is
/// trusted on the build Mac (so images render there) but not on the phone, so
/// only Dio — which already bypasses cert checks — could reach the server.
///
/// Prod builds keep full certificate validation — the override never ships.
void configureDevHttpOverrides() {
  if (!F.isDev) return;
  HttpOverrides.global = _DevHttpOverrides();
}

class _DevHttpOverrides extends HttpOverrides {
  @override
  HttpClient createHttpClient(SecurityContext? context) {
    return super.createHttpClient(context)
      ..badCertificateCallback = (cert, host, port) => true;
  }
}
