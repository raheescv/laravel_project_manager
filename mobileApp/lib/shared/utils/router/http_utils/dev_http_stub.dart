import 'package:dio/dio.dart';

/// Web/no-op: nothing to configure (the browser manages TLS).
void configureDevHttp(Dio dio) {}

/// Web/no-op: the browser manages TLS, so there is nothing to override.
void configureDevHttpOverrides() {}
