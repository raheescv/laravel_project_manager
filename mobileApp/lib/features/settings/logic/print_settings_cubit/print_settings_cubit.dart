import 'dart:convert';
import 'dart:typed_data';

import 'package:invo/shared/domain/constants/global_variables.dart';
import 'package:invo/shared/domain/models/index.dart';
import 'package:invo/shared/domain/repository/lookup_repository.dart';
import 'package:invo/shared/logic/base/holder_cubit.dart';
import 'package:invo/shared/utils/local_storage/local_storage_service.dart';

// Re-export the print value objects so screens importing the cubit also get
// PrintStyle / PaperWidth / PrintSettings (they used to live together).
export 'package:invo/shared/domain/models/print_settings.dart';

/// Holds the active thermal-print configuration and persists every change.
/// Read by `buildReceiptPdf` (via [snapshot]) when laying out a receipt.
///
/// The web Settings → Sale Configuration is the source of truth for language,
/// footers, the show-on-receipt toggles and the Qty/Weight label — they sync
/// down via [applyRemote] (New Sale open / [syncFromServer]) and are cached
/// for offline. The set* methods edit that shared configuration from the app
/// (PUT /settings/sale/print, needs `configuration.settings`): applied
/// optimistically and rolled back when the server rejects the save. Only the
/// paper width is a device-local choice.
class PrintSettingsCubit extends HolderCubit {
  PrintSettingsCubit() {
    _style = PrintStyle.fromKey(_storage.printStyle);
    _width = PaperWidth.fromKey(_storage.printWidth);
    _showDiscount = _storage.printDiscount ?? true;
    _showTotalQty = _storage.printTotalQty ?? true;
    _showBarcode = _storage.printBarcode ?? true;
    _footerEnglish = _storage.printFooterEnglish ?? defaultPrintFooterEn;
    _footerArabic = _storage.printFooterArabic ?? defaultPrintFooterAr;
    _quantityLabel = QuantityLabel.fromKey(_storage.printQuantityLabel);
    // Off until the first sync says otherwise — matches the web defaults.
    _showLogo = _storage.printLogo ?? false;
    _showCompanyName = _storage.printShowCompany ?? false;
    _companyName = _storage.printCompanyName ?? '';
    try {
      final data = _storage.printLogoData;
      _logoBytes = data == null ? null : base64Decode(data);
    } catch (_) {
      _logoBytes = null; // corrupt cache — re-downloaded on next sync
    }
  }

  LocalStorageService get _storage => serviceLocator<LocalStorageService>();
  LookupRepository get _lookup => serviceLocator<LookupRepository>();

  late PrintStyle _style;
  late PaperWidth _width;
  late bool _showDiscount;
  late bool _showTotalQty;
  late bool _showBarcode;
  late String _footerEnglish;
  late String _footerArabic;
  late QuantityLabel _quantityLabel;
  late bool _showLogo;
  Uint8List? _logoBytes;
  late bool _showCompanyName;
  late String _companyName;

  PrintStyle get style => _style;
  PaperWidth get width => _width;
  bool get showDiscount => _showDiscount;
  bool get showTotalQty => _showTotalQty;
  bool get showBarcode => _showBarcode;
  String get footerEnglish => _footerEnglish;
  String get footerArabic => _footerArabic;
  QuantityLabel get quantityLabel => _quantityLabel;
  bool get showLogo => _showLogo;
  bool get showCompanyName => _showCompanyName;
  String get companyName => _companyName;

  /// Paper width is the one device-local option (each till can hold a
  /// different roll).
  Future<void> setWidth(PaperWidth v) async {
    if (v == _width) return;
    _width = v;
    refresh();
    await _storage.setPrintWidth(v.key);
  }

  // ---- shared-config editors (write through to the web) ----

  /// Applies + persists [value] immediately (click-and-go), then pushes [body]
  /// to the web Sale Configuration. Rolls back and returns false when the
  /// server rejects the save (offline / no permission) so the UI can say so.
  Future<bool> _set<T>({
    required T value,
    required T current,
    required void Function(T) assign,
    required Future<void> Function(T) store,
    required Map<String, dynamic> body,
  }) async {
    if (value == current) return true;
    assign(value);
    refresh();
    await store(value);
    try {
      final echo = await _lookup.savePrintSettings(body);
      await applyRemote(echo); // server-normalised values, usually a no-op
      return true;
    } catch (_) {
      assign(current);
      refresh();
      await store(current);
      return false;
    }
  }

  Future<bool> setStyle(PrintStyle v) => _set(
      value: v,
      current: _style,
      assign: (x) => _style = x,
      store: (x) => _storage.setPrintStyle(x.key),
      body: {'style': v.key});

  Future<bool> setQuantityLabel(QuantityLabel v) => _set(
      value: v,
      current: _quantityLabel,
      assign: (x) => _quantityLabel = x,
      store: (x) => _storage.setPrintQuantityLabel(x.key),
      body: {'quantity_label': v.key});

  Future<bool> setShowDiscount(bool v) => _set(
      value: v,
      current: _showDiscount,
      assign: (x) => _showDiscount = x,
      store: _storage.setPrintDiscount,
      body: {'show_discount': v});

  Future<bool> setShowTotalQty(bool v) => _set(
      value: v,
      current: _showTotalQty,
      assign: (x) => _showTotalQty = x,
      store: _storage.setPrintTotalQty,
      body: {'show_total_quantity': v});

  Future<bool> setShowBarcode(bool v) => _set(
      value: v,
      current: _showBarcode,
      assign: (x) => _showBarcode = x,
      store: _storage.setPrintBarcode,
      body: {'show_barcode': v});

  Future<bool> setShowLogo(bool v) async {
    final ok = await _set(
        value: v,
        current: _showLogo,
        assign: (x) => _showLogo = x,
        store: _storage.setPrintLogo,
        body: {'show_logo': v});
    // Just enabled with no cached image yet — fetch it so the very next
    // receipt already carries the logo (applyRemote above only downloads
    // when the version changes).
    if (ok && v && _logoBytes == null) {
      try {
        final bytes = await _lookup.logo();
        if (bytes.isNotEmpty) {
          _logoBytes = bytes;
          await _storage.setPrintLogoData(base64Encode(bytes));
          refresh();
        }
      } catch (_) {
        // Offline — picked up by the next sync.
      }
    }
    return ok;
  }

  Future<bool> setShowCompanyName(bool v) => _set(
      value: v,
      current: _showCompanyName,
      assign: (x) => _showCompanyName = x,
      store: _storage.setPrintShowCompany,
      body: {'show_company_name': v});

  /// Saves both footer messages in one round-trip (the edit sheet submits
  /// them together).
  Future<bool> setFooters({required String english, required String arabic}) async {
    if (english == _footerEnglish && arabic == _footerArabic) return true;
    final prevEn = _footerEnglish, prevAr = _footerArabic;
    _footerEnglish = english;
    _footerArabic = arabic;
    refresh();
    await _storage.setPrintFooterEnglish(english);
    await _storage.setPrintFooterArabic(arabic);
    try {
      final echo = await _lookup.savePrintSettings({'footer_english': english, 'footer_arabic': arabic});
      await applyRemote(echo);
      return true;
    } catch (_) {
      _footerEnglish = prevEn;
      _footerArabic = prevAr;
      refresh();
      await _storage.setPrintFooterEnglish(prevEn);
      await _storage.setPrintFooterArabic(prevAr);
      return false;
    }
  }

  /// Applies the print block of GET /settings/sale and caches it, so the app
  /// receipt follows the web Sale Configuration. Null fields (key absent in
  /// the response) keep the cached value.
  Future<void> applyRemote(RemotePrintConfig? r) async {
    if (r == null) return;
    var changed = false;

    if (r.styleKey != null) {
      final v = PrintStyle.fromKey(r.styleKey);
      if (v != _style) {
        _style = v;
        await _storage.setPrintStyle(v.key);
        changed = true;
      }
    }
    if (r.showDiscount != null && r.showDiscount != _showDiscount) {
      _showDiscount = r.showDiscount!;
      await _storage.setPrintDiscount(_showDiscount);
      changed = true;
    }
    if (r.showTotalQty != null && r.showTotalQty != _showTotalQty) {
      _showTotalQty = r.showTotalQty!;
      await _storage.setPrintTotalQty(_showTotalQty);
      changed = true;
    }
    if (r.showBarcode != null && r.showBarcode != _showBarcode) {
      _showBarcode = r.showBarcode!;
      await _storage.setPrintBarcode(_showBarcode);
      changed = true;
    }
    // An empty string is a deliberate blank footer on the web — apply it;
    // only a missing key keeps the cache/default.
    final fe = r.footerEnglish;
    if (fe != null && fe != _footerEnglish) {
      _footerEnglish = fe;
      await _storage.setPrintFooterEnglish(fe);
      changed = true;
    }
    final fa = r.footerArabic;
    if (fa != null && fa != _footerArabic) {
      _footerArabic = fa;
      await _storage.setPrintFooterArabic(fa);
      changed = true;
    }
    if (r.quantityLabelKey != null) {
      final v = QuantityLabel.fromKey(r.quantityLabelKey);
      if (v != _quantityLabel) {
        _quantityLabel = v;
        await _storage.setPrintQuantityLabel(v.key);
        changed = true;
      }
    }
    if (r.showLogo != null && r.showLogo != _showLogo) {
      _showLogo = r.showLogo!;
      await _storage.setPrintLogo(_showLogo);
      changed = true;
    }
    if (r.showCompanyName != null && r.showCompanyName != _showCompanyName) {
      _showCompanyName = r.showCompanyName!;
      await _storage.setPrintShowCompany(_showCompanyName);
      changed = true;
    }
    final company = r.companyName;
    if (company != null && company != _companyName) {
      _companyName = company;
      await _storage.setPrintCompanyName(company);
      changed = true;
    }
    // (Re-)download the logo image when the web one changed or the cache is
    // empty; the version is only stored on success so a failed download is
    // retried on the next sync.
    final version = r.logoVersion;
    if (_showLogo &&
        version != null &&
        (version != _storage.printLogoVersion || _logoBytes == null)) {
      try {
        final bytes = await _lookup.logo();
        if (bytes.isNotEmpty) {
          _logoBytes = bytes;
          await _storage.setPrintLogoData(base64Encode(bytes));
          await _storage.setPrintLogoVersion(version);
          changed = true;
        }
      } catch (_) {
        // Offline or server error — keep the cached image.
      }
    }
    if (changed) refresh();
  }

  /// Pulls the latest print configuration from the server. Called when the
  /// Printer & Receipt screen opens; no-ops offline (cached values are kept).
  Future<void> syncFromServer() async {
    try {
      final settings = await _lookup.saleSettings();
      await applyRemote(settings.print);
    } catch (_) {
      // Offline or server error — keep the cached values.
    }
  }

  PrintSettings get snapshot => PrintSettings(
        style: _style,
        width: _width,
        showDiscount: _showDiscount,
        showTotalQty: _showTotalQty,
        showBarcode: _showBarcode,
        footerEnglish: _footerEnglish,
        footerArabic: _footerArabic,
        quantityLabel: _quantityLabel,
        logo: _showLogo ? _logoBytes : null,
        companyName: _showCompanyName ? _companyName : '',
      );
}
