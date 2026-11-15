// On a wide pane (≥560pt) the Account hero lays its two buttons out in a
// stretched Column beside the identity. That Column sat straight in a Row, which
// hands its children unbounded width, so "BoxConstraints forces an infinite
// width" fired and the whole Settings/Profile pane failed to lay out on tablets.
import 'package:flutter/material.dart';
import 'package:flutter_bloc/flutter_bloc.dart';
import 'package:flutter_test/flutter_test.dart';
import 'package:google_fonts/google_fonts.dart';
import 'package:shared_preferences/shared_preferences.dart';

import 'package:invo/features/auth/logic/auth_cubit/auth_cubit.dart';
import 'package:invo/features/profile/widgets/v3/account_overview.dart';
import 'package:invo/shared/domain/constants/app_config.dart';
import 'package:invo/shared/domain/constants/global_variables.dart';
import 'package:invo/shared/utils/components/theme/palette.dart';
import 'package:invo/shared/utils/components/theme/theme_manager.dart';
import 'package:invo/shared/utils/local_storage/local_storage_service.dart';
import 'package:invo/shared/utils/router/http_utils/http_service.dart';

import 'support/sample_data.dart';

void main() {
  setUpAll(() async {
    GoogleFonts.config.allowRuntimeFetching = false;
    SharedPreferences.setMockInitialValues({});
    final storage = await LocalStorageService.create();
    if (!serviceLocator.isRegistered<HttpService>()) {
      serviceLocator.registerSingleton<HttpService>(HttpService(
        storage: storage,
        config: AppConfig(baseUrl: 'https://192.168.68.101', tenant: 'project_manager'),
      ));
    }
  });

  for (final (label, width) in [('phone', 390.0), ('narrow tablet pane', 620.0), ('tablet', 1194.0)]) {
    testWidgets('the account overview lays out without errors ($label)', (tester) async {
      tester.view.physicalSize = Size(width, 1400);
      tester.view.devicePixelRatio = 1;
      addTearDown(tester.view.reset);

      final auth = AuthCubit()..user = sampleUser();
      addTearDown(auth.close);

      await tester.pumpWidget(BlocProvider<AuthCubit>.value(
        value: auth,
        child: MaterialApp(
          theme: buildAstraTheme(AstraPresets.auroraGlass),
          home: Scaffold(
            body: SingleChildScrollView(
              child: AccountOverview(onEditProfile: () {}, onChangePin: () {}, onChangePassword: () {}),
            ),
          ),
        ),
      ));
      await tester.pump();

      expect(tester.takeException(), isNull);
      expect(find.text('Edit profile'), findsOneWidget);
      expect(find.text('Change photo'), findsOneWidget);
      expect(find.text('Sign out'), findsOneWidget);
    });
  }
}
