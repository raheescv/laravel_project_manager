import 'package:flutter/material.dart';

import 'app.dart';
import 'flavors.dart';
import 'features/auth/logic/auth_cubit/auth_cubit.dart';
import 'shared/domain/constants/global_variables.dart';
import 'shared/logic/branch_cubit/branch_cubit.dart';
import 'shared/logic/currency_cubit/currency_cubit.dart';
import 'shared/utils/service_locator_setup/setup.dart';

/// Shared boot sequence. The flavor entry points (`main_dev.dart` /
/// `main_prod.dart`) set `F.appFlavor` then call this; a plain `flutter run`
/// lands here directly and defaults to the dev flavor.
Future<void> main() async {
  F.appFlavor ??= Flavor.dev;
  WidgetsFlutterBinding.ensureInitialized();

  await setUpServiceLocator();

  final auth = serviceLocator<AuthCubit>();
  await auth.bootstrap();

  final currency = serviceLocator<CurrencyCubit>();
  final branch = serviceLocator<BranchCubit>();

  // Refresh the cached currency list when already signed in (authenticated
  // endpoint; no-ops offline and the cache is used).
  if (auth.user != null) currency.refreshCurrencies();

  // After a fresh sign-in, default the active branch to that user's home branch
  // and pull the latest currency list to cache for offline use.
  auth.onAuthenticated = (user) {
    branch.applyUserDefault(int.tryParse(user.branchId ?? ''));
    currency.refreshCurrencies();
  };

  runApp(const InvoApp());
}
