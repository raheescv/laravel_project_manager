import 'package:flutter/material.dart';

import 'app/app.dart';
import 'core/api_client.dart';
import 'core/api_service.dart';
import 'core/config.dart';
import 'core/storage.dart';
import 'state/auth_controller.dart';
import 'state/branch_controller.dart';
import 'state/currency_controller.dart';
import 'state/print_settings_controller.dart';
import 'state/theme_controller.dart';

Future<void> main() async {
  WidgetsFlutterBinding.ensureInitialized();

  final storage = await Storage.create();
  final config = AppConfig.resolve(
    savedBaseUrl: storage.baseUrl,
    savedTenant: storage.tenant,
  );

  // Startup diagnostic: confirms whether env.json was compiled in. If
  // env.baseUrl is empty here, the --dart-define-from-file flag did NOT reach
  // this build (you're running without the flag or it's a stale build).
  debugPrint('[AstraConfig] env.baseUrl="${AppConfig.envBaseUrl}" '
      'env.tenant="${AppConfig.envTenant}" env.host="${AppConfig.envHostHeader}" '
      '-> active baseUrl=${config.baseUrl} tenant=${config.tenant} '
      'host=${config.hostHeader}');

  final client = ApiClient(storage: storage, config: config);
  final service = ApiService(client);
  final auth = AuthController(client: client, service: service, storage: storage);
  await auth.bootstrap();

  final theme = ThemeController(storage);
  final currency = CurrencyController(storage);
  final branch = BranchController(
    service: service,
    client: client,
    storage: storage,
    userBranchId: int.tryParse(auth.user?.branchId ?? ''),
  );
  // After a fresh sign-in, default the active branch to that user's home branch.
  auth.onAuthenticated = (user) => branch.applyUserDefault(int.tryParse(user.branchId ?? ''));
  final printSettings = PrintSettingsController(storage);

  runApp(AstraApp(
    auth: auth,
    themeController: theme,
    currencyController: currency,
    branchController: branch,
    printSettingsController: printSettings,
    apiService: service,
    apiClient: client,
  ));
}
