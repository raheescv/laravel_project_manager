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
}
