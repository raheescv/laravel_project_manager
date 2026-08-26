import 'package:flutter/material.dart';

import 'app.dart';
import 'shared/utils/router/http_utils/dev_http_stub.dart'
    if (dart.library.io) 'shared/utils/router/http_utils/dev_http_io.dart';
import 'shared/utils/service_locator_setup/setup.dart';

Future<void> main() async {
  WidgetsFlutterBinding.ensureInitialized();
  // Product photos go through Flutter's own HttpClient, not Dio, so the dev
  // certificate bypass has to be installed globally as well.
  configureDevHttpOverrides();
  await setUpServiceLocator();
  runApp(const ShowcaseApp());
}
