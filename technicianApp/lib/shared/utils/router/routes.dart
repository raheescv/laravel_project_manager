/// Route paths for screens reached by `context.go` / `context.push`. The
/// pattern constants are what `app_router.dart` registers.
class Routes {
  Routes._();

  static const String login = '/login';
  static const String home = '/home';
  static String homeTab(int tab) => '$home?tab=$tab';

  /// Shell tab indexes (see TechnicianShell / `technicianTabs`).
  static const int dashboardTabIndex = 0;
  static const int jobsTabIndex = 1;
  static const int checklistsTabIndex = 2;
  static const int settingsTabIndex = 3;
  static const String checklistsTab = '$home?tab=$checklistsTabIndex';

  /// "My Jobs" as a deep link.
  static const String complaints = '/complaints';
  static const String complaintDetailPattern = '/complaints/:id';
  static String complaintDetail(int id) => '/complaints/$id';

  static const String checklistDetailPattern = '/checklists/:id';
  static String checklistDetail(int id, {String? phase}) =>
      phase == null || phase.isEmpty ? '/checklists/$id' : '/checklists/$id?phase=$phase';

  // ---- Account ----
  static const String profile = '/profile';
  static const String editProfile = '/edit-profile';
  static const String changePin = '/change-pin';
  static const String changePassword = '/change-password';
}
