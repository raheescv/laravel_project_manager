import 'package:dio/dio.dart';

/// Web/no-op: the browser manages TLS, so there is nothing to configure.
void configureDevHttp(Dio dio) {}

/// Web/no-op.
void configureDevHttpOverrides() {}
