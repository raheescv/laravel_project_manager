import 'package:flutter_secure_storage/flutter_secure_storage.dart';
import 'package:shared_preferences/shared_preferences.dart';

import 'keys.dart';

/// Thin persistence layer: the auth token lives in the secure keystore, while
/// non-secret config (base URL, tenant, theme preset) lives in shared prefs.
class LocalStorageService {
  LocalStorageService._(this._prefs);

  final SharedPreferences _prefs;
  static const _secure = FlutterSecureStorage(
    aOptions: AndroidOptions(encryptedSharedPreferences: true),
    // macOS: use the legacy keychain instead of the data-protection keychain
    // (the latter needs an application-identifier entitlement a locally-signed
    // dev build doesn't have).
    mOptions: MacOsOptions(useDataProtectionKeyChain: false),
  );

  static Future<LocalStorageService> create() async =>
      LocalStorageService._(await SharedPreferences.getInstance());

  // ---- token (secure) ----
  Future<String?> readToken() => _secure.read(key: LocalStorageKeys.token);
  Future<void> writeToken(String token) =>
      _secure.write(key: LocalStorageKeys.token, value: token);
  Future<void> clearToken() => _secure.delete(key: LocalStorageKeys.token);

  // ---- biometric credential (secure) ----
  Future<String?> readBiometric() =>
      _secure.read(key: LocalStorageKeys.biometric);
  Future<void> writeBiometric(String json) =>
      _secure.write(key: LocalStorageKeys.biometric, value: json);
  Future<void> clearBiometric() =>
      _secure.delete(key: LocalStorageKeys.biometric);

  // ---- config ----
  String? get baseUrl => _prefs.getString(LocalStorageKeys.baseUrl);
  Future<void> setBaseUrl(String v) =>
      _prefs.setString(LocalStorageKeys.baseUrl, v);

  String? get tenant => _prefs.getString(LocalStorageKeys.tenant);
  Future<void> setTenant(String v) =>
      _prefs.setString(LocalStorageKeys.tenant, v);

  String? get presetId => _prefs.getString(LocalStorageKeys.preset);
  Future<void> setPresetId(String v) =>
      _prefs.setString(LocalStorageKeys.preset, v);

  String? get themeMode => _prefs.getString(LocalStorageKeys.themeMode);
  Future<void> setThemeMode(String v) =>
      _prefs.setString(LocalStorageKeys.themeMode, v);

  String? get currencyCode => _prefs.getString(LocalStorageKeys.currency);
  Future<void> setCurrencyCode(String v) =>
      _prefs.setString(LocalStorageKeys.currency, v);

  String? get currenciesJson => _prefs.getString(LocalStorageKeys.currencies);
  Future<void> setCurrenciesJson(String v) =>
      _prefs.setString(LocalStorageKeys.currencies, v);

  String? get baseCurrencyCode =>
      _prefs.getString(LocalStorageKeys.baseCurrency);
  Future<void> setBaseCurrencyCode(String v) =>
      _prefs.setString(LocalStorageKeys.baseCurrency, v);

  // Sale item default quantity (Settings → Sale Configuration).
  double? get defaultQuantity =>
      _prefs.getDouble(LocalStorageKeys.defaultQuantity);
  Future<void> setDefaultQuantity(double v) =>
      _prefs.setDouble(LocalStorageKeys.defaultQuantity, v);

  // Whether the "Add a Tip" option is enabled (Settings → Sale Configuration).
  bool? get tipEnabled => _prefs.getBool(LocalStorageKeys.tipEnabled);
  Future<void> setTipEnabled(bool v) =>
      _prefs.setBool(LocalStorageKeys.tipEnabled, v);

  // Default POS Product/Service filter (Settings → Sale Configuration).
  // 'product' / 'service' narrow the catalog; '' means All Types.
  String? get defaultProductType =>
      _prefs.getString(LocalStorageKeys.defaultProductType);
  Future<void> setDefaultProductType(String v) =>
      _prefs.setString(LocalStorageKeys.defaultProductType, v);

  // New Sale catalog rendering preference — 'grid' (image tiles) or 'list'.
  String? get saleView => _prefs.getString(LocalStorageKeys.saleView);
  Future<void> setSaleView(String v) =>
      _prefs.setString(LocalStorageKeys.saleView, v);

  // New Sale — last used Product/Service filter ('', 'product', 'service').
  String? get saleType => _prefs.getString(LocalStorageKeys.saleType);
  Future<void> setSaleType(String v) =>
      _prefs.setString(LocalStorageKeys.saleType, v);

  // New Sale — last used staff/stylist, auto-selected on the next ticket.
  int? get saleStylistId => _prefs.getInt(LocalStorageKeys.saleStylistId);
  String? get saleStylistName =>
      _prefs.getString(LocalStorageKeys.saleStylistName);
  Future<void> setSaleStylist(int id, String name) async {
    await _prefs.setInt(LocalStorageKeys.saleStylistId, id);
    await _prefs.setString(LocalStorageKeys.saleStylistName, name);
  }

  // Whether the app-wide haptic tap feedback is enabled (Settings → Haptics).
  bool? get hapticsEnabled => _prefs.getBool(LocalStorageKeys.haptics);
  Future<void> setHapticsEnabled(bool v) =>
      _prefs.setBool(LocalStorageKeys.haptics, v);

  int? get branchId => _prefs.getInt(LocalStorageKeys.branch);
  Future<void> setBranchId(int v) =>
      _prefs.setInt(LocalStorageKeys.branch, v);

  // ---- thermal print settings ----
  String? get printStyle => _prefs.getString(LocalStorageKeys.printStyle);
  Future<void> setPrintStyle(String v) =>
      _prefs.setString(LocalStorageKeys.printStyle, v);

  String? get printWidth => _prefs.getString(LocalStorageKeys.printWidth);
  Future<void> setPrintWidth(String v) =>
      _prefs.setString(LocalStorageKeys.printWidth, v);

  bool? get printDiscount => _prefs.containsKey(LocalStorageKeys.printDiscount)
      ? _prefs.getBool(LocalStorageKeys.printDiscount)
      : null;
  Future<void> setPrintDiscount(bool v) =>
      _prefs.setBool(LocalStorageKeys.printDiscount, v);

  bool? get printTotalQty => _prefs.containsKey(LocalStorageKeys.printTotalQty)
      ? _prefs.getBool(LocalStorageKeys.printTotalQty)
      : null;
  Future<void> setPrintTotalQty(bool v) =>
      _prefs.setBool(LocalStorageKeys.printTotalQty, v);

  bool? get printBarcode => _prefs.containsKey(LocalStorageKeys.printBarcode)
      ? _prefs.getBool(LocalStorageKeys.printBarcode)
      : null;
  Future<void> setPrintBarcode(bool v) =>
      _prefs.setBool(LocalStorageKeys.printBarcode, v);

  String? get printFooterEnglish =>
      _prefs.getString(LocalStorageKeys.printFooterEn);
  Future<void> setPrintFooterEnglish(String v) =>
      _prefs.setString(LocalStorageKeys.printFooterEn, v);

  String? get printFooterArabic =>
      _prefs.getString(LocalStorageKeys.printFooterAr);
  Future<void> setPrintFooterArabic(String v) =>
      _prefs.setString(LocalStorageKeys.printFooterAr, v);

  // Receipt quantity label ('quantity' → Qty, 'weight' → Weight); mirrors the
  // web `print_quantity_label` config.
  String? get printQuantityLabel =>
      _prefs.getString(LocalStorageKeys.printQtyLabel);
  Future<void> setPrintQuantityLabel(String v) =>
      _prefs.setString(LocalStorageKeys.printQtyLabel, v);

  // Company logo on the receipt: show flag (web `enable_logo_in_print`), the
  // server-side version marker and the cached image bytes (base64) so receipts
  // print the logo offline.
  bool? get printLogo => _prefs.containsKey(LocalStorageKeys.printLogo)
      ? _prefs.getBool(LocalStorageKeys.printLogo)
      : null;
  Future<void> setPrintLogo(bool v) =>
      _prefs.setBool(LocalStorageKeys.printLogo, v);

  String? get printLogoVersion =>
      _prefs.getString(LocalStorageKeys.printLogoVersion);
  Future<void> setPrintLogoVersion(String v) =>
      _prefs.setString(LocalStorageKeys.printLogoVersion, v);

  String? get printLogoData =>
      _prefs.getString(LocalStorageKeys.printLogoData);
  Future<void> setPrintLogoData(String v) =>
      _prefs.setString(LocalStorageKeys.printLogoData, v);

  // Company name on the receipt header (web `enable_company_name_in_print` +
  // `company_name` from Company Profile).
  bool? get printShowCompany =>
      _prefs.containsKey(LocalStorageKeys.printShowCompany)
          ? _prefs.getBool(LocalStorageKeys.printShowCompany)
          : null;
  Future<void> setPrintShowCompany(bool v) =>
      _prefs.setBool(LocalStorageKeys.printShowCompany, v);

  String? get printCompanyName =>
      _prefs.getString(LocalStorageKeys.printCompanyName);
  Future<void> setPrintCompanyName(String v) =>
      _prefs.setString(LocalStorageKeys.printCompanyName, v);

  // ---- cached user json ----
  String? get userJson => _prefs.getString(LocalStorageKeys.user);
  Future<void> setUserJson(String v) =>
      _prefs.setString(LocalStorageKeys.user, v);
  Future<void> clearUser() => _prefs.remove(LocalStorageKeys.user);
}
