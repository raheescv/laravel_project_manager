/// Every route path in the app, in one place. `createRouter` declares its
/// `GoRoute`s from these and every `context.go` / `context.push` call site
/// navigates by constant — so renaming a path is a single edit the compiler
/// checks, instead of a silent runtime no-match.
class Routes {
  Routes._();

  // ---- Auth / shell ----
  static const String login = '/login';
  static const String home = '/home';

  /// The shell with a specific destination selected (tablet side rail / bottom
  /// nav both deep-link into it).
  static String homeTab(int tab) => '$home?tab=$tab';

  // ---- Sale flow ----
  static const String sale = '/sale';
  static const String cart = '/cart';
  static const String review = '/review';

  /// Requires a `Sale` in `extra`; guarded by the route's redirect.
  static const String invoice = '/invoice';
  static const String sales = '/sales';

  // ---- Sale returns ----
  static const String salesReturns = '/sales-returns';
  static const String saleReturn = '/sale-return';
  static const String saleReturnPick = '/sale-return/pick';
  static const String saleReturnReview = '/sale-return/review';

  /// Requires a `SaleReturn` in `extra`; guarded by the route's redirect.
  static const String returnReceipt = '/return-receipt';

  // ---- Stock check ----
  static const String stockCheck = '/stock-check';
  static const String stockCheckNew = '/stock-check/new';

  /// Requires a `StockCheckDetail` in `extra`; guarded by the route's redirect.
  static const String stockCheckCount = '/stock-check/count';

  // ---- Account / settings ----
  static const String profile = '/profile';
  static const String editProfile = '/edit-profile';
  static const String changePin = '/change-pin';
  static const String changePassword = '/change-password';
  static const String daySession = '/day-session';
  static const String printSettings = '/print-settings';
  static const String permissions = '/permissions';
}
