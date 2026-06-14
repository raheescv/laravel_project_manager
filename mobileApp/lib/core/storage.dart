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
  static const _kCurrency = 'astra.currency';
  static const _kUser = 'astra.user';
  static const _kBiometric = 'astra.biometric'; // JSON credential blob for biometric replay

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

  String? get currencyCode => _prefs.getString(_kCurrency);
  Future<void> setCurrencyCode(String v) => _prefs.setString(_kCurrency, v);

  // ---- cached user json ----
  String? get userJson => _prefs.getString(_kUser);
  Future<void> setUserJson(String v) => _prefs.setString(_kUser, v);
  Future<void> clearUser() => _prefs.remove(_kUser);
}
