import 'flavors.dart';
import 'main.dart' as runner;

/// Dev flavor entry point.
Future<void> main() async {
  F.appFlavor = Flavor.dev;
  await runner.main();
}
