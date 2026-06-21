import 'package:flutter/material.dart';
import 'package:go_router/go_router.dart';
import 'package:provider/provider.dart';

import '../core/api_client.dart';
import '../core/api_service.dart';
import '../state/admin_controller.dart';
import '../state/auth_controller.dart';
import '../state/branch_controller.dart';
import '../state/cart_controller.dart';
import '../state/catalog_controller.dart';
import '../state/currency_controller.dart';
import '../state/print_settings_controller.dart';
import '../state/stylist_controller.dart';
import '../state/theme_controller.dart';
import '../theme/theme.dart';
import 'router.dart';

class AstraApp extends StatefulWidget {
  const AstraApp({
    super.key,
    required this.auth,
    required this.themeController,
    required this.currencyController,
    required this.branchController,
    required this.printSettingsController,
    required this.apiService,
    required this.apiClient,
  });

  final AuthController auth;
  final ThemeController themeController;
  final CurrencyController currencyController;
  final BranchController branchController;
  final PrintSettingsController printSettingsController;
  final ApiService apiService;
  final ApiClient apiClient;

  @override
  State<AstraApp> createState() => _AstraAppState();
}

class _AstraAppState extends State<AstraApp> {
  late final GoRouter _router = createRouter(widget.auth);

  @override
  Widget build(BuildContext context) {
    return MultiProvider(
      providers: [
        Provider.value(value: widget.apiClient),
        Provider.value(value: widget.apiService),
        ChangeNotifierProvider.value(value: widget.auth),
        ChangeNotifierProvider.value(value: widget.themeController),
        ChangeNotifierProvider.value(value: widget.currencyController),
        ChangeNotifierProvider.value(value: widget.branchController),
        ChangeNotifierProvider.value(value: widget.printSettingsController),
        ChangeNotifierProvider(create: (_) => CartController()),
        ChangeNotifierProvider(create: (_) => CatalogController(widget.apiService)),
        ChangeNotifierProvider(create: (_) => StylistController(widget.apiService)),
        ChangeNotifierProvider(create: (_) => AdminController(widget.apiService)),
      ],
      // Depend on currency too so a currency change reformats every amount.
      child: Consumer2<ThemeController, CurrencyController>(
        builder: (context, theme, currency, _) => MaterialApp.router(
          title: 'Invo',
          debugShowCheckedModeBanner: false,
          theme: buildAstraTheme(theme.palette),
          routerConfig: _router,
          // Tap anywhere outside a field to dismiss the keyboard. iOS number
          // pads have no return key, so this is the app-wide escape hatch.
          builder: (context, child) => GestureDetector(
            behavior: HitTestBehavior.translucent,
            onTap: () => FocusManager.instance.primaryFocus?.unfocus(),
            child: child,
          ),
        ),
      ),
    );
  }
}
