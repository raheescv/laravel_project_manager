import 'flavors.dart';
import 'main.dart' as runner;

/// Prod flavor entry point.
Future<void> main() async {
  F.appFlavor = Flavor.prod;
  await runner.main();
}
