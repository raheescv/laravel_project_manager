import 'package:flutter/foundation.dart';

import '../core/storage.dart';

/// Thermal receipt language / layout. Mirrors the web `thermal_printer_style`
/// config (resources/views/sale/print.blade.php).
enum PrintStyle {
  englishOnly('english_only', 'English only'),
  withArabic('with_arabic', 'With Arabic');

  const PrintStyle(this.key, this.label);
  final String key;
  final String label;

  static PrintStyle fromKey(String? k) =>
      PrintStyle.values.firstWhere((s) => s.key == k, orElse: () => PrintStyle.withArabic);

  bool get isArabic => this == PrintStyle.withArabic;
}

/// Thermal roll width.
enum PaperWidth {
  mm80('80', '80 mm'),
  mm58('58', '58 mm');

  const PaperWidth(this.key, this.label);
  final String key;
  final String label;

  static PaperWidth fromKey(String? k) =>
      PaperWidth.values.firstWhere((w) => w.key == k, orElse: () => PaperWidth.mm80);
}

/// Default footers match database/seeders/ConfigurationSeeder.php so a fresh
/// install prints the same thing the web does.
const _defaultFooterEn =
    'Thank you for shopping! Keep your bill for exchange within 14 days. Terms & Conditions apply.';
const _defaultFooterAr = 'شكرا للتسوق. يُمكن التبديل خلال 14 يومًا. تطبق الشروط والأحكام.';

/// Holds the active thermal-print configuration and persists every change.
/// Read by [buildReceiptPdf] when laying out a receipt.
class PrintSettingsController extends ChangeNotifier {
  PrintSettingsController(this._storage)
      : _style = PrintStyle.fromKey(_storage.printStyle),
        _width = PaperWidth.fromKey(_storage.printWidth),
        _showDiscount = _storage.printDiscount ?? true,
        _showTotalQty = _storage.printTotalQty ?? true,
        _showBarcode = _storage.printBarcode ?? true,
        _footerEnglish = _storage.printFooterEnglish ?? _defaultFooterEn,
        _footerArabic = _storage.printFooterArabic ?? _defaultFooterAr;

  final Storage _storage;

  PrintStyle _style;
  PaperWidth _width;
  bool _showDiscount;
  bool _showTotalQty;
  bool _showBarcode;
  String _footerEnglish;
  String _footerArabic;

  PrintStyle get style => _style;
  PaperWidth get width => _width;
  bool get showDiscount => _showDiscount;
  bool get showTotalQty => _showTotalQty;
  bool get showBarcode => _showBarcode;
  String get footerEnglish => _footerEnglish;
  String get footerArabic => _footerArabic;

  Future<void> setStyle(PrintStyle v) async {
    if (v == _style) return;
    _style = v;
    notifyListeners();
    await _storage.setPrintStyle(v.key);
  }

  Future<void> setWidth(PaperWidth v) async {
    if (v == _width) return;
    _width = v;
    notifyListeners();
    await _storage.setPrintWidth(v.key);
  }

  Future<void> setShowDiscount(bool v) async {
    if (v == _showDiscount) return;
    _showDiscount = v;
    notifyListeners();
    await _storage.setPrintDiscount(v);
  }

  Future<void> setShowTotalQty(bool v) async {
    if (v == _showTotalQty) return;
    _showTotalQty = v;
    notifyListeners();
    await _storage.setPrintTotalQty(v);
  }

  Future<void> setShowBarcode(bool v) async {
    if (v == _showBarcode) return;
    _showBarcode = v;
    notifyListeners();
    await _storage.setPrintBarcode(v);
  }

  Future<void> setFooterEnglish(String v) async {
    if (v == _footerEnglish) return;
    _footerEnglish = v;
    notifyListeners();
    await _storage.setPrintFooterEnglish(v);
  }

  Future<void> setFooterArabic(String v) async {
    if (v == _footerArabic) return;
    _footerArabic = v;
    notifyListeners();
    await _storage.setPrintFooterArabic(v);
  }

  /// Immutable snapshot handed to the receipt builder.
  PrintSettings get snapshot => PrintSettings(
        style: _style,
        width: _width,
        showDiscount: _showDiscount,
        showTotalQty: _showTotalQty,
        showBarcode: _showBarcode,
        footerEnglish: _footerEnglish,
        footerArabic: _footerArabic,
      );
}

/// Plain value object consumed by buildReceiptPdf — keeps the PDF layer free of
/// Provider / ChangeNotifier dependencies.
class PrintSettings {
  const PrintSettings({
    required this.style,
    required this.width,
    required this.showDiscount,
    required this.showTotalQty,
    required this.showBarcode,
    required this.footerEnglish,
    required this.footerArabic,
  });

  final PrintStyle style;
  final PaperWidth width;
  final bool showDiscount;
  final bool showTotalQty;
  final bool showBarcode;
  final String footerEnglish;
  final String footerArabic;
}
