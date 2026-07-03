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

  // Load environment variables from .env file
  await dotenv.load();

  await setUpServiceLocator();

  final auth = serviceLocator<AuthCubit>();
  await auth.bootstrap();

  runApp(const TechnicianApp());
}
