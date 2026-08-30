/// All local-storage key strings. Values are kept exactly as the previous
/// `astra.*` keys so existing installs keep their saved token & preferences
/// after the restructure — do NOT rename the string values.
class LocalStorageKeys {
  LocalStorageKeys._();

  // Secure (keystore / keychain).
  static const String token = 'astra.token';
  static const String biometric = 'astra.biometric';

  // Config (shared prefs).
  static const String baseUrl = 'astra.baseUrl';
  static const String tenant = 'astra.tenant';
  static const String preset = 'astra.preset';
  static const String themeMode = 'astra.themeMode';
  static const String typeface = 'astra.typeface';
  /// How the tablet window is framed — an `AstraChrome.id`. Device-local:
  /// a till bolted to a counter and a manager's iPad can differ.
  static const String chrome = 'astra.chrome';
  static const String currency = 'astra.currency';
  static const String currencies = 'astra.currencies';
  static const String baseCurrency = 'astra.baseCurrency';
  static const String defaultQuantity = 'astra.defaultQuantity';
  static const String tipEnabled = 'astra.tipEnabled';
  static const String defaultProductType = 'astra.defaultProductType';
  static const String haptics = 'astra.haptics';
  static const String branch = 'astra.branch';

  /// The branch the app last actually operated as — including one it resolved
  /// on its own, which [branch] deliberately does not record. Read only as a
  /// fallback, so a launch with no network still lands on the branch whose
  /// snapshot is on the device. See `BranchCubit`.
  static const String lastBranch = 'astra.branch.last';
  static const String user = 'astra.user';
  // New Sale — remembered choices, auto-selected on the next ticket.
  static const String saleView = 'astra.saleView'; // 'grid' | 'list'
  static const String saleType = 'astra.saleType'; // '' | 'product' | 'service'
  static const String saleStylistId = 'astra.saleStylistId';
  static const String saleStylistName = 'astra.saleStylistName';

  // Terminal lock: set while a session is alive but the till is locked, so a
  // force-quit can't walk back in past the lock screen.
  static const String authLocked = 'astra.auth.locked';

  // Point-of-sale flow — device-local.
  static const String posLockAfterSale = 'astra.pos.lockAfterSale';
  // How many product tiles the New Sale catalog grid fits across (2 | 3 | 4).
  static const String posGridColumns = 'astra.pos.gridColumns';
  /// How the New Sale category rail is drawn (a `CategoryDisplay.key`).
  /// Device-local, like the grid density beside it: whether a photo rail earns
  /// its header height depends on the screen it is on and on how much of that
  /// shop's catalog has actually been photographed.
  static const String posCategoryDisplay = 'astra.pos.categoryDisplay';
  /// Whether opening New Sale asks who the ticket is for before anything else.
  /// Device-local: a salon takes the name up front, a counter selling to a queue
  /// wants the catalog the moment the screen opens.
  static const String posAskClient = 'astra.pos.askClient';
  /// Whether the tip row is offered at checkout on THIS device. A device-local
  /// veto over the web's "Enable Tip" — a counter till that never takes tips can
  /// hide the row without turning it off for the salon's other tills. It can
  /// only hide: the web setting still has the final say. See [tipEnabled].
  static const String posShowTip = 'astra.pos.showTip';
  /// Which screen a signed-in session lands on (a `StartScreen.key`). Device-
  /// local: a counter till opens straight on the POS while the manager's own
  /// phone keeps the dashboard, on the same build and the same account.
  static const String posStartScreen = 'astra.pos.startScreen';

  // Offline selling — device-local. The tag identifies this till in the
  // provisional references it prints; the sequence numbers them.
  static const String offlineDeviceTag = 'astra.offline.deviceTag';
  static const String offlineSequence = 'astra.offline.sequence';
  /// Whether provisioning also pre-downloads product photos. Device-local
  /// because it is a storage and data-plan decision, and a tablet on shop wifi
  /// and a phone on a metered SIM should be free to answer it differently.
  static const String offlineCachePhotos = 'astra.offline.cachePhotos';

  /// Roster of users who have signed in on this device, so an offline till can
  /// authenticate a cashier who has used it before. SECURE storage only.
  static const String deviceAccounts = 'astra.auth.deviceAccounts';

  // Thermal print settings (mirror the web `thermal_printer_*` config).
  static const String printStyle = 'astra.print.style';
  static const String printWidth = 'astra.print.width';
  static const String printDiscount = 'astra.print.discount';
  static const String printTotalQty = 'astra.print.totalQty';
  static const String printBarcode = 'astra.print.barcode';
  static const String printFooterEn = 'astra.print.footerEn';
  static const String printFooterAr = 'astra.print.footerAr';
  static const String printQtyLabel = 'astra.print.qtyLabel';
  static const String printLogo = 'astra.print.logo';
  static const String printLogoVersion = 'astra.print.logoVersion';
  static const String printLogoData = 'astra.print.logoData';
  static const String printShowCompany = 'astra.print.showCompany';
  static const String printCompanyName = 'astra.print.companyName';

  // Auto-print — device-local (every till is paired with its own printer, so
  // these never sync to the shared web Sale Configuration).
  static const String printAuto = 'astra.print.auto';
  static const String printerTransport = 'astra.print.printerTransport';
  static const String printerUrl = 'astra.print.printerUrl';
  static const String printerName = 'astra.print.printerName';
  static const String printSkipInvoice = 'astra.print.skipInvoice';
}
