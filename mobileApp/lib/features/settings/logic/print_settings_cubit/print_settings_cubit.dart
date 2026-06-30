import 'package:invo/shared/domain/constants/global_variables.dart';
import 'package:invo/shared/domain/models/index.dart';
import 'package:invo/shared/logic/base/holder_cubit.dart';
import 'package:invo/shared/utils/local_storage/local_storage_service.dart';

// Re-export the print value objects so screens importing the cubit also get
// PrintStyle / PaperWidth / PrintSettings (they used to live together).
export 'package:invo/shared/domain/models/print_settings.dart';

/// Holds the active thermal-print configuration and persists every change.
/// Read by `buildReceiptPdf` (via [snapshot]) when laying out a receipt.
class PrintSettingsCubit extends HolderCubit {
  PrintSettingsCubit() {
    _style = PrintStyle.fromKey(_storage.printStyle);
    _width = PaperWidth.fromKey(_storage.printWidth);
    _showDiscount = _storage.printDiscount ?? true;
    _showTotalQty = _storage.printTotalQty ?? true;
    _showBarcode = _storage.printBarcode ?? true;
    _footerEnglish = _storage.printFooterEnglish ?? defaultPrintFooterEn;
    _footerArabic = _storage.printFooterArabic ?? defaultPrintFooterAr;
  }

  LocalStorageService get _storage => serviceLocator<LocalStorageService>();

  late PrintStyle _style;
  late PaperWidth _width;
  late bool _showDiscount;
  late bool _showTotalQty;
  late bool _showBarcode;
  late String _footerEnglish;
  late String _footerArabic;

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
    refresh();
    await _storage.setPrintStyle(v.key);
  }

  Future<void> setWidth(PaperWidth v) async {
    if (v == _width) return;
    _width = v;
    refresh();
    await _storage.setPrintWidth(v.key);
  }

  Future<void> setShowDiscount(bool v) async {
    if (v == _showDiscount) return;
    _showDiscount = v;
    refresh();
    await _storage.setPrintDiscount(v);
  }

  Future<void> setShowTotalQty(bool v) async {
    if (v == _showTotalQty) return;
    _showTotalQty = v;
    refresh();
    await _storage.setPrintTotalQty(v);
  }

  Future<void> setShowBarcode(bool v) async {
    if (v == _showBarcode) return;
    _showBarcode = v;
    refresh();
    await _storage.setPrintBarcode(v);
  }

  Future<void> setFooterEnglish(String v) async {
    if (v == _footerEnglish) return;
    _footerEnglish = v;
    refresh();
    await _storage.setPrintFooterEnglish(v);
  }

  Future<void> setFooterArabic(String v) async {
    if (v == _footerArabic) return;
    _footerArabic = v;
    refresh();
    await _storage.setPrintFooterArabic(v);
  }

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
