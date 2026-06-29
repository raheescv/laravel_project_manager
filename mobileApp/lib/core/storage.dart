import 'package:flutter_secure_storage/flutter_secure_storage.dart';
import 'package:shared_preferences/shared_preferences.dart';

/// Thin persistence layer: the auth token lives in the secure keystore, while
/// non-secret config (base URL, tenant, theme preset) lives in shared prefs.
class Storage {
  Storage._(this._prefs);

  final SharedPreferences _prefs;
  static const _secure = FlutterSecureStorage(
    aOptions: AndroidOptions(encryptedSharedPreferences: true),
    // macOS: use the legacy keychain instead of the data-protection keychain.
    // The data-protection keychain requires an application-identifier /
    // keychain-access-groups entitlement (i.e. Apple dev-team signing); on a
    // locally-signed dev build without it, secure writes fail with
    // errSecMissingEntitlement (-34018). The legacy keychain needs no entitlement
    // (paired with the App Sandbox being disabled for Debug — see
    // macos/Runner/DebugProfile.entitlements).
    mOptions: MacOsOptions(useDataProtectionKeyChain: false),
  );

  static const _kToken = 'astra.token';
  static const _kBaseUrl = 'astra.baseUrl';
  static const _kTenant = 'astra.tenant';
  static const _kPreset = 'astra.preset';
  static const _kThemeMode = 'astra.themeMode'; // light | dark | system
  static const _kCurrency = 'astra.currency';
  static const _kCurrencies = 'astra.currencies'; // JSON list synced from web settings
  static const _kBaseCurrency = 'astra.baseCurrency'; // base currency code
  static const _kBranch = 'astra.branch';
  static const _kUser = 'astra.user';
  static const _kBiometric = 'astra.biometric'; // JSON credential blob for biometric replay
  // ---- thermal print settings (mirror the web `thermal_printer_*` config) ----
  static const _kPrintStyle = 'astra.print.style'; // english_only | with_arabic
  static const _kPrintWidth = 'astra.print.width'; // 58 | 80
  static const _kPrintDiscount = 'astra.print.discount'; // 1 | 0
  static const _kPrintTotalQty = 'astra.print.totalQty'; // 1 | 0
  static const _kPrintBarcode = 'astra.print.barcode'; // 1 | 0
  static const _kPrintFooterEn = 'astra.print.footerEn';
  static const _kPrintFooterAr = 'astra.print.footerAr';

  static Future<Storage> create() async =>
      Storage._(await SharedPreferences.getInstance());

  // ---- token (secure) ----
  Future<String?> readToken() => _secure.read(key: _kToken);
  Future<void> writeToken(String token) => _secure.write(key: _kToken, value: token);
  Future<void> clearToken() => _secure.delete(key: _kToken);

  // ---- biometric credential (secure) ----
  Future<String?> readBiometric() => _secure.read(key: _kBiometric);
  Future<void> writeBiometric(String json) => _secure.write(key: _kBiometric, value: json);
  Future<void> clearBiometric() => _secure.delete(key: _kBiometric);

  // ---- config ----
  String? get baseUrl => _prefs.getString(_kBaseUrl);
  Future<void> setBaseUrl(String v) => _prefs.setString(_kBaseUrl, v);

  String? get tenant => _prefs.getString(_kTenant);
  Future<void> setTenant(String v) => _prefs.setString(_kTenant, v);

  String? get presetId => _prefs.getString(_kPreset);
  Future<void> setPresetId(String v) => _prefs.setString(_kPreset, v);

  String? get themeMode => _prefs.getString(_kThemeMode);
  Future<void> setThemeMode(String v) => _prefs.setString(_kThemeMode, v);

  String? get currencyCode => _prefs.getString(_kCurrency);
  Future<void> setCurrencyCode(String v) => _prefs.setString(_kCurrency, v);

  /// Cached currency list (JSON array) + base code, fetched from the web so the
  /// app can format and convert amounts offline.
  String? get currenciesJson => _prefs.getString(_kCurrencies);
  Future<void> setCurrenciesJson(String v) => _prefs.setString(_kCurrencies, v);

  String? get baseCurrencyCode => _prefs.getString(_kBaseCurrency);
  Future<void> setBaseCurrencyCode(String v) => _prefs.setString(_kBaseCurrency, v);

  int? get branchId => _prefs.getInt(_kBranch);
  Future<void> setBranchId(int v) => _prefs.setInt(_kBranch, v);

  // ---- thermal print settings ----
  String? get printStyle => _prefs.getString(_kPrintStyle);
  Future<void> setPrintStyle(String v) => _prefs.setString(_kPrintStyle, v);

  String? get printWidth => _prefs.getString(_kPrintWidth);
  Future<void> setPrintWidth(String v) => _prefs.setString(_kPrintWidth, v);

  bool? get printDiscount => _prefs.containsKey(_kPrintDiscount) ? _prefs.getBool(_kPrintDiscount) : null;
  Future<void> setPrintDiscount(bool v) => _prefs.setBool(_kPrintDiscount, v);

  bool? get printTotalQty => _prefs.containsKey(_kPrintTotalQty) ? _prefs.getBool(_kPrintTotalQty) : null;
  Future<void> setPrintTotalQty(bool v) => _prefs.setBool(_kPrintTotalQty, v);

  bool? get printBarcode => _prefs.containsKey(_kPrintBarcode) ? _prefs.getBool(_kPrintBarcode) : null;
  Future<void> setPrintBarcode(bool v) => _prefs.setBool(_kPrintBarcode, v);

  String? get printFooterEnglish => _prefs.getString(_kPrintFooterEn);
  Future<void> setPrintFooterEnglish(String v) => _prefs.setString(_kPrintFooterEn, v);

  String? get printFooterArabic => _prefs.getString(_kPrintFooterAr);
  Future<void> setPrintFooterArabic(String v) => _prefs.setString(_kPrintFooterAr, v);

  // ---- cached user json ----
  String? get userJson => _prefs.getString(_kUser);
  Future<void> setUserJson(String v) => _prefs.setString(_kUser, v);
  Future<void> clearUser() => _prefs.remove(_kUser);
}
