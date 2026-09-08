import 'dart:async';

import 'package:flutter/material.dart';

import 'app.dart';
import 'flavors.dart';
import 'features/auth/logic/auth_cubit/auth_cubit.dart';
import 'shared/domain/constants/global_variables.dart';
import 'shared/logic/branch_cubit/branch_cubit.dart';
import 'shared/logic/currency_cubit/currency_cubit.dart';
import 'shared/utils/router/http_utils/dev_http_stub.dart'
    if (dart.library.io) 'shared/utils/router/http_utils/dev_http_io.dart';
import 'shared/utils/crash_reporter.dart';
import 'shared/widgets/boot_failure_app.dart';
import 'shared/utils/service_locator_setup/setup.dart';

/// Shared boot sequence. The flavor entry points (`main_dev.dart` /
/// `main_prod.dart`) set `F.appFlavor` then call this; a plain `flutter run`
/// lands here directly and defaults to the dev flavor.
Future<void> main() async {
  F.appFlavor ??= Flavor.dev;
  WidgetsFlutterBinding.ensureInitialized();

  // Before anything else, so a failure during boot is captured too.
  CrashReporter.install();

  // Let Image.network / NetworkImage reach local `.test` HTTPS hosts on a
  // physical device (dev only) — same self-signed-cert bypass Dio already uses.
  configureDevHttpOverrides();

  await _boot();
}

/// Runs the boot and mounts whatever it earns: the app on success, the failure
/// screen on failure.
///
/// [_start] is async, so anything that throws in it escapes `main()` and would
/// otherwise leave the device on a blank white screen. `runApp` happens either
/// way — and the failure screen is handed [_retry], so a fault that clears on a
/// second attempt costs a cashier one tap instead of a force-quit.
Future<void> _boot() async {
  await CrashReporter.guardBoot(
    _start,
    onSuccess: () => runApp(const InvoApp()),
    onBootError: (error, stack) =>
        runApp(BootFailureApp(error: error, onRetry: _retry)),
  );
}

/// Boots again from a clean slate. Whatever the failed attempt managed to
/// register has to go first, or get_it rejects the second registration and the
/// retry fails for a reason that has nothing to do with the original fault.
Future<void> _retry() async {
  await serviceLocator.reset();
  await _boot();
}

Future<void> _start() async {
  await setUpServiceLocator();

  final auth = serviceLocator<AuthCubit>();
  await auth.bootstrap();

  final currency = serviceLocator<CurrencyCubit>();
  final branch = serviceLocator<BranchCubit>();

  // Refresh the cached currency list when already signed in (authenticated
  // endpoint; no-ops offline and the cache is used).
  if (auth.user != null) unawaited(currency.refreshCurrencies());

  // After a fresh sign-in, default the active branch to that user's home
  // branch and pull the latest currency list to cache for offline use.
  auth.onAuthenticated = (user) {
    branch.applyUserDefault(int.tryParse(user.branchId ?? ''));
    currency.refreshCurrencies();
  };
}
