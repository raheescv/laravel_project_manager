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

  // ---- Technician (maintenance workflow) ----
  static const String technicianDashboard = '/technician/dashboard';
  static const String technicianComplaints = '/technician/complaints';
  static String technicianComplaint(int id) => '/technician/complaints/$id';
  static String technicianComplete(int id) =>
      '/technician/complaints/$id/complete';
  static String technicianSupplyItems(int id) =>
      '/technician/complaints/$id/supply-items';
  static String technicianSupplyItem(int itemId) =>
      '/technician/supply-items/$itemId';
  static String technicianNotes(int id) => '/technician/complaints/$id/notes';
  static String technicianNote(int noteId) => '/technician/notes/$noteId';
  static String technicianAttachments(int id) =>
      '/technician/complaints/$id/attachments';
  static String technicianAttachment(int imageId) =>
      '/technician/attachments/$imageId';
}
