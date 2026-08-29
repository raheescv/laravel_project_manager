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

  /// Sales taken while offline that the server has not acknowledged yet.
  static const String pendingSales = '/pending-sales';

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

  /// What the till holds for selling with no network, and the controls for it.
  static const String offlineData = '/offline-data';
}

/// Where a signed-in session lands.
///
/// Device-local (Settings → Start screen), because it is a decision about *this
/// terminal*, not this account: the counter tablet a cashier rings tickets on
/// wants to open on the POS, while the same manager's own phone — often the
/// same login — still wants the dashboard. Storing it on the server would make
/// the two fight over one value.
enum StartScreen {
  home('home', 'Dashboard', 'Today\'s figures and the module tiles', Routes.home),
  sale('sale', 'New Sale', 'Straight into the POS, ready to ring a ticket', Routes.sale),
  // The Sales tab *inside* the shell, not the standalone `/sales` route: landed
  // on directly that one has no bottom nav and nothing to go back to, which
  // strands the user on the screen they chose to start from.
  sales('sales', 'Sales', 'The invoice list, with the rest of the app around it',
      '${Routes.home}?tab=1');

  const StartScreen(this.key, this.label, this.blurb, this.location);

  /// Stored in local storage — never rename a value, older installs hold them.
  final String key;
  final String label;
  final String blurb;

  /// The route this option lands on.
  final String location;

  /// True when the destination lives inside the home shell, which the router
  /// only lets through for an account that can see the dashboard.
  bool get needsDashboard => location.startsWith(Routes.home);

  static StartScreen fromKey(String? k) =>
      StartScreen.values.firstWhere((s) => s.key == k, orElse: () => StartScreen.home);

  /// Where to actually go. A till set to the dashboard whose cashier can't open
  /// one — a permission revoked since, or a shared device signed into by
  /// somebody else — falls back to New Sale rather than bouncing off `/home`.
  String resolve({required bool canViewDashboard}) =>
      needsDashboard && !canViewDashboard ? Routes.sale : location;

  /// The options worth offering this account, in display order.
  static List<StartScreen> optionsFor({required bool canViewDashboard}) =>
      StartScreen.values.where((s) => canViewDashboard || !s.needsDashboard).toList();
}
