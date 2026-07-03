import 'package:flutter/material.dart';
import 'package:flutter_dotenv/flutter_dotenv.dart';

import 'app.dart';
import 'flavors.dart';
import 'features/auth/logic/auth_cubit/auth_cubit.dart';
import 'shared/domain/constants/global_variables.dart';
import 'shared/utils/service_locator_setup/setup.dart';

/// Boot sequence for the Technician app. A plain `flutter run` lands here and
/// defaults to the dev flavor.
Future<void> main() async {
  F.appFlavor ??= Flavor.dev;
  WidgetsFlutterBinding.ensureInitialized();

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
