import 'package:invo/shared/api/end_points.dart';
import 'package:invo/shared/domain/constants/global_variables.dart';
import 'package:invo/shared/domain/models/index.dart';
import 'package:invo/shared/utils/router/http_utils/http_service.dart';

import '../repository/admin_repository.dart';

class AdminService implements AdminRepository {
  HttpService get _http => serviceLocator<HttpService>();

  @override
  Future<DashboardData> dashboard({int? branchId}) async {
    final data = await _http.get(EndPoints.dashboard, query: {
      if (branchId != null) 'branch_id': branchId,
    });
    return DashboardData.fromJson(Map<String, dynamic>.from(data));
  }

  @override
  Future<Map<String, dynamic>> report({
    required String type,
    String? startDate,
    String? endDate,
    int? page,
    int? perPage,
    String? sort,
    String? productType,
  }) async {
    final data = await _http.get(EndPoints.reports, query: {
      'type': type,
      if (startDate != null) 'startDate': startDate,
      if (endDate != null) 'endDate': endDate,
      if (page != null) 'page': page,
      if (perPage != null) 'per_page': perPage,
      if (sort != null) 'sort': sort,
      if (productType != null) 'product_type': productType,
    });
    return Map<String, dynamic>.from(data);
  }

  @override
  Future<DaySessionToggleResult> toggleDay(String dateTime) async {
    final data = await _http.post(EndPoints.dayStatus, body: {'date': dateTime});
    return DaySessionToggleResult.fromJson(Map<String, dynamic>.from(data));
  }
}
