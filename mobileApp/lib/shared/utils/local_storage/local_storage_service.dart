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

  // ---- device account roster (secure) ----
  // Who has signed in on this device before, so an offline till can still let them
  // back in. Secure storage because it holds their PIN / password and API token —
  // the same class of secret as the biometric credential below, kept the same way.
  Future<String?> readDeviceAccounts() =>
      _secure.read(key: LocalStorageKeys.deviceAccounts);
  Future<void> writeDeviceAccounts(String json) =>
      _secure.write(key: LocalStorageKeys.deviceAccounts, value: json);
  Future<void> clearDeviceAccounts() =>
      _secure.delete(key: LocalStorageKeys.deviceAccounts);

  // ---- biometric credential (secure) ----
  Future<String?> readBiometric() =>
      _secure.read(key: LocalStorageKeys.biometric);
  Future<void> writeBiometric(String json) =>
      _secure.write(key: LocalStorageKeys.biometric, value: json);
  Future<void> clearBiometric() =>
      _secure.delete(key: LocalStorageKeys.biometric);

  // ---- terminal lock ----
  // The session survives a lock, so this flag is what stops a force-quit and
  // relaunch from landing straight back inside the app.
  bool get authLocked => _prefs.getBool(LocalStorageKeys.authLocked) ?? false;
  Future<void> setAuthLocked(bool v) =>
      _prefs.setBool(LocalStorageKeys.authLocked, v);

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

  String? get typefaceId => _prefs.getString(LocalStorageKeys.typeface);
  Future<void> setTypefaceId(String v) =>
      _prefs.setString(LocalStorageKeys.typeface, v);

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

  /// The branch the user explicitly picked. Null until they pick one, which is
  /// what lets their home branch still apply — see `BranchCubit.applyUserDefault`.
  int? get branchId => _prefs.getInt(LocalStorageKeys.branch);
  Future<void> setBranchId(int v) =>
      _prefs.setInt(LocalStorageKeys.branch, v);

  /// The branch actually in use, explicit or resolved. The offline fallback.
  int? get lastBranchId => _prefs.getInt(LocalStorageKeys.lastBranch);
  Future<void> setLastBranchId(int v) =>
      _prefs.setInt(LocalStorageKeys.lastBranch, v);

  // ---- point-of-sale flow (device-local) ----
  // Shared-till mode: lock the terminal after every completed sale so the next
  // cashier has to identify themselves.
  bool? get posLockAfterSale =>
      _prefs.containsKey(LocalStorageKeys.posLockAfterSale)
          ? _prefs.getBool(LocalStorageKeys.posLockAfterSale)
          : null;
  Future<void> setPosLockAfterSale(bool v) =>
      _prefs.setBool(LocalStorageKeys.posLockAfterSale, v);

  // How many product tiles New Sale fits across in grid view. Null until the
  // till picks one, so the screen keeps its own default.
  int? get posGridColumns => _prefs.getInt(LocalStorageKeys.posGridColumns);
  Future<void> setPosGridColumns(int v) =>
      _prefs.setInt(LocalStorageKeys.posGridColumns, v);

  // Which screen a signed-in session lands on — a `StartScreen.key`. Null until
  // the till picks one, so the app keeps landing where it always has.
  String? get posStartScreen =>
      _prefs.getString(LocalStorageKeys.posStartScreen);
  Future<void> setPosStartScreen(String v) =>
      _prefs.setString(LocalStorageKeys.posStartScreen, v);

  // ---- offline selling (device-local) ----
  // Short tag identifying this till inside the provisional references it prints,
  // minted once on the first offline sale and never changed after — reusing a
  // tag on another device would let two queues print the same reference.
  String? get offlineDeviceTag =>
      _prefs.getString(LocalStorageKeys.offlineDeviceTag);
  Future<void> setOfflineDeviceTag(String v) =>
      _prefs.setString(LocalStorageKeys.offlineDeviceTag, v);

  // Monotonic counter behind the provisional reference. Never reset: a repeated
  // reference on two receipts is worse than a gap in the sequence.
  int? get offlineSequence => _prefs.getInt(LocalStorageKeys.offlineSequence);
  Future<void> setOfflineSequence(int v) =>
      _prefs.setInt(LocalStorageKeys.offlineSequence, v);

  // Pre-download product photos so the catalog still looks like a catalog with
  // no network. Defaults to on: a grid of blank tiles is the failure people
  // actually notice, and a till that cannot afford the storage can turn it off.
  bool get offlineCachePhotos =>
      _prefs.getBool(LocalStorageKeys.offlineCachePhotos) ?? true;
  Future<void> setOfflineCachePhotos(bool v) =>
      _prefs.setBool(LocalStorageKeys.offlineCachePhotos, v);

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

  // ---- auto-print (device-local) ----
  // Print the receipt the moment a sale is charged, on the printer this till
  // is paired with — so the cashier never taps Print.
  bool? get printAuto => _prefs.containsKey(LocalStorageKeys.printAuto)
      ? _prefs.getBool(LocalStorageKeys.printAuto)
      : null;
  Future<void> setPrintAuto(bool v) =>
      _prefs.setBool(LocalStorageKeys.printAuto, v);

  // The paired printer: `url` is the platform's identifier, `name` is what we
  // show. Both cleared together when the pairing is removed.
  String? get printerUrl => _prefs.getString(LocalStorageKeys.printerUrl);
  String? get printerName => _prefs.getString(LocalStorageKeys.printerName);
  Future<void> setPrinter(String url, String name) async {
    await _prefs.setString(LocalStorageKeys.printerUrl, url);
    await _prefs.setString(LocalStorageKeys.printerName, name);
  }

  Future<void> clearPrinter() async {
    await _prefs.remove(LocalStorageKeys.printerUrl);
    await _prefs.remove(LocalStorageKeys.printerName);
  }

  bool? get printSkipInvoice =>
      _prefs.containsKey(LocalStorageKeys.printSkipInvoice)
          ? _prefs.getBool(LocalStorageKeys.printSkipInvoice)
          : null;
  Future<void> setPrintSkipInvoice(bool v) =>
      _prefs.setBool(LocalStorageKeys.printSkipInvoice, v);

  // ---- cached user json ----
  String? get userJson => _prefs.getString(LocalStorageKeys.user);
  Future<void> setUserJson(String v) =>
      _prefs.setString(LocalStorageKeys.user, v);
  Future<void> clearUser() => _prefs.remove(LocalStorageKeys.user);
}
