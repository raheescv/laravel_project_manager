// Example usage of Environment configuration throughout the app

import 'package:invo/shared/config/environment.dart';

/// Example: Using environment variables in API client setup
class ApiClientExample {
  static String getBaseUrl() {
    // Returns LAN URL if USE_LAN_API=true, otherwise production URL
    return Environment.activeApiBaseUrl;
  }

  static String getProductionBaseUrl() {
    return Environment.apiBaseUrl;
  }

  static String getLanBaseUrl() {
    return Environment.apiBaseUrlLan;
  }

  static String getLanTenant() {
    return Environment.apiTenantLan;
  }

  static bool isUsingLanApi() {
    return Environment.useLanApi;
  }

  static String getApiVersion() {
    return Environment.apiVersion;
  }
}

/// Example: Using feature flags in widgets
class FeatureFlagsExample {
  static bool shouldEnableBiometric() {
    return Environment.enableBiometricLogin;
  }

  static bool shouldEnableOffline() {
    return Environment.enableOfflineMode;
  }

  static bool shouldEnablePrinting() {
    return Environment.enableReceiptPrinting;
  }
}

/// Example: Using app configuration
class AppConfigExample {
  static String getAppTitle() {
    return Environment.appName;
  }

  static String getAppVersion() {
    return Environment.appVersion;
  }
}

/// Example: Using logging configuration
class LoggerExample {
  static bool isLoggingEnabled() {
    return Environment.enableLogging;
  }

  static String getLogLevel() {
    return Environment.logLevel;
  }
}
