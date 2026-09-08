import 'package:flutter_secure_storage/flutter_secure_storage.dart';
import 'package:shared_preferences/shared_preferences.dart';

import '../crash_reporter.dart';
import '../friendly_error.dart';
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

  // ---- secure store access ----
  //
  // Touching the keystore can fail without anything in the app being wrong.
  // Android keeps these values in an encrypted preferences file that outlives
  // the key which opens it, so a reinstall, a device-to-device restore or a
  // screen-lock reset leaves every read throwing `BadPaddingException:
  // BAD_DECRYPT`. That used to escape `main()` — the first read is the auth
  // token, on the boot path — and put a Java stack trace in front of a cashier
  // over a token they could have replaced by signing in again.
  //
  // So an unreadable store is not fatal here: it reads as "nothing stored",
  // which is the same path an expired session already takes, and the ruined
  // entries are dropped so the next launch starts clean.
  static Future<String?> _read(String key) async {
    try {
      return await _secure.read(key: key);
    } catch (e, s) {
      await _recover(e, s, 'read $key');
      return null;
    }
  }

  static Future<void> _write(String key, String value) async {
    try {
      await _secure.write(key: key, value: value);
    } catch (e, s) {
      // Signing in should repair the device rather than fail on it, so a write
      // into a store that can't be opened gets one more go after the wipe.
      final wiped = await _recover(e, s, 'write $key');
      if (!wiped) return;
      try {
        await _secure.write(key: key, value: value);
      } catch (_) {
        // Nothing left to try: this session simply won't survive a restart.
      }
    }
  }

  static Future<void> _delete(String key) async {
    try {
      await _secure.delete(key: key);
    } catch (e, s) {
      await _recover(e, s, 'delete $key');
    }
  }

  /// Records the fault and, when the store is unreadable for good, empties it.
  /// Returns whether it wiped.
  ///
  /// Only [FriendlyError.isSecureStoreUnreadable] earns the wipe. Every value in
  /// here is re-obtainable by signing in, but the device-account roster is what
  /// lets an offline till authenticate anyone at all — too costly to throw away
  /// over a fault that might just be a keystore that wasn't ready yet.
  static Future<bool> _recover(Object e, StackTrace s, String op) async {
    CrashReporter.report(e, s, context: 'secure storage: $op');
    if (!FriendlyError.isSecureStoreUnreadable(e)) return false;
    try {
      await _secure.deleteAll();
      return true;
    } catch (_) {
      // Can't even clear it. Reads keep answering null through the catch above,
      // so the app still starts — at the sign-in screen.
      return false;
    }
  }

  // ---- token (secure) ----
  Future<String?> readToken() => _read(LocalStorageKeys.token);
  Future<void> writeToken(String token) =>
      _write(LocalStorageKeys.token, token);
  Future<void> clearToken() => _delete(LocalStorageKeys.token);

  // ---- device account roster (secure) ----
  // Who has signed in on this device before, so an offline till can still let them
  // back in. Secure storage because it holds their PIN / password and API token —
  // the same class of secret as the biometric credential below, kept the same way.
  Future<String?> readDeviceAccounts() => _read(LocalStorageKeys.deviceAccounts);
  Future<void> writeDeviceAccounts(String json) =>
      _write(LocalStorageKeys.deviceAccounts, json);
  Future<void> clearDeviceAccounts() => _delete(LocalStorageKeys.deviceAccounts);

  // ---- biometric credential (secure) ----
  Future<String?> readBiometric() => _read(LocalStorageKeys.biometric);
  Future<void> writeBiometric(String json) =>
      _write(LocalStorageKeys.biometric, json);
  Future<void> clearBiometric() => _delete(LocalStorageKeys.biometric);

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

  // How the tablet window is framed — an `AstraChrome.id`. Null until the
  // device picks one, so `AstraChrome.fallback` decides the default.
  String? get chromeId => _prefs.getString(LocalStorageKeys.chrome);
  Future<void> setChromeId(String v) =>
      _prefs.setString(LocalStorageKeys.chrome, v);

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

  // How the New Sale category rail draws itself (a `CategoryDisplay.key`).
  // Null until the till picks one, which keeps the plain text chips this
  // screen has always shown.
  String? get posCategoryDisplay =>
      _prefs.getString(LocalStorageKeys.posCategoryDisplay);
  Future<void> setPosCategoryDisplay(String v) =>
      _prefs.setString(LocalStorageKeys.posCategoryDisplay, v);

  // Whether New Sale opens the client form before the catalog. Null until the
  // till picks a side, so the screen keeps its long-standing "ask" behaviour.
  bool? get posAskClient => _prefs.containsKey(LocalStorageKeys.posAskClient)
      ? _prefs.getBool(LocalStorageKeys.posAskClient)
      : null;
  Future<void> setPosAskClient(bool v) =>
      _prefs.setBool(LocalStorageKeys.posAskClient, v);

  // Whether this device offers the tip row at checkout. Null until the till
  // picks a side, so a fresh install follows the web setting alone.
  bool? get posShowTip => _prefs.containsKey(LocalStorageKeys.posShowTip)
      ? _prefs.getBool(LocalStorageKeys.posShowTip)
      : null;
  Future<void> setPosShowTip(bool v) =>
      _prefs.setBool(LocalStorageKeys.posShowTip, v);

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

  // The paired printer. `transport` names the link that carries the job
  // (network / bluetooth / usb / builtin / system — see PrinterTarget), `url`
  // is that transport's address and `name` is what we show. A pairing saved
  // before transports existed has no `transport` key and reads back as
  // `system`, which is exactly what it was. All three move together.
  String? get printerTransport => _prefs.getString(LocalStorageKeys.printerTransport);
  String? get printerUrl => _prefs.getString(LocalStorageKeys.printerUrl);
  String? get printerName => _prefs.getString(LocalStorageKeys.printerName);
  Future<void> setPrinter(String transport, String url, String name) async {
    await _prefs.setString(LocalStorageKeys.printerTransport, transport);
    await _prefs.setString(LocalStorageKeys.printerUrl, url);
    await _prefs.setString(LocalStorageKeys.printerName, name);
  }

  Future<void> clearPrinter() async {
    await _prefs.remove(LocalStorageKeys.printerTransport);
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
