/// All local-storage key strings. Values are kept exactly as the previous
/// `astra.*` keys so existing installs keep their saved token & preferences
/// after the restructure — do NOT rename the string values.
class LocalStorageKeys {
  LocalStorageKeys._();

  // Secure (keystore / keychain).
  static const String token = 'astra.token';
  static const String biometric = 'astra.biometric';

  // Config (shared prefs).
  static const String baseUrl = 'astra.baseUrl';
  static const String tenant = 'astra.tenant';
  static const String preset = 'astra.preset';
  static const String themeMode = 'astra.themeMode';
  static const String currency = 'astra.currency';
  static const String currencies = 'astra.currencies';
  static const String baseCurrency = 'astra.baseCurrency';
  static const String defaultQuantity = 'astra.defaultQuantity';
  static const String branch = 'astra.branch';
  static const String user = 'astra.user';

  // Thermal print settings (mirror the web `thermal_printer_*` config).
  static const String printStyle = 'astra.print.style';
  static const String printWidth = 'astra.print.width';
  static const String printDiscount = 'astra.print.discount';
  static const String printTotalQty = 'astra.print.totalQty';
  static const String printBarcode = 'astra.print.barcode';
  static const String printFooterEn = 'astra.print.footerEn';
  static const String printFooterAr = 'astra.print.footerAr';
}
