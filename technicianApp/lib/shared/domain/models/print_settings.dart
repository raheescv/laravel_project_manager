/// Thermal receipt language / layout. Mirrors the web `thermal_printer_style`
/// config (resources/views/sale/print.blade.php).
enum PrintStyle {
  englishOnly('english_only', 'English only'),
  withArabic('with_arabic', 'With Arabic');

  const PrintStyle(this.key, this.label);
  final String key;
  final String label;

  static PrintStyle fromKey(String? k) => PrintStyle.values
      .firstWhere((s) => s.key == k, orElse: () => PrintStyle.withArabic);

  bool get isArabic => this == PrintStyle.withArabic;
}

/// Thermal roll width.
enum PaperWidth {
  mm80('80', '80 mm'),
  mm58('58', '58 mm');

  const PaperWidth(this.key, this.label);
  final String key;
  final String label;

  static PaperWidth fromKey(String? k) => PaperWidth.values
      .firstWhere((w) => w.key == k, orElse: () => PaperWidth.mm80);
}

/// Default footers match database/seeders/ConfigurationSeeder.php so a fresh
/// install prints the same thing the web does.
const String defaultPrintFooterEn =
    'Thank you for shopping! Keep your bill for exchange within 14 days. Terms & Conditions apply.';
const String defaultPrintFooterAr =
    'شكرا للتسوق. يُمكن التبديل خلال 14 يومًا. تطبق الشروط والأحكام.';

/// Plain value object consumed by `buildReceiptPdf` — keeps the PDF layer free
/// of state-management dependencies.
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
