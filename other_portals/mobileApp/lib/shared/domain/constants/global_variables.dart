import 'package:get_it/get_it.dart';

/// App-wide service locator. Register dependencies in
/// `shared/utils/service_locator_setup/setup.dart`; resolve them anywhere with
/// `serviceLocator<T>()`.
final serviceLocator = GetIt.instance;
