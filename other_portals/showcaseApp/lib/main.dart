import 'package:flutter/material.dart';
import 'package:flutter/services.dart';

import 'app.dart';
import 'shared/utils/router/http_utils/dev_http_stub.dart'
    if (dart.library.io) 'shared/utils/router/http_utils/dev_http_io.dart';
import 'shared/utils/service_locator_setup/setup.dart';

Future<void> main() async {
  WidgetsFlutterBinding.ensureInitialized();
  // The panel belongs to the app.
  //
  // Android was drawing a status bar and a navigation bar over the kiosk, which
  // costs a strip of screen at both ends and — worse — puts Home and Back in
  // front of a customer who is meant to be looking at shoes. Sticky, so the
  // bars come back on a deliberate swipe and hide themselves again: a member of
  // staff can still get out, a browsing customer will not.
  //
  // This is not lock-task mode. True pinning needs the device provisioned as
  // its own owner, which is a decision about the hardware, not the app.
  SystemChrome.setEnabledSystemUIMode(SystemUiMode.immersiveSticky);
  // Product photos go through Flutter's own HttpClient, not Dio, so the dev
  // certificate bypass has to be installed globally as well.
  configureDevHttpOverrides();
  await setUpServiceLocator();
  runApp(const ShowcaseApp());
}
