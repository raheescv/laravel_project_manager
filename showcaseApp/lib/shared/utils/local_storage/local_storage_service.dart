import 'package:shared_preferences/shared_preferences.dart';

/// Device-local preferences. Nothing here is customer data — it is the branch
/// the tablet stands in, the grid density the staff prefer, and the theme mode.
class LocalStorageService {
  LocalStorageService._(this._prefs);

  static Future<LocalStorageService> create() async =>
      LocalStorageService._(await SharedPreferences.getInstance());

  final SharedPreferences _prefs;

  static const _kBranchId = 'branch_id';
  static const _kThemeMode = 'theme_mode';
  static const _kLocale = 'locale';
  static const _kTextScale = 'text_scale';
  static const _kTypeface = 'typeface';
  static const _kLightPreset = 'theme_preset_light';
  static const _kDarkPreset = 'theme_preset_dark';
  static const _kGridColumns = 'grid_columns';
  static const _kBaseUrl = 'base_url';
  static const _kTenant = 'tenant';
  static const _kSpinHintSeen = 'spin_hint_seen';
  static const _kReservations = 'reservations';

  int? get branchId => _prefs.getInt(_kBranchId);
  Future<void> setBranchId(int id) => _prefs.setInt(_kBranchId, id);

  /// 'system' | 'light' | 'dark'
  String get themeMode => _prefs.getString(_kThemeMode) ?? 'system';
  Future<void> setThemeMode(String mode) => _prefs.setString(_kThemeMode, mode);

  /// 'en' | 'ar', or null to follow the device.
  String? get locale => _prefs.getString(_kLocale);
  Future<void> setLocale(String? code) =>
      code == null ? _prefs.remove(_kLocale) : _prefs.setString(_kLocale, code);

  /// How large the type is set, as a multiplier. 0 means never chosen.
  double get textScale => _prefs.getDouble(_kTextScale) ?? 0;
  Future<void> setTextScale(double value) => _prefs.setDouble(_kTextScale, value);

  /// Which typeface pairing the app is set in.
  String? get typeface => _prefs.getString(_kTypeface);
  Future<void> setTypeface(String name) => _prefs.setString(_kTypeface, name);

  /// Which palette dresses each mode. Null until the staff pick one, so the
  /// default lives with the theme rather than being duplicated here.
  String? get lightPreset => _prefs.getString(_kLightPreset);
  Future<void> setLightPreset(String name) => _prefs.setString(_kLightPreset, name);

  String? get darkPreset => _prefs.getString(_kDarkPreset);
  Future<void> setDarkPreset(String name) => _prefs.setString(_kDarkPreset, name);

  int get gridColumns => _prefs.getInt(_kGridColumns) ?? 0; // 0 = auto
  Future<void> setGridColumns(int n) => _prefs.setInt(_kGridColumns, n);

  String? get baseUrl => _prefs.getString(_kBaseUrl);
  String? get tenant => _prefs.getString(_kTenant);

  /// The "drag to spin" coach mark shows once per device, not once per product.
  bool get spinHintSeen => _prefs.getBool(_kSpinHintSeen) ?? false;
  Future<void> setSpinHintSeen() => _prefs.setBool(_kSpinHintSeen, true);

  /// What a customer asked to be held, as a line of text per note. Local only —
  /// there is no reservation endpoint and the showcase never writes to the
  /// server. Kept to the last 50 so the store cannot grow without bound.
  List<String> get reservations => _prefs.getStringList(_kReservations) ?? const [];

  Future<void> addReservation({
    required int productId,
    required String productName,
    String? size,
    int? branchId,
  }) async {
    final note = [
      DateTime.now().toIso8601String(),
      '#\$productId',
      productName,
      if (size != null) 'size \$size',
      if (branchId != null) 'branch \$branchId',
    ].join(' · ');
    final rows = [note, ...reservations];
    await _prefs.setStringList(
      _kReservations,
      rows.length > 50 ? rows.sublist(0, 50) : rows,
    );
  }
}
