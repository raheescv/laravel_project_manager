import 'package:flutter/material.dart';
import 'package:flutter_dotenv/flutter_dotenv.dart';

import 'app.dart';
import 'flavors.dart';
import 'features/auth/logic/auth_cubit/auth_cubit.dart';
import 'shared/domain/constants/global_variables.dart';
import 'shared/utils/service_locator_setup/setup.dart';
// Native-only dev TLS handling; no-op on web.
import 'shared/utils/router/http_utils/dev_http_stub.dart'
    if (dart.library.io) 'shared/utils/router/http_utils/dev_http_io.dart';

/// Boot sequence for the Technician app. A plain `flutter run` lands here and
/// defaults to the dev flavor.
Future<void> main() async {
  F.appFlavor ??= Flavor.dev;
  WidgetsFlutterBinding.ensureInitialized();

  // Dev only: let every HttpClient (incl. the one Image.network uses) accept
  // self-signed certs so `.test` attachment images load. No-op in prod / web.
  installDevHttpOverrides();

  // Local dev API URL override — see `.env` / `.env.example`. Never let a
  // missing/malformed file block boot: AppConfig falls back to the saved or
  // default URL when `.env` isn't loaded.
  try {
    await dotenv.load();
  } catch (_) {
    // No .env bundled (e.g. release build) — fall through to other sources.
  }

  await setUpServiceLocator();

  final auth = serviceLocator<AuthCubit>();
  await auth.bootstrap();

  runApp(const TechnicianApp());
}
