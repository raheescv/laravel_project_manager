import 'package:get_it/get_it.dart';

/// The one service-locator instance. Every dependency is resolved through this,
/// never constructed at a call site — see `setUpServiceLocator`.
final GetIt serviceLocator = GetIt.instance;
