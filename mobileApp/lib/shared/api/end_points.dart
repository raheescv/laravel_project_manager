/// Every `api/v1` endpoint path used by the app, relative to `config.apiV1`
/// (the base URL is prepended by [HttpService]). Keeping them here removes the
/// scattered string literals the feature services used to carry.
class EndPoints {
  EndPoints._();

  // ---- Auth ----
  static const String login = '/login';
  static const String logout = '/logout';
  static const String changePin = '/change-pin';
  static const String changePassword = '/change-password';

  // ---- Profile ----
  static const String profile = '/profile';
  static const String profilePhoto = '/profile/photo';

  // ---- Catalog ----
  static const String products = '/products';
  static const String categories = '/categories';
  static const String branches = '/branches';
  static const String customers = '/customers';
  static const String employees = '/employees';
  static const String paymentMethods = '/payment-methods';

  // ---- Settings ----
  static const String currencies = '/settings/currencies';
  static const String saleSettings = '/settings/sale';
  static const String printSettings = '/settings/sale/print';
  static const String logo = '/settings/logo';

  // ---- Diagnostics ----
  /// Where CrashReporter posts uncaught client errors.
  static const String clientError = '/client-error';

  // ---- Sales ----
  static const String sale = '/sale';
  static String saleById(String id) => '/sale/$id';
  static String saleReceipt(String id) => '/sale/$id/receipt';

  /// This till reports its offline queue and reads the rest of the branch's, so
  /// day close can see sales held on a device other than the one cashing up.
  static const String offlineTillState = '/offline-till-state';

  // ---- Sale Returns ----
  static const String saleReturn = '/sale-return';
  static String saleReturnById(String id) => '/sale-return/$id';
  static String returnableSale(String saleId) => '/sale-return/from-sale/$saleId';

  // ---- Admin ----
  static const String dashboard = '/admin/dashboard';
  static const String reports = '/admin/reports';
  static const String dayStatus = '/admin/day-status';

  // ---- Stock Check ----
  static const String stockCheck = '/stock-check';
  static String stockCheckById(int id) => '/stock-check/$id';
  static String stockCheckItems(int id) => '/stock-check/$id/items';
  static String stockCheckScan(int id) => '/stock-check/$id/scan';
  static String stockCheckStatus(int id) => '/stock-check/$id/status';
}
