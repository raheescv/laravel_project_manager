// Widget-test harness: registers the app's service locator against fakes,
// stubs the platform channels the app touches, and pumps either the whole app
// or a single screen at a chosen size and brightness.
import 'dart:convert';
import 'dart:io';

import 'package:flutter/foundation.dart';
import 'package:flutter/material.dart';
import 'package:flutter/services.dart';
import 'package:flutter_bloc/flutter_bloc.dart';
import 'package:flutter_test/flutter_test.dart';
import 'package:go_router/go_router.dart';
import 'package:google_fonts/google_fonts.dart';
import 'package:shared_preferences/shared_preferences.dart';

import 'package:invo/app.dart';
import 'package:invo/features/auth/domain/repository/auth_repository.dart';
import 'package:invo/features/auth/logic/auth_cubit/auth_cubit.dart';
import 'package:invo/features/checklist/domain/repository/checklist_repository.dart';
import 'package:invo/features/checklist/logic/checklist_detail_cubit/checklist_detail_cubit.dart';
import 'package:invo/features/checklist/logic/checklist_inbox_cubit/checklist_inbox_cubit.dart';
import 'package:invo/features/profile/domain/repository/profile_repository.dart';
import 'package:invo/features/profile/logic/profile_cubit/profile_cubit.dart';
import 'package:invo/features/technician/domain/repository/technician_repository.dart';
import 'package:invo/features/technician/logic/complaints_cubit/complaints_cubit.dart';
import 'package:invo/features/technician/logic/dashboard_cubit/dashboard_cubit.dart';
import 'package:invo/shared/domain/constants/app_config.dart';
import 'package:invo/shared/domain/constants/global_variables.dart';
import 'package:invo/shared/logic/haptics_cubit/haptics_cubit.dart';
import 'package:invo/shared/logic/theme_cubit/theme_cubit.dart';
import 'package:invo/shared/utils/components/haptics.dart';
import 'package:invo/shared/utils/components/theme/index.dart';
import 'package:invo/shared/utils/local_storage/local_storage_service.dart';
import 'package:invo/shared/utils/router/http_utils/http_service.dart';

import 'fakes.dart';
import 'sample_data.dart';

const phoneSize = Size(390, 844);
const tabletSize = Size(1194, 834);
const wideSize = Size(1366, 1024);

/// Everything a test may want to poke at after setup.
class TestEnv {
  TestEnv({required this.auth, required this.tech, required this.checklist, required this.profile});

  final FakeAuthRepository auth;
  final FakeTechnicianRepository tech;
  final FakeChecklistRepository checklist;
  final FakeProfileRepository profile;

  AuthCubit get authCubit => serviceLocator<AuthCubit>();
  ChecklistInboxCubit get inbox => serviceLocator<ChecklistInboxCubit>();
}

/// Platform channels the app calls that have no plugin under `flutter test`.
void _stubChannels() {
  final messenger = TestDefaultBinaryMessengerBinding.instance.defaultBinaryMessenger;
  String? clipboard;
  messenger.setMockMethodCallHandler(SystemChannels.platform, (call) async {
    if (call.method == 'Clipboard.setData') clipboard = (call.arguments as Map)['text'] as String?;
    if (call.method == 'Clipboard.getData') return {'text': clipboard};
    return null;
  });
  // In-memory keychain, so the token / biometric credential round-trip.
  secureStore.clear();
  messenger.setMockMethodCallHandler(const MethodChannel('plugins.it_nomads.com/flutter_secure_storage'), (call) async {
    final args = Map<String, dynamic>.from(call.arguments as Map? ?? const {});
    final key = args['key'] as String?;
    switch (call.method) {
      case 'write':
        secureStore[key!] = args['value'] as String;
        return null;
      case 'read':
        return secureStore[key];
      case 'delete':
        secureStore.remove(key);
        return null;
      case 'containsKey':
        return secureStore.containsKey(key);
      default:
        return null;
    }
  });
}

/// The fake keychain behind `flutter_secure_storage` (reset per test).
final secureStore = <String, String>{};

/// Registers the service locator against fakes. [signedIn] puts a sample user
/// on [AuthCubit]; [dark] persists the dark mode before ThemeCubit reads it.
Future<TestEnv> setUpEnv({
  bool signedIn = true,
  bool dark = false,
  FakeTechnicianRepository? tech,
  FakeChecklistRepository? checklist,
}) async {
  GoogleFonts.config.allowRuntimeFetching = false;
  Haptics.enabled = false;
  _stubChannels();
  SharedPreferences.setMockInitialValues({if (dark) 'astra.themeMode': 'dark'});
  await serviceLocator.reset();

  final storage = await LocalStorageService.create();
  final http = HttpService(storage: storage, config: AppConfig(baseUrl: 'https://demo.example.com', tenant: 'demo'));
  final env = TestEnv(
    auth: FakeAuthRepository(),
    tech: tech ?? FakeTechnicianRepository(),
    checklist: checklist ?? FakeChecklistRepository(),
    profile: FakeProfileRepository(),
  );

  serviceLocator
    ..registerSingleton<LocalStorageService>(storage)
    ..registerSingleton<HttpService>(http)
    ..registerSingleton<AuthRepository>(env.auth)
    ..registerSingleton<TechnicianRepository>(env.tech)
    ..registerSingleton<ChecklistRepository>(env.checklist)
    ..registerSingleton<ProfileRepository>(env.profile)
    ..registerLazySingleton<AuthCubit>(AuthCubit.new)
    ..registerLazySingleton<ThemeCubit>(ThemeCubit.new)
    ..registerLazySingleton<HapticsCubit>(HapticsCubit.new)
    ..registerLazySingleton<ChecklistInboxCubit>(() => ChecklistInboxCubit(serviceLocator()))
    ..registerFactory<ChecklistDetailCubit>(() => ChecklistDetailCubit(serviceLocator()))
    ..registerFactory<ProfileCubit>(() => ProfileCubit(serviceLocator(), serviceLocator()));

  final auth = serviceLocator<AuthCubit>();
  if (signedIn) {
    auth
      ..user = sampleUser()
      ..status = AuthStatus.signedIn;
  } else {
    auth.status = AuthStatus.signedOut;
  }
  return env;
}

void setViewSize(WidgetTester tester, Size size) {
  tester.view.physicalSize = size;
  tester.view.devicePixelRatio = 1;
  addTearDown(tester.view.reset);
}

/// Advances time enough for fake async loads, debounces and transitions,
/// without waiting for indefinite animations (skeleton pulses, spinners).
Future<void> settle(WidgetTester tester, {int frames = 12, Duration step = const Duration(milliseconds: 100)}) async {
  for (var i = 0; i < frames; i++) {
    await tester.pump(step);
  }
}

/// The whole app — real router, shell and providers.
Future<void> pumpApp(WidgetTester tester, {Size size = phoneSize}) async {
  setViewSize(tester, size);
  await tester.pumpWidget(const TechnicianApp());
  await settle(tester);
}

/// One screen inside a router (so `context.push` / `canPop` work) with the
/// app-level cubits provided. Unknown routes render `route:<uri>` so a test
/// can assert where a tap navigated.
Future<void> pumpScreen(
  WidgetTester tester,
  Widget screen, {
  Size size = phoneSize,
  List<BlocProvider> providers = const [],
  bool settleAfter = true,
  bool pushed = false,
}) async {
  setViewSize(tester, size);
  // [pushed]: the screen sits on top of a host page, so it can pop back.
  final router = GoRouter(
    initialLocation: pushed ? '/test/screen' : '/test',
    routes: [
      GoRoute(
        path: '/test',
        builder: (_, __) => pushed ? const Scaffold(body: Center(child: Text('host page'))) : screen,
        routes: [GoRoute(path: 'screen', builder: (_, __) => screen)],
      ),
    ],
    errorBuilder: (_, state) => Scaffold(body: Center(child: Text('route:${state.uri}'))),
  );
  addTearDown(router.dispose);
  await tester.pumpWidget(MultiBlocProvider(
    providers: [
      BlocProvider.value(value: serviceLocator<AuthCubit>()),
      BlocProvider.value(value: serviceLocator<ThemeCubit>()),
      BlocProvider.value(value: serviceLocator<HapticsCubit>()),
      BlocProvider.value(value: serviceLocator<ChecklistInboxCubit>()),
      BlocProvider(create: (_) => TechnicianDashboardCubit()),
      BlocProvider(create: (_) => ComplaintsCubit()),
      ...providers,
    ],
    child: BlocBuilder<ThemeCubit, ThemeState>(
      builder: (context, theme) => MaterialApp.router(
        debugShowCheckedModeBanner: false,
        theme: buildAstraTheme(theme.palette, theme.typeface),
        routerConfig: router,
      ),
    ),
  ));
  if (settleAfter) await settle(tester);
}

/// Fails with every pending framework exception, not just the first — an
/// overflow often hides a second, more useful error behind it.
void expectNoErrors(WidgetTester tester) {
  final errors = <Object>[];
  Object? e;
  while ((e = tester.takeException()) != null) {
    errors.add(e!);
  }
  expect(errors, isEmpty, reason: errors.join('\n---\n'));
}

/// The signature capture pad (its painter type is private to the app).
final signaturePad = find.byWidgetPredicate(
    (w) => w is CustomPaint && w.painter != null && w.painter.runtimeType.toString() == '_SignaturePainter');

/// Opt-in for tests that let real time pass (`tester.runAsync`, e.g. to wait
/// on an isolate). Under fake time google_fonts' font loads never finish; given
/// real time they finish, find no bundled font file, and fail the test. This
/// serves every face the app can ask for from the repo's IBM Plex font (the same
/// face `flutter_test_config.dart` registers) and every other asset as the test
/// binding does. Call before the first pump.
void serveGoogleFontsFromBundle() {
  const families = ['Manrope', 'Marcellus', 'Inter', 'IBM Plex Sans', 'Hanken Grotesk', 'Fraunces', 'Roboto'];
  const weights = {
    100: 'Thin', 200: 'ExtraLight', 300: 'Light', 400: 'Regular', 500: 'Medium',
    600: 'SemiBold', 700: 'Bold', 800: 'ExtraBold', 900: 'Black',
  };
  final regular = File('assets/fonts/IBMPlexSansArabic-Regular.ttf').readAsBytesSync();
  final bold = File('assets/fonts/IBMPlexSansArabic-Bold.ttf').readAsBytesSync();
  final fonts = <String, Uint8List>{
    for (final family in families)
      for (final w in weights.entries)
        for (final italic in [false, true])
          'test_fonts/${family.replaceAll(' ', '')}-${italic ? (w.key == 400 ? 'Italic' : '${w.value}Italic') : w.value}.ttf':
              w.key >= 600 ? bold : regular,
  };

  final folder = Platform.environment['UNIT_TEST_ASSETS'];
  Uint8List? bundled(String key) {
    final file = File('$folder/$key');
    return folder != null && file.existsSync() ? file.readAsBytesSync() : null;
  }

  TestDefaultBinaryMessengerBinding.instance.defaultBinaryMessenger.setMockMessageHandler('flutter/assets', (message) {
    final key = utf8.decode(message!.buffer.asUint8List(message.offsetInBytes, message.lengthInBytes));
    Uint8List? bytes;
    if (key == 'AssetManifest.bin') {
      const codec = StandardMessageCodec();
      final original = bundled(key);
      final manifest = <Object?, Object?>{
        if (original != null) ...codec.decodeMessage(ByteData.sublistView(original)) as Map<Object?, Object?>,
        for (final path in fonts.keys) path: [<String, Object?>{'asset': path}],
      };
      final encoded = codec.encodeMessage(manifest)!;
      bytes = encoded.buffer.asUint8List(encoded.offsetInBytes, encoded.lengthInBytes);
    } else {
      bytes = fonts[key] ?? bundled(key);
    }
    return bytes == null ? null : SynchronousFuture(ByteData.sublistView(bytes));
  });
}
