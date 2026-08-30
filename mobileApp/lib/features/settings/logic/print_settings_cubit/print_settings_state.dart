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
    this.autoPrint = true,
    this.printer = PrinterTarget.none,
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

  /// The printer this till prints to, and over which link.
  final PrinterTarget printer;
  final bool skipInvoice;

  /// True once this till has a printer chosen at all.
  bool get hasPrinter => printer.isPaired;

  /// True when receipts reach paper with no dialog and no tap — the only case
  /// where auto-print is genuinely hands-off, and so the only case where
  /// skipping the invoice screen makes sense.
  bool get printsSilently => printer.isDirect;

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
    PrinterTarget? printer,
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
        printer: clearPrinter ? PrinterTarget.none : (printer ?? this.printer),
        skipInvoice: skipInvoice ?? this.skipInvoice,
      );

  @override
  List<Object?> get props => [
        style, width, quantityLabel, showDiscount, showTotalQty, showBarcode,
        footerEnglish, footerArabic, showLogo, logoBytes, showCompanyName,
        companyName, autoPrint, printer, skipInvoice,
      ];
}
