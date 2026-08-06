part of 'print_settings_cubit.dart';

/// State for [PrintSettingsCubit] — the §5 shape.
///
/// Mixes two origins deliberately: the shared web Sale Configuration (style,
/// footers, show-on-receipt toggles, company/logo) and the device-local choices
/// (paper width, auto-print, paired printer). See the cubit doc.
class PrintSettingsState extends Equatable {
  const PrintSettingsState({
    required this.style,
    required this.width,
    required this.quantityLabel,
    this.showDiscount = true,
    this.showTotalQty = true,
    this.showBarcode = true,
    this.footerEnglish = '',
    this.footerArabic = '',
    this.showLogo = false,
    this.logoBytes,
    this.showCompanyName = false,
    this.companyName = '',
    this.autoPrint = false,
    this.printerUrl,
    this.printerName,
    this.skipInvoice = false,
  });

  final PrintStyle style;
  final PaperWidth width;
  final QuantityLabel quantityLabel;
  final bool showDiscount;
  final bool showTotalQty;
  final bool showBarcode;
  final String footerEnglish;
  final String footerArabic;
  final bool showLogo;
  final Uint8List? logoBytes;
  final bool showCompanyName;
  final String companyName;
  final bool autoPrint;
  final String? printerUrl;
  final String? printerName;
  final bool skipInvoice;

  /// True once this till is paired with a printer we can drive without a dialog.
  bool get hasPrinter => (printerUrl ?? '').isNotEmpty;

  /// What `buildReceiptPdf` lays a receipt out from.
  PrintSettings get snapshot => PrintSettings(
        style: style,
        width: width,
        showDiscount: showDiscount,
        showTotalQty: showTotalQty,
        showBarcode: showBarcode,
        footerEnglish: footerEnglish,
        footerArabic: footerArabic,
        quantityLabel: quantityLabel,
        logo: showLogo ? logoBytes : null,
        companyName: showCompanyName ? companyName : '',
      );

  PrintSettingsState copyWith({
    PrintStyle? style,
    PaperWidth? width,
    QuantityLabel? quantityLabel,
    bool? showDiscount,
    bool? showTotalQty,
    bool? showBarcode,
    String? footerEnglish,
    String? footerArabic,
    bool? showLogo,
    Uint8List? logoBytes,
    bool? showCompanyName,
    String? companyName,
    bool? autoPrint,
    String? printerUrl,
    String? printerName,
    bool? skipInvoice,
    bool clearPrinter = false,
  }) =>
      PrintSettingsState(
        style: style ?? this.style,
        width: width ?? this.width,
        quantityLabel: quantityLabel ?? this.quantityLabel,
        showDiscount: showDiscount ?? this.showDiscount,
        showTotalQty: showTotalQty ?? this.showTotalQty,
        showBarcode: showBarcode ?? this.showBarcode,
        footerEnglish: footerEnglish ?? this.footerEnglish,
        footerArabic: footerArabic ?? this.footerArabic,
        showLogo: showLogo ?? this.showLogo,
        logoBytes: logoBytes ?? this.logoBytes,
        showCompanyName: showCompanyName ?? this.showCompanyName,
        companyName: companyName ?? this.companyName,
        autoPrint: autoPrint ?? this.autoPrint,
        printerUrl: clearPrinter ? null : (printerUrl ?? this.printerUrl),
        printerName: clearPrinter ? null : (printerName ?? this.printerName),
        skipInvoice: skipInvoice ?? this.skipInvoice,
      );

  @override
  List<Object?> get props => [
        style, width, quantityLabel, showDiscount, showTotalQty, showBarcode,
        footerEnglish, footerArabic, showLogo, logoBytes, showCompanyName,
        companyName, autoPrint, printerUrl, printerName, skipInvoice,
      ];
}
