import 'package:invo/shared/domain/constants/global_variables.dart';
import 'package:invo/shared/logic/base/holder_cubit.dart';
import 'package:invo/shared/utils/router/http_utils/common_exception.dart';

import '../../domain/models/technician_models.dart';
import '../../domain/repository/technician_repository.dart';

/// Backs the technician dashboard: KPI counts, priority breakdown and recent
/// complaints for the signed-in technician.
class TechnicianDashboardCubit extends HolderCubit {
  TechnicianRepository get _repo => serviceLocator<TechnicianRepository>();

  bool loading = false;
  String? error;
  TechnicianDashboard? data;

  Future<void> load() async {
    loading = true;
    error = null;
    refresh();
    try {
      data = await _repo.dashboard();
    } on ApiException catch (e) {
      error = e.message;
    } catch (_) {
      error = 'Could not load your dashboard.';
    }
    loading = false;
    refresh();
  }
}
