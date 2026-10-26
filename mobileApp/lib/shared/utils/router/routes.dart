/// Every route path in the app, in one place. `createRouter` declares its
/// `GoRoute`s from these and every `context.go` / `context.push` call site
/// navigates by constant — so renaming a path is a single edit the compiler
/// checks, instead of a silent runtime no-match.
class Routes {
  Routes._();

  // ---- Auth / shell ----
  static const String login = '/login';

  /// Which branch this session works as — held here after a sign-in (or an
  /// unlock) by a user with more than one branch. See `AuthState.branchPending`.
  static const String selectBranch = '/select-branch';
  static const String home = '/home';

  /// A user's first sign-in on this device asks where they want to land
  /// before landing anywhere. See `PosSettingsCubit.needsStartScreenFor`.
  static const String chooseStartScreen = '/choose-start-screen';

  /// The shell with a specific destination selected (tablet side rail / bottom
  /// nav both deep-link into it).
  static String homeTab(int tab) => '$home?tab=$tab';

  // ---- Sale flow ----
  static const String sale = '/sale';
  static const String cart = '/cart';
  static const String review = '/review';

  /// Where the sale flow sends someone who can't open the day themselves while
  /// it is closed — "ask your admin". See `DayGate`.
  static const String dayClosed = '/day-closed';

  /// Day Session opened *on the way to* New Sale: it says why they are there and
  /// carries on to the POS once the day is open. See `DayGate`.
  static const String daySessionForSale = '$daySession?for=sale';

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
  /// Link / replace a student's NFC card (`student card.assign`).
  static const String linkStudentCard = '/student-card/link';
  static const String stockCheck = '/stock-check';
  static const String stockCheckNew = '/stock-check/new';

  /// Requires a `StockCheckDetail` in `extra`; guarded by the route's redirect.
  static const String stockCheckCount = '/stock-check/count';

  // ---- Queue tokens (`sale.queue token`) ----
  static const String token = '/token';
  static const String tokenSettings = '/token/settings';

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
/// Device-local and per user (Settings → Start screen), because it is a
/// decision about *this terminal and this person*: the counter tablet a cashier
/// rings tickets on wants to open on the POS, while the manager signing in on
/// the same till — or on their own phone — still wants the dashboard. Storing
/// it on the server would make two devices fight over one value.
enum StartScreen {
  home('home', 'Dashboard', 'Today\'s figures and the module tiles', Routes.home),
  sale('sale', 'New Sale', 'Straight into the POS, ready to ring a ticket', Routes.sale),
  // The Sales tab *inside* the shell, not the standalone `/sales` route: landed
  // on directly that one has no bottom nav and nothing to go back to, which
  // strands the user on the screen they chose to start from.
  sales('sales', 'Sales', 'The invoice list, with the rest of the app around it',
      '${Routes.home}?tab=1'),
  // The shell's Reports tab, for the same reason. It opens on whichever report
  // (Overview / Breakdown) the user last looked at.
  reports('reports', 'Reports', 'Sales reports, on the tab you left them',
      '${Routes.home}?tab=2');

  const StartScreen(this.key, this.label, this.blurb, this.location);

  /// Stored in local storage — never rename a value, older installs hold them.
  final String key;
  final String label;
  final String blurb;

  /// The route this option lands on.
  final String location;

  static StartScreen fromKey(String? k) =>
      StartScreen.values.firstWhere((s) => s.key == k, orElse: () => StartScreen.home);

  /// The options an account can actually land on — Reports needs the report
  /// permission, everything else is open to every signed-in user.
  static List<StartScreen> optionsFor({required bool canViewReports}) =>
      [for (final s in values) if (s != reports || canViewReports) s];

  /// Where this option lands for an account: one that has lost the report
  /// permission since picking Reports opens on the dashboard instead of a
  /// "restricted" page.
  String resolve({required bool canViewReports}) =>
      this == reports && !canViewReports ? home.location : location;
}
