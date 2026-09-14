import 'dart:typed_data';

import 'package:invo/shared/domain/models/index.dart';

abstract class AdminRepository {
  Future<DashboardData> dashboard({int? branchId});

  Future<Map<String, dynamic>> report({
    required String type,
    String? startDate,
    String? endDate,
    int? page,
    int? perPage,
    String? sort,
    String? productType,
  });

  Future<DaySessionToggleResult> toggleDay(String dateTime);

  /// The branch's day-session state as the server holds it right now.
  Future<DayStatus> dayStatus();

  /// The session the Sale Bill Report is for: the branch's open session, or the
  /// one opened last once the day is shut. Null before any day was opened.
  Future<DaySessionSummary?> currentDaySession();

  /// One session's Sale Bill Report figures, laid out on the thermal roll.
  Future<DaySessionReport> daySessionReport(String id);

  /// The web A4 Sale Bill Report for one session, as PDF bytes.
  Future<Uint8List> daySessionReportPdf(String id);
}
