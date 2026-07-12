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

  // ---- Catalog ----
  static const String products = '/products';
  static const String categories = '/categories';
  static const String branches = '/branches';
  static const String customers = '/customers';
  static const String employees = '/employees';
  static const String paymentMethods = '/payment-methods';

  // ---- Currencies ----
  static const String currencies = '/settings/currencies';

  // ---- Sales ----
  static const String sale = '/sale';
  static String saleById(String id) => '/sale/$id';
  static String saleReceipt(String id) => '/sale/$id/receipt';

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
}
