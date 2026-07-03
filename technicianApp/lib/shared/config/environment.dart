import 'package:flutter_dotenv/flutter_dotenv.dart';

/// Environment configuration — loads values from .env file.
class Environment {
  Environment._();

  // API Configuration
  static String get apiBaseUrl => dotenv.env['API_BASE_URL'] ?? 'http://localhost:8000';
  static String get apiVersion => dotenv.env['API_VERSION'] ?? 'v1';

  // LAN Configuration (for local development on physical devices)
  static String get apiBaseUrlLan => dotenv.env['API_BASE_URL_LAN'] ?? 'http://192.168.68.104:8090';
  static String get apiTenantLan => dotenv.env['API_TENANT_LAN'] ?? 'project_manager';
  static bool get useLanApi => _parseBool(dotenv.env['USE_LAN_API'] ?? 'false');

  /// Get the active API base URL based on configuration.
  /// Returns LAN URL if USE_LAN_API is true, otherwise returns production URL.
  static String get activeApiBaseUrl => useLanApi ? apiBaseUrlLan : apiBaseUrl;

  // App Configuration
  static String get appName => dotenv.env['APP_NAME'] ?? 'FixMate';
  static String get appVersion => dotenv.env['APP_VERSION'] ?? '1.0.0';

  // Feature Flags
  static bool get enableBiometricLogin => _parseBool(dotenv.env['ENABLE_BIOMETRIC_LOGIN'] ?? 'true');
  static bool get enableOfflineMode => _parseBool(dotenv.env['ENABLE_OFFLINE_MODE'] ?? 'true');
  static bool get enableReceiptPrinting => _parseBool(dotenv.env['ENABLE_RECEIPT_PRINTING'] ?? 'true');

  // Logging
  static bool get enableLogging => _parseBool(dotenv.env['ENABLE_LOGGING'] ?? 'true');
  static String get logLevel => dotenv.env['LOG_LEVEL'] ?? 'debug';

  /// Helper to parse boolean string values.
  static bool _parseBool(String value) {
    return value.toLowerCase() == 'true' || value == '1';
  }
}
