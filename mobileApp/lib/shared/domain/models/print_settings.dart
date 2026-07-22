import 'dart:typed_data';

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

/// What the receipt's quantity column/total row is called. Mirrors the web
/// `print_quantity_label` config ('quantity' → Qty, 'weight' → Weight); the
/// Arabic label stays الكمية either way, matching print.blade.php.
enum QuantityLabel {
  quantity('quantity', 'Qty', 'Total Qty'),
  weight('weight', 'Weight', 'Total Weight');

  const QuantityLabel(this.key, this.column, this.total);
  final String key;
  final String column;
  final String total;

  static QuantityLabel fromKey(String? k) => QuantityLabel.values
      .firstWhere((q) => q.key == k, orElse: () => QuantityLabel.quantity);
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
    this.quantityLabel = QuantityLabel.quantity,
    this.logo,
    this.companyName = '',
  });

  final PrintStyle style;
  final PaperWidth width;
  final bool showDiscount;
  final bool showTotalQty;
  final bool showBarcode;
  final String footerEnglish;
  final String footerArabic;
  final QuantityLabel quantityLabel;

  /// Company logo bytes (png/jpg/svg) for the receipt header, or null to skip
  /// — already gated on the web `enable_logo_in_print` flag by the cubit.
  final Uint8List? logo;

  /// Company name printed above the branch line, or '' to skip — already
  /// gated on the web `enable_company_name_in_print` flag by the cubit.
  final String companyName;
}

/// The thermal-print block of GET /settings/sale — the web Sale Configuration
/// is the source of truth for these; the app only caches them for offline.
/// Null fields mean the key was absent from the response (keep the cache).
class RemotePrintConfig {
  const RemotePrintConfig({
    this.styleKey,
    this.footerEnglish,
    this.footerArabic,
    this.showDiscount,
    this.showTotalQty,
    this.showBarcode,
    this.showLogo,
    this.logoVersion,
    this.showCompanyName,
    this.companyName,
    this.quantityLabelKey,
  });

  factory RemotePrintConfig.fromJson(Map<String, dynamic> j) => RemotePrintConfig(
        styleKey: j['style']?.toString(),
        footerEnglish: j['footer_english']?.toString(),
        footerArabic: j['footer_arabic']?.toString(),
        showDiscount: j['show_discount'] is bool ? j['show_discount'] as bool : null,
        showTotalQty: j['show_total_quantity'] is bool ? j['show_total_quantity'] as bool : null,
        showBarcode: j['show_barcode'] is bool ? j['show_barcode'] as bool : null,
        showLogo: j['show_logo'] is bool ? j['show_logo'] as bool : null,
        logoVersion: j['logo_version']?.toString(),
        showCompanyName: j['show_company_name'] is bool ? j['show_company_name'] as bool : null,
        companyName: j['company_name']?.toString(),
        quantityLabelKey: j['quantity_label']?.toString(),
      );

  final String? styleKey;
  final String? footerEnglish;
  final String? footerArabic;
  final bool? showDiscount;
  final bool? showTotalQty;
  final bool? showBarcode;
  final bool? showLogo;

  /// Opaque marker that changes when a new logo is uploaded on the web —
  /// tells the app to re-download GET /settings/logo.
  final String? logoVersion;
  final bool? showCompanyName;
  final String? companyName;
  final String? quantityLabelKey;
}
