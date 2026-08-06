import 'dart:convert';
import 'dart:typed_data';

import 'package:invo/shared/domain/constants/global_variables.dart';
import 'package:invo/shared/domain/models/index.dart';
import 'package:invo/shared/domain/repository/lookup_repository.dart';
import 'package:equatable/equatable.dart';
import 'package:flutter_bloc/flutter_bloc.dart';
import 'package:invo/shared/utils/local_storage/local_storage_service.dart';

// Re-export the print value objects so screens importing the cubit also get
// PrintStyle / PaperWidth / PrintSettings (they used to live together).
export 'package:invo/shared/domain/models/print_settings.dart';

part 'print_settings_state.dart';

/// Holds the active thermal-print configuration and persists every change.
/// Read by `buildReceiptPdf` (via [snapshot]) when laying out a receipt.
///
/// The web Settings → Sale Configuration is the source of truth for language,
/// footers, the show-on-receipt toggles and the Qty/Weight label — they sync
/// down via [applyRemote] (New Sale open / [syncFromServer]) and are cached
/// for offline. The set* methods edit that shared configuration from the app
/// (PUT /settings/sale/print, needs `configuration.settings`): applied
/// optimistically and rolled back when the server rejects the save. The paper
/// width and the auto-print block (auto-print on/off, the paired printer, and
/// whether to jump straight back to New Sale) are the device-local choices —
/// each till has its own roll and its own printer.
class PrintSettingsCubit extends Cubit<PrintSettingsState> {
  PrintSettingsCubit() : super(_initialState());

  /// Built before `super`, so it cannot touch instance members.
  static PrintSettingsState _initialState() {
    final st = serviceLocator<LocalStorageService>();
    Uint8List? logo;
    try {
      final data = st.printLogoData;
      logo = data == null ? null : base64Decode(data);
    } catch (_) {
      logo = null; // corrupt cache — re-downloaded on next sync
    }
    return PrintSettingsState(
      style: PrintStyle.fromKey(st.printStyle),
      width: PaperWidth.fromKey(st.printWidth),
      quantityLabel: QuantityLabel.fromKey(st.printQuantityLabel),
      showDiscount: st.printDiscount ?? true,
      showTotalQty: st.printTotalQty ?? true,
      showBarcode: st.printBarcode ?? true,
      footerEnglish: st.printFooterEnglish ?? defaultPrintFooterEn,
      footerArabic: st.printFooterArabic ?? defaultPrintFooterAr,
      // Off until the first sync says otherwise — matches the web defaults.
      showLogo: st.printLogo ?? false,
      logoBytes: logo,
      showCompanyName: st.printShowCompany ?? false,
      companyName: st.printCompanyName ?? '',
      autoPrint: st.printAuto ?? false,
      printerUrl: st.printerUrl,
      printerName: st.printerName,
      skipInvoice: st.printSkipInvoice ?? false,
    );
  }

  LocalStorageService get _storage => serviceLocator<LocalStorageService>();
  LookupRepository get _lookup => serviceLocator<LookupRepository>();

  // Read facade over `state`.
  PrintStyle get style => state.style;
  PaperWidth get width => state.width;
  bool get showDiscount => state.showDiscount;
  bool get showTotalQty => state.showTotalQty;
  bool get showBarcode => state.showBarcode;
  String get footerEnglish => state.footerEnglish;
  String get footerArabic => state.footerArabic;
  QuantityLabel get quantityLabel => state.quantityLabel;
  bool get showLogo => state.showLogo;
  bool get showCompanyName => state.showCompanyName;
  String get companyName => state.companyName;
  bool get autoPrint => state.autoPrint;
  String? get printerUrl => state.printerUrl;
  String? get printerName => state.printerName;
  bool get skipInvoice => state.skipInvoice;
  bool get hasPrinter => state.hasPrinter;
  PrintSettings get snapshot => state.snapshot;

  // ---- device-local options (never pushed to the web config) ----

  /// Paper width — each till can hold a different roll.
  Future<void> setWidth(PaperWidth v) async {
    if (v == state.width) return;
    emit(state.copyWith(width: v));
    await _storage.setPrintWidth(v.key);
  }

  /// Print the receipt automatically the moment a sale is charged.
  Future<void> setAutoPrint(bool v) async {
    if (v == state.autoPrint) return;
    emit(state.copyWith(autoPrint: v));
    await _storage.setPrintAuto(v);
  }

  /// Pair (or, with a null [url], un-pair) the printer this till prints to.
  Future<void> setPrinter(String? url, String? name) async {
    final u = (url ?? '').trim();
    if (u.isEmpty) {
      emit(state.copyWith(clearPrinter: true));
      await _storage.clearPrinter();
      return;
    }
    final label = (name ?? '').trim().isEmpty ? u : name!.trim();
    emit(state.copyWith(printerUrl: u, printerName: label));
    await _storage.setPrinter(u, label);
  }

  /// After a successful auto-print, go straight back to a fresh ticket instead
  /// of stopping on the invoice screen.
  Future<void> setSkipInvoice(bool v) async {
    if (v == state.skipInvoice) return;
    emit(state.copyWith(skipInvoice: v));
    await _storage.setPrintSkipInvoice(v);
  }

  // ---- shared-config editors (write through to the web) ----

  /// Applies + persists [value] immediately (click-and-go), then pushes [body]
  /// to the web Sale Configuration. Rolls back and returns false when the
  /// server rejects the save (offline / no permission) so the UI can say so.
  Future<bool> _set<T>({
    required T value,
    required T current,
    required PrintSettingsState Function(PrintSettingsState, T) apply,
    required Future<void> Function(T) store,
    required Map<String, dynamic> body,
  }) async {
    if (value == current) return true;
    emit(apply(state, value));
    await store(value);
    try {
      final echo = await _lookup.savePrintSettings(body);
      await applyRemote(echo); // server-normalised values, usually a no-op
      return true;
    } catch (_) {
      emit(apply(state, current));
      await store(current);
      return false;
    }
  }

  Future<bool> setStyle(PrintStyle v) => _set(
      value: v,
      current: state.style,
      apply: (s, x) => s.copyWith(style: x),
      store: (x) => _storage.setPrintStyle(x.key),
      body: {'style': v.key});

  Future<bool> setQuantityLabel(QuantityLabel v) => _set(
      value: v,
      current: state.quantityLabel,
      apply: (s, x) => s.copyWith(quantityLabel: x),
      store: (x) => _storage.setPrintQuantityLabel(x.key),
      body: {'quantity_label': v.key});

  Future<bool> setShowDiscount(bool v) => _set(
      value: v,
      current: state.showDiscount,
      apply: (s, x) => s.copyWith(showDiscount: x),
      store: _storage.setPrintDiscount,
      body: {'show_discount': v});

  Future<bool> setShowTotalQty(bool v) => _set(
      value: v,
      current: state.showTotalQty,
      apply: (s, x) => s.copyWith(showTotalQty: x),
      store: _storage.setPrintTotalQty,
      body: {'show_total_quantity': v});

  Future<bool> setShowBarcode(bool v) => _set(
      value: v,
      current: state.showBarcode,
      apply: (s, x) => s.copyWith(showBarcode: x),
      store: _storage.setPrintBarcode,
      body: {'show_barcode': v});

  Future<bool> setShowLogo(bool v) async {
    final ok = await _set(
        value: v,
        current: state.showLogo,
        apply: (s, x) => s.copyWith(showLogo: x),
        store: _storage.setPrintLogo,
        body: {'show_logo': v});
    // Just enabled with no cached image yet — fetch it so the very next
    // receipt already carries the logo (applyRemote above only downloads
    // when the version changes).
    if (ok && v && state.logoBytes == null) {
      try {
        final bytes = await _lookup.logo();
        if (bytes.isNotEmpty) {
          emit(state.copyWith(logoBytes: bytes));
          await _storage.setPrintLogoData(base64Encode(bytes));
        }
      } catch (_) {
        // Offline — picked up by the next sync.
      }
    }
    return ok;
  }

  Future<bool> setShowCompanyName(bool v) => _set(
      value: v,
      current: state.showCompanyName,
      apply: (s, x) => s.copyWith(showCompanyName: x),
      store: _storage.setPrintShowCompany,
      body: {'show_company_name': v});

  /// Saves both footer messages in one round-trip (the edit sheet submits
  /// them together).
  Future<bool> setFooters({required String english, required String arabic}) async {
    if (english == state.footerEnglish && arabic == state.footerArabic) return true;
    final prevEn = state.footerEnglish, prevAr = state.footerArabic;
    emit(state.copyWith(footerEnglish: english, footerArabic: arabic));
    await _storage.setPrintFooterEnglish(english);
    await _storage.setPrintFooterArabic(arabic);
    try {
      final echo = await _lookup.savePrintSettings({'footer_english': english, 'footer_arabic': arabic});
      await applyRemote(echo);
      return true;
    } catch (_) {
      emit(state.copyWith(footerEnglish: prevEn, footerArabic: prevAr));
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
    var next = state;

    if (r.styleKey != null) {
      final v = PrintStyle.fromKey(r.styleKey);
      if (v != next.style) {
        next = next.copyWith(style: v);
        await _storage.setPrintStyle(v.key);
      }
    }
    if (r.showDiscount != null && r.showDiscount != next.showDiscount) {
      next = next.copyWith(showDiscount: r.showDiscount);
      await _storage.setPrintDiscount(r.showDiscount!);
    }
    if (r.showTotalQty != null && r.showTotalQty != next.showTotalQty) {
      next = next.copyWith(showTotalQty: r.showTotalQty);
      await _storage.setPrintTotalQty(r.showTotalQty!);
    }
    if (r.showBarcode != null && r.showBarcode != next.showBarcode) {
      next = next.copyWith(showBarcode: r.showBarcode);
      await _storage.setPrintBarcode(r.showBarcode!);
    }
    // An empty string is a deliberate blank footer on the web — apply it;
    // only a missing key keeps the cache/default.
    final fe = r.footerEnglish;
    if (fe != null && fe != next.footerEnglish) {
      next = next.copyWith(footerEnglish: fe);
      await _storage.setPrintFooterEnglish(fe);
    }
    final fa = r.footerArabic;
    if (fa != null && fa != next.footerArabic) {
      next = next.copyWith(footerArabic: fa);
      await _storage.setPrintFooterArabic(fa);
    }
    if (r.quantityLabelKey != null) {
      final v = QuantityLabel.fromKey(r.quantityLabelKey);
      if (v != next.quantityLabel) {
        next = next.copyWith(quantityLabel: v);
        await _storage.setPrintQuantityLabel(v.key);
      }
    }
    if (r.showLogo != null && r.showLogo != next.showLogo) {
      next = next.copyWith(showLogo: r.showLogo);
      await _storage.setPrintLogo(r.showLogo!);
    }
    if (r.showCompanyName != null && r.showCompanyName != next.showCompanyName) {
      next = next.copyWith(showCompanyName: r.showCompanyName);
      await _storage.setPrintShowCompany(r.showCompanyName!);
    }
    final company = r.companyName;
    if (company != null && company != next.companyName) {
      next = next.copyWith(companyName: company);
      await _storage.setPrintCompanyName(company);
    }
    // (Re-)download the logo image when the web one changed or the cache is
    // empty; the version is only stored on success so a failed download is
    // retried on the next sync.
    final version = r.logoVersion;
    if (next.showLogo &&
        version != null &&
        (version != _storage.printLogoVersion || next.logoBytes == null)) {
      try {
        final bytes = await _lookup.logo();
        if (bytes.isNotEmpty) {
          next = next.copyWith(logoBytes: bytes);
          await _storage.setPrintLogoData(base64Encode(bytes));
          await _storage.setPrintLogoVersion(version);
        }
      } catch (_) {
        // Offline or server error — keep the cached image.
      }
    }
    // Equatable makes this a no-op when nothing actually changed.
    if (!isClosed) emit(next);
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

}
